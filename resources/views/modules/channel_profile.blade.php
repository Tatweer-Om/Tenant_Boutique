@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.channel_profile', [], session('locale')) ?? 'Channel Profile' }}</title>
@endpush

<main class="flex-1 p-4 md:p-6"
      x-data="channelProfile()"
      x-init="init()">

  <div class="w-full max-w-screen-xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
          @if(session('locale') == 'ar')
            {{ $channel->channel_name_ar ?? 'Channel Profile' }}
          @else
            {{ $channel->channel_name_en ?? 'Channel Profile' }}
          @endif
        </h2>
        <p class="text-gray-500 text-sm">
          @if(session('locale') == 'ar')
            {{ trans('messages.channel_name_ar', [], session('locale')) }}: {{ $channel->channel_name_ar ?? '-' }}
          @else
            {{ trans('messages.channel_name_en', [], session('locale')) }}: {{ $channel->channel_name_en ?? '-' }}
          @endif
        </p>
      </div>
      <div class="flex gap-2">
        <a href="{{ route('channel') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition font-semibold">
          <span class="material-symbols-outlined text-base align-middle">arrow_back</span> {{ trans('messages.back', [], session('locale')) }}
        </a>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.total_items', [], session('locale')) ?? 'Total Items' }}</p>
          <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">{{ number_format($totalItems) }}</h3>
        </div>
        <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">
          inventory_2
        </span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.total_transfers', [], session('locale')) ?? 'Total Transfers' }}</p>
          <h3 class="text-2xl font-extrabold text-[var(--primary-color)]">{{ number_format($totalTransfers) }}</h3>
        </div>
        <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">
          local_shipping
        </span>
      </div>
      <div class="p-4 rounded-2xl bg-gradient-to-br from-pink-50 to-purple-100 shadow-md flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ trans('messages.channel_name', [], session('locale')) ?? 'Channel Name' }}</p>
          <h3 class="text-lg font-extrabold text-[var(--primary-color)]">
            @if(session('locale') == 'ar')
              {{ $channel->channel_name_ar ?? '-' }}
            @else
              {{ $channel->channel_name_en ?? '-' }}
            @endif
          </h3>
        </div>
        <span class="material-symbols-outlined bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full p-3">
          store
        </span>
      </div>
    </div>

    <!-- General Details -->
    <div class="bg-white border border-pink-100 rounded-2xl p-6 shadow-sm">
      <h3 class="text-xl font-bold text-[var(--primary-color)] mb-4">{{ trans('messages.general_details', [], session('locale')) ?? 'General Details' }}</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.channel_name_en', [], session('locale')) ?? 'Channel Name (English)' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->channel_name_en ?? '-' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.channel_name_ar', [], session('locale')) ?? 'Channel Name (Arabic)' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->channel_name_ar ?? '-' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.status', [], session('locale')) ?? 'Status' }}</p>
          <p class="font-semibold text-gray-800">
            @if($channel->status_for_pos == 1)
              <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-700">{{ trans('messages.active', [], session('locale')) ?? 'Active' }}</span>
            @else
              <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">{{ trans('messages.inactive', [], session('locale')) ?? 'Inactive' }}</span>
            @endif
          </p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.added_by', [], session('locale')) ?? 'Added By' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->added_by ?? '-' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.created_at', [], session('locale')) ?? 'Created At' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->created_at ? $channel->created_at->format('Y-m-d H:i:s') : '-' }}</p>
        </div>
        <div>
          <p class="text-sm text-gray-600 mb-1">{{ trans('messages.updated_at', [], session('locale')) ?? 'Updated At' }}</p>
          <p class="font-semibold text-gray-800">{{ $channel->updated_at ? $channel->updated_at->format('Y-m-d H:i:s') : '-' }}</p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-3 border-b border-pink-100 overflow-x-auto no-scrollbar bg-white rounded-xl shadow-sm px-4">
      <button @click="tab='transfers'"
              :class="tab==='transfers' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">local_shipping</span> {{ trans('messages.transfers', [], session('locale')) ?? 'Transfers' }}
      </button>
      <button @click="tab='transfer_items'"
              :class="tab==='transfer_items' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-3 flex items-center gap-1">
        <span class="material-symbols-outlined text-base">inventory</span> {{ trans('messages.transfer_items', [], session('locale')) ?? 'Transfer Items' }}
      </button>
    </div>

    <!-- TRANSFERS TAB -->
    <section x-show="tab==='transfers'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input x-model="transferSearch" type="text" 
                 :placeholder="'{{ trans('messages.search', [], session('locale')) ?? 'Search' }}...'"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]"
                 @input="filterTransfers()">
          <input x-model="transferDateFrom" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterTransfers()">
          <input x-model="transferDateTo" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterTransfers()">
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[1000px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.transfer_code', [], session('locale')) ?? 'Transfer Code' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.transfer_date', [], session('locale')) ?? 'Date' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.transfer_type', [], session('locale')) ?? 'Type' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.channel_type', [], session('locale')) ?? 'Channel Type' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.quantity', [], session('locale')) ?? 'Quantity' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.from', [], session('locale')) ?? 'From' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.to', [], session('locale')) ?? 'To' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.items_count', [], session('locale')) ?? 'Items Count' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.notes', [], session('locale')) ?? 'Notes' }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="filteredTransfers.length === 0">
                <tr>
                  <td colspan="9" class="px-3 py-4 text-center text-gray-500">
                    {{ trans('messages.no_transfers_found', [], session('locale')) ?? 'No transfers found' }}
                  </td>
                </tr>
              </template>
              <template x-for="transfer in filteredTransfers" :key="transfer.id">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 font-semibold" x-text="transfer.transfer_code"></td>
                  <td class="px-3 py-2" x-text="transfer.date"></td>
                  <td class="px-3 py-2">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                          :class="transfer.transfer_type === 'add' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                          x-text="transfer.transfer_type === 'add' ? '{{ trans('messages.add', [], session('locale')) ?? 'Add' }}' : '{{ trans('messages.minus', [], session('locale')) ?? 'Minus' }}'"></span>
                  </td>
                  <td class="px-3 py-2" x-text="transfer.channel_type || '-'"></td>
                  <td class="px-3 py-2" x-text="transfer.quantity || '-'"></td>
                  <td class="px-3 py-2" x-text="transfer.from || '-'"></td>
                  <td class="px-3 py-2" x-text="transfer.to || '-'"></td>
                  <td class="px-3 py-2" x-text="transfer.items_count || 0"></td>
                  <td class="px-3 py-2" x-text="transfer.notes || '-'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- TRANSFER ITEMS TAB -->
    <section x-show="tab==='transfer_items'" x-transition>
      <div class="bg-white border border-pink-100 rounded-2xl p-6 mt-4">
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <input x-model="itemSearch" type="text" 
                 :placeholder="'{{ trans('messages.search', [], session('locale')) ?? 'Search' }}...'"
                 class="h-10 px-3 border border-pink-200 rounded-lg flex-1 focus:ring-2 focus:ring-[var(--primary-color)]"
                 @input="filterTransferItems()">
          <input x-model="itemDateFrom" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterTransferItems()">
          <input x-model="itemDateTo" type="date" 
                 class="h-10 px-2 border border-pink-200 rounded-lg" 
                 @change="filterTransferItems()">
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm min-w-[1200px]">
            <thead class="bg-gradient-to-l from-pink-50 to-purple-50 text-gray-800">
              <tr>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.transfer_code', [], session('locale')) ?? 'Transfer Code' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.transfer_date', [], session('locale')) ?? 'Transfer Date' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.abaya_code', [], session('locale')) ?? 'Abaya Code' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.item_type', [], session('locale')) ?? 'Item Type' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.color', [], session('locale')) ?? 'Color' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.size', [], session('locale')) ?? 'Size' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.quantity', [], session('locale')) ?? 'Quantity' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.from_location', [], session('locale')) ?? 'From Location' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.to_location', [], session('locale')) ?? 'To Location' }}</th>
                <th class="px-3 py-2 text-right font-bold">{{ trans('messages.created_at', [], session('locale')) ?? 'Created At' }}</th>
              </tr>
            </thead>
            <tbody>
              <template x-if="filteredTransferItems.length === 0">
                <tr>
                  <td colspan="10" class="px-3 py-4 text-center text-gray-500">
                    {{ trans('messages.no_transfer_items_found', [], session('locale')) ?? 'No transfer items found' }}
                  </td>
                </tr>
              </template>
              <template x-for="item in filteredTransferItems" :key="item.id">
                <tr class="border-t hover:bg-pink-50/60">
                  <td class="px-3 py-2 font-semibold" x-text="item.transfer_code || '-'"></td>
                  <td class="px-3 py-2" x-text="item.transfer_date || '-'"></td>
                  <td class="px-3 py-2" x-text="item.abaya_code || '-'"></td>
                  <td class="px-3 py-2" x-text="item.item_type || '-'"></td>
                  <td class="px-3 py-2" x-text="item.color_name || '-'"></td>
                  <td class="px-3 py-2" x-text="item.size_name || '-'"></td>
                  <td class="px-3 py-2" x-text="item.quantity || '-'"></td>
                  <td class="px-3 py-2" x-text="item.from_location || '-'"></td>
                  <td class="px-3 py-2" x-text="item.to_location || '-'"></td>
                  <td class="px-3 py-2" x-text="item.created_at || '-'"></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </div>
</main>

<script>
function channelProfile() {
  return {
    tab: 'transfers',
    transfers: [],
    transferItems: [],
    filteredTransfers: [],
    filteredTransferItems: [],
    transferSearch: '',
    transferDateFrom: '',
    transferDateTo: '',
    itemSearch: '',
    itemDateFrom: '',
    itemDateTo: '',
    loading: false,

    async init() {
      await this.loadTransfers();
      await this.loadTransferItems();
    },

    async loadTransfers() {
      this.loading = true;
      try {
        const response = await fetch('/channel_profile/{{ $channel->id }}/transfers');
        const data = await response.json();
        this.transfers = data || [];
        this.filteredTransfers = this.transfers;
      } catch (error) {
        console.error('Error loading transfers:', error);
        this.transfers = [];
        this.filteredTransfers = [];
      } finally {
        this.loading = false;
      }
    },

    async loadTransferItems() {
      this.loading = true;
      try {
        const response = await fetch('/channel_profile/{{ $channel->id }}/transfer-items');
        const data = await response.json();
        this.transferItems = data || [];
        this.filteredTransferItems = this.transferItems;
      } catch (error) {
        console.error('Error loading transfer items:', error);
        this.transferItems = [];
        this.filteredTransferItems = [];
      } finally {
        this.loading = false;
      }
    },

    filterTransfers() {
      let filtered = [...this.transfers];

      // Search filter
      if (this.transferSearch) {
        const search = this.transferSearch.toLowerCase();
        filtered = filtered.filter(transfer => 
          (transfer.transfer_code && transfer.transfer_code.toLowerCase().includes(search)) ||
          (transfer.from && transfer.from.toLowerCase().includes(search)) ||
          (transfer.to && transfer.to.toLowerCase().includes(search)) ||
          (transfer.notes && transfer.notes.toLowerCase().includes(search))
        );
      }

      // Date filters
      if (this.transferDateFrom) {
        filtered = filtered.filter(transfer => 
          transfer.date && transfer.date >= this.transferDateFrom
        );
      }

      if (this.transferDateTo) {
        filtered = filtered.filter(transfer => 
          transfer.date && transfer.date <= this.transferDateTo
        );
      }

      this.filteredTransfers = filtered;
    },

    filterTransferItems() {
      let filtered = [...this.transferItems];

      // Search filter
      if (this.itemSearch) {
        const search = this.itemSearch.toLowerCase();
        filtered = filtered.filter(item => 
          (item.transfer_code && item.transfer_code.toLowerCase().includes(search)) ||
          (item.abaya_code && item.abaya_code.toLowerCase().includes(search)) ||
          (item.color_name && item.color_name.toLowerCase().includes(search)) ||
          (item.size_name && item.size_name.toLowerCase().includes(search))
        );
      }

      // Date filters
      if (this.itemDateFrom) {
        filtered = filtered.filter(item => 
          item.transfer_date && item.transfer_date >= this.itemDateFrom
        );
      }

      if (this.itemDateTo) {
        filtered = filtered.filter(item => 
          item.transfer_date && item.transfer_date <= this.itemDateTo
        );
      }

      this.filteredTransferItems = filtered;
    }
  }
}
</script>

@endsection

