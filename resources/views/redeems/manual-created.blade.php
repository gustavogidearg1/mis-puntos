@extends('layouts.app')
@section('title','Consumo realizado')

@section('content')
<style>
  @media print{
    .topbar, .right-sidebar, .sidebar-nav { display:none !important; }
    .btn, .no-print { display:none !important; }
    .app-shell{ display:block !important; }
    .app-content{ padding:0 !important; }
    .card{ box-shadow:none !important; }
  }
  .qr-box{
    background:#fff;
    border:1px solid rgba(0,0,0,.08);
    border-radius:16px;
    padding:16px;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  }
</style>

<div class="container py-3">
  <div class="card mat-card">
    <div class="mat-header">
      <h3 class="mat-title mb-0">
        <i class="bi bi-check2-circle me-2"></i> Consumo realizado (Comprobante)
      </h3>

      <div class="ms-auto d-flex gap-2 no-print">
        <a href="{{ route('redeems.manual.create') }}" class="btn btn-outline-secondary btn-mat">
          <i class="bi bi-arrow-left"></i> Volver
        </a>

        <button type="button" class="btn btn-outline-primary btn-mat" onclick="window.print()">
          <i class="bi bi-printer"></i> Imprimir
        </button>
      </div>
    </div>

    <div class="card-body p-4">
      <div class="row g-4">
        <div class="col-12 col-lg-5">
          <div class="qr-box">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(260)->margin(1)->generate($url) !!}
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold mb-1">Código / Token</label>
            <div class="input-group">
              <input id="tokenInput" class="form-control mono" value="{{ $redemption->token }}" readonly>
              <button class="btn btn-outline-secondary" type="button" onclick="copyText('tokenInput')">
                <i class="bi bi-clipboard"></i> Copiar
              </button>
            </div>
            <div class="form-text text-muted">
              Este token identifica el comprobante del consumo.
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold mb-1">Link del comprobante</label>
            <div class="input-group">
              <input id="urlInput" class="form-control" value="{{ $url }}" readonly>
              <button class="btn btn-outline-secondary" type="button" onclick="copyText('urlInput')">
                <i class="bi bi-clipboard"></i> Copiar
              </button>
            </div>
          </div>

          @php
            $whatsText = "Comprobante de consumo de puntos.\n\n".
                         "Negocio: ".($business->name ?? '—')."\n".
                         "Empleado: ".($employee->name ?? '—')."\n".
                         "Puntos: ".number_format($redemption->points)."\n".
                         "Fecha: ".optional($movement->occurred_at)->format('d/m/Y H:i')."\n\n".
                         "Ver comprobante:\n".$url."\n\n".
                         "Código: ".$redemption->token;
            $waUrl = 'https://wa.me/?text='.urlencode($whatsText);
          @endphp

          <div class="mt-3 d-flex gap-2 no-print">
            <a class="btn btn-success btn-mat w-100" href="{{ $waUrl }}" target="_blank" rel="noopener">
              <i class="bi bi-whatsapp"></i> Compartir WhatsApp
            </a>
          </div>
        </div>

        <div class="col-12 col-lg-7">
          <div class="card mat-card">
            <div class="mat-header">
              <h3 class="mat-title mb-0"><i class="bi bi-info-circle me-2"></i>Detalle</h3>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-4">Negocio</dt>
                <dd class="col-sm-8">{{ $business->name ?? '—' }}</dd>

                <dt class="col-sm-4">Empleado</dt>
                <dd class="col-sm-8">{{ $employee->name ?? '—' }}</dd>

                <dt class="col-sm-4">Puntos</dt>
                <dd class="col-sm-8"><strong>{{ number_format($redemption->points) }}</strong></dd>

                <dt class="col-sm-4">Fecha</dt>
                <dd class="col-sm-8">
                  {{ optional($movement->occurred_at)->format('d/m/Y H:i') ?? '—' }}
                </dd>

                <dt class="col-sm-4">Referencia</dt>
                <dd class="col-sm-8">
                  <span class="badge bg-light text-dark">{{ $movement->reference ?? '—' }}</span>
                </dd>

                <dt class="col-sm-4">Nota</dt>
                <dd class="col-sm-8">{{ $movement->note ?? '—' }}</dd>
              </dl>

              <div class="alert alert-success mt-3 mb-0">
                El consumo <strong>ya fue descontado</strong> del saldo del empleado.
              </div>
            </div>
          </div>

          {{-- Opcional: link a movimientos del empleado --}}
          <div class="mt-3 no-print">
            <a class="btn btn-outline-secondary btn-mat" href="{{ route('points.index') }}">
              <i class="bi bi-wallet2"></i> Ver movimientos
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function copyText(id){
  const el = document.getElementById(id);
  if(!el) return;
  el.select();
  el.setSelectionRange(0, 99999);
  navigator.clipboard?.writeText(el.value);
}
</script>
@endsection
