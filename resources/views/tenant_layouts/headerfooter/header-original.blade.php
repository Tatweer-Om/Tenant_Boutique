<!DOCTYPE html>
<html dir="rtl" lang="ar"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>لوحة تحكم Bridal Bliss</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
<link rel="stylesheet" href="/dress-rental/output.css">

<!-- THIS JS FOR SIDE MENUE TO OPEN SUB MNUE -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style type="text/tailwindcss">
        :root {
            --primary-color: #D63384;
            --secondary-color: #F8D7DA;
            --background-color: #FFF9FB;
            --text-color: #495057;
            --muted-text-color: #6c757d;
            --highlight-color: #FBCFE8;
        }
        body {
            font-family: "Cairo", sans-serif;
            color: var(--text-color);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24
        }
        .shadow-custom {
            box-shadow: 0 4px 14px 0 rgba(214, 51, 132, 0.08);
        }
    </style>
</head>
<body class="bg-background-color">
<div class="flex min-h-screen">
<aside class="w-64 bg-white flex flex-col p-6 space-y-6">
<div class="text-3xl font-bold text-center" style="color: var(--primary-color)">Bridal Bliss</div>
<nav class="flex flex-col gap-4 mt-8 flex-grow">
<a class="flex items-center gap-4 px-5 py-3 rounded-xl bg-highlight-color font-bold" href="dash.php" style="color: var(--primary-color);">
<span class="material-symbols-outlined">dashboard</span>
<span>الرئيسية</span>
</a>
<a class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-highlight-color/50 transition-colors text-muted-text-color hover:text-primary-color" href="add_dress.php">
<span class="material-symbols-outlined">styler</span>
<span>إضافة فستان</span>
</a>

<div x-data="{ open: false }">
<button @click="open = !open" class="flex items-center justify-between w-full gap-4 px-5 py-3 rounded-xl hover:bg-highlight-color/50 transition-colors text-muted-text-color hover:text-primary-color">
<div class="flex items-center gap-4">
<span class="material-symbols-outlined">receipt_long</span>
<span> الخيارات</span>
</div>
<span :class="{ 'rotate-180': open }" class="material-symbols-outlined transition-transform">expand_more</span>
</button>
<div class="mt-2 pr-8 space-y-2" x-cloak="" x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0 transform -translate-y-2" x-transition:leave-start="opacity-100 transform translate-y-0">
<a class="block px-4 py-2 text-sm rounded-lg text-muted-text-color hover:bg-highlight-color/50 hover:text-primary-color" href="view_colors.php">الألوان</a>
<a class="block px-4 py-2 text-sm rounded-lg text-muted-text-color hover:bg-highlight-color/50 hover:text-primary-color" href="view_sizes.php">المقاسات </a>
</div>
</div>

