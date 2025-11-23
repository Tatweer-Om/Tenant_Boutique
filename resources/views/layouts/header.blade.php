<!DOCTYPE html>
<html class="light" dir="rtl" lang="ar">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @stack('title')
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link href="{{asset('css/style.css')}}" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

  <!-- Tailwind Config -->
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#8B5CF6",
            "secondary": "#F1E8FF",
            "accent": "#7C3AED",
            "text-primary": "#1F2937",
            "background-light": "#F6F5F8",
            "background-dark": "#151022",
          },
          fontFamily: {
            "display": ["IBM Plex Sans Arabic", "sans-serif"]
          },
        },
      },
    }
  </script>

</head>

<body class="bg-background-light dark:bg-background-dark font-display text-text-primary dark:text-gray-200">

  <div id="overlay" class="overlay"></div>
  <div class="flex flex-col min-h-screen">
    <div class="flex flex-1">

      <!-- ======================== SIDEBAR ======================== -->
      <aside id="sidebar" class="flex flex-col w-72 bg-white dark:bg-gray-900 text-text-primary dark:text-gray-200 shadow-sm transition-all duration-300">
        <!-- Brand -->
        <div class="flex items-center justify-center h-20 border-b border-gray-200 dark:border-gray-800 px-6">
          <div class="flex items-center gap-3">
    
            <span class="material-symbols-outlined text-primary text-3xl">store</span>
            <h1 class="text-xl font-bold">نظام العبايات</h1>
          </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
          <!-- Dashboard -->
          <a href="#" class="flex items-center gap-4 px-4 py-2.5 rounded-lg hover:bg-secondary hover:text-accent transition-colors">
            <span class="material-symbols-outlined text-2xl">dashboard</span>
            <span class="font-medium">الرئيسية</span>
          </a>

          <!-- Inventory (collapsible) -->
          <div>
            <button onclick="toggleSubmenu('inventoryMenu')" class="w-full flex items-center justify-between gap-4 px-4 py-2.5 rounded-lg hover:bg-secondary hover:text-accent transition-colors">
              <div class="flex items-center gap-4">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
                <span class="font-medium">إدارة المخزون</span>
              </div>
              <span class="material-symbols-outlined transition-transform" id="arrow-inventoryMenu">expand_more</span>
            </button>
            <div id="inventoryMenu" class="submenu mt-2 pl-8 space-y-1">
              <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> المخزون
              </a>
              <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> إرسال كميات
              </a>
              <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> نقاط البيع
              </a>
            </div>
          </div>

          <!-- Boutiques (collapsible) -->
          <div>
            <button onclick="toggleSubmenu('boutiquesMenu')" class="w-full flex items-center justify-between gap-4 px-4 py-2.5 rounded-lg hover:bg-secondary hover:text-accent transition-colors">
              <div class="flex items-center gap-4">
                <span class="material-symbols-outlined text-2xl">storefront</span>
                <span class="font-medium">إدارة البوتيكات</span>
              </div>
              <span class="material-symbols-outlined transition-transform" id="arrow-boutiquesMenu">expand_more</span>
            </button>
            <div id="boutiquesMenu" class="submenu mt-2 pl-8 space-y-1">
              <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> قائمة البوتيكات
              </a>
              <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> الأقسام الفرعية
              </a>
            </div>
          </div>

          <!-- Tailor Orders (collapsible) -->
          <div>
            <button onclick="toggleSubmenu('tailorMenu')" class="w-full flex items-center justify-between gap-4 px-4 py-2.5 rounded-lg hover:bg-secondary hover:text-accent transition-colors">
              <div class="flex items-center gap-4">
                <span class="material-symbols-outlined text-2xl">cut</span>
                <span class="font-medium">طلبات التفصيل</span>
              </div>
              <span class="material-symbols-outlined transition-transform" id="arrow-tailorMenu">expand_more</span>
            </button>
            <div id="tailorMenu" class="submenu mt-2 pl-8 space-y-1">
              <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> قائمة الطلبات
              </a>
              <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg hover:bg-secondary hover:text-accent">
                <span class="material-symbols-outlined text-sm">chevron_right</span> الخياطين
              </a>
            </div>
          </div>

          <!-- Other main links -->
          <a href="#" class="flex items-center gap-4 px-4 py-2.5 rounded-lg hover:bg-secondary hover:text-accent">
            <span class="material-symbols-outlined text-2xl">point_of_sale</span> نقطة البيع
          </a>
          <a href="#" class="flex items-center gap-4 px-4 py-2.5 rounded-lg hover:bg-secondary hover:text-accent">
            <span class="material-symbols-outlined text-2xl">assignment_return</span> المرتجعات
          </a>
          <a href="#" class="flex items-center gap-4 px-4 py-2.5 rounded-lg hover:bg-secondary hover:text-accent">
            <span class="material-symbols-outlined text-2xl">receipt_long</span> المصروفات
          </a>
          <a href="#" class="flex items-center gap-4 px-4 py-2.5 rounded-lg hover:bg-secondary hover:text-accent">
            <span class="material-symbols-outlined text-2xl">group</span> المستخدمين
          </a>
          <a href="#" class="flex items-center gap-4 px-4 py-2.5 rounded-lg hover:bg-secondary hover:text-accent">
            <span class="material-symbols-outlined text-2xl">assessment</span> التقارير
          </a>
        </nav>

        <!-- Sidebar Profile -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
          <div class="flex items-center gap-4">
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-11"
                 style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDQi91QSkV-4K0sUKmlCpkHw7UCi1VfGmQ-c05lLV0I4_qyMmxhkJUIZXZMlB8tIrZANsYNJaOhlhyhj1iC-BN8Sgiw_uJXw8pEDNOFVFaBsJNHEADXLw46WplAJvQpldP7TQMotk4xe1F-PgoK5241wnuaPr6-Xo5FDbVryyGeGYn2WK4Sv_dGNkNYyFZqaRZPOB7fMvO7KIaex6b6Ebkg3ELbq6GQO-uaaWpQutOsk7duPdr9FdkpYZZ9dhni3iNHFIMXf2bEmgds");'></div>
            <div class="flex-1">
              <h3 class="text-sm font-semibold">أحمد الفرقاني</h3>
              <p class="text-xs text-gray-500">مدير النظام</p>
            </div>
            <button class="text-gray-500 hover:text-accent" title="تسجيل الخروج">
              <span class="material-symbols-outlined">logout</span>
            </button>
          </div>
        </div>
      </aside>
      <!-- ======================== END SIDEBAR ======================== -->

      <!-- ======================== MAIN ======================== -->
      <div class="flex-1 flex flex-col">
        <!-- HEADER -->
        <header class="flex items-center justify-between bg-white dark:bg-gray-800/50 shadow-sm px-4 py-3 lg:px-6 lg:py-4">
  <!-- زر القائمة يظهر فقط في الموبايل -->
  <div class="flex items-center gap-2">
    <button id="menuBtn"
      class="lg:hidden h-10 w-10 flex items-center justify-center rounded-md hover:bg-secondary transition">
      <span class="material-symbols-outlined text-primary text-3xl">menu</span>
    </button>

    <span class="material-symbols-outlined text-primary text-3xl hidden lg:inline">dashboard</span>
    <h2 class="text-lg font-bold hidden lg:inline">لوحة التحكم</h2>
  </div>

          <!-- Actions (right side in RTL; visually on the left edge) -->
          <div class="flex items-center gap-3">
            <!-- Language Switch (click opens dropdown) -->
            <div class="relative">
              <button id="langBtn" class="h-10 w-10 rounded-full bg-secondary/60 dark:bg-primary/20 flex items-center justify-center hover:bg-secondary dark:hover:bg-primary/30 transition-colors" aria-haspopup="true" aria-expanded="false">
                <!-- Flag changes via JS (🇴🇲 or 🇬🇧) -->
                <span id="langFlag" class="text-lg leading-none">🇴🇲</span>
              </button>
              <!-- Language Dropdown -->
              <div id="langMenu" class="dropdown absolute top-full mt-2 right-0 w-40 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg z-20">
                <button data-lang="ar" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-secondary dark:hover:bg-primary/20">
                  <span class="text-base">🇴🇲</span> العربية
                </button>
                <button data-lang="en" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-secondary dark:hover:bg-primary/20">
                  <span class="text-base">🇬🇧</span> English
                </button>
              </div>
            </div>

            <!-- Notifications (click opens dropdown) -->
            <div class="relative">
              <button id="notifBtn" class="relative h-10 w-10 rounded-full bg-secondary/60 dark:bg-primary/20 flex items-center justify-center hover:bg-secondary dark:hover:bg-primary/30 transition-colors" aria-haspopup="true" aria-expanded="false">
                <span class="material-symbols-outlined text-primary text-2xl">notifications</span>
                <span class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">2</span>
              </button>
              <!-- Notifications Dropdown -->
              <div id="notifMenu" class="dropdown absolute top-full mt-2 right-0 w-80 max-w-[85vw] bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-xl z-20">
                <div class="p-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                  <span class="material-symbols-outlined text-primary">notifications</span>
                  <h4 class="font-semibold text-sm">الإشعارات</h4>
                </div>
                <ul class="max-h-80 overflow-auto">
                  <!-- Notification item -->
                  <li class="px-3 py-3 hover:bg-secondary/50 dark:hover:bg-primary/10 transition">
                    <div class="flex items-start gap-3">
                      <span class="material-symbols-outlined text-amber-500">inventory</span>
                      <div class="flex-1">
                        <p class="text-sm font-medium">كمية عباية منخفضة في المخزون</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">الكمية المتبقية: 3 قطع – تم التحديث قبل 10 دقائق</p>
                      </div>
                    </div>
                  </li>
                  <!-- Notification item -->
                  <li class="px-3 py-3 hover:bg-secondary/50 dark:hover:bg-primary/10 transition">
                    <div class="flex items-start gap-3">
                      <span class="material-symbols-outlined text-red-500">schedule</span>
                      <div class="flex-1">
                        <p class="text-sm font-medium">عباية متأخرة عن التسليم</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">طلب #A-1029 – متأخر منذ 2 ساعة</p>
                      </div>
                    </div>
                  </li>
                </ul>
                <div class="p-2 border-t border-gray-100 dark:border-gray-700 text-center">
                  <a href="#" class="text-sm text-accent hover:underline">عرض كل الإشعارات</a>
                </div>
              </div>
            </div>

            <!-- Divider -->
            <div class="h-8 w-px bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>

            <!-- Profile (avatar + dropdown) -->
            <div class="relative">
              <button id="profileBtn" class="flex items-center gap-2">
                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9"
                     style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAMV8cxyrsD5UEUacXZKG1uOrUd6OzsEMfwBfXgQOZmbd7p6YyokoBslcg1oqcqY76c8ztgCAg9dpxT70RZAni__6Sdm-iZTfhOxMbIXEtwSl4MCoUCDqDyTBo5EJOYTIFZdvlI8Nl6JPqU_JJEyhMWzO11GpP5HTweoTGbGYt2TWdCtKmGd9WwCBzxVeqlhvMLWaP4RGBbymAtu9eOjH-lzowyMre0ADXxfcHJhhtxIqkeMjO3Hsa3iE-vyevTaGk43vmxXPjseZon");'></div>
                <span class="hidden md:inline text-sm font-medium">أحمد الفرقاني</span>
                <span class="material-symbols-outlined text-gray-500">expand_more</span>
              </button>
              <!-- Profile Dropdown -->
              <div id="profileMenu" class="dropdown absolute top-full mt-2 right-0 w-48 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg shadow-lg z-20">
                <ul class="py-2">
                  <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-secondary dark:hover:bg-primary/20">
                      <span class="material-symbols-outlined text-base">person</span> الملف الشخصي
                    </a>
                  </li>
                  <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10">
                      <span class="material-symbols-outlined text-base">logout</span> تسجيل الخروج
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          
        </header>

  @yield('main')