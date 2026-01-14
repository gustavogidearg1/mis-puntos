{{-- resources/views/points/crear.blade.php --}}
@extends('layouts.app')

@section('title', 'Crear movimiento de puntos')

@push('styles')
<style>
  .mat-card{
    border-radius:16px;
    box-shadow:0 6px 18px rgba(15,23,42,.12);
    border:0;
  }
  .mat-header{
    display:flex;
    align-items:center;
    gap:.75rem;
    padding:.9rem 1rem;
    border-bottom:1px solid rgba(0,0,0,.06);
    background:transparent;
  }
  .mat-title{
    font-weight:800;
    font-size:1.05rem;
    margin:0;
    color:#0f172a;
  }
  .btn-mat{
    border-radius:999px;
    padding:.5rem .95rem;
    font-weight:700;
  }
</style>
@endpush

@section('content')
<x-flash />

<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title">
      <i class="bi bi-plus-circle me-1"></i> Crear movimiento de puntos
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

      {{-- COMPANY (solo admin_sitio) --}}
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

      {{-- EMPLEADO --}}
      <div class="col-12 col-md-{{ $isSiteAdmin ? '8' : '6' }}">
        <label class="form-label">Empleado</label>
        <select name="employee_user_id" class="form-select @error('employee_user_id') is-invalid @enderror" required>
          <option value="">Seleccionar empleado…</option>
          @foreach($employees as $e)
            <option value="{{ $e->id }}" @selected(old('employee_user_id') == $e->id)>
              {{ $e->name }}
              — {{ $e->cuil ?? 'sin CUIL' }}
              ({{ $e->company->name ?? 'sin compañía' }})
            </option>
          @endforeach
        </select>
        @error('employee_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- TIPO --}}
      <div class="col-12 col-md-3">
        <label class="form-label">Tipo</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
          @foreach($types as $k => $label)
            <option value="{{ $k }}" @selected(old('type', 'earn') == $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- PUNTOS --}}
      <div class="col-12 col-md-3">
        <label class="form-label">Puntos</label>
        <input type="number"
               min="1"
               name="points"
               class="form-control @error('points') is-invalid @enderror"
               value="{{ old('points', 10) }}"
               required>
        <div class="form-text">
          Para “Canje / Consumo” y “Vencimiento” se descuenta automáticamente (queda negativo).
        </div>
        @error('points') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- FECHA/HORA (default ahora) --}}
      <div class="col-12 col-md-3">
        <label class="form-label">Fecha y hora</label>
        <input type="datetime-local"
               name="occurred_at"
               class="form-control @error('occurred_at') is-invalid @enderror"
               value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}">
        <div class="form-text">Por defecto se carga la fecha/hora actual (podés cambiarla).</div>
        @error('occurred_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- REFERENCIA (OBLIGATORIA) --}}
      <div class="col-12 col-md-6">
        <label class="form-label">Referencia <span class="text-danger">*</span></label>

        @if($isSiteAdmin)
  <div class="col-12 col-md-4">
    <label class="form-label">Compañía (para referencias)</label>
    <select name="company_id" class="form-select" onchange="this.form.submit()">
      <option value="">Todas</option>
      @foreach($companies as $c)
        <option value="{{ $c->id }}" @selected((string)request('company_id') === (string)$c->id)>
          {{ $c->name }}
        </option>
      @endforeach
    </select>
    <div class="form-text">Esto también filtra las referencias disponibles.</div>
  </div>
@endif

        <select name="reference_id"
                class="form-select @error('reference_id') is-invalid @enderror"
                required>
          <option value="">Seleccionar referencia…</option>

          @forelse($references as $ref)
            <option value="{{ $ref->id }}" @selected(old('reference_id') == $ref->id)>
              {{ $ref->name }}
            </option>
          @empty
            <option value="" disabled>
              (No hay referencias activas cargadas en point_references)
            </option>
          @endforelse
        </select>

        <div class="form-text">
          La referencia es obligatoria.
        </div>
        @error('reference_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- NOTA --}}
      <div class="col-12">
        <label class="form-label">Nota (opcional)</label>
        <textarea name="note"
                  rows="3"
                  class="form-control @error('note') is-invalid @enderror"
                  maxlength="500"
                  placeholder="Detalle interno (opcional)">{{ old('note') }}</textarea>
        @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      {{-- ACTIONS --}}
      <div class="col-12 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary btn-mat">
          <i class="bi bi-check2"></i> Guardar movimiento
        </button>
        <a href="{{ route('points.index') }}" class="btn btn-outline-secondary btn-mat">
          Cancelar
        </a>
      </div>

      {{-- Ayuda si no hay referencias --}}
      @if(($references ?? collect())->isEmpty())
        <div class="col-12">
          <div class="alert alert-warning mb-0">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle"></i> No hay referencias disponibles</div>
            Cargá referencias en la tabla <code>point_references</code> (por ejemplo: Sueldo, Premios, Vacaciones, Ajuste, etc.)
            y marcá <code>is_active = 1</code>. Luego volvé a esta pantalla.
          </div>
        </div>
      @endif

    </form>
  </div>
</div>
@endsection
