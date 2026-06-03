<?php

namespace App\Services\Rdapatient;

class CompositionResourceService
{
    public function build(object $data, string $fechaIso, string $pacienteRef, string $medicoRef, string $codigoPrestador, array $conditionSection, array $allergySection, array $medicationSection, array $familySection): array
    {
        return [
            "resourceType" => "Composition",
            "meta" => [
                "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/CompositionPatientStatementRDA"]
            ],
            "status" => "final",
            "type" => ["coding" => [["system" => "http://loinc.org", "code" => "102089-0", "display" => "FHIR resource patient medical record"]]],
            "subject" => ["reference" => "#{$pacienteRef}"],
            "date" => $fechaIso,
            "author" => [["reference" => "#{$medicoRef}"]],
            "title" => "Resumen Digital de Atención en Salud - RDA de antecedentes manifestados por el paciente",
            "confidentiality" => "N",
            "attester" => [["mode" => "legal", "party" => ["reference" => "#{$codigoPrestador}"]]],
            "custodian" => ["reference" => "#{$codigoPrestador}"],
            "event" => [[
                "code" => [
                    ["coding" => [["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianTechModality", "code" => "01", "display" => "Intramural"]]],
                    ["coding" => [["system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/GrupoServicios", "code" => "01", "display" => "Consulta externa"]]]
                ],
                "period" => [
                    "start" => $data->cita_fecha ? date('Y-m-d\TH:i:s-05:00', strtotime($data->cita_fecha)) : $fechaIso,
                    "end" => $fechaIso
                ]
            ]],
            "section" => [
                
                $conditionSection,   // section[0]
                $allergySection,     // section[1]
                $medicationSection,  // section[2]
                $familySection       // section[3]
            ]
        ];
    }
}