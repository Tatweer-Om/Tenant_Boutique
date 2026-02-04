@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.settings', [], session('locale')) ?: 'Settings' }}</title>
@endpush

<main class="flex-1 p-4 md:p-6" x-data="settingsPage()" x-init="init()">
  <div class="w-full max-w-screen-xl mx-auto space-y-6">
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ trans('messages.settings', [], session('locale')) ?: 'Settings' }}</h1>
        <p class="text-gray-500 text-sm">{{ trans('messages.manage_company_settings', [], session('locale')) ?: 'Manage company information and system settings' }}</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white border border-pink-100 rounded-2xl shadow-sm">
      <div class="flex gap-2 p-2 border-b bg-gradient-to-r from-pink-50 via-purple-50 to-gray-50 overflow-x-auto no-scrollbar">
        <button @click="tab='company'" 
                :class="tab==='company' ? 'bg-[var(--primary-color)] text-white' : 'bg-white text-gray-700 border'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition whitespace-nowrap">
          {{ trans('messages.company_information', [], session('locale')) ?: 'Company Information' }}
        </button>
        {{-- <button @click="tab='delivery'" 
                :class="tab==='delivery' ? 'bg-[var(--primary-color)] text-white' : 'bg-white text-gray-700 border'"
              style="display: none"   class="hidden px-4 py-2 rounded-xl text-sm font-semibold transition whitespace-nowrap">
          {{ trans('messages.late_delivery_settings', [], session('locale')) ?: 'Late Delivery Settings' }}
        </button> --}}
      </div>

      <!-- Company Information Tab -->
      <section x-show="tab==='company'" class="p-6 space-y-6" x-cloak>
        <form @submit.prevent="saveCompanyInfo()" class="space-y-6">
          <!-- Company Name -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              {{ trans('messages.company_name', [], session('locale')) ?: 'Company Name' }}
            </label>
            <input type="text" 
                   x-model="settings.company_name"
                   class="w-full h-12 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-4"
                   :placeholder="'{{ trans('messages.enter_company_name', [], session('locale')) ?: 'Enter company name' }}'">
          </div>

          <!-- Company Email -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              {{ trans('messages.company_email', [], session('locale')) ?: 'Company Email' }}
            </label>
            <input type="email" 
                   x-model="settings.company_email"
                   class="w-full h-12 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-4"
                   :placeholder="'{{ trans('messages.enter_company_email', [], session('locale')) ?: 'Enter company email' }}'">
          </div>

          <!-- CR No -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              {{ trans('messages.cr_no', [], session('locale')) ?: 'Commercial Registration No (CR No)' }}
            </label>
            <input type="text" 
                   x-model="settings.company_cr_no"
                   class="w-full h-12 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-4"
                   :placeholder="'{{ trans('messages.enter_cr_no', [], session('locale')) ?: 'Enter CR No' }}'">
          </div>

          <!-- Company Address -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              {{ trans('messages.company_address', [], session('locale')) ?: 'Company Address' }}
            </label>
            <textarea x-model="settings.company_address"
                      rows="3"
                      class="w-full rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-4 py-3"
                      :placeholder="'{{ trans('messages.enter_company_address', [], session('locale')) ?: 'Enter company address' }}'"></textarea>
          </div>

          <!-- Company Logo -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              {{ trans('messages.company_logo', [], session('locale')) ?: 'Company Logo' }}
            </label>
            
            <!-- Current Logo Display -->
            <div x-show="settings.company_logo" class="mb-4">
              <img 
                   alt="Company Logo"
                   src="{{ url('images/company_logo/').'/'.$settings->company_logo }}"
                   class="w-32 h-32 object-contain border border-pink-200 rounded-xl p-2 bg-gray-50">
            </div>
            
            <!-- Logo Upload -->
            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2 px-4 py-2 rounded-xl border border-pink-200 cursor-pointer hover:bg-pink-50 transition">
                <span class="material-symbols-outlined text-[var(--primary-color)]">upload</span>
                <span class="text-sm font-semibold">{{ trans('messages.upload_logo', [], session('locale')) ?: 'Upload Logo' }}</span>
                <input type="file" 
                       accept="image/*"
                       @change="handleLogoUpload($event)"
                       class="hidden">
              </label>
              <span x-show="logoFile" class="text-sm text-gray-600" x-text="logoFile?.name"></span>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ trans('messages.logo_upload_hint', [], session('locale')) ?: 'Recommended: PNG or JPG, max 2MB' }}</p>
          </div>

          <!-- Save Button -->
          <div class="flex justify-end pt-4 border-t">
            <button type="submit" 
                    :disabled="saving"
                    class="px-6 py-3 rounded-xl bg-[var(--primary-color)] text-white font-semibold hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed transition">
              <span x-show="!saving">{{ trans('messages.save', [], session('locale')) ?: 'Save' }}</span>
              <span x-show="saving">{{ trans('messages.saving', [], session('locale')) ?: 'Saving...' }}</span>
            </button>
          </div>
        </form>
      </section>

      <!-- Late Delivery Settings Tab -->
      <section x-show="tab==='delivery'" class="p-6 space-y-6" x-cloak>
        <form @submit.prevent="saveDeliverySettings()" class="space-y-6">
          <!-- Late Delivery Weeks -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              {{ trans('messages.late_delivery_weeks', [], session('locale')) ?: 'Late Delivery Time (Weeks)' }}
            </label>
            <div class="flex items-center gap-4">
              <input type="number" 
                     min="1"
                     x-model.number="settings.late_delivery_weeks"
                     class="w-32 h-12 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-4 text-center"
                     :placeholder="'{{ trans('messages.enter_weeks', [], session('locale')) ?: 'Enter weeks' }}'">
              <span class="text-sm text-gray-600">{{ trans('messages.weeks', [], session('locale')) ?: 'weeks' }}</span>
            </div>
            <p class="text-xs text-gray-500 mt-2">
              {{ trans('messages.late_delivery_hint', [], session('locale')) ?: 'Orders exceeding this time period will be marked as late delivery' }}
            </p>
          </div>

          <!-- Save Button -->
          <div class="flex justify-end pt-4 border-t">
            <button type="submit" 
                    :disabled="saving"
                    class="px-6 py-3 rounded-xl bg-[var(--primary-color)] text-white font-semibold hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed transition">
              <span x-show="!saving">{{ trans('messages.save', [], session('locale')) ?: 'Save' }}</span>
              <span x-show="saving">{{ trans('messages.saving', [], session('locale')) ?: 'Saving...' }}</span>
            </button>
          </div>
        </form>
      </section>
    </div>
  </div>
