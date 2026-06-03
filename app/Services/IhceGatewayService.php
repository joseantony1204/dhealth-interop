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

    /**
     * Envía de forma centralizada cualquier recurso o Bundle FHIR al API Gateway del Ministerio
     */
    public function sendRdaPatientBundleX(Ihceconfiguraciones $config, array $fhirBundle): array
    {
        // 1. Obtener Token de Azure de forma automática usando el método existente
        $tokenData = $this->getAccessToken($config);
        $token = $tokenData['access_token'] ?? throw new Exception("No se pudo recuperar el token de acceso para la transmisión.");

        // 2. Construir la URL limpia
        $endpointUrl = rtrim($config->endpoint_url, '/');

        // 3. 🚀 FORZAMOS LA URL EXACTA QUE TE FUNCIONA EN POSTMAN
        $urlExactaMinSalud = "{$endpointUrl}/Composition/\$enviar-rda-paciente";
        
        // 4. Ejecutar la petición física usando withBody para cadenas Raw
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
     * Envía de forma centralizada cualquier recurso o Bundle FHIR al API Gateway del Ministerio
     */
    public function sendRdaConsultaBundleX(Ihceconfiguraciones $config, array $fhirBundle): array
    {
        // 1. Obtener Token de Azure de forma automática usando el método existente
        $tokenData = $this->getAccessToken($config);
        $token = $tokenData['access_token'] ?? throw new Exception("No se pudo recuperar el token de acceso para la transmisión.");

        // 2. Construir la URL limpia
        $endpointUrl = rtrim($config->endpoint_url, '/');

        // 3. 🚀 FORZAMOS LA URL EXACTA QUE TE FUNCIONA EN POSTMAN
        $urlExactaMinSalud = "{$endpointUrl}/Composition/\$enviar-rda-consulta";
        
        // 4. Ejecutar la petición física usando withBody para cadenas Raw
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
}