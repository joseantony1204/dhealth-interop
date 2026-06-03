<?php

namespace App\Services\Fhir;

class OrganizationResourceService
{
    public function build(string $codigoPrestador, string $tipoIdentificacionPrestador, string $identificacionPrestador): array
    {
        return [
            "resourceType" => "Organization",
            "id" => $codigoPrestador,
            "meta" => [
                "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/CareDeliveryOrganizationRDA"]
            ],
            "identifier" => [
                [
                    "id" => "TaxIdentifier-0",
                    "use" => "official",
                    "type" => [
                        "coding" => [
                            ["system" => "http://terminology.hl7.org/CodeSystem/v2-0203", "code" => "TAX", "display" => "Tax ID number"],
                            ["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianOrganizationIdentifiers", "code" => $tipoIdentificacionPrestador, "display" => "Número de Identificación Tributaria"]
                        ]
                    ],
                    "value" => $identificacionPrestador
                ],
                [
                    "id" => "HealthcareProviderIdentifier-0",
                    "use" => "official",
                    "type" => [
                        "coding" => [
                            ["system" => "http://terminology.hl7.org/CodeSystem/v2-0203", "code" => "PRN", "display" => "Provider number"],
                            ["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianOrganizationIdentifiers", "code" => "CodigoPrestador", "display" => "Código de habilitación de prestador de servicios de salud"]
                        ]
                    ],
                    "system" => "https://fhir.minsalud.gov.co/rda/NamingSystem/REPS",
                    "value" => $codigoPrestador
                ]
            ]
        ];
    }
}