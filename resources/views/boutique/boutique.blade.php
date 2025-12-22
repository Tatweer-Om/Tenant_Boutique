@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.boutique_lang', [], session('locale')) }}</title>
@endpush

<main class="flex-1 p-4 md:p-6">
  <div class="w-full max-w-screen-xl mx-auto">
    
    <!-- العنوان -->
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
        {{ trans('messages.add_new_boutique', [], session('locale')) }}
    </h2>
    <div class="flex items-center gap-3">
        <a href="{{url('boutique_list')}}"
           class="inline-flex items-center justify-center h-11 px-5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold transition">
            <span class="material-symbols-outlined text-base me-1">arrow_back</span>
            {{ trans('messages.back_to_list', [], session('locale')) }}
        </a>
        <a href="{{url('boutique_list')}}"
           class="inline-flex items-center justify-center h-11 px-5 rounded-lg bg-[var(--primary-color)] hover:opacity-90 text-white font-semibold transition">
            <span class="material-symbols-outlined text-base me-1">list</span>
            {{ trans('messages.boutique_list', [], session('locale')) }}
        </a>
    </div>
</div>

    <!-- النموذج -->
   <form  
      class="bg-white border border-pink-100 shadow-md rounded-2xl p-6 sm:p-8 space-y-8" id="add_boutique">
    @csrf

    <!-- 🩷 Section 1: Basic Boutique Info -->
    <div>
        <h3 class="text-lg font-bold text-[var(--primary-color)] mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-[var(--primary-color)]">storefront</span>
            {{ trans('messages.boutique_basic_info', [], session('locale')) }}
        </h3>
    <input type="hidden" name="boutique_id" id="boutique_id" value="">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Boutique Name -->
            <div>
                <label class="font-semibold text-gray-700 flex items-center gap-1">
                    <span class="material-symbols-outlined text-gray-400 text-sm">store</span>
                    {{ trans('messages.boutique_name', [], session('locale')) }}
                </label>
                <input type="text" name="boutique_name" id="boutique_name"
                       placeholder="{{ trans('messages.boutique_name_placeholder', [], session('locale')) }}"
                       class="form-input w-full h-11 mt-1 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3">
            </div>

            <!-- Shelf Number -->
            <div>
                <label class="font-semibold text-gray-700 flex items-center gap-1">
                    <span class="material-symbols-outlined text-gray-400 text-sm">numbers</span>
                    {{ trans('messages.shelf_no', [], session('locale')) }}
                </label>
                <input type="text" name="shelf_no" id="shelf_no"
                       placeholder="{{ trans('messages.shelf_no_placeholder', [], session('locale')) }}"
                       class="form-input w-full h-11 mt-1 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3">
            </div>
        </div>
    </div>

    <!-- 💰 Section 2: Rent Info -->
    <div>
        <h3 class="text-lg font-bold text-[var(--primary-color)] mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-[var(--primary-color)]">payments</span>
            {{ trans('messages.rent_info', [], session('locale')) }}
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <!-- Monthly Rent -->
            <div>
                <label class="font-semibold text-gray-700 flex items-center gap-1">
                    <span class="material-symbols-outlined text-gray-400 text-sm">attach_money</span>
                    {{ trans('messages.monthly_rent', [], session('locale')) }}
                </label>
                <input type="number" step="0.001" name="monthly_rent" id="monthly_rent"
                       placeholder="{{ trans('messages.monthly_rent_placeholder', [], session('locale')) }}"
                       class="form-input w-full h-11 mt-1 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3">
            </div>

            <!-- Rent Payment Date -->
            <div>
                <label class="font-semibold text-gray-700 flex items-center gap-1">
                    <span class="material-symbols-outlined text-gray-400 text-sm">calendar_month</span>
                    {{ trans('messages.rent_date', [], session('locale')) }}
                </label>
                <input type="date" name="rent_date" id="rent_date"
                       class="form-input w-full h-11 mt-1 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3">
            </div>

            <!-- Rent Status (Optional) -->
            <div>
                <label class="font-semibold text-gray-700 flex items-center gap-1">
                    <span class="material-symbols-outlined text-gray-400 text-sm">check_circle</span>
                    {{ trans('messages.status', [], session('locale')) }}
                </label>
                <select name="status"
                        class="form-input w-full h-11 mt-1 rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3">
                    <option value="1">{{ trans('messages.active', [], session('locale')) }}</option>
                    <option value="2">{{ trans('messages.inactive', [], session('locale')) }}</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 📍 Section 3: Boutique Location -->
    <div>
        <h3 class="text-lg font-bold text-[var(--primary-color)] mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-[var(--primary-color)]">location_on</span>
            {{ trans('messages.boutique_location', [], session('locale')) }}
        </h3>
        <textarea name="boutique_address" rows="3" id="boutique_address"
                  placeholder="{{ trans('messages.boutique_address_placeholder', [], session('locale')) }}"
                  class="form-input w-full rounded-xl border border-pink-200 focus:ring-2 focus:ring-[var(--primary-color)] px-3"></textarea>
    </div>

    <!-- Buttons -->
    <div class="flex justify-end gap-3 pt-4 border-t border-dashed">
        <a href=""
           class="bg-gray-200 hover:bg-gray-300 px-5 py-2 rounded-lg font-semibold text-gray-700 transition">
            {{ trans('messages.cancel', [], session('locale')) }}
        </a>
        <button type="submit"
                class="bg-[var(--primary-color)] text-white px-6 py-2 rounded-lg font-bold hover:bg-opacity-90 transition">
            💾 {{ trans('messages.save_boutique', [], session('locale')) }}
        </button>
    </div>
</form>

  </div>
</main>

@include('layouts.footer')
@endsection