<?php

namespace App\Jobs;

use Throwable;
use App\Models\Ihcetransmisiones;
use App\Services\IhceGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessRdaTransmission implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly int $transmisionId
    ) {}

    public function handle(IhceGatewayService $gateway): void
    {
        $transmision = Ihcetransmisiones::with('configuracion')
            ->findOrFail($this->transmisionId);

        try {

            $bundle = $transmision->source_snapshot_data;

            if (empty($bundle)) {
                throw new \Exception(
                    "La transmisión {$transmision->id} no posee snapshot FHIR."
                );
            }

            $config = $transmision->configuracion;

            $response = match ($transmision->typerda) {

                'RDA_PATIENT' =>
                    $gateway->sendRdaPatientBundle(
                        $config,
                        $bundle
                    ),

                'RDA_CONSULTA' =>
                    $gateway->sendRdaConsultaBundle(
                        $config,
                        $bundle
                    ),

                default =>
                    throw new \Exception(
                        "Tipo RDA no soportado: {$transmision->typerda}"
                    ),
            };

            $transmision->update([
                'last_payload_sent' => $bundle,
                'response_log'      => $response,
                'estado'            => $response['success']
                    ? 'APPROVED'
                    : 'REJECTED',
            ]);

            Log::info(
                "Transmisión {$transmision->id} procesada",
                [
                    'estado' => $transmision->estado
                ]
            );

        } catch (Throwable $e) {

            $transmision->increment('retry_count');

            $transmision->update([
                'estado' => 'ERROR',
                'response_log' => [
                    'message' => $e->getMessage(),
                ]
            ]);

            Log::error(
                "Error procesando transmisión {$transmision->id}",
                [
                    'error' => $e->getMessage()
                ]
            );

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Ihcetransmisiones::where(
            'id',
            $this->transmisionId
        )->update([
            'estado' => 'REJECTED',
            'response_log' => [
                'message' => $e->getMessage(),
            ]
        ]);
    }
}