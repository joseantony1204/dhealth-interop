<?php

namespace App\Services;

use App\Models\Ihcetransmisiones;
use App\Models\Ihceconfiguraciones;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Http;

use App\Services\Fhir\PatientResourceService;
use App\Services\Fhir\OrganizationResourceService;
use App\Services\Fhir\PractitionerResourceService;
use App\Services\Fhir\ClinicalResourcesService;

use App\Services\Rdapatient\CompositionResourceService;

class RdaPatientService
{
    protected $compositionService;
    protected $patientService;
    protected $organizationService;
    protected $practitionerService;
    protected $clinicalService;
    protected $gatewayService;

    // Inyección de dependencias de cada recurso independiente
    // Inyectamos el Gateway centralizado
    public function __construct(
        CompositionResourceService $compositionService,
        PatientResourceService $patientService,
        OrganizationResourceService $organizationService,
        PractitionerResourceService $practitionerService,
        ClinicalResourcesService $clinicalService,
        IhceGatewayService $gatewayService
    ) {
        $this->compositionService = $compositionService;
        $this->patientService = $patientService;
        $this->organizationService = $organizationService;
        $this->practitionerService = $practitionerService;
        $this->clinicalService = $clinicalService;
        $this->gatewayService = $gatewayService;
    }

    public function buildBundle(Ihcetransmisiones $transmision, string $nuevoEstado = 'PENDING'): Ihcetransmisiones
    {
        // PASO 1. Construir el Bundle FHIR estructurado (devuelve un array nativo)
        $fhirBundle = $this->buildJson($transmision);
        
        // PASO 2: Asegurar el formato correcto para la columna de la BD
        // Si tu modelo NO tiene protected $casts = ['source_snapshot_data' => 'array'], 
        // debes guardarlo usando json_encode. Si sí lo tiene, pasas el $fhirBundle directo.
        $snapshotData = is_array($fhirBundle) ? $fhirBundle : json_decode($fhirBundle, true);

        // PASO 3: Actualizar el registro con su fotografía inmutable
        try {
            $transmision->update([
                'estado'               => $nuevoEstado,
                'source_snapshot_data' => is_array($fhirBundle) ? json_encode($fhirBundle) : $fhirBundle,
                'last_attempt_at'      => now()
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Error crítico al persistir el Snapshot FHIR: " . $e->getMessage());
        }

        return $transmision;
    }

    /**
     * Orquesta la compilación de datos, la persistencia en BD y delega el envío al Gateway
     */
    public function dispatch(Ihcetransmisiones $transmision): Ihcetransmisiones
    {
        // 1. Obtener la configuración de forma eficiente (vía relación o fallback a BD si no está eager-loaded)
        $config = $transmision->configuracion ?? Ihceconfiguraciones::findOrFail($transmision->configuracion_id);

        try {
            // Aseguramos que 'source_snapshot_data' sea lo que se mande al Gateway (corrigiendo 'json_snapshot')
            $payload = $transmision->source_snapshot_data;

            // 2. Actualización de auditoría e incremento de reintentos atómico
            $transmision->update([
                'last_payload_sent' => $payload,
                'retry_count'       => DB::raw('COALESCE(retry_count, 0) + 1'),
                'last_attempt_at'   => now()
            ]);
            
            // Refrescamos para obtener el 'retry_count' real recalculado por el motor de BD
            $transmision->refresh();

            // 3. DELEGACIÓN AL GATEWAY SERVICE
            $result = $this->gatewayService->sendRdaPatientBundle($config, $transmision->last_payload_sent);

            // Mapeamos el resultado contra los estados estructurados de tu Ecosistema
            if ($result['success']) {
                $transmision->estado = 'APPROVED';
                // Captura del ID único del documento retornado por MinSalud (Código VIDA)
                $transmision->vida_code = $result['body']['id'] ?? $result['body']['vida_code'] ?? null;
                $transmision->recordResponseLog($result['status'], $result['body'], false);

                // Persistimos la aprobación final de inmediato
                $transmision->save();
            } else {
                $transmision->estado = 'REJECTED';
                $transmision->recordResponseLog($result['status'], $result['body'], true);
                $transmision->save();

                // 🚀 ERROR DE VALIDACIÓN: El JSON falló. Re-compilamos el Snapshot médico para corregirlo
                $transmision = $this->buildBundle($transmision,'REJECTED');
            }

        } catch (\Exception $e) {
            // Captura errores de TLS, conectividad de red local, timeout, DNS, etc.
            $transmision->estado = 'CONNECTIVITY_ERROR';
            $transmision->recordResponseLog(500, ['error' => $e->getMessage()], true);
            $transmision->save();

            // 🚀 ERROR DE CONECTIVIDAD: Volvemos a generar el bundle para asegurar 
            // que en el próximo intento vaya con datos y marcas de tiempo actualizados.
            $transmision = $this->buildBundle($transmision,'CONNECTIVITY_ERROR');
        }

        return $transmision;
    }

    /**
     * Construye el Bundle FHIR estructurado bajo los estándares del MinSalud
     */
    public function buildJson($transmision): array
    {
        //PASO 1. Compilar los datos médicos reales de la BD D-Health
        $prestador = $this->fetchPrestadorData($transmision);
        $data = $this->fetchRawMedicalData($transmision->cita_id);

        $fechaIso = now()->toIso8601String();
        $pacienteRef = "{$data->pac_tipo_id}-{$data->pac_identificacion}";
        $medicoRef = "{$data->med_tipo_id}-{$data->med_identificacion}";
        $codigoPrestador = $prestador->emp_codigo_prestador; 
        $tipoIdentificacionPrestador = $prestador->emp_tipo_identificacion; 
        $identificacionPrestador = $prestador->emp_identificacion; 

        $medicamentosRaw = [
            [
                "codigo" => "961",
                "nombre" => "SULFASOMIZOL",
                "observacion" => "Toma 1 tableta de 500mg cada 8 horas si presenta dolor."
            ],
            [	
                "codigo" => "9899", // Código hipotético de INN Mipres
                "nombre" => "BECLABUVIR",
                "observacion" => "1 tableta de 500mg cada mañana para control de la presión."
            ],
            [	
                "codigo" => "9797", // Código hipotético de INN Mipres
                "nombre" => "DORAVIRINA",
                "observacion" => "1 tableta de 500mg cada mañana para control de la presión."
            ]
        ];

        $diagnosticosRaw = [
            [
                "cie10_codigo" => "I10X",
                "cie10_nombre" => "Hipertensión esencial (primaria)",
                "texto_libre"  => "Paciente hipertenso controlado con medicamento."
            ],
            [
                "cie10_codigo" => "E119",
                "cie10_nombre" => "Diabetes mellitus no insulinodependiente sin complicaciones",
                "texto_libre"  => "Diabetes tipo 2 controlada con dieta."
            ]
        ];

        $alergiasRaw = [
            [
                "codigo" => "01",
                "tipo_display" => "Medicamento",
                "sustancia_texto" => "Alergia severa a la Penicilina (Shock Anafiláctico)"
            ],
            [
                "codigo" => "02",
                "tipo_display" => "Alimento",
                "sustancia_texto" => "Intolerancia a los mariscos y pescados"
            ]
        ];

        $familiaresRaw = [
            [
                "parentesco_codigo" => "01", // Código MinSalud para "Padres"
                "parentesco_display" => "Padres",
                "cie10_codigo" => "Z034",
                "cie10_nombre" => "OBSERVACION POR SOSPECHA DE INFARTO DE MIOCARDIO"
            ],
            [
                "parentesco_codigo" => "02", // Código MinSalud para "Hermanos" (ejemplo)
                "parentesco_display" => "Hermanos",
                "cie10_codigo" => "E119",
                "cie10_nombre" => "DIABETES MELLITUS NO INSULINODEPENDIENTE SIN COMPLICACIONES"
            ]
        ];

        // Extraer o formatear los medicamentos que vienen de $data (asumiendo que viene una colección o array)
        // Si $data->medicamentos no existe o viene de otra consulta, asegúrate de pasarlo como array aquí.
        $medicamentosRaw = $data->medicamentos ?? [];
        $diagnosticosRaw = $data->diagnosticos ?? [];
        $alergiasRaw     = $data->alergias ?? [];
        $familiaresRaw   = $data->antecedentes ?? [];

        // 🚀 PROCESAMIENTO ANTICIPADO DE SECCIONES CLÍNICAS (Dinas & Robustas)
        $conditionData  = $this->clinicalService->buildConditionSection($diagnosticosRaw, $pacienteRef);
        $allergyData    = $this->clinicalService->buildAllergySection($alergiasRaw, $pacienteRef);
        $medicationData = $this->clinicalService->buildMedicationSection($medicamentosRaw, $pacienteRef);
        $familyData     = $this->clinicalService->buildFamilyHistorySection($familiaresRaw, $pacienteRef);
        
        // Secciones listas para inyectar al bloque Composition
        $conditionSection  = $conditionData['section'];
        $allergySection    = $allergyData['section'];
        $medicationSection = $medicationData['section'];
        $familySection     = $familyData['section'];

        // Recursos independientes listos para inyección plana en el Bundle
        $conditionResources  = $conditionData['resources'];
        $allergyResources    = $allergyData['resources'];
        $medicationResources = $medicationData['resources'];
        $familyResources     = $familyData['resources'];

        //PASO 2. Ensamble del árbol estructurado del JSON FHIR
        $bundle = [
            "resourceType" => "Bundle",
            "language" => "es-CO",
            "type" => "document",
            "entry" => [
                // 💡 Le pasamos la sección procesada al CompositionService para que la renderice en sus secciones
                ["resource" => $this->compositionService->build($data, $fechaIso, $pacienteRef, $medicoRef, $codigoPrestador, $conditionSection, $allergySection, $medicationSection, $familySection)],

                ["resource" => $this->patientService->build($data, $pacienteRef)],
                ["resource" => $this->organizationService->build($codigoPrestador, $tipoIdentificacionPrestador, $identificacionPrestador)],
                ["resource" => $this->practitionerService->build($data, $medicoRef)],
            ]
        ];

        // 🚀 INYECCIÓN DINÁMICA DE RECURSOS AL NIVEL ENTRADA (ENTRY) DEL BUNDLE
        if (!empty($conditionResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $conditionResources);
        }

        if (!empty($allergyResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $allergyResources);
        }

        if (!empty($medicationResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $medicationResources);
        }

        if (!empty($familyResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $familyResources);
        }

        return $bundle;
    }

    /**
     * Consulta unificada a la base de datos externa de D-Health Core
     */
    private function fetchPrestadorData($transmision): object
    {
        $data = DB::connection('dhealth_core')
            ->table('cfempresas')
            ->join("cfsedes AS s", "cfempresas.id", "=", "s.empresa_id")
            ->join("cfmaestras AS t","t.id","=","cfempresas.tipoi_id")
            ->select(
                's.nombre as emp_nombre',
                'cfempresas.identificacion as emp_identificacion',
                't.nombre as emp_tipo_identificacion',
                's.prestador as emp_codigo_prestador',
                's.direccion as emp_direccion',
            )
            ->where('s.id', $transmision->configuracion->sede_id)
            ->first(); 

        if (!$data) {
            throw new Exception("No se encontraron registros para el Core: {$transmision->id}");
        }

        return $data;
    }
    
    private function fetchRawMedicalData(int $citaId): object
    {
        $data = DB::connection('dhealth_core')
            ->table('adcitas')
            ->join("cfservicios AS s", "s.id", "=", "adcitas.servicio_id")
            ->join("cfdisponibilidades AS d", "d.id", "=", "adcitas.disponibilidad_id")
            
            ->join("adpacientes AS pc", "pc.id", "=", "adcitas.paciente_id")
            ->join("personas AS p", "p.id", "=", "pc.persona_id")
            ->join("cfmaestras AS t","t.id","=","p.tipoidentificacion_id")
            ->join("cfmaestras AS sx","sx.id","=","p.sexo_id")

            ->join("cffuncionarios AS f", "f.id", "=", "d.funcionario_id")
            ->join("personas AS pf", "pf.id", "=", "f.persona_id")
            ->join("cfmaestras AS tf","tf.id","=","pf.tipoidentificacion_id")

            ->where('adcitas.id', $citaId)
            ->select(
                'p.identificacion as pac_identificacion',

                't.codigo as pac_tipo_id',
                't.nombre as pac_tipo_id_nombre',
                
                'p.nombre as pac_nombre',
                'p.segundonombre as pac_segundonombre',
                'p.apellido as pac_apellido',
                'p.segundoapellido as pac_segundoapellido',
                'p.fechanacimiento as pac_fechanacimiento',
                'p.telefonomovil as pac_telefono',

                'sx.codigo AS pac_codigo_sexo',
                'sx.nombre AS pac_nombre_sexo',
                'sx.observacion AS pac_extra_sexo',
                // BUSCAR ZONA PACIENTE
                DB::raw("(SELECT z.codigo FROM cfmaestras z WHERE z.id = p.zona_id LIMIT 1) AS pac_zona_codigo"),
                DB::raw("(SELECT z.nombre FROM cfmaestras z WHERE z.id = p.zona_id LIMIT 1) AS pac_zona_nombre"),

                // BUSCAR ETNIAS PACIENTE
                DB::raw("(SELECT e.codigo FROM cfmaestras e WHERE e.id = p.etnia_id LIMIT 1) AS pac_etnia_codigo"),
                DB::raw("(SELECT e.nombre FROM cfmaestras e WHERE e.id = p.etnia_id LIMIT 1) AS pac_etnia_nombre"),

                // BUSCAR DISCAPACIDAD PACIENTE
                DB::raw("(SELECT d.codigo FROM cfmaestras d WHERE d.id = p.discapacidad_id LIMIT 1) AS pac_discapacidad_codigo"),
                DB::raw("(SELECT d.nombre FROM cfmaestras d WHERE d.id = p.discapacidad_id LIMIT 1) AS pac_discapacidad_nombre"),

                // BUSCAR PAIS PACIENTE
                DB::raw("(SELECT pa.codigo FROM cfmaestras pa WHERE pa.id = p.pais_id LIMIT 1) AS pac_pais_codigo"),
                DB::raw("(SELECT pa.nombre FROM cfmaestras pa WHERE pa.id = p.pais_id LIMIT 1) AS pac_pais_nombre"),

                // BUSCAR DEPARTAMENTO PACIENTE
                DB::raw("(SELECT de.codigo FROM cfmaestras de WHERE de.id = p.departamento_id LIMIT 1) AS pac_departamento_codigo"),
                DB::raw("(SELECT de.nombre FROM cfmaestras de WHERE de.id = p.departamento_id LIMIT 1) AS pac_departamento_nombre"),

                // BUSCAR CIUDAD PACIENTE
                DB::raw("(SELECT ci.codigo FROM cfmaestras ci WHERE ci.id = p.ciudad_id LIMIT 1) AS pac_ciudad_codigo"),
                DB::raw("(SELECT ci.nombre FROM cfmaestras ci WHERE ci.id = p.ciudad_id LIMIT 1) AS pac_ciudad_nombre"),

                'pf.identificacion as med_identificacion',
                'pf.nombre as med_nombre',
                'pf.segundonombre as med_segundonombre',
                'pf.apellido as med_apellido',
                'pf.segundoapellido as med_segundoapellido',
                'tf.codigo as med_tipo_id',
                'tf.nombre as med_tipo_id_nombre',
               
                'adcitas.fechasolicitud as cita_fecha'
            )
            ->first();

        if (!$data) {
            throw new Exception("No se encontraron registros clínicos en el Core para la cita ID: {$citaId}");
        }
        return $data;
    }

    /**
     * Consume la API externa de D-Health y parsea el JSON a un Objeto stdClass
     */
    private function getRawMedicalData(int $pacienteId): object
    {
        try {
            // 1. Consumir el endpoint de la API externa
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                //'Authorization' => 'Bearer ' . config('services.dhealth.token') // Opcional si requiere token
            ])->get("https://ipsmedigroup.com/dhealth/api/paciente/ficha/{$pacienteId}");
    
            // Si la API responde con un error (440, 500, etc.)
            if ($response->failed()) {
                throw new Exception("La API externa retornó un error: Status {$response->status()}");
            }
          
            // 2. OBTENER EL STRING JSON CRUDO
            $jsonCrudo = $response->body(); 
    
            // 3. CONVERTIR EL JSON EN UN OBJETO (Pasando false como segundo parámetro)
            // Esto genera un objeto stdClass idéntico a lo que retornaba el Query Builder.
            $data = json_decode($jsonCrudo, false);
    
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error de parseo en el JSON de la API: " . json_last_error_msg());
            }
    
            return $data;
    
        } catch (Exception $e) {
            throw new Exception("No se pudieron obtener los registros clínicos vía API para el paciente ID {$pacienteId}: " . $e->getMessage());
        }
    }
}