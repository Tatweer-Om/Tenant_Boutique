 <!-- إغلاق أي div مفتوح من المحتوى -->

<!-- الفوتر -->
<footer class="relative z-40 bg-white py-4 border-t border-pink-100">
  <div class="w-full px-4 sm:px-6 text-center text-sm text-gray-500">
    <p>© جميع الحقوق محفوظة – صُنع بحُب 
      <span class="text-pink-500 text-lg align-middle">❤</span> 
      بواسطة <span class="font-semibold text-[var(--primary-color)]">تطوير</span>
    </p>
  </div>
</footer>

<!-- Bottom Navigation for Mobile -->
<div class="mobile-nav fixed bottom-0 left-0 w-full z-50 bg-white border-t border-pink-100 shadow-md md:hidden flex justify-around items-center py-2">
  <a href="/dress-rental/dash.php" class="flex flex-col items-center text-pink-500">
    <span class="material-symbols-outlined text-xl">dashboard</span>
    <span class="text-xs">الرئيسية</span>
  </a>

  <a href="{{ route($apiVersion.'.booking.index') }}" class="flex flex-col items-center text-gray-500">
    <span class="material-symbols-outlined text-xl">calendar_month</span>
    <span class="text-xs">{{ trans('messages.booking_lang', [], session('locale')) }}</span>
  </a>

  <!-- زر القائمة الجانبية (منتصف البار) -->
  <button @click="sidebarOpen = !sidebarOpen"
          class="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-pink-500 text-white rounded-full w-14 h-14 shadow-lg flex items-center justify-center border-4 border-white z-50">
    <span class="material-symbols-outlined text-3xl">menu</span>
  </button>

  <a href="{{ route($apiVersion.'.dresses.index') }}" class="flex flex-col items-center text-gray-500">
    <span class="material-symbols-outlined text-xl">styler</span>
    <span class="text-xs">{{ trans('messages.dress_lang', [], session('locale')) }}</span>
  </a>

  <a href="{{ route('tlogout') }}" class="flex flex-col items-center text-gray-500">
    <span class="material-symbols-outlined text-xl">logout</span>
    <span class="text-xs">{{ trans('messages.logout_lang', [], session('locale')) }}</span>
  </a>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
 

<script src="{{ url('assets/vendors/toastr/js/toastr.min.js')}}"></script>
<script src="{{ url('assets/js/plugins-init/toastr-init.js')}}"></script>

<script src="{{ url('assets/vendors/sweetalert2/dist/sweetalert2.min.js')}}"></script>
<script src="{{  url('assets/js/plugins-init/sweetalert.init.js')}}"></script>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  window.crsftoken = {
    crsfToken: '{{ csrf_token() }}',
  };
</script>
<script src="{{ url('assets/modules/custom.js') }}"></script>
@php
    $lastSegment = request()->segment(1); // gets the first segment after domain
@endphp
@if ($lastSegment == 'dashboards')
  <script src="{{ url('assets/modules/dashboard.js') }}"></script>
@elseif ($lastSegment == 'colors')
  <script src="{{ url('assets/modules/color.js') }}"></script>
@elseif ($lastSegment == 'sizes')
  <script src="{{ url('assets/modules/size.js') }}"></script>
@elseif ($lastSegment == 'laundrys')
  <script src="{{ url('assets/modules/laundry.js') }}"></script>
@elseif ($lastSegment == 'customers')
  <script src="{{ url('assets/modules/customer.js') }}"></script>
@elseif ($lastSegment == 'settings')
  <script src="{{ url('assets/modules/setting.js') }}"></script>
@elseif ($lastSegment == 'dresscategorys')
  <script src="{{ url('assets/modules/dresscategorys.js') }}"></script>
@elseif ($lastSegment == 'dresses')
  <script src="{{ url('assets/modules/dress.js') }}"></script>
@elseif ($lastSegment == 'dressHistory')
  <script src="{{ url('assets/modules/dress_history.js') }}"></script>
@elseif ($lastSegment == 'users')
  <script src="{{ url('assets/modules/user.js') }}"></script>
@elseif ($lastSegment == 'expensecategorys')
  <script src="{{ url('assets/modules/expense_category.js') }}"></script>
@elseif ($lastSegment == 'expense')
  <script src="{{ url('assets/modules/expense.js') }}"></script>
@elseif ($lastSegment == 'add_dress')
  <script src="{{ url('assets/modules/dress.js') }}"></script>
@elseif ($lastSegment == 'accessories')
  <script src="{{ url('assets/modules/accessories.js') }}"></script>
@elseif ($lastSegment == 'bookings')
  <script src="{{ url('assets/modules/booking.js') }}"></script>
@elseif ($lastSegment == 'add_booking')
  <script src="{{ url('assets/modules/booking.js') }}"></script>
@elseif ($lastSegment == 'update_booking')
  <script src="{{ url('assets/modules/booking.js') }}"></script>
@elseif ($lastSegment == 'laundryMaintenance')
  <script src="{{ url('assets/modules/laundry_maintenance.js') }}"></script>
@elseif ($lastSegment == 'bookingDelivery')
  <script src="{{ url('assets/modules/booking_delivery.js') }}"></script>
@elseif ($lastSegment == 'bookingReceive')
  <script src="{{ url('assets/modules/booking_receive.js') }}"></script>
@elseif ($lastSegment == 'getBookingReceive')
  <script src="{{ url('assets/modules/booking_receive.js') }}"></script>
@elseif ($lastSegment == 'bookingReport')
  <script src="{{ url('assets/modules/report.js') }}"></script>
@endif

 
</body>
</html>
