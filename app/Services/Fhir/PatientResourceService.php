<?php

namespace App\Services\Fhir;

class PatientResourceService
{
    public function build(object $data, string $pacienteRef): array
    {
        $birthDateClean = $data->pac_fechanacimiento ? date('Y-m-d', strtotime($data->pac_fechanacimiento)) : "1990-01-01";

        return [
            "resourceType" => "Patient",
            "id" => $pacienteRef,
            "meta" => [
                "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/PatientRDA"]
            ],
            "extension" => [
                ["url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionPatientNationality", "valueCoding" => ["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ISO31661", "code" => $data->pac_pais_codigo ?: "170", "display" => $data->pac_pais_nombre ?: "Colombia"]],
                ["url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionPatientEthnicity", "valueCoding" => ["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianEthnicGroup", "code" => $data->pac_etnia_codigo ?: "6", "display" => $data->pac_etnia_nombre ?: "Otras etnias"]],
                ["url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionPatientDisability", "valueCoding" => ["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianDisabilityClassification", "code" => $data->pac_discapacidad_codigo ?: "08", "display" => $data->pac_discapacidad_nombre ?: "Sin discapacidad"]],
                ["url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionPatientGenderIdentity", "valueCoding" => ["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianGenderIdentity", "code" => $data->pac_extra_sexo, "display" => $data->pac_nombre_sexo]]
            ],
            "identifier" => [[
                "type" => [
                    "coding" => [
                        ["system" => "http://terminology.hl7.org/CodeSystem/v2-0203", "code" => "PN", "display" => "Person number"],
                        ["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianPersonIdentifier", "code" => $data->pac_tipo_id, "display" => $data->pac_tipo_id_nombre]
                    ]
                ],
                "id" => "NationalPersonIdentifier-0",
                "use" => "official",
                "system" => "https://fhir.minsalud.gov.co/rda/NamingSystem/RNEC",
                "value" => $data->pac_identificacion
            ]],
            "name" => [[
                "given" => array_values(array_filter([$data->pac_nombre, $data->pac_segundonombre])),
                "use" => "official",
                "family" => trim("{$data->pac_apellido} {$data->pac_segundoapellido}"),
                "_family" => [
                    "extension" => [
                        ["url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionFathersFamilyName", "valueString" => $data->pac_apellido ?? ''],
                        ["url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionMothersFamilyName", "valueString" => $data->pac_segundoapellido ?? '']
                    ]
                ]
            ]],
            "telecom" => [["system" => "phone", "value" => $data->pac_telefono ?? '3000000000', "use" => "mobile"]],
            "address" => [[
                "id" => "HomeAddress-0",
                "use" => "home",
                "type" => "physical",
                "city" => $data->pac_ciudad_nombre ?: "RIOHACHA",
                "_city" => [
                    "extension" => [[
                        "url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionDivipolaMunicipality",
                        "valueCoding" => [
                            "code" => $data->pac_ciudad_codigo ?: "44001",
                            "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/DIVIPOLA"
                        ]
                    ]]
                ],
                "country" => $data->pac_pais_nombre ?: "Colombia",
                "_country" => [
                    "extension" => [[
                        "url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionCountryCode",
                        "valueCoding" => [
                            "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ISO31661",
                            "code" => $data->pac_pais_codigo ?: "170",
                        ]
                    ]]
                ],
                "extension" => [[
                    "url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionResidenceZone",
                    "valueCoding" => [
                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianResidenceZone",
                        "code" => ($data->pac_zona_codigo == '01') ? "02" : "01",
                        "display" => $data->pac_zona_nombre ?: "Urbana",
                    ]
                ]]
            ]],
            "active" => true,
            "gender" => ($data->pac_codigo_sexo == 'F') ? "female" : "male",
            "_gender" => [
                "extension" => [[
                    "url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionBiologicalGender",
                    "valueCoding" => [
                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianGenderGroup",
                        "code" => $data->pac_extra_sexo,
                        "display" => ($data->pac_codigo_sexo == 'F') ? "Mujer" : "Hombre"
                    ]
                ]]
            ],
            "birthDate" => $birthDateClean,
            "_birthDate" => [
                "extension" => [[
                    "url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionBirthTime",
                    "valueTime" => "00:00:00"
                ]]
            ],
            "deceasedBoolean" => false
        ];
    }
}