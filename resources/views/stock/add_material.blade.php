@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.add_material', [], session('locale')) }}</title>
@endpush

<style>
  body {
    font-family: 'IBM Plex Sans Arabic', sans-serif;
  }
</style>
<main class="flex-1 p-4 md:p-6">
  <div class="max-w-7xl mx-auto">
    
    <!-- Header with back button and action buttons -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <a href="{{ url('view_material') }}" 
           class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
          <span class="material-symbols-outlined text-gray-600">arrow_back</span>
        </a>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
          {{ trans('messages.add_material', [], session('locale')) }}
        </h1>
      </div>
      <div class="flex items-center gap-3">
        <a href="{{ url('view_material') }}" 
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm hover:shadow-md">
          <span class="material-symbols-outlined text-lg">inventory_2</span>
          <span class="font-semibold text-sm">{{ trans('messages.view_material_lang', [], session('locale')) }}</span>
        </a>
      </div>
    </div>

    <!-- Single compact form card -->
    <form id="add_material" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
      @csrf
      
      <!-- Form Content -->
      <div class="p-6 sm:p-8 space-y-6">
        
        <!-- Basic Information Section -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 pb-3 border-b border-gray-200">
            <span class="material-symbols-outlined text-primary text-xl">info</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.basic_info', [], session('locale')) }}</h2>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Material Name -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.material_name', [], session('locale')) }}</span>
              <input type="text" 
                     placeholder="{{ trans('messages.material_name_placeholder', [], session('locale')) }}"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                     name="material_name" 
                     id="material_name" />
            </label>

            <!-- Unit -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.unit', [], session('locale')) }}</span>
              <select class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                      name="material_unit" 
                      id="material_unit">
                <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                <option value="meter">{{ trans('messages.meter', [], session('locale')) }}</option>
                <option value="piece">{{ trans('messages.piece', [], session('locale')) }}</option>
                <option value="roll">{{ trans('messages.roll', [], session('locale')) }}</option>
              </select>
            </label>

            <!-- Category -->
            <label class="flex flex-col">
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-sm font-semibold text-gray-700">{{ trans('messages.material_category', [], session('locale')) }}</span>
                <button type="button" 
                        onclick="openAddCategoryModal()"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                  <span class="material-symbols-outlined text-sm">add</span>
                  <span>{{ trans('messages.add_category', [], session('locale')) }}</span>
                </button>
              </div>
              <select class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                      name="material_category" 
                      id="material_category">
                <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
                <option value="fabric">{{ trans('messages.fabric', [], session('locale')) }}</option>
                <option value="embroidery">{{ trans('messages.embroidery', [], session('locale')) }}</option>
                <option value="accessories">{{ trans('messages.accessories', [], session('locale')) }}</option>
              </select>
            </label>

          </div>

          <!-- Description and Image in One Row -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <!-- Description -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.description', [], session('locale')) }}</span>
              <textarea placeholder="{{ trans('messages.description_placeholder', [], session('locale')) }}" 
                        rows="5"
                        class="rounded-lg px-4 py-2.5 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition resize-none" 
                        name="material_notes" 
                        id="material_notes"></textarea>
            </label>

            <!-- Image Upload -->
            <div class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.raw_material_image', [], session('locale')) }}</span>
              <label class="flex flex-col items-center justify-center w-full h-full min-h-[140px] border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-all relative group"
                     id="imageBoxLabel">
                
                <!-- Image preview -->
                <img id="imagePreview" 
                     src="" 
                     alt="{{ trans('messages.preview', [], session('locale')) }}" 
                     class="hidden absolute inset-0 w-full h-full object-cover rounded-xl" />

                <!-- Upload Icon -->
                <span class="material-symbols-outlined text-3xl text-gray-400 group-hover:text-primary transition-colors z-10" id="uploadIcon">
                  cloud_upload
                </span>

                <!-- Upload Text -->
                <p class="text-sm text-gray-500 mt-2 z-10" id="uploadText">
                  {{ trans('messages.upload_image', [], session('locale')) }}
                </p>

                <input type="file" 
                       class="hidden" 
                       name="material_image" 
                       id="material_image" 
                       accept="image/*" />
              </label>
            </div>
          </div>
        </div>

        <!-- Pricing & Quantity Section -->
        <div class="space-y-4 pt-4 border-t border-gray-200">
          <div class="flex items-center gap-2 pb-3 border-b border-gray-200">
            <span class="material-symbols-outlined text-primary text-xl">attach_money</span>
            <h2 class="text-lg font-bold text-gray-800">{{ trans('messages.qty_price', [], session('locale')) }}</h2>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Buy Price -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.buy_price', [], session('locale')) }}</span>
              <input type="number" 
                     step="0.01"
                     min="0"
                     placeholder="0.00"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                     name="purchase_price" 
                     id="purchase_price" />
            </label>

            <!-- Meters/Pieces -->
            <label class="flex flex-col">
              <span class="text-sm font-semibold text-gray-700 mb-1.5">{{ trans('messages.total_meters_pieces', [], session('locale')) }}</span>
              <input type="number" 
                     step="0.01"
                     placeholder="0"
                     class="h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" 
                     name="meters_pieces" 
                     id="meters_pieces" />
            </label>
          </div>
        </div>

      </div>

      <!-- Form Footer with Submit Button -->
      <div class="bg-gray-50 px-6 sm:px-8 py-4 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
        <a href="{{ url('view_material') }}" 
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

