@extends('layouts.app')

@section('title','Edit User - '.$user->name)

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0">
      <i class="bi bi-person-gear"></i> Edit User
    </h3>
    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('abm.users.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
      <a href="{{ route('abm.users.show', $user) }}" class="btn btn-outline-secondary btn-sm">View</a>
    </div>
  </div>

  <div class="card-body">
    <form method="POST"
          action="{{ route('abm.users.update', $user) }}"
          class="row g-3"
          enctype="multipart/form-data">
      @csrf
      @method('PUT')

      {{-- =========================
           Datos básicos
      ========================== --}}
      <div class="col-md-6">
        <label class="form-label">Name</label>
        <input name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $user->name) }}"
               required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input name="email"
               type="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $user->email) }}"
               required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">CUIL</label>
        <input name="cuil"
               class="form-control @error('cuil') is-invalid @enderror"
               value="{{ old('cuil', $user->cuil) }}"
               maxlength="13">
        @error('cuil') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Dirección</label>
        <input name="direccion"
               class="form-control @error('direccion') is-invalid @enderror"
               value="{{ old('direccion', $user->direccion) }}">
        @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- =========================
           Ubicación
      ========================== --}}
      <div class="col-md-4">
        <label class="form-label">País</label>
        <select name="pais_id" class="form-select @error('pais_id') is-invalid @enderror">
          <option value="">—</option>
          @foreach($paises as $p)
            <option value="{{ $p->id }}"
              @selected((string)old('pais_id', $user->pais_id)===(string)$p->id)>
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
            <option value="{{ $prov->id }}"
              @selected((string)old('provincia_id', $user->provincia_id)===(string)$prov->id)>
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
            <option value="{{ $loc->id }}"
              @selected((string)old('localidad_id', $user->localidad_id)===(string)$loc->id)>
              {{ $loc->nombre }} @if($loc->cp) ({{ $loc->cp }}) @endif
            </option>
          @endforeach
        </select>
        @error('localidad_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- =========================
           Company + estado
      ========================== --}}
      @php
        use Illuminate\Support\Collection;

        $authUser       = auth()->user();
        $isSiteAdmin    = $authUser && $authUser->hasRole('admin_sitio');
        $isCompanyAdmin = $authUser && $authUser->hasRole('admin_empresa');
        $canEditRoles   = $isSiteAdmin || $isCompanyAdmin;

        // roles actuales (por nombre)
        $selectedRoles = old('roles', $currentRoleNames ?? $user->roles->pluck('name')->toArray());

        if (!$isSiteAdmin && $isCompanyAdmin && empty($selectedRoles)) {
          $selectedRoles = ['empleado'];
        }

        $roleLabels = [
          'admin_sitio'    => 'Administrador del sitio',
          'admin_empresa' => 'Administrador de compañía',
          'negocio'         => 'negocio / empleado interno',
          'empleado'        => 'empleado',
        ];

        // Normalizar roles a array de strings
        $roleList = $roles instanceof Collection ? $roles->all() : (array)$roles;

        // admin_empresa no puede asignar admin_sitio
        $availableRoles = $roleList;
        if ($isCompanyAdmin && !$isSiteAdmin) {
          $availableRoles = array_filter($roleList, fn($rname) => $rname !== 'admin_sitio');
        }
      @endphp

      @if($isSiteAdmin)
        <div class="col-md-6">
          <label class="form-label">Company</label>
          <select name="company_id" class="form-select @error('company_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach($companies as $c)
              <option value="{{ $c->id }}"
                @selected((string)old('company_id', $user->company_id)===(string)$c->id)>
                {{ $c->name }}
              </option>
            @endforeach
          </select>
          @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      @else
        <input type="hidden" name="company_id" value="{{ old('company_id', $user->company_id) }}">
      @endif

      <div class="col-md-3">
        <label class="form-label">Activo</label>
        <select name="activo" class="form-select @error('activo') is-invalid @enderror">
          <option value="1" @selected(old('activo', (int)$user->activo)==1)>Sí</option>
          <option value="0" @selected(old('activo', (int)$user->activo)==0)>No</option>
        </select>
        @error('activo') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-3">
        <label class="form-label">Fecha nacimiento</label>
        <input type="date"
               name="fecha_nacimiento"
               class="form-control @error('fecha_nacimiento') is-invalid @enderror"
               value="{{ old('fecha_nacimiento', optional($user->fecha_nacimiento)->format('Y-m-d')) }}">
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

        @if($user->imagen)
          <div class="mt-2">
            <div class="small text-muted mb-1">Actual:</div>
            <img src="{{ asset('storage/'.$user->imagen) }}"
                 alt="User image"
                 style="max-height:80px;border-radius:12px;">
          </div>
        @endif
      </div>

      <div class="col-md-6">
        <label class="form-label">Nueva contraseña (opcional)</label>
        <input name="password"
               type="password"
               class="form-control @error('password') is-invalid @enderror"
               placeholder="Dejar vacío para no cambiar">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Confirmar nueva contraseña</label>
        <input name="password_confirmation" type="password" class="form-control">
      </div>

      {{-- =========================
           ROLES (checkbox)
      ========================== --}}
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
        <button class="btn btn-primary btn-mat"><i class="bi bi-check2"></i> Save</button>
      </div>
    </form>
  </div>
</div>
@endsection
