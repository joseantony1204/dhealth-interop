<?php

namespace App\Services\Rdaconsulta;

class CompositionResourceService
{
    public function build(
            object $data, string $fechaIso, string $pacienteRef, string $medicoRef, string $codigoPrestador, string $encounterRef, 
            array $entidadesSection,
            array $demograficosSection, 
            array $incapacidadesSection,
            array $conditionSection, 
            array $allergySection, 
            array $factoresSection,
            array $medicationSection, 
            array $prescripcionesSection, 
            array $documentosSection
        ): array
    {
        return [
            "resourceType" => "Composition",
            "meta" => [
                "profile" => [
                    "https://fhir.minsalud.gov.co/rda/StructureDefinition/CompositionAmbulatoryRDA"
                    ]
            ],
            "status" => "final",
            "type" => ["coding" => [["system" => "http://loinc.org", "code" => "51845-6", "display" => "Outpatient Consult note"]]],
            "subject" => ["reference" => "#{$pacienteRef}"],
            "encounter" => ["reference" => "#{$encounterRef}"],
            "date" => $fechaIso,
            "author" => [["reference" => "#{$codigoPrestador}"]],
            "title" => "RDA Consulta",
            "confidentiality" => "N",
            "attester" => [["mode" => "legal", "party" => ["reference" => "#{$medicoRef}"]]],
            "custodian" => ["reference" => "#{$codigoPrestador}"],
            "event" => [
                "period" => [
                    "start" => $data->cita_fecha ? date('Y-m-d\TH:i:s-05:00', strtotime($data->cita_fecha)) : $fechaIso,
                    "end" => $fechaIso
                ]
            ],
            "section" => [
                $entidadesSection,       // section[0] Entidad(es) responsable(s) por el plan de beneficios en salud (consulta),
                $demograficosSection,    // section[1] Otros datos demográficos,
                $incapacidadesSection,   // section[2] Datos incapacidad (SIPE – Sistema de Incapacidades y Prestaciones Economicas),
               
                $conditionSection,       // section[3] Diagnósticos, problemas, motivos de consulta o razones clínicas (incluye diagnóstico principal, diagnóstico secundario, motivo de consulta, etc.),
                $allergySection,         // section[4] Alergias e intolerancias,
                $factoresSection,        // section[5] Factores de riesgo,
           
                $medicationSection,      // section[6] Medicamentos prescritos, indicados o administrados (incluye medicamentos de uso continuo, medicamentos de uso ocasional, medicamentos prescritos durante la consulta, etc.),
                $prescripcionesSection,  // section[7] Órdenes, prescripciones o solicitudes de servicio,
                $documentosSection,      // section[8] Documentos de soporte,
               
            ]
        ];
    }
}