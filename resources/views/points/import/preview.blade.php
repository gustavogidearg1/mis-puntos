@extends('layouts.app')

@section('title','Previsualización Importación')

@section('content')
<x-page-header title="Previsualización de Importación" />

<x-flash />

<div class="card mat-card mb-3">
  <div class="card-body">
    <div class="d-flex flex-wrap gap-3 align-items-center">
      <div>
        <div class="fw-semibold">Lote #{{ $batch->id }}</div>
        <div class="text-muted small">Archivo: {{ $batch->filename }}</div>
      </div>

      <div class="ms-auto d-flex gap-2">
        <span class="badge text-bg-secondary">Total: {{ $batch->rows_total }}</span>
        <span class="badge text-bg-success">OK: {{ $batch->rows_ok }}</span>
        <span class="badge text-bg-danger">Error: {{ $batch->rows_error }}</span>
      </div>
    </div>

    <hr>

    <form method="POST" action="{{ route('points.import.commit') }}">
      @csrf
      <input type="hidden" name="confirm" value="1">

      <button class="btn btn-success btn-mat" @if($batch->rows_ok == 0) disabled @endif>
        <i class="bi bi-check2-circle me-1"></i> Confirmar Importación
      </button>

      <a href="{{ route('points.import.create') }}" class="btn btn-outline-secondary ms-2">
        Volver
      </a>

      @if($batch->rows_ok == 0)
        <div class="text-danger small mt-2">No hay filas válidas para importar.</div>
      @endif
    </form>
  </div>
</div>

<div class="card mat-card">
  <div class="card-body">
    <h6 class="mb-3">Detalle (primeras filas leídas)</h6>

    <div class="table-responsive">
  <table class="table table-sm align-middle">
    <thead>
      <tr>
        <th>Línea</th>
        <th>CUIL/CUIT</th>
        <th>Puntos</th>
        <th>Fecha</th>
        <th>Ref</th>
        <th>Estado</th>
        <th>Observaciones</th>
      </tr>
    </thead>
    <tbody>
      @foreach($preview as $r)
        <tr>
          <td>{{ $r['line'] }}</td>
          <td>{{ $r['employee_cuil'] ?? '—' }}</td>
          <td>{{ $r['points'] ?? '—' }}</td>
          <td>{{ $r['occurred_at'] ?? '—' }}</td>
          <td>{{ $r['reference'] ?? '—' }}</td>
          <td>
            @if(($r['status'] ?? '') === 'ok')
              <span class="badge text-bg-success">OK</span>
            @else
              <span class="badge text-bg-danger">Error</span>
            @endif
          </td>
          <td class="text-muted small">
            @if(!empty($r['issues']))
              {{ implode(' | ', $r['issues']) }}
            @else
              —
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>


  </div>
</div>
@endsection
