<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','MisPuntos')</title>

  @vite(['resources/css/app.css','resources/js/app.js'])
  @stack('styles')
</head>
<body>

@php
  // Si tenés "company activa" en sesión o auth, podés traerla acá.
  // Ejemplo simple:
  $activeCompany = session('active_company'); // opcional
  // o $activeCompany = auth()->user()?->company ?? null; (si existe relación)
@endphp

<div class="app-shell" id="appShell">

  {{-- ===== Topbar ===== --}}
  <header class="topbar">
    <div class="topbar-left">
      {{-- Logo de Company (si existe) --}}
      @if($activeCompany && !empty($activeCompany->logo))
        <img class="company-logo"
             src="{{ asset('storage/'.$activeCompany->logo) }}"
             alt="Company Logo">
      @else
        <div class="company-logo-fallback">
            <img
            src="{{ asset('logos/ImgLogoCircular-SF.png') }}"
            alt="Logo {{ config('app.name') }}"
            width="36"
            height="36"
            style="border-radius:12px; object-fit:cover; box-shadow: 0 6px 16px rgba(0,0,0,.12);"
        />

        </div>
      @endif

      <div class="topbar-title">
        <div class="app-name">MisPuntos</div>
        @if($activeCompany)
          <div class="company-name">{{ $activeCompany->name ?? '' }}</div>
        @endif
      </div>
    </div>

    <div class="topbar-actions">
      {{-- Hamburguesa (solo mobile/tablet) --}}
      <button class="btn btn-light btn-sm d-lg-none"
              type="button"
              data-bs-toggle="offcanvas"
              data-bs-target="#rightSidebarOffcanvas"
              aria-controls="rightSidebarOffcanvas">
        <i class="bi bi-list"></i>
      </button>

      {{-- Toggle desktop (colapsar sidebar) --}}
      <button class="btn btn-light btn-sm d-none d-lg-inline-flex"
              type="button"
              id="sidebarToggleBtn"
              title="Toggle sidebar">
        <i class="bi bi-layout-sidebar-inset-reverse"></i>
      </button>
    </div>
  </header>

  {{-- ===== Content ===== --}}
  <main class="app-content">
    <div class="container-fluid py-3">
      @yield('content')
    </div>
  </main>

  {{-- ===== Right Sidebar Desktop ===== --}}
  <aside class="right-sidebar d-none d-lg-flex" id="rightSidebar">
    @include('layouts.partials.right_sidebar')
  </aside>

  {{-- ===== Right Sidebar Mobile/Tablet (Offcanvas) ===== --}}
  <div class="offcanvas offcanvas-end sidebar-offcanvas d-lg-none"
       tabindex="-1"
       id="rightSidebarOffcanvas"
       aria-labelledby="rightSidebarOffcanvasLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="rightSidebarOffcanvasLabel">Menu</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
      @include('layouts.partials.right_sidebar', ['isOffcanvas' => true])
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')

<script>
  (function() {
    const shell = document.getElementById('appShell');
    const btn = document.getElementById('sidebarToggleBtn');
    const KEY = 'mispuntos_sidebar_collapsed';

    function isDesktop() {
      return window.matchMedia('(min-width: 992px)').matches;
    }

    function getLinksForTooltip(){
      // Solo links del sidebar desktop (no el offcanvas)
      const desktopSidebar = document.getElementById('rightSidebar');
      if (!desktopSidebar) return [];
      return Array.from(desktopSidebar.querySelectorAll('[data-bs-toggle="tooltip"]'));
    }

    function initTooltipsIfNeeded(){
      if (!isDesktop()) return;

      const collapsed = shell.classList.contains('sidebar-collapsed');
      const links = getLinksForTooltip();

      // Si NO está colapsado, destruir tooltips si existen
      if (!collapsed) {
        links.forEach(el => {
          const instance = bootstrap.Tooltip.getInstance(el);
          if (instance) instance.dispose();
        });
        return;
      }

      // Si está colapsado, crear tooltips (si no existen)
      links.forEach(el => {
        if (!bootstrap.Tooltip.getInstance(el)) {
          new bootstrap.Tooltip(el, { trigger: 'hover focus' });
        }
      });
    }

    // Estado inicial desde localStorage
    const initial = localStorage.getItem(KEY) === '1';
    if (initial) shell.classList.add('sidebar-collapsed');

    // Primera inicialización (por si arranca colapsado)
    if (window.bootstrap) initTooltipsIfNeeded();

    // Toggle
    if (btn) {
      btn.addEventListener('click', () => {
        shell.classList.toggle('sidebar-collapsed');
        localStorage.setItem(KEY, shell.classList.contains('sidebar-collapsed') ? '1' : '0');

        if (window.bootstrap) initTooltipsIfNeeded();
      });
    }

    // Si cambia el breakpoint (resize), re-evaluar
    window.addEventListener('resize', () => {
      if (window.bootstrap) initTooltipsIfNeeded();
    });
  })();
</script>


</body>
</html>
