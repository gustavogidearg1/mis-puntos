@extends('layouts.app')

@section('title','Localidad')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0"><i class="bi bi-eye"></i> Localidad</h3>
    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('abm.localidades.edit',$localidad) }}" class="btn btn-outline-primary btn-sm">Editar</a>
      <a href="{{ route('abm.localidades.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
  </div>

  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="text-muted small">Nombre</div>
        <div class="fw-semibold">{{ $localidad->nombre }}</div>
      </div>

      <div class="col-md-3">
        <div class="text-muted small">CP</div>
        <div class="fw-semibold">{{ $localidad->cp ?? '—' }}</div>
      </div>

      <div class="col-md-6">
        <div class="text-muted small">Provincia</div>
        <div class="fw-semibold">{{ $localidad->provincia?->nombre ?? '—' }}</div>
      </div>

      <div class="col-md-6">
        <div class="text-muted small">País</div>
        <div class="fw-semibold">{{ $localidad->provincia?->pais?->nombre ?? '—' }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