</main>

<script>
function settingsPage() {
  return {
    tab: 'company',
    saving: false,
    logoFile: null,
    settings: {
      company_name: '',
      company_email: '',
      company_cr_no: '',
      company_logo: null,
      company_address: '',
      late_delivery_weeks: 2
    },

    async init() {
      await this.loadSettings();
    },

    async loadSettings() {
      try {
        const response = await fetch('/settings/get');
        const data = await response.json();
        
        if (data.success) {
          this.settings = {
            company_name: data.settings.company_name || '',
            company_email: data.settings.company_email || '',
            company_cr_no: data.settings.company_cr_no || '',
            company_logo: data.settings.company_logo || null,
            company_address: data.settings.company_address || '',
            late_delivery_weeks: data.settings.late_delivery_weeks || 2
          };
        }
      } catch (error) {
        console.error('Error loading settings:', error);
        this.toast('{{ trans('messages.error_loading_settings', [], session('locale')) ?: 'Error loading settings' }}', 'error');
      }
    },

    handleLogoUpload(event) {
      const file = event.target.files[0];
      if (file) {
        // Validate file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
          this.toast('{{ trans('messages.file_too_large', [], session('locale')) ?: 'File size must be less than 2MB' }}', 'error');
          return;
        }
        
        // Validate file type
        if (!file.type.match('image.*')) {
          this.toast('{{ trans('messages.invalid_file_type', [], session('locale')) ?: 'Please select an image file' }}', 'error');
          return;
        }
        
        this.logoFile = file;
      }
    },

    async saveCompanyInfo() {
      this.saving = true;
      
      try {
        const formData = new FormData();
        formData.append('company_name', this.settings.company_name || '');
        formData.append('company_email', this.settings.company_email || '');
        formData.append('company_cr_no', this.settings.company_cr_no || '');
        formData.append('company_address', this.settings.company_address || '');
        
        if (this.logoFile) {
          formData.append('company_logo', this.logoFile);
        }

        const response = await fetch('/settings/update', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: formData
        });

        const data = await response.json();

        if (data.success) {
          this.settings.company_logo = data.settings.company_logo;
          this.logoFile = null;
          this.toast('{{ trans('messages.settings_updated_successfully', [], session('locale')) ?: 'Settings updated successfully' }}', 'success');
        } else {
          this.toast(data.message || '{{ trans('messages.error_updating_settings', [], session('locale')) ?: 'Error updating settings' }}', 'error');
        }
      } catch (error) {
        console.error('Error saving company info:', error);
        this.toast('{{ trans('messages.error_updating_settings', [], session('locale')) ?: 'Error updating settings' }}', 'error');
      } finally {
        this.saving = false;
      }
    },

    async saveDeliverySettings() {
      this.saving = true;
      
      try {
        const formData = new FormData();
        formData.append('late_delivery_weeks', this.settings.late_delivery_weeks || 2);
        formData.append('company_name', this.settings.company_name || '');
        formData.append('company_email', this.settings.company_email || '');
        formData.append('company_cr_no', this.settings.company_cr_no || '');
        formData.append('company_address', this.settings.company_address || '');

        const response = await fetch('/settings/update', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: formData
        });

        const data = await response.json();

        if (data.success) {
          this.toast('{{ trans('messages.settings_updated_successfully', [], session('locale')) ?: 'Settings updated successfully' }}', 'success');
        } else {
          this.toast(data.message || '{{ trans('messages.error_updating_settings', [], session('locale')) ?: 'Error updating settings' }}', 'error');
        }
      } catch (error) {
        console.error('Error saving delivery settings:', error);
        this.toast('{{ trans('messages.error_updating_settings', [], session('locale')) ?: 'Error updating settings' }}', 'error');
      } finally {
        this.saving = false;
      }
    },

    toast(message, type = 'success') {
      // Simple toast notification
      const toast = document.createElement('div');
      toast.className = `fixed bottom-4 left-1/2 -translate-x-1/2 z-[9999] px-6 py-3 rounded-full shadow-lg font-semibold ${
        type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
      }`;
      toast.textContent = message;
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.remove();
      }, 3000);
    }
  }
}
</script>

@include('layouts.footer')
@endsection

