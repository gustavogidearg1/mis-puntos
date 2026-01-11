<?php

namespace App\Http\Controllers;

use App\Models\PointImportBatch;
use App\Models\PointMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PointImportController extends Controller
{
    public function create()
    {
        return view('points.import.create');
    }

    /**
     * PREVIEW: sube CSV, valida y muestra OK/Errores.
     *
     * CSV requerido:
     *  - employee_cuil
     *  - points
     *  - occurred_at (YYYY-MM-DD o YYYY-MM-DD HH:MM)
     * Opcionales:
     *  - reference
     *  - note
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $user = $request->user();
        $companyId = $user->company_id ?? null;

        if (!$companyId && !$user->hasRole('site_admin')) {
            return back()->withErrors(['file' => 'No se detectó company_id en el usuario.']);
        }

        $path = $request->file('file')->store('imports/points', 'local');
        $filename = basename($path);

        [$header, $rows] = $this->readCsv(Storage::disk('local')->path($path), 5000);

        if (empty($header)) {
            return back()->withErrors(['file' => 'No se pudo leer el header del CSV.']);
        }

        $headerMap = array_flip($header);

        foreach (['employee_cuil', 'points', 'occurred_at'] as $required) {
            if (!isset($headerMap[$required])) {
                return back()->withErrors(['file' => "Falta la columna obligatoria: {$required}."]);
            }
        }

        $preview = [];
        $ok = 0;
        $err = 0;

        foreach ($rows as $i => $row) {
            $line = $i + 2; // header = línea 1

            $cuilRaw   = trim((string)($row[$headerMap['employee_cuil']] ?? ''));
            $pointsRaw = trim((string)($row[$headerMap['points']] ?? ''));
            $occRaw    = trim((string)($row[$headerMap['occurred_at']] ?? ''));

            $reference = isset($headerMap['reference']) ? trim((string)($row[$headerMap['reference']] ?? '')) : null;
            $note      = isset($headerMap['note']) ? trim((string)($row[$headerMap['note']] ?? '')) : null;

            $issues = [];

            // CUIL/CUIT normalizado
            $cuil = $this->digitsOnly($cuilRaw);
            if ($cuil === '') {
                $issues[] = 'employee_cuil vacío o inválido';
            }

            // points
            $pointsNormalized = str_replace(',', '.', $pointsRaw);
            if ($pointsRaw === '' || !is_numeric($pointsNormalized)) {
                $issues[] = 'points no numérico';
            }

            // occurred_at
            $occurredAt = $this->parseDateTime($occRaw);
            if (!$occurredAt) {
                $issues[] = 'occurred_at inválida';
            }

            // lookup empleado (si todo ok)
            if (empty($issues)) {
                $q = User::query();

                // si Admin Sitio luego querés permitir elegir empresa destino, acá se ajusta
                if ($companyId) {
                    $q->where('company_id', $companyId);
                }

                $employee = $q->where('cuil', $cuil)->first();

                if (!$employee) {
                    $issues[] = 'Empleado no encontrado (company_id + CUIL/CUIT)';
                } else {
                    if (!$employee->hasRole('empleado')) $issues[] = 'El usuario no es rol Empleado (empleado)';
                    if ($employee->activo === false) $issues[] = 'Empleado inactivo';
                }
            }

            $status = empty($issues) ? 'ok' : 'error';
            if ($status === 'ok') $ok++; else $err++;

            $preview[] = [
                'line' => $line,
                'employee_cuil' => $cuilRaw ?: '—',
                'points' => $pointsRaw ?: '—',
                'occurred_at' => $occRaw ?: '—',
                'reference' => $reference ?: '—',
                'note' => $note ?: '—',
                'status' => $status,
                'issues' => $issues,
            ];
        }

        $batch = PointImportBatch::create([
            'company_id' => $companyId ?? 0,
            'created_by' => $user->id,
            'filename' => $filename,
            'status' => 'preview',
            'rows_total' => count($preview),
            'rows_ok' => $ok,
            'rows_error' => $err,
        ]);

        session([
            'points_import_batch_id' => $batch->id,
            'points_import_path' => $path,
        ]);

        return view('points.import.preview', [
            'batch' => $batch,
            'preview' => $preview,
            'header' => $header,
        ]);
    }

    /**
     * COMMIT: confirma y guarda movimientos (solo filas válidas).
     */
    public function commit(Request $request)
    {
        $request->validate([
            'confirm' => ['required', 'in:1'],
        ]);

        $batchId = session('points_import_batch_id');
        $path = session('points_import_path');

        if (!$batchId || !$path || !Storage::disk('local')->exists($path)) {
            return redirect()->route('points.import.create')
                ->withErrors(['file' => 'No hay un lote en previsualización para confirmar.']);
        }

        $batch = PointImportBatch::findOrFail($batchId);
        $user  = $request->user();

        // Admin Empresa solo su empresa
        if (!$user->hasRole('site_admin') && (int)$user->company_id !== (int)$batch->company_id) {
            abort(403);
        }

        [$header, $rows] = $this->readCsv(Storage::disk('local')->path($path), 1000000);
        $headerMap = array_flip($header);

        foreach (['employee_cuil', 'points', 'occurred_at'] as $required) {
            if (!isset($headerMap[$required])) {
                return redirect()->route('points.import.create')
                    ->withErrors(['file' => "El CSV no contiene {$required}."]);
            }
        }

        DB::transaction(function () use ($rows, $headerMap, $batch, $user) {

            $batch->status = 'imported';
            $batch->save();

            foreach ($rows as $row) {
                $cuilRaw   = trim((string)($row[$headerMap['employee_cuil']] ?? ''));
                $pointsRaw = trim((string)($row[$headerMap['points']] ?? ''));
                $occRaw    = trim((string)($row[$headerMap['occurred_at']] ?? ''));

                $reference = isset($headerMap['reference']) ? trim((string)($row[$headerMap['reference']] ?? '')) : null;
                $note      = isset($headerMap['note']) ? trim((string)($row[$headerMap['note']] ?? '')) : null;

                $cuil = $this->digitsOnly($cuilRaw);
                if ($cuil === '') continue;

                $pointsNormalized = str_replace(',', '.', $pointsRaw);
                if (!is_numeric($pointsNormalized)) continue;

                $points = (int) round((float)$pointsNormalized);

                $occurredAt = $this->parseDateTime($occRaw);
                if (!$occurredAt) continue;

                $employee = User::query()
                    ->where('company_id', $batch->company_id)
                    ->where('cuil', $cuil)
                    ->first();

                if (!$employee) continue;
                if (!$employee->hasRole('empleado')) continue;
                if ($employee->activo === false) continue;

                PointMovement::create([
                    'company_id' => $batch->company_id,
                    'employee_user_id' => $employee->id,
                    'business_user_id' => null, // carga masiva
                    'created_by' => $user->id,
                    'batch_id' => $batch->id,

                    'type' => 'earn',
                    'points' => $points,
                    'money_amount' => null,
                    'reference' => $reference ?: null,
                    'note' => $note ?: null,
                    'occurred_at' => $occurredAt,
                ]);
            }
        });

        session()->forget(['points_import_batch_id', 'points_import_path']);

        return redirect()->route('points.import.create')
            ->with('success', "Importación confirmada. Lote #{$batch->id} guardado.");
    }

    // ---------------- Helpers ----------------

    private function digitsOnly(string $value): string
    {
        $value = trim($value);
        if ($value === '') return '';
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function readCsv(string $fullPath, int $limitRows = 5000): array
    {
        $handle = fopen($fullPath, 'r');
        if (!$handle) return [[], []];

        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = (substr_count((string)$firstLine, ';') > substr_count((string)$firstLine, ',')) ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter);
        $header = array_map(fn($h) => trim((string)$h), $header ?: []);

        $rows = [];
        $count = 0;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($data) === 1 && trim((string)$data[0]) === '') continue;
            $rows[] = $data;
            $count++;
            if ($count >= $limitRows) break;
        }

        fclose($handle);

        $header = array_map(function ($h) {
            $h = mb_strtolower($h);
            $h = str_replace([' ', '-'], '_', $h);
            return trim($h);
        }, $header);

        return [$header, $rows];
    }

    private function parseDateTime(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') return null;

        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}$/', $value)) {
                return Carbon::createFromFormat('Y-m-d H:i', $value);
            }
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
