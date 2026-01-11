<?php

use Illuminate\Support\Facades\Route;

use App\Models\User;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PointsController;
use App\Http\Controllers\PointImportController;
use App\Http\Controllers\RedemptionController;
use Illuminate\Support\Facades\Storage;


// ABM
use App\Http\Controllers\Abm\CompanyController;
use App\Http\Controllers\Abm\PaisController;
use App\Http\Controllers\Abm\ProvinciaController;
use App\Http\Controllers\Abm\LocalidadController;
use App\Http\Controllers\Abm\UserController;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/test-user-image', function () {
    $path = 'users/TXOrb9VCnW7dPtdTZ4FE3vK9JVxiD7loO4qoSviN.png';
    abort_unless(Storage::disk('public')->exists($path), 404, 'No existe');
    return response()->file(storage_path('app/public/'.$path));
});

/**
 * Dashboard
 */
Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/**
 * Perfil
 */
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * ============================
 * REDENCIONES (QR) - Negocio / Empleado
 * ============================
 * - Negocio: crea solicitud + QR
 * - Empleado: confirma
 */
Route::middleware(['auth'])->group(function () {
    // Negocio crea solicitud + QR
    Route::get('/redeems/create', [RedemptionController::class, 'create'])->name('redeems.create');
    Route::post('/redeems', [RedemptionController::class, 'store'])->name('redeems.store');
    Route::patch('/redeems/{redemption}/cancel', [RedemptionController::class, 'cancel'])
    ->name('redeems.cancel');

    Route::patch('/points/{movement}/void', [PointsController::class, 'void'])
    ->name('points.void');


    // Empleado confirma (desde QR)
  Route::get('/redeems/confirm/{token}', [RedemptionController::class, 'showConfirm'])->name('redeems.confirm.show');
    Route::post('/redeems/confirm/{token}', [RedemptionController::class, 'confirm'])->name('redeems.confirm.do');
});

Route::get('/abm/businesses/{id}/json', function ($id) {
    // si negocio = user con rol 'negocio'
    $u = User::query()->whereKey($id)->firstOrFail();

    return response()->json([
        'id' => $u->id,
        'name' => $u->name,
    ]);
})->middleware(['auth']);

Route::get('/abm/businesses/{id}/json', [RedemptionController::class, 'businessJson'])
  ->middleware(['auth']);

/**
 * ============================
 * PUNTOS
 * ============================
 * - Empleado entra a points.index y el controller lo lleva a su vista (employeeView)
 * - Admin sitio/empresa ven todo/empresa respectivamente
 */
Route::middleware(['auth'])->prefix('points')->name('points.')->group(function () {
    Route::get('/', [PointsController::class, 'index'])->name('index');

    // Resumen (admins)
    Route::get('/summary', [PointsController::class, 'summary'])
        ->middleware('role:admin_sitio|admin_empresa')
        ->name('summary');

    // Alias español (por si lo usás en menú)
    Route::get('/resumen', [PointsController::class, 'summary'])
        ->middleware('role:admin_sitio|admin_empresa')
        ->name('resumen');

    // Crear / guardar manual (admins)
    Route::get('/crear', [PointsController::class, 'create'])
        ->middleware('role:admin_sitio|admin_empresa')
        ->name('create');

    Route::post('/guardar', [PointsController::class, 'store'])
        ->middleware('role:admin_sitio|admin_empresa')
        ->name('store');

    // Detalle por empleado (admins)
    Route::get('/employee/{employee}', [PointsController::class, 'employeeDetail'])
        ->middleware('role:admin_sitio|admin_empresa')
        ->name('employee.detail');

    // Export (admins)
    Route::get('/export', [PointsController::class, 'export'])
        ->middleware('role:admin_sitio|admin_empresa')
        ->name('export');
});

/**
 * ============================
 * IMPORTACIÓN MASIVA (admins)
 * ============================
 */
Route::middleware(['auth', 'role:admin_sitio|admin_empresa'])->group(function () {
    Route::get('/points/import', [PointImportController::class, 'create'])->name('points.import.create');
    Route::post('/points/import/preview', [PointImportController::class, 'preview'])->name('points.import.preview');
    Route::post('/points/import/commit', [PointImportController::class, 'commit'])->name('points.import.commit');
});

/**
 * ============================
 * ABM
 * ============================
 * - admin_sitio: todo
 * - admin_empresa: todo menos companies (crear empresas/sitios)
 */
Route::middleware(['auth', 'role:admin_sitio|admin_empresa'])
    ->prefix('abm')
    ->name('abm.')
    ->group(function () {

        // ABM comunes (admin_sitio y admin_empresa)
        Route::resource('paises', PaisController::class);
        Route::resource('provincias', ProvinciaController::class);
        Route::resource('localidades', LocalidadController::class);

        // Users (ABM a mano como lo tenías)
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');

        // SOLO admin_sitio puede administrar empresas
        Route::middleware(['role:admin_sitio'])->group(function () {
            Route::resource('companies', CompanyController::class);
        });
    });

// ============================
// CONSUMO MANUAL (Empleado -> Negocio)
// ============================

// 1) Pantalla general: empleado elige negocio
Route::get('/redeems/manual', [RedemptionController::class, 'manualIndex'])
  ->name('redeems.manual.index');

// 2) Pantalla por QR: negocio precargado
Route::get('/redeems/manual/{business}', [RedemptionController::class, 'manualCreate'])
  ->name('redeems.manual.create');

// 3) Guardar consumo manual (requiere negocio en URL)
Route::post('/redeems/manual/{business}', [RedemptionController::class, 'manualStore'])
  ->name('redeems.manual.store');

require __DIR__ . '/auth.php';
