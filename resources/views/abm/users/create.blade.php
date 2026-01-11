@extends('layouts.app')

@section('title','New User')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0">
      <i class="bi bi-person-plus"></i> New User
    </h3>
    <div class="ms-auto">
      <a href="{{ route('abm.users.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
  </div>

  <div class="card-body">
    <form method="POST"
          action="{{ route('abm.users.store') }}"
          class="row g-3"
          enctype="multipart/form-data">
      @csrf

      {{-- =========================
           Datos básicos
      ========================== --}}
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}"
               required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input name="email"
               type="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}"
               required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">CUIL</label>
        <input name="cuil"
               class="form-control @error('cuil') is-invalid @enderror"
               value="{{ old('cuil') }}"
               maxlength="13">
        @error('cuil') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Dirección</label>
        <input name="direccion"
               class="form-control @error('direccion') is-invalid @enderror"
               value="{{ old('direccion') }}">
        @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- =========================
           Ubicación (País/Prov/Loc)
           (simple por ahora, sin AJAX)
      ========================== --}}
      <div class="col-md-4">
        <label class="form-label">País</label>
        <select name="pais_id" class="form-select @error('pais_id') is-invalid @enderror">
          <option value="">—</option>
          @foreach($paises as $p)
            <option value="{{ $p->id }}" @selected((string)old('pais_id')===(string)$p->id)>
              {{ $p->nombre }}
            </option>
          @endforeach
        </select>
        @error('pais_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Provincia</label>
        <select name="provincia_id" class="form-select @error('provincia_id') is-invalid @enderror">
          <option value="">—</option>
          @foreach($provincias as $prov)
            <option value="{{ $prov->id }}" @selected((string)old('provincia_id')===(string)$prov->id)>
              {{ $prov->nombre }}
            </option>
          @endforeach
        </select>
        @error('provincia_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Localidad</label>
        <select name="localidad_id" class="form-select @error('localidad_id') is-invalid @enderror">
          <option value="">—</option>
          @foreach($localidades as $loc)
            <option value="{{ $loc->id }}" @selected((string)old('localidad_id')===(string)$loc->id)>
              {{ $loc->nombre }} @if($loc->cp) ({{ $loc->cp }}) @endif
            </option>
          @endforeach
        </select>
        @error('localidad_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- =========================
           Company + Estado
      ========================== --}}
      @php
        $authUser       = auth()->user();
        $isSiteAdmin    = $authUser && $authUser->hasRole('admin_sitio');
        $isCompanyAdmin = $authUser && $authUser->hasRole('admin_empresa');
      @endphp

      {{-- Empresa: solo admin_sitio la cambia --}}
      @if($isSiteAdmin)
        <div class="col-md-6">
          <label class="form-label">Company</label>
          <select name="company_id" class="form-select @error('company_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach($companies as $c)
              <option value="{{ $c->id }}" @selected((string)old('company_id')===(string)$c->id)>
                {{ $c->name }}
              </option>
            @endforeach
          </select>
          @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      @else
        {{-- si NO es admin_sitio, que vaya fijo por backend (no confiar en hidden) --}}
        <input type="hidden" name="company_id" value="{{ old('company_id') }}">
      @endif

      <div class="col-md-3">
        <label class="form-label">Activo</label>
        <select name="activo" class="form-select @error('activo') is-invalid @enderror">
          <option value="1" @selected(old('activo','1')=='1')>Sí</option>
          <option value="0" @selected(old('activo')=='0')>No</option>
        </select>
        @error('activo') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-3">
        <label class="form-label">Fecha nacimiento</label>
        <input type="date"
               name="fecha_nacimiento"
               class="form-control @error('fecha_nacimiento') is-invalid @enderror"
               value="{{ old('fecha_nacimiento') }}">
        @error('fecha_nacimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- =========================
           Imagen
      ========================== --}}
      <div class="col-md-6">
        <label class="form-label">Imagen</label>
        <input type="file"
               name="imagen"
               class="form-control @error('imagen') is-invalid @enderror"
               accept="image/*">
        @error('imagen') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">JPG/PNG/WebP recomendado.</div>
      </div>

      {{-- =========================
           Password
      ========================== --}}
      <div class="col-md-6">
        <label class="form-label">Password</label>
        <input name="password"
               type="password"
               class="form-control @error('password') is-invalid @enderror"
               required>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Confirm Password</label>
        <input name="password_confirmation" type="password" class="form-control" required>
      </div>

     {{-- =========================
     ROLES (checkbox como pediste)
========================== --}}
@php
    // Quien puede ver/editar roles
    $canEditRoles = $isSiteAdmin || $isCompanyAdmin;

    // $roles debe venir como array de strings (nombres)
    $roleList = $roles instanceof \Illuminate\Support\Collection ? $roles->all() : (array)$roles;

    $selectedRoles = old('roles', []);

    // VALOR POR DEFECTO: 'empleado' para admin_empresa
    if (!$isSiteAdmin && $isCompanyAdmin && empty($selectedRoles)) {
        $selectedRoles = ['empleado']; // Rol por defecto
    }
    // VALOR POR DEFECTO GENERAL: Si no hay selección y es admin_sitio
    elseif ($isSiteAdmin && empty($selectedRoles)) {
        // Puedes elegir qué rol por defecto para admin_sitio
        $selectedRoles = ['empleado']; // o el que prefieras
    }

    $roleLabels = [
        'admin_sitio'    => 'Administrador del sitio',
        'admin_empresa'  => 'Administrador de compañía',
        'negocio'        => 'Negocio / empleado interno',
        'empleado'       => 'Empleado',
    ];

    $availableRoles = $roleList;

    if ($isCompanyAdmin && !$isSiteAdmin) {
        $availableRoles = array_values(array_filter($roleList, fn($rname) => $rname !== 'admin_sitio'));
    }
@endphp

      <div class="col-12">
        <label class="form-label">Roles</label>

        @if($canEditRoles)
          <div class="d-flex flex-wrap gap-2">
            @foreach($availableRoles as $rname)
              @php $label = $roleLabels[$rname] ?? $rname; @endphp
              <div class="form-check me-3">
                <input class="form-check-input"
                       type="checkbox"
                       name="roles[]"
                       id="role_{{ $rname }}"
                       value="{{ $rname }}"
                       @checked(in_array($rname, $selectedRoles))>
                <label class="form-check-label" for="role_{{ $rname }}">
                  {{ $label }}
                </label>
              </div>
            @endforeach
          </div>
          @error('roles') <div class="text-danger small">{{ $message }}</div> @enderror
        @else
          @foreach($selectedRoles as $rname)
            <input type="hidden" name="roles[]" value="{{ $rname }}">
          @endforeach
        @endif
      </div>

      <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('abm.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary btn-mat"><i class="bi bi-check2"></i> Create</button>
      </div>
    </form>
  </div>
</div>
@endsection
