@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.daily_sales_report', [], session('locale')) ?: 'Daily Sales Report' }}</title>
@endpush

<script>
window.dailySalesReport = function() {
  return {
    dataUrl: '{{ route('reports.daily_sales.data') }}',
    fromDate: '{{ date('Y-m-d') }}',
    toDate: '{{ date('Y-m-d') }}',
    rows: [],
    totals: {},
    loading: false,
    error: null,
    loaded: false,

    currentPage: 1,
    lastPage: 1,

    async loadReport(page) {
      page = page ?? 1;
      this.loading = true;
      this.error = null;
      const params = new URLSearchParams({ from_date: this.fromDate || '', to_date: this.toDate || '', page: String(page) });
      try {
        const res = await fetch(this.dataUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const d = await res.json();
        if (!res.ok || !d.success) { this.error = d.message || 'Error loading data'; this.rows = []; return; }
        this.rows = d.rows || [];
        this.totals = d.totals || {};
        this.currentPage = Number(d.current_page) || 1;
        this.lastPage = Math.max(1, Number(d.last_page) || 1);
      } catch (e) { this.error = e.message || 'Error loading data'; this.rows = []; }
      finally { this.loading = false; this.loaded = true; }
    },

    goToPage(p) { if (p < 1 || p > this.lastPage) return; this.loadReport(p); },

    get pageList() {
      const L = this.lastPage, C = this.currentPage;
      if (L <= 1) return [];
      if (L <= 7) return Array.from({ length: L }, (_, i) => i + 1);
      if (C <= 4) return [1, 2, 3, 4, 5, '...', L];
      if (C >= L - 3) return [1, '...', L - 4, L - 3, L - 2, L - 1, L];
      return [1, '...', C - 1, C, C + 1, '...', L];
    },

    formatNum(n) { return Number(n ?? 0).toFixed(3); },

    get exportExcelUrl() {
      let u = '{{ route('reports.daily_sales.export_excel') }}';
      if (this.fromDate) u += (u.includes('?') ? '&' : '?') + 'from_date=' + encodeURIComponent(this.fromDate);
      if (this.toDate) u += (u.includes('?') ? '&' : '?') + 'to_date=' + encodeURIComponent(this.toDate);
      return u;
    }
  };
};
</script>

<main class="flex-1 p-6 md:p-8 bg-gradient-to-b from-slate-50 to-slate-100 dark:from-gray-900 dark:to-gray-800 overflow-y-auto" x-data="dailySalesReport()" x-init="loadReport()">
    <div class="max-w-7xl mx-auto space-y-6">
        {{-- Header --}}
        <div class="relative bg-gradient-to-r from-indigo-600 to-violet-700 rounded-2xl shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0djItSDI0di0yaDEyek0zNiAyNHYySDI0di0yaDEyeiIvPjwvZz48L2c+PC9zdmc+')] opacity-40"></div>
            <div class="relative px-6 py-5 md:py-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 backdrop-blur">
                            <span class="material-symbols-outlined text-2xl text-white">calendar_view_month</span>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white tracking-tight">
                                {{ trans('messages.daily_sales_report', [], session('locale')) ?: 'Daily Sales Report' }}
                            </h1>
                            <p class="mt-0.5 text-sm text-indigo-100">
                                @if(session('locale') == 'ar')
                                    طلبات خاصة، نقطة البيع، وتسوية — لكل يوم
                                @else
                                    Special Orders, POS & Settlement — per day
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters + Export --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200/80 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg text-indigo-500">tune</span>
                    @if(session('locale') == 'ar') الفلاتر @else Filters @endif
                </h2>
            </div>
            <div class="p-5 flex flex-col md:flex-row md:items-end gap-4">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">@if(session('locale') == 'ar') من تاريخ @else From Date @endif</label>
                        <input type="date" x-model="fromDate" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">@if(session('locale') == 'ar') إلى تاريخ @else To Date @endif</label>
                        <input type="date" x-model="toDate" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="loadReport(1)" :disabled="loading" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed transition shadow-md">
                        <span class="material-symbols-outlined text-lg" :class="loading ? 'animate-spin' : ''">search</span>
                        @if(session('locale') == 'ar') بحث @else Filter @endif
                    </button>
                    <a :href="exportExcelUrl" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition shadow-md">
                        <span class="material-symbols-outlined text-lg">file_download</span>
                        @if(session('locale') == 'ar') تصدير إكسل @else Export Excel @endif
                    </a>
                </div>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4" x-show="Object.keys(totals).length > 0" x-transition>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-200/80 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">@if(session('locale') == 'ar') إجمالي القطع @else Total Items Sold @endif</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white" x-text="totals.total_items || 0">0</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/40">
                        <span class="material-symbols-outlined text-2xl text-amber-600 dark:text-amber-400">inventory_2</span>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-200/80 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">@if(session('locale') == 'ar') إجمالي المبيعات @else Total Sales @endif</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400" x-text="formatNum(totals.total_sales)">0.000</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/40">
                        <span class="material-symbols-outlined text-2xl text-emerald-600 dark:text-emerald-400">payments</span>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-200/80 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">@if(session('locale') == 'ar') إجمالي الربح @else Total Profit @endif</p>
                        <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400" x-text="formatNum(totals.total_profit)">0.000</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/40">
                        <span class="material-symbols-outlined text-2xl text-indigo-600 dark:text-indigo-400">trending_up</span>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-200/80 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">@if(session('locale') == 'ar') طلبات خاصة @else Special Orders @endif</p>
                        <p class="mt-1 text-2xl font-bold text-violet-600 dark:text-violet-400" x-text="totals.so_orders || 0">0</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-900/40">
                        <span class="material-symbols-outlined text-2xl text-violet-600 dark:text-violet-400">receipt_long</span>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-200/80 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">@if(session('locale') == 'ar') طلبات نقطة البيع @else POS Orders @endif</p>
                        <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="totals.pos_orders || 0">0</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/40">
                        <span class="material-symbols-outlined text-2xl text-blue-600 dark:text-blue-400">point_of_sale</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200/80 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg text-indigo-500">table_chart</span>
                    @if(session('locale') == 'ar') المبيعات حسب اليوم @else Sales by day @endif
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/60">
                            <th class="px-4 py-3.5 text-left font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap border-b border-gray-200 dark:border-gray-600">@if(session('locale') == 'ar') التاريخ @else Date @endif</th>
                            <th colspan="4" class="px-3 py-3 text-center font-semibold text-violet-700 dark:text-violet-300 border-l border-gray-200 dark:border-gray-600 bg-violet-50/50 dark:bg-violet-900/20">@if(session('locale') == 'ar') طلبات خاصة @else Special Orders @endif</th>
                            <th colspan="4" class="px-3 py-3 text-center font-semibold text-blue-700 dark:text-blue-300 border-l border-gray-200 dark:border-gray-600 bg-blue-50/50 dark:bg-blue-900/20">POS</th>
                            <th colspan="3" class="px-3 py-3 text-center font-semibold text-amber-700 dark:text-amber-300 border-l border-gray-200 dark:border-gray-600 bg-amber-50/50 dark:bg-amber-900/20">@if(session('locale') == 'ar') التسوية @else Settlement @endif</th>
                            <th colspan="3" class="px-3 py-3 text-center font-semibold text-gray-700 dark:text-gray-300 border-l border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/80">@if(session('locale') == 'ar') الإجمالي @else Total @endif</th>
                        </tr>
                        <tr class="bg-gray-50/80 dark:bg-gray-700/40">
                            <th class="px-2 py-2.5 border-b border-gray-200 dark:border-gray-600 w-24 max-w-[6.5rem]"></th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 border-l border-gray-200 dark:border-gray-600 w-14">Ord</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-14">Itm</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-20">Sales</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-20">Profit</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 border-l border-gray-200 dark:border-gray-600 w-14">Ord</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-14">Itm</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-20">Sales</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-20">Profit</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 border-l border-gray-200 dark:border-gray-600 w-14">Itm</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-20">Sales</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-20">Profit</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 border-l border-gray-200 dark:border-gray-600 w-14">Itm</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-20">Sales</th>
                            <th class="px-3 py-2.5 text-center text-xs font-medium text-gray-600 dark:text-gray-400 w-20">Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-if="loading">
                            <tr>
                                <td colspan="15" class="px-4 py-12 text-center">
                                    <div class="inline-flex items-center gap-2 text-gray-500 dark:text-gray-400">
                                        <span class="material-symbols-outlined animate-spin text-2xl">progress_activity</span>
                                        @if(session('locale') == 'ar') جاري التحميل... @else Loading... @endif
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && error">
                            <tr>
                                <td colspan="15" class="px-4 py-8 text-center text-red-500 font-medium" x-text="error"></td>
                            </tr>
                        </template>
                        <template x-if="!loading && !error && (!rows || rows.length === 0) && loaded">
                            <tr>
                                <td colspan="15" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">@if(session('locale') == 'ar') لا توجد بيانات @else No data found @endif</td>
                            </tr>
                        </template>
                        <template x-if="!loading && !error && rows && rows.length > 0">
                            <template x-for="(r, i) in rows" :key="r.date">
                                <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors" :class="i % 2 === 1 ? 'bg-gray-50/50 dark:bg-gray-800/50' : ''">
                                    <td class="px-2 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap w-24 max-w-[6.5rem]" x-text="r.date"></td>
                                    <td class="px-3 py-3 text-center border-l border-gray-100 dark:border-gray-700" x-text="r.so_orders"></td>
                                    <td class="px-3 py-3 text-center" x-text="r.so_items"></td>
                                    <td class="px-3 py-3 text-center font-medium text-emerald-600 dark:text-emerald-400" x-text="formatNum(r.so_sales)"></td>
                                    <td class="px-3 py-3 text-center font-medium text-indigo-600 dark:text-indigo-400" x-text="formatNum(r.so_profit)"></td>
                                    <td class="px-3 py-3 text-center border-l border-gray-100 dark:border-gray-700" x-text="r.pos_orders"></td>
                                    <td class="px-3 py-3 text-center" x-text="r.pos_items"></td>
                                    <td class="px-3 py-3 text-center font-medium text-emerald-600 dark:text-emerald-400" x-text="formatNum(r.pos_sales)"></td>
                                    <td class="px-3 py-3 text-center font-medium text-indigo-600 dark:text-indigo-400" x-text="formatNum(r.pos_profit)"></td>
                                    <td class="px-3 py-3 text-center border-l border-gray-100 dark:border-gray-700" x-text="r.settlement_items"></td>
                                    <td class="px-3 py-3 text-center font-medium text-emerald-600 dark:text-emerald-400" x-text="formatNum(r.settlement_sales)"></td>
                                    <td class="px-3 py-3 text-center font-medium text-indigo-600 dark:text-indigo-400" x-text="formatNum(r.settlement_profit)"></td>
                                    <td class="px-3 py-3 text-center border-l border-gray-100 dark:border-gray-700 font-semibold text-gray-900 dark:text-white" x-text="r.total_items"></td>
                                    <td class="px-3 py-3 text-center font-semibold text-emerald-600 dark:text-emerald-400" x-text="formatNum(r.total_sales)"></td>
                                    <td class="px-3 py-3 text-center font-semibold text-indigo-700 dark:text-indigo-300" x-text="formatNum(r.total_profit)"></td>
                                </tr>
                            </template>
                        </template>
                        <template x-if="!loading && !loaded && !error">
                            <tr>
                                <td colspan="15" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">@if(session('locale') == 'ar') اختر التواريخ واضغط بحث @else Select dates and click Filter @endif</td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot x-show="!loading && rows && rows.length > 0 && Object.keys(totals).length > 0">
                        <tr class="bg-indigo-50 dark:bg-indigo-900/30 font-semibold border-t-2 border-indigo-200 dark:border-indigo-800">
                            <td class="px-2 py-3.5 text-gray-900 dark:text-white w-24 max-w-[6.5rem]">@if(session('locale') == 'ar') الإجمالي @else Total @endif</td>
                            <td class="px-3 py-3.5 text-center border-l border-indigo-200 dark:border-indigo-800" x-text="totals.so_orders"></td>
                            <td class="px-3 py-3.5 text-center" x-text="totals.so_items"></td>
                            <td class="px-3 py-3.5 text-center text-emerald-600 dark:text-emerald-400" x-text="formatNum(totals.so_sales)"></td>
                            <td class="px-3 py-3.5 text-center text-indigo-600 dark:text-indigo-400" x-text="formatNum(totals.so_profit)"></td>
                            <td class="px-3 py-3.5 text-center border-l border-indigo-200 dark:border-indigo-800" x-text="totals.pos_orders"></td>
                            <td class="px-3 py-3.5 text-center" x-text="totals.pos_items"></td>
                            <td class="px-3 py-3.5 text-center text-emerald-600 dark:text-emerald-400" x-text="formatNum(totals.pos_sales)"></td>
                            <td class="px-3 py-3.5 text-center text-indigo-600 dark:text-indigo-400" x-text="formatNum(totals.pos_profit)"></td>
                            <td class="px-3 py-3.5 text-center border-l border-indigo-200 dark:border-indigo-800" x-text="totals.settlement_items"></td>
                            <td class="px-3 py-3.5 text-center text-emerald-600 dark:text-emerald-400" x-text="formatNum(totals.settlement_sales)"></td>
                            <td class="px-3 py-3.5 text-center text-indigo-600 dark:text-indigo-400" x-text="formatNum(totals.settlement_profit)"></td>
                            <td class="px-3 py-3.5 text-center border-l border-indigo-200 dark:border-indigo-800" x-text="totals.total_items"></td>
                            <td class="px-3 py-3.5 text-center text-emerald-600 dark:text-emerald-400" x-text="formatNum(totals.total_sales)"></td>
                            <td class="px-3 py-3.5 text-center text-indigo-700 dark:text-indigo-300" x-text="formatNum(totals.total_profit)"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            {{-- Pagination --}}
            <div class="flex justify-center py-4 border-t border-gray-100 dark:border-gray-700" x-show="lastPage > 1">
                <ul class="flex flex-wrap gap-1.5 items-center justify-center list-none p-0 m-0">
                    <li>
                        <button @click.prevent="goToPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-2.5 py-1.5 rounded-lg border text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">«</button>
                    </li>
                    <template x-for="(p, i) in pageList" :key="p === '...' ? 'e'+i : p">
                        <li>
                            <template x-if="p === '...'">
                                <span class="px-2 py-1 text-gray-500">…</span>
                            </template>
                            <template x-if="p !== '...'">
                                <button @click.prevent="goToPage(p)" :class="p === currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'" class="px-3 py-1.5 border rounded-lg text-sm font-medium transition" x-text="p"></button>
                            </template>
                        </li>
                    </template>
                    <li>
                        <button @click.prevent="goToPage(currentPage + 1)" :disabled="currentPage >= lastPage" class="px-2.5 py-1.5 rounded-lg border text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">»</button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</main>

@include('layouts.footer')
@endsection
