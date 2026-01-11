<?php

namespace App\Http\Controllers;

use App\Models\PointMovement;
use App\Models\User;
use App\Models\Company;
use App\Models\PointImportBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Notifications\MovimientoPuntosCreado;


class PointsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Índice principal de puntos con filtros
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSiteAdmin = $user->hasRole('admin_sitio');
        $isCompanyAdmin = $user->hasRole('admin_empresa');

        // Determinar qué puede ver cada usuario
        if ($user->hasRole('empleado')) {
            // Empleado solo ve sus propios puntos
            return $this->employeeView($user);
        }

        // Obtener parámetros de filtro
        $search = $request->input('q');
        $companyId = $request->input('company_id');
        $employeeId = $request->input('employee_id');
        $type = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $batchId = $request->input('batch_id');
        $perPage = $request->input('per', 15);

        // Construir consulta base con relaciones
        $query = PointMovement::with([
            'employee:id,name,email,cuil',
            'business:id,name',
            'createdBy:id,name',
            'company:id,name',
            'batch:id,filename,created_at'
        ]);

        // Restricciones según rol
        if ($isSiteAdmin) {
            // Admin del sitio ve todo
        } elseif ($isCompanyAdmin) {
            // Admin de empresa solo ve su empresa
            $query->where('company_id', $user->company_id);
        } else {
            // Otros usuarios (si no son empleado) redirigir
            return redirect()->route('dashboard')
                ->with('error', 'No tiene permisos para ver puntos.');
        }

        // Aplicar filtros
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('cuil', 'like', "%{$search}%");
                })
                ->orWhereHas('business', function ($businessQuery) use ($search) {
                    $businessQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhere('note', 'like', "%{$search}%");
            });
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($employeeId) {
            $query->where('employee_user_id', $employeeId);
        }

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        if ($startDate) {
            $query->whereDate('occurred_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('occurred_at', '<=', $endDate);
        }

        // Ordenar por fecha más reciente primero
        $query->orderBy('occurred_at', 'desc');

        // Obtener datos para filtros
        $companies = $isSiteAdmin
            ? Company::orderBy('name')->get(['id', 'name'])
            : collect();

        $employees = User::query()
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when(!$isSiteAdmin && $isCompanyAdmin, function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            })
            ->whereHas('roles', function ($q) {
                $q->where('name', 'empleado');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'cuil']);

        // Obtener batches para filtro
        $batches = PointImportBatch::query()
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when(!$isSiteAdmin && $isCompanyAdmin, function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            })
            ->orderBy('created_at', 'desc')
            ->get(['id', 'filename', 'created_at', 'rows_total']);

        // Obtener tipos únicos
        $types = PointMovement::select('type')->distinct()->pluck('type');

        // Calcular estadísticas generales
        $stats = $this->getStats(clone $query);

        $points = $query->paginate($perPage)->withQueryString();

        return view('points.index', compact(
            'points',
            'companies',
            'employees',
            'batches',
            'types',
            'stats',
            'isSiteAdmin',
            'isCompanyAdmin',
            'search',
            'companyId',
            'employeeId',
            'type',
            'batchId',
            'startDate',
            'endDate',
            'perPage'
        ));
    }

    /**
 * Formulario para crear un movimiento manual de puntos (solo admins)
 */
public function create(Request $request)
{
    $user = Auth::user();
    $isSiteAdmin = $user->hasRole('admin_sitio');
    $isCompanyAdmin = $user->hasRole('admin_empresa');

    if (!$isSiteAdmin && !$isCompanyAdmin) {
        return redirect()->route('points.index')->with('error', 'No tiene permisos para crear puntos.');
    }

    $companyId = $isCompanyAdmin ? $user->company_id : $request->input('company_id');

    $companies = $isSiteAdmin
        ? Company::orderBy('name')->get(['id', 'name'])
        : collect();

    $employees = User::query()
        ->with('company:id,name')
        ->whereHas('roles', fn($q) => $q->where('name', 'empleado'))
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->when(!$isSiteAdmin && $isCompanyAdmin, fn($q) => $q->where('company_id', $user->company_id))
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'cuil', 'company_id']);

    // Tipos permitidos (en español en UI, pero guardamos los códigos)
    $types = [
        'earn'   => 'Cargar (sumar)',
        'redeem' => 'Canje (restar)',
        'adjust' => 'Ajuste',
    ];

    return view('points.crear', compact(
        'isSiteAdmin',
        'isCompanyAdmin',
        'companies',
        'employees',
        'companyId',
        'types'
    ));
}

