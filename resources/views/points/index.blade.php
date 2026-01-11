{{-- resources/views/points/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Gestión de puntos')

@section('content')

<style>
    .mat-sort {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        cursor: pointer;
        text-decoration: none;
    }
    .mat-sort .sort-icon { font-size: .9rem; opacity: .6; }
    .mat-sort.active { font-weight: 600; }

    .points-positive { color: #198754; font-weight: 600; }
    .points-negative { color: #dc3545; font-weight: 600; }
    .points-neutral  { color: #6c757d; }

    .badge-points { font-size: 0.9em; padding: 0.25em 0.6em; }
    .badge-earn   { background-color: #d1e7dd; color: #0f5132; }
    .badge-redeem { background-color: #f8d7da; color: #842029; }
    .badge-adjust { background-color: #cff4fc; color: #055160; }
    .badge-expire { background-color: #e2e3e5; color: #41464b; }

    .summary-card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: transform 0.2s;
    }
    .summary-card:hover { transform: translateY(-2px); }
    .summary-icon { font-size: 2rem; opacity: 0.8; }
    .stats-number { font-size: 1.8rem; font-weight: bold; margin-bottom: 0.25rem; }
    .stats-label  { font-size: 0.9rem; color: #6c757d; }

    .type-badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50rem;
        display: inline-block;
    }
</style>

<div class="card mat-card">
    <div class="mat-header">
        <h3 class="mat-title mb-0"><i class="bi bi-trophy"></i> Gestión de puntos</h3>

        <div class="ms-auto d-flex gap-2">
            @if($isSiteAdmin || $isCompanyAdmin)
                <a href="{{ route('points.create') }}" class="btn btn-primary btn-mat">
                    <i class="bi bi-plus-lg"></i> Crear movimiento
                </a>

                <a href="{{ route('points.resumen') }}" class="btn btn-outline-secondary btn-mat">
                    <i class="bi bi-bar-chart"></i> Resumen
                </a>

                <a href="{{ route('points.export', request()->all()) }}" class="btn btn-outline-success btn-mat">
                    <i class="bi bi-download"></i> Exportar CSV
                </a>
            @endif
        </div>
    </div>

    <div class="card-body">
        {{-- Estadísticas rápidas --}}
        @if(isset($stats) && ($isSiteAdmin || $isCompanyAdmin))
            <div class="row mb-4">
                <div class="col-md-2 col-6 mb-3">
                    <div class="card summary-card h-100">
                        <div class="card-body text-center">
                            <div class="text-primary"><i class="bi bi-arrow-up-circle summary-icon"></i></div>
                            <div class="stats-number">{{ number_format($stats['total_earned']) }}</div>
                            <div class="stats-label">Puntos ganados</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2 col-6 mb-3">
                    <div class="card summary-card h-100">
                        <div class="card-body text-center">
                            <div class="text-danger"><i class="bi bi-arrow-down-circle summary-icon"></i></div>
                            <div class="stats-number">{{ number_format($stats['total_redeemed']) }}</div>
                            <div class="stats-label">Puntos canjeados</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2 col-6 mb-3">
                    <div class="card summary-card h-100">
                        <div class="card-body text-center">
                            <div class="text-success"><i class="bi bi-wallet2 summary-icon"></i></div>
                            <div class="stats-number">{{ number_format($stats['total_points']) }}</div>
                            <div class="stats-label">Puntos totales</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2 col-6 mb-3">
                    <div class="card summary-card h-100">
                        <div class="card-body text-center">
                            <div class="text-info"><i class="bi bi-list-ul summary-icon"></i></div>
                            <div class="stats-number">{{ number_format($stats['total_movements']) }}</div>
                            <div class="stats-label">Movimientos totales</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2 col-6 mb-3">
                    <div class="card summary-card h-100">
                        <div class="card-body text-center">
                            <div class="text-warning"><i class="bi bi-graph-up summary-icon"></i></div>
                            <div class="stats-number">{{ number_format($stats['avg_points'] ?? 0, 1) }}</div>
                            <div class="stats-label">Promedio por movimiento</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Filtros --}}
        @if($isSiteAdmin || $isCompanyAdmin)
            <form method="GET" class="row g-3 mb-4" action="{{ route('points.index') }}">
                <div class="col-12 col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                           placeholder="empleado, negocio, referencia, notas...">
                </div>

                @if($isSiteAdmin)
                    <div class="col-12 col-md-2">
                        <label class="form-label">Compañía</label>
                        <select name="company_id" class="form-select">
                            <option value="">Todas las empresas</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-12 col-md-2">
                    <label class="form-label">Empleado</label>
                    <select name="employee_id" class="form-select">
                        <option value="">Todos los empleados</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select">
                        <option value="all">Todos los tipos</option>
                        @foreach($types as $typeName)
                            <option value="{{ $typeName }}" @selected(request('type') == $typeName)>
                                {{ ucfirst($typeName) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label">Lote</label>
                    <select name="batch_id" class="form-select">
                        <option value="">Todos los lotes</option>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" @selected(request('batch_id') == $batch->id)>
                                {{ $batch->filename }} ({{ $batch->rows_total }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">Fecha de inicio</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">Fecha de finalización</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label">Por página</label>
                    <select name="per" class="form-select">
                        @foreach([15, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" @selected(request('per', 15) == $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex gap-2 align-items-end">
                    <button class="btn btn-primary btn-mat" type="submit">
                        <i class="bi bi-funnel"></i> Aplicar filtros
                    </button>

                    @if(request()->anyFilled(['q', 'company_id', 'employee_id', 'type', 'batch_id', 'start_date', 'end_date']))
                        <a href="{{ route('points.index') }}" class="btn btn-outline-secondary btn-mat">
                            <i class="bi bi-x-lg"></i> Limpiar
                        </a>
                    @endif
                </div>
            </form>
        @endif

        {{-- Tabla --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>

                        @if($isSiteAdmin || $isCompanyAdmin)
                            <th>Empleado</th>
                            <th>Negocio</th>
                            <th>Confirmado por</th>
                        @endif

                        <th>Tipo</th>
                        <th class="text-end">Puntos</th>
                        <th>Referencia</th>
                        <th>Notas</th>

                        @if($isSiteAdmin || $isCompanyAdmin)
                            <th>Creado por</th>
                            <th>Lote</th>
                        @endif

                        <th class="text-end">Acción</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($points as $point)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $point->occurred_at?->format('Y-m-d') }}</div>
                                <small class="text-muted">{{ $point->occurred_at?->format('H:i') }}</small>
                            </td>

                            @if($isSiteAdmin || $isCompanyAdmin)
                                <td>
                                    <div class="fw-semibold">{{ $point->employee->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $point->employee->cuil ?? '—' }}</small>
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $point->business->name ?? '—' }}</div>
                                    <small class="text-muted">
                                        {{ $point->business_user_id ? 'Consumo en negocio' : '—' }}
                                    </small>
                                </td>

                                <td>
                                    <small>{{ $point->confirmedBy->name ?? '—' }}</small>
                                </td>
                            @endif

                            <td>
                                @php
                                    $typeClass = [
                                        'earn' => 'badge-earn',
                                        'redeem' => 'badge-redeem',
                                        'adjust' => 'badge-adjust',
                                        'expire' => 'badge-expire',
                                    ][$point->type] ?? 'badge-secondary';
                                @endphp

                                <span class="type-badge {{ $typeClass }}">
                                    {{ ucfirst($point->type) }}
                                </span>
                            </td>

                            <td class="text-end">
                                <span class="{{ $point->points >= 0 ? 'points-positive' : 'points-negative' }}">
                                    {{ $point->points >= 0 ? '+' : '' }}{{ number_format($point->points) }}
                                </span>

                                @if($point->money_amount)
                                    <div class="small text-muted">
                                        ${{ number_format($point->money_amount, 2) }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if($point->reference)
                                    <span class="badge bg-light text-dark">{{ $point->reference }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if($point->note)
                                    <span title="{{ $point->note }}">
                                        {{ \Illuminate\Support\Str::limit($point->note, 30) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            @if($isSiteAdmin || $isCompanyAdmin)
                                <td>
                                    <small>{{ $point->createdBy->name ?? '—' }}</small>
                                </td>

                                <td>
                                    @if($point->batch)
                                        <small class="text-muted" title="{{ $point->batch->filename }}">
                                            {{ \Illuminate\Support\Str::limit(basename($point->batch->filename), 20) }}
                                        </small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif

                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    @if($isSiteAdmin || $isCompanyAdmin)
                                        <a href="{{ route('points.employee.detail', $point->employee_user_id) }}"
                                           class="btn btn-outline-secondary" title="Ver empleado">
                                            <i class="bi bi-person"></i>
                                        </a>
                                    @endif
                                    {{-- Acciones futuras: editar / eliminar --}}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($isSiteAdmin || $isCompanyAdmin) ? 11 : 6 }}"
                                class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <div class="mt-2">No se encontraron movimientos</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="mt-3 d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Mostrando {{ $points->firstItem() ?? 0 }}–{{ $points->lastItem() ?? 0 }}
                de {{ $points->total() }} movimientos
            </small>
            {{ $points->withQueryString()->links() }}
        </div>
    </div>
</div>

@if($isSiteAdmin || $isCompanyAdmin)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate   = document.querySelector('input[name="end_date"]');

    if (startDate && endDate) {
        startDate.addEventListener('change', function() {
            if (!endDate.value || endDate.value < this.value) {
                endDate.value = this.value;
            }
        });
    }
});
</script>
@endif

@endsection
