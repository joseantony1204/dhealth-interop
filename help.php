¡Excelente que ya existan! Eso nos ahorra un montón de tiempo en migraciones de base de datos. Si ciudad_id, departamento_id, sexo_id, etc., ya están integrados en las tablas personas y cfmaestras, lo único que debemos asegurar a nivel de Normalización (Reglas de Negocio de la Etapa 1) es la homologación de códigos.

Es decir, que cuando el Ministerio nos pida el código DIVIPOLA (ej. 11001 para Bogotá), dhealth sepa que su ciudad_id => 45 equivale exactamente a ese código oficial. Eso lo resolveremos limpiamente con métodos mapeadores en los modelos o mediante tablas de equivalencias de la maestra.

Siguiendo estrictamente el orden de la Etapa 1 (Semanas 1 y 2), enfoquémonos en el corazón del entregable actual: El Motor de Selección de Servicios e Inclusión y la Arquitectura de Conectores (Escalabilidad).
🛠️ Diseñando el Motor de Selección (Configuración)

Para que la administración de dhealth decida qué contratos/convenios reportar, qué tipos de servicios incluir (Consulta Externa) y qué RDAs están activos, necesitamos persistir esa configuración en la base de datos.

Vamos a crear la tabla que gobernará las reglas de negocio del motor de interoperabilidad. Corre el siguiente comando en tu terminal para generar la migración:
Bash

php artisan make:migration create_interop_settings_table

Abre el archivo generado en database/migrations/xxxx_xx_xx_xxxxxx_create_interop_settings_table.php y reemplaza el método up() con este esquema estructurado y escalable:
PHP

public function up()
{
    Schema::create('interop_settings', function (Blueprint $table) {
        $table->id();
        
        // Tipo de Registro de Datos de Atención (RDA): 'paciente', 'consulta', 'procedimiento', etc.
        $table->string('rda_type', 50); 
        
        // Filtros del Motor de Selección
        $table->unsignedBigInteger('convenio_id')->nullable(); // Relación con cfconvenios
        $table->unsignedBigInteger('servicio_id')->nullable(); // Relación con tus servicios de adcitas
        
        // Control de Estado y Conector asignado
        $table->string('connector_driver', 50)->default('ministerio_fhir'); // Para saber qué lógica/api usar
        $table->boolean('is_active')->default(true); // Interruptor On/Off para este flujo
        
        $table->timestamps();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('updated_by')->nullable();

        // Índices para optimizar las consultas del Job asíncrono
        $table->index(['rda_type', 'is_active']);
    });
}

Luego ejecuta la migración para crear la tabla físicamente en tu XAMPP:
Bash

php artisan migrate

📐 Estructurando la Escalabilidad: El Patrón "Conector"

Para asegurar que el módulo sea tolerante a cambios del Ministerio o futuras integraciones con otras plataformas sin modificar el núcleo de dhealth, implementaremos el Patrón Estrategia (Strategy Pattern) mediante Managers de Laravel.

Vamos a crear la estructura de carpetas necesaria para los Conectores y Servicios. Ejecuta estos comandos en tu terminal de Mac para crear el árbol de directorios:
Bash

mkdir -p app/Services/Interop/Connectors

1. La Interfaz del Conector (app/Services/Interop/Connectors/ConnectorInterface.php)

Crea este archivo. Esta interfaz obliga a cualquier conector futuro (sea del Ministerio, una EPS privada o una actualización internacional) a implementar los mismos métodos de procesamiento, firma y envío:
PHP

<?php

namespace App\Services\Interop\Connectors;

interface ConnectorInterface
{
    /**
     * Procesa y valida el payload según las reglas del estándar (Ej: FHIR).
     */
    public function validateAndFormat(array $data): array;

    /**
     * Aplica la Firma Digital Avanzada (.p12) al recurso.
     */
    public function signPayload(array $payload): string;

    /**
     * Transmite los datos al endpoint definitivo con lógica de reintentos.
     */
    public function transmit(string $signedPayload): array;
}

2. El Conector del Ministerio (app/Services/Interop/Connectors/MinisterioFhirConnector.php)

Crea el primer conector real que usará dhealth. De momento, dejará la estructura lista para cuando en la Etapa 2 integremos Guzzle y los certificados .p12:
PHP

<?php

namespace App\Services\Interop\Connectors;

class MinisterioFhirConnector implements ConnectorInterface
{
    public function validateAndFormat(array $data): array
    {
        // Aquí irá la validación semántica estricta del JSON/FHIR
        return $data;
    }

    public function signPayload(array $payload): string
    {
        // Aquí se aplicará el sellado con la llave privada en la Etapa 2
        return json_encode($payload);
    }

