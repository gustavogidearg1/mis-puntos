@extends('layouts.app')

@section('title', 'User - '.$user->name)

@section('content')

@if($user->hasRole('negocio'))
  @php
    $qrUrl = route('redeems.manual.create', ['business' => $user->id]);
  @endphp

  <div class="card mat-card mt-3">
    <div class="mat-header">
      <h3 class="mat-title mb-0">
        <i class="bi bi-qr-code me-2"></i> QR del Negocio: <h3 class="mat-title mb-0"><i class="bi bi-person-badge"></i> {{ $user->name }}</h3>
      </h3>

      <div class="ms-auto d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-mat" onclick="window.print()">
          <i class="bi bi-printer"></i> Imprimir
        </button>
      </div>
    </div>

    <div class="card-body">
      <div class="row g-3 align-items-center">
        <div class="col-12 col-md-4">
          <div class="p-3 bg-white border rounded-4 d-flex justify-content-center">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(220)->margin(1)->generate($qrUrl) !!}
          </div>
        </div>

        <div class="col-12 col-md-8">
          <div class="mb-2">
            <div class="fw-semibold">Link (para copiar / WhatsApp)</div>
            <div class="input-group">
              <input id="qrBusinessUrl" class="form-control" value="{{ $qrUrl }}" readonly>
              <button class="btn btn-outline-secondary" type="button" onclick="copyText('qrBusinessUrl')">
                <i class="bi bi-clipboard"></i> Copiar
              </button>
            </div>
            <div class="form-text text-muted">
              El empleado escanea este QR y se abre la pantalla con este negocio precargado.
            </div>
          </div>

          <div class="alert alert-info mb-0">
            Recomendación: imprimilo y pegalo en la caja del comercio.
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
@endif


<div class="card mat-card mb-3">
  <div class="mat-header">

    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('abm.users.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
      <a href="{{ route('abm.users.edit', $user) }}" class="btn btn-primary btn-mat btn-sm">
        <i class="bi bi-pencil"></i> Edit
      </a>
    </div>
  </div>

  <div class="card-body">
    <div class="row g-3 align-items-start">
      <div class="col-md-3">
        @if($user->imagen)
          <img src="{{ Storage::url($user->imagen) }}" class="img-fluid rounded-3 shadow-sm" alt="User image">
        @else
          <div class="border rounded-3 p-4 text-center text-muted">
            <i class="bi bi-image" style="font-size:2rem;"></i>
            <div class="small mt-2">No image</div>
          </div>
        @endif
      </div>

      <div class="col-md-9">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted small">Email</div>
            <div class="fw-semibold">{{ $user->email }}</div>
          </div>

          <div class="col-md-6">
            <div class="text-muted small">Company</div>
            <div class="fw-semibold">{{ $user->company?->name ?? '—' }}</div>
          </div>

          <div class="col-md-4">
            <div class="text-muted small">CUIL</div>
            <div class="fw-semibold">{{ $user->cuil ?? '—' }}</div>
          </div>

          <div class="col-md-8">
            <div class="text-muted small">Address</div>
            <div class="fw-semibold">{{ $user->direccion ?? '—' }}</div>
          </div>

          <div class="col-md-4">
            <div class="text-muted small">Birth date</div>
            <div class="fw-semibold">{{ $user->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</div>
          </div>

          <div class="col-md-4">
            <div class="text-muted small">Country</div>
            <div class="fw-semibold">{{ $user->pais?->nombre ?? '—' }}</div>
          </div>

          <div class="col-md-4">
            <div class="text-muted small">Province</div>
            <div class="fw-semibold">{{ $user->provincia?->nombre ?? '—' }}</div>
          </div>

          <div class="col-md-4">
            <div class="text-muted small">Locality</div>
            <div class="fw-semibold">
              {{ $user->localidad?->nombre ?? '—' }}
              @if($user->localidad?->cp)
                <span class="text-muted">({{ $user->localidad->cp }})</span>
              @endif
            </div>
          </div>

          <div class="col-md-4">
            <div class="text-muted small">Active</div>
            @if($user->activo)
              <span class="badge text-bg-success">Yes</span>
            @else
              <span class="badge text-bg-secondary">No</span>
            @endif
          </div>

          <div class="col-md-8">
            <div class="text-muted small">Roles</div>
            <div>
              @forelse($user->roles as $role)
                <span class="badge text-bg-secondary me-1">{{ $role->name }}</span>
              @empty
                <span class="text-muted">—</span>
              @endforelse
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection
