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

use App\Services\Rdaconsulta\CompositionResourceService;

class RdaConsultaService
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

        // PASO 2: Actualizar el registro con su fotografía inmutable
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
            $result = $this->gatewayService->sendRdaConsultaBundle($config, $transmision->last_payload_sent);

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
        $entidadRef = "EPS005";
        $encounterRef = "Encounter-0";
        $medicoRef = "{$data->med_tipo_id}-{$data->med_identificacion}";
        $prestadorCod = $prestador->emp_codigo_prestador; 
        $prestadorRef = $prestador->emp_codigo_prestador . '-02';
        $tipoIdentificacionPrestador = $prestador->emp_tipo_identificacion; 
        $identificacionPrestador = $prestador->emp_identificacion; 

        $medicamentosRaw = [
            [
                // 🚀 Campos fijos obligatorios por el esquema FHIR RDA del MinSalud
                "status"               => "active",
                "intent"               => "order",
                "categoria_codigo"     => "02", 
                "categoria_display"    => "Medicamento con registro sanitario",
                "finalidad_rips"       => "15", 
                "finalidad_display"    => "DIAGNOSTICO",
                "fecha_formulacion"    => date('Y-m-d\TH:i:sP'), // ISO 8601 (-05:00)

                // 📥 Inyección limpia de tus datos crudos (Raw)
                "codigo_mipres"        => '626',
                "nombre_medicamento"   => 'PARACETAMOL',
                
                // 💊 Dosificación por defecto (El texto completo va en la observación)
                "via_codigo"           => "048", 
                "via_display"          => "ORAL",
                "duracion_valor"       => 30, 
                "duracion_unidad"      => "d",
                "frecuencia_codigo"    => "3", 
                "frecuencia_display"   => "Día",
                
                // Valores mínimos requeridos para que no falle el tipado float/int del método
                "dosis_valor"          => 1.0,
                "dosis_unidad"         => "Tablet",
                "dosis_unidad_codigo"  => "243", // Código genérico para tabletas/unidades
                "cada_cuanto_valor"    => 1.0,
                "cada_cuanto_unidad"   => "Día",
                "cada_cuanto_codigo"   => "3"
            ]
        ];

        $demograficosRaw = [
            [
                "codigo" => "3121", // Código oficial de la Clasificación CIUO-88
                "nombre" => "Analistas de sistemas informáticos"
            ]
        ];

        $incapacidadesRaw = [
            [
                "alcance_codigo" => "01",    // Código oficial MinSalud (Ej: '01' para Nueva, '02' para Prórroga)
                "alcance_nombre" => "Nueva", // Glosa o texto descriptivo del alcance
                "dias_licencia"  => 126      // Cantidad de días de la incapacidad o licencia (Maternidad/Enfermedad)
            ]
        ];

        $diagnosticosRaw = [
            [
                "cie10_codigo" => "I10X",
                "cie10_nombre" => "HIPERTENSION ESENCIAL (PRIMARIA)",
                "texto_libre"  => "Paciente hipertenso controlado con medicamento."
            ],/*
            [
                "cie10_codigo" => "E119",
                "cie10_nombre" => "DIABETES MELLITUS NO INSULINODEPENDIENTE, SIN MENCION DE COMPLICACION",
                "texto_libre"  => "Diabetes tipo 2 controlada con dieta."
            ]*/
        ];

        $alergiasRaw = [
            [
                "codigo" => "01",
                "tipo_display" => "Medicamento",
                "sustancia_texto" => "Alergia severa a la Penicilina (Shock Anafiláctico)"
            ],/*
            [
                "codigo" => "02",
                "tipo_display" => "Alimento",
                "sustancia_texto" => "Intolerancia a los mariscos y pescados"
            ]*/
        ];

        $factoresRaw = [
            [
                "factor_codigo"  => "01",        // Código del catálogo MinSalud
                "factor_display" => "Químicos",  // Glosa del sistema de codificación
                "observacion"    => "Tabaquismo" // Condición específica escrita por el médico
            ]
        ];

        $prescripcionesRaw = [
            [
                "categoria_codigo"  => "06", // Dispositivo médico
                "categoria_display" => "Dispositivo médico",
                "tecnologia_texto"  => "Infusión intravenosa de solución salina normal",
                "fecha_orden"       => "2025-07-15",
                "finalidad_codigo"  => "15",
                "finalidad_display" => "DIAGNOSTICO"
            ],/*
            [
                "categoria_codigo"  => "01", // Procedimiento en salud
                "categoria_display" => "Procedimiento en salud",
                "cups_codigo"       => "399501",
                "cups_nombre"       => "HEMODIÁLISIS ESTÁNDAR CON BICARBONATO",
                "fecha_orden"       => "2025-09-10",
                "finalidad_codigo"  => "15",
                "finalidad_display" => "DIAGNOSTICO"
            ]*/
        ];

        $documentosRaw = [
            [
                "type_loinc_code"    => "18842-5",
                "type_loinc_display" => "Discharge summary",
                "type_col_code"      => "EPI",
                "type_col_display"   => "Epicrisis",
                "category_code"      => "55108-5",
                "category_display"   => "Clinical presentation Document",
                "fecha_documento"    => "2025-02-18T14:00:00-05:00",
                "descripcion"        => "Epicrisis del encuentro de atención en salud - RDA",
                "seguridad_codigo"   => "R",
                "seguridad_display"  => "restricted",
                "format_code"        => "application/pdf",
                "format_display"     => "PDF",
                // Aquí pasas el string Base64 del archivo real (sin prefijos "data:application/pdf;base64,")
                "pdf_base64"         => "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MyAwIG9iago8PCAvVGl0bGUgKERvY3VtZW50LVBERikgL1Byb2R1Y2VyIChtYWNPUyBWZXJzaW9uIDExLjcuMTAgXChCdWlsZCAyMEcxNDI3XCkgUXVhcnR6IFBERkNvbnRleHQpCi9BdXRob3IgKE1hcmlvIEVucmlxdWUgQ29ydGVzIHwgSEw3IENvbG9tYmlhKSAvQ3JlYXRvciAoV29yZCkgL0NyZWF0aW9uRGF0ZQooRDoyMDI0MDMyMjA3MzAyMlowMCcwMCcpIC9Nb2REYXRlIChEOjIwMjQwMzIyMDczMDIyWjAwJzAwJykgPj4KZW5kb2JqCnhyZWYKMCAxNAowMDAwMDAwMDAwIDY1NTM1IGYgCjAwMDAwMDEwMTIgMDAwMDAgbiAKMDAwMDAwMzk2MCAwMDAwMCBuIAowMDAwMDAwMDIyIDAwMDAwIG4gCjAwMDAwMDExMTYgMDAwMDAgbiAKMDAwMDAwMzkyNSAwMDAwMCBuIAowMDAwMDAwMDAwIDAwMDAwIG4gCjAwMDAwMDQwOTIgMDAwMDAgbiAKMDAwMDAwMTIxMyAwMDAwMCBuIAowMDAwMDA0MDQzIDAwMDAwIG4gCjAwMDAwMDQ5MzMgMDAwMDAgbiAKMDAwMDAwNDQwMiAwMDAwMCBuIAowMDAwMDA1MTY5IDAwMDAwIG4gCjAwMDAwMTg4OTkgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSAxNCAvUm9vdCA5IDAgUiAvSW5mbyAxMyAwIFIgL0lEIFsgPDlkMWYxMTZhNjhhMTEyYWNjYjZhODI4OWYxYTAzMzUzPgo8OWQxZjExNmE2OGExMTJhY2NiNmE4Mjg5ZjFhMDMzNTM+IF0gPj4Kc3RhcnR4cmVmCjE5MTUwCiUlRU9GCg==" 
            ]
        ];

        $entidadesRaw = [
            "codigo" => "EPS005",
            "nombre" => "ENTIDAD PROMOTORA DE SALUD SANITAS S.A.S."
        ];

        $sedeRaw = [
            "codigo" => $prestadorCod . "-02", // Ej: "4443000012-01" (Código REPS + Sufijo de sede)
            "prestador" => $prestadorCod, // Ej: "4443000012-01" (Código REPS + Sufijo de sede)
            "nombre" => "IPS MEDIGROUP"
        ];

        // 1. Preparar el array plano con datos mínimos o mapeados
        $citaRaw = [
            "num_factura_o_cita"     => "ADT-HS-9864463-12",
            "modalidad_codigo"       => "01",
            "cups_codigo"            => "890201",
            "cups_display"           => "CONSULTA DE PRIMERA VEZ POR MEDICINA GENERAL",
            "causa_externa_codigo"   => "22",
            "tipo_diag_codigo"       => "02"
        ];

        // Extraer o formatear las colecciones clínicas, asegurando que siempre sean arrays (aunque vengan vacíos o no se pasen)
        //$diagnosticosRaw    = $data->diagnosticos ?? [];    // No puede venir vacío porque el método buildConditionRDASection 
                                                              //espera un array de diagnósticos.
                                                     
        $demograficosRaw    = $data->demograficos ?? [];      // Puede venir de $data o ser un array vacío si no se pasa
        $incapacidadesRaw   = $data->incapacidades ?? [];     // Puede venir de $data o ser un array vacío si no se pasa
        $alergiasRaw        = $data->alergias ?? [];          // Puede venir de $data o ser un array vacío si no se pasa
        $factoresRaw        = $data->factores ?? [];          // Puede venir de $data o ser un array vacío si no se pasa
        //$medicamentosRaw    = $data->medicamentos ?? [];    // No puede venir vacío porque el método buildMedicationRequestSection 
                                                              //espera un array de medicamentos.
   
        $prescripcionesRaw  = $data->prescripciones ?? [];    //Puede venir de $data o ser un array vacío si no se pasa
        //$documentosRaw      = $data->documentos ?? [];        //Se puede aceptar vacio puesto que el metodo genera uno por defecto
                                                              // Base64 correspondiente a: "Sin documentos adjuntos para esta atencion."
                                                              //$base64NoAplica = "U2luIGRvY3VtZW50b3MgYWRqdW50b3MgcGFyYSBlc3RhIGF0ZW5jaW9uLg==";

        // 🚀 PROCESAMIENTO ANTICIPADO DE SECCIONES CLÍNICAS (Dinas & Robustas)
        $entidadData       = $this->clinicalService->buildEntidadesSection($entidadesRaw);
        $demograficosData  = $this->clinicalService->buildDemograficosSection($demograficosRaw, $pacienteRef);
        $incapacidadesData = $this->clinicalService->buildIncapacidadesSection($incapacidadesRaw, $pacienteRef, $encounterRef);

        $conditionData      = $this->clinicalService->buildConditionRDASection($diagnosticosRaw, $pacienteRef);
        $medicationData     = $this->clinicalService->buildMedicationRequestSection($medicamentosRaw, $pacienteRef, $medicoRef, $encounterRef);
        $allergyData        = $this->clinicalService->buildAllergyIntoleranceRDASections($alergiasRaw, $pacienteRef, $encounterRef);
        $factoresData       = $this->clinicalService->buildFactoresSection($factoresRaw, $pacienteRef, $encounterRef);
        
        $encounterData      = $this->clinicalService->buildEncounterResource(
            $citaRaw,$pacienteRef,$medicoRef,
            "Condition-0",      // Apunta al ID semantico del diagnóstico principal
            $prestadorCod,      // Apunta al ID semantico de la sede física
            $prestadorRef,      // Apunta al ID semantico de la sede física
            $entidadRef         // Apunta al ID de la Organización/Aseguradora
            );
        $locationData        = $this->clinicalService->buildLocationResource($sedeRaw);
        $prescripcionesData = $this->clinicalService->buildPrescripcionesSection($prescripcionesRaw,$pacienteRef,$medicoRef,$encounterRef);
        $documentosData     = $this->clinicalService->buildDocumentosSection($documentosRaw, $pacienteRef, $prestadorCod, $encounterRef);

        // Secciones listas para inyectar al bloque Composition
        $entidadesSection     = $entidadData['section'];
        $demograficosSection  = $demograficosData['section'];
        $incapacidadesSection = $incapacidadesData['section'];

        $conditionSection  = $conditionData['section'];
        $allergySection    = $allergyData['section'];
        $factoresSection   = $factoresData['section'];

        $medicationSection     = $medicationData['section'];
        $prescripcionesSection = $prescripcionesData['section'];
        $documentosSection     = $documentosData['section'];

        // Recursos independientes listos para inyección plana en el Bundle
        $demograficosResources  = $demograficosData['resources'];
        $incapacidadResources   = $incapacidadesData['resources'];

        $conditionResources      = $conditionData['resources'] ?? [];
        $allergyResources        = $allergyData['resources'] ?? [];
        $factoresResources       = $factoresData['resources'] ?? [];
        $entidadResources   = $entidadData['resources'] ?? [];
        $encounterResources      = $encounterData['resources'] ?? [];
        $locationResources       = $locationData['resources'] ?? [];
        $medicationResources     = $medicationData['resources'] ?? [];
        $prescripcionesResources = $prescripcionesData['resources'] ?? [];
        $documentosResources     = $documentosData['resources'] ?? [];

      
        //PASO 2. Ensamble del árbol estructurado del JSON FHIR
        $bundle = [
            "resourceType" => "Bundle",
            "language" => "es-CO",
            "type" => "document",
            "entry" => [
                // 💡 Le pasamos la sección procesada al CompositionService para que la renderice en sus secciones
                ["resource" => $this->compositionService->build(
                    $data, $fechaIso, $pacienteRef, $medicoRef, $prestadorCod, $encounterRef,
                    $entidadesSection, 
                    $demograficosSection, 
                    $incapacidadesSection,
                    $conditionSection, 
                    $allergySection, 
                    $factoresSection,
                    $medicationSection, 
                    $prescripcionesSection, 
                    $documentosSection
                    )
                ],

                ["resource" => $this->patientService->build($data, $pacienteRef)],
                ["resource" => $this->organizationService->build($prestadorCod, $tipoIdentificacionPrestador, $identificacionPrestador)],
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

        if (!empty($encounterResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $encounterResources);
        }

        if (!empty($locationResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $locationResources);
        }

        if (!empty($incapacidadResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $incapacidadResources);
        }

        if (!empty($demograficosResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $demograficosResources);
        }

        if (!empty($factoresResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $factoresResources);
        }

        if (!empty($entidadResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $entidadResources);
        }

        if (!empty($prescripcionesResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $prescripcionesResources);
        }

        if (!empty($documentosResources)) {
            $bundle['entry'] = array_merge($bundle['entry'], $documentosResources);
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
    private function getRawMedicalData(int $citaId): object
    {
        try {
            // 1. Consumir el endpoint de la API externa
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.dhealth.token') // Opcional si requiere token
            ])->get("https://api.dhealthcore.local/v1/clinical-records/{$citaId}");
    
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
            throw new Exception("No se pudieron obtener los registros clínicos vía API para la cita ID {$citaId}: " . $e->getMessage());
        }
    }
}