@extends('layouts.app')

@section('title','Provincias')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0">
      <i class="bi bi-map"></i> Provincias
    </h3>

    <div class="ms-auto d-flex gap-2">
      <form class="d-flex" method="GET">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Buscar provincia o país..." value="{{ $q ?? '' }}">
      </form>

      <a href="{{ route('abm.provincias.create') }}" class="btn btn-primary btn-mat btn-sm">
        <i class="bi bi-plus-lg"></i> Nuevo
      </a>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Provincia</th>
            <th>País</th>
            <th class="text-end" style="width: 180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($provincias as $provincia)
            <tr>
              <td class="fw-semibold">{{ $provincia->nombre }}</td>
              <td>{{ $provincia->pais?->nombre ?? '—' }}</td>
              <td class="text-end">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('abm.provincias.show',$provincia) }}">Ver</a>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('abm.provincias.edit',$provincia) }}">Editar</a>
                <form action="{{ route('abm.provincias.destroy',$provincia) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Eliminar provincia?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">Eliminar</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-center text-muted py-4">Sin registros</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $provincias->links() }}
    </div>
  </div>
</div>
@endsection
