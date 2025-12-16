<!DOCTYPE html>
<html class="light" dir="rtl" lang="ar"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>{{ trans('messages.pos_system', [], session('locale')) }}</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.min.css" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "primary": "var(--color-primary)",
              "primary-dark": "var(--color-primary-dark)",
              "accent-gold": "var(--color-accent-gold)",
              "background-light": "#fbfbf8", // Lighter, more neutral background
              "background-dark": "#1a1a1a",
            },
            fontFamily: {
              "display": ["IBM Plex Sans Arabic", "sans-serif"],
              "body": ["IBM Plex Sans Arabic", "sans-serif"]
            },
            borderRadius: {"DEFAULT": "1rem", "lg": "1.5rem", "xl": "2rem", "full": "9999px"},
            boxShadow: {
                "soft": "0 4px 20px -2px rgba(0, 0, 0, 0.05)",
                "card": "0 0 0 1px rgba(0,0,0,0.02), 0 4px 12px rgba(0,0,0,0.06)",
                "premium": "0 8px 30px rgba(0,0,0,0.1)",
                "glow-primary": "0 0 15px rgba(var(--color-primary-rgb), 0.3)",
                "glow-accent": "0 0 12px rgba(var(--color-accent-gold-rgb), 0.4)",
            }
          },
        },
      }
    </script>
<style type="text/tailwindcss">
        :root {
            --color-primary: #1F6F67;--color-primary-dark: #1A5C55;
            --color-primary-rgb: 31, 111, 103;
            --color-accent-gold: #B8860B;--color-accent-gold-rgb: 184, 134, 11;
        }
.pay-btn {
  @apply flex flex-col items-center justify-center gap-1 h-16 rounded-xl border
         text-sm font-bold bg-white text-gray-700
         hover:bg-primary hover:text-white transition;
}
.pay-btn.active {
  @apply bg-primary text-white border-primary shadow-md;
}

/* Order type buttons */
.order-type-btn {
  @apply px-6 py-3 rounded-xl border font-bold text-sm
         bg-white text-gray-700
         hover:bg-primary hover:text-white transition;
}

.order-type-btn.active {
  @apply bg-primary text-white border-primary shadow-md;
}
    </style>
<style>::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(var(--color-primary-rgb), 0.2);
            border-radius: 99px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(var(--color-primary-rgb), 0.4);
        }
        body {
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            letter-spacing: -0.02em;}

            .order-type-btn {
  @apply px-6 py-3 rounded-xl border font-bold text-sm
         hover:bg-primary hover:text-white transition;
}
.order-type-btn.active {
  @apply bg-primary text-white;
}
/* Flying cart item animation */
.fly-item {
  position: fixed;
  z-index: 9999;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background-size: cover;
  background-position: center;
  transition: transform 0.9s cubic-bezier(.4,1.4,.6,1), opacity 0.9s;
}
/* Notification shake */
@keyframes shake {
  0% { transform: rotate(0); }
  25% { transform: rotate(-10deg); }
  50% { transform: rotate(10deg); }
  75% { transform: rotate(-6deg); }
  100% { transform: rotate(0); }
}

.shake {
  animation: shake 0.6s ease;
}
.category-tab {
  @apply h-12 px-7 rounded-full bg-gray-100 text-gray-700 font-bold
         transition-all duration-200 outline-none;
}

/* Hover */
.category-tab:hover {
  @apply bg-primary/10 text-primary;
}

.category-tab:active {
  transform: scale(0.96);
}
/* Keyboard / focus ring */
.category-tab:focus-visible {
  box-shadow: 0 0 0 3px rgba(31, 111, 103, 0.35);
}

/* Active (selected tab) */
.category-tab.active {
  background-color: var(--color-primary);
  color: white;
  box-shadow: 0 6px 16px rgba(31, 111, 103, 0.35);
  transform: translateY(-1px);
}

/* Keyboard / programmatic focus */
.category-tab:focus-visible {
  @apply ring-2 ring-primary ring-offset-2;
}

body.modal-open {
  overflow: hidden;
  position: fixed;
  width: 100%;
}

    </style>
</head>
<body class="bg-background-light dark:bg-background-dark h-screen overflow-x-hidden text-[#181811] flex flex-col">
<header class="bg-white shadow-premium z-20">

  <!-- ===================== -->
  <!-- 🖥️ Desktop Header -->
  <!-- ===================== -->
  <div class="hidden lg:flex h-20 px-8 items-center justify-between">

    <!-- Left -->
    <div class="flex items-center gap-6">
      <button
        class="size-12 flex items-center justify-center rounded-full hover:bg-gray-50 transition-colors">
        <span class="material-symbols-outlined text-3xl text-gray-700">menu</span>
      </button>

      <div class="h-10 w-px bg-gray-200"></div>

      <div class="flex items-center gap-4">
        <div class="bg-primary/10 rounded-full size-11 flex items-center justify-center text-primary-dark">
          <span class="material-symbols-outlined text-2xl">person</span>
        </div>
        <h2 class="text-base font-bold text-gray-800">{{ trans('messages.user_name', [], session('locale')) }}</h2>
      </div>
    </div>

    <!-- Center -->
    <h1 class="text-xl font-extrabold text-primary-dark">
      {{ trans('messages.direct_sale', [], session('locale')) }}
    </h1>

    <!-- Right -->
    <div class="flex items-center gap-4">

      <!-- Notifications -->
      <button
        id="notificationBtn"
        onclick="openSuspendedModal()"
        class="relative size-12 rounded-full bg-gray-50 hover:bg-gray-100 flex items-center justify-center">
        <span class="material-symbols-outlined text-gray-600 text-2xl">notifications</span>

        <span
          id="suspendedBadge"
          class="hidden absolute -top-1 -right-1 size-5 rounded-full
                 bg-red-500 text-white text-[11px] font-bold
                 flex items-center justify-center">
          0
        </span>
      </button>

      <!-- Arabic -->
      <button
        id="lang-ar"
        class="flex items-center gap-2 h-12 px-5 rounded-full
               bg-accent-gold text-white font-bold shadow-md hover:opacity-90">
        {{ trans('messages.arabic', [], session('locale')) }}
      </button>

      <!-- English -->
      <button
        id="lang-en"
        class="flex items-center gap-2 h-12 px-5 rounded-full
               bg-gray-800 text-white font-bold shadow-md hover:bg-gray-700">
        {{ trans('messages.english', [], session('locale')) }}
      </button>

    </div>
  </div>

  <!-- ===================== -->
  <!-- 📱 Mobile Header -->
  <!-- ===================== -->
  <div class="lg:hidden h-16 px-4 flex items-center justify-between">

    <!-- Menu -->
    <button
      class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100">
      <span class="material-symbols-outlined text-2xl">menu</span>
    </button>

    <!-- Title -->
    <h1 class="text-base font-extrabold text-primary-dark truncate">
      {{ trans('messages.direct_sale', [], session('locale')) }}
    </h1>

    <!-- Actions -->
    <div class="flex items-center gap-2">

      <!-- Notifications -->
      <button
        onclick="openSuspendedModal()"
        class="relative size-10 rounded-full bg-gray-50 flex items-center justify-center">
        <span class="material-symbols-outlined text-xl">notifications</span>

        <span
          id="suspendedBadgeMobile"
          class="hidden absolute -top-1 -right-1 size-5 rounded-full
                 bg-red-500 text-white text-[11px] font-bold
                 flex items-center justify-center">
          0
        </span>
      </button>

      <!-- Language -->
<button
  id="langMobile"
  onclick="toggleLanguage()"
  class="h-9 px-3 rounded-full border border-gray-300
         text-xs font-extrabold text-primary bg-white">
  AR / EN
</button>


    </div>
  </div>

</header>

<main class="flex-1 flex flex-col lg:flex-row overflow-hidden p-3 lg:p-6 gap-4 lg:gap-6">
<section class="flex flex-col flex-1 bg-white rounded-xl shadow-premium overflow-hidden min-w-0">
    <div class="p-5 border-b border-gray-100">
