@php
  $apiVersion = env('API_VERSION', 'v1'); // default v1
  $currentLang = app()->getLocale(); 
  if($currentLang === 'ar')
  {
    $dir="rtl";
  }
  else {
    $dir="ltr";
  }
@endphp
 
<!DOCTYPE html>
<html dir="{{ $dir }}" lang="ar" x-data="{ sidebarOpen: false }" :class="{'overflow-hidden': sidebarOpen}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" /> 
  <title>@yield('title', config('app.name'))</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ url('assets/vendors/toastr/css/toastr.min.css') }}">
  <link href="{{ url('assets/vendors/sweetalert2/dist/sweetalert2.min.css') }}">
  <!-- <link rel="stylesheet" href="output.css"/> -->
  <link rel="stylesheet" href="{{ url('assets/css/style.css') }}">

 
</head>
<body class="bg-gray-50" x-data="{ loading: true }" x-init="loading = false" class="relative" id="app">
  <!-- Preloader overlay -->
  <div x-show="loading" class="preloader fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
      <div class="loader border-4 border-pink-200 border-t-[var(--primary-color)] rounded-full w-12 h-12 animate-spin mb-3"></div>
      <p class="text-white font-semibold">جارٍ تحميل الصفحة...</p>
  </div>

  <!-- Overlay for mobile sidebar -->
  <div x-show="sidebarOpen" 
       x-transition:enter="transition-opacity ease-linear duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition-opacity ease-linear duration-300"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 bg-black/30 z-40 md:hidden" 
       @click="sidebarOpen=false" 
       aria-hidden="true">
  </div>

  <div class="main-container">
    <!-- Sidebar -->
  <aside
  :class="{'open': sidebarOpen}"
  class="sidebar bg-white flex flex-col p-6 space-y-6 shrink-0
         fixed inset-y-0 right-0 translate-x-full z-50  /* موبايل: أوف-كانفِس */
         md:translate-x-0
         md:sticky md:top-0 md:h-[100dvh] md:overflow-y-auto  /* لابتوب/ديسكتوب: ممتد ويعمل سكرول داخلي */
         border-l border-pink-100 shadow-sm md:shadow-none"
  x-cloak
