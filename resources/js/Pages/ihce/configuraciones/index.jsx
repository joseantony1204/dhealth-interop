import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import axios from 'axios'; // Asegúrate de importar axios al inicio
import { 
    HiShieldCheck, 
    HiPlus, 
    HiPencilAlt, 
    HiLockClosed, 
    HiGlobe, 
    HiKey, 
    HiEye, 
    HiEyeOff, 
    HiX, 
    HiLibrary,
    HiSearch,
    HiDatabase,
    HiServer,
    HiTrash,
    HiCheck,
    HiDuplicate, // Ícono para simular "copiar url"
    HiRefresh, 
    HiCheckCircle, 
    HiExclamationCircle,
    HiTerminal,
    HiClipboardCopy
} from 'react-icons/hi';

export default function Index({ auth, configuraciones, ambientes, sedes }) {
    const [isOpen, setIsOpen] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [revealedSecrets, setRevealedSecrets] = useState({});
    const [testResults, setTestResults] = useState({}); 
    const [testingId, setTestingId] = useState(null);

    // Al lado de tus otros sub-estados de testing
    const [isLogModalOpen, setIsLogModalOpen] = useState(false);
    const [selectedLogPayload, setSelectedLogPayload] = useState(null);

    const openLogDetails = (payload) => {
        setSelectedLogPayload(payload);
        setIsLogModalOpen(true);
    };

    const [copied, setCopied] = useState(false);
    const handleCopyToken = (token) => {
        navigator.clipboard.writeText(token);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000); // Vuelve al estado normal tras 2 segundos
    };
    
    // Estado para controlar qué tarjeta está en modo "esperando confirmación de borrado"
    const [deletingId, setDeletingId] = useState(null);

    const { data, setData, post, put, errors, reset, processing } = useForm({
        id: '',
        sede_id: '',
        ambiente_id: '',
        endpoint_url: '',
        tenant_id: '',
        scope: '',
        apim_subs_key: '',
        client_id: '',
        client_secret: '',
    });

    const toggleSecretReveal = (id) => {
        setRevealedSecrets(prev => ({ ...prev, [id]: !prev[id] }));
    };

    const openModalCreate = () => {
        reset();
        setEditMode(false);
        setIsOpen(true);
    };

    const openModalEdit = (config) => {
        setData({
            id: config.id,
            sede_id: config.sede_id,
            ambiente_id: config.ambiente_id,
            endpoint_url: config.endpoint_url,
            tenant_id: config.tenant_id,
            scope: config.scope,
            apim_subs_key: config.apim_subs_key,
            client_id: config.client_id,
            client_secret: '', 
        });
        setEditMode(true);
        setIsOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editMode) {
            put(route('ihce.config.update', data.id), { onSuccess: () => setIsOpen(false) });
        } else {
            post(route('ihce.config.store'), { onSuccess: () => { reset(); setIsOpen(false); } });
        }
    };

    // Método de ejecución del borrado (Inertia HTTP Destroy)
    const executeDelete = (id) => {
        router.delete(route('ihce.config.destroy', id), {
            onSuccess: () => setDeletingId(null),
            onFinish: () => setDeletingId(null)
        });
    };

    // Filtrado predictivo inteligente
    const filteredConfiguraciones = configuraciones.filter((config) => {
        const sedeName = sedes.find(s => s.id === config.sede_id)?.nombre || '';
        const ambienteName = config.ambiente?.nombre || '';
        const search = searchTerm.toLowerCase();
        
        return (
            sedeName.toLowerCase().includes(search) ||
            ambienteName.toLowerCase().includes(search) ||
            config.endpoint_url.toLowerCase().includes(search) ||
            config.client_id.toLowerCase().includes(search)
        );
    });

    const handleTestConnection = async (id) => {
        setTestingId(id);
        setTestResults(prev => ({ ...prev, [id]: null }));
        
        try {
            // 🚀 Apuntamos a la nueva ruta global del conector unificado
            const response = await axios.post(route('ihce.connector.test', id));
            setTestResults(prev => ({ ...prev, [id]: response.data }));
        } catch (error) {
            setTestResults(prev => ({ 
                ...prev, 
                [id]: { success: false, message: 'Fallo crítico de comunicación interna con el servidor.' } 
            }));
        } finally {
            setTestingId(null);
        }
    };

    return (
        <AuthenticatedLayout user={auth.user} header="Panel de Control de Interoperabilidad">
            <Head title="Core IHCE - Pasarelas de Sede" />

            {/* 1. SECCIÓN: KPI METRICS BOARD */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 max-w-7xl">
                <div className="bg-white dark:bg-[#111827] border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black tracking-widest text-slate-400 dark:text-slate-500 uppercase">Sedes Federadas</p>
                        <p className="text-xl font-black text-slate-800 dark:text-slate-100 mt-0.5">{configuraciones.length}</p>
                    </div>
                    <div className="h-8 w-8 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 flex items-center justify-center text-slate-400">
                        <HiLibrary className="w-4 h-4" />
                    </div>
                </div>

                <div className="bg-white dark:bg-[#111827] border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black tracking-widest text-slate-400 dark:text-slate-500 uppercase">Nodos de Red</p>
                        <p className="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-0.5">{ambientes.length}</p>
                    </div>
                    <div className="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100/50 dark:border-indigo-900/30 flex items-center justify-center text-indigo-500">
                        <HiServer className="w-4 h-4" />
                    </div>
                </div>

                <div className="bg-white dark:bg-[#111827] border border-slate-200/60 dark:border-slate-800/60 rounded-2xl p-4 flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black tracking-widest text-slate-400 dark:text-slate-500 uppercase">Cifrado de Bóveda</p>
                        <p className="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">AES-256</p>
                    </div>
                    <div className="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100/50 dark:border-emerald-900/30 flex items-center justify-center text-emerald-500">
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
                        placeholder="Buscar por sede, URL base, ClientID o entorno..."
                        className="w-full text-xs bg-white dark:bg-[#111827] border border-slate-200/80 dark:border-slate-800/80 rounded-xl pl-9 pr-4 py-2.5 focus:outline-none focus:border-indigo-500 dark:focus:border-indigo-500 text-slate-800 dark:text-slate-200 font-medium placeholder-slate-400 shadow-2xs"
                    />
                </div>

                <button
                    onClick={openModalCreate}
                    className="flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-black text-xs px-4 py-2.5 rounded-xl transition-all shadow-md active:scale-[0.98] cursor-pointer whitespace-nowrap"
                >
                    <HiPlus className="w-4 h-4" /> Aprovisionar Sede
                </button>
            </div>

            {/* 3. SECCIÓN: GRILLA DE PANELES DE SEDE (DISEÑO VISUAL IMPACT CON CONTROL DE ESTADO) */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-7xl">
                {filteredConfiguraciones.map((config) => {
                    const sedeName = sedes.find(s => s.id === config.sede_id)?.nombre || `Sede ID: ${config.sede_id}`;
                    const isSecretVisible = revealedSecrets[config.id];
                    const isConfirmingDelete = deletingId === config.id;
                    
                    // 🚀 Evaluamos si el ambiente está activo o fuera de servicio
                    const isAmbienteActivo = config.ambiente?.estado === 1;

                    return (
                        <div 
                            key={config.id} 
                            className={`bg-white dark:bg-[#111827] border rounded-3xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 relative overflow-hidden group ${
                                isConfirmingDelete 
                                    ? 'border-rose-300 dark:border-rose-900 ring-2 ring-rose-200 dark:ring-rose-950' 
                                    : 'border-slate-100 dark:border-slate-800/80 hover:border-slate-200 dark:hover:border-slate-700'
                            } ${!isAmbienteActivo ? 'bg-slate-50/40 dark:bg-slate-950/20 grayscale-30' : ''}`}
                        >
                            {/* Gradiente de fondo sutil - Cambia de color si el ambiente está inactivo */}
                            <div className={`absolute -top-24 -right-24 h-48 w-48 rounded-full blur-3xl opacity-80 group-hover:opacity-100 transition-opacity ${
                                isAmbienteActivo 
                                    ? 'bg-indigo-50/50 dark:bg-indigo-950/20' 
                                    : 'bg-rose-50/30 dark:bg-rose-950/10'
                            }`} />

                            {/* CABECERA DE LA TARJETA: SEDE Y ACCIONES */}
                            <div className="flex items-start justify-between gap-4 mb-6 relative z-10">
                                <div className="flex items-center gap-4">
                                    <div className={`h-12 w-12 rounded-2xl border flex items-center justify-center shadow-inner transition-colors ${
                                        isAmbienteActivo 
                                            ? 'bg-indigo-50 border-indigo-100 text-indigo-500 dark:bg-indigo-950/50 dark:border-indigo-900 dark:text-indigo-400' 
                                            : 'bg-slate-100 border-slate-200 text-slate-400 dark:bg-slate-900 dark:border-slate-800'
                                    }`}>
                                        <HiLibrary className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h3 className={`text-base font-black tracking-tight uppercase ${
                                            isAmbienteActivo ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400 line-through'
                                        }`}>
                                            {sedeName}
                                        </h3>
                                        <div className="flex items-center gap-2 mt-1">
                                            <div className={`h-2 w-2 rounded-full ${isAmbienteActivo ? 'bg-emerald-500 animate-pulse' : 'bg-rose-400'}`} />
                                            <span className={`font-mono text-[10px] font-black tracking-widest px-2.5 py-0.5 rounded-full uppercase border ${
                                                isAmbienteActivo 
                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200/50 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/40' 
                                                    : 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/40'
                                            }`}>
                                                {isAmbienteActivo ? (config.ambiente?.nombre || 'CLUSTER') : 'CLUSTER FUERA DE LÍNEA'}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {/* PANEL DE ACCIONES ESQUINA SUPERIOR (REACTIVO AL ESTADO DEL AMBIENTE) */}
                                <div className="flex items-center gap-1.5">
                                    {isConfirmingDelete ? (
                                        <div className="flex items-center gap-1 bg-white dark:bg-slate-900 p-1 rounded-xl border border-rose-200 dark:border-rose-900 shadow-2xs animate-fade-in">
                                            <button 
                                                onClick={() => executeDelete(config.id)}
                                                className="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 rounded-lg cursor-pointer transition-colors"
                                            >
                                                <HiCheck className="w-3.5 h-3.5" />
                                            </button>
                                            <button 
                                                onClick={() => setDeletingId(null)}
                                                className="p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg cursor-pointer transition-colors"
                                            >
                                                <HiX className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    ) : (
                                        <>
                                            {/* 🚀 BOTÓN EDITAR: BLOQUEADO SI EL AMBIENTE ESTÁ INACTIVO */}
                                            <button 
                                                disabled={!isAmbienteActivo}
                                                onClick={() => openModalEdit(config)} 
                                                className={`p-2 rounded-xl border transition-all shadow-2xs ${
                                                    isAmbienteActivo
                                                        ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-400 hover:text-indigo-500 dark:hover:text-indigo-400 hover:border-indigo-200 dark:hover:border-indigo-900 cursor-pointer'
                                                        : 'bg-slate-100 dark:bg-slate-950 border-slate-200/60 dark:border-slate-900 text-slate-300 dark:text-slate-700 cursor-not-allowed'
                                                }`}
                                                title={isAmbienteActivo ? "Editar Configuración" : "No se puede editar una configuración con clúster inactivo"}
                                            >
                                                <HiPencilAlt className="w-4 h-4" />
                                            </button>

                                            {/* 🚀 BOTÓN ELIMINAR: ATENUADO PERO DISPONIBLE (O BLOQUEADO SEGÚN TU PREFERENCIA) */}
                                            <button 
                                                disabled={!isAmbienteActivo} // 👈 Quita este 'disabled' si quieres permitir eliminar aunque esté inactivo
                                                onClick={() => setDeletingId(config.id)} 
                                                className={`p-2 rounded-xl border transition-all shadow-2xs ${
                                                    isAmbienteActivo
                                                        ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:border-rose-200 dark:hover:border-rose-900/60 cursor-pointer'
                                                        : 'bg-slate-100 dark:bg-slate-950 border-slate-200/60 dark:border-slate-900 text-slate-300 dark:text-slate-700 cursor-not-allowed'
                                                }`}
                                                title={isAmbienteActivo ? "Eliminar Bóveda" : "No se puede eliminar con clúster inactivo"}
                                            >
                                                <HiTrash className="w-4 h-4" />
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>

                            {/* CUERPO DE LA TARJETA: ENLACE Y DATOS TÉCNICOS */}
                            <div className={`space-y-5 relative z-10 ${!isAmbienteActivo ? 'opacity-65' : ''}`}>
                                {/* Visualización del Túnel de Conectividad */}
                                <div className="space-y-1.5">
                                    <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Punto de Enlace de Datos (FHIR Base URL)</label>
                                    <div className="flex items-center gap-2 bg-slate-50 dark:bg-slate-900 border border-slate-200/70 dark:border-slate-800 rounded-xl px-3 py-2.5">
                                        <HiGlobe className="w-4 h-4 text-slate-400 shrink-0" />
                                        <p className="text-xs font-mono font-bold text-slate-700 dark:text-slate-200 truncate select-all flex-1">
                                            {config.endpoint_url}
                                        </p>
                                        <HiDuplicate className="w-4 h-4 text-slate-300 dark:text-slate-600 cursor-pointer hover:text-indigo-500" title="Simular copiar URL"/>
                                    </div>
                                </div>

                                {/* CAPA DE SEGURIDAD PROTEGIDA (PANEL INFERIOR) */}
                                <div className="bg-slate-50/50 dark:bg-[#1c2331]/50 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-4">
                                    <div className="flex items-center justify-between mb-3.5 border-b border-slate-100 dark:border-slate-800 pb-3">
                                        <div className="flex items-center gap-2">
                                            <HiLockClosed className={`w-4 h-4 ${isAmbienteActivo ? 'text-emerald-500' : 'text-slate-400'}`} />
                                            <span className="text-[11px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-300">Capa de Seguridad OAuth2 (E2EE)</span>
                                        </div>
                                        
                                        {/* 🚀 BOTÓN DE TEST PRO DE CONECTIVIDAD (MUTADO/DESACTIVADO SI EL AMBIENTE ESTÁ INACTIVO) */}
                                        <button
                                            type="button"
                                            disabled={testingId === config.id || !isAmbienteActivo}
                                            onClick={() => handleTestConnection(config.id)}
                                            className={`flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-mono font-bold uppercase tracking-wider border transition-all ${
                                                !isAmbienteActivo
                                                    ? 'bg-slate-100 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-400 cursor-not-allowed select-none'
                                                    : testingId === config.id
                                                    ? 'bg-slate-150 text-slate-400 dark:bg-slate-800 border-slate-200'
                                                    : 'bg-white hover:bg-indigo-50 text-indigo-600 border-indigo-200/60 dark:bg-slate-900 dark:hover:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900/60 shadow-3xs cursor-pointer'
                                            }`}
                                            title={!isAmbienteActivo ? 'No se puede testear un nodo apagado' : 'Probar conexión'}
                                        >
                                            <HiRefresh className={`w-3 h-3 ${testingId === config.id ? 'animate-spin' : ''}`} />
                                            {!isAmbienteActivo ? 'Nodo Inactivo' : testingId === config.id ? 'Verificando...' : 'Test Enlace'}
                                        </button>
                                    </div>

                                    {/* RECUADRO DE RESPUESTA DINÁMICA DEL TEST */}
                                    {testResults[config.id] && isAmbienteActivo && (
                                        <div className={`mb-3 p-3 rounded-2xl border flex flex-col gap-2 font-mono text-[10px] animate-fade-in ${
                                            testResults[config.id].success 
                                                ? 'bg-emerald-50/60 border-emerald-200/60 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-900/40 dark:text-emerald-400' 
                                                : 'bg-rose-50/60 border-rose-200/60 text-rose-800 dark:bg-rose-950/20 dark:border-rose-900/40 dark:text-rose-400'
                                        }`}>
                                            <div className="flex items-start gap-2">
                                                {testResults[config.id].success ? (
                                                    <HiCheckCircle className="w-4 h-4 shrink-0 mt-0.5" />
                                                ) : (
                                                    <HiExclamationCircle className="w-4 h-4 shrink-0 mt-0.5" />
                                                )}
                                                <div className="flex-1">
                                                    <span className="font-black">{testResults[config.id].success ? 'ONLINE:' : 'ERROR DE ENLACE:'}</span>
                                                    <p className="mt-0.5 leading-relaxed break-all">{testResults[config.id].message}</p>
                                                </div>
                                            </div>

                                            {testResults[config.id].success && testResults[config.id].payload && (
                                                <div className="flex justify-end border-t border-emerald-200/40 dark:border-emerald-900/30 pt-2 mt-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => openLogDetails(testResults[config.id].payload)}
                                                        className="flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500/20 dark:hover:bg-emerald-500/30 text-white dark:text-emerald-400 font-sans font-bold text-[9px] uppercase tracking-wider px-2 py-1 rounded-md transition-all cursor-pointer shadow-3xs"
                                                    >
                                                        <HiTerminal className="w-3 h-3" /> Inspeccionar Token & Meta
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* DATOS DE CREDENCIALES */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 font-mono text-[11px]">
                                        <div className="flex items-center justify-between bg-white dark:bg-slate-900 px-2.5 py-1.5 rounded-lg border border-slate-100 dark:border-slate-800/60">
                                            <span className="text-slate-400">Client ID:</span>
                                            <span className="text-slate-800 dark:text-slate-100 font-bold">
                                                {isSecretVisible ? (config.client_id || 'N/A') : '••••••••••••••••••••••••'}
                                            </span>
                                        </div>
                                        
                                        <div className="flex items-center justify-between bg-white dark:bg-slate-900 px-2.5 py-1.5 rounded-lg border border-slate-100 dark:border-slate-800/60">
                                            <span className="text-slate-400">Tenant ID:</span>
                                            <span className="text-slate-800 dark:text-slate-100 font-bold">
                                                {isSecretVisible ? (config.tenant_id || 'N/A') : '••••••••••••••••••••••••'}
                                            </span>
                                        </div>

                                        <div className="flex items-center justify-between bg-white dark:bg-slate-900 px-2.5 py-1.5 rounded-lg border border-slate-100 dark:border-slate-800/60 sm:col-span-2 relative">
                                            <span className="text-slate-400">APIM Key:</span>
                                            <span className="text-slate-700 dark:text-slate-300 font-semibold truncate pr-8">
                                                {isSecretVisible ? config.apim_subs_key : `${config.apim_subs_key.substring(0, 12)}...`}
                                            </span>
                                            <button 
                                                onClick={() => toggleSecretReveal(config.id)}
                                                className="absolute right-2 text-slate-400 hover:text-indigo-500 focus:outline-none cursor-pointer"
                                            >
                                                {isSecretVisible ? <HiEyeOff className="w-3.5 h-3.5" /> : <HiEye className="w-3.5 h-3.5" />}
                                            </button>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    );
                })}

                {filteredConfiguraciones.length === 0 && (
                    <div className="col-span-full border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl p-16 text-center animate-fade-in bg-white dark:bg-[#111827]">
                        <HiDatabase className="w-10 h-10 text-slate-300 dark:text-slate-700 mx-auto mb-3" />
                        <p className="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-tight">Bóveda de Credenciales Vacía</p>
                        <p className="text-[11px] text-slate-400 mt-1">Modifica los términos de búsqueda o aprovisiona el primer endpoint de sede.</p>
                    </div>
                )}
            </div>

            {/* MODAL / FORMULARIO */}
            {isOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-md transition-opacity duration-300 animate-fade-in">
                    <div className="bg-white dark:bg-[#111827] rounded-3xl border border-slate-200 dark:border-slate-800 w-full max-w-xl overflow-hidden shadow-2xl relative max-h-[90vh] flex flex-col animate-zoom-in">
                        
                        <button onClick={() => setIsOpen(false)} className="absolute top-5 right-5 p-1 rounded-full text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-white cursor-pointer transition-colors z-10">
                            <HiX className="w-4 h-4" />
                        </button>

                        <div className="px-7 py-5 bg-slate-50/50 dark:bg-slate-900/30 border-b border-slate-100 dark:border-slate-800/60 flex items-center gap-3 shrink-0">
                            <div className="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-inner">
                                <HiKey className="w-4 h-4" />
                            </div>
                            <div>
                                <h3 className="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    {editMode ? 'Modificar Bóveda de Sede' : 'Aprovisionar Infraestructura de Sede'}
                                </h3>
                                <p className="text-[11px] text-slate-400 dark:text-slate-500">Inyección de llaves criptográficas OAuth2 y tokens APIM federados.</p>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} className="p-7 space-y-4 overflow-y-auto flex-1 grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
                            <div className="sm:col-span-2 bg-slate-50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/50 grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Sede Organizativa Core</label>
                                    <select 
                                        disabled={editMode} 
                                        value={data.sede_id} 
                                        onChange={e => setData('sede_id', e.target.value)} 
                                        className="w-full text-xs bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 dark:text-slate-200 shadow-inner"
                                        required
                                    >
                                        <option value="">Seleccione Sede...</option>
                                        {sedes.map(s => <option key={s.id} value={s.id}>{s.nombre}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Entorno de Red Lógico</label>
                                    <select 
                                        disabled={editMode} 
                                        value={data.ambiente_id} 
                                        onChange={e => setData('ambiente_id', e.target.value)} 
                                        className="w-full text-xs bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500 font-semibold text-slate-700 dark:text-slate-200 shadow-inner"
                                        required
                                    >
                                        <option value="">Seleccione Entorno...</option>
                                        {ambientes.map(a => <option key={a.id} value={a.id}>{a.nombre}</option>)}
                                    </select>
                                </div>
                            </div>

                            <div className="sm:col-span-2">
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">URL Base del Servidor (FHIR Endpoint)</label>
                                <input 
                                    type="url" 
                                    required 
                                    value={data.endpoint_url} 
                                    onChange={e => setData('endpoint_url', e.target.value)} 
                                    className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3.5 py-3 focus:outline-none focus:border-indigo-500 font-mono text-slate-800 dark:text-slate-200" 
                                    placeholder="https://api.minsalud.gov/fhir/v1" 
                                />
                            </div>

                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Tenant ID (Directorio B2C)</label>
                                <input 
                                    type="text" 
                                    required 
                                    value={data.tenant_id} 
                                    onChange={e => setData('tenant_id', e.target.value)} 
                                    className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3.5 py-3 focus:outline-none focus:border-indigo-500 font-mono text-slate-800 dark:text-slate-200" 
                                    placeholder="common o GUID único"
                                />
                            </div>

                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Scope Federado Requerido</label>
                                <input 
                                    type="text" 
                                    required 
                                    value={data.scope} 
                                    onChange={e => setData('scope', e.target.value)} 
                                    className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3.5 py-3 focus:outline-none focus:border-indigo-500 font-mono text-slate-800 dark:text-slate-200" 
                                    placeholder="fhirUser openid roles" 
                                />
                            </div>

                            <div className="sm:col-span-2">
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">APIM Subscription Key Gateway</label>
                                <input 
                                    type="text" 
                                    required 
                                    value={data.apim_subs_key} 
                                    onChange={e => setData('apim_subs_key', e.target.value)} 
                                    className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3.5 py-3 focus:outline-none focus:border-indigo-500 font-mono tracking-wider text-slate-800 dark:text-slate-200" 
                                    placeholder="Ocp-Apim-Subscription-Key..."
                                />
                            </div>

                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Client ID (Application ID GUID)</label>
                                <input 
                                    type="text" 
                                    required 
                                    value={data.client_id} 
                                    onChange={e => setData('client_id', e.target.value)} 
                                    className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3.5 py-3 focus:outline-none focus:border-indigo-500 font-mono text-slate-800 dark:text-slate-200" 
                                    placeholder="00000000-0000-0000-0000-000000000000"
                                />
                            </div>

                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Client Secret (Llave de Aplicación)</label>
                                <input 
                                    type="password" 
                                    placeholder={editMode ? '•••••••••••• (Inalterado)' : 'Llave criptográfica secreta'} 
                                    required={!editMode} 
                                    value={data.client_secret} 
                                    onChange={e => setData('client_secret', e.target.value)} 
                                    className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3.5 py-3 focus:outline-none focus:border-indigo-500 text-slate-800 dark:text-slate-200" 
                                />
                            </div>

                            <div className="sm:col-span-2 flex justify-end gap-2 pt-5 border-t border-slate-100 dark:border-slate-800 mt-2 shrink-0">
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
                                    Firmar e Inyectar Parámetros
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
            {/* MODAL PRO+: INSPECTOR DE METADATOS OAUTH2 / TELEMETRÍA */}
            {isLogModalOpen && selectedLogPayload && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md transition-opacity duration-300 animate-fade-in">
                    <div className="bg-slate-900 border border-slate-800 w-full max-w-xl rounded-2xl overflow-hidden shadow-2xl relative flex flex-col max-h-[85vh] animate-zoom-in text-slate-100">
                        
                        {/* Cabecera Principal */}
                        <div className="px-6 py-4 border-b border-slate-800 bg-slate-950 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <HiTerminal className="w-4 h-4 text-emerald-400 animate-pulse" />
                                <h3 className="text-xs font-black font-mono uppercase tracking-wider text-slate-300">
                                    Bóveda Temporal - SysLog Telemetry
                                </h3>
                            </div>
                            <button 
                                onClick={() => setIsLogModalOpen(false)} 
                                className="text-slate-500 hover:text-white p-1 rounded-full hover:bg-slate-800 transition-colors cursor-pointer"
                            >
                                <HiX className="w-4 h-4" />
                            </button>
                        </div>

                        {/* Panel de Datos Estilo Consola */}
                        <div className="p-6 space-y-4 overflow-y-auto flex-1 font-mono text-[11px] bg-[#0b0f19]">
                            
                            {/* Grid de tiempos de expiración */}
                            <div className="grid grid-cols-3 gap-3">
                                <div className="bg-slate-900/60 border border-slate-800 p-3 rounded-xl">
                                    <span className="block text-[9px] text-slate-500 uppercase tracking-widest font-sans font-bold">Tipo Token</span>
                                    <span className="text-emerald-400 font-bold mt-1 block">{selectedLogPayload.token_type}</span>
                                </div>
                                <div className="bg-slate-900/60 border border-slate-800 p-3 rounded-xl">
                                    <span className="block text-[9px] text-slate-500 uppercase tracking-widest font-sans font-bold">Expiración (Seg)</span>
                                    <span className="text-amber-400 font-bold mt-1 block">{selectedLogPayload.expires_in}s</span>
                                </div>
                                <div className="bg-slate-900/60 border border-slate-800 p-3 rounded-xl">
                                    <span className="block text-[9px] text-slate-500 uppercase tracking-widest font-sans font-bold">Ext Expiración</span>
                                    <span className="text-indigo-400 font-bold mt-1 block">{selectedLogPayload.ext_expires_in}s</span>
                                </div>
                            </div>

                            {/* Bloque del JWT Access Token con Botón de Copiado */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between px-1">
                                    <label className="text-[9px] text-slate-500 uppercase tracking-widest font-sans font-black">
                                        access_token
                                    </label>
                                    
                                    {/* 🚀 BOTÓN INTERACTIVO DE COPIADO */}
                                    <button
                                        type="button"
                                        onClick={() => handleCopyToken(selectedLogPayload.access_token)}
                                        className={`flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-sans font-bold transition-all border cursor-pointer ${
                                            copied
                                                ? 'bg-emerald-950/40 border-emerald-500/40 text-emerald-400'
                                                : 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white hover:border-slate-700 shadow-2xs'
                                        }`}
                                        title="Copiar token al portapapeles"
                                    >
                                        {copied ? (
                                            <>
                                                <HiCheck className="w-3.5 h-3.5 text-emerald-400 animate-scale-in" />
                                                <span>¡Copiado!</span>
                                            </>
                                        ) : (
                                            <>
                                                <HiClipboardCopy className="w-3.5 h-3.5" />
                                                <span>Copiar Access Token</span>
                                            </>
                                        )}
                                    </button>
                                </div>
                                
                                <div className="relative">
                                    <textarea
                                        readOnly
                                        value={selectedLogPayload.access_token}
                                        className="w-full h-32 bg-slate-950 border border-slate-800 rounded-xl p-3 text-slate-400 focus:outline-none text-[10px] leading-relaxed font-mono select-all resize-none shadow-inner"
                                    />
                                    <div className="absolute bottom-3 right-3 text-[9px] font-sans font-bold bg-slate-800 px-2 py-0.5 rounded text-slate-400 pointer-events-none uppercase border border-slate-700/60">
                                        AES-Bearer
                                    </div>
                                </div>
                            </div>

                            <div className="p-3 bg-slate-900/30 border border-slate-800/60 rounded-xl flex items-start gap-2 text-[10px] text-slate-400 font-sans leading-relaxed">
                                <HiShieldCheck className="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                                <p>Este token es emitido dinámicamente por la instancia OAuth2 de Microsoft Azure. Su uso es exclusivo para la pasarela de interoperabilidad en curso y caducará de manera automática en la base de tiempos del servidor central.</p>
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="px-6 py-3 border-t border-slate-800 bg-slate-950 flex justify-end">
                            <button
                                type="button"
                                onClick={() => setIsLogModalOpen(false)}
                                className="px-4 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-[11px] font-sans font-bold transition-all cursor-pointer"
                            >
                                Cerrar Consola
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}