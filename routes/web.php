<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\{
    IhceconfiguracionesController,IhcetransmisionesController,IhceambientesController,
    IhceconnectorController, IhcedashboardController
};

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


Route::get('/dashboard', [IhcedashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware(['auth', 'verified'])->prefix('ihce')->group(function () {
    
    // --- Módulo de Ambientes (Entornos de Red) ---
    Route::get('/ambientes', [IhceambientesController::class, 'index'])->name('ihce.ambiente.index');
    Route::post('/ambientes', [IhceambientesController::class, 'store'])->name('ihce.ambiente.store');
    Route::put('/ambientes/{id}', [IhceambientesController::class, 'update'])->name('ihce.ambiente.update');
    Route::delete('/ambientes/{id}', [IhceambientesController::class, 'destroy'])->name('ihce.ambiente.destroy');

    // --- Módulo de Configuraciones de Ambientes y Sedes ---
    Route::get('/configuraciones', [IhceconfiguracionesController::class, 'index'])->name('ihce.config.index');
    Route::post('/configuraciones', [IhceconfiguracionesController::class, 'store'])->name('ihce.config.store');
    Route::put('/configuraciones/{id}', [IhceconfiguracionesController::class, 'update'])->name('ihce.config.update');
    Route::delete('/configuraciones/{id}', [IhceconfiguracionesController::class, 'destroy'])->name('ihce.config.destroy');

    // --- Visor Clínico y Logs de Transmisiones ---
    Route::get('/transmisiones/rdapacientes', [IhcetransmisionesController::class, 'rdapacientes'])->name('ihce.transmision.rdapacientes');
    Route::get('/transmisiones/rdaconsulta', [IhcetransmisionesController::class, 'rdaconsulta'])->name('ihce.transmision.rdaconsulta');

    Route::post('/transmisiones/{id}/retry', [IhcetransmisionesController::class, 'retry'])->name('ihce.transmision.retry');
    Route::post('/transmisiones', [IhcetransmisionesController::class, 'store'])->name('ihce.transmision.store'); 

    // El Conector Global (Rutas de acción técnica)
    Route::prefix('ihce/connector')->name('ihce.connector.')->group(function () {
        // Ruta para tu botón actual de testeo
        Route::post('/test/{configuracion}', [IhceconnectorController::class, 'test'])->name('test');

        // 🚀 NUEVA RUTA: Envío y guardado de trazabilidad inmediata FHIR-RDA
        Route::post('/transmision-immediate', [IhceConnectorController::class, 'transmisionImmediate'])->name('transmision.immediate');
    });
    
});

require __DIR__.'/auth.php';