<!-- Add Category Modal -->
<div id="addCategoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4">
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-gray-900">{{ trans('messages.add_material_category', [], session('locale')) }}</h3>
        <button type="button" onclick="closeAddCategoryModal()" class="text-gray-400 hover:text-gray-600">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            {{ trans('messages.category_name', [], session('locale')) }}
          </label>
          <input type="text" 
                 id="newCategoryName" 
                 placeholder="{{ trans('messages.category_name_placeholder', [], session('locale')) }}"
                 class="w-full h-11 rounded-lg px-4 border border-gray-300 focus:ring-2 focus:ring-primary/50 focus:border-primary transition" />
        </div>
        <div class="flex items-center gap-3 justify-end pt-4 border-t">
          <button type="button" 
                  onclick="closeAddCategoryModal()"
                  class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
            {{ trans('messages.cancel', [], session('locale')) }}
          </button>
          <button type="button" 
                  onclick="addCategoryToSelect()"
                  class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            {{ trans('messages.add', [], session('locale')) }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function openAddCategoryModal() {
  const modal = document.getElementById('addCategoryModal');
  modal.classList.remove('hidden');
  document.getElementById('newCategoryName').value = '';
  document.getElementById('newCategoryName').focus();
}

function closeAddCategoryModal() {
  document.getElementById('addCategoryModal').classList.add('hidden');
}

function addCategoryToSelect() {
  const categoryName = document.getElementById('newCategoryName').value.trim();
  const categorySelect = document.getElementById('material_category');
  
  if (!categoryName) {
    alert('{{ trans("messages.enter_category_name", [], session("locale")) }}');
    return;
  }
  
  // Check if category already exists
  const existingOptions = Array.from(categorySelect.options).map(opt => opt.value.toLowerCase());
  if (existingOptions.includes(categoryName.toLowerCase())) {
    alert('{{ trans("messages.category_already_exists", [], session("locale")) }}');
    return;
  }
  
  // Add new option
  const newOption = document.createElement('option');
  newOption.value = categoryName;
  newOption.textContent = categoryName;
  categorySelect.appendChild(newOption);
  
  // Select the newly added category
  categorySelect.value = categoryName;
  
  // Close modal
  closeAddCategoryModal();
  
  // Show success message if Swal is available
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      icon: 'success',
      title: '{{ trans("messages.success", [], session("locale")) }}',
      text: '{{ trans("messages.category_added_successfully", [], session("locale")) }}',
      timer: 2000,
      showConfirmButton: false
    });
  }
}

// Close modal when clicking outside
document.getElementById('addCategoryModal')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeAddCategoryModal();
  }
});
</script>

@include('layouts.footer')
@include('custom_js.add_material_js')
@endsection