<label class="flex items-center w-full h-16 bg-gray-50 rounded-full px-5 focus-within:ring-2 focus-within:ring-primary/50 transition-all">
<span class="material-symbols-outlined text-gray-400 text-3xl ml-4">search</span>
<input id="barcodeInput" class="bg-transparent border-none w-full h-full focus:ring-0 text-lg placeholder:text-gray-400" placeholder="{{ trans('messages.scan_barcode_or_search', [], session('locale')) }}" type="text"/>
<button class="p-3 bg-white rounded-full shadow-sm text-gray-600 hover:text-primary transition-colors">
<span class="material-symbols-outlined text-xl">barcode_scanner</span>
</button>
</label>
</div>
<div class="px-5 py-4 flex gap-3 overflow-x-auto no-scrollbar border-b border-gray-50">
<button data-filter="all" class="category-tab shrink-0 h-12 px-7 rounded-full bg-primary text-white text-base font-bold shadow-md shadow-primary/20 whitespace-nowrap">{{ trans('messages.all', [], session('locale')) }}</button>
@foreach($categories as $category)
<button data-filter="category_{{ $category->id }}" class="category-tab shrink-0 h-12 px-7 rounded-full bg-gray-100 text-gray-700 text-base font-medium transition-colors whitespace-nowrap">
  {{ session('locale') == 'ar' && $category->category_name_ar ? $category->category_name_ar : $category->category_name }}
</button>
@endforeach
</div>
<div class="flex-1 overflow-y-auto p-5 bg-gray-50/50">
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4" id="productsGrid">
@foreach($stocks as $stock)
  @php
    $firstImage = $stock->images->first();
    $imageUrl = $firstImage ? asset($firstImage->image_path) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2U1ZTdlYiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5Y2EzYWYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBJbWFnZTwvdGV4dD48L3N2Zz4=';
    $categoryFilter = $stock->category_id ? 'category_' . $stock->category_id : 'all';
    $displayName = session('locale') == 'ar' && $stock->design_name ? $stock->design_name : ($stock->design_name ?: $stock->abaya_code);
  @endphp
  <button
    class="group flex flex-col bg-white rounded-2xl p-4 shadow-sm hover:shadow-lg transition-all active:scale-98 border border-gray-100 hover:border-primary/30 product-item"
    data-id="{{ $stock->id }}"
    data-name="{{ $displayName }}"
    data-price="{{ $stock->sales_price ?? 0 }}"
    data-category="{{ $categoryFilter }}"
    data-image="{{ $imageUrl }}"
    data-barcode="{{ $stock->barcode ?? '' }}"
    data-abaya-code="{{ $stock->abaya_code ?? '' }}"
    data-design-name="{{ $stock->design_name ?? '' }}">
    <div class="w-full aspect-[3/4] rounded-xl bg-gray-100 mb-4 overflow-hidden relative">
      <div class="w-full h-full bg-cover bg-center group-hover:scale-105 transition-transform duration-500" style="background-image: url('{{ $imageUrl }}');"></div>
    </div>
    <div class="text-right w-full">
      <h3 class="font-bold text-gray-900 text-base mb-1 leading-tight">{{ $displayName }}</h3>
      <p class="text-xs text-gray-500 mb-1">{{ trans('messages.code', [], session('locale')) }}: {{ $stock->abaya_code ?? 'N/A' }}</p>
      @if($stock->barcode)
      <p class="text-xs text-gray-500 mb-1">{{ trans('messages.barcode', [], session('locale')) }}: {{ $stock->barcode }}</p>
      @endif
      <p class="text-primary font-black text-xl drop-shadow-sm mt-1">{{ number_format($stock->sales_price ?? 0, 2) }} {{ trans('messages.omr', [], session('locale')) }}</p>
    </div>
  </button>
@endforeach
@if($stocks->isEmpty())
  <div class="col-span-full text-center py-12 text-gray-500">
    <p class="text-lg">{{ trans('messages.no_products', [], session('locale')) }}</p>
  </div>
@endif
</div>
</div>
</section>
<section
 <section
  id="cartDesktop"
  class="hidden lg:flex flex-col
         w-full lg:w-[450px]
         h-[100dvh] lg:h-[85vh]
         bg-white rounded-xl shadow-premium
         overflow-hidden relative">


    <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-white z-10">

  <!-- Mobile Back Button -->
  <button
    onclick="closeCartMobile()"
    class="lg:hidden flex items-center gap-2 text-primary font-bold">
    <span class="material-symbols-outlined">arrow_forward</span>
    {{ trans('messages.products', [], session('locale')) }}
  </button>

  <h2 class="font-bold text-xl text-gray-800 hidden lg:block">
    {{ trans('messages.current_cart', [], session('locale')) }}
    <span id="cartCount" class="text-gray-400 text-base font-normal mr-1">
      (0 {{ trans('messages.items', [], session('locale')) }})
    </span>
  </h2>

  <!-- Delete -->
  <button
    onclick="clearCart()"
    class="text-red-500 hover:bg-red-50 p-2.5 rounded-full transition-colors"
    title="{{ trans('messages.clear_cart', [], session('locale')) }}">
    <span class="material-symbols-outlined text-2xl">delete</span>
  </button>
</div>

<!-- Cart Content Wrapper -->
<div class="flex-1 relative overflow-hidden">

  <!-- Cart Items -->
  <div id="cartItems"
     class="h-full overflow-y-auto overscroll-contain
            touch-pan-y p-4 space-y-4 pb-32">
  </div>

  <!-- Empty Cart State -->
  <div id="emptyCart"
       class="absolute inset-0 flex flex-col items-center justify-center
              text-gray-300 select-none hidden">
    <span class="material-symbols-outlined text-7xl mb-4">
      shopping_cart
    </span>
    <p class="text-lg font-bold">{{ trans('messages.cart_empty', [], session('locale')) }}</p>
    <p class="text-sm text-gray-400 mt-1">{{ trans('messages.start_adding_products', [], session('locale')) }}</p>
  </div>

</div>

</div>
<div class="p-5 bg-white border-t border-gray-100
            sticky bottom-0 z-20
            shadow-[0_-8px_30px_rgba(0,0,0,0.05)]">
<div class="space-y-3 mb-5">

<div class="flex justify-between items-end mt-3 pt-3 border-t border-gray-100">
<span class="font-bold text-xl text-gray-800">{{ trans('messages.total', [], session('locale')) }}</span>
<span id="cartTotal" class="font-black text-3xl text-primary-dark">
  0.00 <span class="text-base font-medium text-gray-500">{{ trans('messages.omr', [], session('locale')) }}</span>
</span>
</div>
</div>
<!-- Discount Box -->
<div id="discountBox"
     class="hidden mb-4 p-4 rounded-xl bg-gray-50 border border-gray-200 space-y-3">

  <div class="flex items-center gap-3">
    <!-- Discount Type -->
    <select id="discountType"
            class="rounded-xl border p-3 text-sm font-bold">
      <option value="percent">{{ trans('messages.percentage', [], session('locale')) }} %</option>
      <option value="amount">{{ trans('messages.fixed_amount', [], session('locale')) }}</option>
    </select>

    <!-- Discount Value -->
    <input id="discountValue"
           type="number"
           min="0"
           placeholder="{{ trans('messages.discount_value', [], session('locale')) }}"
           class="flex-1 rounded-xl border p-3 text-sm"/>
  </div>

  <div class="flex justify-between text-sm">
    <span>{{ trans('messages.discount_value', [], session('locale')) }}</span>
    <strong id="discountAmount">0.00 {{ trans('messages.omr', [], session('locale')) }}</strong>
  </div>
</div>

<div class="grid grid-cols-3 gap-3">
<button
  id="discountBtn"
  onclick="toggleDiscount()"
  class="col-span-1 h-14 flex flex-col items-center justify-center rounded-xl
         bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
  <span class="material-symbols-outlined text-xl mb-1">percent</span>
  <span class="text-xs font-bold">{{ trans('messages.discount', [], session('locale')) }}</span>
</button>
<button id="suspendBtn"
  class="col-span-1 h-14 flex flex-col items-center justify-center rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
  <span class="material-symbols-outlined text-xl mb-1">pause_circle</span>
  <span class="text-xs font-bold">{{ trans('messages.suspend', [], session('locale')) }}</span>
</button>
<button onclick="openPaymentModal()"
  class="col-span-1 h-14 flex flex-col items-center justify-center rounded-xl bg-primary text-white hover:bg-primary-dark transition-colors shadow-md">
  <span class="material-symbols-outlined text-xl mb-1">payments</span>
  <span class="text-xs font-bold">{{ trans('messages.pay', [], session('locale')) }}</span>
