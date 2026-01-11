@extends('layouts.app')

@section('title','Carga Masiva de Puntos')

@section('content')
<x-page-header title="Carga Masiva de Puntos" />

<x-flash />

<div class="card mat-card">
  <div class="card-body">
    <h5 class="mb-2">Subir CSV</h5>
    <p class="text-muted small mb-3">
      Columnas requeridas: <code>employee_email</code> o <code>employee_dni</code>, <code>points</code>, <code>occurred_at</code>.
      Opcionales: <code>reference</code>, <code>note</code>.
    </p>

    <form method="POST" action="{{ route('points.import.preview') }}" enctype="multipart/form-data">
      @csrf

      <div class="mb-3">
        <label class="form-label">Archivo CSV</label>
        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required>
        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <button class="btn btn-primary btn-mat">
        <i class="bi bi-eye me-1"></i> Previsualizar
      </button>
    </form>
  </div>
</div>
@endsection
