@include('tenant_layouts.headerfooter.header')

<main class="container py-4">
    @yield('content')
</main>

{{-- Global Footer --}}
@include('tenant_layouts.headerfooter.footer')

