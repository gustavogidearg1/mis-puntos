<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\PointMovement;
use App\Models\PointReference;
use App\Models\PointImportBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PointsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /* =========================================================
     * INDEX
     * - empleado: ve MIS puntos (points.empleado-view)
     * - admin_empresa: ve movimientos de su empresa (points.index)
     * - admin_sitio: ve todos (points.index)
     * ========================================================= */
    public function index(Request $r)
    {
        $u = $r->user();

        $isSiteAdmin    = $u->hasRole('admin_sitio');
        $isCompanyAdmin = $u->hasRole('admin_empresa');
        $isEmployee     = $u->hasRole('empleado');

        // bloquear negocio u otros roles
        if (!$isSiteAdmin && !$isCompanyAdmin && !$isEmployee) {
            return redirect()->route('dashboard')->with('error', 'No tiene permisos para ver puntos.');
        }

        $hasVoided = Schema::hasColumn('point_movements', 'voided_at');

        // =========================================
        // EMPLEADO -> "Mis puntos"
        // =========================================
        if ($isEmployee && !$isSiteAdmin && !$isCompanyAdmin) {
            $q = PointMovement::query()
                ->where('employee_user_id', $u->id)
                ->with([
                    'business:id,name',
                    'company:id,name',
                    'pointReference:id,name',
                ])
                ->orderByDesc('occurred_at')
                ->orderByDesc('id');

            if ($hasVoided) {
                // no filtramos anulados para empleado, los mostramos (tu vista marca ANULADO)
            }

            $per = (int)($r->get('per', 25));
            if (!in_array($per, [15, 25, 50, 100], true)) $per = 25;

            $points = $q->paginate($per)->withQueryString();

            $totals = $this->computeTotalsForEmployee($u->id, $hasVoided);

            return view('points.empleado-view', [
                'points' => $points,
                'totals' => $totals,
            ]);
        }

        // =========================================
        // ADMINS -> listado general con filtros
        // =========================================
        $q = PointMovement::query()
            ->with([
                'employee:id,name,email,company_id',
                'business:id,name',
                'company:id,name',
                'createdBy:id,name',
                'confirmedBy:id,name',
                'batch:id,filename,rows_total',
            ]);

        // Scope por rol
        if ($isCompanyAdmin && !$isSiteAdmin) {
            $q->where('company_id', $u->company_id);
        }

        // Filtros
        if ($term = trim((string)$r->get('q'))) {
            $q->where(function ($qq) use ($term) {
                $qq->whereHas('employee', fn($q2) => $q2->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('business', fn($q2) => $q2->where('name', 'like', "%{$term}%"))
                    ->orWhere('note', 'like', "%{$term}%")
                    ->orWhere('reference', 'like', "%{$term}%");
            });
        }

        if ($r->filled('company_id')) {
            $q->where('company_id', (int)$r->company_id);
        }

        if ($r->filled('employee_id')) {
            $q->where('employee_user_id', (int)$r->employee_id);
        }

        if ($r->filled('type') && $r->type !== 'all') {
            $q->where('type', $r->type);
        }

        if ($r->filled('batch_id')) {
            $q->where('batch_id', (int)$r->batch_id);
        }

        // fechas
        if ($r->filled('start_date')) {
            $q->whereDate('occurred_at', '>=', $r->start_date);
        }
        if ($r->filled('end_date')) {
            $q->whereDate('occurred_at', '<=', $r->end_date);
        }

        $per = (int)($r->get('per', 15));
        if (!in_array($per, [15, 25, 50, 100], true)) $per = 15;

        $q->orderByDesc('occurred_at')->orderByDesc('id');

        $points = $q->paginate($per)->withQueryString();

        // Combos (admins)
        $employees = User::query()
            ->whereHas('roles', fn($qq) => $qq->where('name', 'empleado'))
            ->when(!$isSiteAdmin, fn($qq) => $qq->where('company_id', $u->company_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $companies = $isSiteAdmin
            ? Company::orderBy('name')->get(['id', 'name'])
            : collect();

        $types = ['earn', 'redeem', 'adjust', 'expire'];

        $batches = PointImportBatch::query()
            ->when(!$isSiteAdmin, fn($qq) => $qq->where('company_id', $u->company_id))
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'filename', 'rows_total']);

        // Stats rápidos (sobre la consulta filtrada SIN paginar)
        $stats = $this->computeStatsForQuery(clone $q);

        return view('points.index', [
            'points'        => $points,
            'q'             => $term ?? '',
            'employees'     => $employees,
            'companies'     => $companies,
            'types'         => $types,
            'batches'       => $batches,
            'stats'         => $stats,
            'isSiteAdmin'   => $isSiteAdmin,
            'isCompanyAdmin' => $isCompanyAdmin,
        ]);
    }

    /* =========================================================
     * SUMMARY / RESUMEN (admins)
     * ========================================================= */
    public function summary(Request $r)
    {
        $u = $r->user();

        abort_unless($u->hasRole('admin_sitio') || $u->hasRole('admin_empresa'), 403);

        $isSiteAdmin = $u->hasRole('admin_sitio');
        $companyId   = $isSiteAdmin ? ($r->company_id ?: null) : ($u->company_id ?? null);

        $hasVoided = Schema::hasColumn('point_movements', 'voided_at');

        // Base empleados
        $employeesQ = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'empleado'))
            ->with('company:id,name');

        if (!$isSiteAdmin) {
            $employeesQ->where('company_id', $companyId);
        } elseif (!empty($companyId)) {
            $employeesQ->where('company_id', $companyId);
        }

        // Subquery movimientos agrupados por empleado
        $movQ = PointMovement::query()
            ->selectRaw('employee_user_id,
                SUM(CASE WHEN type="earn" THEN points ELSE 0 END) as total_earned,
                SUM(CASE WHEN type="redeem" THEN points ELSE 0 END) as total_redeemed,
                COUNT(*) as movement_count
            ')
            ->groupBy('employee_user_id');

        if ($hasVoided) {
            $movQ->whereNull('voided_at');
        }

        if (!$isSiteAdmin && $companyId) {
            $movQ->where('company_id', $companyId);
        } elseif ($isSiteAdmin && $companyId) {
            $movQ->where('company_id', $companyId);
        }

        // Join subquery a empleados
        $summaryQ = $employeesQ
            ->leftJoinSub($movQ, 'm', function ($join) {
                $join->on('users.id', '=', 'm.employee_user_id');
            })
            ->addSelect([
                'users.id',
                'users.name',
                'users.email',
                'users.cuil',
                'users.company_id',
                DB::raw('COALESCE(m.total_earned,0) as total_earned'),
                DB::raw('COALESCE(m.total_redeemed,0) as total_redeemed'),
                DB::raw('(COALESCE(m.total_earned,0) - COALESCE(m.total_redeemed,0)) as total_available'),
                DB::raw('COALESCE(m.movement_count,0) as movement_count'),
            ])
            ->orderByDesc('total_available')
            ->orderBy('users.name');

        $summary = $summaryQ->paginate(25)->withQueryString();

        // Totales generales
        $overallTotals = [
            'total_employees'        => (clone $employeesQ)->count(),
            'total_points_earned'    => 0,
            'total_points_redeemed'  => 0,
            'total_points_available' => 0,
        ];

        $overallMov = PointMovement::query();
        if ($hasVoided) $overallMov->whereNull('voided_at');

        if (!$isSiteAdmin && $companyId) {
            $overallMov->where('company_id', $companyId);
        } elseif ($isSiteAdmin && $companyId) {
            $overallMov->where('company_id', $companyId);
        }

        $overallTotals['total_points_earned']   = (int)(clone $overallMov)->where('type', 'earn')->sum('points');
        $overallTotals['total_points_redeemed'] = (int)(clone $overallMov)->where('type', 'redeem')->sum('points');
        $overallTotals['total_points_available'] = (int)$overallTotals['total_points_earned'] - (int)$overallTotals['total_points_redeemed'];

        $companies = $isSiteAdmin ? Company::orderBy('name')->get(['id', 'name']) : collect();

        return view('points.resumen', [
            'summary'       => $summary,
            'overallTotals' => $overallTotals,
            'companies'     => $companies,
            'isSiteAdmin'   => $isSiteAdmin,
            'companyId'     => $companyId,
        ]);
    }

    /* =========================================================
     * CREATE (admins)
     * ========================================================= */
    public function create(Request $r)
    {
        $u = $r->user();

        abort_unless($u->hasRole('admin_sitio') || $u->hasRole('admin_empresa'), 403);

        $isSiteAdmin = $u->hasRole('admin_sitio');
        $companyId   = $isSiteAdmin ? null : ($u->company_id ?? null);

        $employeesQ = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'empleado'))
            ->with('company:id,name');

        if (!$isSiteAdmin && $companyId) {
            $employeesQ->where('company_id', $companyId);
        }

        $employees = $employeesQ->orderBy('name')->get();

        $companies = $isSiteAdmin
            ? Company::query()->orderBy('name')->get()
            : collect();

        // Referencias activas (globales + de la empresa)
        $references = PointReference::query()
            ->where('is_active', true)
            ->where(function ($qq) use ($companyId, $isSiteAdmin) {
                $qq->whereNull('company_id');
                if (!$isSiteAdmin && $companyId) $qq->orWhere('company_id', $companyId);
                if ($isSiteAdmin && $companyId)  $qq->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $types = [
            'earn'   => 'Acreditación',
            'redeem' => 'Canje / Consumo',
            'adjust' => 'Ajuste',
            'expire' => 'Vencimiento',
        ];

        return view('points.crear', [
            'employees'   => $employees,
            'companies'   => $companies,
            'companyId'   => $companyId,
            'isSiteAdmin' => $isSiteAdmin,
            'types'       => $types,
            'references'  => $references,
        ]);
    }

    /* =========================================================
     * STORE (admins)
     * ========================================================= */
    public function store(Request $r)
    {
        $u = $r->user();

        abort_unless($u->hasRole('admin_sitio') || $u->hasRole('admin_empresa'), 403);

        $isSiteAdmin = $u->hasRole('admin_sitio');
        $companyId   = $isSiteAdmin ? null : ($u->company_id ?? null);

        $data = $r->validate([
            'company_id'        => ['nullable', 'integer', 'exists:companies,id'], // solo admin_sitio
            'employee_user_id'  => ['required', 'integer', 'exists:users,id'],
            'type'              => ['required', 'in:earn,redeem,adjust,expire'],
            'points'            => ['required', 'integer', 'min:1', 'max:1000000'],
            'occurred_at'       => ['nullable', 'date'],

            // 👇 En tu BD NO existe reference_id, así que trabajamos con reference (texto)
            // Si tu form manda "reference" como ID, lo convertimos abajo.
            'reference'         => ['nullable'], // puede ser string o id
            'note'              => ['nullable', 'string', 'max:500'],
        ]);

        // Buscar empleado y validar pertenencia
        $employee = User::query()
            ->whereKey($data['employee_user_id'])
            ->whereHas('roles', fn($q) => $q->where('name', 'empleado'))
            ->firstOrFail();

        // Si admin_empresa: solo su empresa
        if (!$isSiteAdmin) {
            abort_unless((int)$employee->company_id === (int)$companyId, 403);
        }

        // Company final
        $finalCompanyId = $isSiteAdmin
            ? ((int)($data['company_id'] ?? 0) ?: (int)$employee->company_id)
            : (int)$employee->company_id;

        // occurred_at
$occurredAt = $data['occurred_at']
    ? Carbon::parse($data['occurred_at'])
    : now();

        // reference: puede venir como texto o como ID (si tu select envía ID)
        $refValue = $data['reference'] ?? null;
        $refText = null;

        if ($refValue !== null && $refValue !== '') {
            // Si es numérico, asumimos que es ID de point_references y lo traducimos a name
            if (is_numeric($refValue)) {
                $refText = PointReference::query()->whereKey((int)$refValue)->value('name');
            } else {
                $refText = trim((string)$refValue);
            }
        }

        DB::transaction(function () use ($u, $employee, $finalCompanyId, $occurredAt, $data, $refText) {
            $m = new PointMovement();

            $m->company_id       = $finalCompanyId;
            $m->employee_user_id = $employee->id;

            // manual: normalmente sin negocio
            $m->business_user_id = null;

            $m->type        = $data['type'];
            $m->points      = (int)$data['points'];
            $m->occurred_at = $occurredAt;

            // ✅ solo texto
            $m->reference   = $refText;
            $m->note        = $data['note'] ?? null;

            // auditoría (tu campo real)
            $m->created_by  = $u->id;

            $m->save();
        });

        return redirect()->route('points.index')->with('ok', 'Movimiento guardado.');
    }

    /* =========================================================
     * EMPLOYEE DETAIL (admins)
     * ========================================================= */
    public function employeeDetail(Request $r, User $employee)
    {
        $u = $r->user();

        abort_unless($u->hasRole('admin_sitio') || $u->hasRole('admin_empresa'), 403);

        $isSiteAdmin = $u->hasRole('admin_sitio');
        $hasVoided   = Schema::hasColumn('point_movements', 'voided_at');

        abort_unless($employee->hasRole('empleado'), 404);

        if (!$isSiteAdmin) {
            abort_unless((int)$employee->company_id === (int)($u->company_id ?? 0), 403);
        }

        $q = PointMovement::query()
            ->where('employee_user_id', $employee->id)
            ->with([
                'business:id,name',
                'createdBy:id,name',
                'pointReference:id,name',
                'company:id,name',
            ]);

        if ($hasVoided) {
            $q->whereNull('voided_at');
        }

        if ($r->filled('type')) {
            $q->where('type', $r->type);
        }
        if ($r->filled('start_date')) {
            $q->whereDate('occurred_at', '>=', $r->start_date);
        }
        if ($r->filled('end_date')) {
            $q->whereDate('occurred_at', '<=', $r->end_date);
        }

        $q->orderByDesc('occurred_at')->orderByDesc('id');

        $points = $q->paginate(25)->withQueryString();

        $totals = $this->computeTotalsForEmployee($employee->id, $hasVoided);

        return view('points.empleado-detalle', [
            'employee' => $employee->load('company:id,name'),
            'points'   => $points,
            'totals'   => $totals,
        ]);
    }

    /* =========================================================
     * EXPORT (admins) - CSV
     * ========================================================= */
    public function export(Request $r): StreamedResponse
    {
        $u = $r->user();

        abort_unless($u->hasRole('admin_sitio') || $u->hasRole('admin_empresa'), 403);

        $isSiteAdmin = $u->hasRole('admin_sitio');
        $hasVoided   = Schema::hasColumn('point_movements', 'voided_at');

        $q = PointMovement::query()
            ->with([
                'employee:id,name,email,company_id',
                'business:id,name',
                'company:id,name',
                'createdBy:id,name',
                'confirmedBy:id,name',
                'pointReference:id,name',
            ])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if (!$isSiteAdmin) {
            $q->where('company_id', $u->company_id);
        }

        if ($hasVoided) {
            $q->whereNull('voided_at');
        }

        $filename = 'points_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($q, $hasVoided) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'ID',
                'Fecha',
                'Empresa',
                'Empleado',
                'Negocio',
                'Tipo',
                'Puntos',
                'Referencia',
                'Nota',
                'Creado por',
                $hasVoided ? 'Anulado' : null,
            ]);

            $q->chunk(500, function ($rows) use ($out, $hasVoided) {
                foreach ($rows as $m) {
                    $ref = $m->pointReference?->name ?? ($m->reference ?? '');

                    $line = [
                        $m->id,
                        optional($m->occurred_at)->format('Y-m-d H:i:s'),
                        $m->company?->name,
                        $m->employee?->name,
                        $m->business?->name,
                        $m->type,
                        $m->points,
                        $ref,
                        $m->note,
                        $m->createdBy?->name,
                    ];

                    if ($hasVoided) {
                        $line[] = $m->voided_at ? 'SI' : 'NO';
                    }

                    // quitar null final si no hay voided
                    $line = array_values(array_filter($line, fn($v) => $v !== null || $hasVoided));

                    fputcsv($out, $line);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /* =========================================================
     * VOID (anular) - opcional
     * Ruta: PATCH /points/{movement}/void
     * ========================================================= */
    public function void(Request $r, PointMovement $movement)
    {
        $u = $r->user();

        $isSiteAdmin    = $u->hasRole('admin_sitio');
        $isCompanyAdmin = $u->hasRole('admin_empresa');

        abort_unless($isSiteAdmin || $isCompanyAdmin, 403);

        // si no existe columna voided_at -> no hay anulación
        if (!Schema::hasColumn('point_movements', 'voided_at')) {
            return back()->with('error', 'Tu tabla no tiene voided_at. Si querés anulación, agregamos migración.');
        }

        if (!$isSiteAdmin) {
            abort_unless((int)$movement->company_id === (int)($u->company_id ?? 0), 403);
        }

        if ($movement->voided_at) {
            return back()->with('error', 'El movimiento ya estaba anulado.');
        }

        $movement->voided_at = now();

        // si existe voided_by en tu tabla, lo usamos
        if (Schema::hasColumn('point_movements', 'voided_by')) {
            $movement->voided_by = $u->id;
        }

        $movement->save();

        return back()->with('ok', 'Movimiento anulado.');
    }

    /* =========================================================
     * Helpers
     * ========================================================= */
    private function computeTotalsForEmployee(int $employeeUserId, bool $hasVoided): array
    {
        $q = PointMovement::query()
            ->where('employee_user_id', $employeeUserId);

        if ($hasVoided) {
            $q->whereNull('voided_at');
        }

        $earned   = (int)(clone $q)->where('type', 'earn')->sum('points');
        $redeemed = (int)(clone $q)->where('type', 'redeem')->sum('points');

        return [
            'total_earned'   => $earned,
            'total_redeemed' => $redeemed,
            'available'      => $earned - $redeemed,
        ];
    }

    private function computeStatsForQuery($q): array
    {
        // Estadísticas sobre query ya filtrada (sin paginar)
        $totalEarned = (int)(clone $q)->where('type', 'earn')->sum('points');
        $totalRedeem = (int)(clone $q)->where('type', 'redeem')->sum('points');

        $totalMovements = (int)(clone $q)->count();

        $totalPoints = $totalEarned - $totalRedeem;

        $avg = $totalMovements > 0 ? ($totalPoints / $totalMovements) : 0;

        return [
            'total_points'     => $totalPoints,
            'total_earned'     => $totalEarned,
            'total_redeemed'   => $totalRedeem,
            'total_movements'  => $totalMovements,
            'avg_points'       => $avg,
        ];
    }
}
