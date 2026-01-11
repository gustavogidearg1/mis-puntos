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
<div class="mat-header flex-wrap">
  <h3 class="mat-title mb-0 flex-grow-1" style="min-width: 220px;">
    <i class="bi bi-shop me-2"></i> Consumo manual (Empleado → Negocio)
  </h3>

  <div class="d-flex flex-wrap align-items-center gap-2 ms-auto mt-2 mt-lg-0"
       style="max-width:100%;">
    <span class="saldo-chip" title="Saldo disponible">
      <i class="bi bi-wallet2"></i>
      <span class="d-none d-sm-inline">Saldo:</span>
      <strong>{{ number_format($saldo) }}</strong>
    </span>

    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-mat">
      <i class="bi bi-arrow-left"></i>
      <span class="d-none d-sm-inline">Volver</span>
    </a>
  </div>
</div>


    <div class="card-body p-4">
      <div class="mb-3">
        <label class="form-label fw-semibold">Elegí el negocio</label>
        <input id="q" type="text" class="form-control" placeholder="Buscar negocio por nombre…">
      </div>

      <div class="d-flex gap-2 mb-3">
  <button type="button" class="btn btn-primary btn-mat" data-bs-toggle="modal" data-bs-target="#qrScanModal">
    <i class="bi bi-qr-code-scan"></i> Escanear QR
  </button>
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

<div class="modal fade" id="qrScanModal" tabindex="-1" aria-labelledby="qrScanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content mat-card">
      <div class="modal-header mat-header">
        <h5 class="modal-title mat-title" id="qrScanModalLabel">
          <i class="bi bi-camera"></i> Escanear QR del negocio
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-info">
          Apuntá la cámara al QR del negocio. Te vamos a llevar al consumo con el negocio precargado.
        </div>

        <div class="border rounded-4 p-2 bg-dark">
          <div id="qrReader" style="width:100%;"></div>
        </div>

        <div id="qrScanError" class="text-danger small mt-2" style="display:none;"></div>
        <div class="text-muted small mt-2">
          Si no te pide permisos, revisá permisos de cámara del navegador.
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>


@push('scripts')

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // ===========================
  // Filtro buscador negocios
  // ===========================
  const q = document.getElementById('q');
  const list = document.getElementById('list');
  const empty = document.getElementById('empty');

  function normalize(s){ return String(s||'').toLowerCase().trim(); }

  if(q && list){
    q.addEventListener('input', () => {
      const term = normalize(q.value);
      let visible = 0;

      list.querySelectorAll('a.list-group-item').forEach(a => {
        const name = normalize(a.innerText);
        const match = !term || name.includes(term);
        a.style.display = match ? '' : 'none';
        if(match) visible++;
      });

      if (empty) empty.style.display = visible ? 'none' : 'block';
    });
  }

  // ===========================
  // Scanner QR (html5-qrcode)
  // ===========================
  const qrModalEl = document.getElementById('qrScanModal');
  const errEl     = document.getElementById('qrScanError');
  const readerId  = "qrReader";

  if (!qrModalEl) return;

  let qr = null;
  let isRunning = false;

  function showErr(msg){
    if (!errEl) return;
    errEl.textContent = msg || '';
    errEl.style.display = msg ? '' : 'none';
  }

  function extractBusinessId(text){
    try {
      const url = new URL(text, window.location.origin);
      const parts = url.pathname.split('/').filter(Boolean);
      const i = parts.findIndex(p => p === 'manual');
      if (i >= 0 && parts[i+1]) return parts[i+1];
    } catch (e) {
      const parts = String(text).split('/').filter(Boolean);
      const i = parts.findIndex(p => p === 'manual');
      if (i >= 0 && parts[i+1]) return parts[i+1];
    }
    return null;
  }

  async function startQr(){
    showErr('');

    if (isRunning) return; // <-- evita doble start
    isRunning = true;

    // Limpiar UI anterior (clave para que no se “duplique”)
    const el = document.getElementById(readerId);
    if (el) el.innerHTML = '';

    try {
      qr = new Html5Qrcode(readerId);

      await qr.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 250 },
        (decodedText) => {
          const businessId = extractBusinessId(decodedText);
          if (!businessId) return;

          // cortar camera antes de redirigir (más estable en móviles)
          stopQr().finally(() => {
            window.location.href = `/redeems/manual/${businessId}`;
          });
        },
        () => {}
      );
    } catch (e) {
      isRunning = false;
      showErr('No se pudo abrir la cámara. Revisá permisos del navegador.');
    }
  }

  async function stopQr(){
    if (!qr) { isRunning = false; return; }

    try { await qr.stop(); } catch(e) {}
    try { await qr.clear(); } catch(e) {}
    qr = null;
    isRunning = false;

    const el = document.getElementById(readerId);
    if (el) el.innerHTML = '';
  }

  // OJO: usá shown/hidden (vos ya lo hacés bien)
  qrModalEl.addEventListener('shown.bs.modal', startQr);
  qrModalEl.addEventListener('hidden.bs.modal', stopQr);

});
</script>
@endpush
@endsection
