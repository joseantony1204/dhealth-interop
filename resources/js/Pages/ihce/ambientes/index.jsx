import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { 
    HiPlus, 
    HiPencilAlt, 
    HiCube, 
    HiTerminal, 
    HiDatabase,
    HiShieldCheck,
    HiLightningBolt,
    HiSearch,
    HiX,
    HiTrash,
    HiCheck
} from 'react-icons/hi';

export default function Index({ auth, ambientes }) {
    const [isOpen, setIsOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    
    // Estado Pro+ para el borrado seguro en línea
    const [deletingId, setDeletingId] = useState(null);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        id: '',
        codigo: '',
        nombre: '',
        descripcion: '',
        estado: 1,
    });

    const openModalCreate = () => {
        reset();
        setEditMode(false);
        setIsOpen(true);
    };

    const openModalEdit = (ambiente) => {
        setData({
            id: ambiente.id,
            codigo: ambiente.codigo,
            nombre: ambiente.nombre,
            descripcion: ambiente.descripcion || '',
            estado: ambiente.estado,
        });
        setEditMode(true);
        setIsOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editMode) {
            put(route('ihce.ambiente.update', data.id), {
                onSuccess: () => setIsOpen(false),
            });
        } else {
            post(route('ihce.ambiente.store'), {
                onSuccess: () => {
                    reset();
                    setIsOpen(false);
                },
            });
        }
    };

    // Método de ejecución del borrado lógico
    const executeDelete = (id) => {
        router.delete(route('ihce.ambiente.destroy', id), {
            onSuccess: () => setDeletingId(null),
            onFinish: () => setDeletingId(null)
        });
    };

    // Filtrado de nodos en tiempo real en la UI
    const filteredAmbientes = ambientes.filter(amb => 
        amb.nombre.toLowerCase().includes(searchTerm.toLowerCase()) ||
        amb.codigo.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const totalActivos = ambientes.filter(a => a.estado === 1).length;

    return (
        <AuthenticatedLayout user={auth.user} header="Infraestructura de Nodos Logísticos">
            <Head title="Core IHCE - Clústeres Operativos" />

            {/* 1. SECCIÓN: KPI METRICS BOARD */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 max-w-7xl">
                <div className="bg-white dark:bg-[#111827] border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black tracking-widest text-slate-400 dark:text-slate-500 uppercase">Clústeres Registrados</p>
                        <p className="text-xl font-black text-slate-800 dark:text-slate-100 mt-0.5">{ambientes.length}</p>
                    </div>
                    <div className="h-8 w-8 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 flex items-center justify-center text-slate-400">
                        <HiCube className="w-4 h-4" />
                    </div>
                </div>

                <div className="bg-white dark:bg-[#111827] border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black tracking-widest text-slate-400 dark:text-slate-500 uppercase">Pasarelas en Línea</p>
                        <p className="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{totalActivos}</p>
                    </div>
                    <div className="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100/50 dark:border-emerald-900/30 flex items-center justify-center text-emerald-500">
                        <HiLightningBolt className="w-4 h-4 animate-pulse" />
                    </div>
                </div>

                <div className="bg-white dark:bg-[#111827] border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black tracking-widest text-slate-400 dark:text-slate-500 uppercase">Disponibilidad Red</p>
                        <p className="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5">{ambientes.length > 0 ? ((totalActivos / ambientes.length) * 100).toFixed(0) : 0}%</p>
                    </div>
                    <div className="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100/50 dark:border-indigo-900/30 flex items-center justify-center text-indigo-500">
                        <HiShieldCheck className="w-4 h-4" />
                    </div>
                </div>
            </div>

            {/* 2. SECCIÓN: CONTROL BAR & BUSCADOR INTERACTIVO */}
            <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 mb-6 max-w-7xl">
                <div className="relative flex-1 max-w-md">
                    <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <HiSearch className="w-4 h-4" />
                    </span>
                    <input 
                        type="text"
                        value={searchTerm}
                        onChange={e => setSearchTerm(e.target.value)}
                        placeholder="Filtrar por identificador lógico o nombre de nodo..."
                        className="w-full text-xs bg-white dark:bg-[#111827] border border-slate-200/80 dark:border-slate-800/80 rounded-xl pl-9 pr-4 py-2.5 focus:outline-none focus:border-indigo-500 dark:focus:border-indigo-500 text-slate-800 dark:text-slate-200 font-medium placeholder-slate-400 shadow-2xs"
                    />
                </div>

                <button
                    onClick={openModalCreate}
                    className="flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-black text-xs px-4 py-2.5 rounded-xl transition-all shadow-md active:scale-[0.98] cursor-pointer whitespace-nowrap"
                >
                    <HiPlus className="w-4 h-4" /> Provisionar Nodo
                </button>
            </div>

            {/* 3. SECCIÓN: GRID DE INFRAESTRUCTURA DE HARDWARE VIRTUAL (VISUAL IMPACT) */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-7xl">
                {filteredAmbientes.map((amb) => {
                    const isOnline = amb.estado === 1;
                    const isConfirmingDelete = deletingId === amb.id;
                    
                    return (
                        <div 
                            key={amb.id}
                            className={`bg-white dark:bg-[#111827] border rounded-3xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 relative overflow-hidden group ${
                                isConfirmingDelete 
                                    ? 'border-rose-300 dark:border-rose-900 ring-2 ring-rose-200 dark:ring-rose-950' 
                                    : 'border-slate-100 dark:border-slate-800/80 hover:border-slate-200 dark:hover:border-slate-700'
                            }`}
                        >
                            {/* Gradiente decorativo de profundidad */}
                            <div className="absolute -top-24 -right-24 h-48 w-48 rounded-full bg-slate-50/50 dark:bg-slate-950/10 blur-3xl opacity-80 group-hover:opacity-100 transition-opacity" />

                            {/* CABECERA: IDENTIFICACIÓN Y ACCIONES */}
                            <div className="flex items-start justify-between gap-4 mb-5 relative z-10">
                                <div className="flex items-center gap-4">
                                    <div className={`h-12 w-12 rounded-2xl border flex items-center justify-center shadow-inner transition-colors ${
                                        isOnline 
                                            ? 'bg-emerald-50 border-emerald-100 text-emerald-500 dark:bg-emerald-950/30 dark:border-emerald-900/60' 
                                            : 'bg-slate-50 border-slate-100 text-slate-400 dark:bg-slate-900 dark:border-slate-800'
                                    }`}>
                                        <HiCube className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h3 className="text-base font-black text-slate-900 dark:text-white tracking-tight uppercase font-mono">
                                            {amb.nombre}
                                        </h3>
                                        <div className="flex items-center gap-2 mt-1">
                                            <div className={`h-2 w-2 rounded-full ${isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300 dark:bg-slate-700'}`} />
                                            <span className={`font-mono text-[10px] font-black tracking-widest px-2.5 py-0.5 rounded-full uppercase border ${
                                                isOnline 
                                                    ? 'bg-emerald-50/60 text-emerald-700 border-emerald-200/50 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/40' 
                                                    : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:border-slate-800'
                                            }`}>
                                                {isOnline ? 'Gateway Activo' : 'Inactivo'}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {/* ACCIONES EN LA ESQUINA SUPERIOR */}
                                <div className="flex items-center gap-1.5">
                                    {isConfirmingDelete ? (
                                        <div className="flex items-center gap-1 bg-white dark:bg-slate-900 p-1 rounded-xl border border-rose-200 dark:border-rose-900 shadow-2xs animate-fade-in">
                                            <button 
                                                onClick={() => executeDelete(amb.id)}
                                                className="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 rounded-lg cursor-pointer transition-colors"
                                                title="Confirmar"
                                            >
                                                <HiCheck className="w-3.5 h-3.5" />
                                            </button>
                                            <button 
                                                onClick={() => setDeletingId(null)}
                                                className="p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg cursor-pointer transition-colors"
                                                title="Cancelar"
                                            >
                                                <HiX className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    ) : (
                                        <>
                                            <button 
                                                onClick={() => openModalEdit(amb)}
                                                className="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 text-slate-400 hover:text-indigo-500 dark:hover:text-indigo-400 hover:border-indigo-200 dark:hover:border-indigo-900 transition-all cursor-pointer shadow-2xs"
                                                title="Editar Parámetros"
                                            >
                                                <HiPencilAlt className="w-4 h-4" />
                                            </button>
                                            <button 
                                                onClick={() => setDeletingId(amb.id)} 
                                                className="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:border-rose-200 dark:hover:border-rose-900/60 transition-all cursor-pointer shadow-2xs"
                                                title="Deprecar Nodo"
                                            >
                                                <HiTrash className="w-4 h-4" />
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>

                            {/* CONTENIDO INTERNO: DESCRIPCIÓN Y RUTEO LOGICO */}
                            <div className="space-y-4 relative z-10">
                                <p className="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 min-h-[32px] leading-relaxed">
                                    {amb.descripcion || 'Sin descripción asignada en las directivas de interoperabilidad.'}
                                </p>

                                {/* Barra de nivel operativa SysAdmin */}
                                <div className="w-full bg-slate-100 dark:bg-slate-900 h-1 rounded-full overflow-hidden">
                                    <div className={`h-full transition-all duration-500 ${isOnline ? 'w-full bg-emerald-500/80' : 'w-1/12 bg-slate-300 dark:bg-slate-700'}`} />
                                </div>

                                {/* CONSOLA INFERIOR INTEGRADA DENTRO DE LA CAPA */}
                                <div className="bg-slate-50/60 dark:bg-[#1c2331]/40 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-3 flex items-center justify-between font-mono text-[11px]">
                                    <div className="flex items-center gap-1.5 text-slate-400">
                                        <HiTerminal className="w-3.5 h-3.5 text-slate-300 dark:text-slate-600" />
                                        <span className="text-[10px] font-black uppercase tracking-wider">Mapeo Lógico:</span>
                                    </div>
                                    <span className="font-bold text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 px-2.5 py-1 rounded-lg shadow-3xs tracking-wider">
                                        {amb.codigo}
                                    </span>
                                </div>
                            </div>
                        </div>
                    );
                })}

                {filteredAmbientes.length === 0 && (
                    <div className="col-span-full border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl p-16 text-center bg-white dark:bg-[#111827]">
                        <HiDatabase className="w-10 h-10 text-slate-300 dark:text-slate-700 mx-auto mb-3" />
                        <p className="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-tight">No se encontraron clústeres</p>
                        <p className="text-[11px] text-slate-400 mt-1">Aprovisiona un nuevo nodo de interconexión para inicializar el mapeo.</p>
                    </div>
                )}
            </div>

            {/* MODAL / FORMULARIO REDISEÑADO */}
            {isOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-md transition-opacity duration-300 animate-fade-in">
                    <div className="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden shadow-2xl relative animate-zoom-in">
                        
                        <button onClick={() => setIsOpen(false)} className="absolute top-5 right-5 p-1 rounded-full text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-white cursor-pointer transition-colors z-10">
                            <HiX className="w-4 h-4" />
                        </button>

                        <div className="px-7 py-5 bg-slate-50/50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-800/60 flex items-center gap-3">
                            <div className="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-inner">
                                <HiCube className="w-4 h-4" />
                            </div>
                            <div>
                                <h3 className="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    {editMode ? 'Propiedades del Nodo' : 'Aprovisionar Nodo de Red'}
                                </h3>
                                <p className="text-[11px] text-slate-400 dark:text-slate-500">Mapeo lógico de ruteo para el API Gateway federado.</p>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} className="p-7 space-y-4">
                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Código Único (Immutable)</label>
                                <input
                                    type="text" required disabled={editMode}
                                    value={data.codigo} onChange={e => setData('codigo', e.target.value.toUpperCase())}
                                    className="w-full text-xs font-mono font-bold bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-3 focus:outline-none focus:border-indigo-500 disabled:opacity-50 text-indigo-600 dark:text-indigo-400 tracking-wider shadow-inner"
                                    placeholder="EJ: UAT, PROD, DEV"
                                />
                                {errors.codigo && <p className="text-rose-500 text-[10px] mt-1 font-mono font-semibold">{errors.codigo}</p>}
                            </div>

                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Identificador del Nodo</label>
                                <input
                                    type="text" required
                                    value={data.nombre} onChange={e => setData('nombre', e.target.value)}
                                    className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3.5 py-3 focus:outline-none focus:border-indigo-500 text-slate-800 dark:text-slate-100 font-semibold"
                                    placeholder="Ej: Servidor Central Producción"
                                />
                            </div>

                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Descripción Operativa</label>
                                <textarea
                                    value={data.descripcion} onChange={e => setData('descripcion', e.target.value)}
                                    className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3.5 py-3 focus:outline-none focus:border-indigo-500 h-20 resize-none text-slate-700 dark:text-slate-300 leading-relaxed"
                                    placeholder="Especifique topologías o controles de proxy..."
                                />
                            </div>

                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Estado Operacional</label>
                                <div className="flex bg-slate-50 dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 p-1 rounded-xl">
                                    <button
                                        type="button"
                                        onClick={() => setData('estado', 1)}
                                        className={`flex-1 text-center text-[11px] font-black py-2 rounded-lg transition-all cursor-pointer ${
                                            data.estado === 1 
                                                ? 'bg-white dark:bg-[#111827] border border-slate-200/60 dark:border-slate-700 text-emerald-600 dark:text-emerald-400 shadow-2xs' 
                                                : 'text-slate-400 hover:text-slate-600'
                                        }`}
                                    >
                                        Online
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setData('estado', 0)}
                                        className={`flex-1 text-center text-[11px] font-black py-2 rounded-lg transition-all cursor-pointer ${
                                            data.estado === 0 
                                                ? 'bg-white dark:bg-[#111827] border border-slate-200/60 dark:border-slate-700 text-rose-600 dark:text-rose-400 shadow-2xs' 
                                                : 'text-slate-400 hover:text-slate-600'
                                        }`}
                                    >
                                        Offline
                                    </button>
                                </div>
                            </div>

                            <div className="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800/60 mt-2">
                                <button 
                                    type="button" 
                                    onClick={() => setIsOpen(false)} 
                                    className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800/60 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                >
                                    Cancelar
                                </button>
                                <button 
                                    type="submit" 
                                    disabled={processing} 
                                    className="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white rounded-xl text-xs font-black transition-all shadow-md active:scale-[0.98] cursor-pointer"
                                >
                                    Desplegar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}