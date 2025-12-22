@extends('layouts.header')

@section('main')
@push('title')
<title>Maintenance Status</title>
@endpush

<main class="flex-1 p-4 md:p-6" x-data="maintenanceApp" x-init="init()">

  <!-- 📊 Statistics Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Delivered to Tailor -->
    <div class="bg-white rounded-2xl shadow-md p-6 border-l-4 border-orange-500">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-500 text-sm mb-1">Delivered to Tailor</p>
          <p class="text-3xl font-bold text-orange-600" x-text="statistics.delivered_to_tailor || 0"></p>
        </div>
        <div class="bg-orange-100 p-4 rounded-full">
          <span class="material-symbols-outlined text-orange-600 text-4xl">send</span>
        </div>
      </div>
    </div>

    <!-- Received from Tailor -->
    <div class="bg-white rounded-2xl shadow-md p-6 border-l-4 border-blue-500">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-500 text-sm mb-1">Received from Tailor</p>
          <p class="text-3xl font-bold text-blue-600" x-text="statistics.received_from_tailor || 0"></p>
        </div>
        <div class="bg-blue-100 p-4 rounded-full">
          <span class="material-symbols-outlined text-blue-600 text-4xl">inventory_2</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="bg-white rounded-2xl shadow-md p-4 mb-6">
    <div class="flex gap-3 border-b border-gray-200 overflow-x-auto no-scrollbar">
      <button @click="activeTab = 'current'"
              :class="activeTab === 'current' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-4 flex items-center gap-1 whitespace-nowrap">
        <span class="material-symbols-outlined text-base">list</span> Current Items
      </button>
      <button @click="activeTab = 'history'"
              :class="activeTab === 'history' ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)] font-bold' : 'text-gray-600'"
              class="py-3 px-4 flex items-center gap-1 whitespace-nowrap">
        <span class="material-symbols-outlined text-base">history</span> Repair History
      </button>
    </div>
  </div>

  <!-- Current Items Tab -->
  <div x-show="activeTab === 'current'" x-transition>
    <!-- 🔍 Search -->
    <div class="bg-white rounded-2xl shadow-md p-4 mb-6">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <input type="text" 
               placeholder="Search by customer name, design, code, transfer number..."
               x-model="search"
               class="form-input w-full md:w-72 border-gray-300 rounded-xl px-4 py-2 shadow-sm focus:ring-primary">
      </div>
    </div>

    <!-- 📋 Items List -->
  <div class="bg-white rounded-2xl shadow-md overflow-hidden">
    <div x-show="loading" class="p-8 text-center">
      <span class="material-symbols-outlined animate-spin text-4xl text-indigo-600">sync</span>
      <p class="mt-2 text-gray-500">Loading...</p>
    </div>

    <div x-show="!loading && filteredItems().length === 0" class="p-8 text-center text-gray-500">
      No items found
    </div>

    <!-- Desktop Table -->
    <table class="w-full text-sm hidden md:table" x-show="!loading && filteredItems().length > 0">
      <thead class="bg-gray-100 text-gray-700">
        <tr>
          <th class="py-3 px-4 text-left">Image</th>
          <th class="py-3 px-4 text-left">Design Name</th>
          <th class="py-3 px-4 text-left">Code</th>
          <th class="py-3 px-4 text-left">Order No</th>
          <th class="py-3 px-4 text-left">Customer</th>
          <th class="py-3 px-4 text-left">Customer Phone</th>
          <th class="py-3 px-4 text-left">Status</th>
          <th class="py-3 px-4 text-left">Actions</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="item in paginatedItems()" :key="item.id">
          <tr class="border-t hover:bg-indigo-50 transition">
            <td class="py-3 px-4">
              <img :src="item.image" 
                   :alt="item.design_name"
                   class="w-16 h-16 object-cover rounded-lg">
            </td>
            <td class="py-3 px-4 font-semibold" x-text="item.design_name"></td>
            <td class="py-3 px-4 text-gray-600" x-text="item.abaya_code"></td>
            <td class="py-3 px-4">
              <p class="font-semibold text-indigo-600" x-text="item.order_no || '—'"></p>
            </td>
            <td class="py-3 px-4">
              <p class="font-medium" x-text="item.customer_name || 'N/A'"></p>
            </td>
            <td class="py-3 px-4">
              <p class="text-gray-600" x-text="item.customer_phone || 'N/A'"></p>
            </td>
            <td class="py-3 px-4">
              <span x-show="item.maintenance_status" 
                    :class="getStatusBadgeClass(item.maintenance_status)" 
                    class="px-3 py-1 rounded-full text-xs font-semibold"
                    x-text="getStatusLabel(item.maintenance_status)"></span>
              <span x-show="!item.maintenance_status" 
                    class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                Not in Maintenance
              </span>
            </td>
            <td class="py-3 px-4">
              <button @click="openActionModal(item)"
                      :class="item.maintenance_status === 'delivered_to_tailor' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700'"
                      class="text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                <span x-show="item.maintenance_status === 'delivered_to_tailor'">Receive from Tailor</span>
                <span x-show="item.maintenance_status !== 'delivered_to_tailor' && item.maintenance_status !== 'received_from_tailor'">Send to Tailor</span>
                <span x-show="item.maintenance_status === 'received_from_tailor'" class="opacity-50 cursor-not-allowed">Completed</span>
              </button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <!-- Mobile Cards -->
    <div class="md:hidden divide-y" x-show="!loading && filteredItems().length > 0">
      <template x-for="item in paginatedItems()" :key="item.id">
        <div class="p-4">
          <div class="flex gap-4">
            <img :src="item.image" 
                 :alt="item.design_name"
                 class="w-20 h-20 object-cover rounded-lg">
            <div class="flex-1">
              <h3 class="font-semibold text-lg" x-text="item.design_name || 'N/A'"></h3>
              <p class="text-sm text-gray-600" x-text="'Code: ' + (item.abaya_code || 'N/A')"></p>
              <p class="text-sm text-indigo-600 mt-1" x-text="'Order No: ' + (item.order_no || '—')"></p>
              <p class="text-sm mt-1">
                <span class="font-medium" x-text="item.customer_name || 'N/A'"></span>
                <span class="text-gray-500" x-text="' - ' + (item.customer_phone || 'N/A')"></span>
              </p>
              <div class="flex gap-2 mt-2">
                <span :class="getStatusBadgeClass(item.maintenance_status)" 
                      class="inline-block px-3 py-1 rounded-full text-xs font-semibold"
                      x-text="getStatusLabel(item.maintenance_status)"></span>
              </div>
              <button @click="openActionModal(item)"
                      :class="item.maintenance_status === 'delivered_to_tailor' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700'"
                      class="text-white px-4 py-2 rounded-lg text-sm font-semibold transition mt-2">
                <span x-show="item.maintenance_status === 'delivered_to_tailor'">Receive from Tailor</span>
                <span x-show="item.maintenance_status !== 'delivered_to_tailor'">Send to Tailor</span>
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Pagination -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mt-6 px-4 pb-4" x-show="!loading && filteredItems().length > 0">
      <p class="text-sm text-gray-500">
        Showing
        <span x-text="startItem()"></span> -
        <span x-text="endItem()"></span>
        of
        <span x-text="filteredItems().length"></span>
        items
      </p>

      <div class="flex items-center gap-2 justify-end">
        <button @click="prevPage()" 
                class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm transition"
                :disabled="page === 1"
                :class="page === 1 ? 'opacity-50 cursor-not-allowed' : ''">
          Previous
        </button>

        <template x-for="p in pageNumbers()" :key="p">
          <button @click="goToPage(p)"
                  :class="page === p 
                           ? 'px-3 py-1 bg-indigo-600 text-white rounded-lg text-sm font-semibold' 
                           : 'px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm'">
            <span x-text="p"></span>
          </button>
        </template>

        <button @click="nextPage()" 
                class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm transition"
                :disabled="page === totalPages()"
                :class="page === totalPages() ? 'opacity-50 cursor-not-allowed' : ''">
          Next
        </button>
      </div>
    </div>
  </div>
  </div>

  <!-- Repair History Tab -->
  <div x-show="activeTab === 'history'" x-transition>
    <div class="bg-white rounded-2xl shadow-md overflow-hidden">
      <div x-show="loadingHistory" class="p-8 text-center">
        <span class="material-symbols-outlined animate-spin text-4xl text-indigo-600">sync</span>
        <p class="mt-2 text-gray-500">Loading history...</p>
      </div>

      <div x-show="!loadingHistory && repairHistory.length === 0" class="p-8 text-center text-gray-500">
        No repair history found
      </div>

      <!-- History Table -->
      <table class="w-full text-sm hidden md:table" x-show="!loadingHistory && repairHistory.length > 0">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="py-3 px-4 text-left">Order No</th>
            <th class="py-3 px-4 text-left">Transfer Number</th>
            <th class="py-3 px-4 text-left">Design Name</th>
            <th class="py-3 px-4 text-left">Code</th>
            <th class="py-3 px-4 text-left">Customer</th>
            <th class="py-3 px-4 text-left">Customer Phone</th>
            <th class="py-3 px-4 text-left">Tailor</th>
            <th class="py-3 px-4 text-left">Sent Date</th>
            <th class="py-3 px-4 text-left">Received Date</th>
            <th class="py-3 px-4 text-left">Delivery Charges</th>
            <th class="py-3 px-4 text-left">Repair Cost</th>
            <th class="py-3 px-4 text-left">Cost Bearer</th>
            <th class="py-3 px-4 text-left">Status</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="item in repairHistory" :key="item.id">
            <tr class="border-t hover:bg-indigo-50 transition">
              <td class="py-3 px-4 font-semibold text-indigo-600" x-text="item.order_no || '—'"></td>
              <td class="py-3 px-4 font-semibold text-indigo-600" x-text="item.transfer_number || '—'"></td>
              <td class="py-3 px-4 font-semibold" x-text="item.design_name || 'N/A'"></td>
              <td class="py-3 px-4 text-gray-600" x-text="item.abaya_code || 'N/A'"></td>
              <td class="py-3 px-4">
                <p class="font-medium" x-text="item.customer_name || 'N/A'"></p>
              </td>
              <td class="py-3 px-4">
                <p class="text-gray-600" x-text="item.customer_phone || 'N/A'"></p>
              </td>
              <td class="py-3 px-4">
                <p class="font-medium" x-text="item.tailor_name || 'N/A'"></p>
              </td>
              <td class="py-3 px-4 text-sm text-gray-600" x-text="item.sent_date || '—'"></td>
              <td class="py-3 px-4 text-sm text-gray-600" x-text="item.received_date || '—'"></td>
              <td class="py-3 px-4 text-sm font-semibold" x-text="item.delivery_charges ? item.delivery_charges + ' ر.ع' : '—'"></td>
              <td class="py-3 px-4 text-sm font-semibold" x-text="item.repair_cost ? item.repair_cost + ' ر.ع' : '—'"></td>
              <td class="py-3 px-4">
                <span x-show="item.cost_bearer" 
                      :class="item.cost_bearer === 'customer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                      class="px-3 py-1 rounded-full text-xs font-semibold"
                      x-text="item.cost_bearer === 'customer' ? 'Customer' : 'Company'"></span>
                <span x-show="!item.cost_bearer" class="text-gray-400">—</span>
              </td>
              <td class="py-3 px-4">
                <span :class="getStatusBadgeClass(item.maintenance_status)" 
                      class="px-3 py-1 rounded-full text-xs font-semibold"
                      x-text="getStatusLabel(item.maintenance_status)"></span>
              </td>
            </tr>
          </template>
        </tbody>
      </table>

      <!-- Mobile History Cards -->
      <div class="md:hidden divide-y" x-show="!loadingHistory && repairHistory.length > 0">
        <template x-for="item in repairHistory" :key="item.id">
          <div class="p-4">
            <div class="space-y-2">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-semibold text-lg" x-text="item.design_name || 'N/A'"></h3>
                  <p class="text-sm text-gray-600" x-text="'Code: ' + (item.abaya_code || 'N/A')"></p>
                  <p class="text-sm text-indigo-600 mt-1" x-text="'Order No: ' + (item.order_no || '—')"></p>
                  <p class="text-sm text-indigo-600 mt-1" x-text="'Transfer: ' + (item.transfer_number || '—')"></p>
                </div>
                <span :class="getStatusBadgeClass(item.maintenance_status)" 
                      class="inline-block px-3 py-1 rounded-full text-xs font-semibold"
                      x-text="getStatusLabel(item.maintenance_status)"></span>
              </div>
              <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <p class="text-gray-600">Customer:</p>
                  <p class="font-medium" x-text="item.customer_name || 'N/A'"></p>
                  <p class="text-gray-500" x-text="item.customer_phone || 'N/A'"></p>
                </div>
                <div>
                  <p class="text-gray-600">Tailor:</p>
                  <p class="font-medium" x-text="item.tailor_name || 'N/A'"></p>
                </div>
                <div>
                  <p class="text-gray-600">Sent:</p>
                  <p x-text="item.sent_date || '—'"></p>
                </div>
                <div>
                  <p class="text-gray-600">Received:</p>
                  <p x-text="item.received_date || '—'"></p>
                </div>
                <div>
                  <p class="text-gray-600">Delivery Charges:</p>
                  <p class="font-semibold" x-text="item.delivery_charges ? item.delivery_charges + ' ر.ع' : '—'"></p>
                </div>
                <div>
                  <p class="text-gray-600">Repair Cost:</p>
                  <p class="font-semibold" x-text="item.repair_cost ? item.repair_cost + ' ر.ع' : '—'"></p>
                </div>
                <div class="col-span-2">
                  <p class="text-gray-600">Cost Bearer:</p>
                  <span x-show="item.cost_bearer" 
                        :class="item.cost_bearer === 'customer' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                        class="inline-block px-3 py-1 rounded-full text-xs font-semibold"
                        x-text="item.cost_bearer === 'customer' ? 'Customer' : 'Company'"></span>
                  <span x-show="!item.cost_bearer" class="text-gray-400">—</span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- 🔧 Modal: Send/Receive Action -->
  <div x-show="showActionModal" 
       x-transition.opacity
       class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
       @click.away="showActionModal = false">
    <div @click.stop
         x-transition.scale
         class="bg-white w-full max-w-md mx-4 rounded-2xl shadow-2xl p-6">
      <h2 class="text-2xl font-bold mb-4" x-text="selectedItem.maintenance_status === 'delivered_to_tailor' ? 'Receive from Tailor' : 'Send to Tailor'"></h2>
      
      <div class="mb-4">
        <p class="text-gray-600 mb-2" x-text="'Design: ' + (selectedItem.design_name || 'N/A')"></p>
        <p class="text-gray-600 mb-2" x-text="'Code: ' + (selectedItem.abaya_code || 'N/A')"></p>
        <p class="text-gray-600 mb-2" x-show="selectedItem.order_no" x-text="'Order No: ' + selectedItem.order_no"></p>
        <p class="text-gray-600 mb-2" x-text="'Customer: ' + (selectedItem.customer_name || 'N/A') + ' (' + (selectedItem.customer_phone || 'N/A') + ')'"></p>
      </div>

      <div class="mb-4" x-show="selectedItem.maintenance_status !== 'delivered_to_tailor'">
        <label class="block text-sm font-medium mb-2">Select Tailor</label>
        <select x-model="selectedTailorId" 
                class="form-select w-full border-gray-300 rounded-lg">
          <option value="">Select a tailor</option>
          <template x-for="tailor in tailors" :key="tailor.id">
            <option :value="tailor.id" x-text="tailor.name"></option>
          </template>
        </select>
      </div>

      <div class="mb-4" x-show="selectedItem.maintenance_status === 'delivered_to_tailor'">
        <label class="block text-sm font-medium mb-2">Delivery Charges (OMR)</label>
        <input type="number" 
               step="0.001"
               x-model="deliveryCharges"
               class="form-input w-full border-gray-300 rounded-lg">
      </div>

      <div class="mb-4" x-show="selectedItem.maintenance_status === 'delivered_to_tailor'">
        <label class="block text-sm font-medium mb-2">Repair Cost (OMR)</label>
        <input type="number" 
               step="0.001"
               x-model="repairCost"
               class="form-input w-full border-gray-300 rounded-lg">
      </div>

      <div class="mb-4" x-show="selectedItem.maintenance_status === 'delivered_to_tailor'">
        <label class="block text-sm font-medium mb-2">Cost Bearer</label>
        <select x-model="costBearer" 
                class="form-select w-full border-gray-300 rounded-lg">
          <option value="">Select cost bearer</option>
          <option value="customer">Customer</option>
          <option value="company">Company</option>
        </select>
      </div>

      <div class="flex gap-3 justify-end">
        <button @click="showActionModal = false"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
          Cancel
        </button>
        <button @click="performAction()"
                :class="selectedItem.maintenance_status === 'delivered_to_tailor' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700'"
                class="px-4 py-2 text-white rounded-lg">
          <span x-text="selectedItem.maintenance_status === 'delivered_to_tailor' ? 'Confirm Receive' : 'Send'"></span>
        </button>
      </div>
    </div>
  </div>

