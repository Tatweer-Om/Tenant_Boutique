@extends('layouts.header')

@section('main')
@push('title')
<title>{{ trans('messages.add_stock_lang', [], session('locale')) }}</title>
@endpush


<style>
    body {
        font-family: 'IBM Plex Sans Arabic', sans-serif;
    }
</style>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#6D28D9", // Deep Purple
                    "secondary": "#FCE7F3", // Light Pink
                    "accent": "#F97316", // Vibrant Orange
                    "background-light": "#F9FAFB", // Off-white
                    "background-dark": "#111827", // Dark Gray for dark mode
                    "card-light": "#FFFFFF",
                    "card-dark": "#1F2937",
                    "text-primary-light": "#374151",
                    "text-primary-dark": "#E5E7EB",
                    "text-secondary-light": "#6B7280",
                    "text-secondary-dark": "#9CA3AF",
                    "border-light": "#E5E7EB",
                    "border-dark": "#374151"
                },
                fontFamily: {
                    "display": ["IBM Plex Sans Arabic", "sans-serif"]
                },
                borderRadius: {
                    "DEFAULT": "0.75rem", // 12px
                    "lg": "1rem", // 16px
                    "xl": "1.5rem", // 24px
                    "full": "9999px"
                },
            },
        },
    }
</script>
<main class="flex-1 flex flex-col" x-data="{ activeTab: 'raw' }">
    <div class="sticky top-0 z-10 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm border-b border-border-light dark:border-border-dark p-4 sm:p-6">
        <div class="flex items-center justify-between">
            <p class="text-text-primary-light dark:text-text-primary-dark text-3xl font-bold leading-tight tracking-tight">
                {{ trans('messages.manage_inventory', [], session('locale')) }}
            </p>
        </div>
    </div>

    <!-- Tabs -->


    <!-- محتوى الصفحة -->
    <div class="p-4 sm:p-6 space-y-6 overflow-y-auto" style="max-height: calc(100vh - 120px);">
        <!-- ===================== قسم المواد الخام ===================== -->
        <div x-transition>
            <div class="space-y-6">

                <!-- بطاقة البيانات الأساسية -->
                <div class="bg-card-light dark:bg-card-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm">
                    <h2 class="text-text-primary-light dark:text-text-primary-dark text-xl font-bold mb-5">
                        {{ trans('messages.basic_info', [], session('locale')) }}
                    </h2>

                    <form id="update_material" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

                            <label class="flex flex-col col-span-2">
                                <p class="text-sm font-medium mb-2">
                                    {{ trans('messages.material_name', [], session('locale')) }}
                                </p>
                                <input type="text" placeholder="{{ trans('messages.material_name_placeholder', [], session('locale')) }}" value="{{$material->material_name ?? ''}}"
                                    class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="material_name" id="material_name" />
                            </label>

                            <input type="hidden" name="material_id" id="material_id" value="{{ $material->id }}" />
                            <label class="flex flex-col col-span-2">
                                <p class="text-sm font-medium mb-2">
                                    {{ trans('messages.description', [], session('locale')) }}
                                </p>
                                <textarea placeholder="{{ trans('messages.description_placeholder', [], session('locale')) }}"
                                    rows="3"
                                    class="form-textarea rounded-lg border px-4 py-3 focus:ring-2 focus:ring-primary/50"
                                    name="material_notes"
                                    id="material_notes">{{ $material->description ?? '' }}</textarea>
                            </label>

                          <label class="flex flex-col">
    <p class="text-sm font-medium mb-2">
        {{ trans('messages.unit', [], session('locale')) }}
    </p>

       <select class="form-select h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="material_unit" id="material_unit">
        <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
        <option value="meter" {{ ($material->unit ?? '') === 'meter' ? 'selected' : '' }}>{{ trans('messages.meter', [], session('locale')) }}</option>
        <option value="piece" {{ ($material->unit ?? '') === 'piece' ? 'selected' : '' }}>{{ trans('messages.piece', [], session('locale')) }}</option>
        <option value="roll" {{ ($material->unit ?? '') === 'roll' ? 'selected' : '' }}>{{ trans('messages.roll', [], session('locale')) }}</option>
    </select>