>
  <div class="sidebar-inner w-full">
    <div class="text-2xl font-bold text-center text-[var(--primary-color)] py-4 border-b border-pink-100">
      @php echo getColumnValue('settings','id',1,'name'); @endphp
    </div>
    
    <nav class="sidebar-nav flex flex-col gap-3 mt-2 flex-grow px-3 py-4">
      <a href="{{ route($apiVersion.'.dashboards.index') }}" class="flex items-center gap-4 px-5 py-3 rounded-xl bg-pink-100/60 text-[var(--primary-color)] font-semibold">
        <span class="material-symbols-outlined">dashboard</span><span class="label">{{ trans('messages.dashboard_lang', [], session('locale')) }}</span>
      </a>
      @if (Auth::guard('tenant')->user()->booking==1)
        <div x-data="{open:false}">
          <button @click="open = !open" class="flex items-center justify-between w-full gap-4 px-5 py-3 rounded-xl hover:bg-pink-50 transition text-gray-500 hover:text-[var(--primary-color)]">
            <div class="flex items-center gap-4">
              <span class="material-symbols-outlined">calendar_month</span><span class="label">{{ trans('messages.booking_lang', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined transition" :class="{'rotate-180':open}">expand_more</span>
          </button>
          <div class="submenu mt-2 pr-8 space-y-2" x-show="open" x-transition x-cloak>
            <a href="{{ route($apiVersion.'.booking.index') }}" class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.booking_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.booking.laundryMaintenance') }}"  class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.laundry_maintenance_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.booking.bookingDelivery') }}"  class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.dress_delivery_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.booking.bookingReceive') }}"  class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.dress_receiving_lang', [], session('locale')) }}</a>
          </div>
        </div>
      @endif
  
      @if (Auth::guard('tenant')->user()->dress==1)
        <div x-data="{open:false}">
          <button @click="open = !open" class="flex items-center justify-between w-full gap-4 px-5 py-3 rounded-xl hover:bg-pink-50 transition text-gray-500 hover:text-[var(--primary-color)]">
            <div class="flex items-center gap-4">
              <span class="material-symbols-outlined">receipt_long</span><span class="label">{{ trans('messages.options_lang', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined transition" :class="{'rotate-180':open}">expand_more</span>
          </button>
          <div class="submenu mt-2 pr-8 space-y-2" x-show="open" x-transition x-cloak>
            <a href="{{ route($apiVersion.'.dresscategorys.index') }}" class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.dress_category_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.dresses.index') }}"  class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.dress_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.colors.index') }}" class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.color_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.sizes.index') }}"  class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.size_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.accessories.index') }}"  class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.accessory_lang', [], session('locale')) }}</a>
          </div>
        </div>
      @endif  

      @if (Auth::guard('tenant')->user()->customer==1)
        <a href="{{ route($apiVersion.'.customers.index') }}" class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-pink-50 transition text-gray-500 hover:text-[var(--primary-color)]">
          <span class="material-symbols-outlined">groups</span><span class="label">{{ trans('messages.customer_lang', [], session('locale')) }}</span>
        </a>
      @endif  

      @if (Auth::guard('tenant')->user()->laundry==1)
        <a href="{{ route($apiVersion.'.laundrys.index') }}" class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-pink-50 transition text-gray-500 hover:text-[var(--primary-color)]">
          <span class="material-symbols-outlined">styler</span><span class="label">{{ trans('messages.laundry_lang', [], session('locale')) }}</span>
        </a>
      @endif  

      @if (Auth::guard('tenant')->user()->expense==1)
        <div x-data="{open:false}">
          <button @click="open = !open" class="flex items-center justify-between w-full gap-4 px-5 py-3 rounded-xl hover:bg-pink-50 transition text-gray-500 hover:text-[var(--primary-color)]">
            <div class="flex items-center gap-4">
              <span class="material-symbols-outlined">receipt_long</span><span class="label">{{ trans('messages.expense_lang', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined transition" :class="{'rotate-180':open}">expand_more</span>
          </button>
          <div class="submenu mt-2 pr-8 space-y-2" x-show="open" x-transition x-cloak>
            <a href="{{ route($apiVersion.'.expense.index') }}" class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.expense_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.expensecategorys.index') }}"  class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('messages.expense_category_lang', [], session('locale')) }}</a> 
          </div>
        </div>
      @endif  
      
      @if (Auth::guard('tenant')->user()->user==1)
        <a href="{{ route($apiVersion.'.users.index') }}" class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-pink-50 transition text-gray-500 hover:text-[var(--primary-color)]">
          <span class="material-symbols-outlined">person_outline</span><span class="label">{{ trans('messages.user_lang', [], session('locale')) }}</span>
        </a>
      @endif 

      @if (Auth::guard('tenant')->user()->report==1)
        <div x-data="{open:false}">
          <button @click="open = !open" class="flex items-center justify-between w-full gap-4 px-5 py-3 rounded-xl hover:bg-pink-50 transition text-gray-500 hover:text-[var(--primary-color)]">
            <div class="flex items-center gap-4">
              <span class="material-symbols-outlined">bar_chart</span><span class="label">{{ trans('messages.report_lang', [], session('locale')) }}</span>
            </div>
            <span class="material-symbols-outlined transition" :class="{'rotate-180':open}">expand_more</span>
          </button>
          <div class="submenu mt-2 pr-8 space-y-2" x-show="open" x-transition x-cloak>
            <a href="{{ route($apiVersion.'.report.bookingReport') }}" class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('report::messages.view_booking_report_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.report.expenseReport') }}"  class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('report::messages.view_expense_report_lang', [], session('locale')) }}</a>
            <a href="{{ route($apiVersion.'.report.incomeExpenseReport') }}" class="block px-4 py-2 text-sm rounded-lg text-gray-500 hover:bg-pink-50 hover:text-[var(--primary-color)]">{{ trans('report::messages.view_income_expense_report_lang', [], session('locale')) }}</a>
            
          </div>
        </div>
      @endif  

      @if (Auth::guard('tenant')->user()->setting==1)
        <a href="{{ route($apiVersion.'.settings.index') }}" class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-pink-50 transition text-gray-500 hover:text-[var(--primary-color)]">
          <span class="material-symbols-outlined">settings</span><span class="label">{{ trans('messages.setting_lang', [], session('locale')) }}</span>
        </a>
      @endif  
    </nav>

    <div class="mt-auto border-t border-pink-100 pt-4">
      <a href="{{ route('tlogout') }}" class="mx-3 mb-4 flex items-center gap-4 px-5 py-3 rounded-xl text-red-500 hover:bg-red-50 transition">
        <span class="material-symbols-outlined">logout</span><span class="label">{{ trans('messages.logout_lang', [], session('locale')) }}</span>
      </a>
    </div>
  </div>
</aside>


    <!-- Main Content Wrapper -->
    <div class="content-wrapper">
      <header class="sticky top-0 z-30 bg-white border-b border-pink-100 shadow-sm h-16 flex items-center justify-between px-4">
        <div class="flex justify-between items-center gap-4 w-full">
          <div class="flex items-center gap-3">
            <button class="md:hidden inline-flex items-center justify-center size-10 rounded-xl border border-pink-100 bg-white shadow-custom"
                    @click="sidebarOpen = true" aria-label="فتح القائمة الجانبية">
              <span class="material-symbols-outlined">menu</span>
            </button>
            @php
              $hour = date('H'); // 00–23 
              if ($hour < 12) {
                  $text = trans('messages.g_morning_lang', [], session('locale'));
                  $icon = '<span id="ambientIcon" class="material-symbols-outlined text-yellow-400 text-2xl">wb_sunny</span>'; // sun
              } elseif ($hour < 18) {
                  $text = trans('messages.g_afternoon_lang', [], session('locale'));
                  $icon = '<span id="ambientIcon" class="material-symbols-outlined text-yellow-400 text-2xl">brightness_medium</span>';
              } else {
                  $text = trans('messages.g_evening_lang', [], session('locale'));
                  $icon = '<span id="ambientIcon" class="material-symbols-outlined text-yellow-400 text-2xl">brightness_medium</span>';
              }
            @endphp
            {!! $icon !!}
            <h1 id="greetText" class="text-base sm:text-lg font-semibold text-gray-700">  {{$text}} ، {{Auth::guard('tenant')->user()->name}}!</h1>
          </div>
          <div class="flex items-center gap-4">
            <div class="hidden sm:block">
              <button class="relative text-[var(--muted)] hover:text-[var(--primary-color)]">
                <span class="material-symbols-outlined text-2xl">notifications</span>
                <span class="absolute top-0.5 right-0.5 h-2.5 w-2.5 bg-[var(--primary-color)] rounded-full border-2 border-white"></span>
              </button>
            </div>
          

            <div class="flex items-center gap-2 text-sm">
                @if ($currentLang === 'ar')
                    <a href="{{ route('change.language', 'en') }}" class="text-[var(--muted)] hover:text-[var(--primary-color)]">EN</a>
                    <span class="text-gray-300">/</span>
                    <button class="font-bold text-[var(--primary-color)]">عربي</button>
                @else
                    <button class="font-bold text-[var(--primary-color)]">EN</button>
                    <span class="text-gray-300">/</span>
                    <a href="{{ route('change.language', 'ar') }}" class="text-[var(--muted)] hover:text-[var(--primary-color)]">عربي</a>
                @endif
            </div>

            <div class="bg-center bg-no-repeat bg-cover rounded-full size-10 sm:size-12 border-2 border-pink-100"
                 style='background-image:url("https://lh3.googleusercontent.com/aida-public/AB6AXuARfZ0takW-0qzMm7tWJ8EWddGUqithwRxS4nl38qO1Mkv1NWQVtyECBk5vGoA1HsV0fWUyqz6e8OBdrBWuzGurrNscRpmz2Go75KfAETWSbM0gh-zLcBfZjaV0OeHCCK03MMpu1qUFQDGSPtMIpoxhgdNNDdNZo2jUlwBxFCtXP844M5ZDMaZDwRjAr8m3wiY_125FaJs_N2qvxcwlGE7TxZihc6dJMdVBSNuGHQb3lMsjtpIrnnT8HuCpG6ce6OkpvYpZrNZme9k");'></div>
          </div>
        </div>
      </header>

      <!-- Main Content Area -->
    
