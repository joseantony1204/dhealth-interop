import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
// Iconos re-verificados y blindados para versiones antiguas de react-icons/hi
import { HiTrendingUp, HiDatabase, HiGlobe, HiChip, HiRefresh, HiShieldCheck } from 'react-icons/hi';

export default function Dashboard({ auth }) {
    // Datos simulados para la gráfica (Porcentajes de altura para las barras de Tailwind)
    const chartData = [
        { hora: '02:00', carga: 'h-16', peticiones: '340' },
        { hora: '04:00', carga: 'h-24', peticiones: '510' },
        { hora: '06:00', carga: 'h-12', peticiones: '210' },
        { hora: '08:00', carga: 'h-32', peticiones: '890' },
        { hora: '10:00', carga: 'h-40', peticiones: '1,284' }, // Pico
        { hora: '12:00', carga: 'h-36', peticiones: '1,102' },
        { hora: '14:00', carga: 'h-28', peticiones: '750' },
        { hora: '16:00', carga: 'h-20', peticiones: '460' },
    ];

    // Generamos 24 bloques simulando las últimas 24 horas del Gateway
    const uptimeBlocks = [
        { hora: '01:00', status: 'bg-emerald-500' }, { hora: '02:00', status: 'bg-emerald-500' },
        { hora: '03:00', status: 'bg-emerald-500' }, { hora: '04:00', status: 'bg-emerald-500' },
        { hora: '05:00', status: 'bg-rose-500' },    { hora: '06:00', status: 'bg-emerald-500' }, 
        { hora: '07:00', status: 'bg-emerald-500' }, { hora: '08:00', status: 'bg-emerald-500' },
        { hora: '09:00', status: 'bg-emerald-500' }, { hora: '10:00', status: 'bg-amber-500' }, 
        { hora: '11:00', status: 'bg-emerald-500' }, { hora: '12:00', status: 'bg-emerald-500' },
        { hora: '13:00', status: 'bg-emerald-500' }, { hora: '14:00', status: 'bg-emerald-500' },
        { hora: '15:00', status: 'bg-emerald-500' }, { hora: '16:00', status: 'bg-emerald-500' },
        { hora: '17:00', status: 'bg-emerald-500' }, { hora: '18:00', status: 'bg-emerald-500' },
        { hora: '19:00', status: 'bg-emerald-500' }, { hora: '20:00', status: 'bg-emerald-500' },
        { hora: '21:00', status: 'bg-emerald-500' }, { hora: '22:00', status: 'bg-emerald-500' },
        { hora: '23:00', status: 'bg-emerald-500' }, { hora: '00:00', status: 'bg-emerald-500' },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header="Consola de Alta Disponibilidad"
        >
            <Head title="Métricas del Sistema" />

            {/* SECCIÓN 1: PANEL DE CONTROL DE RENDIMIENTO (GRÁFICA + METRICAS HARDWARE) */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                
                {/* COMPONENTE DE LA GRÁFICA (Ocupa 2 columnas) */}
                <div className="lg:col-span-2 bg-white dark:bg-[#111827] rounded-2xl border border-slate-100 dark:border-slate-800/40 p-6 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.02)] flex flex-col justify-between">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <span className="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest block">Telemetría Core</span>
                            <h3 className="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">Volumen de Carga de Peticiones (REST / HL7)</h3>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-xs font-medium text-slate-400 bg-slate-50 dark:bg-slate-800 px-2 py-1 rounded-md border border-slate-100 dark:border-slate-800/60 flex items-center gap-1">
                                <span className="h-1.5 w-1.5 rounded-full bg-indigo-500"></span> Intervalo: 2h
                            </span>
                        </div>
                    </div>

                    {/* Contenedor de las barras de la gráfica */}
                    <div className="h-48 flex items-end justify-between gap-3 pt-4 border-b border-slate-100 dark:border-slate-800/60 px-2 relative">
                        {chartData.map((item, index) => (
                            <div key={index} className="flex-1 flex flex-col items-center gap-2 group cursor-pointer">
                                {/* Tooltip flotante al hacer hover */}
                                <div className="absolute mb-44 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-950 text-white text-[10px] font-mono py-1 px-2 rounded shadow-xl pointer-events-none z-10">
                                    {item.peticiones} reqs
                                </div>
                                {/* Barra estilizada */}
                                <div className={`w-full ${item.carga} bg-gradient-to-t from-indigo-600 to-indigo-400 dark:from-indigo-950 dark:to-indigo-500 rounded-t-lg transition-all duration-300 group-hover:from-indigo-500 group-hover:to-indigo-300 group-hover:scale-x-105 shadow-[0_2px_10px_rgba(79,70,229,0.15)]`} />
                            </div>
                        ))}
                    </div>

                    {/* Etiquetas del eje X de la gráfica */}
                    <div className="flex justify-between items-center mt-3 px-2">
                        {chartData.map((item, index) => (
                            <span key={index} className="text-[10px] text-slate-400 font-mono font-bold flex-1 text-center">
                                {item.hora}
                            </span>
                        ))}
                    </div>
                </div>

                {/* HARDWARE STATUS / RECURSOS DE INFRAESTRUCTURA (1 columna) */}
                <div className="bg-white dark:bg-[#111827] rounded-2xl border border-slate-100 dark:border-slate-800/40 p-6 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.02)] flex flex-col justify-between">
                    <div>
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">Recursos Asignados</h3>
                            <HiChip className="w-4 h-4 text-slate-400" />
                        </div>

                        {/* Barra de progreso 1: CPU */}
                        <div className="space-y-1.5 mb-4">
                            <div className="flex justify-between text-xs">
                                <span className="font-semibold text-slate-700 dark:text-slate-300">Uso de CPU Cluster</span>
                                <span className="font-mono font-bold text-indigo-600 dark:text-indigo-400">32%</span>
                            </div>
                            <div className="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div className="bg-indigo-600 h-2 rounded-full w-[32%] transition-all duration-500" />
                            </div>
                        </div>

                        {/* Barra de progreso 2: RAM */}
                        <div className="space-y-1.5 mb-4">
                            <div className="flex justify-between text-xs">
                                <span className="font-semibold text-slate-700 dark:text-slate-300">Memoria RAM Utilizada</span>
                                <span className="font-mono font-bold text-emerald-600 dark:text-emerald-400">58%</span>
                            </div>
                            <div className="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div className="bg-emerald-500 h-2 rounded-full w-[58%] transition-all duration-500" />
                            </div>
                        </div>

                        {/* Barra de progreso 3: Storage */}
                        <div className="space-y-1.5">
                            <div className="flex justify-between text-xs">
                                <span className="font-semibold text-slate-700 dark:text-slate-300">Almacenamiento SSD</span>
                                <span className="font-mono font-bold text-amber-600 dark:text-amber-400">81%</span>
                            </div>
                            <div className="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div className="bg-amber-500 h-2 rounded-full w-[81%] transition-all duration-500" />
                            </div>
                        </div>
                    </div>

                    <div className="pt-4 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-[11px] text-slate-400">
                        <span className="flex items-center gap-1"><HiRefresh className="w-3 h-3 animate-spin text-slate-300" /> Actualizado hace 3s</span>
                        <span className="font-mono text-slate-500">Cluster #01</span>
                    </div>
                </div>

            </div>

            {/* SECCIÓN 2: HISTORIAL DE DISPONIBILIDAD (UPTIME GRID) */}
            <div className="bg-white dark:bg-[#111827] rounded-2xl border border-slate-100 dark:border-slate-800/40 p-6 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.02)] mb-6">
                <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-4">
                    <div>
                        <span className="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest block">Misión Crítica</span>
                        <h3 className="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">Historial de Operación del API Gateway (Últimas 24h)</h3>
                    </div>
                    <div className="flex items-center gap-4 text-xs font-medium text-slate-500">
                        <div className="flex items-center gap-1"><span className="h-2.5 w-2.5 rounded bg-emerald-500 block"></span> Operacional</div>
                        <div className="flex items-center gap-1"><span className="h-2.5 w-2.5 rounded bg-amber-500 block"></span> Degradación</div>
                        <div className="flex items-center gap-1"><span className="h-2.5 w-2.5 rounded bg-rose-500 block"></span> Interrupción</div>
                    </div>
                </div>

                {/* Grid corregido con valor dinámico para soportar las 24 columnas nativas */}
                <div className="grid grid-cols-6 sm:grid-cols-12 md:grid-cols-[repeat(24,minmax(0,1fr))] gap-2 pt-2">
                    {uptimeBlocks.map((block, index) => (
                        <div key={index} className="group relative flex flex-col items-center">
                            {/* Bloque de color */}
                            <div className={`w-full h-10 rounded-md ${block.status} opacity-85 hover:opacity-100 transition-all cursor-pointer shadow-sm`} />
                            {/* Tooltip Ajustado */}
                            <span className="absolute bottom-12 bg-slate-950 text-white font-mono text-[9px] py-1 px-1.5 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-20 shadow-xl">
                                {block.hora} - Status Check
                            </span>
                        </div>
                    ))}
                </div>

                <div className="flex justify-between items-center text-[11px] text-slate-400 mt-4 pt-3 border-t border-slate-50 dark:border-slate-800/60">
                    <span>Hace 24 horas</span>
                    <span className="font-bold text-emerald-600 dark:text-emerald-400">99.87% Uptime Global</span>
                    <span>Ahora</span>
                </div>
            </div>

            {/* SECCIÓN 3: GRID INFERIOR ASIMÉTRICO (TABLA DE ENRUTAMIENTO) */}
            <div className="bg-white dark:bg-[#111827] rounded-2xl border border-slate-100 dark:border-slate-800/40 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.02)] overflow-hidden">
                <div className="px-6 py-5 border-b border-slate-50 dark:border-slate-800/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 className="text-sm font-bold text-slate-800 dark:text-slate-200">Nodos de Interconexión Activos</h3>
                        <p className="text-xs text-slate-400 mt-0.5">Puntos de control autorizados que transmiten payloads clínicos.</p>
                    </div>
                    <div className="flex gap-2">
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 rounded-lg text-xs font-bold">
                            <HiGlobe className="w-3.5 h-3.5" /> 3 Puertos Seguros
                        </span>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-slate-50/70 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800/40">
                                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Identificador Clínico</th>
                                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Mecanismo</th>
                                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Seguridad</th>
                                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider text-right">Firmas / Token</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50 dark:divide-slate-800/40">
                            <tr className="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                                <td className="px-6 py-3.5">
                                    <p className="text-xs font-bold text-slate-800 dark:text-slate-200">Antonio Gonzalez</p>
                                    <p className="text-[10px] text-slate-400 font-mono">RDA-PACIENTE-NODE</p>
                                </td>
                                <td className="px-6 py-3.5 font-mono text-xs text-slate-500">REST / JSON</td>
                                <td className="px-6 py-3.5">
                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">
                                        <HiShieldCheck className="w-3 h-3" /> TLS 1.3
                                    </span>
                                </td>
                                <td className="px-6 py-3.5 font-mono text-xs text-indigo-600 dark:text-indigo-400 font-bold text-right">V-2026-9981</td>
                            </tr>
                            <tr className="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                                <td className="px-6 py-3.5">
                                    <p className="text-xs font-bold text-slate-800 dark:text-slate-200">Carlos Mendoza</p>
                                    <p className="text-[10px] text-slate-400 font-mono">RDA-PACIENTE-NODE</p>
                                </td>
                                <td className="px-6 py-3.5 font-mono text-xs text-slate-500">HL7 / SOAP</td>
                                <td className="px-6 py-3.5">
                                    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-50 text-slate-600 dark:bg-slate-800/60 dark:text-slate-400">
                                        <HiDatabase className="w-3 h-3" /> API KEY
                                    </span>
                                </td>
                                <td className="px-6 py-3.5 text-xs text-slate-400 text-right font-mono truncate max-w-[120px]" title="err_json_node_afiliado">
                                    err_json_node
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}