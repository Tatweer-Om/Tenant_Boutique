@extends('layouts.header')

@section('main')
@push('title')
<title>Yearly Income Report</title>
@endpush

<main class="flex-1 p-4 md:p-6 bg-gray-50 dark:bg-gray-900 overflow-y-auto text-sm" 
      x-data="yearlyReport()" 
      x-init="init()">
    <div class="max-w-7xl mx-auto space-y-5">

        <!-- Title -->
        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                Yearly Income Report
            </h2>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                Monthly overview • 3 months sample
            </p>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">From Date</label>
                    <input type="date" x-model="filters.from" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-800">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">To Date</label>
                    <input type="date" x-model="filters.to" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-800">
                </div>
                <div class="flex items-end">
                    <button @click="loadReport"
                            class="w-full md:w-auto px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm transition">
                        Generate
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 border border-gray-200 dark:border-gray-700 text-center">
                <p class="text-xs text-gray-600 dark:text-gray-400">Total Revenue</p>
                <p class="text-lg font-bold text-indigo-600" x-text="formatCurrency(summary.totalRevenue)">0</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 border border-gray-200 dark:border-gray-700 text-center">
                <p class="text-xs text-gray-600 dark:text-gray-400">Total Expenses</p>
                <p class="text-lg font-bold text-red-600" x-text="formatCurrency(summary.totalExpenses)">0</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 border border-gray-200 dark:border-gray-700 text-center">
                <p class="text-xs text-gray-600 dark:text-gray-400">Net Profit</p>
                <p class="text-lg font-bold text-emerald-600" x-text="formatCurrency(summary.netProfit)">0</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 border border-gray-200 dark:border-gray-700 text-center">
                <p class="text-xs text-gray-600 dark:text-gray-400">Profit Margin</p>
                <p class="text-lg font-bold text-purple-600" x-text="summary.profitMargin + '%'">0%</p>
            </div>
        </div>

        <!-- Monthly Breakdown Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    Monthly Breakdown
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-xs">
                    <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300 w-28">Month</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-300">Type</th>
                            <th class="px-3 py-2 text-center font-medium text-gray-700 dark:text-gray-300 w-16">Qty</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Total</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Net</th>
                            <th class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Profit</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="(month, index) in reportData" :key="month.name">
                            <!-- POS -->
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" :class="{'border-b border-gray-300 dark:border-gray-600': index % 3 === 2}">
                                <td x-show="index % 3 === 0" 
                                    class="px-4 py-1.5 font-medium text-gray-900 dark:text-gray-200 border-r border-gray-200 dark:border-gray-700"
                                    :rowspan="3"
                                    x-text="month.name"></td>
                                <td class="px-3 py-1.5 text-center text-blue-700 dark:text-blue-400">POS</td>
                                <td class="px-3 py-1.5 text-center" x-text="formatNumber(month.pos.qty)"></td>
                                <td class="px-4 py-1.5 text-right" x-text="formatCurrency(month.pos.amount)"></td>
                                <td class="px-4 py-1.5 text-right" x-text="formatCurrency(month.pos.amount - (month.pos.discount || 0))"></td>
                                <td class="px-4 py-1.5 text-right text-emerald-700 dark:text-emerald-400" x-text="formatCurrency(month.profit * 0.45)"></td>
                            </tr>

                            <!-- Special Order -->
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-3 py-1.5 text-center text-purple-700 dark:text-purple-400">Special</td>
                                <td class="px-3 py-1.5 text-center" x-text="formatNumber(month.special.qty)"></td>
                                <td class="px-4 py-1.5 text-right" x-text="formatCurrency(month.special.amount)"></td>
                                <td class="px-4 py-1.5 text-right" x-text="formatCurrency(month.special.amount - (month.special.alteration || 0) - (month.special.delivery || 0))"></td>
                                <td class="px-4 py-1.5 text-right text-emerald-700 dark:text-emerald-400" x-text="formatCurrency(month.profit * 0.50)"></td>
                            </tr>

                            <!-- Settlement -->
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-3 py-1.5 text-center text-green-700 dark:text-green-400">Settlement</td>
                                <td class="px-3 py-1.5 text-center" x-text="formatNumber(month.settlement.qty)"></td>
                                <td class="px-4 py-1.5 text-right" x-text="formatCurrency(month.settlement.amount)"></td>
                                <td class="px-4 py-1.5 text-right" x-text="formatCurrency(month.settlement.amount)"></td>
                                <td class="px-4 py-1.5 text-right text-emerald-700 dark:text-emerald-400" x-text="formatCurrency(month.profit * 0.05)"></td>
                            </tr>
                        </template>

                        <!-- Total Row -->
                        <tr class="bg-gray-100 dark:bg-gray-700 font-medium">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-200">TOTAL</td>
                            <td class="px-3 py-2 text-center">-</td>
                            <td class="px-3 py-2 text-center" x-text="formatNumber(totalQty)"></td>
                            <td class="px-4 py-2 text-right" x-text="formatCurrency(summary.totalRevenue)"></td>
                            <td class="px-4 py-2 text-right" x-text="formatCurrency(summary.totalRevenue - summary.totalExpenses)"></td>
                            <td class="px-4 py-2 text-right text-emerald-700 dark:text-emerald-400" x-text="formatCurrency(summary.netProfit)"></td>
                        </tr>

                        <!-- No data -->
                        <tr x-show="!reportData || reportData.length === 0">
                            <td colspan="6" class="py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No data available
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<script>
function yearlyReport() {
    return {
        filters: {
            from: '2026-01-01',
            to: '2026-03-31'
        },

        reportData: [],
        summary: { totalRevenue: 0, totalExpenses: 0, netProfit: 0, profitMargin: 0 },
        totalQty: 0,

        init() {
            this.loadReport()
        },

        loadReport() {
            this.reportData = [
                {
                    name: 'Jan 2026',
                    pos: { qty: 142, amount: 185400, discount: 7200 },
                    special: { qty: 38, amount: 248000, alteration: 9500, delivery: 3800 },
                    settlement: { qty: 29, amount: 112500 },
                    expenses: 24500,
                    profit: 312500
                },
                {
                    name: 'Feb 2026',
                    pos: { qty: 118, amount: 154800, discount: 4800 },
                    special: { qty: 45, amount: 295000, alteration: 12800, delivery: 4500 },
                    settlement: { qty: 21, amount: 78500 },
                    expenses: 31800,
                    profit: 338400
                },
                {
                    name: 'Mar 2026',
                    pos: { qty: 165, amount: 218900, discount: 10500 },
                    special: { qty: 52, amount: 368000, alteration: 16400, delivery: 5200 },
                    settlement: { qty: 34, amount: 136200 },
                    expenses: 41200,
                    profit: 458200
                }
            ];

            this.calculateSummary();
        },

        calculateSummary() {
            let rev = 0, exp = 0, profit = 0, qty = 0;

            this.reportData.forEach(m => {
                rev += (m.pos?.amount || 0) + (m.special?.amount || 0) + (m.settlement?.amount || 0);
                exp += m.expenses || 0;
                profit += m.profit || 0;
                qty += (m.pos?.qty || 0) + (m.special?.qty || 0) + (m.settlement?.qty || 0);
            });

            this.summary = {
                totalRevenue: rev,
                totalExpenses: exp,
                netProfit: profit,
                profitMargin: rev > 0 ? Math.round((profit / rev) * 100) : 0
            };

            this.totalQty = qty;
        },

        formatCurrency(n) {
            return 'Rs ' + Number(n || 0).toLocaleString('en-PK', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },

        formatNumber(n) {
            return Number(n || 0).toLocaleString('en-PK');
        }
    }
}
</script>

@endsection