# Server-Side Pagination with Laravel + Alpine.js

This guide covers how pagination works in the reports (`/reports/pos-income`, `/reports/special-orders-income`, `/reports/settlement-profit`) and how to avoid common mistakes.

---

## 1. Controller: Using Laravel `paginate()`

Each report has a **page** endpoint that returns JSON. The controller uses `paginate()` and returns `current_page`, `last_page`, `total`, and `per_page` so the frontend can build pagination links.

### Example (POS Income) – `ReportController::getPosIncomeReport`

```php
$page = $request->input('page', 1);

$query = PosOrders::with('details');
if ($fromDate) $query->whereDate('created_at', '>=', $fromDate);
if ($toDate)   $query->whereDate('created_at', '<=', $toDate);
$query->orderBy('created_at', 'DESC');

// Paginate: 10 per page, use 'page' as the query param
$orders = $query->paginate(10, ['*'], 'page', $page);

// Map to formatted array, then:
return response()->json([
    'success'      => true,
    'orders'       => $formattedOrders,
    'totals'       => $totals,
    'current_page' => $orders->currentPage(),
    'last_page'    => $orders->lastPage(),
    'total'        => $orders->total(),
    'per_page'     => $orders->perPage(),
]);
```

- **Special Orders** uses `paginate(15, ['*'], 'page', $page)` and returns `orders`, `totals`, `current_page`, `last_page`, `total`, `per_page`.
- **Settlement** uses `paginate(10, ['*'], 'page', $page)` and returns `settlements`, `totals`, and the same pagination fields.

---

## 2. Blade: Pagination Links with Alpine.js

Pagination is fully driven by Alpine: the table and the pagination bar are inside one `x-data` component. The backend only returns the current page of rows plus `current_page` and `last_page`.

### 2.1 Root element and `x-init`

```html
<main x-data="posIncomeReport()" x-init="loadReport(1)">
```

- `x-data` defines the Alpine component (state + methods).
- `x-init="loadReport(1)"` loads the first page on mount.

### 2.2 Table body: `x-for` for rows

Rows are bound to an `items` array that `loadReport()` updates from the API:

```html
<tbody>
  <template x-if="loading">...</template>
  <template x-if="!loading && error">...</template>
  <template x-if="!loading && !error && (!items || items.length === 0) && loaded">...</template>
  <template x-if="!loading && !error && items && items.length > 0">
    <template x-for="order in items" :key="order.id">
      <tr>...</tr>
    </template>
  </template>
</tbody>
```

- Use `:key="order.id"` (or a unique field) so Alpine can track and re-render correctly.

### 2.3 Pagination links: `@click.prevent` + `goToPage` / `loadReport`

Pagination is shown only when `lastPage > 1` and is built from a getter `pageList` (e.g. `[1,2,3,'...',10]`). Clicks call `goToPage(p)` (which in turn calls `loadReport(p)`), and the table updates from the new API response.

```html
<div x-show="lastPage > 1">
  <ul class="flex gap-2 list-none">
    <li>
      <button @click.prevent="goToPage(currentPage - 1)" :disabled="currentPage <= 1">«</button>
    </li>
    <template x-for="(p, i) in pageList" :key="p === '...' ? 'e'+i : p">
      <li>
        <template x-if="p === '...'"><span>…</span></template>
        <template x-if="p !== '...'">
          <button @click.prevent="goToPage(p)" :class="p === currentPage ? 'active' : ''" x-text="p"></button>
        </template>
      </li>
    </template>
    <li>
      <button @click.prevent="goToPage(currentPage + 1)" :disabled="currentPage >= lastPage">»</button>
    </li>
  </ul>
</div>
```

- `@click.prevent` stops navigation so we only run `goToPage` / `loadReport` and avoid `#` in the URL.
- `goToPage` should guard invalid pages (e.g. `if (page < 1 || page > this.lastPage) return`) and then call `loadReport(page)`.

---

## 3. Integrating Pagination with Alpine.js

### 3.1 State

Keep at least:

- `items` – current page of rows (from API).
- `currentPage`, `lastPage` – from API.
- `loading` – true while `fetch` runs.
- `error` – message on failure.
- `loaded` – true after the first successful load (to distinguish “no data” from “not loaded yet”).

Filters (e.g. `fromDate`, `toDate`, `boutiqueId`) are also in `x-data` and sent in the same request as `page`.

### 3.2 `loadReport(page)`

1. `loading = true`, `error = null`.
2. Build query: `page`, `from_date`, `to_date`, and any other filters.
3. `fetch(dataUrl + '?' + params)` with `Accept: application/json` and `X-Requested-With: XMLHttpRequest` (optional, for Laravel).
4. `const d = await res.json()`.
5. If `!res.ok` or `!d.success`: set `error`, clear `items`, then `finally { loading = false; loaded = true }`.
6. Else:  
   `items = d.orders` (or `d.settlements`),  
   `totals = d.totals`,  
   `currentPage = d.current_page`,  
   `lastPage = Math.max(1, d.last_page)`,  
   `error = null`,  
   and in `finally`: `loading = false`, `loaded = true`.

### 3.3 `goToPage(p)`

```js
goToPage(page) {
  if (page < 1 || page > this.lastPage) return;
  this.loadReport(page);
}
```

### 3.4 `pageList` getter

Example for a compact list (e.g. 1 … 4 5 6 … 10):

