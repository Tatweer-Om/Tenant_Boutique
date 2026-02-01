@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.tailor_payments', [], session('locale')) ?: 'Tailor Payments' }}</title>
@endpush

<style>
    [x-cloak] {
        display: none !important;
    }
</style>

<main class="flex-1 p-4 md:p-6" x-data="{ activeTab: 'pending', selectedItems: [], loading: false }">
    <div class="w-full max-w-[1920px] mx-auto">
        <!-- Page title -->
        <div class="mb-6">
            <h2 class="text-gray-900 text-2xl sm:text-3xl font-bold">
                {{ trans('messages.tailor_payments', [], session('locale')) ?: 'Tailor Payments' }}
            </h2>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-sm border border-pink-100 mb-6">
            <div class="flex border-b border-gray-200">
                <button @click="activeTab = 'pending'; selectedItems = []" 
                        :class="activeTab === 'pending' ? 'border-b-2 border-[var(--primary-color)] text-[var(--primary-color)] font-bold' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 px-6 py-4 text-center transition">
                    <span class="material-symbols-outlined align-middle me-2">pending</span>
                    {{ trans('messages.pending_payments', [], session('locale')) ?: 'Pending Payments' }}
                </button>
                <button @click="activeTab = 'history'; selectedItems = []" 
                        :class="activeTab === 'history' ? 'border-b-2 border-[var(--primary-color)] text-[var(--primary-color)] font-bold' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 px-6 py-4 text-center transition">
                    <span class="material-symbols-outlined align-middle me-2">history</span>
                    {{ trans('messages.payment_history', [], session('locale')) ?: 'Payment History' }}
                </button>
            </div>
        </div>

        <!-- Pending Payments Tab -->
        <div x-show="activeTab === 'pending'" x-cloak>
            <!-- Payment Form (shown when items selected) -->
            <div x-show="selectedItems.length > 0" 
                 x-transition
                 class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl p-4 md:p-6 mb-6 shadow-md">
                <h3 class="text-lg font-bold text-green-900 mb-4 flex items-center">
                    <span class="material-symbols-outlined me-2">payment</span>
                    {{ trans('messages.process_payment', [], session('locale')) ?: 'Process Payment' }}
                </h3>
                
                <form id="payment_form" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ trans('messages.payment_date', [], session('locale')) ?: 'Payment Date' }}
                            </label>
                            <input type="date" 
                                   name="payment_date" 
                                   id="payment_date"
                                   value="{{ date('Y-m-d') }}"
                                   class="w-full h-11 px-4 rounded-lg border-2 border-gray-300 focus:border-[var(--primary-color)] focus:ring-2 focus:ring-[var(--primary-color)]/20 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ trans('messages.select_account', [], session('locale')) ?: 'Select Account' }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select name="account_id" 
                                    id="account_id"
                                    required
                                    class="w-full h-11 px-4 rounded-lg border-2 border-gray-300 focus:border-[var(--primary-color)] focus:ring-2 focus:ring-[var(--primary-color)]/20 text-sm">
                                <option value="">{{ trans('messages.select_account', [], session('locale')) ?: '-- Select Account --' }}</option>
                                <!-- Options will be populated via JavaScript -->
                            </select>
                            <p class="text-xs text-gray-500 mt-1" id="account_balance_info"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ trans('messages.total_amount', [], session('locale')) ?: 'Total Amount' }}
                            </label>
                            <input type="text" 
                                   id="total_amount_display"
                                   readonly
                                   class="w-full h-11 px-4 rounded-lg border-2 border-green-300 bg-green-50 font-bold text-green-900 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ trans('messages.notes', [], session('locale')) ?: 'Notes' }}
                        </label>
                        <textarea name="notes" 
                                  id="payment_notes"
                                  rows="2"
                                  class="w-full px-4 py-2 rounded-lg border-2 border-gray-300 focus:border-[var(--primary-color)] focus:ring-2 focus:ring-[var(--primary-color)]/20 text-sm"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" 
                                @click="selectedItems = []"
                                class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-50 transition">
                            {{ trans('messages.cancel', [], session('locale')) }}
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 rounded-lg bg-green-600 text-white font-semibold shadow-md hover:shadow-lg hover:bg-green-700 transition">
                            <span class="material-symbols-outlined align-middle me-2">check</span>
                            {{ trans('messages.process_payment', [], session('locale')) ?: 'Process Payment' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pending Payments Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-pink-100 overflow-hidden">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-gray-900">
                            {{ trans('messages.unpaid_abayas', [], session('locale')) ?: 'Unpaid Abayas' }}
                        </h3>
                        <button @click="loadPendingPayments()" 
                                class="px-3 py-1.5 rounded-lg bg-[var(--primary-color)] text-white text-sm font-semibold hover:bg-opacity-90 transition">
                            <span class="material-symbols-outlined align-middle me-1 text-base">refresh</span>
                            {{ trans('messages.refresh', [], session('locale')) ?: 'Refresh' }}
                        </button>
                    </div>

                    <!-- Date filter -->
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ trans('messages.from_date', [], session('locale')) ?: 'From date' }}
                            </label>
                            <input type="date"
                                   id="pending_from_date"
                                   class="w-full h-10 px-3 rounded-lg border border-gray-300 focus:border-[var(--primary-color)] focus:ring-2 focus:ring-[var(--primary-color)]/20 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                {{ trans('messages.to_date', [], session('locale')) ?: 'To date' }}
                            </label>
                            <input type="date"
                                   id="pending_to_date"
                                   class="w-full h-10 px-3 rounded-lg border border-gray-300 focus:border-[var(--primary-color)] focus:ring-2 focus:ring-[var(--primary-color)]/20 text-sm bg-white">
                        </div>
                        <div class="flex gap-2">
                            <button type="button"
                                    id="pending_filter_btn"
                                    class="flex-1 h-10 px-4 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-base">filter_alt</span>
                                {{ trans('messages.filter', [], session('locale')) ?: 'Filter' }}
                            </button>
                            <button type="button"
                                    id="pending_reset_btn"
                                    class="flex-1 h-10 px-4 rounded-lg bg-gray-200 text-gray-800 text-sm font-semibold hover:bg-gray-300 transition flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-base">restart_alt</span>
                                {{ trans('messages.reset', [], session('locale')) ?: 'Reset' }}
                            </button>
                        </div>
                        <div class="text-xs text-gray-500">
                            <span class="font-semibold">{{ trans('messages.note', [], session('locale')) ?: 'Note' }}:</span>
                            {{ trans('messages.filter_by_date_note', [], session('locale')) ?: 'Filter applies to the order date shown in the table.' }}
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-center font-bold">
                                    <input type="checkbox" 
                                           @change="toggleAllPending($event.target.checked)"
                                           class="w-4 h-4 text-[var(--primary-color)] rounded">
                                </th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.order_number', [], session('locale')) ?: 'Order No' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.abaya', [], session('locale')) ?: 'Abaya Name' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.tailor', [], session('locale')) ?: 'Tailor' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.type', [], session('locale')) ?: 'Type' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.unit_charge', [], session('locale')) ?: 'Unit Charge' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.total_charge', [], session('locale')) ?: 'Total Charge' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.source', [], session('locale')) ?: 'Source' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.details', [], session('locale')) ?: 'Details' }}</th>
                            </tr>
                        </thead>
                        <tbody id="pending_payments_body">
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <div class="border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-12 h-12 animate-spin mb-3"></div>
                                        {{ trans('messages.loading', [], session('locale')) ?: 'Loading...' }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div id="pending_pagination" class="p-4 border-t border-gray-200"></div>
            </div>
        </div>

        <!-- Payment History Tab -->
        <div x-show="activeTab === 'history'" x-cloak>
            <div class="bg-white rounded-2xl shadow-sm border border-pink-100 overflow-hidden">
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-gray-900">
                            {{ trans('messages.payment_history', [], session('locale')) ?: 'Payment History' }}
                        </h3>
                        <button @click="loadPaymentHistory()" 
                                class="px-3 py-1.5 rounded-lg bg-[var(--primary-color)] text-white text-sm font-semibold hover:bg-opacity-90 transition">
                            <span class="material-symbols-outlined align-middle me-1 text-base">refresh</span>
                            {{ trans('messages.refresh', [], session('locale')) ?: 'Refresh' }}
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-l from-pink-50 to-pink-100 text-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.payment_date', [], session('locale')) ?: 'Payment Date' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.select_account', [], session('locale')) ?: 'Account' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.total_amount', [], session('locale')) ?: 'Total Amount' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.tailor', [], session('locale')) ?: 'Tailor' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.abaya_code', [], session('locale')) ?: 'Abaya Code' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.design_name', [], session('locale')) ?: 'Design Name' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.unit_charge', [], session('locale')) ?: 'Unit Charge' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.total_charge', [], session('locale')) ?: 'Total Charge' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.source', [], session('locale')) ?: 'Source' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.notes', [], session('locale')) ?: 'Notes' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.added_by', [], session('locale')) ?: 'Added By' }}</th>
                                <th class="px-4 py-3 text-center font-bold">{{ trans('messages.details', [], session('locale')) ?: 'Details' }}</th>
                            </tr>
                        </thead>
                        <tbody id="payment_history_body">
                            <tr>
                                <td colspan="13" class="px-4 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <div class="border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-12 h-12 animate-spin mb-3"></div>
                                        {{ trans('messages.loading', [], session('locale')) ?: 'Loading...' }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div id="history_pagination" class="p-4 border-t border-gray-200"></div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="details_modal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[100] hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl p-7 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">{{ trans('messages.abaya_details', [], session('locale')) ?: 'Abaya Details' }}</h2>
                <button onclick="closeDetailsModal()" class="text-gray-500 hover:text-gray-700">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Abaya Image -->
                <div>
                    <img id="detail_abaya_image" src="" class="w-full rounded-xl border object-cover shadow" alt="Abaya Image">
                </div>

                <!-- Details -->
                <div class="space-y-3 text-sm">
                    <div>
                        <strong>{{ trans('messages.order_number', [], session('locale')) ?: 'Order Number' }}:</strong> 
                        <span id="detail_order_no" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.abaya', [], session('locale')) ?: 'Abaya Name' }}:</strong> 
                        <span id="detail_abaya_name" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.abaya_code', [], session('locale')) ?: 'Abaya Code' }}:</strong> 
                        <span id="detail_abaya_code" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}:</strong> 
                        <span id="detail_quantity" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.tailor', [], session('locale')) ?: 'Tailor' }}:</strong> 
                        <span id="detail_tailor" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.customer_name', [], session('locale')) ?: 'Customer Name' }}:</strong> 
                        <span id="detail_customer_name" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.phone_number', [], session('locale')) ?: 'Phone Number' }}:</strong> 
                        <span id="detail_customer_phone" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.abaya_length', [], session('locale')) ?: 'Abaya Length' }}:</strong> 
                        <span id="detail_length" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.bust_one_side', [], session('locale')) ?: 'Bust (One Side)' }}:</strong> 
                        <span id="detail_bust" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.sleeves_length', [], session('locale')) ?: 'Sleeves Length' }}:</strong> 
                        <span id="detail_sleeves" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.buttons', [], session('locale')) ?: 'Buttons' }}:</strong> 
                        <span id="detail_buttons" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.unit_charge', [], session('locale')) ?: 'Unit Charge' }}:</strong> 
                        <span id="detail_unit_charge" class="ml-2"></span>
                    </div>
                    <div>
                        <strong>{{ trans('messages.total_charge', [], session('locale')) ?: 'Total Charge' }}:</strong> 
                        <span id="detail_total_charge" class="ml-2 font-bold text-green-600"></span>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <strong class="block mb-2">{{ trans('messages.notes', [], session('locale')) ?: 'Notes' }}:</strong>
                <p id="detail_notes" class="bg-gray-50 p-4 rounded-xl border"></p>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeDetailsModal()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-xl">
                    {{ trans('messages.close', [], session('locale')) ?: 'Close' }}
                </button>
            </div>
        </div>
    </div>
</main>

@include('layouts.footer')
@include('custom_js.tailor_payment_js')
@endsection
