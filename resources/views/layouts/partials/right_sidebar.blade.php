@php
  $isOffcanvas = $isOffcanvas ?? false;
  $r = request()->route()?->getName() ?? '';

  $u = auth()->user();

  // Roles (Spatie)
  $isSiteAdmin    = $u?->hasRole('admin_sitio') ?? false;
  $isCompanyAdmin = $u?->hasRole('admin_empresa') ?? false;
  $isBusiness     = $u?->hasRole('negocio') ?? false;
  $isEmployee     = $u?->hasRole('empleado') ?? false;

  // Prioridad: si es admin, ocultamos secciones duplicadas
  $showAdminSite    = $isSiteAdmin;
  $showAdminCompany = !$isSiteAdmin && $isCompanyAdmin;

  $showBusiness = !$showAdminSite && !$showAdminCompany && $isBusiness;
  $showEmployee = !$showAdminSite && !$showAdminCompany && $isEmployee;

  // Helpers
  $isActive = fn($name) => $r === $name;
  $starts   = fn($prefix) => str_starts_with($r, $prefix);

  // Auto-open de grupos (si alguna ruta del grupo está activa)
  $openGeneral  = $isActive('dashboard');

  $openEmployee = $showEmployee && (
      $starts('points.') ||
      $starts('redeems.manual.')
  );

  $openBusiness = $showBusiness && (
      $starts('redeems.')
  );

  $openAdminPoints = ($showAdminSite || $showAdminCompany) && (
      $starts('points.')
  );

  $openAdminAbm = ($showAdminSite || $showAdminCompany) && (
      $starts('abm.users.') ||
      $starts('abm.paises.') ||
      $starts('abm.provincias.') ||
      $starts('abm.localidades.') ||
      $starts('abm.point-references.') ||
      $starts('abm.companies.')
  );

  // IDs únicos por si el sidebar se renderiza 2 veces (desktop + offcanvas)
  $uid = $isOffcanvas ? 'oc' : 'sb';

  $collapseId = fn($key) => "collapse-{$uid}-{$key}";
@endphp



