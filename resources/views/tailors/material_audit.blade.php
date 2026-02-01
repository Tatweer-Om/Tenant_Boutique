@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.tailor_material_audit', [], session('locale')) ?: 'Tailor Material Audit' }}</title>
@endpush

<style>
  body {
    font-family: 'IBM Plex Sans Arabic', sans-serif;
  }
</style>

<main class="flex-1 p-4 md:p-6" x-data="materialAudit()">
  <div class="w-full max-w-screen-2xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ trans('messages.tailor_material_audit', [], session('locale')) ?: 'Tailor Material Audit' }}</h2>
        <p class="text-gray-500 text-sm mt-1">{{ trans('messages.material_audit_description', [], session('locale')) ?: 'Track materials sent to tailors and received abayas' }}</p>
      </div>
      <div class="flex gap-2">
        <a href="/tailor" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition font-semibold flex items-center gap-2">
          <span class="material-symbols-outlined text-base">arrow_back</span> 
          {{ trans('messages.back', [], session('locale')) }}
        </a>
      </div>
    </div>

    <!-- Tailor Selection Card -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
      <div class="flex items-center gap-3 mb-4">
        <div class="bg-[var(--primary-color)] rounded-full p-2">
          <span class="material-symbols-outlined text-white">person</span>
        </div>
        <h3 class="text-lg font-bold text-gray-800">{{ trans('messages.select_tailor', [], session('locale')) ?: 'Select Tailor' }}</h3>
      </div>
      
      <div class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="flex-1">
          <label class="block text-sm font-semibold text-gray-700 mb-2">{{ trans('messages.tailor', [], session('locale')) }}</label>
          <select x-model="selectedTailorId" 
                  @change="loadAuditData()"
                  class="w-full h-12 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-[var(--primary-color)]/50 focus:border-[var(--primary-color)] text-sm">
            <option value="">{{ trans('messages.choose_tailor', [], session('locale')) ?: 'Choose Tailor' }}</option>
            @foreach($tailors as $tailor)
              <option value="{{ $tailor->id }}">{{ $tailor->tailor_name }}</option>
            @endforeach
          </select>
        </div>
        <button @click="loadAuditData()" 
                :disabled="!selectedTailorId || loading"
                class="px-6 py-3 bg-[var(--primary-color)] text-white font-semibold rounded-lg hover:bg-[var(--primary-darker)] transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
          <span class="material-symbols-outlined" x-show="!loading">refresh</span>
          <span class="material-symbols-outlined animate-spin" x-show="loading" x-cloak>hourglass_empty</span>
          {{ trans('messages.load_data', [], session('locale')) ?: 'Load Data' }}
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div x-show="summary && selectedTailorId" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
      <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border border-blue-200 shadow-md">
        <div class="flex items-center justify-between mb-2">
          <span class="material-symbols-outlined text-blue-600 text-2xl">inventory_2</span>
        </div>
        <p class="text-sm text-gray-600 mb-1">{{ trans('messages.total_materials', [], session('locale')) ?: 'Total Materials' }}</p>
        <h3 class="text-2xl font-extrabold text-blue-600" x-text="summary?.total_materials || 0"></h3>
      </div>

      <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 border border-purple-200 shadow-md">
        <!-- <h3 class="text-2xl font-extrabold text-purple-600 mb-2" x-text="summary?.total_expected_abayas || 0"></h3> -->
        <div x-show="summary?.grouped_by_abaya && summary.grouped_by_abaya.length > 0" class="mt-2 max-h-40 overflow-y-auto">
          <template x-for="(abayaGroup, index) in summary.grouped_by_abaya" :key="index">
            <template x-for="(material, mIndex) in abayaGroup.materials" :key="'abaya-' + index + '-material-' + mIndex">
              <div class="text-xs text-gray-600 mb-0.5" x-text="'• ' + material.material_name + ' - ' + material.abayas_expected + ' @if(session('locale') == 'ar') متوقع @else expected @endif'"></div>
            </template>
          </template>
        </div>
      </div>

      <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border border-green-200 shadow-md">
        <div class="flex items-center justify-between mb-2">
          <span class="material-symbols-outlined text-green-600 text-2xl">check_circle</span>
        </div>
        <p class="text-sm text-gray-600 mb-1">{{ trans('messages.received_abayas', [], session('locale')) ?: 'Received Abayas' }}</p>
        <h3 class="text-2xl font-extrabold text-green-600" x-text="summary?.total_received_abayas || 0"></h3>
      </div>

      <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-5 border border-orange-200 shadow-md">
        <div class="flex items-center justify-between mb-2">
          <span class="material-symbols-outlined text-orange-600 text-2xl">warehouse</span>
        </div>
        <p class="text-sm text-gray-600 mb-1">{{ trans('messages.stock_abayas', [], session('locale')) ?: 'Stock Abayas' }}</p>
        <h3 class="text-2xl font-extrabold text-orange-600" x-text="summary?.total_stock_abayas || 0"></h3>
        <div class="mt-2 pt-2 border-t border-orange-200">
          <p class="text-xs text-gray-600">{{ trans('messages.from_stock_additions', [], session('locale')) ?: 'From Stock Additions' }}</p>
        </div>
      </div>

      <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl p-5 border border-pink-200 shadow-md">
        <div class="flex items-center justify-between mb-2">
          <span class="material-symbols-outlined text-pink-600 text-2xl">receipt_long</span>
        </div>
        <p class="text-sm text-gray-600 mb-1">{{ trans('messages.special_order_abayas', [], session('locale')) ?: 'Special Order Abayas' }}</p>
        <h3 class="text-2xl font-extrabold text-pink-600" x-text="summary?.total_special_order_abayas || 0"></h3>
        <div class="mt-2 pt-2 border-t border-pink-200">
          <p class="text-xs text-gray-600">{{ trans('messages.from_special_orders', [], session('locale')) ?: 'From Special Orders' }}</p>
        </div>
      </div>
    </div>

    <!-- Audit Table -->
    <div x-show="auditData.length > 0 && selectedTailorId" x-transition class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
          <span class="material-symbols-outlined text-[var(--primary-color)]">table_chart</span>
          {{ trans('messages.material_audit_details', [], session('locale')) ?: 'Material Audit Details' }}
        </h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ trans('messages.transaction_id', [], session('locale')) ?: 'Transaction ID' }}</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ trans('messages.material_name', [], session('locale')) }}</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ trans('messages.abaya', [], session('locale')) ?: 'Abaya' }}</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ trans('messages.sent_date', [], session('locale')) }}</th>
              <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ trans('messages.rolls_boxes', [], session('locale')) ?: 'Rolls/Boxes' }}</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-700">{{ trans('messages.expected_abayas', [], session('locale')) ?: 'Expected' }}</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-700">{{ trans('messages.received_abayas', [], session('locale')) ?: 'Received' }}</th>
              <th class="px-4 py-3 text-center font-semibold text-gray-700">{{ trans('messages.breakdown', [], session('locale')) ?: 'Breakdown' }}</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="(item, index) in auditData" :key="index">
              <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                  <span class="font-mono text-xs font-semibold text-blue-600" x-text="item.transaction_id"></span>
                </td>
                <td class="px-4 py-3">
                  <div>
                    <p class="font-semibold text-gray-800" x-text="item.material_name"></p>
                    <p class="text-xs text-gray-500" x-text="item.material_code"></p>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div>
                    <p class="font-medium text-gray-800" x-text="item.abaya_name"></p>
                    <p class="text-xs text-gray-500" x-text="item.abaya_code"></p>
                    <p class="text-xs text-gray-400" x-text="item.barcode" x-show="item.barcode !== '-'"></p>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-600" x-text="item.sent_date"></td>
                <td class="px-4 py-3">
                  <span class="font-semibold text-gray-800" x-text="item.quantity"></span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700" x-text="item.abayas_expected"></span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700" x-text="item.received_abayas"></span>
                </td>
                <td class="px-4 py-3 text-center">
                  <div class="flex flex-col gap-1 items-center">
                    <div class="flex items-center justify-center gap-1">
                      <span class="material-symbols-outlined text-blue-600 text-sm">inventory_2</span>
                      <span class="font-semibold text-blue-700 text-xs" x-text="item.stock_abayas || 0"></span>
                      <span class="text-gray-600 text-xs">Stock</span>
                    </div>
                    <div class="flex items-center justify-center gap-1">
                      <span class="material-symbols-outlined text-purple-600 text-sm">shopping_bag</span>
                      <span class="font-semibold text-purple-700 text-xs" x-text="item.special_order_abayas || 0"></span>
                      <span class="text-gray-600 text-xs">Special Order</span>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div x-show="auditData.length === 0 && selectedTailorId && !loading" class="p-12 text-center">
        <span class="material-symbols-outlined text-gray-300 text-6xl mb-4">inventory_2</span>
        <p class="text-gray-500 text-lg">{{ trans('messages.no_audit_data', [], session('locale')) ?: 'No audit data found for this tailor' }}</p>
      </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" x-transition class="bg-white rounded-2xl shadow-lg border border-gray-100 p-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-[var(--primary-color)] mb-4"></div>
      <p class="text-gray-600">{{ trans('messages.loading', [], session('locale')) ?: 'Loading audit data...' }}</p>
    </div>

  </div>
</main>

@include('layouts.footer')
@endsection
