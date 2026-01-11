{{-- resources/views/points/crear.blade.php --}}
@extends('layouts.app')

@section('title', 'Crear movimiento de puntos')

@section('content')
<x-flash />

<div class="card mat-card">
  <div class="mat-header d-flex align-items-center">
    <h3 class="mat-title mb-0">
      <i class="bi bi-plus-circle"></i> Crear movimiento de puntos
    </h3>

    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('points.index') }}" class="btn btn-outline-secondary btn-mat">
        <i class="bi bi-arrow-left"></i> Volver
      </a>
    </div>
  </div>

  <div class="card-body">
    <form method="POST" action="{{ route('points.store') }}" class="row g-3">
      @csrf

      {{-- Company (solo admin_sitio) --}}
      @if($isSiteAdmin)
        <div class="col-12 col-md-4">
          <label class="form-label">Compañía (opcional)</label>
          <select name="company_id" class="form-select @error('company_id') is-invalid @enderror">
            <option value="">Tomar desde el empleado</option>
            @foreach($companies as $c)
              <option value="{{ $c->id }}" @selected(old('company_id', $companyId) == $c->id)>
                {{ $c->name }}
              </option>
            @endforeach
          </select>
          <div class="form-text">Si no elegís compañía, se toma automáticamente la del empleado.</div>
          @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
      @endif

      {{-- Empleado --}}
      <div class="col-12 col-md-{{ $isSiteAdmin ? '8' : '6' }}">
        <label class="form-label">Empleado</label>
        <select name="employee_user_id" class="form-select @error('employee_user_id') is-invalid @enderror" required>
          <option value="">Seleccionar empleado…</option>
          @foreach($employees as $e)
            <option value="{{ $e->id }}" @selected(old('employee_user_id') == $e->id)>
              {{ $e->name }} — {{ $e->cuil ?? 'sin CUIL' }} ({{ $e->company->name ?? 'sin compañía' }})
            </option>
          @endforeach
        </select>
        @error('employee_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Tipo --}}
      <div class="col-12 col-md-3">
        <label class="form-label">Tipo</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
          @foreach($types as $k => $label)
            <option value="{{ $k }}" @selected(old('type', 'earn') == $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Puntos --}}
      <div class="col-12 col-md-3">
        <label class="form-label">Puntos</label>
        <input type="number"
               min="1"
               name="points"
               class="form-control @error('points') is-invalid @enderror"
               value="{{ old('points', 10) }}"
               required>
        <div class="form-text">Para “Canje” se descuenta automáticamente.</div>
        @error('points') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Fecha --}}
      <div class="col-12 col-md-3">
        <label class="form-label">Fecha</label>
<input type="datetime-local"
       name="occurred_at"
       class="form-control @error('occurred_at') is-invalid @enderror"
       value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}">

        @error('occurred_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- ✅ Referencia (OBLIGATORIA) --}}
      <div class="col-12 col-md-6">
        <label class="form-label">
          Referencia <span class="text-danger">*</span>
        </label>

        {{-- IMPORTANTE:
             - tu controller debe leer "reference" (no reference_id)
             - enviamos el ID como value, y el controller lo traduce a name
        --}}
        <select name="reference"
                class="form-select @error('reference') is-invalid @enderror"
                required>
          <option value="" disabled @selected(old('reference') === null || old('reference') === '')>
            Seleccionar referencia…
          </option>

          @foreach($references as $ref)
            <option value="{{ $ref->id }}" @selected((string)old('reference') === (string)$ref->id)>
              {{ $ref->name }}
            </option>
          @endforeach
        </select>

        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Este campo es obligatorio para clasificar el movimiento (ej: Vacaciones, Premio, etc.).</div>
      </div>

      {{-- Nota --}}
      <div class="col-12">
        <label class="form-label">Nota</label>
        <textarea name="note"
                  rows="3"
                  class="form-control @error('note') is-invalid @enderror"
                  maxlength="500"
                  placeholder="Detalle interno (opcional)">{{ old('note') }}</textarea>
        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- Acciones --}}
      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-mat">
          <i class="bi bi-check2"></i> Guardar movimiento
        </button>
        <a href="{{ route('points.index') }}" class="btn btn-outline-secondary btn-mat">
          Cancelar
        </a>
      </div>

    </form>
  </div>
</div>
@endsection
