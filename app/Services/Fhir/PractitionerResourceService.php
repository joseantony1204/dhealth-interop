<?php

namespace App\Services\Fhir;

class PractitionerResourceService
{
    public function build(object $data, string $medicoRef): array
    {
        return [
            "resourceType" => "Practitioner",
            "id" => $medicoRef,
            "meta" => [
                "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/PractitionerRDA"]
            ],
            "identifier" => [[
                "id" => "NationalPersonIdentifier-0",
                "use" => "official",
                "type" => [
                    "coding" => [
                        ["system" => "http://terminology.hl7.org/CodeSystem/v2-0203", "code" => "PN", "display" => "Person number"],
                        ["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianPersonIdentifier", "code" => $data->med_tipo_id, "display" => $data->med_tipo_id_nombre]
                    ]
                ],
                "value" => $data->med_identificacion
            ]],
            "name" => [[
                "use" => "official",
                "family" => trim("{$data->med_apellido} {$data->med_segundoapellido}"),
                "_family" => [
                    "extension" => [
                        ["url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionFathersFamilyName", "valueString" => $data->med_apellido ?? ''],
                        ["url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionMothersFamilyName", "valueString" => $data->med_segundoapellido ?? '']
                    ]
                ],
                "given" => array_values(array_filter([$data->med_nombre, $data->med_segundonombre]))
            ]]
        ];
    }
}