<a class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-highlight-color/50 transition-colors text-muted-text-color hover:text-primary-color" href="dresses.php">
<span class="material-symbols-outlined">styler</span>
<span>الفساتين</span>
</a>
<a class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-highlight-color/50 transition-colors text-muted-text-color hover:text-primary-color" href="#">
<span class="material-symbols-outlined">calendar_month</span>
<span>الحجوزات</span>
</a>
<a class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-highlight-color/50 transition-colors text-muted-text-color hover:text-primary-color" href="#">
<span class="material-symbols-outlined">groups</span>
<span>العملاء</span>
</a>
<a class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-highlight-color/50 transition-colors text-muted-text-color hover:text-primary-color" href="#">
<span class="material-symbols-outlined">receipt_long</span>
<span>الفواتير والمدفوعات</span>
</a>
<a class="flex items-center gap-4 px-5 py-3 rounded-xl hover:bg-highlight-color/50 transition-colors text-muted-text-color hover:text-primary-color" href="#">
<span class="material-symbols-outlined">settings</span>
<span>الإعدادات</span>
</a>
</nav>
<div class="mt-auto">
<a class="flex items-center gap-4 px-5 py-3 rounded-xl text-red-500 hover:bg-red-50 transition-colors" href="#">
<span class="material-symbols-outlined">logout</span>
<span>تسجيل الخروج</span>
</a>
</div>
</aside>
<main class="flex-1 px-6 py-8 w-full">
<!-- <div class="mx-auto max-w-7xl"> -->
<header class="flex items-center justify-between bg-white/80 backdrop-blur-sm px-8 py-4 border-b border-pink-100">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-yellow-400 text-3xl">wb_sunny</span>
<h1 class="text-xl font-semibold text-gray-700">صباح الخير، ليلى!</h1>
</div>
<div class="flex items-center gap-6">
<div class="relative group">
<button class="relative text-muted-text-color hover:text-primary-color transition-colors" style="color: var(--muted-text-color)">
<span class="material-symbols-outlined text-2xl">notifications</span>
<span class="absolute top-0.5 right-0.5 h-2.5 w-2.5 bg-primary-color rounded-full border-2 border-white"></span>
</button>
<div class="absolute top-full right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-pink-100 p-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
<div class="flex justify-between items-center mb-3">
<h4 class="font-bold text-gray-800">الإشعارات</h4>
<a class="text-sm font-medium text-primary-color hover:underline" href="#">عرض الكل</a>
</div>
<div class="flex flex-col gap-3">
<div class="flex items-start gap-3 p-2 rounded-lg hover:bg-pink-50/50">
<div class="w-10 h-10 bg-secondary-color rounded-full flex items-center justify-center flex-shrink-0" style="background-color: var(--secondary-color)">
<span class="material-symbols-outlined text-primary-color" style="color: var(--primary-color)">check_circle</span>
</div>
<div>
<p class="font-semibold text-sm text-gray-700">تم تأكيد حجز جديد لليلى عبد الرحمن.</p>
<p class="text-xs text-muted-text-color">قبل ساعتين</p>
</div>
</div>
<div class="flex items-start gap-3 p-2 rounded-lg hover:bg-pink-50/50">
<div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
<span class="material-symbols-outlined" style="color: #c0392b">payment</span>
</div>
<div>
<p class="font-semibold text-sm text-gray-700">تنبيه دفع متأخر من سارة السيد.</p>
<p class="text-xs text-muted-text-color">أمس</p>
</div>
</div>
<div class="flex items-start gap-3 p-2 rounded-lg hover:bg-pink-50/50">
<div class="w-10 h-10 bg-secondary-color rounded-full flex items-center justify-center flex-shrink-0" style="background-color: var(--secondary-color)">
<span class="material-symbols-outlined text-primary-color" style="color: var(--primary-color)">calendar_add_on</span>
</div>
<div>
<p class="font-semibold text-sm text-gray-700">تذكير: موعد تجربة فستان لنورة المحمد غداً.</p>
<p class="text-xs text-muted-text-color">3 أيام مضت</p>
</div>
</div>
</div>
</div>
</div>
<div class="flex items-center gap-2 text-base">
<button class="font-bold" style="color: var(--primary-color)">عربي</button>
<span class="text-gray-300">/</span>
<button class="text-muted-text-color hover:text-primary-color transition-colors" style="color: var(--muted-text-color)">EN</button>
</div>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12 border-2 border-pink-100" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuARfZ0takW-0qzMm7tWJ8EWddGUqithwRxS4nl38qO1Mkv1NWQVtyECBk5vGoA1HsV0fWUyqz6e8OBdrBWuzGurrNscRpmz2Go75KfAETWSbM0gh-zLcBfZjaV0OeHCCK03MMpu1qUFQDGSPtMIpoxhgdNNDdNZo2jUlwBxFCtXP844M5ZDMaZDwRjAr8m3wiY_125FaJs_N2qvxcwlGE7TxZihc6dJMdVBSNuGHQb3lMsjtpIrnnT8HuCpG6ce6OkpvYpZrNZme9k");'></div>
</div>
</header>