</button>
</div>
</div>
</section>

</main>

<!-- PRODUCT MODAL -->
<!-- PREMIUM PRODUCT MODAL -->
<div id="productModal"
     class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">

<div class="bg-white w-full max-w-3xl rounded-2xl shadow-premium
            max-h-[90vh] flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <h3 id="modalName" class="text-xl font-extrabold text-gray-800"></h3>
      <button onclick="closeModal()" class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100">
        <span class="material-symbols-outlined text-xl">close</span>
      </button>
    </div>

    <!-- Content -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6
            overflow-y-auto flex-1">
      <!-- Image -->
      <div class="rounded-xl overflow-hidden bg-gray-100">
        <div id="modalImage"
             class="w-full h-[380px] bg-cover bg-center transition-transform duration-500 hover:scale-105">
        </div>
      </div>

      <!-- Options -->
      <div class="flex flex-col">

        <!-- Price -->
        <div class="mb-5">
          <p class="text-sm text-gray-500 mb-1">{{ trans('messages.price', [], session('locale')) }}</p>
          <p id="modalPrice" class="text-3xl font-black text-primary-dark"></p>
        </div>

        <!-- Colors and Sizes with Quantities -->
        <div class="mb-6">
          <p class="font-bold mb-2 text-gray-800 text-sm">{{ trans('messages.available_colors_sizes', [], session('locale')) }}</p>
          <div id="colorSizeContainer" class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
            <!-- Colors and sizes will be loaded here dynamically -->
            <div class="text-center text-gray-400 py-4">
              <span class="material-symbols-outlined text-3xl mb-2 block">inventory_2</span>
              <p class="text-xs">{{ trans('messages.loading', [], session('locale')) }}...</p>
            </div>
          </div>
        </div>

        <!-- Quantity -->
        <div class="flex items-center justify-between mb-6 bg-gray-50 rounded-xl p-3">
          <span class="font-bold">{{ trans('messages.quantity', [], session('locale')) }}</span>
          <div class="flex items-center gap-3">
            <button onclick="changeQty(-1)"
                    class="size-9 rounded-full bg-white shadow flex items-center justify-center">−</button>
            <span id="modalQty" class="w-8 text-center font-bold">1</span>
            <button onclick="changeQty(1)"
                    class="size-9 rounded-full bg-primary text-white shadow">+</button>
          </div>
        </div>

        <!-- Action -->
       <button 
       type="button"
  ontouchstart="confirmAddToCart()"
  onclick="confirmAddToCart()"
  class="mt-auto h-14 rounded-xl bg-primary hover:bg-primary-dark
         text-white font-extrabold text-lg shadow-glow-primary transition-all">
  {{ trans('messages.add_to_cart', [], session('locale')) }}
</button>


      </div>
    </div>
  </div>
</div>


<script src="js/pos.js"></script>



<!-- PAYMENT MODAL -->
<div id="paymentModal"
     class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm">

<div class="bg-white w-full max-w-5xl rounded-2xl shadow-premium border border-gray-100
            max-h-[95vh] flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="text-xl font-extrabold text-gray-800">{{ trans('messages.complete_payment', [], session('locale')) }}</h3>
      <button onclick="closePaymentModal()" class="size-10 flex items-center justify-center rounded-full hover:bg-gray-100">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Content -->
<div class="p-6 space-y-6 overflow-y-auto flex-1">
      <!-- Subtotal -->
      <div class="flex justify-between items-center bg-gray-50 rounded-xl p-3">
        <span class="font-semibold text-sm text-gray-700">{{ trans('messages.subtotal', [], session('locale')) }}</span>
        <span id="paymentSubtotal" class="font-bold text-lg text-gray-800">0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
      </div>

      <!-- Discount -->
      <div id="paymentDiscountRow" class="hidden flex justify-between items-center bg-yellow-50 rounded-xl p-3 border border-yellow-200">
        <span class="font-semibold text-sm text-gray-700">{{ trans('messages.discount', [], session('locale')) }}</span>
        <span id="paymentDiscount" class="font-bold text-lg text-red-600">-0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
      </div>

      <!-- Payable Amount -->
      <div class="flex justify-between items-center bg-primary/10 rounded-xl p-4 border-2 border-primary/30">
        <span class="font-bold text-lg text-gray-800">{{ trans('messages.payable_amount', [], session('locale')) }}</span>
        <span id="paymentTotal" class="font-black text-2xl text-primary-dark">0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
      </div>

      <!-- Payment Method -->
      <div>
<p class="font-bold mb-3 text-gray-800 text-sm tracking-wide">{{ trans('messages.payment_method', [], session('locale')) }}</p>
       <div class="grid grid-cols-4 gap-3">
  <button class="pay-btn" data-method="cash">
    <span class="material-symbols-outlined">payments</span>
    {{ trans('messages.cash', [], session('locale')) }}
  </button>

  <button class="pay-btn" data-method="visa">
    <span class="material-symbols-outlined">credit_card</span>
    {{ trans('messages.visa', [], session('locale')) }}
  </button>

  <button class="pay-btn" data-method="transfer">
    <span class="material-symbols-outlined">account_balance</span>
    {{ trans('messages.transfer', [], session('locale')) }}
  </button>

  <button class="pay-btn" data-method="partial">
    <span class="material-symbols-outlined">call_split</span>
    {{ trans('messages.partial_payment', [], session('locale')) }}
  </button>
</div>

<div id="changeBox" class="hidden mt-3 flex justify-between bg-gray-50 p-3 rounded-xl">
  <span>{{ trans('messages.change_amount', [], session('locale')) }}</span>
  <strong id="changeAmount">0.00 {{ trans('messages.omr', [], session('locale')) }}</strong>
</div>

      </div>

<div id="partialPaymentBox" class="hidden space-y-3 mt-3">

  <div class="grid grid-cols-3 gap-3">
    <input id="partialCash" type="number" placeholder="{{ trans('messages.cash', [], session('locale')) }}"
           class="rounded-xl border p-3"/>
    <input id="partialVisa" type="number" placeholder="{{ trans('messages.visa', [], session('locale')) }}"
           class="rounded-xl border p-3"/>
    <input id="partialTransfer" type="number" placeholder="{{ trans('messages.transfer', [], session('locale')) }}"
           class="rounded-xl border p-3"/>
  </div>

  <div class="flex justify-between bg-gray-50 p-3 rounded-xl">
    <span>{{ trans('messages.remaining', [], session('locale')) }}</span>
    <strong id="partialRemaining">0.00 {{ trans('messages.omr', [], session('locale')) }}</strong>
  </div>
</div>


      <!-- Order Type -->
      <div>
        <p class="font-bold mb-3">{{ trans('messages.order_type', [], session('locale')) }}</p>
        <div class="flex gap-3">
<button class="order-type-btn" data-type="direct">{{ trans('messages.direct', [], session('locale')) }}</button>
<button class="order-type-btn" data-type="delivery">{{ trans('messages.delivery', [], session('locale')) }}</button>
        </div>
      </div>

      <!-- Delivery -->
      <div id="deliverySection" class="hidden space-y-3">
        <div class="grid grid-cols-2 gap-3">
        <select id="deliveryArea" class="w-full rounded-xl border p-3">
  <option value="">{{ trans('messages.select_area', [], session('locale')) }}</option>
  <option value="muscat">{{ trans('messages.muscat', [], session('locale')) }}</option>
  <option value="batinah">{{ trans('messages.batinah', [], session('locale')) }}</option>
</select>

         <select id="deliveryWilayah" class="w-full rounded-xl border p-3">
  <option value="">{{ trans('messages.select_wilayah', [], session('locale')) }}</option>
</select>
        </div>

        <!-- Full delivery address -->
<div>
  <label class="block text-sm font-bold mb-2">
    {{ trans('messages.full_delivery_address', [], session('locale')) }}
  </label>

  <textarea
    id="deliveryAddress"
    rows="3"
    placeholder="{{ trans('messages.delivery_address_placeholder', [], session('locale')) }}"
    class="w-full rounded-xl border p-3 resize-none focus:ring-2 focus:ring-primary/40"
  ></textarea>
