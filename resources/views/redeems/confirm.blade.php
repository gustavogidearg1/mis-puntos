@extends('layouts.app')
@section('title','Comprobante de consumo')

@section('content')
<div class="container py-3">
  <div class="card mat-card">
    <div class="mat-header">
      <h3 class="mat-title mb-0"><i class="bi bi-receipt me-2"></i> Comprobante de consumo</h3>
      <div class="ms-auto">
        <a href="{{ route('points.index') }}" class="btn btn-outline-secondary btn-mat">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
    </div>

    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-4">Empleado</dt>
        <dd class="col-sm-8">{{ $redemption->employee->name ?? '—' }}</dd>

        <dt class="col-sm-4">Puntos consumidos</dt>
        <dd class="col-sm-8"><strong>{{ number_format($redemption->points) }}</strong></dd>

        <dt class="col-sm-4">Negocio</dt>
        <dd class="col-sm-8">{{ $redemption->business->name ?? '—' }}</dd>

        <dt class="col-sm-4">Fecha</dt>
        <dd class="col-sm-8">{{ optional($redemption->confirmed_at)->format('Y-m-d H:i') ?? '—' }}</dd>

        <dt class="col-sm-4">Nota</dt>
        <dd class="col-sm-8">{{ $redemption->note ?? '—' }}</dd>

        <dt class="col-sm-4">Estado</dt>
        <dd class="col-sm-8">
          @if(($redemption->status ?? '') === 'voided' || optional($redemption->movement)->voided_at)
            <span class="badge bg-danger">ANULADO</span>
          @else
            <span class="badge bg-success">CONFIRMADO</span>
          @endif
        </dd>
      </dl>
    </div>
  </div>
</div>
@endsection
