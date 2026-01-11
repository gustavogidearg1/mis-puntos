@extends('layouts.app')

@section('title','Nuevo País')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0"><i class="bi bi-plus-circle"></i> Nuevo País</h3>
    <div class="ms-auto">
      <a href="{{ route('abm.paises.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
  </div>

  <div class="card-body">
    <form method="POST" action="{{ route('abm.paises.store') }}" class="row g-3">
      @csrf



      <div class="col-md-6">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" value="{{ old('nombre') }}" required>
        @error('nombre') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-3">
        <label class="form-label">ISO2</label>
        <input name="iso2" class="form-control text-uppercase" value="{{ old('iso2') }}" maxlength="2" placeholder="AR">
        @error('iso2') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-3">
        <label class="form-label">ISO3</label>
        <input name="iso3" class="form-control text-uppercase" value="{{ old('iso3') }}" maxlength="3" placeholder="ARG">
        @error('iso3') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('abm.paises.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button class="btn btn-primary btn-mat"><i class="bi bi-check2"></i> Guardar</button>
      </div>
    </form>
  </div>

      <div class="col-12">
        <div class="alert alert-info mb-0" role="alert" style="border-radius:14px;">
          <div class="fw-semibold mb-1">¿Para qué sirven ISO2 e ISO3?</div>
          <div class="small">
            Los campos <b>iso2</b> y <b>iso3</b> en países son para guardar los códigos estándar <b>ISO 3166</b> del país:
            <ul class="mb-0 mt-1">
              <li><b>ISO2</b>: código de 2 letras (ej: <b>AR</b>, <b>BR</b>, <b>US</b>)</li>
              <li><b>ISO3</b>: código de 3 letras (ej: <b>ARG</b>, <b>BRA</b>, <b>USA</b>)</li>
            </ul>
          </div>
        </div>
      </div>

</div>
@endsection
