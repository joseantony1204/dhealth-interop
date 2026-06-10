<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Ihceconfiguraciones;
use Exception;
use Illuminate\Support\Facades\Log;

class IhceGatewayService
{
    /**
     * Motor base para obtener el Access Token desde Azure B2C
     */
    public function getAccessToken(Ihceconfiguraciones $config): array
    {
        $tokenUrl = "https://login.microsoftonline.com/{$config->tenant_id}/oauth2/v2.0/token";

        $response = Http::withoutVerifying()
            ->asForm()
            ->post($tokenUrl, [
                'client_id'     => $config->client_id,
                'client_secret' => $config->client_secret,
                'grant_type'    => 'client_credentials',
                'scope'         => $config->scope,
            ]);

        if ($response->failed()) {
            throw new Exception("Error de Autenticación Azure: " . ($response->json()['error_description'] ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * Test de enlace ampliado con metadatos de auditoría de tokens
     */
    public function testConexion(Ihceconfiguraciones $config): array
    {
        try {
            // 1. Obtenemos la respuesta completa de Azure
            $azureResponse = $this->getAccessToken($config);
            $token = $azureResponse['access_token'] ?? throw new Exception("Token no encontrado.");

            // 2. Evaluamos el API Gateway del Ministerio
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $config->apim_subs_key,
                    'Accept' => 'application/json'
                ])->get($config->endpoint_url);

            // 3. Empaquetamos todo para el Frontend
            return [
                'success' => true,
                'status' => $response->status(),
                'message' => 'API Gateway alcanzado correctamente.',
                'payload' => [
                    'token_type'     => $azureResponse['token_type'] ?? 'Bearer',
                    'expires_in'     => $azureResponse['expires_in'] ?? null,
                    'ext_expires_in' => $azureResponse['ext_expires_in'] ?? null,
                    'access_token'   => $token,
                    'gateway_headers'=> $response->headers()
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function sendRdaBundle(Ihceconfiguraciones $config, array $fhirBundle, string $tipo): array
    {
        // 1. Obtener Token de Azure de forma automática usando el método existente
        $tokenData = $this->getAccessToken($config);
        $token = $tokenData['access_token'] ?? throw new Exception("No se pudo recuperar el token de acceso para la transmisión.");

        // 2. Construir la URL limpia
        $endpointUrl = rtrim($config->endpoint_url, '/');

        // 3. Determinar el recurso dinámico exacto según el tipo solicitado
        $endpointFinal = $tipo === 'consulta' ? '$enviar-rda-consulta' : '$enviar-rda-paciente';
        
        Log::channel('stack')->info("Iniciando envío RDA [Tipo: $tipo] a $endpointFinal");
        
        $urlExactaMinSalud = "{$endpointUrl}/Composition/{$endpointFinal}";
        
        // 4. Ejecutar la petición física usando con cadenas Raw
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Ocp-Apim-Subscription-Key' => $config->apim_subs_key, // Tu APIMSubsKey de Postman
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        ->withBody(
            // Enviamos el Bundle completo inmaculado y sin escapes
            json_encode($fhirBundle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 
            'application/json'
        )
        ->post($urlExactaMinSalud);

        // 5. Retornar un formato estandarizado de respuesta
        return [
            'success'   => $response->successful(),
            'status'    => $response->status(),
            'body'      => $response->json() ?? ['raw_response' => $response->body()]
        ];
    }

    /**
     * Atajo para enviar RDA Paciente (Mantiene compatibilidad hacia atrás)
     */
    public function sendRdaPatientBundle(Ihceconfiguraciones $config, array $fhirBundle): array
    {
        return $this->sendRdaBundle($config, $fhirBundle, 'paciente');
    }

    /**
     * Atajo para enviar RDA Consulta Externa (Mantiene compatibilidad hacia atrás)
     */
    public function sendRdaConsultaBundle(Ihceconfiguraciones $config, array $fhirBundle): array
    {
        return $this->sendRdaBundle($config, $fhirBundle, 'consulta');
    }

    /**
     * OPERACIÓN MAESTRA UNIFICADA (Versión Secuencial de Máxima Compatibilidad)
     * Ejecuta las peticiones de forma estable, extrae referencias cruzadas de secciones FHIR,
     * resuelve recursos individuales faltantes y consolida el historial clínico homologado con Node.js.
     */
    public function obtenerHistoriaClinicaCompleta(
        Ihceconfiguraciones $config, 
        string $tipoDocumento, 
        string $documento, 
        string $humanUser, 
        ?string $fechaDesde = null
    ): array {
        Log::channel('stack')->info("Orquestando motor de compatibilidad secuencial + Extractor para: {$tipoDocumento}-{$documento}");

        try {
            $tokenData = $this->getAccessToken($config);
            $token = $tokenData['access_token'] ?? throw new Exception("Token de seguridad no disponible.");
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Error al obtener Token: " . $e->getMessage()
            ];
        }

        // 1. Construcción del Payload estándar (Parameters)
        $parameters = [
            "resourceType" => "Parameters",
            "parameter" => [
                [
                    "name" => "identifier",
                    "part" => [
                        ["name" => "type", "valueString" => trim($tipoDocumento)],
                        ["name" => "value", "valueString" => trim((string)$documento)]
                    ]
                ],
                ["name" => "humanuser", "valueString" => trim($humanUser)]
            ]
        ];

        if (!empty($fechaDesde)) {
            $parameters['parameter'][] = ["name" => "fechaDesde", "valueDate" => $fechaDesde];
        }

        $endpointUrl = rtrim($config->endpoint_url, '/');
        $jsonPayload = json_encode($parameters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $urlPaciente = "{$endpointUrl}/Composition/\$consultar-rda-paciente";
        $urlEncuentros = "{$endpointUrl}/Composition/\$consultar-rda-encuentros-clinicos";
        $urlInmunizacion = "{$endpointUrl}/Immunization/\$consultar-inmunizacion";

        // 2. Ejecución secuencial robusta de las peticiones RDA primarias
        $resPaciente = null;
        $resEncuentros = null;
        $resInmunizacion = null;

        // Petición 1: RDA Paciente
        try {
            $resPaciente = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Ocp-Apim-Subscription-Key' => $config->apim_subs_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->withBody($jsonPayload, 'application/json')->post($urlPaciente);
        } catch (Exception $e) {
            Log::channel('stack')->error("Fallo secuencial RDA Paciente: " . $e->getMessage());
        }

        // Petición 2: RDA Encuentros
        try {
            $resEncuentros = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Ocp-Apim-Subscription-Key' => $config->apim_subs_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->withBody($jsonPayload, 'application/json')->post($urlEncuentros);
        } catch (Exception $e) {
            Log::channel('stack')->error("Fallo secuencial RDA Encuentros: " . $e->getMessage());
        }

        // Petición 3: Vacunas PAIWEB
        try {
            $resInmunizacion = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Ocp-Apim-Subscription-Key' => $config->apim_subs_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->withBody($jsonPayload, 'application/json')->post($urlInmunizacion);
        } catch (Exception $e) {
            Log::channel('stack')->error("Fallo secuencial Inmunización: " . $e->getMessage());
        }

        $allEntries = [];
        $entriesPaciente = [];
        $entriesEncuentros = [];
        $paginationLinks = [];

        // Procesar Entrada de RDA Paciente
        if ($resPaciente && $resPaciente->successful() && isset($resPaciente->json()['entry'])) {
            if (isset($resPaciente->json()['link'])) {
                $paginationLinks = $resPaciente->json()['link'];
            }
            foreach ($resPaciente->json()['entry'] as $entry) {
                $entry['_source'] = 'rda-paciente';
                $entry['_sourceLabel'] = 'RDA de antecedentes manifestados por el paciente';
                $allEntries[] = $entry;
                $entriesPaciente[] = $entry;
            }
        }

        // Procesar Entrada de RDA Encuentros
        if ($resEncuentros && $resEncuentros->successful() && isset($resEncuentros->json()['entry'])) {
            if (empty($paginationLinks) && isset($resEncuentros->json()['link'])) {
                $paginationLinks = $resEncuentros->json()['link'];
            }
            foreach ($resEncuentros->json()['entry'] as $entry) {
                $entry['_source'] = 'rda-encuentros';
                $entry['_sourceLabel'] = 'RDA de encuentros clínicos';
                $allEntries[] = $entry;
                $entriesEncuentros[] = $entry;
            }
        }

        // Armar el Bundle combinado idéntico al de Node.js
        $combinedBundle = [
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => count($allEntries),
            'entry' => $allEntries
        ];

        // 3. PROCESAMIENTO DE RECURSOS REFERENCIADOS CRUZADOS (Secuencial Estable)
        $referencedResources = [
            'patients'                  => [],
            'encounters'                => [],
            'practitioners'             => [],
            'practitionerRoles'         => [],
            'organizations'             => [],
            'locations'                 => [],
            'conditions'                => [],
            'allergyIntolerances'       => [],
            'medicationStatements'      => [],
            'medicationAdministrations' => [],
            'medicationRequests'        => [],
            'familyMemberHistories'     => [],
            'procedures'                => [],
            'observations'              => [],
            'riskAssessments'           => [],
            'serviceRequests'           => [],
            'documentReferences'        => []
        ];

        if (count($allEntries) > 0) {
            try {
                $referencedResources = $this->obtenerRecursosReferenciadosInterno($combinedBundle, $endpointUrl, $token, $config->apim_subs_key, $referencedResources);
            } catch (Exception $refError) {
                Log::channel('stack')->error("⚠️ Error obteniendo recursos referenciados: " . $refError->getMessage());
            }
        }

        // 4. Procesar Vacunas PAIWEB
        $vacunasBundle = [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'entry' => []
        ];

        if ($resInmunizacion && $resInmunizacion->successful() && isset($resInmunizacion->json()['entry'])) {
            foreach ($resInmunizacion->json()['entry'] as $vEntry) {
                $vEntry['_source'] = 'inmunizacion';
                $vEntry['_sourceLabel'] = 'Registro de inmunización';
                $vacunasBundle['entry'][] = $vEntry;

                if (isset($vEntry['resource'])) {
                    $this->categorizeResourceInterno($vEntry['resource'], $referencedResources);
                }
            }
        }

        // 5. Retornar respuesta final exacta
        return [
            'resourceType'    => 'Bundle',
            'type'            => 'searchset',
            'total'           => count($allEntries),
            'entry'           => $allEntries,
            'link'            => $paginationLinks,
            'entriesBySource' => [
                'paciente'   => $entriesPaciente,
                'encuentros' => $entriesEncuentros
            ],
            'vacunas_bundle'  => $vacunasBundle,
            'rdaDetails'      => [
                'paciente' => [
                    'status'  => ($resPaciente && $resPaciente->successful()) ? 'fulfilled' : 'rejected',
                    'total'   => ($resPaciente && $resPaciente->successful()) ? ($resPaciente->json()['total'] ?? 0) : 0,
                    'entries' => count($entriesPaciente),
                    'error'   => ($resPaciente && !$resPaciente->successful()) ? $resPaciente->body() : ($resPaciente ? null : 'Error físico de red o timeout')
                ],
                'encuentros' => [
                    'status'  => ($resEncuentros && $resEncuentros->successful()) ? 'fulfilled' : 'rejected',
                    'total'   => ($resEncuentros && $resEncuentros->successful()) ? ($resEncuentros->json()['total'] ?? 0) : 0,
                    'entries' => count($entriesEncuentros),
                    'error'   => ($resEncuentros && !$resEncuentros->successful()) ? $resEncuentros->body() : ($resEncuentros ? null : 'Error físico de red o timeout')
                ]
            ],
            'referencedResources' => $referencedResources,
            'summary' => [
                'totalCompositions'      => count(collect($allEntries)->where('resource.resourceType', 'Composition')),
                'compositionsPaciente'   => count(collect($entriesPaciente)->where('resource.resourceType', 'Composition')),
                'compositionsEncuentros' => count(collect($entriesEncuentros)->where('resource.resourceType', 'Composition')),
                'patients'               => count($referencedResources['patients']),
                'practitioners'          => count($referencedResources['practitioners']),
                'organizations'          => count($referencedResources['organizations']),
                'conditions'             => count($referencedResources['conditions']),
                'allergyIntolerances'    => count($referencedResources['allergyIntolerances']),
                'medicationStatements'   => count($referencedResources['medicationStatements']),
            ]
        ];
    }

    /**
     * MÉTODOS AUXILIARES SECUENCIALES SEGUROS
     */

    /**
     * MÉTODOS AUXILIARES CONCURRENTES OPTIMIZADOS (Velocidad de Node.js en PHP)
     */
    private function obtenerRecursosReferenciadosInterno(array $compositionBundle, string $baseUrl, string $token, string $subsKey, array &$referencedResources): array 
    {
        $embeddedIndex = [];

        // 1. Indexar recursos que ya vengan incrustados
        if (isset($compositionBundle['entry']) && is_array($compositionBundle['entry'])) {
            foreach ($compositionBundle['entry'] as $e) {
                $r = $e['resource'] ?? null;
                if ($r && isset($r['resourceType']) && isset($r['id'])) {
                    $key = "{$r['resourceType']}/{$r['id']}";
                    $embeddedIndex[$key] = $r;
                }
            }
        }

        // 2. Extraer listado de referencias únicas en texto plano
        $allReferences = $this->extractReferencesInterno($compositionBundle);

        // 3. Filtrar las referencias que NO están incrustadas
        $neededReferences = [];
        foreach ($allReferences as $ref) {
            $key = ltrim($ref, '/');
            if (!isset($embeddedIndex[$key])) {
                $neededReferences[] = $ref;
            }
        }

        // 4. DISPARO CONCURRENTE MASIVO (Aquí ganamos la velocidad del Ministerio)
        if (!empty($neededReferences)) {
            Log::channel('stack')->info("Disparando descarga paralela de " . count($neededReferences) . " recursos clínicos.");
            
            $fetchResponses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($neededReferences, $baseUrl, $token, $subsKey) {
                $promises = [];
                foreach ($neededReferences as $reference) {
                    $url = str_starts_with($reference, 'http') ? $reference : "{$baseUrl}/{$reference}";
                    
                    // Configuramos cada hilo de forma independiente con sus credenciales y quitando SSL
                    $promises[$reference] = $pool->withoutVerifying()
                        ->timeout(15) // Evita colgar el pool si un recurso falla
                        ->withHeaders([
                            'Authorization'             => 'Bearer ' . $token,
                            'Ocp-Apim-Subscription-Key' => $subsKey,
                            'Accept'                    => 'application/json'
                        ])->get($url);
                }
                return $promises;
            });

            // Procesar y categorizar los resultados del pool asíncrono
            foreach ($fetchResponses as $ref => $response) {
                if ($response && $response->successful()) {
                    $this->categorizeResourceInterno($response->json(), $referencedResources);
                } else {
                    Log::channel('stack')->warning("No se pudo obtener la referencia concurrente [{$ref}]. Status: " . ($response ? $response->status() : 'Error de red'));
                }
            }
        }

        // 5. Categorizar recursos que ya venían embebidos
        foreach ($embeddedIndex as $resource) {
            $this->categorizeResourceInterno($resource, $referencedResources);
        }

        // 6. Forzar la inclusión de todos los DocumentReference del bundle
        if (isset($compositionBundle['entry']) && is_array($compositionBundle['entry'])) {
            foreach ($compositionBundle['entry'] as $entry) {
                $r = $entry['resource'] ?? null;
                if ($r && isset($r['resourceType']) && $r['resourceType'] === 'DocumentReference') {
                    $this->categorizeResourceInterno($r, $referencedResources);
                }
            }
        }

        return $referencedResources;
    }

    private function obtenerRecursosReferenciadosInternos(array $compositionBundle, string $baseUrl, string $token, string $subsKey, array &$referencedResources): array 
    {
        $embeddedIndex = [];

        // 1. Indexar recursos incrustados
        if (isset($compositionBundle['entry']) && is_array($compositionBundle['entry'])) {
            foreach ($compositionBundle['entry'] as $e) {
                $r = $e['resource'] ?? null;
                if ($r && isset($r['resourceType']) && isset($r['id'])) {
                    $key = "{$r['resourceType']}/{$r['id']}";
                    $embeddedIndex[$key] = $r;
                }
            }
        }

        // 2. Extraer listado de referencias en texto plano
        $allReferences = $this->extractReferencesInterno($compositionBundle);

        // 3. Descarga secuencial controlada de referencias faltantes
        foreach ($allReferences as $reference) {
            $key = ltrim($reference, '/');
            if (!isset($embeddedIndex[$key])) {
                try {
                    $url = str_starts_with($reference, 'http') ? $reference : "{$baseUrl}/{$reference}";
                    $response = Http::withoutVerifying()
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Ocp-Apim-Subscription-Key' => $subsKey,
                            'Accept' => 'application/json'
                        ])->get($url);

                    if ($response && $response->successful()) {
                        $this->categorizeResourceInterno($response->json(), $referencedResources);
                    }
                } catch (Exception $e) {
                    Log::channel('stack')->warning("No se pudo obtener la referencia individual {$reference}: " . $e->getMessage());
                }
            }
        }

        // 4. Categorizar recursos embebidos
        foreach ($embeddedIndex as $resource) {
            $this->categorizeResourceInterno($resource, $referencedResources);
        }

        // 5. Forzar la inclusión de todos los DocumentReference del bundle
        if (isset($compositionBundle['entry']) && is_array($compositionBundle['entry'])) {
            foreach ($compositionBundle['entry'] as $entry) {
                $r = $entry['resource'] ?? null;
                if ($r && isset($r['resourceType']) && $r['resourceType'] === 'DocumentReference') {
                    $this->categorizeResourceInterno($r, $referencedResources);
                }
            }
        }

        return $referencedResources;
    }

    private function extractReferencesInterno(array $compositionBundle): array
    {
        $allReferences = [];

        if (!isset($compositionBundle['entry']) || !is_array($compositionBundle['entry'])) {
            return $allReferences;
        }

        foreach ($compositionBundle['entry'] as $entry) {
            $resource = $entry['resource'] ?? null;
            if (!$resource) continue;

            if ($resource['resourceType'] === 'Composition') {
                if (isset($resource['subject']['reference'])) $allReferences[] = $resource['subject']['reference'];
                if (isset($resource['encounter']['reference'])) $allReferences[] = $resource['encounter']['reference'];
                if (isset($resource['custodian']['reference'])) $allReferences[] = $resource['custodian']['reference'];

                if (isset($resource['author']) && is_array($resource['author'])) {
                    foreach ($resource['author'] as $a) {
                        if (isset($a['reference'])) $allReferences[] = $a['reference'];
                    }
                }

                if (isset($resource['attester']) && is_array($resource['attester'])) {
                    foreach ($resource['attester'] as $at) {
                        if (isset($at['party']['reference'])) $allReferences[] = $at['party']['reference'];
                    }
                }

                if (isset($resource['section']) && is_array($resource['section'])) {
                    foreach ($resource['section'] as $section) {
                        if (isset($section['entry']) && is_array($section['entry'])) {
                            foreach ($section['entry'] as $se) {
                                if (isset($se['reference'])) $allReferences[] = $se['reference'];
                            }
                        }
                    }
                }
            }

            if ($resource['resourceType'] === 'Encounter') {
                if (isset($resource['subject']['reference'])) $allReferences[] = $resource['subject']['reference'];
                if (isset($resource['serviceProvider']['reference'])) $allReferences[] = $resource['serviceProvider']['reference'];

                if (isset($resource['participant']) && is_array($resource['participant'])) {
                    foreach ($resource['participant'] as $p) {
                        if (isset($p['individual']['reference'])) $allReferences[] = $p['individual']['reference'];
                    }
                }

                if (isset($resource['diagnosis']) && is_array($resource['diagnosis'])) {
                    foreach ($resource['diagnosis'] as $d) {
                        if (isset($d['condition']['reference'])) $allReferences[] = $d['condition']['reference'];
                    }
                }
            }

            if ($resource['resourceType'] === 'DocumentReference' && isset($resource['id'])) {
                $allReferences[] = "DocumentReference/{$resource['id']}";
            }
        }

        return array_unique($allReferences);
    }

    private function categorizeResourceInterno(?array $resource, array &$referencedResources): void
    {
        if (!$resource || !isset($resource['resourceType']) || !isset($resource['id'])) return;

        $typeMap = [
            'Patient'                  => 'patients',
            'Encounter'                => 'encounters',
            'Practitioner'             => 'practitioners',
            'PractitionerRole'         => 'practitionerRoles',
            'Organization'             => 'organizations',
            'Location'                 => 'locations',
            'Condition'                => 'conditions',
            'AllergyIntolerance'       => 'allergyIntolerances',
            'MedicationStatement'      => 'medicationStatements',
            'MedicationAdministration' => 'medicationAdministrations',
            'MedicationRequest'        => 'medicationRequests',
            'FamilyMemberHistory'      => 'familyMemberHistories',
            'Procedure'                => 'procedures',
            'Observation'              => 'observations',
            'RiskAssessment'           => 'riskAssessments',
            'ServiceRequest'           => 'serviceRequests',
            'DocumentReference'        => 'documentReferences'
        ];

        $resourceType = $resource['resourceType'];
        $category = $typeMap[$resourceType] ?? null;

        if ($category) {
            $exists = false;
            foreach ($referencedResources[$category] as $existing) {
                if (isset($existing['id']) && $existing['id'] === $resource['id']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $referencedResources[$category][] = $resource;
            }
        }
    }
   
}