/**
 * Guardar movimiento manual de puntos (solo admins)
 */
/**
 * Guardar movimiento manual de puntos (solo admins)
 */
public function store(Request $request)
{
    $user = Auth::user();
    $isSiteAdmin = $user->hasRole('admin_sitio');
    $isCompanyAdmin = $user->hasRole('admin_empresa');

    if (!$isSiteAdmin && !$isCompanyAdmin) {
        return redirect()->route('points.index')->with('error', 'No tiene permisos para crear puntos.');
    }

    $data = $request->validate([
        'company_id'        => ['nullable', 'integer', 'exists:companies,id'],
        'employee_user_id'  => ['required', 'integer', 'exists:users,id'],
        'type'              => ['required', 'in:earn,redeem,adjust'],
        'points'            => ['required', 'integer', 'min:1'],
        'occurred_at'       => ['nullable', 'date'],
        'reference'         => ['nullable', 'string', 'max:120'],
        'note'              => ['nullable', 'string', 'max:500'],
    ], [], [
        'employee_user_id' => 'empleado',
        'type' => 'tipo',
        'points' => 'puntos',
        'occurred_at' => 'fecha',
        'reference' => 'referencia',
        'note' => 'nota',
    ]);

    $employee = User::with('company')->findOrFail($data['employee_user_id']);

    // Seguridad por empresa (admin_empresa solo su empresa)
    if ($isCompanyAdmin && (int)$employee->company_id !== (int)$user->company_id) {
        return back()->withErrors(['employee_user_id' => 'El empleado no pertenece a su empresa.'])->withInput();
    }

    // Determinar company_id
    $companyId = $isCompanyAdmin
        ? $user->company_id
        : ($data['company_id'] ?? $employee->company_id);

    if (!$companyId) {
        return back()->withErrors(['company_id' => 'No se pudo determinar la compañía.'])->withInput();
    }

    // Signo de puntos
    $points = (int) $data['points'];
    if ($data['type'] === 'redeem') {
        $points = -abs($points);
    } elseif ($data['type'] === 'earn') {
        $points = abs($points);
    } else {
        // adjust: lo dejamos positivo (si querés permitir negativo, cambiamos validación y lógica)
        $points = abs($points);
    }

    $occurredAt = $data['occurred_at']
        ? Carbon::parse($data['occurred_at'])
        : now();

    // Crear movimiento manual
    $movimiento = PointMovement::create([
        'company_id'        => $companyId,
        'employee_user_id'  => $employee->id,

        // Manual: no hay negocio asociado
        'business_user_id'  => null,

        // created_by = admin que lo carga
        'created_by'        => $user->id,

        // confirmed_by: para manual no aplica (si tenés el campo, lo dejamos null)
        'confirmed_by'      => null,

        'batch_id'          => null,
        'type'              => $data['type'],
        'points'            => $points,
        'money_amount'      => null,
        'reference'         => $data['reference'] ?? null,
        'note'              => $data['note'] ?? null,
        'occurred_at'       => $occurredAt,
    ]);

    // Notificar por mail al empleado (si tiene email y si NO es el mismo usuario que carga)
    if (!empty($employee->email) && $employee->id !== $user->id) {
        $employee->notify(new MovimientoPuntosCreado($movimiento));
    }

    return redirect()
        ->route('points.index', ['employee_id' => $employee->id])
        ->with('success', 'Movimiento de puntos creado correctamente. Se notificó al empleado por correo.');
}



    /**
     * Vista para empleados (solo sus puntos)
     */
    private function employeeView($user)
    {
 $points = PointMovement::with([
        'business:id,name',
        'createdBy:id,name',
        'confirmedBy:id,name',
        'company:id,name',
        'batch:id,filename'
    ])
    ->where('employee_user_id', $user->id)
    ->orderBy('occurred_at', 'desc')
    ->paginate(15);

        // Calcular totales del empleado
        $totals = [
            'total_earned' => PointMovement::where('employee_user_id', $user->id)
                ->where('type', 'earn')
                ->sum('points'),
            'total_redeemed' => abs(PointMovement::where('employee_user_id', $user->id)
                ->where('type', 'redeem')
                ->sum('points')),
            'available' => PointMovement::where('employee_user_id', $user->id)
                ->sum('points'),
        ];

        return view('points.empleado-view', compact('points', 'totals', 'user'));

    }

    /**
     * Obtener estadísticas de puntos
     */
    private function getStats($query)
    {
        $statsQuery = clone $query;

        return [
            'total_points' => $statsQuery->sum('points'),
            'total_movements' => $statsQuery->count(),
            'total_earned' => $statsQuery->where('type', 'earn')->sum('points'),
            'total_redeemed' => abs($statsQuery->where('type', 'redeem')->sum('points')),
            'avg_points' => $statsQuery->where('type', 'earn')->avg('points'),
        ];
    }

    /**
     * Mostrar resumen de puntos por empleado (dashboard)
     */
    public function summary(Request $request)
    {
        $user = Auth::user();
        $isSiteAdmin = $user->hasRole('admin_sitio');
        $isCompanyAdmin = $user->hasRole('admin_empresa');

        if (!$isSiteAdmin && !$isCompanyAdmin) {
            return redirect()->route('points.index')->with('error', 'No tiene permisos para ver resúmenes.');
        }

        $companyId = $request->input('company_id', $isCompanyAdmin ? $user->company_id : null);

        // Obtener resumen de puntos por empleado
        $summary = User::query()
            ->with(['company:id,name'])
            ->whereHas('roles', function ($q) {
                $q->where('name', 'empleado');
            })
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->when(!$isSiteAdmin && $isCompanyAdmin, function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            })
            ->withCount(['pointMovements as total_earned' => function ($query) {
                $query->select(DB::raw('COALESCE(SUM(points), 0)'))
                    ->where('type', 'earn');
            }])
            ->withCount(['pointMovements as total_redeemed' => function ($query) {
                $query->select(DB::raw('COALESCE(ABS(SUM(points)), 0)'))
                    ->where('type', 'redeem');
            }])
            ->withCount(['pointMovements as total_available' => function ($query) {
                $query->select(DB::raw('COALESCE(SUM(points), 0)'));
            }])
            ->withCount(['pointMovements as movement_count'])
            ->orderBy('name')
            ->paginate(20);

        // Calcular totales generales
        $totalsQuery = User::query()
            ->whereHas('roles', function ($q) {
                $q->where('name', 'empleado');
            })
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });

        $overallTotals = [
            'total_employees' => $totalsQuery->count(),
            'total_points_earned' => $summary->sum('total_earned'),
            'total_points_redeemed' => $summary->sum('total_redeemed'),
            'total_points_available' => $summary->sum('total_available'),
        ];

        $companies = $isSiteAdmin
            ? Company::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('points.resumen', compact(
            'summary',
            'companies',
            'companyId',
            'overallTotals',
            'isSiteAdmin'
        ));
    }

    /**
     * Ver detalle de puntos de un empleado específico (para admins)
     */
    public function employeeDetail($employeeId, Request $request)
    {
        $user = Auth::user();
        $isSiteAdmin = $user->hasRole('admin_sitio');
        $isCompanyAdmin = $user->hasRole('admin_empresa');

        $employee = User::with('company')->findOrFail($employeeId);

        // Verificar permisos
        if (!$isSiteAdmin && !$isCompanyAdmin && $user->id !== $employee->id) {
            return redirect()->route('points.index')->with('error', 'No tiene permisos para ver estos puntos.');
        }

        if ($isCompanyAdmin && $employee->company_id !== $user->company_id) {
            return redirect()->route('points.index')->with('error', 'El empleado no pertenece a su empresa.');
        }

        // Obtener movimientos del empleado
        $query = PointMovement::with([
    'employee:id,name,email,cuil',
    'business:id,name',
    'createdBy:id,name',
    'confirmedBy:id,name',
    'company:id,name',
    'batch:id,filename,created_at'
        ])
        ->where('employee_user_id', $employee->id);

        // Aplicar filtros
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('occurred_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('occurred_at', '<=', $endDate);
        }

        $points = $query->orderBy('occurred_at', 'desc')->paginate(15);

        // Calcular totales
        $totals = [
            'total_earned' => PointMovement::where('employee_user_id', $employee->id)
                ->where('type', 'earn')
                ->sum('points'),
            'total_redeemed' => abs(PointMovement::where('employee_user_id', $employee->id)
                ->where('type', 'redeem')
                ->sum('points')),
            'available' => PointMovement::where('employee_user_id', $employee->id)
                ->sum('points'),
        ];

return view('points.empleado-detalle', compact('employee', 'points', 'totals'));


    }

    /**
     * Exportar puntos a CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $isSiteAdmin = $user->hasRole('admin_sitio');
        $isCompanyAdmin = $user->hasRole('admin_empresa');

        if (!$isSiteAdmin && !$isCompanyAdmin) {
            return redirect()->route('points.index')->with('error', 'No tiene permisos para exportar.');
        }

        // Construir consulta similar al index
        $query = PointMovement::with([
    'employee:id,name,email,cuil',
    'business:id,name',
    'createdBy:id,name',
    'confirmedBy:id,name',
    'company:id,name',
    'batch:id,filename'
        ]);

        if ($isCompanyAdmin) {
            $query->where('company_id', $user->company_id);
        }

        // Aplicar mismos filtros
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_user_id', $request->employee_id);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('occurred_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('occurred_at', '<=', $request->end_date);
        }

        $points = $query->orderBy('occurred_at', 'desc')->get();

        // Generar CSV
        $filename = 'points-export-' . date('Y-m-d-H-i') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($points) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
     'Fecha',
    'Empleado',
    'CUIL',
    'Empresa',
    'Tipo',
    'Puntos',
    'Negocio',
    'Confirmado por',
    'Referencia',
    'Notas',
    'Creado por',
    'Lote'
            ]);

            foreach ($points as $point) {
                fputcsv($file, [
    $point->occurred_at->format('Y-m-d H:i'),
    $point->employee->name ?? '',
    $point->employee->cuil ?? '',
    $point->company->name ?? '',
    $point->type,
    $point->points,
    $point->business->name ?? '',
    $point->confirmedBy->name ?? '',
    $point->reference ?? '',
    $point->note ?? '',
    $point->createdBy->name ?? '',
    $point->batch->filename ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function void(Request $request, PointMovement $movement)
{
    $user = Auth::user();

    if (!$user->hasRole('negocio') && !$user->hasRole('admin_sitio')) {
        return back()->with('error', 'No tiene permisos para anular movimientos.');
    }

    // negocio solo anula lo suyo (admin_sitio puede todo)
    if (!$user->hasRole('admin_sitio') && (int)$movement->business_user_id !== (int)$user->id) {
        return back()->with('error', 'Solo puede anular consumos de su negocio.');
    }

    if (!is_null($movement->voided_at)) {
        return back()->with('error', 'Este movimiento ya está anulado.');
    }

    $request->validate([
        'reason' => ['nullable','string','max:200'],
    ]);

    $movement->update([
        'voided_at' => now(),
        'voided_by' => $user->id,
        'void_reason' => $request->input('reason'),
    ]);

    // Marcar también la redemption asociada (si existe)
    PointRedemption::where('point_movement_id', $movement->id)->update([
        'status' => 'voided',
        'confirmed_by' => $user->id,
    ]);

    return back()->with('success', 'Consumo anulado correctamente.');
}

}
