<?php
use App\Http\Controllers\Admin\LugarController;
use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ZonaController;
use App\Http\Controllers\Operativo\DashboardController;
use App\Http\Controllers\Operativo\EvaluacionConcentracionController;
use App\Http\Controllers\Operativo\EvaluacionFetController;
use App\Http\Controllers\Operativo\EvaluacionFitController;
use App\Http\Controllers\Operativo\EvaluacionIrritacionController;
use App\Http\Controllers\Operativo\EvaluacionPaisajeController;
use App\Http\Controllers\Operativo\EvaluacionPercepcionController;
use App\Http\Controllers\Operativo\EvaluacionPotencialidadController;
use App\Http\Controllers\Operativo\EvaluacionValoracionTerritorialController;
use App\Http\Controllers\Operativo\FrecuentacionController;
use App\Http\Controllers\Operativo\InventarioController;
use App\Http\Controllers\Operativo\InvolucradosController;
use App\Http\Controllers\Operativo\VttController;
use App\Http\Controllers\Operativo\ZonaPanelController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('index'));

// Reparte según el rol, con el mismo predicado que usa el middleware IsAdmin.
// Antes comparaba role_id con un 1 literal: una segunda definición de «es
// admin», suelta en las rutas, que podía contradecir a la del modelo sin que
// nada lo detectara.
Route::get('/dashboard', function () {
    return Auth::user()->esAdmin()
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
    Route::get('/dashboard', [PanelController::class, 'index'])->name('dashboard');

    Route::resource('users',   UserController::class);
    Route::resource('lugares', LugarController::class);
    Route::resource('zonas',   ZonaController::class);
});

