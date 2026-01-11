@extends('layouts.app')

@section('title','Localidades')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0">
      <i class="bi bi-geo-alt"></i> Localidades
    </h3>

    <div class="ms-auto d-flex gap-2">
      <form class="d-flex" method="GET">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Buscar localidad, CP, provincia o país..." value="{{ $q ?? '' }}">
      </form>

      <a href="{{ route('abm.localidades.create') }}" class="btn btn-primary btn-mat btn-sm">
        <i class="bi bi-plus-lg"></i> Nuevo
      </a>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Localidad</th>
            <th class="text-center">CP</th>
            <th>Provincia</th>
            <th>País</th>
            <th class="text-end" style="width: 180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($localidades as $localidad)
            <tr>
              <td class="fw-semibold">{{ $localidad->nombre }}</td>
              <td class="text-center">{{ $localidad->cp ?? '—' }}</td>
              <td>{{ $localidad->provincia?->nombre ?? '—' }}</td>
              <td>{{ $localidad->provincia?->pais?->nombre ?? '—' }}</td>
              <td class="text-end">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('abm.localidades.show',$localidad) }}">Ver</a>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('abm.localidades.edit',$localidad) }}">Editar</a>
                <form action="{{ route('abm.localidades.destroy',$localidad) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Eliminar localidad?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">Eliminar</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Sin registros</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $localidades->links() }}
    </div>
  </div>
</div>
@endsection