</div>

        <div class="flex justify-between items-center bg-gray-50 rounded-xl p-3">
          <span>{{ trans('messages.delivery_price', [], session('locale')) }}</span>
          <span id="deliveryPrice">0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
        </div>

        <label class="flex items-center gap-2">
          <input type="checkbox" id="deliveryPaid"/>
          <span class="text-sm">{{ trans('messages.delivery_price_paid', [], session('locale')) }}</span>
        </label>
      </div>

      <!-- Customer -->
      <div>
        <p class="font-bold mb-3">{{ trans('messages.customer_data', [], session('locale')) }}</p>
        <div class="grid grid-cols-2 gap-3">
          <div class="relative">
  <input id="customerPhone"
         type="text"
         placeholder="{{ trans('messages.phone_number', [], session('locale')) }}"
         class="rounded-xl border p-3 w-full"/>

  <div id="customerSuggestions"
       class="hidden absolute top-full mt-2 w-full bg-white border rounded-xl shadow-md z-10">
  </div>
</div>

<input id="customerName"
       type="text"
       placeholder="{{ trans('messages.customer_name', [], session('locale')) }}" class="rounded-xl border p-3"/>

                    </div>
                    <div id="selectedCustomer"
     class="hidden mt-3 p-3 bg-green-50 border border-green-200 rounded-xl">
</div>
      </div>

    </div>

    <!-- Footer -->
    <div class="p-5 border-t flex gap-3">
      <button
  onclick="closePaymentModal()"
  class="flex-1 h-14 rounded-xl bg-gray-100 font-bold hover:bg-gray-200 transition">
  {{ trans('messages.cancel', [], session('locale')) }}
</button>
      <button class="flex-1 h-14 rounded-xl bg-primary text-white font-bold">
        {{ trans('messages.confirm_payment', [], session('locale')) }}
      </button>
    </div>

  </div>
</div>

<div id="suspendedModal"
 class="fixed inset-0 hidden items-center justify-center bg-black/60 z-50 backdrop-blur-sm">

  <div class="bg-white w-full max-w-xl rounded-2xl shadow-premium overflow-hidden">

    <div class="flex justify-between items-center px-6 py-4 border-b">
      <h3 class="font-bold text-lg">{{ trans('messages.suspended_invoices', [], session('locale')) }}</h3>
      <button onclick="closeSuspendedModal()">✕</button>
    </div>

    <div id="suspendedList" class="p-4 space-y-3 max-h-[60vh] overflow-y-auto">
    </div>

  </div>
</div>


<!-- Mobile Cart Button -->
<button
  id="openCartMobile"
  onclick="openCartMobile()"
  class="lg:hidden fixed bottom-4 right-4 z-40
         w-16 h-16 rounded-full bg-primary text-white
         flex items-center justify-center shadow-xl">

  <span class="material-symbols-outlined text-3xl">shopping_cart</span>

  <!-- Badge -->
  <span
    id="cartMobileBadge"
    class="absolute -top-1 -left-1 w-6 h-6 rounded-full bg-red-500 text-white
           text-xs font-bold flex items-center justify-center">
    0
  </span>
</button>

<<!-- Mobile Cart Modal -->
<!-- Mobile Cart Modal -->
<div
  id="cartMobile"
  class="fixed inset-0 z-50 hidden lg:hidden">

  <!-- Overlay (يستقبل الضغط فقط للإغلاق) -->
  <div
    class="absolute inset-0 bg-black/60 backdrop-blur-sm"
    onclick="closeCartMobile()">
  </div>

  <!-- Cart Container -->
  <div
    class="absolute inset-x-0 bottom-0 bg-white rounded-t-2xl
           h-[85vh] flex flex-col shadow-2xl
           pointer-events-auto">

    <!-- Header (ثابت) -->
    <div class="flex items-center justify-between p-4 border-b shrink-0">
      <h3 class="font-bold text-lg">{{ trans('messages.cart', [], session('locale')) }}</h3>
      <button
        onclick="closeCartMobile()"
        class="w-10 h-10 rounded-full hover:bg-gray-100">
        ✕
      </button>
    </div>

    <!-- Cart Items (هنا السكرول الحقيقي) -->
    <div
      id="cartMobileContent"
      class="flex-1 overflow-y-auto p-4 space-y-4
             overscroll-contain"
      style="-webkit-overflow-scrolling: touch;">
    </div>

    <!-- Footer (ثابت) -->
    <div class="p-4 border-t shrink-0 space-y-3 bg-white">

  <!-- Buttons -->
  <div class="grid grid-cols-2 gap-3">
    
    <!-- Suspend -->
    <button
      onclick="suspendCurrentCart(); closeCartMobile();"
      class="h-14 flex flex-col items-center justify-center
             rounded-xl bg-gray-100 text-gray-700
             hover:bg-gray-200 transition">
      <span class="material-symbols-outlined text-xl mb-1">
        pause_circle
      </span>
      <span class="text-xs font-bold">{{ trans('messages.suspend', [], session('locale')) }}</span>
    </button>

    <!-- Pay -->
    <button
      onclick="openPaymentModal(); closeCartMobile();"
      class="h-14 flex flex-col items-center justify-center
             rounded-xl bg-primary text-white font-bold
             hover:bg-primary-dark transition">
      <span class="material-symbols-outlined text-xl mb-1">
        payments
      </span>
      <span class="text-xs font-bold">{{ trans('messages.pay', [], session('locale')) }}</span>
    </button>

  </div>

  <!-- Total -->
  <div class="flex justify-between text-lg font-bold pt-2">
    <span>{{ trans('messages.total', [], session('locale')) }}</span>
    <span id="cartMobileTotal">0.00 {{ trans('messages.omr', [], session('locale')) }}</span>
  </div>

</div>

</div>


<script>
    // Translations
    const translations = {
      selectSizeColor: "{{ trans('messages.select_size_color', [], session('locale')) }}",
      cartEmpty: "{{ trans('messages.cart_empty', [], session('locale')) }}",
      productNotFound: "{{ trans('messages.product_not_found', [], session('locale')) }}",
      noSuspendedInvoices: "{{ trans('messages.no_suspended_invoices', [], session('locale')) }}",
      items: "{{ trans('messages.items', [], session('locale')) }}",
      restore: "{{ trans('messages.restore', [], session('locale')) }}",
      size: "{{ trans('messages.size', [], session('locale')) }}",
      unitPrice: "{{ trans('messages.unit_price', [], session('locale')) }}",
      total: "{{ trans('messages.total', [], session('locale')) }}",
      omr: "{{ trans('messages.omr', [], session('locale')) }}",
      loading: "{{ trans('messages.loading', [], session('locale')) }}",
      errorLoadingData: "{{ trans('messages.errorLoadingData', [], session('locale')) }}",
      noStockAvailable: "{{ trans('messages.noStockAvailable', [], session('locale')) }}",
      available: "{{ trans('messages.available', [], session('locale')) }}",
      quantityError: "{{ trans('messages.quantityError', [], session('locale')) }}",
      quantityAvailable: "{{ trans('messages.quantityAvailable', [], session('locale')) }}",
      ok: "{{ trans('messages.ok', [], session('locale')) }}"
    };

    /* =========================================================
   POS SYSTEM - CLEAN FULL VERSION (NO FEATURE REMOVED)
   - Product Modal (size/color/qty + add)
   - Cart render (unit + total + qty controls)
   - Empty cart state
   - Cart qty count in header (total qty)
   - Discount box (percent/amount) + live total update (NO TAX)
   - Payment modal (methods + partial + order type + delivery section + address)
   - Customer autocomplete
   - Suspend invoices (fly animation + badge + list + restore)
   - Barcode enter opens product modal (demo mapping)
   - Category tabs filtering + active focus
========================================================= */

/* ===============================
   SOUND
================================ */

function syncMobileCart() {
  const desktop = document.getElementById("cartItems");
  const mobile = document.getElementById("cartMobileContent");
  if (desktop && mobile) {
    mobile.innerHTML = desktop.innerHTML;
  }
}

function playBeep() {
  const ctx = new (window.AudioContext || window.webkitAudioContext)();
  const oscillator = ctx.createOscillator();
  const gain = ctx.createGain();

  oscillator.type = "square";
  oscillator.frequency.value = 1200;
  gain.gain.value = 0.08;

  oscillator.connect(gain);
  gain.connect(ctx.destination);

  oscillator.start();
  oscillator.stop(ctx.currentTime + 0.12);
}

