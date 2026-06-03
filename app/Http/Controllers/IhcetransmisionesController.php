<?php

namespace App\Http\Controllers;

use App\Models\{Ihcetransmisiones, Ihceconfiguraciones};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\{RdaPatientService, RdaConsultaService};

class IhcetransmisionesController extends Controller
{
    protected $rdaPatientService;
    protected $rdaConsultaService;

    public function __construct(RdaPatientService $rdaPatientService, RdaConsultaService $rdaConsultaService)
    {
        $this->rdaPatientService = $rdaPatientService;
        $this->rdaConsultaService = $rdaConsultaService;
    }

    /**
     * Carga el visor de control con filtros avanzados de auditoría
     */
    public function rdapacientes(Request $request)
    {
        // Sistema de ordenamiento por defecto o solicitado
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Validar columnas permitidas para evitar inyecciones SQL
        $allowedSorts = ['cita_id', 'estado', 'vida_code', 'retry_count', 'created_at', 'last_attempt_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        // Base query para métricas globales
        $metricsQuery = Ihcetransmisiones::query()->where('typerda', 'RDA_PATIENT');

        if ($request->filled('estado')) {
            $metricsQuery->where('estado', $request->estado);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $metricsQuery->where(function($q) use ($search) {
                $q->where('vida_code', 'like', "%{$search}%")
                ->orWhere('cita_id', $search);
            });
        }

        $totalGlobal = (clone $metricsQuery)->count();
        $procesadasGlobal = (clone $metricsQuery)->where('estado', 'APPROVED')->count();
        $fallidasGlobal = (clone $metricsQuery)->whereIn('estado', ['REJECTED', 'CONNECTIVITY_ERROR'])->count();
        $colaGlobal = $totalGlobal - $procesadasGlobal - $fallidasGlobal;
        $tasaExitoGlobal = $totalGlobal > 0 ? round(($procesadasGlobal / $totalGlobal) * 100) : 100;

        // Consulta de la tabla con orden dinámico aplicado
        $query = Ihcetransmisiones::query()->with(['configuracion.ambiente'])->where('typerda', 'RDA_PATIENT');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('vida_code', 'like', "%{$search}%")
                ->orWhere('cita_id', $search);
            });
        }

        return Inertia::render('ihce/transmisiones/pacientes/index', [
            'transmisiones'   => $query->orderBy($sortBy, $sortOrder)->paginate(15)->withQueryString(),
            'configuraciones' => Ihceconfiguraciones::with(['ambiente'])->get(),
            'filters'         => $request->only(['estado', 'search', 'sort_by', 'sort_order']),
            'globalMetrics'   => [
                'total'       => $totalGlobal,
                'procesadas'  => $procesadasGlobal,
                'fallidas'    => $fallidasGlobal,
                'cola'        => $colaGlobal,
                'tasaExito'   => $tasaExitoGlobal,
            ]
        ]);
    }

    /**
     * Carga el visor de control con filtros avanzados de auditoría
     */
    public function rdaconsulta(Request $request)
    {
        // Sistema de ordenamiento por defecto o solicitado
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Validar columnas permitidas para evitar inyecciones SQL
        $allowedSorts = ['cita_id', 'estado', 'vida_code', 'retry_count', 'created_at', 'last_attempt_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        // Base query para métricas globales
        $metricsQuery = Ihcetransmisiones::query()->where('typerda', 'RDA_CONSULTA');

        if ($request->filled('estado')) {
            $metricsQuery->where('estado', $request->estado);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $metricsQuery->where(function($q) use ($search) {
                $q->where('vida_code', 'like', "%{$search}%")
                ->orWhere('cita_id', $search);
            });
        }

        $totalGlobal = (clone $metricsQuery)->count();
        $procesadasGlobal = (clone $metricsQuery)->where('estado', 'APPROVED')->count();
        $fallidasGlobal = (clone $metricsQuery)->whereIn('estado', ['REJECTED', 'CONNECTIVITY_ERROR'])->count();
        $colaGlobal = $totalGlobal - $procesadasGlobal - $fallidasGlobal;
        $tasaExitoGlobal = $totalGlobal > 0 ? round(($procesadasGlobal / $totalGlobal) * 100) : 100;

        // Consulta de la tabla con orden dinámico aplicado
        $query = Ihcetransmisiones::query()->with(['configuracion.ambiente'])->where('typerda', 'RDA_CONSULTA');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('vida_code', 'like', "%{$search}%")
                ->orWhere('cita_id', $search);
            });
        }

        return Inertia::render('ihce/transmisiones/consulta/index', [
            'transmisiones'   => $query->orderBy($sortBy, $sortOrder)->paginate(15)->withQueryString(),
            'configuraciones' => Ihceconfiguraciones::with(['ambiente'])->get(),
            'filters'         => $request->only(['estado', 'search', 'sort_by', 'sort_order']),
            'globalMetrics'   => [
                'total'       => $totalGlobal,
                'procesadas'  => $procesadasGlobal,
                'fallidas'    => $fallidasGlobal,
                'cola'        => $colaGlobal,
                'tasaExito'   => $tasaExitoGlobal,
            ]
        ]);
    }

    /**
     * Prepara y fuerza el reenvío manual de un registro bloqueado o rechazado
     */
    public function retry($id)
    {
        $transmision = Ihcetransmisiones::findOrFail($id);
        
        $transmision->update([
            'estado'          => 'PENDING',
            'last_attempt_at' => now()
        ]);

        // HITO COLA ASÍNCRONA:
        // ProcessRdaPatientTransmission::dispatch($transmision);

        return redirect()->back()->with('success', 'La transmisión ha sido añadida a la cola de reintentos.');
    }

    /**
     * 🚀 Almacenar, construir snapshot y encolar una nueva transmisión manual
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cita_id'          => 'required|integer',
            'typerda'          => 'required|string|in:RDA_PATIENT,RDA_CONSULTA',
            'configuracion_id' => 'required|integer|exists:ihce_configuraciones,id',
        ]);

        // Evitar duplicados con estado exitoso APPROVED
        $existe = Ihcetransmisiones::where('cita_id', $validated['cita_id'])
            ->where('configuracion_id', $validated['configuracion_id'])
            ->where('typerda', $validated['typerda'])
            ->where('estado', 'APPROVED')
            ->exists();

        if ($existe) {
            return back()->withErrors([
                'cita_id' => "La cita #{$validated['cita_id']} ya cuenta con una homologación APPROVED exitosa ante MinSalud."
            ]);
        }

        // Ejecución atómica: Si el constructor FHIR falla, no se crea un registro vacío en la BD
        try {
            DB::transaction(function () use ($validated) {
                $transmision = Ihcetransmisiones::create([
                    'cita_id'              => $validated['cita_id'],
                    'configuracion_id'     => $validated['configuracion_id'],
                    'typerda'              => $validated['typerda'],
                    'estado'               => 'PENDING',
                    'retry_count'          => 0,
                    'source_snapshot_data' => null,
                    'last_payload_sent'    => null,
                    'response_log'         => null,
                    'vida_code'            => null
                ]);

                // Construye el JSON y actualiza el snapshot inmutable
                if ($validated['typerda'] === 'RDA_PATIENT') {
                    $this->rdaPatientService->buildBundle($transmision);
                } elseif ($validated['typerda'] === 'RDA_CONSULTA') {
                    $this->rdaConsultaService->buildBundle($transmision);
                }
                // HITO COLA ASÍNCRONA AUTOMATIZADA:
                // ProcessRdaPatientTransmission::dispatch($transmision);
            });

            return back()->with('success', 'Transmisión manual inicializada y procesada en cola correctamente.');

        } catch (\Exception $e) {
            return back()->withErrors([
                'cita_id' => 'Error al procesar el mapeo del recurso FHIR: ' . $e->getMessage()
            ]);
        }
    }

    /* Al guardar la cita en D-Health, registramos el servicio en nuestra tabla de control
    $transmission = \App\Models\RdaTransmission::create([
        'cita_id' => $cita->id,
        'adpaciente_id' => $cita->paciente_id,
        'status' => 'PENDING',
    ]);

    // Lo empujamos de inmediato a la cola asíncrona automatizada
    \App\Jobs\ProcessRdaPatientTransmission::dispatch($transmission);*/
}