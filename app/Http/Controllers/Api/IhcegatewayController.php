<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\{IhceGatewayService, RdaPatientService, RdaConsultaService};
use App\Models\{Ihceconfiguraciones,Ihcetransmisiones};
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\{Auth, DB, Log};
use App\Jobs\ProcessRdaTransmission;


class IhcegatewayController extends Controller
{
    protected $gatewayService;
    protected $rdaPatientService;
    protected $rdaConsultaService;

    public function __construct(IhceGatewayService $gatewayService, RdaPatientService $rdaPatientService, RdaConsultaService $rdaConsultaService)
    {
        $this->gatewayService = $gatewayService;
        $this->rdaPatientService = $rdaPatientService;
        $this->rdaConsultaService = $rdaConsultaService;
    }

    /**
     * Muestra la interfaz gráfica de consultas mediante Inertia
     */
    public function index()
    {
        return Inertia::render('ihce/consultas/index', [
            'user' => Auth::user()
            
        ]);
    }

    public function consultarPaciente(Request $request)
    {
        set_time_limit(120);
        $request->validate([
            'tipo_documento' => 'required|string',
            'documento'      => 'required|string',
        ]);

        $config = Ihceconfiguraciones::firstOrFail();
        
        // Extraemos el documento del usuario logueado en Laravel para la auditoría (humanuser)
        $docUsuarioLogueado = Auth::user()->documento ?? '1118851951'; 
        $humanUser = "CC-" . $docUsuarioLogueado;

        // Llamamos al Orquestador Clínico
        $resultado = $this->gatewayService->obtenerHistoriaClinicaCompleta(
            $config,
            $request->input('tipo_documento'),
            $request->input('documento'),
            $humanUser,
            $request->input('fecha_desde') // Nullable
        );

        return response()->json($resultado);
    }

    /**
     * 🚀 Almacenar, construir snapshot y encolar una nueva transmisión automática
     */
    public function encolarTransmision(Request $request)
    {
        $validated = $request->validate(
            [
                'cita_id'          => ['required', 'integer'],
                'typerda'          => ['required', 'string', 'in:RDA_PATIENT,RDA_CONSULTA'],
                'configuracion_id' => ['required', 'integer', 'exists:ihce_configuraciones,id'],
            ],
            [
                'cita_id.required' => 'Debe enviar el id de la cita.',
                'typerda.required' => 'Debe enviar el tipo de RDA.',
                'typerda.in' => 'El tipo de RDA no es válido.',
                'configuracion_id.required' => 'Debe enviar la configuración.',
                'configuracion_id.exists' => 'La configuración indicada no existe.',
            ]
        );

        $existe = Ihcetransmisiones::query()
            ->where('cita_id', $validated['cita_id'])
            ->where('configuracion_id', $validated['configuracion_id'])
            ->where('typerda', $validated['typerda'])
            ->where('estado', 'APPROVED')
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => "La cita #{$validated['cita_id']} ya cuenta con una homologación APPROVED ante MinSalud."
            ], 409);
        }

        try {
            $transmision = DB::transaction(function () use ($validated) {

                $transmision = Ihcetransmisiones::create([
                    'cita_id'              => $validated['cita_id'],
                    'configuracion_id'     => $validated['configuracion_id'],
                    'typerda'              => $validated['typerda'],
                    'estado'               => 'PENDING',
                    'retry_count'          => 0,
                    'source_snapshot_data' => null,
                    'last_payload_sent'    => null,
                    'response_log'         => null,
                    'vida_code'            => null,
                ]);

                $serviceProperty = $this->bundleBuilders[$validated['typerda']];
                $this->{$serviceProperty}->buildBundle($transmision);

                
                return $transmision;
            });
            ProcessRdaTransmission::dispatch(
                $transmision->id
            )->afterCommit();

            return response()->json([
                'success'        => true,
                'transmision_id' => $transmision->id,
                'message'        => 'Transmisión inicializada correctamente.'
            ], 201);

        } catch (\Throwable $e) {

            Log::error('Error al encolar transmisión', [
                'cita_id' => $validated['cita_id'],
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el recurso FHIR.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private array $bundleBuilders = [
        'RDA_PATIENT'  => 'rdaPatientService',
        'RDA_CONSULTA' => 'rdaConsultaService',
    ];

/*
    public function descargarPdf(Request $request, $id)
    {
        $config = Ihceconfiguraciones::firstOrFail(); // Tu modelo de configuración
        
        $resultado = $this->gatewayService->descargarRdaEpicrisis($config, $id);

        if (!$resultado['success']) {
            return response()->json(['error' => $resultado['message']], 500);
        }

        return response($resultado['bytes'])
            ->header('Content-Type', $resultado['content_type'])
            ->header('Content-Disposition', 'inline; filename="rda-'.$id.'.pdf"');
    }*/
}