/* ===============================
   STATE
================================ */
let cart = [];
let currentProduct = {};
let selectedSize = null;
let selectedColor = null;
let modalQty = 1;

// Discount state (global discount for whole cart)
let discount = {
  type: "percent", // percent | amount
  value: 0
};

// Suspended invoices
let suspendedInvoices = [];

/* ===============================
   HELPERS
================================ */
function $(id) {
  return document.getElementById(id);
}

function parseMoneyFromText(text) {
  // Extract number from something like "450.00 ر.ع"
  const num = parseFloat(String(text).replace(/[^\d.]/g, ""));
  return isNaN(num) ? 0 : num;
}

function formatMoney(value) {
  return `${Number(value || 0).toFixed(2)} ${translations.omr}`;
}

/* ===============================
   PRODUCT MODAL LOGIC
================================ */
function resetProductSelectionUI() {
  selectedSize = null;
  selectedColor = null;

  // Reset old size/color buttons if they exist
  document.querySelectorAll(".size-btn").forEach((btn) => {
    btn.classList.remove("bg-primary", "text-white");
  });

  document.querySelectorAll(".color-btn").forEach((btn) => {
    btn.classList.remove("ring-2", "ring-primary");
  });

  // Reset color-size items
  document.querySelectorAll(".color-size-item").forEach((item) => {
    item.classList.remove("border-primary", "bg-primary/5", "shadow-md");
    item.classList.add("border-gray-200");
  });
}

function changeQty(change) {
  modalQty = Math.max(1, modalQty + change);
  $("modalQty").innerText = modalQty;
}

function openProductModal(product) {
  // Reset selection every open (fixes the "must change size/color to add again" bug)
  resetProductSelectionUI();

  currentProduct = {
    id: product.id,
    name: product.name,
    price: Number(product.price),
    image: product.image
  };

  modalQty = 1;
  $("modalName").innerText = currentProduct.name;
  $("modalPrice").innerText = formatMoney(currentProduct.price);
  $("modalImage").style.backgroundImage = `url('${currentProduct.image}')`;
  $("modalQty").innerText = "1";

  // Show loading state
  const container = document.getElementById("colorSizeContainer");
  container.innerHTML = `
    <div class="text-center text-gray-400 py-4">
      <span class="material-symbols-outlined text-4xl mb-2 block animate-pulse">inventory_2</span>
      <p class="text-sm">${translations.loading}...</p>
    </div>
  `;

  const modal = $("productModal");
  modal.classList.remove("hidden");
  modal.classList.add("flex");

  // Fetch stock details with colors and sizes
  fetch(`{{ url('pos/stock') }}/${product.id}`)
    .then(response => response.json())
    .then(data => {
      displayColorSizes(data.colorSizes);
    })
    .catch(error => {
      console.error('Error fetching stock details:', error);
      container.innerHTML = `
        <div class="text-center text-red-400 py-4">
          <span class="material-symbols-outlined text-4xl mb-2 block">error</span>
          <p class="text-sm">${translations.errorLoadingData}</p>
        </div>
      `;
    });
}

function displayColorSizes(colorSizes) {
  const container = document.getElementById("colorSizeContainer");
  
  if (!colorSizes || colorSizes.length === 0) {
    container.innerHTML = `
      <div class="text-center text-gray-400 py-4">
        <span class="material-symbols-outlined text-4xl mb-2 block">inventory</span>
        <p class="text-sm">${translations.noStockAvailable}</p>
      </div>
    `;
    return;
  }

  let html = '';
  
  colorSizes.forEach((item) => {
    const isAvailable = item.quantity > 0;
    const quantityClass = isAvailable ? 'text-primary font-bold' : 'text-gray-400';
    const cardClass = isAvailable 
      ? 'bg-white border border-gray-200 hover:border-primary hover:shadow-sm transition-all cursor-pointer' 
      : 'bg-gray-50 border border-gray-200 opacity-50 cursor-not-allowed';
    
    html += `
      <div class="color-size-item rounded-lg p-2 ${cardClass}" 
           data-size-id="${item.size_id}" 
           data-color-id="${item.color_id}"
           data-size-name="${item.size_name}"
           data-color-name="${item.color_name}"
           data-color-code="${item.color_code}"
           data-quantity="${item.quantity}"
           ${isAvailable ? 'onclick="selectColorSize(this)"' : ''}>
        <div class="flex items-center gap-2">
          <!-- Color Circle with Name -->
          <div class="flex flex-col items-center gap-1 flex-shrink-0">
            <div class="w-8 h-8 rounded-full border border-gray-300 shadow-sm" 
                 style="background-color: ${item.color_code}"></div>
            <span class="text-[10px] text-gray-600 text-center leading-tight max-w-[50px] truncate">${item.color_name}</span>
          </div>
          
          <!-- Size Name -->
          <div class="flex-1 min-w-0">
            <div class="text-xs font-semibold text-gray-800 mb-0.5">${item.size_name}</div>
            <div class="text-[10px] text-gray-500">
              ${translations.available}: <span class="${quantityClass}">${item.quantity}</span>
            </div>
          </div>
          
          <!-- Quantity Badge -->
          <div class="flex-shrink-0">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full ${isAvailable ? 'bg-primary/10 text-primary' : 'bg-gray-200 text-gray-400'} font-bold text-[11px]">
              ${item.quantity}
            </span>
          </div>
        </div>
      </div>
    `;
  });
  
  container.innerHTML = html;
}

function selectColorSize(element) {
  // Remove previous selection
  document.querySelectorAll('.color-size-item').forEach(item => {
    item.classList.remove('border-primary', 'bg-primary/5', 'shadow-sm');
    if (!item.classList.contains('opacity-50')) {
      item.classList.add('border-gray-200');
    }
  });
  
  // Add selection to clicked item
  element.classList.remove('border-gray-200');
  element.classList.add('border-primary', 'bg-primary/5', 'shadow-sm');
  
  // Set selected size and color
  selectedSize = element.dataset.sizeName;
  selectedColor = element.dataset.colorId;
  
  // Update size and color button styles (if they exist)
  updateSelectionUI(element);
}

function updateSelectionUI(selectedElement) {
  // This function can be used to update any additional UI elements
  // For now, the selection is handled by the card styling
}

function closeModal() {
  const modal = $("productModal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");

  // Reset selection when closing
  resetProductSelectionUI();
  modalQty = 1;
}

function confirmAddToCart() {
  if (!selectedSize || !selectedColor) {
    alert(translations.selectSizeColor);
    return;
  }

  // Get selected color-size element to get quantity
  const selectedElement = document.querySelector('.color-size-item.border-primary');
  if (!selectedElement) {
    alert(translations.selectSizeColor);
    return;
  }

  const availableQty = parseInt(selectedElement.dataset.quantity) || 0;
  
  // Check if requested quantity is available
  if (modalQty > availableQty) {
    Swal.fire({
      icon: 'error',
      title: translations.quantityError || 'Quantity Error',
      text: `${translations.quantityAvailable} ${availableQty}`,
      confirmButtonText: translations.ok || 'OK',
      confirmButtonColor: '#1F6F67'
    });
    return;
  }

  // Get color and size names for display
  const colorName = selectedElement.dataset.colorName || '';
  const sizeName = selectedElement.dataset.sizeName || selectedSize;
  
  // Get color code from data attribute
  const colorCode = selectedElement.dataset.colorCode || '#000000';

  // Build cart item
  const item = {
    id: currentProduct.id,
    name: currentProduct.name,
    price: Number(currentProduct.price),
    image: currentProduct.image,
    size: sizeName,
    color: colorName,
    colorId: selectedColor,
    colorCode: colorCode,
    availableQty: availableQty,
    qty: modalQty
  };

  // Merge logic - match by id, size name, and color id
  const existing = cart.find((i) => 
    i.id === item.id && 
    i.size === item.size && 
    i.colorId === item.colorId
  );

  if (existing) {
    const newQty = existing.qty + item.qty;
    if (newQty > availableQty) {
      Swal.fire({
        icon: 'error',
        title: translations.quantityError || 'Quantity Error',
        text: (translations.quantityAvailable || 'Available quantity is') + ' ' + availableQty,
        confirmButtonText: translations.ok || 'OK',
        confirmButtonColor: '#1F6F67'
      });
      return;
    }
    existing.qty = newQty;
    // Update availableQty in case it changed
    existing.availableQty = availableQty;
  } else {
    cart.push(item);
  }

  playBeep();
  renderCart();
  recalculateTotals();
  closeModal(); // Ensure it closes after add
}