```js
get pageList() {
  const L = this.lastPage, C = this.currentPage;
  if (L <= 1) return [];
  if (L <= 7) return Array.from({ length: L }, (_, i) => i + 1);
  if (C <= 4) return [1, 2, 3, 4, 5, '...', L];
  if (C >= L - 3) return [1, '...', L - 4, L - 3, L - 2, L - 1, L];
  return [1, '...', C - 1, C, C + 1, '...', L];
}
```

Use `'...'` as a non-clickable placeholder and give it a stable `:key` (e.g. `'e'+i`) in `x-for`.

### 3.5 Defining the component

The component is attached to `window` so Alpine can resolve it by name:

```html
<script>
window.posIncomeReport = function() {
  return {
    dataUrl: '{{ route('reports.pos_income.data') }}',
    fromDate: '{{ date('Y-m-d') }}',
    toDate: '{{ date('Y-m-d') }}',
    items: [],
    totals: {},
    currentPage: 1,
    lastPage: 1,
    loading: false,
    error: null,
    loaded: false,
    async loadReport(page = 1) { /* ... */ },
    goToPage(page) { /* ... */ },
    get pageList() { /* ... */ },
    formatNum(n) { return Number(n ?? 0).toFixed(3); }
  };
};
</script>
```

This script must run before Alpine initializes the `x-data` (before `</body>` or before Alpine’s `defer` runs). The report views place it at the top of the main content.

---

## 4. Common Mistakes

| Mistake | Why it breaks | Fix |
|--------|----------------|-----|
| **Using `<a href="#">` for pagination without `@click.prevent`** | The `#` can change the URL or scroll; the intended handler may not run or may run and then be overridden. | Use `<button @click.prevent="goToPage(p)">` or `<a href="#" @click.prevent="goToPage(p)">`. |
| **Forgetting to send `page` in the API request** | Backend always returns page 1. | Always add `page` to the query string in `loadReport`: `params.set('page', page)` or similar. |
| **Using `$route` or full-page navigation for “next page”** | You’d load a new HTML document; the table would flicker and you’d need to manage `page` in the URL yourself. | Use `fetch` (or axios) to the JSON endpoint and update `items`, `currentPage`, `lastPage` in Alpine. |
| **Not handling `last_page === 1` or 0** | You can show a useless “1” or “Prev/Next” when there’s only one page; or `pageList` can produce invalid indices. | Use `x-show="lastPage > 1"` on the pagination block; in `pageList`, `if (L <= 1) return []`. |
| **Missing `:key` in `x-for="order in items"`** | Alpine can’t track rows; re-renders can be wrong or slow. | Use `:key="order.id"` (or another unique field). |
| **`x-for` on `<template>` with multiple roots** | Alpine’s `x-for` expects one root element per iteration. | Put one root (e.g. `<tr>`) inside the `x-for` template. |
| **Loading the wrong URL or wrong response shape** | Table stays empty or pagination state is wrong. | Ensure `dataUrl` points to the correct route and that you read `d.orders` / `d.settlements` and `d.current_page`, `d.last_page` to match the controller. |
| **Including both jQuery-based and Alpine-based JS for the same report** | Two systems update the same table/pagination; conflicts and double requests. | Use either jQuery **or** Alpine for a given report. The report Blades use only Alpine; the footer no longer includes the jQuery report JS for these. |
| **Relying on `$route_name` for reports** | For `reports.pos_income`, `$route_name` is `reports` (first segment). A condition like `$route_name == 'reports.pos_income'` never matches. | Use the full `$routeName` when you need route-specific logic (e.g. in the footer). The report Blades no longer need footer-included JS. |
| **Not setting `loaded`** | You can’t tell “no data” from “not loaded yet”, so the empty state may show too early or never. | Set `loaded = true` in the `finally` of `loadReport` after the first request. |
| **Using `last_page` from the response as 0 or undefined** | `goToPage` and `pageList` can misbehave. | Use `lastPage = Math.max(1, Number(d.last_page) || 1)`. |

---

## 5. Request/Response Shape

### Request (GET)

- `page` (required for pagination)
- `from_date`, `to_date` (and `boutique_id` for Settlement)

Example:  
`/reports/pos-income/data?page=2&from_date=2025-01-01&to_date=2025-01-31`

### Response (JSON)

```json
{
  "success": true,
  "orders": [ { "id": 1, "order_no": "000001", ... } ],
  "totals": { "total_amount": 100, "paid_amount": 90, "discount": 10, "profit": 20 },
  "current_page": 2,
  "last_page": 5,
  "total": 50,
  "per_page": 10
}
```

For Settlement, replace `orders` with `settlements` and use `totals` with `number_of_items`, `sales`, `profit`.

---

## 6. Files Touched

- **Controllers:** `ReportController::getPosIncomeReport`, `getSpecialOrdersIncomeReport`, `getSettlementProfitReport` (already use `paginate()` and return the structure above).
- **Views:**  
  - `resources/views/reports/pos_income_report.blade.php`  
  - `resources/views/reports/special_orders_income_report.blade.php`  
  - `resources/views/reports/settlement_profit_report.blade.php`  
  Each contains an Alpine `x-data` component and the pagination markup.
- **Footer:** `resources/views/layouts/footer.blade.php` – the three report-specific jQuery `custom_js` includes were removed; those reports now rely only on Alpine in the Blade.
