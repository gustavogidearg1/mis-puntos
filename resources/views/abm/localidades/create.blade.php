@extends('layouts.app')

@section('title','Nueva Localidad')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0"><i class="bi bi-plus-circle"></i> Nueva Localidad</h3>
    <div class="ms-auto">
      <a href="{{ route('abm.localidades.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
  </div>

  <div class="card-body">
    <form method="POST" action="{{ route('abm.localidades.store') }}" class="row g-3">
      @csrf

      <div class="col-md-6">
        <label class="form-label">Provincia</label>
        <select id="provincia_id" name="provincia_id" class="form-select" required>
          <option value="" disabled {{ old('provincia_id') ? '' : 'selected' }}>Seleccionar...</option>
          @foreach($provincias as $provincia)
            <option
              value="{{ $provincia->id }}"
              data-pais="{{ $provincia->pais?->nombre ?? '' }}"
              @selected(old('provincia_id') == $provincia->id)
            >
              {{ $provincia->nombre }} ({{ $provincia->pais?->nombre ?? '—' }})
            </option>
          @endforeach
        </select>
        @error('provincia_id') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label">País</label>
        <input id="pais_nombre" class="form-control" value="" readonly>
        <div class="form-text">Se completa automáticamente según la provincia.</div>
      </div>

      <div class="col-md-8">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" value="{{ old('nombre') }}" required>
        @error('nombre') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Código Postal</label>
        <input name="cp" class="form-control" value="{{ old('cp') }}" maxlength="12">
        @error('cp') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>

      <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('abm.localidades.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button class="btn btn-primary btn-mat"><i class="bi bi-check2"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function(){
    const sel = document.getElementById('provincia_id');
    const paisInput = document.getElementById('pais_nombre');

    function syncPais(){
      const opt = sel.options[sel.selectedIndex];
      paisInput.value = opt ? (opt.getAttribute('data-pais') || '') : '';
    }

    sel.addEventListener('change', syncPais);

    // inicializar si viene old('provincia_id')
    syncPais();
  })();
</script>
@endsection
