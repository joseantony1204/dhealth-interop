# ***Temas importantes a definir*** #
# 1. Colocar campo en servicios para identificar los que se van a transmitir al MIN SALUD Tpo /Intramural/
# 2. Hacer un Observer para cuando se cierre una historia enviarla a D-HEALTH CORE IHCE
# 3. Validar citas de tal manera que no se envien repetidas al D-HEALTH CORE IHCE


# ***Temas complementarios*** #
# Construir Apis para los siguintes puntos:
# 0. Datos demograficos paciente params{pacienteId: pacienteId}
{

  "pac_identificacion": "10203040",
  "pac_tipo_id": "CC",
  "pac_tipo_id_nombre": "CEDULA DE CIUDADANIA",
  "pac_nombre": "JUAN",
  "pac_segundonombre": "DAVID",
  "pac_apellido": "PEREZ",
  "pac_segundoapellido": "LOPEZ",
  "pac_fechanacimiento": "1990-05-15",
  "pac_telefono": "3117187108",
  "pac_codigo_sexo: "F",
  "pac_nombre_sexo: "Femenino", 
  "pac_extra_sexo: "02" 
  "pac_zona_codigo: "",
  "pac_zona_nombre: "",
  "pac_etnia_codigo: "6" 
  "pac_etnia_nombre": "Otras etnias" 
  "pac_discapacidad_codigo: "",
  "pac_discapacidad_nombre "",
  "pac_pais_codigo: "170",
  "pac_pais_nombre: "Colombia"
  "pac_departamento_codigo: "GUA" 
  "pac_departamento_nombre: "LA GUAJIRA" 
  "pac_ciudad_codigo: "44001" 
  "pac_ciudad_nombre: "RIOHACHA" 

  "med_identificacion": "71223344",
  "med_tipo_id": "CC",
  "med_nombre": "MARIA",
  "med_segundoapellido": "LUZ",
  "med_apellido": "GOMEZ",
  "med_segundoapellido": "PEREZ",
  "med_tipo_id: "CC",
  "med_tipo_id_nombre": "CEDULA DE CIUDADANIA" 

  "cita_fecha": "2026-05-25 08:00:00",
  
  "prestador":[
    { 
        "emp_nombre": "...", 
        "emp_identificacion": "...", 
        "emp_tipo_identificacion": "..."
        "emp_codigo_prestador": "..."
        "emp_direccion": "..."
    },
  ],
  "medicamentos": [
    { "codigo": "961", "nombre": "SULFASOMIZOL", "observacion": "Toma 1 tableta..." }
  ],
  "diagnosticos": [
    { "cie10_codigo": "I10X", "cie10_nombre": "Hipertensión esencial", "texto_libre": "..." }
  ],
  "alergias": [
    { "codigo": "01", "tipo_display": "Medicamento", "sustancia_texto": "Penicilina" }
  ],
  "antecedentesfamiliares": [
    { "parentesco_codigo": "01", "parentesco_display": "Padres", "cie10_codigo": "Z034", "cie10_nombre": "..." }
  ]
}

# 1. Medicamentos
#    https://web.sispro.gov.co/WebPublico/Consultas/ConsultarDetalleReferenciaBasica.aspx?Code=DCI
- $medicamentosRaw = [
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
# 2. Diagnosticos
#    https://web.sispro.gov.co/WebPublico/Consultas/ConsultarDetalleReferenciaBasica.aspx?Code=CIE10
- $diagnosticosRaw = [
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
# 3. Alergias
- $alergiasRaw = [
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
# 4. Antecedentes Familiares (dependen del CIE10)
- $familiaresRaw = [
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


El documento ha sido generado con el número 141254383951

-RDA consulta
--Encouter en AllergyIntolerance

-MedicationRequest en vez de MedicationStatement
--Encouter en MedicationRequest

-Observation
-Location
-Observation 
--hay un Observation con Observationy otro no
-Organization