// ── OPERATIVO ─────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'personal'])->group(function () {

    Route::get('/mis-zonas', [DashboardController::class, 'index'])->name('operativo.dashboard');

    Route::get('/api/categorias/{id}/hijos', [InventarioController::class, 'subcategorias']);

    Route::prefix('operativo/zona/{zona}')->middleware('zona')->name('operativo.')->group(function () {

        Route::get('/', [ZonaPanelController::class, 'show'])->name('zona.panel');

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
        Route::post('/evaluacion-potencialidad/reconfigurar', [EvaluacionPotencialidadController::class, 'reconfigurarCampos'])->name('evaluacion_potencialidad.reconfigurar');
        Route::get('/evaluacion-potencialidad/resultados',[EvaluacionPotencialidadController::class, 'ponderacion'])->name('evaluacion_potencialidad.ponderacion');

        // Matriz de Percepción de la Localidad
        Route::get('/evaluacion-percepcion',             [EvaluacionPercepcionController::class, 'edit'])->name('evaluacion_percepcion.edit');
        Route::post('/evaluacion-percepcion',            [EvaluacionPercepcionController::class, 'update'])->name('evaluacion_percepcion.update');
        Route::get('/evaluacion-percepcion/resultados',  [EvaluacionPercepcionController::class, 'ponderacion'])->name('evaluacion_percepcion.ponderacion');

        // Matriz de Análisis y Valoración del Paisaje
        Route::get('/paisaje',            [EvaluacionPaisajeController::class, 'edit'])->name('evaluacion_paisaje.edit');
        Route::post('/paisaje',           [EvaluacionPaisajeController::class, 'update'])->name('evaluacion_paisaje.update');
        Route::get('/paisaje/resultados', [EvaluacionPaisajeController::class, 'ponderacion'])->name('evaluacion_paisaje.ponderacion');

        // Matriz de Valoración Territorial
        Route::get('/valoracion-territorial',            [EvaluacionValoracionTerritorialController::class, 'edit'])->name('evaluacion_valoracion_territorial.edit');
        Route::post('/valoracion-territorial',           [EvaluacionValoracionTerritorialController::class, 'update'])->name('evaluacion_valoracion_territorial.update');
        Route::get('/valoracion-territorial/resultados', [EvaluacionValoracionTerritorialController::class, 'ponderacion'])->name('evaluacion_valoracion_territorial.ponderacion');

        // Índice de Irritación Turística
        Route::get('/irritacion',            [EvaluacionIrritacionController::class, 'edit'])->name('evaluacion_irritacion.edit');
        Route::post('/irritacion',           [EvaluacionIrritacionController::class, 'update'])->name('evaluacion_irritacion.update');
        Route::get('/irritacion/resultados', [EvaluacionIrritacionController::class, 'ponderacion'])->name('evaluacion_irritacion.ponderacion');

        // Índice de Concentración Turística
        Route::get('/concentracion',            [EvaluacionConcentracionController::class, 'edit'])->name('evaluacion_concentracion.edit');
        Route::post('/concentracion',           [EvaluacionConcentracionController::class, 'update'])->name('evaluacion_concentracion.update');
        Route::get('/concentracion/resultados', [EvaluacionConcentracionController::class, 'ponderacion'])->name('evaluacion_concentracion.ponderacion');

        // Matriz de Involucrados Turísticos Territoriales
        //
        // 'nuevo', 'validar' y 'resultados' van ANTES que '{actor}/editar':
        // con {actor} declarada primero, una petición a /involucrados/validar
        // encajaría en ese patrón con actor='validar' y nunca llegaría a la
        // ruta pensada para validar. Laravel resuelve por orden de
        // declaración, no por especificidad.
        Route::get('/involucrados',                 [InvolucradosController::class, 'index'])->name('involucrados.index');
        Route::get('/involucrados/nuevo',           [InvolucradosController::class, 'create'])->name('involucrados.create');
        Route::post('/involucrados',                [InvolucradosController::class, 'store'])->name('involucrados.store');
        Route::post('/involucrados/validar',        [InvolucradosController::class, 'validar'])->name('involucrados.validar');
        Route::get('/involucrados/resultados',      [InvolucradosController::class, 'resultados'])->name('involucrados.resultados');
        Route::get('/involucrados/{actor}/editar',  [InvolucradosController::class, 'edit'])->name('involucrados.edit');
        Route::put('/involucrados/{actor}',         [InvolucradosController::class, 'update'])->name('involucrados.update');
        Route::delete('/involucrados/{actor}',      [InvolucradosController::class, 'destroy'])->name('involucrados.destroy');

        // Índice Espacial de Frecuentación Turística
        //
        // Mismo cuidado de orden que Involucrados: los segmentos fijos
        // ('nuevo', 'superficie', 'validar', 'resultados') van ANTES que
        // '{sitio}/editar', o una petición a /frecuentacion/validar
        // encajaría en ese patrón con sitio='validar'.
        Route::get('/frecuentacion',                [FrecuentacionController::class, 'index'])->name('frecuentacion.index');
        Route::get('/frecuentacion/nuevo',           [FrecuentacionController::class, 'create'])->name('frecuentacion.create');
        Route::post('/frecuentacion',                [FrecuentacionController::class, 'store'])->name('frecuentacion.store');
        Route::post('/frecuentacion/superficie',     [FrecuentacionController::class, 'actualizarSuperficie'])->name('frecuentacion.superficie');
        Route::post('/frecuentacion/validar',        [FrecuentacionController::class, 'validar'])->name('frecuentacion.validar');
        Route::get('/frecuentacion/resultados',      [FrecuentacionController::class, 'resultados'])->name('frecuentacion.resultados');
        Route::get('/frecuentacion/{sitio}/editar',  [FrecuentacionController::class, 'edit'])->name('frecuentacion.edit');
        Route::put('/frecuentacion/{sitio}',         [FrecuentacionController::class, 'update'])->name('frecuentacion.update');
        Route::delete('/frecuentacion/{sitio}',      [FrecuentacionController::class, 'destroy'])->name('frecuentacion.destroy');
    });
});

require __DIR__ . '/auth.php';
