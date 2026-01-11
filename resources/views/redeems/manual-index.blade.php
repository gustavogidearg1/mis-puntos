@extends('layouts.app')
@section('title','Consumo manual')

@section('content')
<style>
  .saldo-chip{
    display:inline-flex; align-items:center; gap:.5rem;
    border-radius:999px; padding:.35rem .75rem;
    background:#EAF2FF; color:#1e40af; font-weight:700;
  }
  .list-group-item-action{ cursor:pointer; }
</style>

<div class="container py-3">
  <div class="card mat-card">
    <div class="mat-header">
      <h3 class="mat-title mb-0">
        <i class="bi bi-shop me-2"></i> Consumo manual (Empleado → Negocio)
      </h3>

      <div class="ms-auto d-flex align-items-center gap-2">
        <span class="saldo-chip" title="Saldo disponible">
          <i class="bi bi-wallet2"></i> Saldo: <strong>{{ number_format($saldo) }}</strong>
        </span>

        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-mat">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
    </div>

    <div class="card-body p-4">
      <div class="mb-3">
        <label class="form-label fw-semibold">Elegí el negocio</label>
        <input id="q" type="text" class="form-control" placeholder="Buscar negocio por nombre…">
      </div>

      <div id="list" class="list-group">
        @foreach($businesses as $b)
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
             href="{{ route('redeems.manual.create', $b->id) }}">
            <span class="fw-semibold">{{ $b->name }}</span>
            <i class="bi bi-chevron-right text-muted"></i>
          </a>
        @endforeach
      </div>

      <div id="empty" class="text-center text-muted py-4" style="display:none;">
        <i class="bi bi-inbox" style="font-size:2rem;"></i>
        <div class="mt-2">Sin resultados</div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const q = document.getElementById('q');
  const list = document.getElementById('list');
  const empty = document.getElementById('empty');
  if(!q || !list) return;

  function normalize(s){ return String(s||'').toLowerCase().trim(); }

  q.addEventListener('input', () => {
    const term = normalize(q.value);
    let visible = 0;

    list.querySelectorAll('a.list-group-item').forEach(a => {
      const name = normalize(a.innerText);
      const match = !term || name.includes(term);
      a.style.display = match ? '' : 'none';
      if(match) visible++;
    });

    empty.style.display = visible ? 'none' : 'block';
  });
});
</script>
@endpush
@endsection
