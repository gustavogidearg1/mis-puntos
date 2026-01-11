@extends('layouts.app')
@section('title','Crear consumo')

@section('content')
<style>
  .input-group.flex-nowrap .form-select { min-width: 0; }
  .input-group.flex-nowrap .btn { white-space: nowrap; }
  .list-group-item-action { cursor: pointer; }
  .muted-hint { color:#6c757d; font-size:.9rem; }
</style>

<div class="container py-3">
  <div class="card mat-card">
    <div class="mat-header">
      <h3 class="mat-title mb-0">
        <i class="bi bi-qr-code-scan me-2"></i> Crear consumo (Negocio)
      </h3>
      <div class="ms-auto">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-mat">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
    </div>

    <div class="card-body p-4">
      @if (session('error'))
        <div class="alert alert-danger mat-alert">{{ session('error') }}</div>
      @endif

      <form method="POST" action="{{ route('redeems.store') }}">
        @csrf

<div class="mb-3">
  <label class="form-label fw-semibold">Negocio</label>

  <div class="input-group">
    <input id="businessName" class="form-control" value="{{ old('business_name') }}"
           placeholder="Escaneá el QR del negocio..." readonly>
    <input type="hidden" name="business_id" id="businessId" value="{{ old('business_id') }}">
    <button type="button" class="btn btn-outline-primary btn-mat"
            data-bs-toggle="modal" data-bs-target="#qrScanModal">
      <i class="bi bi-qr-code-scan"></i> Escanear QR
    </button>
  </div>

  <div id="qrScanErrorInline" class="text-danger small mt-2" style="display:none;"></div>

  <div class="form-text text-muted-500">
    Escaneá el QR del negocio (ej: <code>/redeems/manual/1</code>).
  </div>
</div>

            {{-- =========================
     Negocio (por QR)
========================== --}}
<div class="row g-3 mb-3">
  <div class="col-md-8">
    <label class="form-label fw-semibold">Negocio</label>

    <div class="input-group">
      <input id="businessName" class="form-control" value="{{ old('business_name') }}" placeholder="Escaneá el QR del negocio..." readonly>
      <input type="hidden" name="business_id" id="businessId" value="{{ old('business_id') }}">
      <button type="button" class="btn btn-outline-primary btn-mat" data-bs-toggle="modal" data-bs-target="#qrScanModal">
        <i class="bi bi-qr-code-scan"></i> Escanear QR
      </button>
    </div>

    @error('business_id')
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    <div class="form-text text-muted-500">
      Escaneá el QR del negocio (ej: <code>/redeems/manual/1</code>) para cargarlo automáticamente.
    </div>
  </div>
</div>


            <label class="form-label fw-semibold">Empleado</label>

            <div class="input-group flex-nowrap">
              <select id="employeeSelect"
                      name="employee_user_id"
                      class="form-select @error('employee_user_id') is-invalid @enderror"
                      required>
                <option value="">Seleccionar...</option>
                @foreach($employees as $e)
                  <option value="{{ $e->id }}"
                          data-name="{{ $e->name }}"
                          data-cuil="{{ $e->cuil ?? '' }}"
                          {{ old('employee_user_id') == $e->id ? 'selected' : '' }}>
                    {{ $e->name }} {{ $e->cuil ? '— '.$e->cuil : '' }}
                  </option>
                @endforeach
              </select>

              <button type="button"
                      class="btn btn-success btn-mat"
                      data-bs-toggle="modal"
                      data-bs-target="#employeeModal"
                      title="Buscar empleado">
                <i class="bi bi-search"></i>
                <span class="d-none d-sm-inline">Buscar</span>
              </button>
            </div>

            @error('employee_user_id')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror

            <div class="form-text text-muted-500">
              Elegí del listado o usá <strong>Buscar</strong> para encontrar por nombre o CUIL.
            </div>
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Puntos a consumir</label>
            <input type="number" min="1" name="points"
                   value="{{ old('points', 1) }}"
                   class="form-control @error('points') is-invalid @enderror" required>
            @error('points') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Nota (opcional)</label>
          <textarea name="note" rows="3"
                    class="form-control @error('note') is-invalid @enderror"
                    placeholder="Detalle del gasto...">{{ old('note') }}</textarea>
          @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="d-grid">
          <button class="btn btn-primary btn-mat" type="submit">
            <i class="bi bi-qr-code me-1"></i> Generar QR
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ===================== Modal Buscador de Empleados ===================== --}}
<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content mat-card">
      <div class="modal-header mat-header">
        <h5 class="modal-title mat-title" id="employeeModalLabel">
          <i class="bi bi-person-search"></i> Buscar empleado
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input id="employeeSearchInput" type="text" class="form-control" placeholder="Nombre o CUIL…">
        </div>

        <div class="muted-hint mb-2">
          Tip: podés escribir parte del nombre o los números del CUIL.
        </div>

        <div id="employeeResults" class="list-group">
          @foreach($employees as $e)
            <button type="button"
                    class="list-group-item list-group-item-action employee-result"
                    data-id="{{ $e->id }}"
                    data-name="{{ $e->name }}"
                    data-cuil="{{ $e->cuil ?? '' }}">
              <div class="d-flex w-100 justify-content-between">
                <strong>{{ $e->name }}</strong>
                <small class="text-muted">{{ $e->cuil ?? '' }}</small>
              </div>
            </button>
          @endforeach
        </div>

        <div id="employeeEmpty" class="text-center py-4 text-muted" style="display:none;">
          <i class="bi bi-inbox"></i> Sin resultados
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

