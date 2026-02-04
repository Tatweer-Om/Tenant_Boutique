@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.edit_stock', [], session('locale')) ?: 'Edit Stock' }}</title>
@endpush

<style>
  body {
    font-family: 'IBM Plex Sans Arabic', sans-serif;
  }
</style>
<main class="flex-1 p-4 md:p-6">
  <div class="max-w-7xl mx-auto">
    
    <!-- Header with back button -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <a href="{{ url('view_stock') }}{{ isset($returnPage) && $returnPage > 1 ? '?page=' . $returnPage : '' }}" 
           class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
          <span class="material-symbols-outlined text-gray-600">arrow_back</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
          {{ trans('messages.edit_stock', [], session('locale')) ?: 'Edit Stock' }}
        </h1>
      </div>
    </div>

    <!-- Single compact form card -->
    <form id="update_abaya" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      @csrf
      <input type="hidden" value="{{ $stock->id }}" name="stock_id" id="stock_id"/>
      <input type="hidden" name="return_page" value="{{ $returnPage ?? 1 }}" />
      
      <!-- Form Content -->
      <div class="p-6 sm:p-8 space-y-6">
        
        <!-- Basic Information Section -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 pb-3 border-b border-gray-200">
            <span class="material-symbols-outlined text-primary text-xl">info</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.basic_info', [], session('locale')) }}</h2>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4" x-data="{ barcode: '{{ $stock->barcode ?? '' }}' }">
            <!-- Abaya Code -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.abaya_code', [], session('locale')) }}</span>
              <input type="text" 
                     name="abaya_code" 
                     id="abaya_code"
                     placeholder="{{ trans('messages.abaya_code_placeholder', [], session('locale')) }}"
                     value="{{ $stock->abaya_code ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label>

            <!-- Design Name -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.design_name', [], session('locale')) }}</span>
              <input type="text" 
                     name="design_name" 
                     id="design_name"
                     placeholder="{{ trans('messages.design_name_placeholder', [], session('locale')) }}"
                     value="{{ $stock->design_name ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label>

            <!-- Category -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.category', [], session('locale')) }}</span>
              <select class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                      name="category_id" 
                      id="category_id">
                <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                @foreach($categories as $category)
                  <option value="{{ $category->id }}" {{ $stock->category_id == $category->id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                @endforeach
              </select>
            </label>

            <!-- Barcode and Images in One Row -->
            <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Barcode (Read-only) -->
              <label class="flex flex-col">
                <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.barcode', [], session('locale')) }}</span>
                <input type="text" 
                       name="barcode" 
                       id="barcode"
                       value="{{ $stock->barcode ?? '' }}"
                       class="h-11 rounded-lg px-4 border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-primary/50 transition-all"
                       readonly />
              </label>

              <!-- Images Upload -->
              <div x-data="imageUploader()" class="flex flex-col">
                <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.abaya_images', [], session('locale')) }}</span>
                <input type="file" 
                       multiple 
                       x-ref="fileInput" 
                       @change="handleFiles($event)"
                       class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition w-full mb-2" 
                       id="images" 
                       name="images[]" />
                
                <!-- EXISTING DB IMAGES -->
                <div class="flex flex-wrap gap-2 mt-auto" id="imageContainer">
                  @foreach($stock->images as $image)
                    <div class="relative w-20 h-20 border-2 border-gray-200 rounded-lg overflow-hidden group" id="image-{{ $image->id }}">
                      <img src="{{ asset($image->image_path) }}" class="object-cover w-full h-full" />
                      <button type="button"
                              class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-red-700 opacity-0 group-hover:opacity-100 transition-opacity delete-image"
                              data-id="{{ $image->id }}">
                        <span class="material-symbols-outlined text-xs">close</span>
                      </button>
                    </div>
                  @endforeach
                </div>

                <!-- NEW PREVIEW IMAGES -->
                <div class="flex flex-wrap gap-2 mt-2">
                  <template x-for="(img, index) in images" :key="index">
                    <div class="relative w-20 h-20 border-2 border-gray-200 rounded-lg overflow-hidden group">
                      <img :src="img.url" class="object-cover w-full h-full" />
                      <button type="button"
                              @click="removeImage(index)"
                              class="absolute top-1 right-1 bg-gray-700 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-black opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="material-symbols-outlined text-xs">close</span>
                      </button>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            
            <!-- Hidden description field for form submission -->
            <input type="hidden" name="abaya_notes" id="abaya_notes" value="{{ $stock->abaya_notes ?? '' }}" />
          </div>
        </div>

        <!-- Pricing Section (no tailor) -->
        <div class="space-y-4 pt-4 border-t border-gray-200">
          <div class="flex items-center gap-2 pb-3 border-b border-gray-200">
            <span class="material-symbols-outlined text-primary text-xl">attach_money</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.costs_tailors', [], session('locale')) ?: 'Pricing' }}</h2>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Cost Price -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.cost_price', [], session('locale')) }}</span>
              <input type="number"
                     step="0.01"
                     placeholder="{{ trans('messages.cost_price_placeholder', [], session('locale')) }}"
                     value="{{ $stock->cost_price ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                     name="cost_price" 
                     id="cost_price" />
            </label>

            <!-- Sale Price -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.sale_price', [], session('locale')) }}</span>
              <input type="number" 
                     step="0.01"
                     name="sales_price" 
                     id="sales_price"
                     placeholder="{{ trans('messages.sale_price_placeholder', [], session('locale')) }}"
                     value="{{ $stock->sales_price ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label>

            {{-- Tailor Charges - commented out for Stock module (no tailor) --}}
            {{-- <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.tailor_value', [], session('locale')) }}</span>
              <input type="number" 
                     step="0.01"
                     name="tailor_charges" 
                     id="tailor_charges"
                     placeholder="{{ trans('messages.tailor_value_placeholder', [], session('locale')) }}"
                     value="{{ $stock->tailor_charges ?? '' }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
            </label> --}}
          </div>
        </div>

        <!-- Availability Options Section -->
        <div x-data="{ mode: '{{ $stock->mode ?? 'color_size' }}' }" class="space-y-4 pt-4 border-t border-gray-200">
          <div class="flex items-center gap-2 pb-3 border-b border-gray-200">
            <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.availability_options', [], session('locale')) }}</h2>
          </div>

          <!-- Minimum Stock Alert -->
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4 border border-yellow-300 bg-yellow-50 p-4 rounded-xl">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-yellow-600 text-xl">warning</span>
              <div>
                <label class="text-sm font-semibold text-yellow-800 block">
                  {{ trans('messages.general_minimum_abaya', [], session('locale')) }}
                </label>
                <p class="text-xs text-yellow-600">
                  {{ trans('messages.system_alert_on_min_stock', [], session('locale')) }}
                </p>
              </div>
            </div>
            <input type="number" 
                   placeholder="0" 
                   name="notification_limit" 
                   id="notification_limit" 
                   value="{{ $stock->notification_limit ?? '' }}"
                   class="h-10 w-32 sm:w-40 rounded-lg text-center border border-yellow-400 focus:ring-2 focus:ring-yellow-400/50 focus:border-yellow-400 bg-white shadow-sm" />
          </div>

          <!-- Mode Selection -->
          <div class="w-full">
            <label class="flex items-center gap-2 p-3 border border-primary bg-primary/5 rounded-lg cursor-pointer transition hover:bg-primary/10 w-full">
              <input type="radio" name="mode" value="color_size" x-model="mode" checked/>
              <span class="text-sm font-medium">{{ trans('messages.by_color_and_size', [], session('locale')) }}</span>
            </label>
          </div>

          <!-- Color Only Mode -->
          <div x-show="mode === 'color'" 
               x-transition 
               x-data="{
                 colors: [
                   @foreach($colors as $c)
                     { id: {{ $c->id }}, name: '{{ session('locale') == 'ar' ? addslashes($c->color_name_ar) : addslashes($c->color_name_en) }}', color_code: '{{ $c->color_code }}' },
                   @endforeach
                 ],
                 rows: [
                   @foreach($stock->colors as $row)
                     { color_id: {{ $row->color_id }}, qty: {{ $row->qty }} },
                   @endforeach
                 ]
               }">
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 border text-right">{{ trans('messages.color', [], session('locale')) }}</th>
                    <th class="px-4 py-2 border text-right">{{ trans('messages.quantity', [], session('locale')) }}</th>
                    <th class="px-4 py-2 border text-center">{{ trans('messages.action', [], session('locale')) }}</th>
                  </tr>
                </thead>
                <tbody>
                  <template x-for="(row, index) in rows" :key="index">
                    <tr>
                      <td class="px-4 py-2 border flex items-center gap-2">
                        <div class="w-5 h-5 rounded-full border" :style="'background:' + colors.find(c => c.id === row.color_id).color_code"></div>
                        <span x-text="colors.find(c => c.id === row.color_id).name"></span>
                        <input type="hidden" :name="'colors[' + row.color_id + '][color_id]'" :value="row.color_id">
                      </td>
                      <td class="px-4 py-2 border">
                        <input type="number" 
                               data-validate="quantity"
                               class="h-10 w-24 text-center rounded-md border border-gray-300 bg-gray-50 cursor-not-allowed" 
                               :name="'colors[' + row.color_id + '][qty]'"
                               x-model="row.qty" 
                               readonly
                               placeholder="0">
                      </td>
                      <td class="px-4 py-2 border text-center">
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Size Only Mode -->
          <div x-show="mode === 'size'" 
               x-transition 
               x-data="{
                 availableSizes: [
                   @foreach($sizes as $s)
                     { id: {{ $s->id }}, name: '{{ session('locale') == 'ar' ? addslashes($s->size_name_ar) : addslashes($s->size_name_en) }}' },
                   @endforeach
                 ],
                 rows: [
                   @foreach($stock->sizes as $row)
                     { size_id: {{ $row->size_id }}, size_name: '{{ session('locale') == 'ar' ? addslashes($row->size->size_name_ar ?? '') : addslashes($row->size->size_name_en ?? '') }}', qty: {{ $row->qty }} },
                   @endforeach
                 ]
               }">
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 border text-right">{{ trans('messages.size', [], session('locale')) }}</th>
                    <th class="px-4 py-2 border text-right">{{ trans('messages.quantity', [], session('locale')) }}</th>
                    <th class="px-4 py-2 border text-center">{{ trans('messages.action', [], session('locale')) }}</th>
                  </tr>
                </thead>
                <tbody>
                  <template x-for="(row, index) in rows" :key="index">
                    <tr>
                      <td class="border px-4 py-2 text-right" x-text="row.size_name">
                        <input type="hidden" :name="'sizes['+row.size_id+'][size_id]'" :value="row.size_id">
                      </td>
                      <td class="border px-4 py-2">
                        <input type="number" 
                               data-validate="quantity"
                               class="w-24 h-10 text-center border border-gray-300 rounded-md bg-gray-50 cursor-not-allowed"
                               :name="'sizes['+row.size_id+'][qty]'"
                               x-model="row.qty" 
                               readonly
                               placeholder="0">
                      </td>
                      <td class="border px-4 py-2 text-center">
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Color & Size Mode -->
          <div x-show="mode === 'color_size'" 
               x-transition 
               x-data="{
                 availableColors: [
                   @foreach($colors as $c)
                     { id: {{ $c->id }}, name: '{{ session('locale') == 'ar' ? addslashes($c->color_name_ar) : addslashes($c->color_name_en) }}', color_code: '{{ $c->color_code }}' },
                   @endforeach
                 ],
                 availableSizes: [
                   @foreach($sizes as $s)
                     { id: {{ $s->id }}, name: '{{ session('locale') == 'ar' ? addslashes($s->size_name_ar) : addslashes($s->size_name_en) }}' },
                   @endforeach
                 ],
                 colorSizes: [
                   @foreach($stock->colorSizes as $cs)
                     { color_id: {{ $cs->color_id }}, size_id: {{ $cs->size_id }}, qty: {{ $cs->qty }} },
                   @endforeach
                 ],
                 checkAndMergeDuplicate(currentIndex) {
                   if (!this.colorSizes || !this.colorSizes[currentIndex]) return;
                   const currentItem = this.colorSizes[currentIndex];
                   if (!currentItem.color_id || !currentItem.size_id) return;
                   for (let i = 0; i < this.colorSizes.length; i++) {
                     if (i === currentIndex) continue;
                     const otherItem = this.colorSizes[i];
                     if (otherItem && otherItem.color_id == currentItem.color_id && otherItem.size_id == currentItem.size_id) {
                       const currentQty = parseInt(currentItem.qty) || 0;
                       const otherQty = parseInt(otherItem.qty) || 0;
                       otherItem.qty = currentQty + otherQty;
                       this.colorSizes.splice(currentIndex, 1);
                       if (typeof show_notification !== 'undefined') {
                         show_notification('success', '{{ trans('messages.quantity_merged', [], session('locale')) ?: 'Quantity merged with existing color/size combination' }}');
                       }
                       return;
                     }
                   }
                 },
                 addColorSizeRow() {
                   const hasEmptyRow = this.colorSizes && this.colorSizes.some(item => !item.color_id && !item.size_id);
                   if (!hasEmptyRow) {
                     if (!this.colorSizes) this.colorSizes = [];
                     this.colorSizes.push({ color_id: '', size_id: '', qty: 1 });
                   }
                 }
               }">
            <!-- Mobile cards -->
            <div class="md:hidden space-y-3">
              <template x-for="(item, index) in colorSizes" :key="'m-' + index">
                <div class="border border-gray-200 rounded-xl p-4 bg-white">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <p class="text-sm font-semibold text-gray-800">
                        {{ trans('messages.color', [], session('locale')) }} / {{ trans('messages.size', [], session('locale')) }}
                      </p>
                      <p class="text-xs text-gray-500 mt-0.5">
                        {{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}
                      </p>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 gap-3 mt-3">
                    <div>
                      <label class="block text-xs font-semibold text-gray-600 mb-1">{{ trans('messages.color', [], session('locale')) }}</label>
                      <select class="h-11 w-full rounded-lg px-3 border border-gray-300 text-sm bg-gray-50 cursor-not-allowed"
                              :value="item.color_id"
                              @change.prevent
                              @mousedown.prevent
                              style="pointer-events: none;">
                        <option value="">{{ trans('messages.select_color', [], session('locale')) ?: 'Select Color' }}</option>
                        <template x-for="c in availableColors" :key="c.id">
                          <option :value="c.id" x-text="c.name" :selected="c.id == item.color_id"></option>
                        </template>
                      </select>
                    </div>
                    <div>
                      <label class="block text-xs font-semibold text-gray-600 mb-1">{{ trans('messages.size', [], session('locale')) }}</label>
                      <select class="h-11 w-full rounded-lg px-3 border border-gray-300 text-sm bg-gray-50 cursor-not-allowed"
                              :value="item.size_id"
                              @change.prevent
                              @mousedown.prevent
                              style="pointer-events: none;">
                        <option value="">{{ trans('messages.select_size', [], session('locale')) ?: 'Select Size' }}</option>
                        <template x-for="s in availableSizes" :key="s.id">
                          <option :value="s.id" x-text="s.name" :selected="s.id == item.size_id"></option>
                        </template>
                      </select>
                    </div>
                    <div>
                      <label class="block text-xs font-semibold text-gray-600 mb-1">{{ trans('messages.quantity', [], session('locale')) ?: 'Quantity' }}</label>
                      <input type="number"
                             data-validate="quantity"
                             class="w-full h-11 text-center border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                             x-model="item.qty"
                             readonly
                             placeholder="0"
                             min="1">
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block overflow-x-auto">
              <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 border text-right">{{ trans('messages.color', [], session('locale')) }}</th>
                    <th class="px-4 py-2 border text-right">{{ trans('messages.size', [], session('locale')) }}</th>
                    <th class="px-4 py-2 border text-right">{{ trans('messages.quantity', [], session('locale')) }}</th>
                    <th class="px-4 py-2 border text-center">{{ trans('messages.action', [], session('locale')) }}</th>
                  </tr>
                </thead>
                <tbody>
                  <template x-for="(item, index) in colorSizes" :key="index">
                    <tr>
                      <td class="px-4 py-2 border">
                        <select class="h-10 w-full rounded-lg px-3 border border-gray-300 text-sm bg-gray-50 cursor-not-allowed"
                                :value="item.color_id"
                                @change.prevent
                                @mousedown.prevent
                                style="pointer-events: none;">
                          <option value="">{{ trans('messages.select_color', [], session('locale')) ?: 'Select Color' }}</option>
                          <template x-for="c in availableColors" :key="c.id">
                            <option :value="c.id" x-text="c.name" :selected="c.id == item.color_id"></option>
                          </template>
                        </select>
                      </td>
                      <td class="border px-4 py-2">
                        <select class="h-10 w-full rounded-lg px-3 border border-gray-300 text-sm bg-gray-50 cursor-not-allowed"
                                :value="item.size_id"
                                @change.prevent
                                @mousedown.prevent
                                style="pointer-events: none;">
                          <option value="">{{ trans('messages.select_size', [], session('locale')) ?: 'Select Size' }}</option>
                          <template x-for="s in availableSizes" :key="s.id">
                            <option :value="s.id" x-text="s.name" :selected="s.id == item.size_id"></option>
                          </template>
                        </select>
                      </td>
                      <td class="border px-4 py-2">
                        <input type="number"
                               data-validate="quantity"
                               class="w-24 h-10 text-center border border-gray-300 rounded-md bg-gray-50 cursor-not-allowed"
                               x-model="item.qty"
                               readonly
                               placeholder="0"
                               min="1">
                      </td>
                      <td class="border px-4 py-2 text-center">
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>

            <!-- Hidden inputs for submission (shared for mobile + desktop) -->
            <div class="hidden">
              <template x-for="(item, index) in colorSizes" :key="'h-' + index">
                <template x-if="item.color_id && item.size_id">
                  <div>
                    <input type="hidden" :name="'color_sizes[' + item.color_id + '][' + item.size_id + '][qty]'" :value="item.qty">
                    <input type="hidden" :name="'color_sizes[' + item.color_id + '][' + item.size_id + '][size_id]'" :value="item.size_id">
                  </div>
                </template>
              </template>
            </div>
          </div>
        </div>

      </div>

      <!-- Form Footer with Submit Button -->
      <div class="bg-gray-50 px-6 sm:px-8 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
        <a href="{{ url('view_stock') }}{{ isset($returnPage) && $returnPage > 1 ? '?page=' . $returnPage : '' }}" 
           class="text-sm text-gray-600 hover:text-gray-800 transition-colors">
          {{ trans('messages.cancel', [], session('locale')) }}
        </a>
        <button type="submit"
                class="w-full sm:w-auto px-8 py-3 bg-primary text-white font-semibold rounded-xl shadow-md hover:bg-primary/90 hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
          <span class="material-symbols-outlined text-lg">save</span>
          <span>{{ trans('messages.save', [], session('locale')) }}</span>
        </button>
      </div>

    </form>

  </div>
</main>

@include('layouts.footer')
@endsection