    public function transmit(string $signedPayload): array
    {
        // Aquí operará Guzzle con las colas y políticas de reintento
        return ['status' => 'pending', 'message' => 'Listo para transmisión'];
    }
}

🚦 ¿Qué sigue ahora?

Con la tabla de configuración creada y la base del patrón Conector lista, el siguiente paso de esta Semana 1 es crear el Modelo de Laravel (InteropSetting.php) y diseñar el Query Scope que se encargará de filtrar los pacientes de dhealth.

Ese Query Scope dirá textualmente: "Tráeme solo los pacientes de la tabla adpacientes que tengan citas asistidas (adcitas.estado_id válidos), cuyo convenio y servicio estén marcados como activos en la tabla interop_settings para el RDA de pacientes".

¿Lograste correr la migración y crear los archivos de los conectores con éxito?





Opción A (FHIR API): Creamos los primeros controladores en Laravel para servir recursos estándar en JSON (por ejemplo, estructurar el recurso Patient o Encounter bajo el estándar FHIR).

Opción B (Procesador HL7 Plano): Creamos un parser/orquestador en PHP capaz de recibir una cadena HL7 clásica por POST, trocearla, validar su estructura y meterla a la base de datos de forma limpia.

Opción C (Estructura de Base de Datos Core): Diseñamos las migraciones de Laravel para las tablas relacionales de afiliados, logs de auditoría médica y tokens de acceso clínico seguro.

3. Siguiente Paso del Cronograma: El Motor de Selección de Servicios

Con las tablas de datos normalizadas instaladas, lo que sigue en esta primera etapa es construir la interfaz administrativa del Motor de Selección de Servicios (los filtros por tipo de contrato, convenios y si el nodo RDA está activo o apagado).

¿Quieres que desarrollemos el API Resource de Laravel que va a empaquetar estos datos en formato FHIR limpio o pasamos directamente a programar la lógica del backend para filtrar qué consultas califican para ser enviadas al Ministerio?


import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { HiAdjustments, HiCheckCircle, HiXCircle } from 'react-icons/hi';

export default function RdaConfig({ auth, convenios }) {
    
    const handleToggle = (convenioId, currentStatus) => {
        router.post(route('rda.config.toggle'), {
            convenio_id: convenioId,
            is_active: currentStatus === 1 ? 0 : 1
        }, {
            preserveScroll: true
        });
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Motor de Selección de Servicios">
            <Head title="Configuración RDA" />

            <div className="bg-white dark:bg-[#111827] rounded-2xl border border-slate-100 dark:border-slate-800/40 p-6 shadow-xs max-w-4xl">
                <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                        <HiAdjustments className="w-5 h-5" />
                    </div>
                    <div>
                        <h2 className="text-sm font-bold text-slate-800 dark:text-slate-200">Enrutamiento Temprano de Contratos</h2>
                        <p className="text-xs text-slate-400 mt-0.5">Define qué aseguradoras o convenios están obligados a reportar eventos clínicos en tiempo real.</p>
                    </div>
                </div>

                <div className="overflow-hidden border border-slate-100 dark:border-slate-800/60 rounded-xl">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-slate-50/70 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800/40">
                                <th className="px-4 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Convenio / Aseguradora</th>
                                <th className="px-4 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tipo de Filtro</th>
                                <th className="px-4 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider text-right">Estatus RDA</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50 dark:divide-slate-800/40">
                            {convenios.map((item) => (
                                <tr key={item.convenio_id} className="hover:bg-slate-50/40 dark:hover:bg-slate-800/10 transition-colors">
                                    <td className="px-4 py-3.5">
                                        <p className="text-xs font-bold text-slate-800 dark:text-slate-200">{item.convenio_nombre}</p>
                                        <p className="text-[10px] text-slate-400 font-mono">ID-CONV: 00{item.convenio_id}</p>
                                    </td>
                                    <td className="px-4 py-3.5">
                                        <span className="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400">
                                            {item.service_type}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3.5 text-right">
                                        <button
                                            onClick={() => handleToggle(item.convenio_id, item.is_active)}
                                            className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold transition-all border cursor-pointer select-none
                                                ${item.is_active === 1 
                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30' 
                                                    : 'bg-slate-50 text-slate-400 border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700/60'
                                                }`}
                                        >
                                            {item.is_active === 1 ? (
                                                <>
                                                    <HiCheckCircle className="w-4 h-4 text-emerald-500" /> Transmitiendo
                                                </>
                                            ) : (
                                                <>
                                                    <HiXCircle className="w-4 h-4 text-slate-400" /> Excluido
                                                </>
                                            )}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

https://web.sispro.gov.co/WebPublico/Consultas/ConsultarDetalleReferenciaBasica.aspx?Code=DCI