<nav class="sidebar-nav {{ $isOffcanvas ? 'is-offcanvas' : '' }} d-flex flex-column">

  {{-- Brand --}}
  <div class="sidebar-brand px-3 py-3">
    <div class="brand-title">
      <i class="bi bi-grid-1x2-fill me-2"></i>
      <span class="brand-text">Navegación</span>
    </div>
  </div>

  {{-- ====== GENERAL (siempre) ====== --}}
  <div class="sidebar-section px-3 pt-2">
    <button class="sidebar-section-title w-100 d-flex align-items-center justify-content-between"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $collapseId('general') }}"
            aria-expanded="{{ $openGeneral ? 'true' : 'false' }}"
            aria-controls="{{ $collapseId('general') }}">
      <span>General</span>
      <i class="bi bi-chevron-down small opacity-75"></i>
    </button>

    <div class="collapse {{ $openGeneral ? 'show' : '' }}" id="{{ $collapseId('general') }}">
      <a class="sidebar-link {{ $isActive('dashboard') ? 'active' : '' }}"
         href="{{ route('dashboard') }}"
         @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Dashboard" @endif>
        <i class="bi bi-speedometer2"></i>
        <span class="link-text">Dashboard</span>
      </a>
    </div>
  </div>

  {{-- ====== EMPLEADO ====== --}}
  @if($showEmployee)
    <div class="sidebar-section px-3 pt-3">
      <button class="sidebar-section-title w-100 d-flex align-items-center justify-content-between"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#{{ $collapseId('employee') }}"
              aria-expanded="{{ $openEmployee ? 'true' : 'false' }}"
              aria-controls="{{ $collapseId('employee') }}">
        <span>Empleado</span>
        <i class="bi bi-chevron-down small opacity-75"></i>
      </button>

      <div class="collapse {{ $openEmployee ? 'show' : '' }}" id="{{ $collapseId('employee') }}">
        <a class="sidebar-link {{ $starts('points.') ? 'active' : '' }}"
           href="{{ route('points.index') }}"
           @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Mis puntos" @endif>
          <i class="bi bi-star"></i>
          <span class="link-text">Mis puntos</span>
        </a>

        <a class="sidebar-link {{ $starts('redeems.manual.') ? 'active' : '' }}"
           href="{{ route('redeems.manual.index') }}"
           @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Consumo manual" @endif>
          <i class="bi bi-shop"></i>
          <span class="link-text">Consumo manual</span>
        </a>
      </div>
    </div>
  @endif

  {{-- ====== NEGOCIO ====== --}}
  @if($showBusiness)
    <div class="sidebar-section px-3 pt-3">
      <button class="sidebar-section-title w-100 d-flex align-items-center justify-content-between"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#{{ $collapseId('business') }}"
              aria-expanded="{{ $openBusiness ? 'true' : 'false' }}"
              aria-controls="{{ $collapseId('business') }}">
        <span>Negocio</span>
        <i class="bi bi-chevron-down small opacity-75"></i>
      </button>

      <div class="collapse {{ $openBusiness ? 'show' : '' }}" id="{{ $collapseId('business') }}">
        <a class="sidebar-link {{ $starts('redeems.') ? 'active' : '' }}"
           href="{{ route('redeems.create') }}"
           @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Consumir puntos" @endif>
          <i class="bi bi-qr-code-scan"></i>
          <span class="link-text">Consumir puntos</span>
        </a>
      </div>
    </div>
  @endif

  {{-- ====== ADMIN (EMPRESA o SITIO) ====== --}}
  @if($showAdminCompany || $showAdminSite)

    {{-- Encabezado admin (texto distinto según rol) --}}
    <div class="sidebar-section px-3 pt-3">
      <div class="sidebar-section-title">
        {{ $showAdminSite ? 'Administrador del sitio' : 'Administrador de empresa' }}
      </div>

      {{-- === Grupo: PUNTOS === --}}
      <button class="sidebar-section-title w-100 d-flex align-items-center justify-content-between mt-2"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#{{ $collapseId('admin_points') }}"
              aria-expanded="{{ $openAdminPoints ? 'true' : 'false' }}"
              aria-controls="{{ $collapseId('admin_points') }}">
        <span>Puntos</span>
        <i class="bi bi-chevron-down small opacity-75"></i>
      </button>

      <div class="collapse {{ $openAdminPoints ? 'show' : '' }}" id="{{ $collapseId('admin_points') }}">

        <a class="sidebar-link {{ $starts('points.') && !$starts('points.import.') ? 'active' : '' }}"
           href="{{ route('points.index') }}"
           @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Gestión de puntos" @endif>
          <i class="bi bi-trophy"></i>
          <span class="link-text">Gestión de puntos</span>
        </a>

        <a class="sidebar-link {{ ($r === 'points.resumen' || $r === 'points.summary') ? 'active' : '' }}"
           href="{{ route('points.resumen') }}"
           @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Resumen" @endif>
          <i class="bi bi-bar-chart"></i>
          <span class="link-text">Resumen</span>
        </a>

        <a class="sidebar-link {{ $starts('points.import.') ? 'active' : '' }}"
           href="{{ route('points.import.create') }}"
           @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Importación" @endif>
          <i class="bi bi-upload"></i>
          <span class="link-text">Importación</span>
        </a>
      </div>

      {{-- === Grupo: ABM / Configuración === --}}
      <button class="sidebar-section-title w-100 d-flex align-items-center justify-content-between mt-3"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#{{ $collapseId('admin_abm') }}"
              aria-expanded="{{ $openAdminAbm ? 'true' : 'false' }}"
              aria-controls="{{ $collapseId('admin_abm') }}">
        <span>Configuración</span>
        <i class="bi bi-chevron-down small opacity-75"></i>
      </button>

      <div class="collapse {{ $openAdminAbm ? 'show' : '' }}" id="{{ $collapseId('admin_abm') }}">

        {{-- Solo admin_sitio --}}
        @if($showAdminSite)
          <a class="sidebar-link {{ $starts('abm.companies.') ? 'active' : '' }}"
             href="{{ route('abm.companies.index') }}"
             @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Empresas" @endif>
            <i class="bi bi-buildings"></i>
            <span class="link-text">Empresas</span>
          </a>
        @endif

        <a class="sidebar-link {{ $starts('abm.users.') ? 'active' : '' }}"
           href="{{ route('abm.users.index') }}"
           @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Usuarios" @endif>
          <i class="bi bi-people"></i>
          <span class="link-text">Usuarios</span>
        </a>

        <a class="sidebar-link {{ $starts('abm.point-references.') ? 'active' : '' }}"
           href="{{ route('abm.point-references.index') }}"
           @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Referencias de puntos" @endif>
          <i class="bi bi-tags"></i>
          <span class="link-text">Referencias</span>
        </a>

        <a class="sidebar-link {{ $starts('abm.paises.') ? 'active' : '' }}"
           href="{{ route('abm.paises.index') }}">
          <i class="bi bi-globe"></i>
          <span class="link-text">Países</span>
        </a>

        <a class="sidebar-link {{ $starts('abm.provincias.') ? 'active' : '' }}"
           href="{{ route('abm.provincias.index') }}">
          <i class="bi bi-map"></i>
          <span class="link-text">Provincias</span>
        </a>

        <a class="sidebar-link {{ $starts('abm.localidades.') ? 'active' : '' }}"
           href="{{ route('abm.localidades.index') }}">
          <i class="bi bi-geo-alt"></i>
          <span class="link-text">Localidades</span>
        </a>
      </div>
    </div>
  @endif

  {{-- Footer --}}
  <div class="sidebar-footer mt-auto px-3 py-3">
    <div class="sidebar-user"
         @if(!$isOffcanvas) data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="{{ $u->name ?? '' }}" @endif>
      <div class="user-dot"></div>
      <div class="user-info">
        <div class="user-name">{{ $u->name ?? '' }}</div>
        <div class="user-email">{{ $u->email ?? '' }}</div>
      </div>
    </div>

    {{-- Company (no mostrar para admin_sitio) --}}
@if(!$isSiteAdmin)
  <div class="user-company mt-1">
    <span class="badge text-bg-light">
      <i class="bi bi-buildings me-1"></i>
      {{ $u?->company?->name ?? 'Sin empresa' }}
    </span>
  </div>
@endif

    <form method="POST" action="{{ route('logout') }}" class="mt-2">
      @csrf
      <button type="submit"
              class="sidebar-link w-100 text-start"
              style="border:0; background:transparent;">
        <i class="bi bi-box-arrow-right"></i>
        <span class="link-text">Cerrar sesión</span>
      </button>
    </form>
  </div>
</nav>