/* ===============================
   CART RENDER + TOTALS
================================ */
function getCartSubtotal() {
  return cart.reduce((sum, item) => sum + item.price * item.qty, 0);
}

function getDiscountAmount(subtotal) {
  let amount = 0;

  if (discount.type === "percent") {
    amount = subtotal * (discount.value / 100);
  } else {
    amount = discount.value;
  }

  // Never exceed subtotal
  amount = Math.min(amount, subtotal);
  return amount;
}

function recalculateTotals() {
  let subtotal = 0;
  let totalQty = 0;

  cart.forEach((item) => {
    subtotal += item.price * item.qty;
    totalQty += item.qty;
  });

  // Calculate discount
  const discountAmount = getDiscountAmount(subtotal);
  const total = subtotal - discountAmount;

  // ===== Desktop total (shows payable amount after discount) =====
  const totalEl = document.getElementById("cartTotal");
  if (totalEl) {
    totalEl.innerHTML = `${total.toFixed(2)} <span class="text-base font-medium text-gray-500">${translations.omr}</span>`;
  }

  // ===== Update payment modal amounts =====
  updatePaymentModalAmounts(subtotal, discountAmount, total);

  // ===== Cart count (العنوان) =====
  const countEl = document.getElementById("cartCount");
  if (countEl) {
    countEl.innerText = `(${totalQty} ${translations.items})`;
  }

  // ===== Mobile badge =====
  const mobileBadge = document.getElementById("cartMobileBadge");
  if (mobileBadge) {
    if (totalQty > 0) {
      mobileBadge.innerText = totalQty;
      mobileBadge.classList.remove("hidden");
    } else {
      mobileBadge.classList.add("hidden");
    }
  }

  // ===== Mobile total (لو موجود) =====
  const mobileTotal = document.getElementById("cartMobileTotal");
  if (mobileTotal) {
    mobileTotal.innerText = total.toFixed(2) + " " + translations.omr;
  }

  // ===== Empty state (desktop) =====
  const emptyState = document.getElementById("emptyCart");
  if (emptyState) {
    if (cart.length === 0) {
      emptyState.classList.remove("hidden");
    } else {
      emptyState.classList.add("hidden");
    }
  }

  // ===== Sync mobile cart =====
  syncMobileCart();
}

function renderCart() {
  const container = document.getElementById("cartItems");
  const emptyState = document.getElementById("emptyCart");

  container.innerHTML = "";

  if (cart.length === 0) {
    emptyState.classList.remove("hidden");
    recalculateTotals();
    return;
  }

  emptyState.classList.add("hidden");

  cart.forEach((item, index) => {
    const itemTotal = item.price * item.qty;

    container.innerHTML += `
     <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-3">

  <!-- Top row -->
  <div class="flex items-center gap-4">
    <!-- Image -->
    <div class="w-16 h-16 rounded-xl bg-gray-100 overflow-hidden shrink-0">
      <div class="w-full h-full bg-cover bg-center"
           style="background-image:url('${item.image}')"></div>
    </div>

    <!-- Name + size + color -->
    <div class="flex-1 min-w-0">
      <h4 class="font-bold text-gray-800 truncate">${item.name}</h4>
      <div class="flex items-center gap-2 mt-1">
        <p class="text-xs text-gray-500">${translations.size}: ${item.size}</p>
        ${item.color ? `
          <span class="text-gray-400">•</span>
          <div class="flex items-center gap-1.5">
            <div class="w-4 h-4 rounded-full border border-gray-300" style="background-color: ${item.colorCode || '#000000'}"></div>
            <p class="text-xs text-gray-500">${item.color}</p>
          </div>
        ` : ''}
      </div>
    </div>
  </div>

  <!-- Prices -->
  <div class="flex justify-between text-sm text-gray-600">
    <span>${translations.unitPrice}</span>
    <span class="font-bold">${item.price.toFixed(2)} ${translations.omr}</span>
  </div>

  <div class="flex justify-between items-center">
    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-3 py-1">
      <button onclick="updateQty(${index}, -1)"
        class="w-7 h-7 rounded-full bg-white text-gray-600 hover:bg-gray-200">−</button>

      <span class="w-6 text-center font-bold">${item.qty}</span>

      <button onclick="updateQty(${index}, 1)"
        class="w-7 h-7 rounded-full bg-primary text-white">+</button>
    </div>

    <div class="text-right">
      <p class="text-xs text-gray-500">${translations.total}</p>
      <p class="font-extrabold text-primary">
        ${(item.price * item.qty).toFixed(2)} ${translations.omr}
      </p>
    </div>
  </div>

</div>





        
        </div>
      </div>
    `;
  });

  recalculateTotals();
}

function updateQty(index, change) {
  if (!cart[index]) return;

  const item = cart[index];
  const newQty = item.qty + change;

  // Check if trying to increase quantity beyond available
  if (change > 0) {
    const availableQty = parseInt(item.availableQty) || 0;
    if (availableQty > 0 && newQty > availableQty) {
      Swal.fire({
        icon: 'error',
        title: translations.quantityError || 'Quantity Error',
        text: (translations.quantityAvailable || 'Available quantity is') + ' ' + availableQty,
        confirmButtonText: translations.ok || 'OK',
        confirmButtonColor: '#1F6F67'
      });
      return;
    }
  }

  // Decrease quantity or remove if 0
  if (newQty <= 0) {
    cart.splice(index, 1);
  } else {
    item.qty = newQty;
  }

  renderCart();
  recalculateTotals();
}

function clearCart() {
  cart = [];
  renderCart();
  recalculateTotals();
}

/* ===============================
   DISCOUNT
================================ */
function toggleDiscount() {
  const box = $("discountBox");
  const btn = $("discountBtn");
  if (!box) return;

  box.classList.toggle("hidden");

  // Focus style on the button
  if (btn) {
    btn.classList.toggle("bg-primary");
    btn.classList.toggle("text-white");
  }
}

function initDiscountHandlers() {
  const type = $("discountType");
  const value = $("discountValue");

  if (type) {
    type.onchange = function () {
      discount.type = this.value;
      recalculateTotals();
      updateDiscountDisplay();
    };
  }

  if (value) {
    value.oninput = function () {
      discount.value = parseFloat(this.value) || 0;
      recalculateTotals();
      updateDiscountDisplay();
    };
  }
}

function updateDiscountDisplay() {
  const subtotal = getCartSubtotal();
  const discountAmount = getDiscountAmount(subtotal);
  const discountAmountEl = $("discountAmount");
  
  if (discountAmountEl) {
    discountAmountEl.innerText = discountAmount.toFixed(2) + " " + translations.omr;
  }
}

/* ===============================
   PAYMENT MODAL
================================ */
function openPaymentModal() {
  if (!cart.length) {
    alert(translations.cartEmpty);
    return;
  }

  // Set payment total from calculated total (not raw text)
  recalculateTotals();

  const modal = $("paymentModal");
  modal.classList.remove("hidden");
  modal.classList.add("flex");

  initPaymentButtons();
  initOrderTypeButtons();
  initCustomerAutocomplete();
  initPartialPaymentInputs();
}

function updatePaymentModalAmounts(subtotal, discountAmount, payableAmount) {
  // Update subtotal
  const subtotalEl = document.getElementById("paymentSubtotal");
  if (subtotalEl) {
    subtotalEl.innerText = subtotal.toFixed(2) + " " + translations.omr;
  }

  // Update discount (show/hide based on discount amount)
  const discountRow = document.getElementById("paymentDiscountRow");
  const discountEl = document.getElementById("paymentDiscount");
  if (discountRow && discountEl) {
    if (discountAmount > 0) {
      discountRow.classList.remove("hidden");
      discountEl.innerText = "-" + discountAmount.toFixed(2) + " " + translations.omr;
    } else {
      discountRow.classList.add("hidden");
    }
  }

  // Update payable amount
  const paymentTotalEl = document.getElementById("paymentTotal");
  if (paymentTotalEl) {
    paymentTotalEl.innerText = payableAmount.toFixed(2) + " " + translations.omr;
  }
}

