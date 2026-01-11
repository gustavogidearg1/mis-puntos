@extends('layouts.app')

@section('title','Paises')

@section('content')
<div class="card mat-card">
  <div class="mat-header">
    <h3 class="mat-title mb-0">
      <i class="bi bi-globe"></i> Paises
    </h3>

    <div class="ms-auto d-flex gap-2">
      <form class="d-flex" method="GET">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Buscar..." value="{{ $q ?? '' }}">
      </form>

      <a href="{{ route('abm.paises.create') }}" class="btn btn-primary btn-mat btn-sm">
        <i class="bi bi-plus-lg"></i> Nuevo
      </a>
    </div>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Nombre</th>
            <th class="text-end" style="width: 160px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($paises as $pais)
            <tr>
              <td class="fw-semibold">{{ $pais->nombre }}</td>
              <td class="text-end">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('abm.paises.show',$pais) }}">
                  Ver
                </a>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('abm.paises.edit',$pais) }}">
                  Editar
                </a>
                <form action="{{ route('abm.paises.destroy',$pais) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Eliminar país?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">Eliminar</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Sin registros</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $paises->links() }}
    </div>
  </div>
</div>
@endsection
