@php
  $isOffcanvas = $isOffcanvas ?? false;
  $r = request()->route()?->getName() ?? '';

  $u = auth()->user();

  // Roles (Spatie)
  $isSiteAdmin    = $u?->hasRole('admin_sitio') ?? false;
  $isCompanyAdmin = $u?->hasRole('admin_empresa') ?? false;
  $isBusiness     = $u?->hasRole('negocio') ?? false;
  $isEmployee     = $u?->hasRole('empleado') ?? false;

  // Prioridad: si es admin, ocultamos secciones que generan duplicado
  $showAdminSite    = $isSiteAdmin;
  $showAdminCompany = !$isSiteAdmin && $isCompanyAdmin;

  $showBusiness = !$showAdminSite && !$showAdminCompany && $isBusiness;
  $showEmployee = !$showAdminSite && !$showAdminCompany && $isEmployee;
@endphp

<nav class="sidebar-nav {{ $isOffcanvas ? 'is-offcanvas' : '' }} d-flex flex-column">
  <div class="sidebar-brand px-3 py-3">
    <div class="brand-title">
      <i class="bi bi-grid-1x2-fill me-2"></i>
      <span class="brand-text">Navegación</span>
    </div>
  </div>

  {{-- ========== General (todos) ========== --}}
  <div class="sidebar-section px-3 pt-2">
    <div class="sidebar-section-title">General</div>

    <a class="sidebar-link {{ $r === 'dashboard' ? 'active' : '' }}"
       href="{{ route('dashboard') }}"
       data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Dashboard">
      <i class="bi bi-speedometer2"></i>
      <span class="link-text">Dashboard</span>
    </a>
  </div>

  {{-- ===========================
       EMPLEADO (si NO es admin)
       =========================== --}}
  @if($showEmployee)
    <div class="sidebar-section px-3 pt-3">
      <div class="sidebar-section-title">Empleado</div>

      <a class="sidebar-link {{ str_starts_with($r, 'points.') ? 'active' : '' }}"
         href="{{ route('points.index') }}"
         data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Mis puntos">
        <i class="bi bi-star"></i>
        <span class="link-text">Mis puntos</span>
      </a>

      {{-- opcional: consumo manual empleado -> negocio
      <a class="sidebar-link {{ str_starts_with($r, 'redeems.manual.') ? 'active' : '' }}"
         href="{{ route('redeems.manual.create') }}">
        <i class="bi bi-shop"></i>
        <span class="link-text">Consumo manual</span>
      </a>
      --}}
    </div>
  @endif

  {{-- ===========================
       NEGOCIO (si NO es admin)
       =========================== --}}
  @if($showBusiness)
    <div class="sidebar-section px-3 pt-3">
      <div class="sidebar-section-title">Negocio</div>

      <a class="sidebar-link {{ str_starts_with($r, 'redeems.') ? 'active' : '' }}"
         href="{{ route('redeems.create') }}"
         data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Consumir puntos">
        <i class="bi bi-qr-code-scan"></i>
        <span class="link-text">Consumir puntos</span>
      </a>
    </div>
  @endif

  {{-- ===========================
       ADMIN EMPRESA (solo si NO es admin_sitio)
       =========================== --}}
  @if($showAdminCompany)
    <div class="sidebar-section px-3 pt-3">
      <div class="sidebar-section-title">Administrador de empresa</div>

      <a class="sidebar-link {{ str_starts_with($r, 'points.') && !str_starts_with($r, 'points.import.') ? 'active' : '' }}"
         href="{{ route('points.index') }}"
         data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Gestión de puntos">
        <i class="bi bi-trophy"></i>
        <span class="link-text">Gestión de puntos</span>
      </a>

      <a class="sidebar-link {{ $r === 'points.resumen' || $r === 'points.summary' ? 'active' : '' }}"
         href="{{ route('points.resumen') }}"
         data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Resumen">
        <i class="bi bi-bar-chart"></i>
        <span class="link-text">Resumen de puntos</span>
      </a>

      <a class="sidebar-link {{ str_starts_with($r, 'points.import.') ? 'active' : '' }}"
         href="{{ route('points.import.create') }}"
         data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Importación">
        <i class="bi bi-upload"></i>
        <span class="link-text">Importación</span>
      </a>

      <div class="mt-2"></div>

      <a class="sidebar-link {{ str_starts_with($r, 'abm.users.') ? 'active' : '' }}"
         href="{{ route('abm.users.index') }}"
         data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Usuarios">
        <i class="bi bi-people"></i>
        <span class="link-text">Usuarios</span>
      </a>

      <a class="sidebar-link {{ str_starts_with($r, 'abm.paises.') ? 'active' : '' }}"
         href="{{ route('abm.paises.index') }}">
        <i class="bi bi-globe"></i>
        <span class="link-text">Países</span>
      </a>

      <a class="sidebar-link {{ str_starts_with($r, 'abm.provincias.') ? 'active' : '' }}"
         href="{{ route('abm.provincias.index') }}">
        <i class="bi bi-map"></i>
        <span class="link-text">Provincias</span>
      </a>

      <a class="sidebar-link {{ str_starts_with($r, 'abm.localidades.') ? 'active' : '' }}"
         href="{{ route('abm.localidades.index') }}">
        <i class="bi bi-geo-alt"></i>
        <span class="link-text">Localidades</span>
      </a>
    </div>
  @endif

  {{-- ===========================
       ADMIN SITIO (máxima prioridad)
       =========================== --}}
  @if($showAdminSite)
    <div class="sidebar-section px-3 pt-3">
      <div class="sidebar-section-title">Administrador del sitio</div>

      <a class="sidebar-link {{ str_starts_with($r, 'points.') && !str_starts_with($r, 'points.import.') ? 'active' : '' }}"
         href="{{ route('points.index') }}">
        <i class="bi bi-trophy"></i>
        <span class="link-text">Gestión de puntos</span>
      </a>

      <a class="sidebar-link {{ $r === 'points.resumen' || $r === 'points.summary' ? 'active' : '' }}"
         href="{{ route('points.resumen') }}">
        <i class="bi bi-bar-chart"></i>
        <span class="link-text">Resumen de puntos</span>
      </a>

      <a class="sidebar-link {{ str_starts_with($r, 'points.import.') ? 'active' : '' }}"
         href="{{ route('points.import.create') }}">
        <i class="bi bi-upload"></i>
        <span class="link-text">Importación</span>
      </a>

      <div class="mt-2"></div>

      <a class="sidebar-link {{ str_starts_with($r, 'abm.companies.') ? 'active' : '' }}"
         href="{{ route('abm.companies.index') }}">
        <i class="bi bi-buildings"></i>
        <span class="link-text">Empresas</span>
      </a>

      <a class="sidebar-link {{ str_starts_with($r, 'abm.users.') ? 'active' : '' }}"
         href="{{ route('abm.users.index') }}">
        <i class="bi bi-people"></i>
        <span class="link-text">Usuarios</span>
      </a>

      <a class="sidebar-link {{ str_starts_with($r, 'abm.paises.') ? 'active' : '' }}"
         href="{{ route('abm.paises.index') }}">
        <i class="bi bi-globe"></i>
        <span class="link-text">Países</span>
      </a>

      <a class="sidebar-link {{ str_starts_with($r, 'abm.provincias.') ? 'active' : '' }}"
         href="{{ route('abm.provincias.index') }}">
        <i class="bi bi-map"></i>
        <span class="link-text">Provincias</span>
      </a>

      <a class="sidebar-link {{ str_starts_with($r, 'abm.localidades.') ? 'active' : '' }}"
         href="{{ route('abm.localidades.index') }}">
        <i class="bi bi-geo-alt"></i>
        <span class="link-text">Localidades</span>
      </a>

      {{-- Si querés, dejalo solo para pruebas de admin_sitio --}}
      <a class="sidebar-link {{ str_starts_with($r, 'redeems.') ? 'active' : '' }}"
         href="{{ route('redeems.create') }}">
        <i class="bi bi-qr-code-scan"></i>
        <span class="link-text">Consumir puntos (test)</span>
      </a>
    </div>
  @endif

  {{-- Footer --}}
  <div class="sidebar-footer mt-auto px-3 py-3">
    <div class="sidebar-user"
         data-bs-toggle="tooltip"
         data-bs-placement="left"
         data-bs-title="{{ $u->name ?? '' }}">
      <div class="user-dot"></div>
      <div class="user-info">
        <div class="user-name">{{ $u->name ?? '' }}</div>
        <div class="user-email">{{ $u->email ?? '' }}</div>
      </div>
    </div>

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
