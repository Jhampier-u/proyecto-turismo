<?php
use App\Http\Controllers\Admin\LugarController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ZonaController;
use App\Http\Controllers\Operativo\DashboardController;
use App\Http\Controllers\Operativo\EvaluacionFetController;
use App\Http\Controllers\Operativo\EvaluacionFitController;
use App\Http\Controllers\Operativo\EvaluacionPotencialidadController;
use App\Http\Controllers\Operativo\InventarioController;
use App\Http\Controllers\Operativo\VttController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('index'));

Route::get('/dashboard', function () {
    return Auth::user()->role_id === 1
        ? redirect()->route('admin.dashboard')
        : redirect()->route('operativo.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── ADMIN ────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');

    Route::resource('users',   UserController::class);
    Route::resource('lugares', LugarController::class);
    Route::resource('zonas',   ZonaController::class);

    // Admin: ver resultados VTT de una zona
    Route::get('/zona/{zona}/resultado-vtt', [VttController::class, 'resultadoFinal'])
        ->name('vtt.final.admin');

    // Admin: ver potencialidad de una zona
    Route::get('/zona/{zona}/potencialidad', [ZonaController::class, 'potencialidad'])
        ->name('zonas.potencialidad');
});

// ── OPERATIVO ─────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'personal'])->group(function () {

    Route::get('/mis-zonas', [DashboardController::class, 'index'])->name('operativo.dashboard');

    Route::get('/api/categorias/{id}/hijos', [InventarioController::class, 'subcategorias']);

    Route::prefix('operativo/zona/{zona}')->name('operativo.')->group(function () {

        Route::resource('inventarios', InventarioController::class);

        Route::get('/evaluacion-fit',             [EvaluacionFitController::class, 'edit'])->name('evaluacion_fit.edit');
        Route::post('/evaluacion-fit',            [EvaluacionFitController::class, 'update'])->name('evaluacion_fit.update');
        Route::get('/evaluacion-fit/ponderacion', [EvaluacionFitController::class, 'ponderacion'])->name('evaluacion_fit.ponderacion');

        Route::get('/evaluacion-fet',             [EvaluacionFetController::class, 'edit'])->name('evaluacion_fet.edit');
        Route::post('/evaluacion-fet',            [EvaluacionFetController::class, 'update'])->name('evaluacion_fet.update');
        Route::get('/evaluacion-fet/ponderacion', [EvaluacionFetController::class, 'ponderacion'])->name('evaluacion_fet.ponderacion');

        Route::get('/resultado-vtt', [VttController::class, 'resultadoFinal'])->name('vtt.final');

        // Potencialidad
        Route::get('/evaluacion-potencialidad',           [EvaluacionPotencialidadController::class, 'edit'])->name('evaluacion_potencialidad.edit');
        Route::post('/evaluacion-potencialidad',          [EvaluacionPotencialidadController::class, 'update'])->name('evaluacion_potencialidad.update');
        Route::post('/evaluacion-potencialidad/campos',   [EvaluacionPotencialidadController::class, 'guardarCampos'])->name('evaluacion_potencialidad.campos');
        Route::post('/evaluacion-potencialidad/reconfigurar', [EvaluacionPotencialidadController::class, 'reconfigurarCampos'])->name('evaluacion_potencialidad.reconfigurar');
        Route::get('/evaluacion-potencialidad/resultados',[EvaluacionPotencialidadController::class, 'ponderacion'])->name('evaluacion_potencialidad.ponderacion');
    });
});

require __DIR__ . '/auth.php';
