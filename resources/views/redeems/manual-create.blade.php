@extends('layouts.app')
@section('title','Consumo manual')

@section('content')
<style>
  .muted-hint { color:#6c757d; font-size:.9rem; }

  .saldo-chip{
    display:inline-flex; align-items:center; gap:.5rem;
    border-radius:999px; padding:.35rem .75rem;
    background:#EAF2FF; color:#1e40af; font-weight:700;
  }
</style>

<div class="container py-3">
  <div class="card mat-card">
    <div class="mat-header">
      <h3 class="mat-title mb-0">
        <i class="bi bi-shop me-2"></i> Consumo manual (Empleado → Negocio)
      </h3>
      <div class="ms-auto d-flex align-items-center gap-2">
        <span class="saldo-chip" title="Saldo disponible">
          <i class="bi bi-wallet2"></i> Saldo: <strong id="saldoVal">{{ number_format($saldo) }}</strong>
        </span>

        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-mat">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
    </div>

    <div class="card-body p-4">
      @if (session('error'))
        <div class="alert alert-danger mat-alert">{{ session('error') }}</div>
      @endif

      @if ($errors->any())
        <div class="alert alert-danger mat-alert">
          <ul class="mb-0">
            @foreach($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('redeems.manual.store', $business->id) }}" id="manualForm">
        @csrf

        <div class="mb-3">
          <label class="form-label fw-semibold">Negocio</label>
          <input class="form-control" value="{{ $business->name }}" readonly>
        </div>

        <input type="hidden" name="business_user_id" value="{{ $business->id }}">

        <div class="mb-3">
          <label class="form-label fw-semibold">Puntos a consumir</label>
          <input id="pointsInput"
                 type="number"
                 min="1"
                 name="points"
                 class="form-control @error('points') is-invalid @enderror"
                 value="{{ old('points', 1) }}"
                 required>
          @error('points') <div class="invalid-feedback">{{ $message }}</div> @enderror

          <div id="warnSaldo" class="alert alert-warning mt-2 mb-0" style="display:none;">
            El valor ingresado supera tu saldo disponible.
          </div>

          <div class="form-text muted-hint">
            Se descontarán <strong id="restara">0</strong> puntos.
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Nota (opcional)</label>
          <textarea name="note"
                    class="form-control @error('note') is-invalid @enderror"
                    rows="3">{{ old('note') }}</textarea>
          @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button id="submitBtn" class="btn btn-primary btn-mat w-100" type="submit">
          Confirmar consumo
        </button>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const saldo = {{ (int)$saldo }};
  const pointsInput = document.getElementById('pointsInput');
  const warnSaldo = document.getElementById('warnSaldo');
  const submitBtn = document.getElementById('submitBtn');
  const restara = document.getElementById('restara');

  function updateSaldoUI(){
    const val = parseInt(pointsInput.value || '0', 10);
    const safeVal = isNaN(val) ? 0 : val;

    restara.textContent = safeVal.toLocaleString('es-AR');

    const tooMuch = safeVal > saldo;
    warnSaldo.style.display = tooMuch ? 'block' : 'none';

    const invalid = (safeVal < 1 || tooMuch);
    submitBtn.disabled = invalid;
  }

  pointsInput.addEventListener('input', updateSaldoUI);
  updateSaldoUI();
});
</script>
@endpush
@endsection
