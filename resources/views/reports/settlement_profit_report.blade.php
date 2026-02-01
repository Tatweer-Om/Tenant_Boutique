@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.settlement_profit_report', [], session('locale')) ?: 'Settlement Profit Report' }}</title>
@endpush

<script>
window.settlementProfitReport = function() {
  return {
    dataUrl: '{{ route('reports.settlement_profit.data') }}',
    fromDate: '{{ date('Y-m-d') }}',
    toDate: '{{ date('Y-m-d') }}',
    boutiqueId: '',
    items: [],
    totals: {},
    currentPage: 1,
    lastPage: 1,
    loading: false,
    error: null,
    loaded: false,

    async loadReport(page = 1) {
      this.loading = true;
      this.error = null;
      const params = new URLSearchParams({ page: String(page), from_date: this.fromDate || '', to_date: this.toDate || '' });
      if (this.boutiqueId) params.set('boutique_id', this.boutiqueId);
      try {
        const res = await fetch(this.dataUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const d = await res.json();
        if (!res.ok || !d.success) { this.error = d.message || 'Error loading data'; this.items = []; return; }
        this.items = d.settlements || [];
        this.totals = d.totals || {};
        this.currentPage = Number(d.current_page) || 1;
        this.lastPage = Math.max(1, Number(d.last_page) || 1);
      } catch (e) { this.error = e.message || 'Error loading data'; this.items = []; }
      finally { this.loading = false; this.loaded = true; }
    },

    goToPage(page) { if (page < 1 || page > this.lastPage) return; this.loadReport(page); },

    get pageList() {
      const L = this.lastPage, C = this.currentPage;
      if (L <= 1) return [];
      if (L <= 7) return Array.from({ length: L }, (_, i) => i + 1);
      if (C <= 4) return [1, 2, 3, 4, 5, '...', L];
      if (C >= L - 3) return [1, '...', L - 4, L - 3, L - 2, L - 1, L];
      return [1, '...', C - 1, C, C + 1, '...', L];
    },

    formatNum(n) { return Number(n ?? 0).toFixed(3); },

    get exportPdfUrl() {
      let u = '{{ route('reports.settlement_profit.export_pdf') }}';
      if (this.fromDate) u += (u.includes('?') ? '&' : '?') + 'from_date=' + encodeURIComponent(this.fromDate);
      if (this.toDate) u += (u.includes('?') ? '&' : '?') + 'to_date=' + encodeURIComponent(this.toDate);
      if (this.boutiqueId) u += (u.includes('?') ? '&' : '?') + 'boutique_id=' + encodeURIComponent(this.boutiqueId);
      return u;
    },
    get exportExcelUrl() {
      let u = '{{ route('reports.settlement_profit.export_excel') }}';
      if (this.fromDate) u += (u.includes('?') ? '&' : '?') + 'from_date=' + encodeURIComponent(this.fromDate);
      if (this.toDate) u += (u.includes('?') ? '&' : '?') + 'to_date=' + encodeURIComponent(this.toDate);
      if (this.boutiqueId) u += (u.includes('?') ? '&' : '?') + 'boutique_id=' + encodeURIComponent(this.boutiqueId);
      return u;
    }
  };
};
</script>

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" x-data="settlementProfitReport()" x-init="loadReport(1)">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">{{ trans('messages.settlement_profit_report', [], session('locale')) ?: 'Settlement Profit Report' }}</h2>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-[var(--border-color)]">
            <div class="flex flex-col gap-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">@if(session('locale') == 'ar') من تاريخ @else From Date @endif</label>
                        <input type="date" x-model="fromDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">@if(session('locale') == 'ar') إلى تاريخ @else To Date @endif</label>
                        <input type="date" x-model="toDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">@if(session('locale') == 'ar') البوتيك @else Boutique @endif</label>
                        <select x-model="boutiqueId" @change="loadReport(1)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                            <option value="">@if(session('locale') == 'ar') جميع البوتيكات @else All Boutiques @endif</option>
                            @foreach($boutiques as $boutique)
                                <option value="{{ $boutique->id }}">{{ $boutique->boutique_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button @click="loadReport(1)" :disabled="loading" class="w-full px-6 py-3 bg-primary text-white rounded-lg hover:bg-accent transition flex items-center justify-center gap-2 disabled:opacity-70">
                            <span class="material-symbols-outlined" :class="loading ? 'animate-spin' : ''">search</span>
                            @if(session('locale') == 'ar') بحث @else Filter @endif
                        </button>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a :href="exportPdfUrl" target="_blank" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                        <span class="material-symbols-outlined">picture_as_pdf</span>
                        {{ trans('messages.export_pdf', [], session('locale')) ?: 'Export PDF' }}
                    </a>
                    <a :href="exportExcelUrl" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                        <span class="material-symbols-outlined">file_download</span>
                        {{ trans('messages.export_excel', [], session('locale')) ?: 'Export Excel' }}
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" x-show="Object.keys(totals).length > 0" x-transition>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-[var(--border-color)]">
                <p class="text-sm text-gray-600 mb-1">@if(session('locale') == 'ar') عدد العناصر @else Number of Items @endif</p>
                <p class="text-2xl font-bold text-[var(--text-primary)]" x-text="totals.number_of_items || 0">0</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-[var(--border-color)]">
                <p class="text-sm text-gray-600 mb-1">@if(session('locale') == 'ar') إجمالي المبيعات @else Total Sales @endif</p>
                <p class="text-2xl font-bold text-[var(--text-primary)]" x-text="formatNum(totals.sales)">0.000</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-[var(--border-color)]">
                <p class="text-sm text-gray-600 mb-1">@if(session('locale') == 'ar') الربح @else Profit @endif</p>
                <p class="text-2xl font-bold text-[var(--text-primary)]" x-text="formatNum(totals.profit)">0.000</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-sm text-right">
                    <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                        <tr>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">@if(session('locale') == 'ar') رقم العملية @else Operation Number @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">@if(session('locale') == 'ar') الشهر @else Month @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap">@if(session('locale') == 'ar') البوتيك @else Boutique @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') عدد العناصر @else Number of Items @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') المبيعات (ر.ع) @else Sales (OMR) @endif</th>
                            <th class="px-4 py-4 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') الربح @else Profit @endif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500"><span class="material-symbols-outlined animate-spin align-middle">refresh</span> @if(session('locale') == 'ar') جاري التحميل... @else Loading... @endif</td></tr>
                        </template>
                        <template x-if="!loading && error">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-red-500" x-text="error"></td></tr>
                        </template>
                        <template x-if="!loading && !error && (!items || items.length === 0) && loaded">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">@if(session('locale') == 'ar') لا توجد بيانات @else No data found @endif</td></tr>
                        </template>
                        <template x-if="!loading && !error && items && items.length > 0">
                            <template x-for="row in items" :key="row.id">
                                <tr class="hover:bg-pink-50/50 transition-colors border-b">
                                    <td class="px-4 py-4 font-semibold whitespace-nowrap" x-text="row.operation_number"></td>
                                    <td class="px-4 py-4 whitespace-nowrap" x-text="row.month"></td>
                                    <td class="px-4 py-4 whitespace-nowrap" x-text="row.boutique"></td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap" x-text="row.number_of_items"></td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap" x-text="formatNum(row.sales)"></td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap" x-text="formatNum(row.profit)"></td>
                                </tr>
                            </template>
                        </template>
                        <template x-if="!loading && !loaded && !error">
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">@if(session('locale') == 'ar') اختر التواريخ واضغط بحث لعرض التقرير @else Select dates and click Filter to view report @endif</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-center mt-6" x-show="lastPage > 1">
            <ul class="dress_pagination flex flex-wrap gap-2 justify-center items-center list-none p-0 m-0">
                <li><button @click.prevent="goToPage(currentPage - 1)" :disabled="currentPage <= 1" class="px-3 py-1 border rounded-lg text-sm transition disabled:opacity-40 disabled:cursor-not-allowed disabled:bg-gray-200">«</button></li>
                <template x-for="(p, i) in pageList" :key="p === '...' ? 'e'+i : p">
                    <li>
                        <template x-if="p === '...'"><span class="px-2 py-1 text-gray-500">…</span></template>
                        <template x-if="p !== '...'"><button @click.prevent="goToPage(p)" :class="p === currentPage ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white hover:bg-gray-100'" class="px-4 py-1 border rounded-lg text-sm font-medium transition" x-text="p"></button></template>
                    </li>
                </template>
                <li><button @click.prevent="goToPage(currentPage + 1)" :disabled="currentPage >= lastPage" class="px-3 py-1 border rounded-lg text-sm transition disabled:opacity-40 disabled:cursor-not-allowed disabled:bg-gray-200">»</button></li>
            </ul>
        </div>
    </div>
</main>

@include('layouts.footer')
@endsection
