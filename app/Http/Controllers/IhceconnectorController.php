<?php

namespace App\Http\Controllers;

use App\Models\{Ihceconfiguraciones,Ihcetransmisiones};
use App\Services\IhceGatewayService;
use App\Services\RdaPatientService;
use App\Services\RdaConsultaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IhceconnectorController extends Controller
{
    protected $gatewayService;
    protected $rdaPatientService;
    protected $rdaConsultaService;

    // Inyectamos los servicios centralizados
    public function __construct(IhceGatewayService $gatewayService, RdaPatientService $rdaPatientService, RdaConsultaService $rdaConsultaService)
    {
        $this->gatewayService = $gatewayService;
        $this->rdaPatientService = $rdaPatientService;
        $this->rdaConsultaService = $rdaConsultaService;
    }

    /**
     * Acción para probar la conexión desde el panel de control
     */
    public function test(Ihceconfiguraciones $configuracion): JsonResponse
    {
        $resultado = $this->gatewayService->testConexion($configuracion);
        return response()->json($resultado);
    }

    /**
     * Gatillo para forzar la compilación e interoperabilidad inmediata de una cita médica
     */
    public function transmisionImmediate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transmision_id' => 'required|integer|exists:ihce_transmisiones,id',
        ]);

        // Traemos la transmisión junto con su configuración para ahorrar queries en el Service
        $transmision = Ihcetransmisiones::with('configuracion')->findOrFail($validated['transmision_id']);

        // Si ya está aprobada, evitamos un re-envío accidental al Ministerio
        if ($transmision->estado === 'APPROVED') {
            return response()->json([
                'success' => true,
                'estado'  => 'APPROVED',
                'message' => 'Esta transmisión ya fue homologada previamente.'
            ]);
        }

        // Ejecutamos el despacho inmediato
        if($transmision->typerda === 'RDA_PATIENT') {
            $transmision = $this->rdaPatientService->dispatch($transmision);
        } else if($transmision->typerda === 'RDA_CONSULTA') {
            $transmision = $this->rdaConsultaService->dispatch($transmision);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de RDA no soportado para transmisión inmediata.'
            ], 400);
        }
        return response()->json([
            'success'        => $transmision->estado === 'APPROVED',
            'estado'         => $transmision->estado,
            'transmision_id' => $transmision->id,
            'payload_saved'  => $transmision->source_snapshot_data,
            'logs'           => $transmision->response_log
        ]);
    }
}