function closePaymentModal() {
  const modal = $("paymentModal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

/* Payment method buttons (cash/visa/transfer/partial) */
function initPaymentButtons() {
  const buttons = document.querySelectorAll(".pay-btn");
  const partialBox = $("partialPaymentBox");

  buttons.forEach((btn) => {
    btn.onclick = () => {
      buttons.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      if (btn.dataset.method === "partial") {
        partialBox?.classList.remove("hidden");
      } else {
        partialBox?.classList.add("hidden");
      }
    };
  });

  // Default focus to visa
  document.querySelector('.pay-btn[data-method="visa"]')?.classList.add("active");
}

/* Order type buttons (direct/delivery) */
function initOrderTypeButtons() {
  const buttons = document.querySelectorAll(".order-type-btn");
  const delivery = $("deliverySection");

  buttons.forEach((btn) => {
    btn.onclick = () => {
      buttons.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      if (btn.dataset.type === "delivery") {
        delivery?.classList.remove("hidden");
      } else {
        delivery?.classList.add("hidden");
      }
    };
  });

  // Default direct
  document.querySelector('.order-type-btn[data-type="direct"]')?.classList.add("active");
  $("deliverySection")?.classList.add("hidden");
}

/* Partial payment remaining calculation */
function initPartialPaymentInputs() {
  ["partialCash", "partialVisa", "partialTransfer"].forEach((id) => {
    const el = $(id);
    if (!el) return;
    el.addEventListener("input", updatePartialRemaining);
  });
}

function updatePartialRemaining() {
  // Use payable amount (after discount) from payment modal
  const payableAmount = parseMoneyFromText($("paymentTotal")?.innerText || "0");
  const cash = parseFloat($("partialCash")?.value || "0") || 0;
  const visa = parseFloat($("partialVisa")?.value || "0") || 0;
  const transfer = parseFloat($("partialTransfer")?.value || "0") || 0;

  const remaining = payableAmount - (cash + visa + transfer);
  if ($("partialRemaining")) {
    $("partialRemaining").innerText = `${Math.max(0, remaining).toFixed(2)} ${translations.omr}`;
  }
}

/* ===============================
   CUSTOMER AUTOCOMPLETE
================================ */
function initCustomerAutocomplete() {
  const demoCustomers = [
    { phone: "9999", name: "أحمد سالم" },
    { phone: "99991", name: "سارة محمد" },
    { phone: "99992", name: "فاطمة علي" }
  ];

  const input = $("customerPhone");
  const box = $("customerSuggestions");
  const name = $("customerName");
  const selected = $("selectedCustomer");

  if (!input || !box) return;

  input.oninput = () => {
    box.innerHTML = "";

    const value = input.value.trim();
    if (!value) {
      box.classList.add("hidden");
      return;
    }

    const matches = demoCustomers.filter((c) => c.phone.startsWith(value));
    if (!matches.length) {
      box.classList.add("hidden");
      return;
    }

    matches.forEach((c) => {
      const div = document.createElement("div");
      div.className = "p-3 hover:bg-gray-50 cursor-pointer text-sm";
      div.innerText = `${c.name} – ${c.phone}`;
      div.onclick = () => {
        if (name) name.value = c.name;
        input.value = c.phone;

        box.classList.add("hidden");

        if (selected) {
          selected.classList.remove("hidden");
          selected.innerHTML = `
            <strong>الزبون:</strong> ${c.name}<br/>
            <span class="text-sm text-gray-500">${c.phone}</span>
          `;
        }
      };
      box.appendChild(div);
    });

    box.classList.remove("hidden");

    // auto scroll while search customer
    setTimeout(() => {
      box.scrollIntoView({
        behavior: "smooth",
        block: "nearest"
      });
    }, 50);
  };
}

/* ===============================
   SUSPEND INVOICE (FLY + BADGE + LIST)
================================ */
function updateSuspendedBadge() {
  const badge = $("suspendedBadge");
  const count = suspendedInvoices.length;

  if (!badge) return;

  if (count > 0) {
    badge.innerText = count;
    badge.classList.remove("hidden");
  } else {
    badge.classList.add("hidden");
  }
}

function suspendCurrentCart() {
  if (!cart.length) {
    alert(translations.cartEmpty);
    return;
  }

  // Fly animation target = notification button
  const target = $("notificationBtn");
  const targetRect = target.getBoundingClientRect();
  const cartRect = $("cartItems").getBoundingClientRect();

  cart.forEach((item, idx) => {
    const fly = document.createElement("div");
    fly.className = "fly-item";
    fly.style.backgroundImage = `url(${item.image})`;
    fly.style.left = cartRect.left + 40 + "px";
    fly.style.top = cartRect.top + 40 + idx * 18 + "px";
    document.body.appendChild(fly);

    requestAnimationFrame(() => {
      fly.style.transform = `
        translate(${targetRect.left - cartRect.left}px, ${targetRect.top - cartRect.top}px) scale(0.2)
      `;
      fly.style.opacity = "0";
    });

    setTimeout(() => fly.remove(), 900);
  });

  // Generate order number: YYYYMM-random number
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const randomNum = Math.floor(Math.random() * 10000);
  const orderNumber = `${year}${month}-${randomNum}`;
  
  // Format date and time
  const day = String(now.getDate()).padStart(2, '0');
  const monthStr = String(now.getMonth() + 1).padStart(2, '0');
  const yearStr = now.getFullYear();
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  const dateTime = `${day}/${monthStr}/${yearStr} ${hours}:${minutes}:${seconds}`;

  // Calculate total amount
  const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
  const discountAmount = getDiscountAmount(subtotal);
  const totalAmount = subtotal - discountAmount;

  // Save invoice snapshot
  suspendedInvoices.push({
    id: orderNumber,
    orderNumber: orderNumber,
    date: dateTime,
    items: JSON.parse(JSON.stringify(cart)),
    discount: JSON.parse(JSON.stringify(discount)),
    subtotal: subtotal,
    discountAmount: discountAmount,
    total: totalAmount,
    totalFormatted: formatMoney(totalAmount)
  });

  // Shake notification
  target.classList.add("shake");
  setTimeout(() => target.classList.remove("shake"), 600);

  updateSuspendedBadge();

  // Clear cart
  cart = [];
  renderCart();
  recalculateTotals();
}

function openSuspendedModal() {
  const modal = $("suspendedModal");
  const list = $("suspendedList");

  if (!modal || !list) return;

  list.innerHTML = "";

  if (!suspendedInvoices.length) {
    list.innerHTML = `
      <div class="p-6 text-center text-gray-500">
        ${translations.noSuspendedInvoices}
      </div>
    `;
  } else {
    suspendedInvoices.forEach((inv, i) => {
      const totalItems = inv.items.reduce((s, x) => s + x.qty, 0);
      const itemsList = inv.items.map(item => 
        `${item.name} (${item.size}${item.color ? ', ' + item.color : ''}) x${item.qty}`
      ).join(', ');
      
      list.innerHTML += `
        <div class="p-4 border rounded-xl bg-white hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-3">
            <div class="flex-1">
              <p class="font-bold text-lg text-gray-800 mb-1">${translations.orderNumber || 'Order'}: ${inv.orderNumber || inv.id}</p>
              <p class="text-xs text-gray-500 mb-2">
                <span class="material-symbols-outlined text-xs align-middle">schedule</span>
                ${inv.date}
              </p>
            </div>
            <div class="text-right ml-4">
              <p class="font-bold text-xl text-primary">${inv.totalFormatted || inv.total}</p>
            </div>
          </div>
          
          <div class="mb-3 pt-3 border-t border-gray-100">
            <p class="text-xs font-semibold text-gray-600 mb-2">${translations.items} (${totalItems}):</p>
            <div class="text-xs text-gray-600 space-y-1 max-h-20 overflow-y-auto">
              ${inv.items.map(item => `
                <div class="flex items-center gap-2">
                  <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                  <span>${item.name} - ${item.size}${item.color ? ' (' + item.color + ')' : ''} x${item.qty}</span>
                </div>
              `).join('')}
            </div>
          </div>
          
          <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
            <button onclick="restoreInvoice(${i})"
              class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-dark transition-colors">
              ${translations.restore}
            </button>
          </div>
        </div>
      `;
    });
  }

  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeSuspendedModal() {
  const modal = $("suspendedModal");
  modal?.classList.add("hidden");
  modal?.classList.remove("flex");
}

function restoreInvoice(index) {
  const inv = suspendedInvoices[index];
  if (!inv) return;

  cart = inv.items || [];
  discount = inv.discount || { type: "percent", value: 0 };

  suspendedInvoices.splice(index, 1);

  // Reflect discount UI if box exists
  if ($("discountType")) $("discountType").value = discount.type;
  if ($("discountValue")) $("discountValue").value = discount.value;

  updateSuspendedBadge();
  renderCart();
  recalculateTotals();
  closeSuspendedModal();
}

/* ===============================
   BARCODE → OPEN PRODUCT MODAL (DEMO)
================================ */
function initBarcode() {
  const input = $("barcodeInput");
  if (!input) return;

  // Search functionality - filter products as user types
  input.addEventListener("input", function() {
    const searchTerm = this.value.trim().toLowerCase();
    const products = document.querySelectorAll(".product-item");
    
    // Get active category filter
    const activeTab = document.querySelector(".category-tab.active");
    const activeFilter = activeTab ? activeTab.dataset.filter : "all";
    
    if (!searchTerm) {
      // Show all products based on category filter when search is empty
      products.forEach((p) => {
        const cat = p.dataset.category || "";
        if (activeFilter === "all" || cat === activeFilter) {
          p.classList.remove("hidden");
        } else {
          p.classList.add("hidden");
        }
      });
      return;
    }

    products.forEach((product) => {
      const barcode = (product.dataset.barcode || "").toLowerCase();
      const abayaCode = (product.dataset.abayaCode || "").toLowerCase();
      const designName = (product.dataset.designName || "").toLowerCase();
      const name = (product.dataset.name || "").toLowerCase();
      
      // Check if search term matches barcode, abaya code, or name
      const searchMatch = 
        barcode.includes(searchTerm) ||
        abayaCode.includes(searchTerm) ||
        designName.includes(searchTerm) ||
        name.includes(searchTerm);
      
      // Check category filter
      const cat = product.dataset.category || "";
      const categoryMatch = (activeFilter === "all" || cat === activeFilter);
      
      // Show product only if both category and search match
      if (searchMatch && categoryMatch) {
        product.classList.remove("hidden");
      } else {
        product.classList.add("hidden");
      }
    });
  });

  // Enter key - open product modal if exact match found
  input.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;

    const searchTerm = input.value.trim();
    if (!searchTerm) return;

    // Try to find exact match by barcode first
    let productItem = document.querySelector(`.product-item[data-barcode="${searchTerm}"]`);
    
    // If not found by barcode, try abaya code
    if (!productItem) {
      productItem = document.querySelector(`.product-item[data-abaya-code="${searchTerm}"]`);
    }
    
    // If still not found, try to find first visible product
    if (!productItem) {
      productItem = document.querySelector(".product-item:not(.hidden)");
    }
    
    if (!productItem) {
      alert(translations.productNotFound);
      input.value = "";
      return;
    }

    const product = {
      id: productItem.dataset.id,
      name: productItem.dataset.name,
      price: parseFloat(productItem.dataset.price),
      image: productItem.dataset.image
    };

    playBeep();
    openProductModal(product);
    input.value = "";
    
    // Reset search filter but respect category filter
    const activeTab = document.querySelector(".category-tab.active");
    const activeFilter = activeTab ? activeTab.dataset.filter : "all";
    
    document.querySelectorAll(".product-item").forEach((p) => {
      const cat = p.dataset.category || "";
      if (activeFilter === "all" || cat === activeFilter) {
        p.classList.remove("hidden");
      } else {
        p.classList.add("hidden");
      }
    });
  });
}

/* ===============================
   CATEGORY TABS FILTERING
================================ */
function initCategoryTabs() {
  const tabs = document.querySelectorAll(".category-tab");
  const products = document.querySelectorAll(".product-item");

  if (!tabs.length) return;

  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("active"));

      tab.classList.add("active");
      tab.focus();

      const filter = tab.dataset.filter;
      const searchInput = $("barcodeInput");
      const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : "";

      products.forEach((p) => {
        const cat = p.dataset.category || "";
        const barcode = (p.dataset.barcode || "").toLowerCase();
        const abayaCode = (p.dataset.abayaCode || "").toLowerCase();
        const designName = (p.dataset.designName || "").toLowerCase();
        const name = (p.dataset.name || "").toLowerCase();
        
        // Check category filter
        let categoryMatch = false;
        if (filter === "all") {
          categoryMatch = true;
        } else {
          categoryMatch = (cat === filter);
        }
        
        // Check search filter
        let searchMatch = true;
        if (searchTerm) {
          searchMatch = 
            barcode.includes(searchTerm) ||
            abayaCode.includes(searchTerm) ||
            designName.includes(searchTerm) ||
            name.includes(searchTerm);
        }
        
        // Show product only if both category and search match
        if (categoryMatch && searchMatch) {
          p.classList.remove("hidden");
        } else {
          p.classList.add("hidden");
        }
      });
    });
  });

  // Default active tab
  const defaultTab = document.querySelector('.category-tab[data-filter="all"]');
  if (defaultTab) defaultTab.classList.add("active");
}

