import { useState, useEffect, useRef } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import Swal from 'sweetalert2'; // 🚀 Importación de SweetAlert2
import { 
    HiTerminal, HiRefresh, HiEye, HiCheck, HiExclamation, HiClock, 
    HiDatabase, HiSearch, HiTrendingUp, HiArrowsExpand,
    HiShieldCheck, HiLightningBolt, HiClipboardCopy, HiPlus, HiX
} from 'react-icons/hi';

export default function Rdapaciente({ auth, transmisiones, filters, configuraciones = [], globalMetrics = {}}) {
    const [search, setSearch] = useState(filters.search || '');
    const [estado, setEstado] = useState(filters.estado || '');
    const [activeLog, setActiveLog] = useState(null);
    const [loadingRows, setLoadingRows] = useState({});
    const [copiedSection, setCopiedSection] = useState(null);
    const [expandedSection, setExpandedSection] = useState(null);

    // ESTADOS PARA LA MODAL DE REGISTRO MANUAL
    const [isOpenModal, setIsOpenModal] = useState(false);
    const [formCitaId, setFormCitaId] = useState('');
    const [formConfigId, setFormConfigId] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // NUEVOS ESTADOS PARA BÚSQUEDA AVANZADA EN EL FRONTEND
    const [showAdvanced, setShowAdvanced] = useState(false);
    const [advCitaId, setAdvCitaId] = useState('');
    const [advDoc, setAdvDoc] = useState('');
    const [advNombre, setAdvNombre] = useState('');
    const [advGenero, setAdvGenero] = useState('');

    const isFirstRender = useRef(true);
    const [showExportMenu, setShowExportMenu] = useState(false);

    const ESTADOS = {
        PROCESADO: 'APPROVED',
        FALLIDO: 'REJECTED',
        PREPARADO: 'PENDING',
        CONECTIVIDAD: 'CONNECTIVITY_ERROR'
    };

    // 🎨 Mixin de configuración base para Toasts rápidos y limpios
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#1f2937',
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // 🚀 ASIGNA las métricas globales directas del backend
    const totalTx = globalMetrics.total || 0;
    const procesadas = globalMetrics.procesadas || 0;
    const fallidas = globalMetrics.fallidas || 0;
    const tasaExito = globalMetrics.tasaExito || 100;
    const enCola = globalMetrics.cola || 0;

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const delayDebounceFn = setTimeout(() => {
            router.get(
                route('ihce.transmision.rdapacientes'), 
                { search, estado }, 
                { preserveState: true, replace: true, preserveScroll: true }
            );
        }, 400);

        return () => clearTimeout(delayDebounceFn);
    }, [search, estado]);

    // 🔄 Transmisión inmediata con feedback interactivo de SweetAlert2
    const handleRowTransmission = async (transmisionId, citaId) => {
        setLoadingRows(prev => ({ ...prev, [transmisionId]: true }));
        try {
            const response = await axios.post(route('ihce.connector.transmision.immediate'), {
                transmision_id: transmisionId,
            });

            const isDark = document.documentElement.classList.contains('dark');

            if (response.data.success) {
                Toast.fire({
                    icon: 'success',
                    title: `Cita #${citaId} procesada con éxito.`
                });
                router.reload({ only: ['transmisiones','globalMetrics'] });
            } else {
                Toast.fire({
                    title: 'Transmisión Rechazada',
                    text: `El Gateway MinSalud retornó un rechazo. Estado del core: ${response.data.estado}`,
                    icon: 'warning',
                    background: isDark ? '#111827' : '#ffffff',
                    color: isDark ? '#f3f4f6' : '#111827',
                    confirmButtonColor: '#4f46e5',
                    customClass: { popup: 'rounded-2xl border border-slate-200 dark:border-slate-800' }
                });
                router.reload({ only: ['transmisiones','globalMetrics'] });
            }
        } catch (error) {
            const isDark = document.documentElement.classList.contains('dark');
            Toast.fire({
                title: 'Error de Conectividad',
                text: error.response?.data?.message || 'Error crítico de comunicación con el puente API de MinSalud.',
                icon: 'error',
                background: isDark ? '#111827' : '#ffffff',
                color: isDark ? '#f3f4f6' : '#111827',
                confirmButtonColor: '#e11d48',
                customClass: { popup: 'rounded-2xl border border-slate-200 dark:border-slate-800' }
            });
        } finally {
            setLoadingRows(prev => ({ ...prev, [transmisionId]: false }));
        }
    };

    // 🗳️ Validación del Envío Manual con SweetAlert2
    const handleSubmitManualTx = (e) => {
        e.preventDefault();
        const isDark = document.documentElement.classList.contains('dark');

        if (!formCitaId || !formConfigId) {
            Swal.fire({
                title: 'Campos Incompletos',
                text: 'Por favor complete todos los campos obligatorios.',
                icon: 'info',
                background: isDark ? '#111827' : '#ffffff',
                color: isDark ? '#f3f4f6' : '#111827',
                confirmButtonColor: '#4f46e5',
            });
            return;
        }

        setIsSubmitting(true);
        router.post(route('ihce.transmision.store'), {
            cita_id: formCitaId,
            configuracion_id: formConfigId,
            typerda: 'RDA_PATIENT'
        }, {
            onSuccess: () => {
                setIsOpenModal(false);
                setFormCitaId('');
                setFormConfigId('');
                Toast.fire({
                    icon: 'success',
                    title: 'Transmisión en cola registrada.'
                });
            },
            onError: (errors) => {
                Swal.fire({
                    title: 'Error de Validación',
                    text: Object.values(errors).join('\n') || 'Ocurrió un error al procesar la solicitud.',
                    icon: 'error',
                    background: isDark ? '#111827' : '#ffffff',
                    color: isDark ? '#f3f4f6' : '#111827',
                    confirmButtonColor: '#e11d48',
                });
            },
            onFinish: () => setIsSubmitting(false)
        });
    };

    // 📋 Copiado al Portapapeles usando los micro-toasts refinados
    const handleCopyToClipboard = (text, sectionName) => {
        if (!text) return;
        const stringified = typeof text === 'object' ? JSON.stringify(text, null, 2) : text;
        
        navigator.clipboard.writeText(stringified).then(() => {
            setCopiedSection(sectionName);
            Toast.fire({
                icon: 'success',
                title: `Copiado: ${sectionName}`
            });
            setTimeout(() => setCopiedSection(null), 2000);
        });
    };

    const closeMainMonitor = () => {
        setActiveLog(null);
        setExpandedSection(null);
    };
    
    // Función para extraer los datos del recurso Patient dentro del Bundle de forma limpia
    const getPatientFromSnapshot = (snapshot) => {
        if (!snapshot || !snapshot.entry) return null;
        
        const patientEntry = snapshot.entry.find(e => e.resource?.resourceType === 'Patient');
        if (!patientEntry || !patientEntry.resource) return null;

        const patient = patientEntry.resource;

        const nameObj = patient.name?.[0] || {};
        const givenName = Array.isArray(nameObj.given) ? nameObj.given.join(' ') : '';
        const familyName = nameObj.family || '';
        const nombreCompleto = `${givenName} ${familyName}`.trim() || 'Paciente sin Nombre';

        const identifierObj = patient.identifier?.find(i => i.use === 'official') || patient.identifier?.[0] || {};
        const tipoIdCoding = identifierObj.type?.coding?.find(c => c.system?.includes('ColombianPersonIdentifier')) || identifierObj.type?.coding?.[0] || {};
        const tipoId = tipoIdCoding.code || 'CC';
        const identificacion = identifierObj.value || '---';

        const etniaExt = patient.extension?.find(e => e.url?.includes('ExtensionPatientEthnicity'));
        const etnia = etniaExt?.valueCoding?.display || null;

        const generoBiolExt = patient._gender?.extension?.find(e => e.url?.includes('ExtensionBiologicalGender'));
        const generoBiol = generoBiolExt?.valueCoding?.display || (patient.gender === 'female' ? 'Mujer' : 'Hombre');

        return {
            nombre: nombreCompleto,
            tipoId,
            identificacion,
            genero: generoBiol,
            etnia,
            celular: patient.telecom?.[0]?.value || null
        };
    };

    // 🧠 MOTOR DE BÚSQUEDA AVANZADA EN MEMORIA
    const transmisionesFiltradas = transmisiones.data.filter((tx) => {
        const paciente = getPatientFromSnapshot(tx.source_snapshot_data);

        if (advCitaId && !tx.cita_id?.toString().includes(advCitaId)) return false;

        if (paciente) {
            if (advDoc) {
                const fullDoc = `${paciente.tipoId} ${paciente.identificacion}`.toLowerCase();
                if (!fullDoc.includes(advDoc.toLowerCase())) return false;
            }
            if (advNombre && !paciente.nombre.toLowerCase().includes(advNombre.toLowerCase())) return false;
            if (advGenero && (!paciente.genero || !paciente.genero.toLowerCase().includes(advGenero.toLowerCase()))) return false;
        } else if (advDoc || advNombre || advGenero) {
            return false;
        }

        return true;
    });

    // 📊 MOTOR DE EXPORTACIÓN CLIENT-SIDE
    const handleExport = (format) => {
        // Obtenemos la data mapeada y limpia de las transmisiones visibles
        const dataToExport = transmisionesFiltradas.map(tx => {
            const paciente = getPatientFromSnapshot(tx.source_snapshot_data);
            return {
                Cita_ID: tx.cita_id,
                Paciente_Nombre: paciente?.nombre || 'N/A',
                Paciente_Doc: paciente ? `${paciente.tipoId} ${paciente.identificacion}` : 'N/A',
                Genero: paciente?.genero || 'N/A',
                Estado: tx.estado,
                Codigo_VIDA: tx.vida_code || 'Sin Firma',
                Intentos: tx.retry_count,
                Ultimo_Intento: tx.last_attempt_at ? new Date(tx.last_attempt_at).toLocaleString() : 'N/A'
            };
        });

        if (dataToExport.length === 0) {
            Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros en el lote actual para exportar.', confirmButtonColor: '#4f46e5' });
            return;
        }

        let filename = `RDA_Report_${new Date().toISOString().split('T')[0]}`;
        
        if (format === 'csv' || format === 'excel') {
            const headers = Object.keys(dataToExport[0]).join(',');
            const rows = dataToExport.map(row => 
                Object.values(row).map(val => `"${String(val).replace(/"/g, '""')}"`).join(',')
            );
            const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + [headers, ...rows].join("\n");
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `${filename}.${format === 'csv' ? 'csv' : 'xls'}`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else if (format === 'txt') {
            let textContent = `==== REPORTES DE AUDITORÍA RDA PACIENTES ====\nGenerado: ${new Date().toLocaleString()}\n\n`;
            dataToExport.forEach(r => {
                textContent += `[Cita #${r.Cita_ID}] - ${r.Estado}\n Paciente: ${r.Paciente_Nombre} (${r.Paciente_Doc})\n Firma VIDA: ${r.Codigo_VIDA}\n Intentos: ${r.Intentos} | Último: ${r.Ultimo_Intento}\n---------------------------------------------\n`;
            });
            const blob = new Blob([textContent], { type: 'text/plain;charset=utf-8' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = `${filename}.txt`;
            link.click();
        }
        setShowExportMenu(false);
        Toast.fire({ icon: 'success', title: `Exportado exitosamente como ${format.toUpperCase()}` });
    };

    // 🔄 LÓGICA DE INTERRUPTOR DE ORDENACIÓN (SORTING)
    const handleSort = (columnName) => {
        const currentOrder = filters.sort_by === columnName ? filters.sort_order : 'desc';
        const nextOrder = currentOrder === 'asc' ? 'desc' : 'asc';

        router.get(
            route('ihce.transmision.rdapacientes'), 
            { ...filters, sort_by: columnName, sort_order: nextOrder }, 
            { preserveState: true, replace: true, preserveScroll: true }
        );
    };

    // Helper sutil para renderizar las flechas indicadoras de orden
    const renderSortIcon = (columnName) => {
        if (filters.sort_by !== columnName) return <span className="opacity-30 ml-1">↕</span>;
        return filters.sort_order === 'asc' ? <span className="text-indigo-500 ml-1">▲</span> : <span className="text-indigo-500 ml-1">▼</span>;
    };


    return (
        <AuthenticatedLayout user={auth.user} header="Visor de Transmisiones e Interoperabilidad: RDA Pacientes">
            <Head title="Monitor de Tráfico Clínico" />

            {/* PANEL DE METRICAS Y BOTÓN ACCIÓN SUPERIOR */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 max-w-7xl">
                <h2 className="text-xl font-black text-slate-800 dark:text-slate-100 tracking-tight">Consola de Ecosistema Digital</h2>
                <button 
                    onClick={() => setIsOpenModal(true)}
                    className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs px-4 py-2.5 rounded-xl cursor-pointer shadow-md transition-all active:scale-95 self-start md:self-auto"
                >
                    <HiPlus className="w-4 h-4"/> Forzar Transmisión Manual
                </button>
            </div>


            {/* METRICAS CARDS GLOBALES */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6 max-w-7xl">
                <div className="bg-white dark:bg-[#111827] border border-slate-200/50 dark:border-slate-800/60 p-5 rounded-2xl flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tasa de Éxito API</p>
                        <h3 className="text-2xl font-black text-slate-800 dark:text-slate-100 mt-1">{tasaExito}%</h3>
                    </div>
                    <div className="p-3 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500 rounded-xl"><HiTrendingUp className="w-5 h-5"/></div>
                </div>
                <div className="bg-white dark:bg-[#111827] border border-slate-200/50 dark:border-slate-800/60 p-5 rounded-2xl flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Homologadas MinSalud</p>
                        <h3 className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{procesadas} <span className="text-xs text-slate-400 font-normal">/ {totalTx}</span></h3>
                    </div>
                    <div className="p-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500 rounded-xl"><HiShieldCheck className="w-5 h-5"/></div>
                </div>
                <div className="bg-white dark:bg-[#111827] border border-slate-200/50 dark:border-slate-800/60 p-5 rounded-2xl flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Rechazos / Errores</p>
                        <h3 className="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{fallidas}</h3>
                    </div>
                    <div className="p-3 bg-rose-50 dark:bg-rose-950/40 text-rose-500 rounded-xl"><HiExclamation className="w-5 h-5"/></div>
                </div>
                <div className="bg-white dark:bg-[#111827] border border-slate-200/50 dark:border-slate-800/60 p-5 rounded-2xl flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Túnel de Cola Activo</p>
                        <h3 className="text-2xl font-black text-amber-500 mt-1">{enCola}</h3>
                    </div>
                    <div className="p-3 bg-amber-50 dark:bg-amber-950/40 text-amber-500 rounded-xl"><HiLightningBolt className="w-5 h-5"/></div>
                </div>
            </div>

            {/* CONTROL DE FILTROS PREDICTIVOS Y AVANZADOS */}
            <div className="bg-white dark:bg-[#111827] border border-slate-200/60 dark:border-slate-800/60 p-5 rounded-2xl mb-6 max-w-7xl shadow-2xs transition-all duration-300">
                <div className="flex flex-wrap gap-4 items-end">
                    <div className="flex-1 min-w-[280px]">
                        <label className="text-[10px] font-black text-slate-400 dark:text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1">
                            <HiSearch className="w-3.5 h-3.5" /> Buscar en la Base de Datos (Cita / Documento)
                        </label>
                        <input 
                            type="text" 
                            value={search} 
                            onChange={e => setSearch(e.target.value)} 
                            placeholder="Escribe para buscar y consultar al servidor..." 
                            className="w-full text-xs bg-slate-50/50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium" 
                        />
                    </div>
                    <div className="flex-1 sm:flex-none sm:w-72">
                        <label className="block text-[10px] font-black text-slate-400 dark:text-slate-400 uppercase tracking-widest mb-2">Estado Ecosistema</label>
                        <select 
                            value={estado} 
                            onChange={e => setEstado(e.target.value)} 
                            className="w-full text-xs bg-slate-50/50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-700 dark:text-slate-300 font-bold transition-all cursor-pointer"
                        >
                            <option value="">Todos los flujos</option>
                            <option value={ESTADOS.PROCESADO}>🟢 PROCESADO (Aceptado MinSalud)</option>
                            <option value={ESTADOS.FALLIDO}>🔴 FALLIDO (Error de Esquema)</option>
                            <option value={ESTADOS.PREPARADO}>🟡 PREPARADO (En Cola)</option>
                            <option value={ESTADOS.CONECTIVIDAD}>🟠 ERROR CONECTIVIDAD</option>
                        </select>
                    </div>
                    
                    {/* Botón de activación del panel avanzado */}
                    <button
                        type="button"
                        onClick={() => setShowAdvanced(!showAdvanced)}
                        className={`flex items-center gap-1.5 px-4 py-2.5 text-xs font-black rounded-xl border transition-all cursor-pointer h-[38px] ${
                            showAdvanced 
                                ? 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-900/50' 
                                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800/50'
                        }`}
                    >
                        <HiTerminal className="w-4 h-4"/> 
                        {showAdvanced ? 'Ocultar Filtros' : 'Búsqueda Avanzada'}
                    </button>
                </div>

                {/* PANEL EXPANDIBLE DE BÚSQUEDA AVANZADA TÉCNICA */}
                {showAdvanced && (
                    <div className="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/80 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 animate-in fade-in slide-in-from-top-2 duration-200">
                        <div>
                            <label className="block text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Filtrar por ID Cita</label>
                            <input 
                                type="text"
                                value={advCitaId}
                                onChange={e => setAdvCitaId(e.target.value)}
                                placeholder="Ej: 26995"
                                className="w-full text-xs bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-slate-700 dark:text-slate-300 font-mono"
                            />
                        </div>
                        <div>
                            <label className="block text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Filtrar por Documento</label>
                            <input 
                                type="text"
                                value={advDoc}
                                onChange={e => setAdvDoc(e.target.value)}
                                placeholder="Ej: CC 26995953"
                                className="w-full text-xs bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-slate-700 dark:text-slate-300 font-mono"
                            />
                        </div>
                        <div>
                            <label className="block text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Filtrar por Nombre Paciente</label>
                            <input 
                                type="text"
                                value={advNombre}
                                onChange={e => setAdvNombre(e.target.value)}
                                placeholder="Ej: Araceli"
                                className="w-full text-xs bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-slate-700 dark:text-slate-300"
                            />
                        </div>
                        <div>
                            <label className="block text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Filtrar por Genero</label>
                            <input 
                                type="text"
                                value={advGenero}
                                onChange={e => setAdvGenero(e.target.value)}
                                placeholder="Ej: Hombre"
                                className="w-full text-xs bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-slate-700 dark:text-slate-300"
                            />
                        </div>
                    </div>
                )}
            </div>

            {/* 🛠️ BARRA DE HERRAMIENTAS ADICIONALES DE LA TABLA */}
            <div className="max-w-7xl mb-3 flex items-center justify-between px-2">
                <div className="flex items-center gap-2">
                    {transmisionesFiltradas.length !== transmisiones.data.length && (
                        <span className="text-[10px] bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 px-2 py-1 rounded-md font-bold border border-indigo-100 dark:border-indigo-900/40 animate-pulse">
                            🔎 Filtro avanzado activo: Mostrando {transmisionesFiltradas.length} de {transmisiones.data.length} de esta página
                        </span>
                    )}
                </div>

                {/* MENÚ DESPLEGABLE DE EXPORTACIONES */}
                <div className="relative">
                    <button 
                        onClick={() => setShowExportMenu(!showExportMenu)}
                        className="flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-black bg-white border border-slate-200 dark:bg-[#111827] dark:border-slate-800 rounded-xl text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all cursor-pointer shadow-3xs"
                    >
                        <HiClipboardCopy className="w-3.5 h-3.5"/> Exportar Lote 📥
                    </button>

                    {showExportMenu && (
                        <>
                            <div className="fixed inset-0 z-10" onClick={() => setShowExportMenu(false)}></div>
                            <div className="absolute right-0 mt-2 w-40 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl z-20 overflow-hidden py-1 animate-in fade-in slide-in-from-top-1 duration-100">
                                <button onClick={() => handleExport('csv')} className="w-full text-left px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60 font-medium flex items-center gap-2">🟢 Formato CSV</button>
                                <button onClick={() => handleExport('excel')} className="w-full text-left px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60 font-medium flex items-center gap-2">📊 Formato Excel (.xls)</button>
                                <button onClick={() => handleExport('txt')} className="w-full text-left px-4 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60 font-medium flex items-center gap-2">📄 Texto Plano (.txt)</button>
                            </div>
                        </>
                    )}
                </div>
            </div>

            {/* TABLA DE AUDITORÍA AVANZADA */}
            <div className="bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/60 dark:border-slate-800/60 overflow-hidden max-w-7xl shadow-xs">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead>
                            <tr className="bg-slate-50/70 dark:bg-slate-900/60 border-b border-slate-200/60 dark:border-slate-800 select-none">
                                <th 
                                    onClick={() => handleSort('cita_id')} 
                                    className="px-5 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest cursor-pointer hover:text-indigo-500 transition-colors"
                                >
                                    Cita & Cronología {renderSortIcon('cita_id')}
                                </th>
                                <th 
                                    onClick={() => handleSort('estado')} 
                                    className="px-5 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest cursor-pointer hover:text-indigo-500 transition-colors"
                                >
                                    Estado Servidor {renderSortIcon('estado')}
                                </th>
                                <th 
                                    onClick={() => handleSort('vida_code')} 
                                    className="px-5 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest cursor-pointer hover:text-indigo-500 transition-colors"
                                >
                                    Código Aprobación (VIDA) {renderSortIcon('vida_code')}
                                </th>
                                <th 
                                    onClick={() => handleSort('retry_count')} 
                                    className="px-5 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest cursor-pointer hover:text-indigo-500 transition-colors"
                                >
                                    Túnel de Envíos {renderSortIcon('retry_count')}
                                </th>
                                <th className="px-5 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest text-right">
                                    Controles Clínicos Directos
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800/50 text-xs">
                            {transmisionesFiltradas.map((tx) => {      
                                const isRowLoading = !!loadingRows[tx.id];
                                const paciente = getPatientFromSnapshot(tx.source_snapshot_data);

                                return (
                                    <tr key={tx.id} className="hover:bg-slate-50/40 dark:hover:bg-slate-900/20 transition-colors group">
                                        {/* El contenido de tus TD (Cita, Estado, Código Vida, Reintentos, Acciones) se mantiene EXACTAMENTE IGUAL a tu diseño PRO original */}
                                        <td className="px-5 py-4">
                                            <div className="flex items-start gap-4">
                                                <div className="h-10 w-10 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center text-slate-400 group-hover:text-indigo-500 group-hover:border-indigo-500/30 transition-all shadow-3xs flex-shrink-0 mt-0.5">
                                                    <HiDatabase className="w-4 h-4 mb-0.5"/>
                                                </div>
                                                <div className="flex flex-col gap-1">
                                                    {paciente ? (
                                                        <>
                                                            <h4 className="font-black text-slate-800 dark:text-slate-100 text-sm tracking-tight capitalize leading-none group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                                {paciente.nombre.toLowerCase()}
                                                            </h4>
                                                            <div className="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                                                <span className="font-mono bg-slate-100 dark:bg-slate-800/60 px-1.5 py-0.5 rounded text-[10px] font-black border border-slate-200/40 dark:border-slate-700/40 text-slate-600 dark:text-slate-300">
                                                                    {paciente.tipoId} {paciente.identificacion}
                                                                </span>
                                                                {paciente.genero && (
                                                                    <>
                                                                        <span className="text-slate-300 dark:text-slate-700">•</span>
                                                                        <span className="text-[10px] bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400/90 font-bold px-1.5 py-0.2 rounded border border-amber-200/20 dark:border-amber-900/30">
                                                                            {paciente.genero}
                                                                        </span>
                                                                    </>
                                                                )}
                                                                <span className="text-slate-300 dark:text-slate-700">•</span>
                                                                <span>#{tx.cita_id}</span>
                                                            </div>
                                                        </>
                                                    ) : (
                                                        <p className="text-slate-400 dark:text-slate-500 italic text-[11px] pt-1">
                                                            Información demográfica no disponible
                                                        </p>
                                                    )}
                                                    <p className="text-[10px] text-slate-400 dark:text-slate-500 font-mono flex items-center gap-1 mt-0.5">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-700 inline-block"></span>
                                                        {tx.last_attempt_at ? new Date(tx.last_attempt_at).toLocaleString() : 'Pendiente primer intento'}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td className="px-5 py-4">
                                            <span className={`inline-flex items-center gap-1.5 text-[9px] font-mono font-black px-2.5 py-1 rounded-md uppercase tracking-wider border ${
                                                tx.estado === ESTADOS.PROCESADO 
                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200/40 dark:bg-emerald-950/20 dark:text-emerald-400' 
                                                    : tx.estado === ESTADOS.FALLIDO || tx.estado === ESTADOS.CONECTIVIDAD
                                                    ? 'bg-rose-50 text-rose-700 border-rose-200/40 dark:bg-rose-950/20 dark:text-rose-400' 
                                                    : 'bg-amber-50 text-amber-700 border-amber-200/40 dark:bg-amber-950/20 dark:text-amber-400'
                                            }`}>
                                                {tx.estado === ESTADOS.PROCESADO && <HiCheck className="w-3 h-3"/>}
                                                {(tx.estado === ESTADOS.FALLIDO || tx.estado === ESTADOS.CONECTIVIDAD) && <HiExclamation className="w-3 h-3"/>}
                                                {tx.estado === ESTADOS.PREPARADO && <HiClock className="w-3 h-3"/>}
                                                {tx.estado}
                                            </span>
                                        </td>

                                        <td className="px-5 py-4 font-mono font-bold text-xs text-slate-700 dark:text-slate-300">
                                            {tx.vida_code ? (
                                                <span className="bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-2.5 py-1 rounded-md text-slate-800 dark:text-slate-200 text-[11px] font-black">
                                                    {tx.vida_code}
                                                </span>
                                            ) : (
                                                <span className="text-slate-400 dark:text-slate-600 font-normal italic">Sin firma digital</span>
                                            )}
                                        </td>

                                        <td className="px-5 py-4 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                            <div className="flex items-center gap-1">
                                                <span className="font-black text-slate-700 dark:text-slate-300">{tx.retry_count}</span> <span className="text-slate-400 text-[10px]">/ 5 reintentos</span>
                                            </div>
                                        </td>

                                        <td className="px-5 py-4 text-right">
                                            <div className="flex justify-end items-center gap-2">
                                                <button 
                                                    disabled={isRowLoading || tx.estado === ESTADOS.PROCESADO}
                                                    onClick={() => handleRowTransmission(tx.id, tx.cita_id)}
                                                    className={`flex items-center gap-1.5 px-3 py-2 text-[11px] font-black rounded-xl transition-all shadow-3xs ${
                                                        tx.estado === ESTADOS.PROCESADO
                                                            ? 'bg-slate-100 text-slate-400 dark:bg-slate-900/60 dark:text-slate-600 cursor-not-allowed border border-transparent'
                                                            : 'bg-emerald-600 border border-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.97] cursor-pointer disabled:bg-slate-300'
                                                    }`}
                                                >
                                                    {isRowLoading ? <> <HiRefresh className="animate-spin w-3.5 h-3.5"/> Despachando... </> : <> <HiRefresh className="w-3.5 h-3.5" /> {tx.estado === ESTADOS.FALLIDO || tx.estado === ESTADOS.CONECTIVIDAD ? 'Reintentar' : 'Transmitir'} </>}
                                                </button>

                                                <button onClick={() => setActiveLog(tx)} className="flex items-center gap-1.5 px-3 py-2 text-[11px] font-black bg-white border border-slate-200 dark:bg-slate-900 dark:border-slate-800 rounded-xl text-slate-600 dark:text-slate-300 hover:text-indigo-500 dark:hover:text-indigo-400 hover:border-indigo-200 dark:hover:border-indigo-900 cursor-pointer shadow-3xs active:scale-[0.97] transition-all">
                                                    <HiEye className="w-3.5 h-3.5"/> Inspeccionar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                            {transmisionesFiltradas.length === 0 && (
                                <tr>
                                    <td colSpan="5" className="px-5 py-10 text-center text-slate-400 dark:text-slate-500 italic text-xs">
                                        No se encontraron transmisiones con los criterios avanzados ingresados en este lote.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* CONTROLES DE PAGINACIÓN */}
                <div className="bg-slate-50/50 dark:bg-slate-900/40 border-t border-slate-100 dark:border-slate-800/80 px-5 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Mostrando <span className="font-bold text-slate-800 dark:text-slate-200">{transmisiones.from || 0}</span> al <span className="font-bold text-slate-800 dark:text-slate-200">{transmisiones.to || 0}</span> de <span className="font-bold text-slate-800 dark:text-slate-200">{transmisiones.total}</span> registros en total
                    </div>

                    {transmisiones.links && transmisiones.links.length > 3 && (
                        <div className="flex flex-wrap items-center gap-1">
                            {transmisiones.links.map((link, index) => {
                                const key = link.label + index;
                                let cleanLabel = link.label;
                                if (cleanLabel.includes('Previous')) cleanLabel = '«';
                                if (cleanLabel.includes('Next')) cleanLabel = '»';

                                if (!link.url) {
                                    return <span key={key} className="px-3 py-2 text-xs rounded-xl bg-transparent text-slate-300 dark:text-slate-700 cursor-not-allowed select-none font-medium">{cleanLabel}</span>;
                                }

                                return (
                                    <button
                                        key={key}
                                        onClick={() => {
                                            router.get(link.url, filters, { preserveState: true, replace: true, preserveScroll: true });
                                        }}
                                        className={`px-3 py-2 text-xs font-bold rounded-xl transition-all border cursor-pointer ${link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-3xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'}`}
                                    >
                                        {cleanLabel}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            {/* MODAL FLOTANTE PARA INGRESAR NUEVA TRANSMISIÓN MANUAL */}
            {isOpenModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-xs transition-all p-4">
                    <div className="bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden animate-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 px-5 py-4">
                            <h3 className="text-sm font-black text-slate-800 dark:text-slate-100 uppercase tracking-wider flex items-center gap-2">
                                <HiDatabase className="text-indigo-500 w-4 h-4"/> Forzar Flujo Manual
                            </h3>
                            <button 
                                onClick={() => setIsOpenModal(false)} 
                                className="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 cursor-pointer transition-colors"
                            >
                                <HiX className="w-4 h-4" />
                            </button>
                        </div>
                        
                        <form onSubmit={handleSubmitManualTx} className="p-5 space-y-4">
                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-400 uppercase tracking-widest mb-1.5">Código Único de Cita (Core ID)</label>
                                <input 
                                    type="number" 
                                    required
                                    value={formCitaId}
                                    onChange={e => setFormCitaId(e.target.value)}
                                    placeholder="Ej: 14502"
                                    className="w-full text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-bold transition-all"
                                />
                            </div>

                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-400 uppercase tracking-widest mb-1.5">Configuración de Canal (Entidad / IPS)</label>
                                <select 
                                    required
                                    value={formConfigId} 
                                    onChange={e => setFormConfigId(e.target.value)} 
                                    className="w-full text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-700 dark:text-slate-300 font-bold transition-all cursor-pointer"
                                >
                                    <option value="">Seleccione una pasarela...</option>
                                    {configuraciones.map((config) => (
                                        <option key={config.id} value={config.id}>
                                            🏢 {config.nombre || `Configuración #${config.id}`} {config.nit ? `(NIT: ${config.nit})` : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                <button 
                                    type="button" 
                                    onClick={() => setIsOpenModal(false)}
                                    className="flex-1 bg-slate-50 hover:bg-slate-100 dark:bg-slate-900 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-black text-xs py-2.5 rounded-xl cursor-pointer transition-colors uppercase tracking-wider"
                                >
                                    Cancelar
                                </button>
                                <button 
                                    type="submit" 
                                    disabled={isSubmitting}
                                    className="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs py-2.5 rounded-xl cursor-pointer shadow-md transition-all active:scale-[0.98] disabled:bg-slate-300 uppercase tracking-wider flex items-center justify-center gap-1"
                                >
                                    {isSubmitting ? 'Encolando...' : 'Encolar Transmisión'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* MONITOR FLOTANTE TRADICIONAL */}
            {activeLog && (
                <div className="fixed inset-0 z-50 flex items-center justify-end bg-slate-950/60 backdrop-blur-xs transition-all">
                    <div className="bg-[#090d16] text-slate-200 border-l border-slate-800/60 w-full max-w-xl h-full p-6 shadow-2xl flex flex-col justify-between overflow-y-auto animate-in slide-in-from-right duration-250">
                        <div className="flex flex-col h-full">
                            <div className="flex items-center justify-between border-b border-slate-800/80 pb-4 mb-4">
                                <div>
                                    <h3 className="text-xs font-black uppercase tracking-widest text-slate-400 flex items-center gap-2 font-mono">
                                        <HiTerminal className="text-indigo-400 w-4 h-4" /> Trazabilidad Cita #{activeLog.cita_id}
                                    </h3>
                                    <p className="text-[10px] text-slate-500 font-mono mt-1">ID Registro Técnico: #{activeLog.id}</p>
                                </div>
                                <button onClick={closeMainMonitor} className="text-[10px] text-slate-400 hover:text-white font-black uppercase tracking-wider bg-slate-900 hover:bg-slate-800 border border-slate-800 px-3 py-1.5 rounded-lg transition-colors cursor-pointer">Cerrar</button>
                            </div>
                            
                            <div className="space-y-4 flex-1 overflow-y-auto pr-1 custom-scrollbar">
                                {/* 1. SECCIÓN INSTANTÁNEA CORE */}
                                <div>
                                    <div className="flex items-center justify-between mb-1.5">
                                        <h4 className="text-[9px] uppercase font-black text-indigo-400 tracking-widest font-mono flex items-center gap-1.5">
                                            <span className="w-1.5 h-1.5 rounded-full bg-indigo-400"></span> Instantánea Core (Snapshot)
                                        </h4>
                                        <div className="flex items-center gap-1.5">
                                            <button 
                                                onClick={() => handleCopyToClipboard(activeLog.source_snapshot_data, 'snapshot')}
                                                className="inline-flex items-center gap-1 text-[9px] font-mono font-bold bg-slate-900 hover:bg-indigo-950/50 border border-slate-800 text-slate-400 hover:text-indigo-400 px-2 py-1 rounded-md transition-all cursor-pointer"
                                            >
                                                <HiClipboardCopy className="w-3 h-3" />
                                                {copiedSection === 'snapshot' ? '¡Copiado!' : 'Copiar JSON'}
                                            </button>
                                            <button 
                                                onClick={() => setExpandedSection({ title: 'Instantánea Core (Snapshot)', data: activeLog.source_snapshot_data, color: 'text-indigo-400', badge: 'bg-indigo-400' })}
                                                className="inline-flex items-center gap-1 text-[9px] font-mono font-bold bg-slate-900 hover:bg-indigo-950/50 border border-slate-800 text-slate-400 hover:text-indigo-400 p-1.5 rounded-md transition-all cursor-pointer"
                                                title="Maximizar"
                                            >
                                                <HiArrowsExpand className="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>
                                    <pre className="text-[11px] font-mono bg-[#04070d] p-3.5 rounded-xl border border-slate-800/60 overflow-x-auto max-h-40 custom-scrollbar text-indigo-300/90 select-all shadow-inner">
                                        {JSON.stringify(activeLog.source_snapshot_data, null, 2)}
                                    </pre>
                                </div>

                                {/* 2. SECCIÓN ÚLTIMO JSON FHIR */}
                                <div>
                                    <div className="flex items-center justify-between mb-1.5">
                                        <h4 className="text-[9px] uppercase font-black text-emerald-400 tracking-widest font-mono flex items-center gap-1.5">
                                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Último JSON FHIR Despachado
                                        </h4>
                                        <div className="flex items-center gap-1.5">
                                            {activeLog.last_payload_sent && (
                                                <>
                                                    <button 
                                                        onClick={() => handleCopyToClipboard(activeLog.last_payload_sent, 'payload')}
                                                        className="inline-flex items-center gap-1 text-[9px] font-mono font-bold bg-slate-900 hover:bg-emerald-950/50 border border-slate-800 text-slate-400 hover:text-emerald-400 px-2 py-1 rounded-md transition-all cursor-pointer"
                                                    >
                                                        <HiClipboardCopy className="w-3 h-3" />
                                                        {copiedSection === 'payload' ? '¡Copiado!' : 'Copiar JSON'}
                                                    </button>
                                                    <button 
                                                        onClick={() => setExpandedSection({ title: 'Último JSON FHIR Despachado', data: activeLog.last_payload_sent, color: 'text-emerald-400', badge: 'bg-emerald-400' })}
                                                        className="inline-flex items-center gap-1 text-[9px] font-mono font-bold bg-slate-900 hover:bg-emerald-950/50 border border-slate-800 text-slate-400 hover:text-emerald-400 p-1.5 rounded-md transition-all cursor-pointer"
                                                        title="Maximizar"
                                                    >
                                                        <HiArrowsExpand className="w-3 h-3" />
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                    <pre className="text-[11px] font-mono bg-[#04070d] p-3.5 rounded-xl border border-slate-800/60 overflow-x-auto max-h-40 custom-scrollbar text-emerald-300/90 select-all shadow-inner">
                                        {activeLog.last_payload_sent ? JSON.stringify(activeLog.last_payload_sent, null, 2) : '// No transmitido aún'}
                                    </pre>
                                </div>

                                {/* 3. SECCIÓN RESPONSE LOG */}
                                <div>
                                    <div className="flex items-center justify-between mb-1.5">
                                        <h4 className="text-[9px] uppercase font-black text-rose-400 tracking-widest font-mono flex items-center gap-1.5">
                                            <span className="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Historial de Respuesta API (Response Log)
                                        </h4>
                                        <div className="flex items-center gap-1.5">
                                            {activeLog.response_log && (
                                                <>
                                                    <button 
                                                        onClick={() => handleCopyToClipboard(activeLog.response_log, 'response')}
                                                        className="inline-flex items-center gap-1 text-[9px] font-mono font-bold bg-slate-900 hover:bg-rose-950/50 border border-slate-800 text-slate-400 hover:text-rose-400 px-2 py-1 rounded-md transition-all cursor-pointer"
                                                    >
                                                        <HiClipboardCopy className="w-3 h-3" />
                                                        {copiedSection === 'response' ? '¡Copiado!' : 'Copiar JSON'}
                                                    </button>
                                                    <button 
                                                        onClick={() => setExpandedSection({ title: 'Historial de Respuesta API (Response Log)', data: activeLog.response_log, color: 'text-rose-400', badge: 'bg-rose-400' })}
                                                        className="inline-flex items-center gap-1 text-[9px] font-mono font-bold bg-slate-900 hover:bg-rose-950/50 border border-slate-800 text-slate-400 hover:text-rose-400 p-1.5 rounded-md transition-all cursor-pointer"
                                                        title="Maximizar"
                                                    >
                                                        <HiArrowsExpand className="w-3 h-3" />
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                    <pre className="text-[11px] font-mono bg-[#04070d] p-3.5 rounded-xl border border-slate-800/60 overflow-x-auto max-h-48 custom-scrollbar text-rose-300/90 select-all shadow-inner">
                                        {activeLog.response_log ? JSON.stringify(activeLog.response_log, null, 2) : '// Sin respuestas registradas'}
                                    </pre>
                                </div>
                            </div>
                        </div>
                        
                        <button onClick={closeMainMonitor} className="w-full bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white font-black text-xs py-3 rounded-xl mt-4 cursor-pointer uppercase tracking-wider transition-colors shadow-md">
                            Cerrar Monitor Técnico
                        </button>
                    </div>
                </div>
            )}

            {/* 🚀 EL INTERRUPTOR DEL MODAL EXPANDIDO SE SEPARA CON UN INDEX DE CAPA CRÍTICA (z-[90]) */}
            {expandedSection && (
                <div className="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-6 animate-in fade-in duration-150">
                    <div className="bg-[#090d16] border border-slate-800 rounded-2xl w-full max-w-5xl h-[85vh] flex flex-col p-6 shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-800/80 pb-4 mb-4">
                            <div className="flex items-center gap-2">
                                <span className={`w-2 h-2 rounded-full ${expandedSection.badge}`}></span>
                                <h3 className={`text-xs font-black uppercase tracking-widest font-mono ${expandedSection.color}`}>
                                    {expandedSection.title} (Vista Expandida)
                                </h3>
                            </div>
                            <button 
                                onClick={() => setExpandedSection(null)}
                                className="inline-flex items-center gap-1.5 text-[10px] font-mono font-black text-slate-400 hover:text-white uppercase tracking-wider bg-slate-900 hover:bg-slate-800 border border-slate-800 px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                            >
                                <HiX className="w-3.5 h-3.5" /> Cerrar Vista
                            </button>
                        </div>
                        
                        <div className="flex-1 overflow-auto bg-[#04070d] border border-slate-800/60 rounded-xl p-4 custom-scrollbar shadow-inner">
                            <pre className="text-xs font-mono text-slate-300 select-all leading-relaxed tab-size-2">
                                {JSON.stringify(expandedSection.data, null, 2)}
                            </pre>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}