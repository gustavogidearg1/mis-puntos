@extends('layouts.app')

@section('title','País')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0"><i class="bi bi-eye"></i> País</h3>
    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('abm.paises.edit',$pais) }}" class="btn btn-outline-primary btn-sm">Editar</a>
      <a href="{{ route('abm.paises.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
  </div>

  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="text-muted small">Nombre</div>
        <div class="fw-semibold">{{ $pais->nombre }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">ISO2</div>
        <div class="fw-semibold">{{ $pais->iso2 ?? '—' }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">ISO3</div>
        <div class="fw-semibold">{{ $pais->iso3 ?? '—' }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
