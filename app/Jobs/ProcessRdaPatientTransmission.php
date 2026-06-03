<?php

namespace App\Jobs;

use App\Models\Ihcetransmisiones;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessRdaPatientTransmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transmission;

    // Número de reintentos automáticos si falla la conexión por red (Tolerancia a fallos)
    public $tries = 3;
    
    // Espera 10 segundos antes de volver a intentar si el backend del gobierno está saturado
    public $backoff = 10; 

    public function __construct(Ihcetransmisiones $transmission)
    {
        $this->transmission = $transmission;
    }

    public function handle(): void
    {
        // Si ya fue aprobada en un intento previo, salimos de la cola.
        if ($this->transmission->status === 'APPROVED') {
            return;
        }

        try {
            // 1. Extraemos y estructuramos la información uniendo tus tablas
            $payload = $this->transmission->compilePatientData();
            
            // Guardamos el payload que se va a enviar para auditoría (Etapa 3)
            $this->transmission->update([
                'source_snapshot_data' => $payload
            ]);

            // 2. LLAMADA AL MOTOR DE INTEROPERABILIDAD (Se construirá a fondo en la Etapa 2)
            // Aquí simulamos la respuesta del endpoint del Ministerio/Entidad de Salud
            // $response = Http::withToken('...')->post('https://api.minsalud.gov/rda/Patient', $payload);
            
            // SIMULACIÓN DE ÉXITO (Para pruebas iniciales de la cola)
            $mockSuccess = true; 

            if ($mockSuccess) {
                $this->transmission->update([
                    'status' => 'APPROVED',
                    'vida_code' => 'VIDA-' . strtoupper(Str::random(10)),
                    'transmitted_at' => now(),
                    'response_log' => null
                ]);
                Log::info("RDA Paciente procesado con éxito para Cita ID: {$this->transmission->cita_id}");
            } else {
                throw new \Exception("Error semántico devuelto por el servidor remoto.");
            }

        } catch (\Exception $e) {
            $this->transmission->increment('retry_count');
            
            $this->transmission->update([
                'status' => 'REJECTED',
                'response_log' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);

            Log::error("Fallo en Cola RDA Paciente (Cita: {$this->transmission->cita_id}): " . $e->getMessage());
            
            // Lanza la excepción para que Laravel Queue sepa que debe reintentar según las reglas de negocio
            throw $e; 
        }
    }
}