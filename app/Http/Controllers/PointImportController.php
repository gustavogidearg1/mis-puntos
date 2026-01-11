<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Models\PointMovement;
use App\Models\PointImportBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PointImportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin_sitio|admin_empresa']);
    }

    public function create(Request $r)
    {
        $u = $r->user();
        $isSiteAdmin = $u->hasRole('admin_sitio');

        $companies = $isSiteAdmin
            ? Company::query()->orderBy('name')->get()
            : collect();

        return view('points.import.create', [
            'isSiteAdmin' => $isSiteAdmin,
            'companies'   => $companies,
        ]);
    }

    public function preview(Request $r)
    {
        $u = $r->user();
        $isSiteAdmin = $u->hasRole('admin_sitio');

        $data = $r->validate([
            'company_id' => ['nullable','integer','exists:companies,id'], // solo admin_sitio
            'file'       => ['required','file','mimes:csv,txt','max:5120'],
        ]);

        $companyId = $isSiteAdmin
            ? ((int)($data['company_id'] ?? 0) ?: (int)($u->company_id ?? 0))
            : (int)$u->company_id;

        if (!$companyId) {
            return back()->with('error', 'No se pudo determinar la empresa para la importación.');
        }

        // Guardar archivo temporal
        $path = $r->file('file')->store('imports/points');

        // Parse CSV
        $fullPath = Storage::path($path);
        $fh = fopen($fullPath, 'r');

        if (!$fh) {
            return back()->with('error', 'No se pudo leer el archivo.');
        }

        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);
            return back()->with('error', 'CSV vacío o inválido.');
        }

        $header = array_map(fn($h) => trim((string)$h), $header);

        $rows = [];
        $ok = 0;
        $err = 0;
        $line = 1;

        while (($cols = fgetcsv($fh)) !== false) {
            $line++;
            if (count($cols) === 1 && trim((string)$cols[0]) === '') continue;

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $cols[$i] ?? null;
            }

            $validation = $this->validateImportRow($row, $companyId, $isSiteAdmin, $u);

            $rows[] = [
                'line'   => $line,
                'row'    => $row,
                'ok'     => $validation['ok'],
                'error'  => $validation['error'],
                'mapped' => $validation['mapped'], // ya resuelto employee_id, points signed, etc.
            ];

            $validation['ok'] ? $ok++ : $err++;

            if (count($rows) >= 5000) { // hard limit de preview
                break;
            }
        }

        fclose($fh);

        // Guardar en sesión para commit
        session([
            'points_import.path'      => $path,
            'points_import.companyId' => $companyId,
            'points_import.rows'      => $rows,
            'points_import.ok'        => $ok,
            'points_import.err'       => $err,
        ]);

        return view('points.import.preview', [
            'companyId' => $companyId,
            'path'      => $path,
            'rows'      => $rows,
            'ok'        => $ok,
            'err'       => $err,
        ]);
    }

    public function commit(Request $r)
    {
        $u = $r->user();

        $path      = session('points_import.path');
        $companyId = (int) session('points_import.companyId');
        $rows      = session('points_import.rows', []);
        $okCount   = (int) session('points_import.ok', 0);
        $errCount  = (int) session('points_import.err', 0);

        if (!$path || !$companyId || empty($rows)) {
            return redirect()->route('points.import.create')->with('error', 'No hay preview cargado para confirmar.');
        }

        // Confirmación simple
        $r->validate([
            'confirm' => ['required','in:1'],
        ]);

        DB::transaction(function () use ($u, $companyId, $path, $rows, $okCount, $errCount) {

            $batch = PointImportBatch::create([
                'company_id'  => $companyId,
                'created_by'  => $u->id,
                'filename'    => $path,
                'status'      => 'committed',
                'rows_total'  => count($rows),
                'rows_ok'     => $okCount,
                'rows_error'  => $errCount,
            ]);

            foreach ($rows as $rrow) {
                if (!$rrow['ok']) continue;

                $m = $rrow['mapped'];

                PointMovement::create([
                    'company_id'        => $companyId,
                    'employee_user_id'  => $m['employee_user_id'],
                    'business_user_id'  => null,
                    'created_by'        => $u->id,
                    'confirmed_by'      => null,
                    'batch_id'          => $batch->id,
                    'type'              => $m['type'],
                    'points'            => $m['points'], // ya viene signed
                    'money_amount'      => null,
                    'reference'         => $m['reference'] ?? null,
                    'note'              => $m['note'] ?? null,
                    'occurred_at'       => $m['occurred_at'] ?? now(),
                ]);
            }
        });

        // limpiar sesión
        session()->forget([
            'points_import.path',
            'points_import.companyId',
            'points_import.rows',
            'points_import.ok',
            'points_import.err',
        ]);

        return redirect()->route('points.index')->with('ok', 'Importación confirmada y aplicada.');
    }

    /* =========================================================
     * Helpers
     * ========================================================= */
    private function validateImportRow(array $row, int $companyId, bool $isSiteAdmin, $u): array
    {
        // Campos posibles
        $employeeId   = $row['employee_id'] ?? null;
        $employeeCuil = $row['employee_cuil'] ?? ($row['employee_cuit'] ?? null);
        $employeeMail = $row['employee_email'] ?? null;

        $type   = strtolower(trim((string)($row['type'] ?? '')));
        $points = (int)($row['points'] ?? 0);

        if (!$employeeId && !$employeeCuil && !$employeeMail) {
            return ['ok'=>false, 'error'=>'Falta employee_id / employee_cuil / employee_email', 'mapped'=>null];
        }

        if (!in_array($type, ['earn','redeem','adjust','expire'], true)) {
            return ['ok'=>false, 'error'=>'Type inválido (earn/redeem/adjust/expire)', 'mapped'=>null];
        }

        if ($points <= 0) {
            return ['ok'=>false, 'error'=>'Points debe ser > 0', 'mapped'=>null];
        }

        // Resolver empleado
        $empQ = User::query()->whereHas('roles', fn($q) => $q->where('name','empleado'));

        if ($employeeId) {
            $empQ->whereKey((int)$employeeId);
        } elseif ($employeeMail) {
            $empQ->where('email', trim((string)$employeeMail));
        } else {
            // cuil/cuit en tu app suele estar como "cuil"
            $empQ->where('cuil', preg_replace('/\D+/', '', (string)$employeeCuil));
        }

        $emp = $empQ->first();

        if (!$emp) {
            return ['ok'=>false, 'error'=>'Empleado no encontrado', 'mapped'=>null];
        }

        // admin_empresa: solo su empresa
        if (!$isSiteAdmin) {
            if ((int)$emp->company_id !== (int)($u->company_id ?? 0)) {
                return ['ok'=>false, 'error'=>'Empleado fuera de tu empresa', 'mapped'=>null];
            }
        } else {
            // admin_sitio: si importás para una empresa, el empleado debe pertenecer a esa empresa
            if ((int)$emp->company_id !== (int)$companyId) {
                return ['ok'=>false, 'error'=>'Empleado no pertenece a la empresa seleccionada', 'mapped'=>null];
            }
        }

        // puntos signed (redeem negativo)
        $signedPoints = ($type === 'redeem') ? -abs($points) : abs($points);

        // occurred_at opcional
        $occurredAt = null;
        if (!empty($row['occurred_at'])) {
            try {
                $occurredAt = \Illuminate\Support\Carbon::parse($row['occurred_at']);
            } catch (\Throwable $e) {
                return ['ok'=>false, 'error'=>'occurred_at inválida', 'mapped'=>null];
            }
        }

        return [
            'ok'    => true,
            'error' => null,
            'mapped'=> [
                'employee_user_id' => $emp->id,
                'type'             => $type,
                'points'           => $signedPoints,
                'reference'        => $row['reference'] ?? null,
                'note'             => $row['note'] ?? null,
                'occurred_at'      => $occurredAt,
            ],
        ];
    }
}
