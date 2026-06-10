import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { HiDatabase, HiGlobe, HiChip, HiRefresh, HiShieldCheck } from 'react-icons/hi';

export default function Dashboard({ auth, telemetria, transmisiones }) {
    const { chartData, uptimeBlocks, hardware, nodos, uptimeGlobal } = telemetria;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header="Consola de Alta Disponibilidad"
        >
            <Head title="Métricas del Sistema" />

            {/* SECCIÓN 1: TELEMETRÍA Y CONTROL DE FLUJO */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                
                {/* COMPONENTE DE LA GRÁFICA DE CARGA REAL CON CONTROL DE ZONA FUTURA */}
                <div className="lg:col-span-2 bg-white dark:bg-[#111827] rounded-2xl border border-slate-100 dark:border-slate-800/40 p-6 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.02)] flex flex-col justify-between">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <span className="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest block">Telemetría Core</span>
                            <h3 className="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">Timeline de Carga de Peticiones (Tiempo Real)</h3>
                        </div>
                        <span className="text-xs font-medium text-slate-400 bg-slate-50 dark:bg-slate-800 px-2 py-1 rounded-md border border-slate-100 dark:border-slate-800/60 flex items-center gap-1">
                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> En Vivo
                        </span>
                    </div>

                    {/* Contenedor del Timeline */}
                    <div className="h-48 flex items-end justify-between gap-3 pt-4 border-b border-slate-100 dark:border-slate-800/60 px-2 relative">
                        {chartData.map((item, index) => (
                            <div 
                                key={index} 
                                className={`flex-1 flex flex-col items-center gap-2 ${
                                    item.es_futuro ? 'pointer-events-none select-none opacity-10' : 'group cursor-pointer'
                                }`}
                            >
                                {/* Tooltip Inteligente: Solo activo en el pasado real con peticiones */}
                                {!item.es_futuro && item.peticiones !== '0' && (
                                    <div className="absolute mb-44 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-950 text-white text-[10px] font-mono py-1 px-2 rounded shadow-xl pointer-events-none z-10">
                                        {item.peticiones} reqs
                                    </div>
                                )}
                                
                                {/* Barra Física: Forzada a invisibilidad total si es una hora del futuro */}
                                <div 
                                    className={`w-full ${item.carga} bg-gradient-to-t from-indigo-600 to-indigo-400 dark:from-indigo-950 dark:to-indigo-500 rounded-t-lg transition-all duration-300 ${
                                        item.es_futuro || item.peticiones === '0' 
                                            ? '!h-0 opacity-0 invisible pointer-events-none' 
                                            : 'group-hover:from-indigo-500 group-hover:to-indigo-300 group-hover:scale-x-105 shadow-[0_2px_10px_rgba(79,70,229,0.15)]'
                                    }`} 
                                    style={item.es_futuro ? { height: '0px' } : {}}
                                />
                            </div>
                        ))}
                    </div>

                    {/* Eje X cronológico */}
                    <div className="flex justify-between items-center mt-3 px-2">
                        {chartData.map((item, index) => (
                            <span 
                                key={index} 
                                className={`text-[10px] font-mono font-bold flex-1 text-center ${
                                    item.es_futuro 
                                        ? 'text-slate-200 dark:text-slate-800/30 line-through opacity-40' 
                                        : 'text-slate-400'
                                    }`}
                            >
                                {item.hora}
                            </span>
                        ))}
                    </div>
                </div>

                {/* RENDIMIENTO TRANSACCIONAL DEL API */}
                <div className="bg-white dark:bg-[#111827] rounded-2xl border border-slate-100 dark:border-slate-800/40 p-6 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.02)] flex flex-col justify-between">
                    <div>
                        <div className="flex justify-between items-center mb-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">Rendimiento del API (24h)</h3>
                            <HiChip className="w-4 h-4 text-indigo-500" />
                        </div>

                        {[
                            ['Tasa de Éxito (APPROVED)', hardware.efectividad, 'bg-emerald-500', 'text-emerald-600 dark:text-emerald-400'],
                            ['Flujo en Cola (PENDING)', hardware.encola, 'bg-amber-500', 'text-amber-600 dark:text-amber-400'],
                            ['Tasa de Rechazo (REJECTED)', hardware.rechazo, 'bg-rose-500', 'text-rose-600 dark:text-rose-400']
                        ].map(([label, value, barColor, textColor], idx) => (
                            <div key={idx} className="space-y-1.5 mb-4">
                                <div className="flex justify-between text-xs">
                                    <span className="font-semibold text-slate-700 dark:text-slate-300">{label}</span>
                                    <span className={`font-mono font-bold ${textColor}`}>{value}%</span>
                                </div>
                                <div className="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                    <div className={`${barColor} h-2 rounded-full transition-all duration-500`} style={{ width: `${value}%` }} />
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="pt-4 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-[11px] text-slate-400">
                        <span className="flex items-center gap-1"><HiRefresh className="w-3 h-3 animate-spin text-slate-300" /> Sincronizado</span>
                        <span className="font-mono text-slate-500">Gateway Core</span>
                    </div>
                </div>

            </div>

            {/* SECCIÓN 2: MAPA DE CALOR DE LAS ÚLTIMAS 24 HORAS */}
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

                <div className="grid grid-cols-6 sm:grid-cols-12 md:grid-cols-[repeat(24,minmax(0,1fr))] gap-2 pt-2">
                    {uptimeBlocks.map((block, index) => (
                        <div key={index} className="group relative flex flex-col items-center">
                            <div className={`w-full h-10 rounded-md ${block.status} opacity-85 hover:opacity-100 transition-all cursor-pointer shadow-sm`} />
                            <span className="absolute bottom-12 bg-slate-950 text-white font-mono text-[10px] py-1.5 px-2.5 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-20 shadow-xl border border-slate-800">
                                <strong className="text-indigo-400 block border-b border-slate-800 pb-0.5 mb-1">{block.hora}</strong>
                                {block.detalle}
                            </span>
                        </div>
                    ))}
                </div>

                <div className="flex justify-between items-center text-[11px] text-slate-400 mt-4 pt-3 border-t border-slate-50 dark:border-slate-800/60">
                    <span>Hace 24 horas</span>
                    <span className="font-bold text-emerald-600 dark:text-emerald-400">{uptimeGlobal} Uptime Global</span>
                    <span>Ahora</span>
                </div>
            </div>

            {/* SECCIÓN 3: TABLA DE TRANSMISIONES Y PAGINADOR */}
            <div className="bg-white dark:bg-[#111827] rounded-2xl border border-slate-100 dark:border-slate-800/40 shadow-[0_4px_20px_-5px_rgba(0,0,0,0.02)] overflow-hidden">
                <div className="px-6 py-5 border-b border-slate-50 dark:border-slate-800/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 className="text-sm font-bold text-slate-800 dark:text-slate-200">Nodos de Interconexión Activos</h3>
                        <p className="text-xs text-slate-400 mt-0.5">Puntos de control autorizados que transmiten payloads clínicos.</p>
                    </div>
                    <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 rounded-lg text-xs font-bold self-start sm:self-auto">
                        <HiGlobe className="w-3.5 h-3.5" /> Puerto Seguro
                    </span>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-slate-50/70 dark:bg-slate-800/30 border-b border-slate-100 dark:border-slate-800/40">
                                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Identificador Clínico</th>
                                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Mecanismo</th>
                                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Seguridad / Estado</th>
                                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider text-right">Firmas / Tenant</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-50 dark:divide-slate-800/40">
                            {nodos.map((nodo, idx) => (
                                <tr key={idx} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                                    <td className="px-6 py-3.5">
                                        <p className="text-xs font-bold text-slate-800 dark:text-slate-200">Cita ID: {nodo.cita_id}</p>
                                        <p className="text-[10px] text-slate-400 font-mono">{nodo.identificador}</p>
                                    </td>
                                    <td className="px-6 py-3.5">
                                        <p className="font-mono text-xs text-slate-500">{nodo.mecanismo}</p>
                                        <p className="text-[10px] text-slate-400 font-mono">{nodo.fecha}</p>
                                    </td>
                                    <td className="px-6 py-3.5">
                                        <div className="flex flex-col gap-1 items-start">
                                            <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">
                                                <HiShieldCheck className="w-3 h-3" /> {nodo.seguridad}
                                            </span>
                                            <span className={`inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider ${
                                                nodo.estado === 'APPROVED' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400' :
                                                nodo.estado === 'PENDING' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400' :
                                                'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-400'
                                            }`}>
                                                {nodo.estado} {nodo.reintentos > 0 && `(${nodo.reintentos} retries)`}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-3.5 font-mono text-xs text-slate-400 text-right truncate max-w-[150px]" title={nodo.token}>
                                        {nodo.token.substring(0, 13)}...
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* COMPONENTE DE PAGINACIÓN */}
                <div className="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/20 border-t border-slate-100 dark:border-slate-800/40 flex items-center justify-between">
                    <p className="text-xs text-slate-500">
                        Mostrando <span className="font-semibold">{transmisiones.from}</span> a <span className="font-semibold">{transmisiones.to}</span> de <span className="font-semibold">{transmisiones.total}</span> transmisiones
                    </p>
                    <div className="flex gap-1">
                        {transmisiones.links.map((link, index) => {
                            if (link.label === "...") {
                                return <span key={index} className="px-3 py-1 text-xs text-slate-400">...</span>;
                            }
                            return (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`px-3 py-1 rounded-md text-xs font-medium transition-all ${
                                        link.active 
                                            ? 'bg-indigo-600 text-white shadow-sm' 
                                            : link.url 
                                                ? 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 hover:bg-slate-50 dark:hover:bg-slate-700' 
                                                : 'text-slate-300 dark:text-slate-600 cursor-not-allowed'
                                    }`}
                                    as={link.url && !link.active ? 'a' : 'button'}
                                    disabled={!link.url || link.active}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </div>
                </div>

            </div>
        </AuthenticatedLayout>
    );
}