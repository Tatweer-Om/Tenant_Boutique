@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.profit_expense_report', [], session('locale')) ?: 'Profit & Expense Report' }}</title>
@endpush

<script>
window.profitExpenseReport = function() {
  return {
    dataUrl: '{{ route('reports.profit_expense.data') }}',
    fromDate: '{{ date('Y-m-d') }}',
    toDate: '{{ date('Y-m-d') }}',
    rows: [],
    totals: {},
    loading: false,
    error: null,
    loaded: false,

    async loadReport() {
      this.loading = true;
      this.error = null;
      const params = new URLSearchParams({ from_date: this.fromDate || '', to_date: this.toDate || '' });
      try {
        const res = await fetch(this.dataUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const d = await res.json();
        if (!res.ok || !d.success) { this.error = d.message || 'Error loading data'; this.rows = []; return; }
        this.rows = d.rows || [];
        this.totals = d.totals || {};
      } catch (e) { this.error = e.message || 'Error loading data'; this.rows = []; }
      finally { this.loading = false; this.loaded = true; }
    },

    formatNum(n) { return Number(n ?? 0).toFixed(3); }
  };
};
</script>

<main class="flex-1 p-8 bg-background-light dark:bg-background-dark overflow-y-auto" x-data="profitExpenseReport()" x-init="loadReport()">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h2 class="text-2xl sm:text-4xl font-bold text-[var(--text-primary)]">
                {{ trans('messages.profit_expense_report', [], session('locale')) ?: 'Profit & Expense Report' }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                @if(session('locale') == 'ar')
                    المصروف = مصروفات الوحدة + إيجار البوتيك + صيانة (الشركة). الربح = ربح نقطة البيع + الطلبات الخاصة + التسوية.
                @else
                    Expense = expense module + boutique rent + maintenance (company). Profit = POS + Special Orders + Settlement profit.
                @endif
            </p>
            <p class="mt-1 text-xs text-gray-500">
                @if(session('locale') == 'ar')
                    ملاحظة: تم خصم أجور الخياطة بالفعل من الربح حسب كود العباية.
                @else
                    Note: Tailor charges are already deducted from profit by abaya code.
                @endif
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-[var(--border-color)]">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">@if(session('locale') == 'ar') من تاريخ @else From Date @endif</label>
                    <input type="date" x-model="fromDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">@if(session('locale') == 'ar') إلى تاريخ @else To Date @endif</label>
                    <input type="date" x-model="toDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent">
                </div>
                <div class="flex items-end">
                    <button @click="loadReport()" :disabled="loading" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-accent transition flex items-center gap-2 disabled:opacity-70">
                        <span class="material-symbols-outlined" :class="loading ? 'animate-spin' : ''">search</span>
                        @if(session('locale') == 'ar') بحث @else Filter @endif
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6" x-show="Object.keys(totals).length > 0" x-transition>
            <div class="bg-white rounded-2xl shadow-lg p-4 border border-[var(--border-color)]">
                <p class="text-xs text-gray-600 mb-1">@if(session('locale') == 'ar') إجمالي المصروف @else Total Expense @endif</p>
                <p class="text-xl font-bold text-red-600" x-text="formatNum(totals.expense_total)">0.000</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-4 border border-[var(--border-color)]">
                <p class="text-xs text-gray-600 mb-1">@if(session('locale') == 'ar') إجمالي الربح @else Total Profit @endif</p>
                <p class="text-xl font-bold text-green-600" x-text="formatNum(totals.profit_total)">0.000</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-4 border border-[var(--border-color)]">
                <p class="text-xs text-gray-600 mb-1">@if(session('locale') == 'ar') صافي (ربح - مصروف) @else Net (Profit − Expense) @endif</p>
                <p class="text-xl font-bold" :class="(totals.net || 0) >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="formatNum(totals.net)">0.000</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-[var(--border-color)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-sm text-right">
                    <thead class="bg-gray-50 border-b border-[var(--border-color)]">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap">@if(session('locale') == 'ar') التاريخ @else Date @endif</th>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') مصروف الوحدة @else Expense (Module) @endif</th>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') إيجار البوتيك @else Expense (Boutique Rent) @endif</th>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') صيانة (الشركة) @else Expense (Maintenance - Company) @endif</th>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') إجمالي المصروف @else Total Expense @endif</th>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') ربح نقطة البيع @else Profit (POS) @endif</th>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') ربح الطلبات الخاصة @else Profit (Special) @endif</th>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') ربح التسوية @else Profit (Settlement) @endif</th>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') إجمالي الربح @else Total Profit @endif</th>
                            <th class="px-4 py-3 font-semibold text-[var(--text-secondary)] whitespace-nowrap text-center">@if(session('locale') == 'ar') صافي @else Net @endif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loading">
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                    <span class="material-symbols-outlined animate-spin align-middle">refresh</span>
                                    @if(session('locale') == 'ar') جاري التحميل... @else Loading... @endif
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && error">
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-red-500" x-text="error"></td>
                            </tr>
                        </template>
                        <template x-if="!loading && !error && (!rows || rows.length === 0) && loaded">
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">@if(session('locale') == 'ar') لا توجد بيانات @else No data found @endif</td>
                            </tr>
                        </template>
                        <template x-if="!loading && !error && rows && rows.length > 0">
                            <template x-for="r in rows" :key="r.date">
                                <tr class="hover:bg-pink-50/50 transition-colors border-b">
                                    <td class="px-4 py-3 font-medium whitespace-nowrap" x-text="r.date"></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap text-red-600" x-text="formatNum(r.expense_module)"></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap text-red-600" x-text="formatNum(r.expense_boutique_rent)"></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap text-red-600" x-text="formatNum(r.expense_maintenance_company)"></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap font-medium text-red-600" x-text="formatNum(r.expense_total)"></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap text-green-600" x-text="formatNum(r.profit_pos)"></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap text-green-600" x-text="formatNum(r.profit_special)"></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap text-green-600" x-text="formatNum(r.profit_settlement)"></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap font-medium text-green-600" x-text="formatNum(r.profit_total)"></td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap font-medium" :class="(r.net || 0) >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="formatNum(r.net)"></td>
                                </tr>
                            </template>
                        </template>
                        <template x-if="!loading && !loaded && !error">
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">@if(session('locale') == 'ar') اختر التواريخ واضغط بحث @else Select dates and click Filter @endif</td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot x-show="!loading && rows && rows.length > 0 && Object.keys(totals).length > 0">
                        <tr class="bg-gray-100 font-semibold border-t-2 border-gray-300">
                            <td class="px-4 py-3">@if(session('locale') == 'ar') الإجمالي @else Total @endif</td>
                            <td class="px-4 py-3 text-center text-red-600" x-text="formatNum(totals.expense_module)"></td>
                            <td class="px-4 py-3 text-center text-red-600" x-text="formatNum(totals.expense_boutique_rent)"></td>
                            <td class="px-4 py-3 text-center text-red-600" x-text="formatNum(totals.expense_maintenance_company)"></td>
                            <td class="px-4 py-3 text-center text-red-600" x-text="formatNum(totals.expense_total)"></td>
                            <td class="px-4 py-3 text-center text-green-600" x-text="formatNum(totals.profit_pos)"></td>
                            <td class="px-4 py-3 text-center text-green-600" x-text="formatNum(totals.profit_special)"></td>
                            <td class="px-4 py-3 text-center text-green-600" x-text="formatNum(totals.profit_settlement)"></td>
                            <td class="px-4 py-3 text-center text-green-600" x-text="formatNum(totals.profit_total)"></td>
                            <td class="px-4 py-3 text-center" :class="(totals.net || 0) >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="formatNum(totals.net)"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</main>

@include('layouts.footer')
@endsection
