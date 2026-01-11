@extends('layouts.app')

@section('title','Nueva Provincia')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0"><i class="bi bi-plus-circle"></i> Nueva Provincia</h3>
    <div class="ms-auto">
      <a href="{{ route('abm.provincias.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
  </div>

  <div class="card-body">
    <form method="POST" action="{{ route('abm.provincias.store') }}" class="row g-3">
      @csrf

      <div class="col-md-6">
        <label class="form-label">País</label>
        <select name="pais_id" class="form-select" required>
          <option value="" disabled {{ old('pais_id') ? '' : 'selected' }}>Seleccionar...</option>
          @foreach($paises as $pais)
            <option value="{{ $pais->id }}" @selected(old('pais_id') == $pais->id)>
              {{ $pais->nombre }}
            </option>
          @endforeach
        </select>
        @error('pais_id') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" value="{{ old('nombre') }}" required>
        @error('nombre') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('abm.provincias.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button class="btn btn-primary btn-mat"><i class="bi bi-check2"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection
