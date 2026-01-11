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

      @if($isSiteAdmin)
        <div class="col-12 col-md-4">
          <label class="form-label">Compañía (opcional)</label>
          <select name="company_id" class="form-select">
            <option value="">Tomar desde el empleado</option>
            @foreach($companies as $c)
              <option value="{{ $c->id }}" @selected(old('company_id', $companyId) == $c->id)>
                {{ $c->name }}
              </option>
            @endforeach
          </select>
          <div class="form-text">Si no elegís compañía, se toma automáticamente la del empleado.</div>
          @error('company_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
      @endif

      <div class="col-12 col-md-{{ $isSiteAdmin ? '8' : '6' }}">
        <label class="form-label">Empleado</label>
        <select name="employee_user_id" class="form-select" required>
          <option value="">Seleccionar empleado…</option>
          @foreach($employees as $e)
            <option value="{{ $e->id }}" @selected(old('employee_user_id') == $e->id)>
              {{ $e->name }} — {{ $e->cuil ?? 'sin CUIL' }} ({{ $e->company->name ?? 'sin compañía' }})
            </option>
          @endforeach
        </select>
        @error('employee_user_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Tipo</label>
        <select name="type" class="form-select" required>
          @foreach($types as $k => $label)
            <option value="{{ $k }}" @selected(old('type', 'earn') == $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Puntos</label>
        <input type="number" min="1" name="points" class="form-control"
               value="{{ old('points', 10) }}" required>
        <div class="form-text">Para “Canje” se descuenta automáticamente.</div>
        @error('points') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
      </div>

      <div class="col-12 col-md-3">
        <label class="form-label">Fecha</label>
        <input type="datetime-local" name="occurred_at" class="form-control"
               value="{{ old('occurred_at') }}">
        <div class="form-text">Si queda vacío se usa la fecha/hora actual.</div>
        @error('occurred_at') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label">Referencia</label>
        <input type="text" name="reference" class="form-control"
               value="{{ old('reference') }}" maxlength="120"
               placeholder="Ej: Premio especial, ajuste, corrección…">
        @error('reference') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
      </div>

      <div class="col-12">
        <label class="form-label">Nota</label>
        <textarea name="note" rows="3" class="form-control" maxlength="500"
                  placeholder="Detalle interno (opcional)">{{ old('note') }}</textarea>
        @error('note') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
      </div>

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