/* ===============================
   BIND UI EVENTS
================================ */
function bindProductsClick() {
  document.querySelectorAll(".product-item").forEach((item) => {
    item.addEventListener("click", () => {
      openProductModal({
        id: item.dataset.id,
        name: item.dataset.name,
        price: parseFloat(item.dataset.price),
        image: item.dataset.image
      });
    });
  });
}

function bindSizeColorButtons() {
  document.querySelectorAll(".size-btn").forEach((btn) => {
    btn.onclick = () => {
      document.querySelectorAll(".size-btn").forEach((b) => b.classList.remove("bg-primary", "text-white"));
      btn.classList.add("bg-primary", "text-white");
      selectedSize = btn.innerText.trim();
    };
  });

  document.querySelectorAll(".color-btn").forEach((btn) => {
    btn.onclick = () => {
      document.querySelectorAll(".color-btn").forEach((b) => b.classList.remove("ring-2", "ring-primary"));

      btn.classList.add("ring-2", "ring-primary");
      selectedColor = getComputedStyle(btn).backgroundColor;

      // 📱 Mobile UX: auto confirm if size is selected
      if (window.innerWidth < 768 && selectedSize) {
        confirmAddToCart();
      }
    };
  });
}

function bindSuspendButton() {
  const btn = $("suspendBtn");
  if (!btn) return;

  btn.addEventListener("click", suspendCurrentCart);
}

/* ===============================
   INIT
================================ */
document.addEventListener("DOMContentLoaded", () => {
  // Ensure cart starts empty
  cart = [];

  bindProductsClick();
  bindSizeColorButtons();

  initDiscountHandlers();
  initBarcode();
  initCategoryTabs();
  bindSuspendButton();

  updateSuspendedBadge();
  renderCart();
  recalculateTotals();
});

function openCartMobile() {
  const modal = document.getElementById("cartMobile");
  if (!modal) return;

  modal.classList.remove("hidden");

  syncMobileCart();

  // scroll
  document.body.classList.add("modal-open");
}

function closeCartMobile() {
  const modal = document.getElementById("cartMobile");
  if (!modal) return;

  modal.classList.add("hidden");

  // 🔓 رجّع سكرول الصفحة
  document.body.classList.remove("modal-open");
}

/* ===============================
   EXPOSE FUNCTIONS FOR HTML onclick
================================ */
window.changeQty = changeQty;
window.closeModal = closeModal;
window.confirmAddToCart = confirmAddToCart;

window.openPaymentModal = openPaymentModal;
window.closePaymentModal = closePaymentModal;

window.toggleDiscount = toggleDiscount;
window.clearCart = clearCart;

window.openSuspendedModal = openSuspendedModal;
window.closeSuspendedModal = closeSuspendedModal;
window.restoreInvoice = restoreInvoice;

</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.all.min.js"></script>

</body></html>