{{-- ===================== Modal Escanear QR Negocio ===================== --}}
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
          Apuntá la cámara al QR. Se completará el negocio automáticamente.
        </div>

        <div class="ratio ratio-16x9 bg-dark rounded-4 overflow-hidden">
          <video id="qrVideo" playsinline></video>
        </div>

        <div id="qrScanError" class="text-danger small mt-2" style="display:none;"></div>
        <div class="muted-hint mt-2">
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
<script>
document.addEventListener('DOMContentLoaded', () => {
  /* ===========================
     Modal buscador empleados
  =========================== */
  const employeeModalEl  = document.getElementById('employeeModal');
  const inputEl          = document.getElementById('employeeSearchInput');
  const listEl           = document.getElementById('employeeResults');
  const emptyEl          = document.getElementById('employeeEmpty');
  const selectEl         = document.getElementById('employeeSelect');

  function normalize(s){ return String(s || '').toLowerCase().trim(); }

  if (employeeModalEl && inputEl && listEl && emptyEl && selectEl) {
    function filterEmployees(term){
      term = normalize(term);
      let visible = 0;

      listEl.querySelectorAll('.employee-result').forEach(btn => {
        const name = normalize(btn.dataset.name);
        const cuil = normalize(btn.dataset.cuil).replace(/\D/g,'');
        const tNum = term.replace(/\D/g,'');

        const match = !term || name.includes(term) || (tNum && cuil.includes(tNum));
        btn.style.display = match ? '' : 'none';
        if (match) visible++;
      });

      emptyEl.style.display = (visible === 0) ? 'block' : 'none';
    }

    employeeModalEl.addEventListener('show.bs.modal', () => {
      inputEl.value = '';
      emptyEl.style.display = 'none';
      listEl.querySelectorAll('.employee-result').forEach(btn => btn.style.display = '');
      setTimeout(() => inputEl.focus(), 250);
    });

    inputEl.addEventListener('input', () => filterEmployees(inputEl.value));

    listEl.addEventListener('click', (e) => {
      const btn = e.target.closest('.employee-result');
      if (!btn) return;

      const id = String(btn.dataset.id || '');
      const opt = Array.from(selectEl.options).find(o => String(o.value) === id);
      if (opt) selectEl.value = opt.value;

      const instance = bootstrap.Modal.getInstance(employeeModalEl);
      if (instance) instance.hide();
    });
  }

  /* ===========================
     Modal escaneo QR negocio
  =========================== */
  const qrModalEl       = document.getElementById('qrScanModal');
  const videoEl         = document.getElementById('qrVideo');
  const errEl           = document.getElementById('qrScanError');
  const businessIdEl    = document.getElementById('businessId');
  const businessNameEl  = document.getElementById('businessName');

  if (!qrModalEl || !videoEl || !businessIdEl || !businessNameEl) return;

  let stream = null;
  let detector = null;
  let scanTimer = null;

  function showErr(msg){
    if (!errEl) return;
    errEl.textContent = msg || '';
    errEl.style.display = msg ? '' : 'none';
  }

  function stopCamera(){
    if (scanTimer) { clearInterval(scanTimer); scanTimer = null; }
    if (stream) {
      stream.getTracks().forEach(t => t.stop());
      stream = null;
    }
    videoEl.srcObject = null;
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

  async function fillBusiness(businessId){
    businessIdEl.value = businessId;

    const res = await fetch(`/abm/businesses/${businessId}/json`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    if (!res.ok) throw new Error('No se pudo obtener el negocio.');
    const data = await res.json();

    businessNameEl.value = data.name || data.nombre || (`Negocio #${businessId}`);
  }

  qrModalEl.addEventListener('show.bs.modal', async () => {
    showErr('');

    if (!('mediaDevices' in navigator) || !navigator.mediaDevices.getUserMedia) {
      showErr('Tu navegador no soporta cámara (getUserMedia).');
      return;
    }

    if (!('BarcodeDetector' in window)) {
      showErr('Tu navegador no soporta lectura QR nativa. En iPhone suele pasar: usamos librería si querés.');
      return;
    }

    detector = new BarcodeDetector({ formats: ['qr_code'] });

    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }, audio: false
      });
      videoEl.srcObject = stream;
      await videoEl.play();

      scanTimer = setInterval(async () => {
        try {
          if (!videoEl.videoWidth) return;
          const codes = await detector.detect(videoEl);
          if (!codes || !codes.length) return;

          const text = codes[0].rawValue || '';
          const businessId = extractBusinessId(text);
          if (!businessId) return;

          await fillBusiness(businessId);

          const instance = bootstrap.Modal.getInstance(qrModalEl);
          if (instance) instance.hide();
        } catch (e) {}
      }, 250);

    } catch (e) {
      showErr('No se pudo abrir la cámara. Revisá permisos del navegador.');
    }
  });

  qrModalEl.addEventListener('hidden.bs.modal', () => {
    stopCamera();
    showErr('');
  });
});
</script>

@endpush

@endsection
