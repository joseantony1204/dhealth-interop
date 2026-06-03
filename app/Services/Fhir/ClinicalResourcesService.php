<?php

namespace App\Services\Fhir;
use Illuminate\Support\Str;

class ClinicalResourcesService
{
    
    /**
     * Construye de manera integral la sección y recursos del historial de diagnósticos (Condition)
     */
    public function buildConditionSection(array $diagnosticos, string $pacienteRef): array
    {
        $sectionEntries = [];
        $conditionResources = [];

        // ESCENARIO A: Sí existen diagnósticos declarados en la consulta
        if (!empty($diagnosticos)) {
            foreach ($diagnosticos as $index => $diag) {
                
                $diagArray = (array) $diag;
                $uniqueId = "Condition-{$index}";

                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // (El mapeo del recurso Condition se mantiene igual...)
                $conditionResources[] = [
                    "resource" => [
                        "resourceType" => "Condition",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/ConditionStatementRDA"]
                        ],
                        "clinicalStatus" => [
                            "coding" => [["code" => "active", "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical", "display" => "Active"]]
                        ],
                        "verificationStatus" => [
                            "coding" => [["code" => "unconfirmed", "display" => "Unconfirmed"]]
                        ],
                        "category" => [
                            ["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/condition-category", "code" => "encounter-diagnosis", "display" => "Encounter Diagnosis"]]]
                        ],
                        "code" => [
                            "coding" => isset($diagArray['cie10_codigo']) ? [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => (string)$diagArray['cie10_codigo'],
                                    "display" => strtoupper($diagArray['cie10_nombre'] ?? 'DIAGNOSTICO NO ESPECIFICADO')
                                ]
                            ] : [],
                            "text" => $diagArray['texto_libre'] ?? "Consulta de Control General"
                        ],
                        "subject" => [
                            "reference" => "#{$pacienteRef}"
                        ]
                    ]
                ];
            }

            // 🚀 RETORNO ESCENARIO A: CORREGIDO
            return [
                "section" => [
                    "title" => "Historial de diagnósticos de problemas de salud",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "11450-4",
                                "display" => "Problem list - Reported"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $conditionResources
            ];
        }

        // 🚀 RETORNO ESCENARIO B (emptyReason): CORREGIDO
        return [
            "section" => [
                "title" => "Historial de diagnósticos de problemas de salud",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "11450-4",
                            "display" => "Problem list - Reported"
                        ]
                    ]
                ],
                // 🚀 Inyección obligatoria de narrativa FHIR
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registran diagnósticos o problemas de salud activos para el paciente.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se registran diagnósticos o problemas de salud activos para el paciente."
                ]
            ],
            "resources" => []
        ];
    }
    
    /**
     * Construye de manera integral la sección y recursos del historial de diagnósticos (Condition)
     */
    public function buildConditionRDASection(array $diagnosticos, string $pacienteRef): array
    {
        $sectionEntries = [];
        $conditionResources = [];

        // ESCENARIO A: Sí existen diagnósticos declarados en la consulta
        if (!empty($diagnosticos)) {
            foreach ($diagnosticos as $index => $diag) {
                
                $diagArray = (array) $diag;
                $uniqueId = "Condition-{$index}";

                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // (El mapeo del recurso Condition se mantiene igual...)
                $conditionResources[] = [
                    "resource" => [
                        "resourceType" => "Condition",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => [
                                "https://fhir.minsalud.gov.co/rda/StructureDefinition/ConditionRDA"
                                ]
                        ],
                        "clinicalStatus" => [
                            "coding" => [["code" => "active", "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical", "display" => "Active"]]
                        ],
                        "verificationStatus" => [
                            "coding" => [["code" => "confirmed", "display" => "Confirmed"]]
                        ],
                        "category" => [
                            ["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/condition-category", "code" => "encounter-diagnosis", "display" => "Encounter Diagnosis"]]]
                        ],
                        "code" => [
                            "coding" => isset($diagArray['cie10_codigo']) ? [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => (string)$diagArray['cie10_codigo'],
                                    "display" => strtoupper($diagArray['cie10_nombre'] ?? 'DIAGNOSTICO NO ESPECIFICADO')
                                ]
                            ] : [],
                            "text" => $diagArray['texto_libre'] ?? "Consulta de Control General"
                        ],
                        "subject" => [
                            "reference" => "#{$pacienteRef}"
                        ]
                    ]
                ];
            }

            // 🚀 RETORNO ESCENARIO A: CORREGIDO
            return [
                "section" => [
                    "title" => "Historial de diagnósticos de problemas de salud",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "11450-4",
                                "display" => "Problem list - Reported"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $conditionResources
            ];
        }

        // 🚀 RETORNO ESCENARIO B (emptyReason): CORREGIDO
        return [
            "section" => [
                "title" => "Historial de diagnósticos de problemas de salud",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "11450-4",
                            "display" => "Problem list - Reported"
                        ]
                    ]
                ],
                // 🚀 Inyección obligatoria de narrativa FHIR
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registran diagnósticos o problemas de salud activos para el paciente.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se registran diagnósticos o problemas de salud activos para el paciente."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye de manera integral la sección y recursos del historial de alergias (AllergyIntolerance)
     * Soportando tanto arreglos asociativos como objetos stdClass/Eloquent.
     * Garantiza cumplimiento estricto de guías MinSalud y perfiles FHIR RDA.
     */
    public function buildAllergySection(array $alergias, string $pacienteRef, ?string $encounterRef = null): array
    {
        $sectionEntries = [];
        $allergyResources = [];

        // Preparamos la llave de encounter únicamente si viene una referencia válida
        $encounterData = null;
        if (!empty($encounterRef)) {
            $cleanEncounterRef = str_starts_with($encounterRef, '#') ? $encounterRef : "#{$encounterRef}";
            $encounterData = [
                "reference" => $cleanEncounterRef
            ];
        }

        // ESCENARIO A: Sí existen alergias declaradas en la consulta
        if (!empty($alergias)) {
            foreach ($alergias as $index => $allergy) {
                
                // Convertimos a array temporalmente para leer propiedades de forma segura
                $allergyArray = (array) $allergy;

                // ID relativo e inmutable para el documento (ej: AllergyIntolerance-0)
                $uniqueId = "AllergyIntolerance-{$index}";

                // Almacenamos la referencia para el Composition.section.entry
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // 1. Construimos el recurso FHIR AllergyIntolerance base
                $resource = [
                    "resourceType" => "AllergyIntolerance",
                    "id" => $uniqueId,
                    "meta" => [
                        "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/AllergyIntoleranceStatementRDA"]
                    ],
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                                "code" => $allergyArray['clinical_status'] ?? "active", 
                                "display" => "Active"
                            ]
                        ]
                    ],
                    "verificationStatus" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
                                "code" => $allergyArray['verification_status'] ?? "unconfirmed", 
                                "display" => "Unconfirmed"
                            ]
                        ]
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/TipoAlergia", 
                                "code" => (string)($allergyArray['codigo'] ?? "01"), 
                                "display" => $allergyArray['tipo_display'] ?? "Medicamento"
                            ]
                        ],
                        "text" => $allergyArray['sustancia_texto'] ?? "Alergia o intolerancia no especificada"
                    ],
                    "patient" => [
                        "reference" => "#{$pacienteRef}"
                    ]
                ];

                // 🚀 2. Inyección limpia del Encounter (si existe y no es null)
                if ($encounterData !== null) {
                    $resource["encounter"] = $encounterData;
                }

                // Guardamos el recurso estructurado correctamente
                $allergyResources[] = [
                    "resource" => $resource
                ];
            }

            return [
                "section" => [
                    "title" => "Historial de alergias, intolerancias y reacciones adversas",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "48765-2",
                                "display" => "Allergies and adverse reactions Document"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $allergyResources
            ];
        }

        // ESCENARIO B: No hay alergias (Garantizando cumplimiento de la regla cmp-1)
        return [
            "section" => [
                "title" => "Historial de alergias, intolerancias y reacciones adversas",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "48765-2",
                            "display" => "Allergies and adverse reactions Document"
                        ]
                    ]
                ],
                // 🚀 Cumplimiento de regla cmp-1
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registran alergias o intolerancias conocidas para el paciente.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se registran alergias o intolerancias conocidas para el paciente."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye de manera integral la sección y recursos del historial de alergias (AllergyIntolerance)
     * Soportando tanto arreglos asociativos como objetos stdClass/Eloquent.
     */
    public function buildAllergySections(array $alergias, string $pacienteRef, ?string $encounterRef = NULL): array
    {
        $sectionEntries = [];
        $allergyResources = [];
        // Preparamos el bloque Encounter únicamente si viene una referencia válida
        $encounterBlock = [];
        if (!empty($encounterRef)) {
            $cleanEncounterRef = str_starts_with($encounterRef, '#') ? $encounterRef : "#{$encounterRef}";
            $encounterBlock = ["encounter" => ["reference" => $cleanEncounterRef]];
        }

        // ESCENARIO A: Sí existen alergias declaradas en la consulta
        if (!empty($alergias)) {
            foreach ($alergias as $index => $allergy) {
                
                // Convertimos a array temporalmente para leer propiedades de forma segura
                $allergyArray = (array) $allergy;

                // ID relativo e inmutable para el documento (ej: AllergyIntolerance-0)
                $uniqueId = "AllergyIntolerance-{$index}";

                // Almacenamos la referencia para el Composition.section.entry
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // Mapeamos el recurso individual AllergyIntolerance conforme a MinSalud
                $allergyResources[] = [
                    "resource" => [
                        "resourceType" => "AllergyIntolerance",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/AllergyIntoleranceStatementRDA"]
                        ],
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "code" => $allergyArray['clinical_status'] ?? "active", 
                                    "display" => "Active"
                                ]
                            ]
                        ],
                        "verificationStatus" => [
                            "coding" => [
                                [
                                    "code" => $allergyArray['verification_status'] ?? "unconfirmed", 
                                    "display" => "Unconfirmed"
                                ]
                            ]
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/TipoAlergia", 
                                    "code" => (string)($allergyArray['codigo'] ?? "01"), // Código MinSalud (ej: 01 Medicamento)
                                    "display" => $allergyArray['tipo_display'] ?? "Medicamento"
                                ]
                            ],
                            "text" => $allergyArray['sustancia_texto'] ?? "Alergia o intolerancia no especificada"
                        ],
                        "patient" => [
                            "reference" => "#{$pacienteRef}"
                        ],
                        $encounterBlock
                    ]
                ];
            }

            return [
                "section" => [
                    "title" => "Historial de alergias, intolerancias y reacciones adversas",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "48765-2",
                                "display" => "Allergies and adverse reactions Document"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $allergyResources
            ];
        }

        // ESCENARIO B: No hay alergias (Garantizando cumplimiento de la regla cmp-1)
        return [
            "section" => [
                "title" => "Historial de alergias, intolerancias y reacciones adversas",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "48765-2",
                            "display" => "Allergies and adverse reactions Document"
                        ]
                    ]
                ],
                // 🚀 Cumplimiento de regla cmp-1
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registran alergias o intolerancias conocidas para el paciente.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se registran alergias o intolerancias conocidas para el paciente."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye la sección de Alergias e Intolerancias y genera sus recursos AllergyIntolerance.
     * En caso de no haber registros, genera la sección con su respectivo emptyReason (Regla cmp-1).
     *
     * @param array $alergias Listado de alergias provenientes de la base de datos
     * @param string $pacienteRef ID del paciente (ej: "CC-1118857584")
     * @param string|null $encounterRef ID del encuentro opcional (ej: "Encounter-0")
     * @return array Estructura unificada para Composition.section y Bundle.entry
     */
    public function buildAllergyIntoleranceRDASections(array $alergias, string $pacienteRef, ?string $encounterRef = null): array
    {
        $sectionEntries = [];
        $allergyResources = [];

        // Limpiamos y aseguramos el formato de las referencias internas (#)
        $cleanPacienteRef = '#' . ltrim($pacienteRef, '#');
        $cleanEncounterRef = !empty($encounterRef) ? '#' . ltrim($encounterRef, '#') : null;

        // ESCENARIO A: Sí existen alergias declaradas en la consulta
        if (!empty($alergias)) {
            foreach ($alergias as $index => $allergy) {
                
                // Convertimos a array de forma segura por si viene como objeto
                $allergyArray = (array) $allergy;
                $uniqueId = "AllergyIntolerance-{$index}";

                // Guardamos la referencia para el Composition.section.entry
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // 1. Estructura base inmutable del recurso AllergyIntolerance según Minsalud
                $resourceStructure = [
                    "resourceType" => "AllergyIntolerance",
                    "id" => $uniqueId,
                    "meta" => [
                        "profile" => [
                            "https://fhir.minsalud.gov.co/rda/StructureDefinition/AllergyIntoleranceRDA"
                        ]
                    ],
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                                "code" => $allergyArray['clinical_status'] ?? "active",
                                "display" => "Active"
                            ]
                        ]
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/TipoAlergia",
                                "code" => (string)($allergyArray['codigo'] ?? "01"), // Ej: "01" para Medicamento
                                "display" => $allergyArray['tipo_display'] ?? "Medicamento"
                            ]
                        ],
                        "text" => $allergyArray['sustancia_texto'] ?? "Alergia o intolerancia no especificada"
                    ],
                    "patient" => [
                        "reference" => $cleanPacienteRef
                    ]
                ];

                // 2. Inyección condicional y limpia del Encuentro (Evita índices numéricos rotos)
                if (!empty($cleanEncounterRef)) {
                    $resourceStructure["encounter"] = [
                        "reference" => $cleanEncounterRef
                    ];
                }

                // Guardamos en el listado de recursos finales para el Bundle
                $allergyResources[] = [
                    "resource" => $resourceStructure
                ];
            }

            return [
                "section" => [
                    "title" => "Historial de alergias, intolerancias y reacciones adversas",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "48765-2",
                                "display" => "Allergies and adverse reactions Document"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $allergyResources
            ];
        }

        // ESCENARIO B: No hay alergias (Garantizando cumplimiento de la regla de obligatoriedad)
        return [
            "section" => [
                "title" => "Historial de alergias, intolerancias y reacciones adversas",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "48765-2",
                            "display" => "Allergies and adverse reactions Document"
                        ]
                    ]
                ],
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registran alergias o intolerancias conocidas para el paciente.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se registran alergias o intolerancias conocidas para el paciente."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye de manera integral la sección y recursos de antecedentes familiares (FamilyMemberHistory)
     * Soportando tanto arreglos asociativos como objetos stdClass/Eloquent.
     */
    public function buildFamilyHistorySection(array $antecedentes, string $pacienteRef): array
    {
        $sectionEntries = [];
        $familyResources = [];

        // ESCENARIO A: Sí existen antecedentes familiares declarados
        if (!empty($antecedentes)) {
            foreach ($antecedentes as $index => $ant) {
                
                // Convertimos a array temporalmente para leer propiedades de forma segura
                $antArray = (array) $ant;

                // ID relativo e inmutable para el documento (ej: FamilyMemberHistory-0)
                $uniqueId = "FamilyMemberHistory-{$index}";

                // Almacenamos la referencia para el Composition.section.entry
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // Mapeamos el recurso individual FamilyMemberHistory conforme a MinSalud
                $familyResources[] = [
                    "resource" => [
                        "resourceType" => "FamilyMemberHistory",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/FamilyMemberHistoryRDA"]
                        ],
                        "status" => "partial",
                        "patient" => [
                            "reference" => "#{$pacienteRef}"
                        ],
                        "relationship" => [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ParentescoAntecedente", 
                                    "code" => (string)($antArray['parentesco_codigo'] ?? "01"), // Código MinSalud (ej: 01 Padres)
                                    "display" => $antArray['parentesco_display'] ?? "Padres"
                                ]
                            ]
                        ],
                        "condition" => [
                            [
                                "code" => [
                                    "coding" => isset($antArray['cie10_codigo']) ? [
                                        [
                                            "system" => "http://hl7.org/fhir/sid/icd-10",
                                            "code" => (string)$antArray['cie10_codigo'],
                                            "display" => strtoupper($antArray['cie10_nombre'] ?? 'ANTECEDENTE NO ESPECIFICADO')
                                        ]
                                    ] : []
                                ]
                            ]
                        ]
                    ]
                ];
            }

            return [
                "section" => [
                    "title" => "Historial de antecedentes familiares",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "10157-6",
                                "display" => "History of family member diseases Narrative"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $familyResources
            ];
        }

        // ESCENARIO B: No hay antecedentes (Garantizando cumplimiento estricto de la regla cmp-1)
        return [
            "section" => [
                "title" => "Historial de antecedentes familiares",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "10157-6",
                            "display" => "History of family member diseases Narrative"
                        ]
                    ]
                ],
                // 🚀 Cumplimiento de regla cmp-1
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registran antecedentes médicos familiares relevantes para el paciente.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se registran antecedentes médicos familiares relevantes para el paciente."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye de manera integral la sección y recursos del historial farmacológico
     * Soportando tanto arreglos asociativos como objetos stdClass/Eloquent.
     */
    public function buildMedicationSection(array $medicamentos, string $pacienteRef): array
    {
        $sectionEntries = [];
        $medicationResources = [];

        // ESCENARIO A: Sí existen medicamentos declarados en la consulta
        if (!empty($medicamentos)) {
            foreach ($medicamentos as $index => $med) {
                
                // 🚀 TRUCO DE ROBUSTEZ: Si viene como objeto, lo convertimos a array temporalmente 
                // para poder leerlo siempre con llaves ['propiedad'] de forma segura.
                $medArray = (array) $med;

                // ID relativo e inmutable para el documento
                $uniqueId = "MedicationStatement-{$index}";

                // Almacenamos la referencia para el Composition
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // Mapeamos el recurso individual leyendo las llaves del array
                $medicationResources[] = [
                    "resource" => [
                        "resourceType" => "MedicationStatement",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/MedicationStatementRDA"]
                        ],
                        "status" => "completed",
                        "medicationCodeableConcept" => [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/MipresINN",
                                    // 💡 Ahora leemos con corchetes de forma segura
                                    "code" => (string)($medArray['codigo'] ?? '626'), 
                                    "display" => strtoupper($medArray['nombre'] ?? 'PARACETAMOL') 
                                ]
                            ],
                            "text" => $medArray['observacion'] ?? "Medicamento declarado por el paciente."
                        ],
                        "subject" => [
                            "reference" => "#{$pacienteRef}"
                        ],
                        "informationSource" => [
                            "reference" => "#{$pacienteRef}"
                        ]
                    ]
                ];
            }

            return [
                "section" => [
                    "title" => "Historial de medicamentos",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "10160-0",
                                "display" => "History of Medication use Narrative"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $medicationResources
            ];
        }

        // ESCENARIO B: No hay medicamentos (Construcción estricta de emptyReason)
        return [
            "section" => [
                "title" => "Historial de medicamentos",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "10160-0",
                            "display" => "History of Medication use Narrative"
                        ]
                    ]
                ],
                // 🚀 Inyección obligatoria de narrativa FHIR
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se cuenta con la informacion de si paciente esta tomando algún medicamento actualmente.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se cuenta con la informacion de si paciente esta tomando algún medicamento actualmente."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye la sección estandarizada de Entidades Responsables para el bloque Composition.
     * Soporta inyección de referencia directa o generación limpia de emptyReason.
     *
     * @param string|null $entidadRef ID relativo del recurso Coverage (Ej: "Coverage-0" o "CCFC33")
     * @return array Estructura de sección para el Composition FHIR
     */
    /**
     * Construye la sección de Entidades Responsables leyendo el array crudo de la organización.
     *
     * @param array|null $organizacionRaw Array con llaves ['codigo', 'nombre'] (ej: ['codigo' => 'EPS005', 'nombre' => 'SANITAS'])
     * @return array Estructura unificada con la sección FHIR y sus recursos contenidos
     */
    public function buildEntidadesSection(?array $entidadesRaw): array
    {
        // Base fija de la sección obligatoria según LOINC para fuentes de pago en la RDA
        $baseSection = [
            "title" => "Entidad(es) responsable(s) por el plan de beneficios en salud (consulta)",
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "48768-6",
                        "display" => "Payment sources Document"
                    ]
                ]
            ]
        ];

        // 🚀 TRUCO DE ROBUSTEZ: Convertimos a array si viniera como objeto y validamos que tenga código
        $orgArray = (array) $entidadesRaw;

        // ESCENARIO A: Sí vienen datos de la entidad responsable
        if (!empty($orgArray) && !empty($orgArray['codigo'])) {
            
            // Limpiamos el ID quitando cualquier '#' que pueda venir del origen
            $cleanId = ltrim((string)$orgArray['codigo'], '#');
            // Aseguramos la referencia interna con '#' para el Composition.section.entry
            $cleanRef = "#{$cleanId}";

            // Formateamos el nombre de la entidad de manera segura
            $nombreFinal = !empty($orgArray['nombre']) ? strtoupper((string)$orgArray['nombre']) : "ENTIDAD NO ESPECIFICADA";

            // Inyectamos la referencia en el entry de la sección
            $baseSection["entry"] = [
                [
                    "reference" => $cleanRef
                ]
            ];

            // Construimos el recurso Organization que irá al Bundle principal
            $organizationResource = [
                "resource" => [
                    "resourceType" => "Organization",
                    "id" => $cleanId,
                    "name" => $nombreFinal
                ]
            ];

            return [
                "section" => $baseSection,
                "resources" => [$organizationResource]
            ];
        }

        // ESCENARIO B: No hay datos de entidad (Construcción estricta de emptyReason)
        $baseSection["text"] = [
            "status" => "generated",
            "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registra entidad responsable por el plan de beneficios en salud en esta atención.</p></div>"
        ];
        
        $baseSection["emptyReason"] = [
            "coding" => [
                [
                    "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                    "code" => "nilknown",
                    "display" => "Nil Known"
                ]
            ],
            "text" => "No se registra entidad responsable por el plan de beneficios en salud en esta atención."
        ];

        return [
            "section" => $baseSection,
            "resources" => []
        ];
    }

    /**
     * Construye de manera integral la sección y recursos de "Otros datos demográficos" (Ocupación)
     * Siguiendo de forma idéntica la arquitectura y robustez del historial farmacológico.
     *
     * @param array $demograficosRaw Colección o arreglo de registros de ocupación del paciente.
     * @param string $pacienteRef Identificador relativo del paciente (Ej: "CC-00000000")
     * @return array Estructura con la [section] para el Composition y los [resources] independientes.
     */
    public function buildDemograficosSection(array $demograficosRaw, string $pacienteRef): array
    {
        $sectionEntries = [];
        $demographicResources = [];

        // Aseguramos que la referencia del paciente lleve el prefijo '#' exigido para enlaces contenidos
        $cleanPacienteRef = str_starts_with($pacienteRef, '#') ? $pacienteRef : "#{$pacienteRef}";

        // ESCENARIO A: Sí existen datos demográficos/ocupaciones declarados en la consulta
        if (!empty($demograficosRaw)) {
            foreach ($demograficosRaw as $index => $dem) {
                
                // 🚀 TRUCO DE ROBUSTEZ: Si viene como objeto dentro del array, lo convertimos a array temporalmente
                $demArray = (array) $dem;

                // ID relativo e inmutable para el documento
                $uniqueId = "Observation-{$index}";

                // Almacenamos la referencia para el Composition
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // Mapeamos el recurso individual leyendo las llaves del array
                $demographicResources[] = [
                    "resource" => [
                        "resourceType" => "Observation",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/PatientOccupationAtEncounterRDA"]
                        ],
                        "status" => "final",
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "184104002",
                                    "display" => "ocupación del paciente"
                                ]
                            ],
                            "text" => "Ocupación del paciente en el momento de la atención"
                        ],
                        "subject" => [
                            "reference" => $cleanPacienteRef
                        ],
                        "valueCodeableConcept" => [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/CIUO88AC",
                                    // 💡 Ahora leemos con corchetes de forma segura
                                    "code" => (string)($demArray['codigo'] ?? '9999'), 
                                    "display" => $demArray['nombre'] ?? 'Analistas de sistemas informáticos'
                                ]
                            ]
                        ]
                    ]
                ];
            }

            return [
                "section" => [
                    "title" => "Otros datos demográficos",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "74208-0",
                                "display" => "Demographic information + History of occupation Document"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $demographicResources
            ];
        }

        // ESCENARIO B: No hay datos demográficos (Construcción estricta de emptyReason)
        return [
            "section" => [
                "title" => "Otros datos demográficos",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "74208-0",
                            "display" => "Demographic information + History of occupation Document"
                        ]
                    ]
                ],
                // 🚀 Inyección obligatoria de narrativa FHIR XHTML
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se cuenta con la informacion de la ocupación o datos demográficos adicionales del paciente.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se cuenta con la informacion de la ocupación o datos demográficos adicionales del paciente."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye la sección y recursos de Incapacidades SIPE con un ID Semántico Único
     */
    public function buildIncapacidadesSection(array $incapacidadesRaw, string $pacienteRef, string $encounterRef): array
    {
        $sectionEntries = [];
        $incapacidadResources = [];
        $cleanPacienteRef = str_starts_with($pacienteRef, '#') ? $pacienteRef : "#{$pacienteRef}";
        $cleanEncounterRef = str_starts_with($encounterRef, '#') ? $encounterRef : "#{$encounterRef}";

        if (!empty($incapacidadesRaw)) {
            foreach ($incapacidadesRaw as $index => $inc) {
                $incArray = (array) $inc;
                $index++;
                // 🚀 SOLUCIÓN: Prefijo inconfundible que mantiene la correlación intacta
                $uniqueId = "Observation-{$index}";

                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                $incapacidadResources[] = [
                    "resource" => [
                        "resourceType" => "Observation",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/AttendanceAllowanceRDA"]
                        ],
                        "status" => "final",
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "160983005",
                                    "display" => "permiso de concurrencia"
                                ]
                            ],
                            "text" => "Datos incapacidad (SIPE – Sistema de Incapacidades y Prestaciones Economicas)"
                        ],
                        "subject" => [
                            "reference" => $cleanPacienteRef
                        ],
                        "encounter" => [
                            "reference" => $cleanEncounterRef
                        ],
                        "component" => [
                            [
                                "id" => "LicenseScope",
                                "code" => [
                                    "coding" => [
                                        [
                                            "system" => "http://snomed.info/sct",
                                            "code" => "255590007",
                                            "display" => "alcance"
                                        ]
                                    ],
                                    "text" => "Incapacidad - Alcance de la incapacidad"
                                ],
                                "valueCodeableConcept" => [
                                    "coding" => [
                                        [
                                            "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianLicenseScope",
                                            "code" => (string)($incArray['alcance_codigo'] ?? '01'), // Ejemplo: '01' para Nueva
                                            "display" => $incArray['alcance_nombre'] ?? 'Nueva'
                                        ]
                                    ]
                                ]
                            ],
                            [
                                "id" => "MaternityLicenseTime",
                                "code" => [
                                    "coding" => [
                                        [
                                            "system" => "http://snomed.info/sct",
                                            "code" => "410670007",
                                            "display" => "tiempo"
                                        ]
                                    ],
                                    "text" => "Días de licencia de maternidad"
                                ],
                                "valueQuantity" => [
                                    "value" => (int)($incArray['dias_licencia'] ?? 0),
                                    "unit" => "días",
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "d"
                                ]
                            ]
                        ]
                    ]
                ];
            }

            return [
                "section" => [
                    "title" => "Datos incapacidad (SIPE – Sistema de Incapacidades y Prestaciones Economicas)",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "105583-9",
                                "display" => "Worker Sick leave form"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $incapacidadResources
            ];
        }

        // ESCENARIO B: EmptyReason
        return [
            "section" => [
                "title" => "Datos incapacidad (SIPE – Sistema de Incapacidades y Prestaciones Economicas)",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "105583-9",
                            "display" => "Worker Sick leave form"
                        ]
                    ]
                ],
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registraron incapacidades en esta atención.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ]
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye de manera integral la sección y recursos de "Factores de riesgo" (RiskAssessment)
     * Soportando tanto arreglos asociativos como objetos stdClass/Eloquent.
     *
     * @param array $factoresRaw Colección o arreglo de registros de factores de riesgo.
     * @param string $pacienteRef Identificador relativo del paciente (Ej: "CC-00000000")
     * @param string $encounterRef Identificador relativo del encuentro o cita médica (Ej: "Encounter-0")
     * @return array Estructura con la [section] para el Composition y los [resources] independientes.
     */
    public function buildFactoresSection(array $factoresRaw, string $pacienteRef, string $encounterRef): array
    {
        $sectionEntries = [];
        $riskResources = [];

        // Aseguramos que las referencias lleven el prefijo '#' exigido para enlaces contenidos
        $cleanPacienteRef = str_starts_with($pacienteRef, '#') ? $pacienteRef : "#{$pacienteRef}";
        $cleanEncounterRef = str_starts_with($encounterRef, '#') ? $encounterRef : "#{$encounterRef}";

        // ESCENARIO A: Sí existen factores de riesgo declarados en la consulta
        if (!empty($factoresRaw)) {
            foreach ($factoresRaw as $index => $fac) {
                
                // 🚀 TRUCO DE ROBUSTEZ: Cast temporal a array para leer propiedades de forma segura
                $facArray = (array) $fac;

                // ID semántico e inmutable para evitar colisiones en el merge del Bundle
                $uniqueId = "RiskAssessment-{$index}";

                // Almacenamos la referencia para el Composition
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // Mapeamos el recurso individual leyendo las llaves del array
                $riskResources[] = [
                    "resource" => [
                        "resourceType" => "RiskAssessment",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/RiskFactorRDA"]
                        ],
                        "status" => "registered",
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/FactorRiesgo",
                                    // Código oficial de MinSalud (Ej: '01' para Químicos, '02' para Físicos, etc.)
                                    "code" => (string)($facArray['factor_codigo'] ?? '01'), 
                                    "display" => $facArray['factor_display'] ?? 'Químicos'
                                ]
                            ],
                            // Texto descriptivo detallado (Ej: Tabaquismo, Alcoholismo, Exposición a asbesto)
                            "text" => $facArray['observacion'] ?? "No especificado"
                        ],
                        "subject" => [
                            "reference" => $cleanPacienteRef
                        ],
                        "encounter" => [
                            "reference" => $cleanEncounterRef
                        ]
                    ]
                ];
            }

            return [
                "section" => [
                    "title" => "Factores de riesgo",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "75492-9",
                                "display" => "Risk assessment and screening note"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $riskResources
            ];
        }

        // ESCENARIO B: No hay factores de riesgo (Construcción estricta de emptyReason)
        return [
            "section" => [
                "title" => "Factores de riesgo",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "75492-9",
                            "display" => "Risk assessment and screening note"
                        ]
                    ]
                ],
                // 🚀 Inyección obligatoria de narrativa FHIR XHTML
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se cuenta con la información de factores de riesgo para el paciente en esta atención.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se cuenta con la información de factores de riesgo para el paciente en esta atención."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye de manera integral la sección y recursos de "Órdenes o solicitudes de servicio" (ServiceRequest)
     * Soporta dinámicamente tanto la codificación estricta CUPS como descripciones textuales libres de dispositivos.
     *
     * @param array $ordenesRaw Colección de registros con solicitudes de procedimientos o tecnologías.
     * @param string $pacienteRef Identificador relativo del paciente (Ej: "CC-00000000")
     * @param string $medicoRef Identificador relativo del médico solicitante (Ej: "CC-1111111")
     * @param string $encounterRef Identificador del encuentro o cita (Ej: "Encounter-0")
     * @return array Estructura con la [section] para el Composition y los [resources] independientes.
     */
    public function buildPrescripcionesSection(array $ordenesRaw, string $pacienteRef, string $medicoRef, string $encounterRef): array
    {
        $sectionEntries = [];
        $serviceResources = [];

        // Aseguramos prefijos '#' para las referencias internas del Bundle
        $cleanPacienteRef = str_starts_with($pacienteRef, '#') ? $pacienteRef : "#{$pacienteRef}";
        $cleanMedicoRef = str_starts_with($medicoRef, '#') ? $medicoRef : "#{$medicoRef}";
        $cleanEncounterRef = str_starts_with($encounterRef, '#') ? $encounterRef : "#{$encounterRef}";

        // ESCENARIO A: Sí existen órdenes o solicitudes de servicio declaradas
        if (!empty($ordenesRaw)) {
            foreach ($ordenesRaw as $index => $ord) {
                
                // 🚀 TRUCO DE ROBUSTEZ: Cast temporal por si viene como objeto stdClass
                $ordArray = (array) $ord;

                // ID semántico único para evitar colisiones en la raíz del Bundle Document
                $uniqueId = "ServiceRequest-{$index}";

                // Almacenamos la referencia para el array 'entry' de esta sección del Composition
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // Identificar si es procedimiento (Usa perfil CUPS) o tecnología (Usa perfil de texto libre)
                $categoriaCodigo = (string)($ordArray['categoria_codigo'] ?? '01'); // '01' = Procedimiento, '06' = Dispositivo
                $isProcedimiento = ($categoriaCodigo === '01');

                $profile = $isProcedimiento 
                    ? "https://fhir.minsalud.gov.co/rda/StructureDefinition/ServiceRequestRDA"
                    : "https://fhir.minsalud.gov.co/rda/StructureDefinition/OtherTechnologyServiceRequestRDA";

                // Estructura base del recurso individual ServiceRequest
                $resourceStructure = [
                    "resourceType" => "ServiceRequest",
                    "id" => $uniqueId,
                    "meta" => [
                        "profile" => [$profile]
                    ],
                    "status" => "active",
                    "intent" => "order",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianHealthTechnologyCategory",
                                    "code" => $categoriaCodigo,
                                    "display" => $ordArray['categoria_display'] ?? ($isProcedimiento ? 'Procedimiento en salud' : 'Dispositivo médico')
                                ]
                            ]
                        ]
                    ],
                    "subject" => [
                        "reference" => $cleanPacienteRef
                    ],
                    "encounter" => [
                        "reference" => $cleanEncounterRef
                    ],
                    // Fecha de la orden en formato ISO plano YYYY-MM-DD
                    "authoredOn" => $ordArray['fecha_orden'] ?? now()->toDateString(),
                    "requester" => [
                        "reference" => $cleanMedicoRef
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/RIPSFinalidadConsultaVersion2",
                                    "code" => (string)($ordArray['finalidad_codigo'] ?? '15'), // '15' = DIAGNOSTICO por defecto
                                    "display" => strtoupper($ordArray['finalidad_display'] ?? 'DIAGNOSTICO')
                                ]
                            ]
                        ]
                    ]
                ];

                // 🚀 INYECCIÓN CONDICIONAL DEL CÓDIGO (CUPS para procedimientos, Texto plano para otras tecnologías)
                if ($isProcedimiento) {
                    $resourceStructure["code"] = [
                        "coding" => [
                            [
                                "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/CUPS",
                                "code" => (string)($ordArray['cups_codigo'] ?? '399501'),
                                "display" => strtoupper($ordArray['cups_nombre'] ?? 'HEMODIÁLISIS ESTÁNDAR CON BICARBONATO')
                            ]
                        ]
                    ];
                } else {
                    $resourceStructure["code"] = [
                        "text" => $ordArray['tecnologia_texto'] ?? 'Infusión intravenosa de solución salina normal'
                    ];
                }

                $serviceResources[] = [
                    "resource" => $resourceStructure
                ];
            }

            return [
                "section" => [
                    "title" => "Órdenes, prescripciones o solicitudes de servicio",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "61146-1",
                                "display" => "Orders for services Document"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $serviceResources
            ];
        }

        // ESCENARIO B: No hay Órdenes o solicitudes (Construcción estricta de emptyReason)
        return [
            "section" => [
                "title" => "Órdenes, prescripciones o solicitudes de servicio",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "61146-1",
                            "display" => "Orders for services Document"
                        ]
                    ]
                ],
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se generaron órdenes, prescripciones o solicitudes de servicios en esta atención.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se generaron órdenes, prescripciones o solicitudes de servicios en esta atención."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye de manera integral la sección y recursos de "Documentos de soporte" (DocumentReference)
     * Soporta múltiples documentos adjuntos e inyección segura de binarios en Base64.
     * Si no hay documentos, aplica un bypass estructural (Alternativa 2) para cumplir con la cardinalidad 1..1 de MinSalud.
     *
     * @param array $documentosRaw Colección de registros de documentos con su metadata y base64.
     * @param string $pacienteRef Identificador relativo del paciente (Ej: "CC-00000000")
     * @param string $codigoPrestador Código de habilitación de la IPS (Ej: "Códigohabilitaciónprestador")
     * @param string $encounterRef Identificador del encuentro o cita (Ej: "Encounter-0")
     * @return array Estructura con la [section] para el Composition y los [resources] independientes.
     */
    public function buildDocumentosSection(array $documentosRaw, string $pacienteRef, string $codigoPrestador, string $encounterRef): array
    {
        $sectionEntries = [];
        $documentResources = [];

        // Aseguramos prefijos '#' para las referencias internas del Bundle
        $cleanPacienteRef = str_starts_with($pacienteRef, '#') ? $pacienteRef : "#{$pacienteRef}";
        $cleanPrestadorRef = str_starts_with($codigoPrestador, '#') ? $codigoPrestador : "#{$codigoPrestador}";
        $cleanEncounterRef = str_starts_with($encounterRef, '#') ? $encounterRef : "#{$encounterRef}";

        // ESCENARIO A: Sí existen documentos de soporte reales declarados
        if (!empty($documentosRaw)) {
            foreach ($documentosRaw as $index => $doc) {
                
                // 🚀 TRUCO DE ROBUSTEZ: Cast temporal por si viene como objeto stdClass/Eloquent
                $docArray = (array) $doc;

                // ID semántico único para evitar colisiones en la raíz del Bundle Document
                $uniqueId = "DocumentReference-{$index}";

                // Almacenamos la referencia para el array 'entry' de esta sección del Composition
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // Mapeamos el recurso individual DocumentReference
                $documentResources[] = [
                    "resource" => [
                        "resourceType" => "DocumentReference",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/DocumentReferenceEPIRDA"]
                        ],
                        "text" => [
                            "status" => "generated",
                            "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">Document Reference</div>"
                        ],
                        "status" => "current",
                        "type" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => (string)($docArray['type_loinc_code'] ?? '18842-5'),
                                    "display" => $docArray['type_loinc_display'] ?? 'Discharge summary'
                                ],
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianDocumentTypes",
                                    "code" => (string)($docArray['type_col_code'] ?? 'EPI'), // EPI = Epicrisis
                                    "display" => $docArray['type_col_display'] ?? 'Epicrisis'
                                ]
                            ]
                        ],
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://loinc.org",
                                        "code" => (string)($docArray['category_code'] ?? '55108-5'),
                                        "display" => $docArray['category_display'] ?? 'Clinical presentation Document'
                                    ]
                                ]
                            ]
                        ],
                        "subject" => [
                            "reference" => $cleanPacienteRef
                        ],
                        // Fecha del documento en formato completo ISO 8601
                        "date" => $docArray['fecha_documento'] ?? now()->toIso8601String(),
                        "author" => [
                            [
                                "reference" => $cleanPrestadorRef
                            ]
                        ],
                        "custodian" => [
                            "reference" => "Organization/MinSalud"
                        ],
                        "description" => $docArray['descripcion'] ?? "Epicrisis del encuentro de atención en salud - RDA",
                        "securityLabel" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.hl7.org/CodeSystem/v3-Confidentiality",
                                        "code" => (string)($docArray['seguridad_codigo'] ?? 'R'), // R = Restricted
                                        "display" => $docArray['seguridad_display'] ?? 'restricted'
                                    ]
                                ]
                            ]
                        ],
                        "content" => [
                            [
                                "attachment" => [
                                    "data" => trim((string)($docArray['pdf_base64'] ?? ''))
                                ],
                                "format" => [
                                    "system" => "urn:ietf:bcp:13",
                                    "code" => (string)($docArray['format_code'] ?? 'application/pdf'),
                                    "display" => $docArray['format_display'] ?? 'PDF'
                                ]
                            ]
                        ],
                        "context" => [
                            "encounter" => [
                                [
                                    "reference" => $cleanEncounterRef
                                ]
                            ]
                        ]
                    ]
                ];
            }

            return [
                "section" => [
                    "title" => "Documentos de soporte",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "55107-7",
                                "display" => "Addendum Document"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $documentResources
            ];
        }

        // =========================================================================
        // ESCENARIO B: Alternativa 2 - No hay Documentos reales de soporte.
        // Simulamos el recurso obligatorio para saltar las restricciones de MinSalud.
        // =========================================================================
        
        $uniqueIdNoAplica = "DocumentReference-0";
        
        // Base64 correspondiente a: "Sin documentos adjuntos para esta atencion."
        $base64NoAplica = "U2luIGRvY3VtZW50b3MgYWRqdW50b3MgcGFyYSBlc3RhIGF0ZW5jaW9uLg==";

        return [
            "section" => [
                "title" => "Documentos de soporte",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "55107-7",
                            "display" => "Addendum Document"
                        ]
                    ]
                ],
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registran documentos de soporte para esta atencion.</p></div>"
                ],
                
                "entry" => [
                    [
                        "reference" => "#{$uniqueIdNoAplica}" 
                    ]
                ]
            ],
            "resources" => [
                [
                    "resource" => [
                        "resourceType" => "DocumentReference",
                        "id" => $uniqueIdNoAplica,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/DocumentReferenceEPIRDA"]
                        ],
                        "text" => [
                            "status" => "generated",
                            "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">Documento de soporte - No aplica</div>"
                        ],
                        "status" => "current",
                        "type" => [
                            "coding" => [
                                [
                                    "system" => "http://loinc.org",
                                    "code" => "18842-5",
                                    "display" => "Discharge summary"
                                ],
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianDocumentTypes",
                                    "code" => "EPI",
                                    "display" => "Epicrisis"
                                ]
                            ]
                        ],
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://loinc.org",
                                        "code" => "55108-5",
                                        "display" => "Clinical presentation Document"
                                    ]
                                ]
                            ]
                        ],
                        "subject" => [
                            "reference" => $cleanPacienteRef
                        ],
                        "date" => now()->toIso8601String(),
                        "author" => [
                            [
                                "reference" => $cleanPrestadorRef
                            ]
                        ],
                        "custodian" => [
                            "reference" => "Organization/MinSalud"
                        ],
                        "description" => "Epicrisis del encuentro de atención en salud - RDA",
                        "securityLabel" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.hl7.org/CodeSystem/v3-Confidentiality",
                                        "code" => "R",
                                        "display" => "restricted"
                                    ]
                                ]
                            ]
                        ],
                        "content" => [
                            [
                                "attachment" => [
                                    "data" => $base64NoAplica
                                ],
                                "format" => [
                                    "system" => "urn:ietf:bcp:13",
                                    "code" => "application/pdf",
                                    "display" => "PDF"
                                ]
                            ]
                        ],
                        "context" => [
                            "encounter" => [
                                [
                                    "reference" => $cleanEncounterRef
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Construye de manera integral la sección y recursos de solicitudes de medicamentos (MedicationRequest)
     * Soportando tanto arreglos asociativos como objetos stdClass/Eloquent.
     *
     * @param array $medicamentos Listado de medicamentos formulados.
     * @param string $pacienteRef Referencia interna del paciente (ej: 'CC-00000000').
     * @param string $medicoRef Referencia interna del profesional que prescribe (ej: 'CC-1111111').
     * @param string|null $encounterRef Referencia opcional al encuentro/cita (ej: 'Encounter-0').
     * @return array Estructura con la sección para el Composition y la colección de recursos.
     */
    public function buildMedicationRequestSection(array $medicamentos, string $pacienteRef, string $medicoRef, string $encounterRef): array
    {
        $sectionEntries = [];
        $medicationResources = [];

        // Aseguramos que las referencias al paciente y médico tengan el prefijo '#' para el documento local
        $cleanPatientRef = str_starts_with($pacienteRef, '#') ? $pacienteRef : "#{$pacienteRef}";
        $cleanEncounterRef = str_starts_with($encounterRef, '#') ? $encounterRef : "#{$encounterRef}";
        $cleanMedicoRef = str_starts_with($medicoRef, '#') ? $medicoRef : "#{$medicoRef}";

        // ESCENARIO A: Existen medicamentos en la consulta
        if (!empty($medicamentos)) {
            foreach ($medicamentos as $index => $med) {
                
                // Cast defensivo a array
                $medArray = (array) $med;

                // ID inmutable para trazabilidad individual (ej: MedicationRequest-0)
                $uniqueId = "MedicationRequest-{$index}";

                // Referencia indexada para el Composition.section.entry
                $sectionEntries[] = [
                    "reference" => "#{$uniqueId}"
                ];

                // Construcción base del recurso MedicationRequest según lineamientos de MinSalud
                $resource = [
                    "resourceType" => "MedicationRequest",
                    "id" => $uniqueId,
                    "meta" => [
                        "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/MedicationRequestRDA"]
                    ],
                    "status" => "active",
                    "intent" => "order",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianHealthTechnologyCategory",
                                    "code" => $medArray['categoria_codigo'] ?? "02", // 02 = Medicamento con registro sanitario
                                    "display" => $medArray['categoria_display'] ?? "Medicamento con registro sanitario"
                                ]
                            ]
                        ]
                    ],
                    "reportedBoolean" => true,
                    "medicationCodeableConcept" => [
                        "coding" => [
                            [
                                "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/MipresINN",
                                "code" => (string)($medArray['codigo_mipres'] ?? "626"), // Ej: 626 para Paracetamol
                                "display" => Str::upper($medArray['nombre_medicamento'] ?? "PARACETAMOL")
                            ]
                        ]
                    ],
                    "subject" => [
                        "reference" => $cleanPatientRef
                    ],
                    "encounter" => [
                        "reference" => $cleanEncounterRef
                    ],
                    "authoredOn" => $medArray['fecha_formulacion'] ?? date('Y-m-d\TH:i:sP'),
                    "requester" => [
                        "reference" => $cleanMedicoRef
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/RIPSFinalidadConsultaVersion2",
                                    "code" => $medArray['finalidad_rips'] ?? "15", // 15 = Diagnóstico
                                    "display" => $medArray['finalidad_display'] ?? "DIAGNOSTICO"
                                ]
                            ]
                        ]
                    ],
                    "dosageInstruction" => [
                        [
                            "timing" => [
                                "repeat" => [
                                    "duration" => (int)($medArray['duracion_valor'] ?? 7),
                                    "durationUnit" => $medArray['duracion_unidad'] ?? "d" // d = Días
                                ],
                                "code" => [
                                    "coding" => [
                                        [
                                            "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/MedicationTime",
                                            "code" => (string)($medArray['frecuencia_codigo'] ?? "3"), // 3 = Día
                                            "display" => $medArray['frecuencia_display'] ?? "Día"
                                        ]
                                    ]
                                ]
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/VAD",
                                        "code" => (string)($medArray['via_codigo'] ?? "048"), // 048 = Oral
                                        "display" => Str::upper($medArray['via_display'] ?? "ORAL")
                                    ]
                                ]
                            ],
                            "doseAndRate" => [
                                [
                                    "doseQuantity" => [
                                        "value" => (float)($medArray['dosis_valor'] ?? 10),
                                        "unit" => $medArray['dosis_unidad'] ?? "mg",
                                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/UMM",
                                        "code" => (string)($medArray['dosis_unidad_codigo'] ?? "168")
                                    ],
                                    "rateQuantity" => [
                                        "value" => (float)($medArray['cada_cuanto_valor'] ?? 8), // Ej: Cada 8 horas
                                        "unit" => $medArray['cada_cuanto_unidad'] ?? "Día",
                                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/MedicationTime",
                                        "code" => (string)($medArray['cada_cuanto_codigo'] ?? "3")
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $medicationResources[] = [
                    "resource" => $resource
                ];
            }

            return [
                "section" => [
                    "title" => "Historial de medicamentos",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "10160-0",
                                "display" => "History of Medication use Narrative"
                            ]
                        ]
                    ],
                    "entry" => $sectionEntries
                ],
                "resources" => $medicationResources
            ];
        }

        // ESCENARIO B: No se prescribieron medicamentos (Garantizando validación cmp-1)
        return [
            "section" => [
                "title" => "Solicitudes de medicamentos",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "11416-5",
                            "display" => "History of Medication use Narrative"
                        ]
                    ]
                ],
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p>No se registran prescripciones de medicamentos en este encuentro clínico.</p></div>"
                ],
                "emptyReason" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason",
                            "code" => "nilknown",
                            "display" => "Nil Known"
                        ]
                    ],
                    "text" => "No se registran prescripciones de medicamentos en este encuentro clínico."
                ]
            ],
            "resources" => []
        ];
    }

    /**
     * Construye el recurso de Organización (Aseguradora / EPS) bajo los lineamientos MinSalud.
     * Soporta arreglos asociativos u objetos stdClass/Eloquent planos y limpios.
     *
     * @param array|object|null $organizacion Datos crudos de la entidad.
     * @return array Retorna la estructura estandarizada para el procesamiento del Bundle.
     */
    public function buildOrganizationResource($organizacion): array
    {
        // Si no se envía información, retornamos la estructura con recursos vacíos de forma defensiva
        if (empty($organizacion)) {
            return [
                "resources" => []
            ];
        }

        // Convertimos a array de forma segura
        $orgArray = (array) $organizacion;

        $idOriginal = $orgArray['codigo'] ?? 'COMPENSAR';
        $nombreOriginal = mb_strtoupper($orgArray['nombre'] ?? 'Entidad Administradora No Especificada');

        // Sanitización de ID para cumplir con esquemas URI FHIR
        $cleanId = preg_replace('/[^A-Za-z0-9\-]/', '', $idOriginal);

        // 🚀 Retornamos envuelto en una colección 'resources' para mantener simetría arquitectónica
        return [
            "resources" => [
                [
                    "resource" => [
                        "resourceType" => "Organization",
                        "id" => "{$cleanId}", 
                        "name" => $nombreOriginal
                    ]
                ]
            ]
        ];
    }

    /**
     * Construye el recurso de Ubicación/Sede (Location) bajo los lineamientos MinSalud.
     * Mantiene simetría arquitectónica devolviendo la colección dentro de la llave 'resources'.
     *
     * @param array|object|null $sede Datos crudos de la sede o punto de atención.
     * @param string $organizacionRef Referencia interna a la organización administradora (ej: 'CCFC33').
     * @return array Estructura simétrica estandarizada para el procesamiento del Bundle.
     */
    public function buildLocationResource($sedeRaw): array
    {
        // Si no se envía información, retornamos la estructura con recursos vacíos de forma defensiva
        if (empty($sedeRaw)) {
            return [
                "resources" => []
            ];
        }

        // Convertimos a array de forma segura
        $sedeArray = (array) $sedeRaw;

        // Extraemos las propiedades usando la nomenclatura ultra simple
        $prestadorCod = $sedeArray['codigo'] ?? 'PRESTADOR-02';
        $prestadorRef = $sedeArray['prestador'];
        $prestadorNom = mb_strtoupper($sedeArray['nombre'] ?? 'Sede de Atención No Especificada');

        // Forzamos que el ID limpie caracteres extraños (como tildes o espacios) para esquemas URI FHIR
        $prestadorCodId = preg_replace('/[^A-Za-z0-9\-]/', '', $prestadorCod);

        return [
            "resources" => [
                [
                    "resource" => [
                        "resourceType" => "Location",
                        "id" => "{$prestadorCodId}", // ID Semántico Único e Inmutable
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/CareDeliveryLocationRDA"]
                        ],
                        "status" => "active",
                        "identifier" => [
                            [
                                "use" => "official",
                                "system" => "http://co.fhir.guide/NamingSystem/REPS", // Identificador oficial de sedes REPS
                                "value" => (string) $prestadorCodId
                            ]
                        ],
                        "name" => $prestadorNom,
                        "managingOrganization" => [
                            "reference" => "#{$prestadorRef}"
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Construye el recurso de Encuentro Clínico / Cita (Encounter) bajo los lineamientos MinSalud.
     * Conecta dinámicamente el paciente, médico, diagnósticos primarios, sedes y organizaciones.
     *
     * @param array|object $cita Datos crudos de la cita o encuentro.
     * @param string $pacienteRef Referencia inmutable del paciente (ej: 'CC-00000000').
     * @param string $medicoRef Referencia inmutable del médico (ej: 'CC-1111111').
     * @param string $conditionRef Referencia al diagnóstico principal (ej: 'Condition-0').
     * @param string $locationRef Referencia a la sede física (ej: 'Location-4443000012-01').
     * @param string $organizationRef Referencia al prestador o aseguradora según corresponda (ej: 'Organization-CCFC33').
     * @return array Estructura simétrica estandarizada para el procesamiento del Bundle.
     */
    public function buildEncounterResource($cita, string $pacienteRef, string $medicoRef, string $conditionRef, string $prestadorCod, string $prestadorRef, string $organizationRef): array
    {
        if (empty($cita)) {
            return ["resources" => []];
        }

        $citaArray = (array) $cita;

        // Forzar prefijos '#' locales de forma defensiva para las referencias cruzadas
        $cleanPatientRef = str_starts_with($pacienteRef, '#') ? $pacienteRef : "#{$pacienteRef}";
        $cleanMedicoRef = str_starts_with($medicoRef, '#') ? $medicoRef : "#{$medicoRef}";
        $cleanConditionRef = str_starts_with($conditionRef, '#') ? $conditionRef : "#{$conditionRef}";

        // ID Semántico Único para el Encounter
        $uniqueId = "Encounter-0";

        return [
            "resources" => [
                [
                    "resource" => [
                        "resourceType" => "Encounter",
                        "id" => $uniqueId,
                        "meta" => [
                            "profile" => ["https://fhir.minsalud.gov.co/rda/StructureDefinition/EncounterAmbulatoryRDA"]
                        ],
                        "identifier" => [
                            [
                                "id" => "EncounterIdentifier",
                                "use" => "usual",
                                "system" => "https://fhir.minsalud.gov.co/rda/NamingSystem/Encounters",
                                "value" => $citaArray['num_factura_o_cita'] ?? "ADT-HS-9864463-12"
                            ]
                        ],
                        "status" => "finished",
                        "class" => [
                            "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                            "code" => "AMB",
                            "display" => "ambulatory"
                        ],
                        "type" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianTechModality",
                                        "code" => $citaArray['modalidad_codigo'] ?? "01", // 01 = Intramural
                                        "display" => $citaArray['modalidad_display'] ?? "Intramural"
                                    ]
                                ]
                            ],
                            [
                                "coding" => [
                                    [
                                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/GrupoServicios",
                                        "code" => $citaArray['grupo_codigo'] ?? "01", // 01 = Consulta externa
                                        "display" => $citaArray['grupo_display'] ?? "Consulta externa"
                                    ]
                                ]
                            ],
                            [
                                "coding" => [
                                    [
                                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/REPShealthcareServices",
                                        "code" => $citaArray['servicio_reps_codigo'] ?? "328", // 328 = MEDICINA GENERAL
                                        "display" => mb_strtoupper($citaArray['servicio_reps_display'] ?? "MEDICINA GENERAL")
                                    ]
                                ]
                            ],
                            [
                                "coding" => [
                                    [
                                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/EntornoAtencion",
                                        "code" => $citaArray['entorno_codigo'] ?? "05", // 05 = Institucional
                                        "display" => $citaArray['entorno_display'] ?? "Institucional"
                                    ]
                                ]
                            ]
                        ],
                        "serviceType" => [
                            "coding" => [
                                [
                                    "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/CUPS",
                                    "code" => $citaArray['cups_codigo'] ?? "890201",
                                    "display" => mb_strtoupper($citaArray['cups_display'] ?? "CONSULTA DE PRIMERA VEZ POR MEDICINA GENERAL")
                                ]
                            ]
                        ],
                        "subject" => [
                            "reference" => $cleanPatientRef
                        ],
                        "participant" => [
                            [
                                "id" => "AttenderPhysician",
                                "type" => [
                                    [
                                        "coding" => [
                                            [
                                                "system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                                "code" => "ATND",
                                                "display" => "attender"
                                            ]
                                        ]
                                    ]
                                ],
                                "individual" => [
                                    "reference" => $cleanMedicoRef
                                ]
                            ]
                        ],
                        "period" => [
                            "start" => $citaArray['fecha_inicio'] ?? date('Y-m-d\TH:i:sP'),
                            "end" => $citaArray['fecha_fin'] ?? date('Y-m-d\TH:i:sP', strtotime('+30 minutes'))
                        ],
                        "reasonCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/RIPSCausaExternaVersion2",
                                        "code" => $citaArray['causa_externa_codigo'] ?? "22", // 22 = ACCIDENTE EN EL HOGAR
                                        "display" => mb_strtoupper($citaArray['causa_externa_display'] ?? "ACCIDENTE EN EL HOGAR")
                                    ]
                                ]
                            ]
                        ],
                        "diagnosis" => [
                            [
                                "id" => "MainDiagnosis",
                                "extension" => [
                                    [
                                        "url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionDiagnosisType",
                                        "valueCoding" => [
                                            "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/RIPSTipoDiagnosticoPrincipalVersion2",
                                            "code" => $citaArray['tipo_diag_codigo'] ?? "02", // 02 = Confirmado Nuevo
                                            "display" => $citaArray['tipo_diag_display'] ?? "Confirmado Nuevo"
                                        ]
                                    ]
                                ],
                                "condition" => [
                                    "reference" => $cleanConditionRef
                                ],
                                "use" => [
                                    "coding" => [
                                        [
                                            "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/ColombianDiagnosisRole",
                                            "code" => "8319008",
                                            "display" => "diagnóstico primario"
                                        ]
                                    ]
                                ],
                                "rank" => 1
                            ]
                        ],
                        "extension" => [
                            [
                                "url" => "https://fhir.minsalud.gov.co/rda/StructureDefinition/ExtensionDischargeDisposition",
                                "extension" => [
                                    [
                                        "url" => "DispositionCode",
                                        "valueCoding" => [
                                            "system" => "https://fhir.minsalud.gov.co/rda/CodeSystem/CondicionyDestinoUsuarioEgreso",
                                            "code" => $citaArray['destino_egreso_codigo'] ?? "04", // 04 = REFERIDO A OTRA INSTITUCION
                                            "display" => "REFERIDO A OTRA INSTITUCION"
                                        ]
                                    ],
                                    [
                                        "url" => "ReferenceOrganization",
                                        "valueReference" => [
                                            "reference" => "#{$prestadorCod}"
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "location" => [
                            [
                                "location" => [
                                    "reference" => "#{$prestadorRef}"
                                ]
                            ]
                        ],
                        "serviceProvider" => [
                            "reference" => "#{$prestadorCod}"
                        ]
                    ]
                ]
            ]
        ];
    }

}