</label>



                            <label class="flex flex-col">
                                <p class="text-sm font-medium mb-2">
                                    {{ trans('messages.category', [], session('locale')) }}
                                </p>

                               <select class="form-select h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="material_category" id="material_category">
        <option value="">{{ trans('messages.choose', [], session('locale')) }}</option>
        <option value="fabric" {{ ($material->category ?? '') === 'fabric' ? 'selected' : '' }}>{{ trans('messages.fabric', [], session('locale')) }}</option>
        <option value="embroidery" {{ ($material->category ?? '') === 'embroidery' ? 'selected' : '' }}>{{ trans('messages.embroidery', [], session('locale')) }}</option>
        <option value="accessories" {{ ($material->category ?? '') === 'accessories' ? 'selected' : '' }}>{{ trans('messages.accessories', [], session('locale')) }}</option>
    </select>
                            </label>

                        </div>

                        <!-- بطاقة الكمية والأسعار -->
                        <div class="bg-card-light dark:bg-card-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm mt-6">
                            <h2 class="text-text-primary-light dark:text-text-primary-dark text-xl font-bold mb-5">
                                {{ trans('messages.qty_price', [], session('locale')) }}
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

                                <label class="flex flex-col">
                                    <p class="text-sm font-medium mb-2">{{ trans('messages.buy_price', [], session('locale')) }}</p>
                                    <input type="number" placeholder="0.00"
                                        class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" value="{{$material->buy_price ?? ''}}" name="purchase_price" id="purchase_price" />
                                </label>

                                <label class="flex flex-col">
                                    <p class="text-sm font-medium mb-2">{{ trans('messages.suggested_sell_price', [], session('locale')) }}</p>
                                    <input type="number" placeholder="0.00" value="{{$material->sell_price ?? ''}}"
                                        class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="sale_price" id="sale_price" />
                                </label>

                                <label class="flex flex-col">
                                    <p class="text-sm font-medium mb-2">{{ trans('messages.rolls_count', [], session('locale')) }}</p>
                                    <input type="number" placeholder="0"
                                        class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" value="{{$material->rolls_count ?? ''}}" name="roll_count" id="roll_count" />
                                </label>

                                <label class="flex flex-col">
                                    <p class="text-sm font-medium mb-2">{{ trans('messages.meters_per_roll', [], session('locale')) }}</p>
                                    <input type="number" placeholder="0"
                                        class="form-input h-12 rounded-lg px-4 border focus:ring-2 focus:ring-primary/50" name="meter_per_roll" value="{{$material->meters_per_roll ?? ''}}" id="meter_per_roll" />
                                </label>
                            </div>
                        </div>

                        <!-- بطاقة الصورة -->
                        <div class="bg-card-light dark:bg-card-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm mt-6">
    <h2 class="text-text-primary-light dark:text-text-primary-dark text-xl font-bold mb-5">
        {{ trans('messages.raw_material_image', [], session('locale')) }}
    </h2>

    <label
        class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition relative"
        id="imageBoxLabel">

        <!-- Image preview -->
        <img id="imagePreview"
            src="{{ $material->material_image ? asset('images/materials/' . $material->material_image) : '' }}"
            alt="Preview"
            class="{{ $material->material_image ? 'absolute inset-0 w-full h-full object-contain rounded-lg' : 'hidden' }}" />

        <span class="material-symbols-outlined text-4xl text-text-secondary-light dark:text-text-secondary-dark" id="uploadIcon">
            cloud_upload
        </span>

        <p class="text-sm text-gray-500 mt-2" id="uploadText">
            {{ trans('messages.upload_image', [], session('locale')) }}
        </p>

        <input type="file" class="hidden" name="material_image" id="material_image" />
    </label>
</div>

                </div>

                <!-- زر الحفظ -->
                <div class="mt-10 text-center">
                    <button
                        type="submit"
                        class="w-full sm:w-auto px-10 py-4 bg-primary text-white text-lg font-bold rounded-2xl shadow-lg hover:bg-primary/90 hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-3 mx-auto">
                        <span class="material-symbols-outlined text-2xl">save</span>
                        <span>{{ trans('messages.save_all', [], session('locale')) }}</span>
                    </button>
                </div>

            </div>
            </form>

        </div>


    </div>

</main>


</div>
</form>
</div>
</div>

@include('layouts.footer')
@endsection