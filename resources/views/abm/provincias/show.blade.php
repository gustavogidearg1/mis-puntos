@extends('layouts.app')

@section('title','Provincia')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0"><i class="bi bi-eye"></i> Provincia</h3>
    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('abm.provincias.edit',$provincia) }}" class="btn btn-outline-primary btn-sm">Editar</a>
      <a href="{{ route('abm.provincias.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
  </div>

  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="text-muted small">Nombre</div>
        <div class="fw-semibold">{{ $provincia->nombre }}</div>
      </div>

      <div class="col-md-6">
        <div class="text-muted small">País</div>
        <div class="fw-semibold">{{ $provincia->pais?->nombre ?? '—' }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
