<?php

use Illuminate\Support\Facades\{Route};
use App\Http\Controllers\Api\{
    IhcegatewayController
};

// 1. Ruta para renderizar la interfaz gráfica de Consultas 
Route::get('/consultas', [IhcegatewayController::class, 'index'])
    ->name('ihce.consultas.index');

// 2. CONSULTAR: Ruta para consultar paciente desde D-HEALTH, apuntando al nuevo método en el controlador
Route::post('/gateway/consultar-paciente', [IhcegatewayController::class, 'consultarPaciente'])
    ->name('api.gateway.consultar-paciente');


// 3. ENCOLAR: Encolar nueva cita proveniente de D-HEALTH, apuntando al nuevo método en el controlador
Route::post('/gateway/encolar-transmision', [IhcegatewayController::class, 'encolarTransmision'])
    ->name('api.gateway.encolar-transmision');