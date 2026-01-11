<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use App\Models\User;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PointsController;
use App\Http\Controllers\PointImportController;
use App\Http\Controllers\RedemptionController;

// ABM
use App\Http\Controllers\Abm\CompanyController;
use App\Http\Controllers\Abm\PaisController;
use App\Http\Controllers\Abm\ProvinciaController;
use App\Http\Controllers\Abm\LocalidadController;
use App\Http\Controllers\Abm\UserController;
use App\Http\Controllers\Abm\PointReferenceController;

Route::get('/', fn () => redirect()->route('login'));


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
 * TODO LO QUE REQUIERE LOGIN
 * ============================
 */
Route::middleware(['auth'])->group(function () {

    /**
     * ============================
     * REDENCIONES (QR) - Negocio / Empleado
     * ============================
     * - Negocio: crea solicitud + QR
     * - Empleado: confirma
     */
    Route::get('/redeems/create', [RedemptionController::class, 'create'])
        ->name('redeems.create');

    Route::post('/redeems', [RedemptionController::class, 'store'])
        ->name('redeems.store');

    // Si usás "cancel" y existe el método
    Route::patch('/redeems/{redemption}/cancel', [RedemptionController::class, 'cancel'])
        ->name('redeems.cancel');

    // Confirmación / comprobante por token
    Route::get('/redeems/confirm/{token}', [RedemptionController::class, 'showConfirm'])
        ->name('redeems.confirm.show');

    Route::post('/redeems/confirm/{token}', [RedemptionController::class, 'confirm'])
        ->name('redeems.confirm.do');

    /**
     * Endpoint JSON para completar negocio desde QR (usado por el escáner)
     * Ej: /abm/businesses/1/json
     */
    Route::get('/abm/businesses/{id}/json', function ($id) {
        $u = User::query()
            ->whereKey($id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'negocio'))
            ->firstOrFail();

        return response()->json([
            'id'   => $u->id,
            'name' => $u->name,
        ]);
    })->name('abm.businesses.json');



    /**
     * ============================
     * CONSUMO MANUAL (Empleado -> Negocio)
     * ============================
     */
    Route::get('/redeems/manual', [RedemptionController::class, 'manualIndex'])
        ->name('redeems.manual.index');

    Route::get('/redeems/manual/{business}', [RedemptionController::class, 'manualCreate'])
        ->name('redeems.manual.create');

    Route::post('/redeems/manual/{business}', [RedemptionController::class, 'manualStore'])
        ->name('redeems.manual.store');

    /**
     * ============================
     * PUNTOS
     * ============================
     */
    Route::prefix('points')->name('points.')->group(function () {

        Route::get('/', [PointsController::class, 'index'])->name('index');

        // Void (si lo usás)
        Route::patch('/{movement}/void', [PointsController::class, 'void'])->name('void');

        // Resumen (admins)
        Route::get('/summary', [PointsController::class, 'summary'])
            ->middleware('role:admin_sitio|admin_empresa')
            ->name('summary');

        // Alias español
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
    Route::middleware(['role:admin_sitio|admin_empresa'])->group(function () {
        Route::get('/points/import', [PointImportController::class, 'create'])
            ->name('points.import.create');

        Route::post('/points/import/preview', [PointImportController::class, 'preview'])
            ->name('points.import.preview');

        Route::post('/points/import/commit', [PointImportController::class, 'commit'])
            ->name('points.import.commit');
    });

    /**
     * ============================
     * ABM
     * ============================
     * - admin_sitio: todo
     * - admin_empresa: todo menos companies
     */
    Route::middleware(['role:admin_sitio|admin_empresa'])
  ->prefix('abm')
  ->name('abm.')
  ->group(function () {

    Route::resource('paises', PaisController::class);
    Route::resource('provincias', ProvinciaController::class);
    Route::resource('localidades', LocalidadController::class);

    // Users (ABM a mano)
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');

    // Point references (CRUD completo)
    Route::resource('point-references', PointReferenceController::class)
      ->names('point-references'); // ✅ incluye show

    // SOLO admin_sitio puede administrar empresas
    Route::middleware(['role:admin_sitio'])->group(function () {
      Route::resource('companies', CompanyController::class);
    });
});


});

require __DIR__ . '/auth.php';
