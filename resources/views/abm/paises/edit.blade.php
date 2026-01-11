@extends('layouts.app')

@section('title','Editar País')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0"><i class="bi bi-pencil-square"></i> Editar País</h3>
    <div class="ms-auto">
      <a href="{{ route('abm.paises.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
  </div>

  <div class="card-body">
    <form method="POST" action="{{ route('abm.paises.update',$pais) }}" class="row g-3">
      @csrf @method('PUT')

      <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" value="{{ old('nombre',$pais->nombre) }}" required>
        @error('nombre') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-3">
        <label class="form-label">ISO2</label>
        <input name="iso2" class="form-control" value="{{ old('iso2',$pais->iso2) }}" maxlength="2">
        @error('iso2') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-3">
        <label class="form-label">ISO3</label>
        <input name="iso3" class="form-control" value="{{ old('iso3',$pais->iso3) }}" maxlength="3">
        @error('iso3') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('abm.paises.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button class="btn btn-primary btn-mat"><i class="bi bi-check2"></i> Actualizar</button>
      </div>
    </form>
  </div>
</div>
@endsection