</main>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('maintenanceApp', () => ({
    loading: false,
    loadingHistory: false,
    activeTab: 'current',
    search: '',
    items: [],
    repairHistory: [],
    tailors: [],
    showActionModal: false,
    selectedItem: {},
    selectedTailorId: '',
    deliveryCharges: 0,
    repairCost: 0,
    costBearer: '',

    // Pagination
    page: 1,
    perPage: 10,

    statistics: {
      delivered_to_tailor: 0,
      received_from_tailor: 0,
    },

    async init() {
      await this.loadData();
      await this.loadRepairHistory();
      // Reset to page 1 when search changes
      this.$watch('search', () => {
        this.page = 1;
      });
    },

    async loadData() {
      this.loading = true;
      try {
        const response = await fetch('{{ route('maintenance.data') }}');
        const data = await response.json();
        
        if (data.success) {
          this.statistics = data.statistics || {};
          this.items = data.items || [];
          this.tailors = data.tailors || [];
          // Debug: log first item to check data structure
          if (this.items.length > 0) {
            console.log('First item data:', this.items[0]);
          }
        } else {
          throw new Error(data.message || 'Failed to load data');
        }
      } catch (error) {
        console.error('Error loading data:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
          });
        } else {
          alert('Error: ' + error.message);
        }
      } finally {
        this.loading = false;
      }
    },

    async loadRepairHistory() {
      this.loadingHistory = true;
      try {
        const response = await fetch('{{ route('maintenance.history') }}');
        const data = await response.json();
        
        if (data.success) {
          this.repairHistory = data.history || [];
        } else {
          throw new Error(data.message || 'Failed to load repair history');
        }
      } catch (error) {
        console.error('Error loading repair history:', error);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
          });
        }
      } finally {
        this.loadingHistory = false;
      }
    },

    filteredItems() {
      if (!this.search) return this.items;

      const searchLower = this.search.toLowerCase();
      return this.items.filter(item => 
        item.design_name.toLowerCase().includes(searchLower) ||
        item.abaya_code.toLowerCase().includes(searchLower) ||
        item.customer_name.toLowerCase().includes(searchLower) ||
        item.customer_phone.includes(this.search) ||
        (item.order_no && item.order_no.toLowerCase().includes(searchLower))
      );
    },

    paginatedItems() {
      const filtered = this.filteredItems();
      const start = (this.page - 1) * this.perPage;
      return filtered.slice(start, start + this.perPage);
    },

    totalPages() {
      const total = this.filteredItems().length;
      return total === 0 ? 1 : Math.ceil(total / this.perPage);
    },

    pageNumbers() {
      const total = this.totalPages();
      const current = this.page;
      const pages = [];
      
      if (total <= 5) {
        // Show all pages if 5 or fewer
        for (let i = 1; i <= total; i++) {
          pages.push(i);
        }
      } else {
        // Show pages around current page
        let start = Math.max(1, current - 2);
        let end = Math.min(total, start + 4);
        
        // Adjust if we're near the end
        if (end - start < 4) {
          start = Math.max(1, end - 4);
        }
        
        for (let i = start; i <= end; i++) {
          pages.push(i);
        }
      }
      
      return pages;
    },

    startItem() {
      if (this.filteredItems().length === 0) return 0;
      return (this.page - 1) * this.perPage + 1;
    },

    endItem() {
      return Math.min(this.page * this.perPage, this.filteredItems().length);
    },

    nextPage() {
      if (this.page < this.totalPages()) {
        this.page++;
        this.scrollToTop();
      }
    },

    prevPage() {
      if (this.page > 1) {
        this.page--;
        this.scrollToTop();
      }
    },

    goToPage(pageNum) {
      if (pageNum >= 1 && pageNum <= this.totalPages()) {
        this.page = pageNum;
        this.scrollToTop();
      }
    },

    scrollToTop() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    openActionModal(item) {
      this.selectedItem = item;
      this.selectedTailorId = '';
      this.deliveryCharges = item.delivery_charges || 0;
      this.repairCost = item.repair_cost || 0;
      this.costBearer = item.cost_bearer || '';
      this.showActionModal = true;
    },

    async performAction() {
      if (this.selectedItem.maintenance_status === 'received_from_tailor') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'info',
            title: 'Already Completed',
            text: 'This item has already been received from tailor'
          });
        }
        return;
      }

      if (this.selectedItem.maintenance_status === 'delivered_to_tailor') {
        // Receive from tailor
        if (!this.costBearer) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: 'Required Field',
              text: 'Please select cost bearer'
            });
          } else {
            alert('Please select cost bearer');
          }
          return;
        }

        try {
          const response = await fetch('{{ route('maintenance.receive') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              item_id: this.selectedItem.id,
              delivery_charges: this.deliveryCharges,
              repair_cost: this.repairCost,
              cost_bearer: this.costBearer
            })
          });

          const data = await response.json();

          if (data.success) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message
              });
            } else {
              alert(data.message);
            }
            this.showActionModal = false;
            await this.loadData();
          } else {
            throw new Error(data.message || 'Failed to receive item');
          }
        } catch (error) {
          console.error('Error:', error);
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error.message
            });
          } else {
            alert('Error: ' + error.message);
          }
        }
      } else {
        // Send to tailor
        if (!this.selectedTailorId) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'warning',
              title: 'Select Tailor',
              text: 'Please select a tailor'
            });
          } else {
            alert('Please select a tailor');
          }
          return;
        }

        try {
          const response = await fetch('{{ route('maintenance.send_repair') }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
              item_id: this.selectedItem.id,
              tailor_id: this.selectedTailorId
            })
          });

          const data = await response.json();

          if (data.success) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message
              });
            } else {
              alert(data.message);
            }
            this.showActionModal = false;
            await this.loadData();
          } else {
            throw new Error(data.message || 'Failed to send item');
          }
        } catch (error) {
          console.error('Error:', error);
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error.message
            });
          } else {
            alert('Error: ' + error.message);
          }
        }
      }
    },

    getStatusLabel(status) {
      const labels = {
        'delivered_to_tailor': 'Delivered to Tailor',
        'received_from_tailor': 'Received from Tailor'
      };
      return labels[status] || 'Not in Maintenance';
    },

    getStatusBadgeClass(status) {
      const classes = {
        'delivered_to_tailor': 'bg-orange-100 text-orange-800',
        'received_from_tailor': 'bg-blue-100 text-blue-800'
      };
      return classes[status] || 'bg-gray-100 text-gray-800';
    }
  }));
});
</script>

@include('layouts.footer')
@endsection

