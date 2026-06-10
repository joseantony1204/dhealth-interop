import React, { useState, useMemo } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { 
    HiSearch, 
    HiUsers, 
    HiTerminal, 
    HiClipboardList, 
    HiCalendar, 
    HiIdentification,
    HiSparkles,
    HiOutlineDocumentText,
    HiClock,
    HiRefresh,
    HiEye,
    HiChevronDown,
    HiChevronUp,
    HiBookmark
} from 'react-icons/hi';
import { 
    FaBriefcaseMedical, 
    FaHeartbeat, 
    FaAllergies, 
    FaPills, 
    FaStethoscope, 
    FaSyringe,
    FaNotesMedical,
    FaUserMd
} from 'react-icons/fa';

export default function Index({ auth = {} }) {
    // 1. ESTADOS DEL FORMULARIO DE BÚSQUEDA
    const [tipoDocumento, setTipoDocumento] = useState('CC');
    const [documento, setDocumento] = useState('');
    const [fechaDesde, setFechaDesde] = useState('');
    const [activarFiltro, setActivarFiltro] = useState(false);

    // 2. ESTADOS OPERATIVOS DE CONTROL
    const [loading, setLoading] = useState(false);
    const [responseResult, setResponseResult] = useState(null);
    const [searchTerm, setSearchTerm] = useState('');

    // 3. SECCIONES ABIERTAS DEL ACORDEÓN PRINCIPAL
    const [seccionesAbiertas, setSeccionesAbiertas] = useState({
        busqueda: true,
        filiacion: true,
        vacunas: false,
        atenciones: true
    });

    // 4. CONTROL DE INSPECCIÓN EN LÍNEA (Listar Entradas)
    const [expandedCompositionId, setExpandedCompositionId] = useState(null);

    // 5. VENTANA MODAL PARA INSPECCIÓN CLÍNICA PROFUNDA
    const [selectedComposition, setSelectedComposition] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    const toggleSeccion = (seccion) => {
        setSeccionesAbiertas(prev => ({ ...prev, [seccion]: !prev[seccion] }));
    };

    // Ejecuta la consulta unificada hacia el backend de Laravel
    const handleConsultar = async (e) => {
        e.preventDefault();
        if (!documento.trim()) return;

        setLoading(true);
        setResponseResult(null);
        setExpandedCompositionId(null);

        try {
            const response = await axios.post('/api/gateway/consultar-paciente', {
                tipo_documento: tipoDocumento,
                documento: documento,
                fecha_desde: activarFiltro ? fechaDesde : null
            });

            setResponseResult({
                success: true,
                status: response.status || 200,
                body: response.data
            });
            // Auto-abrir secciones al encontrar datos exitosamente
            setSeccionesAbiertas({ busqueda: false, filiacion: true, vacunas: true, atenciones: true });
        } catch (error) {
            setResponseResult({
                success: false,
                status: error.response?.status || 500,
                body: error.response?.data || { 
                    resourceType: "OperationOutcome", 
                    issue: [{ severity: "error", diagnostics: error.message || "Error de red o pasarela central inaccesible" }] 
                }
            });
        } finally {
            setLoading(false);
        }
    };

    const handleLimpiar = () => {
        setDocumento('');
        setFechaDesde('');
        setActivarFiltro(false);
        setResponseResult(null);
        setSearchTerm('');
        setExpandedCompositionId(null);
        setSeccionesAbiertas({ busqueda: true, filiacion: false, vacunas: false, atenciones: false });
    };

    // ============================================================================
    // EXTRACCIÓN Y TRATAMIENTO DE DATOS FHIR
    // ============================================================================
    const esExitoso = responseResult?.success;
    const httpStatus = responseResult?.status;
    const rawFhirData = responseResult?.body;

    const compositions = useMemo(() => {
        const entries = rawFhirData?.entry || rawFhirData?.entriesBySource?.paciente || [];
        return entries
            .map(item => item?.resource)
            .filter(r => r && r.resourceType === 'Composition');
    }, [rawFhirData]);

    const inmunizaciones = useMemo(() => {
        const rawVacunasEntries = rawFhirData?.vacunas_bundle?.entry || [];
        return rawVacunasEntries
            .map(item => item?.resource)
            .filter(r => r && r.resourceType === 'Immunization');
    }, [rawFhirData]);

    const patientResource = useMemo(() => {
        const rawVacunasEntries = rawFhirData?.vacunas_bundle?.entry || [];
        const entriesRda = rawFhirData?.entry || [];
        
        const encontradoEnVacunas = rawVacunasEntries.map(item => item?.resource).find(r => r?.resourceType === 'Patient');
        if (encontradoEnVacunas) return encontradoEnVacunas;

        return entriesRda.map(item => item?.resource).find(r => r?.resourceType === 'Patient');
    }, [rawFhirData]);

    const cachedOrganizations = rawFhirData?.referencedResources?.organizations || [];
    const cachedPractitioners = rawFhirData?.referencedResources?.practitioners || [];

    const mapaRecursosReferenciados = useMemo(() => {
        const mapa = new Map();
        const ref = rawFhirData?.referencedResources || {};
        const llavesrecursos = [
            'conditions', 'procedures', 'observations', 'allergyIntolerances', 
            'medicationStatements', 'medicationRequests', 'medicationAdministrations'
        ];

        llavesrecursos.forEach(key => {
            if (Array.isArray(ref[key])) {
                ref[key].forEach(resource => {
                    if (resource?.id) mapa.set(resource.id, resource);
                    if (resource?.resourceType && resource?.id) {
                        mapa.set(`${resource.resourceType}/${resource.id}`, resource);
                    }
                });
            }
        });
        return mapa;
    }, [rawFhirData]);

    const filteredCompositions = compositions.filter(comp => {
        if (!comp) return false;
        const titulo = (comp.title || '').toLowerCase();
        const idLogico = (comp.id || '').toLowerCase();
        return titulo.includes(searchTerm.toLowerCase()) || idLogico.includes(searchTerm.toLowerCase());
    });

    const abrirDetalleModal = (composition) => {
        setSelectedComposition(composition);
        setIsModalOpen(true);
    };

    const resolverOrganizacion = (custodianRef) => {
        if (!custodianRef || !custodianRef.reference) return "IPS Origen Local";
        const id = custodianRef.reference.split('/').pop();
        const org = cachedOrganizations.find(o => o.id === id || o.id === custodianRef.reference);
        return org ? org.name : "IPS Origen Local";
    };

    const resolverMedico = (authorArray) => {
        if (!authorArray || authorArray.length === 0) return "Profesional Asignado";
        const ref = authorArray[0]?.reference || '';
        const id = ref.split('/').pop();
        const doc = cachedPractitioners.find(p => p.id === id || p.id === ref);
        if (doc && doc.name?.[0]) {
            const nameObj = doc.name[0];
            return `${nameObj.given?.join(' ') || ''} ${nameObj.family || ''}`;
        }
        return "Médico Tratante";
    };

    const obtenerIconoSeccion = (titulo) => {
        const text = titulo.toLowerCase();
        if (text.includes('diagnóst') || text.includes('problem') || text.includes('hallazg')) return <FaHeartbeat className="text-rose-500 w-4 h-4" />;
        if (text.includes('alergia') || text.includes('intoleran')) return <FaAllergies className="text-amber-500 w-4 h-4" />;
        if (text.includes('medicamento') || text.includes('fármaco') || text.includes('prescrip')) return <FaPills className="text-emerald-500 w-4 h-4" />;
        if (text.includes('procedimiento') || text.includes('cirug')) return <FaStethoscope className="text-sky-500 w-4 h-4" />;
        return <HiBookmark className="text-slate-400 w-4 h-4" />;
    };

    const obtenerNombrePaciente = (nameArray) => {
        if (!nameArray || nameArray.length === 0) return "No Sincronizado";
        const nameObj = nameArray[0];
        const nombres = nameObj.given ? nameObj.given.join(' ') : '';
        const apellidos = nameObj.family ? nameObj.family : '';
        return `${nombres} ${apellidos}`.trim() || "No Sincronizado";
    };

    const renderizarRecursoReferenciado = (entryRef) => {
        if (!entryRef || !entryRef.reference) return null;
        const fullRef = entryRef.reference;
        const id = fullRef.split('/').pop();
        const recurso = mapaRecursosReferenciados.get(id) || mapaRecursosReferenciados.get(fullRef);
        
        if (!recurso) {
            return (
                <div className="text-[11px] text-slate-400 font-mono bg-slate-50 dark:bg-slate-900/60 p-2 border border-slate-100 dark:border-slate-800">
                    Ref Externa: {fullRef}
                </div>
            );
        }

        switch (recurso.resourceType) {
            case 'Condition':
                return (
                    <div className="p-3 bg-white dark:bg-slate-900 border-l-4 border-l-rose-500 border-y border-r border-slate-100 dark:border-slate-800/60 space-y-1 rounded-r-xl shadow-2xs">
                        <div className="flex items-start justify-between gap-2">
                            <span className="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                {recurso.code?.coding?.[0]?.display || recurso.code?.text || 'Diagnóstico'}
                            </span>
                            <span className="text-[10px] font-mono text-slate-400">[{recurso.code?.coding?.[0]?.code || 'N/A'}]</span>
                        </div>
                        <p className="text-[10px] text-slate-400 uppercase tracking-widest font-bold">CIE-10 Clínico</p>
                    </div>
                );
            case 'MedicationStatement':
            case 'MedicationRequest':
                return (
                    <div className="p-3 bg-white dark:bg-slate-900 border-l-4 border-l-emerald-500 border-y border-r border-slate-100 dark:border-slate-800/60 space-y-1 rounded-r-xl shadow-2xs">
                        <div className="flex items-start justify-between gap-2">
                            <span className="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                {recurso.medicationCodeableConcept?.coding?.[0]?.display || recurso.medicationCodeableConcept?.text || 'Prescripción'}
                            </span>
                            <span className="text-[10px] font-mono text-slate-400">[{recurso.medicationCodeableConcept?.coding?.[0]?.code || 'N/A'}]</span>
                        </div>
                        {recurso.dosage?.[0]?.text && (
                            <p className="text-[10px] text-slate-500 dark:text-slate-400 italic">{recurso.dosage[0].text}</p>
                        )}
                    </div>
                );
            default:
                return (
                    <div className="p-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-[11px] text-slate-700 dark:text-slate-300 font-medium">
                        <span className="font-bold text-slate-400">{recurso.resourceType}:</span> {recurso.code?.coding?.[0]?.display || recurso.code?.text || recurso.id}
                    </div>
                );
        }
    };

    return (
        <AuthenticatedLayout header="Visor del Repositorio Nacional de Historia Clínica">
            <Head title="Core IHCE - Consultas Federadas" />

            {/* Panel de Métricas / KPIs */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 max-w-7xl">
                <div className="bg-white dark:bg-[#111827] border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black tracking-widest text-slate-400 dark:text-slate-500 uppercase">Estado Pasarela</p>
                        <p className={`text-base font-black mt-0.5 ${loading ? 'text-amber-500 animate-pulse' : responseResult ? (esExitoso ? 'text-emerald-500' : 'text-rose-500') : 'text-slate-500'}`}>
                            {loading ? 'Consultando...' : responseResult ? `HTTP ${httpStatus}` : 'Esperando Petición'}
                        </p>
                    </div>
                    <div className="h-8 w-8 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800/60 flex items-center justify-center text-slate-400">
                        <HiTerminal className="w-4 h-4" />
                    </div>
                </div>

                <div className="bg-white dark:bg-[#111827] border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black tracking-widest text-slate-400 dark:text-slate-500 uppercase">Atenciones (RDA)</p>
                        <p className="text-xl font-black text-slate-800 dark:text-slate-100 mt-0.5">{esExitoso ? compositions.length : 0}</p>
                    </div>
                    <div className="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100/50 dark:border-indigo-900/30 flex items-center justify-center text-indigo-500">
                        <HiClipboardList className="w-4 h-4" />
                    </div>
                </div>

                <div className="bg-white dark:bg-[#111827] border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 flex items-center justify-between shadow-xs">
                    <div>
                        <p className="text-[10px] font-black tracking-widest text-slate-400 dark:text-slate-500 uppercase">Inmunizaciones (PAIWEB)</p>
                        <p className="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">{esExitoso ? inmunizaciones.length : 0}</p>
                    </div>
                    <div className="h-8 w-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100/50 dark:border-emerald-900/30 flex items-center justify-center text-emerald-500">
                        <HiSparkles className="w-4 h-4" />
                    </div>
                </div>
            </div>

            {/* CONTENEDOR PRINCIPAL CON ACORDEONES */}
            <div className="max-w-7xl space-y-4">

            {/* ACORDEÓN 1: PANEL DE BÚSQUEDA */}
            <div className="bg-white dark:bg-[#111827] border border-slate-200/70 dark:border-slate-800/80 rounded-2xl shadow-xs overflow-hidden">
                <button 
                    onClick={() => toggleSeccion('busqueda')}
                    className="w-full flex items-center justify-between p-4 bg-slate-50/50 dark:bg-slate-900/40 text-left font-bold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-800"
                >
                    <span className="flex items-center gap-2">
                        <HiSearch className="w-4 h-4 text-indigo-500" /> Panel de Búsqueda de Paciente
                    </span>
                    {seccionesAbiertas.busqueda ? <HiChevronUp className="w-4 h-4" /> : <HiChevronDown className="w-4 h-4" />}
                </button>

                {seccionesAbiertas.busqueda && (
                    <div className="p-5 animate-fade-in">
                        <form onSubmit={handleConsultar} className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Tipo de Documento</label>
                                <select 
                                    value={tipoDocumento} 
                                    onChange={e => setTipoDocumento(e.target.value)}
                                    className="w-full text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500 font-bold text-slate-800 dark:text-slate-100"
                                >
                                    <option value="CC">Cédula de Ciudadanía (CC)</option>
                                    <option value="TI">Tarjeta de Identidad (TI)</option>
                                    <option value="CE">Cédula de Extranjería (CE)</option>
                                    <option value="PA">Pasaporte (PA)</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Número de Documento</label>
                                <input 
                                    type="text" required value={documento} 
                                    onChange={e => setDocumento(e.target.value)}
                                    placeholder="Ingrese identificación"
                                    className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-500 font-semibold text-slate-800 dark:text-slate-100"
                                />
                            </div>
                            <div>
                                <label className="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Filtrar por Fecha</label>
                                <div className="flex items-center gap-2">
                                    <input 
                                        type="checkbox" id="chkActivarFiltro" checked={activarFiltro}
                                        onChange={e => setActivarFiltro(e.target.checked)}
                                        className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                                    />
                                    <input 
                                        type="date" disabled={!activarFiltro} value={fechaDesde} 
                                        onChange={e => setFechaDesde(e.target.value)}
                                        className="w-full text-xs bg-slate-50/50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-xl px-3 py-2 focus:outline-none focus:border-indigo-500 text-slate-800 dark:text-slate-100 font-mono disabled:opacity-40"
                                    />
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    type="submit" disabled={loading}
                                    className="flex-1 flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white font-black text-xs px-4 py-2.5 rounded-xl transition-all shadow-xs disabled:opacity-50"
                                >
                                    {loading ? <HiRefresh className="w-4 h-4 animate-spin" /> : <HiSearch className="w-4 h-4" />}
                                    Consultar
                                </button>
                                <button
                                    type="button" onClick={handleLimpiar}
                                    className="border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 font-bold text-xs px-3 py-2.5 rounded-xl transition-all"
                                >
                                    Limpiar
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>

            {/* CARGA ACTIVA CENTRAL */}
            {loading && (
                <div className="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center bg-white dark:bg-[#111827]">
                    <div className="h-8 w-8 rounded-full border-4 border-indigo-500/20 border-t-indigo-500 animate-spin mx-auto mb-3" />
                    <p className="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-tight">Estableciendo Conexión de Red</p>
                    <p className="text-[11px] text-slate-400 mt-0.5">Consultando registros en los nodos centrales del Ministerio de Salud...</p>
                </div>
            )}

            {/* RESULTADOS EXITOSOS DEL HISTORIAL */}
                {!loading && esExitoso && (
                    <div className="space-y-5 animate-fade-in">
                        
                       {/* ACORDEÓN 2: FILIACIÓN DEMOGRÁFICA */}
{patientResource && (
    <div className="bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden group/filiacion transition-all duration-200 hover:shadow-sm">
        <button 
            type="button"
            onClick={() => toggleSeccion('filiacion')}
            className="w-full flex items-center justify-between p-4 bg-white dark:bg-[#111827] border-b border-slate-100 dark:border-slate-800 text-left font-black text-xs uppercase tracking-wider text-slate-700 dark:text-slate-200 relative focus:outline-none"
        >
            {/* Indicador lateral clínico */}
            <div className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-emerald-500 rounded-r-full transition-transform group-hover/filiacion:scale-y-110" />

            <span className="flex items-center gap-2.5 pl-1">
                <HiUsers className="w-4 h-4 text-emerald-500" /> 
                <span>Información de Filiación del Paciente</span>
            </span>
            {seccionesAbiertas.filiacion ? (
                <HiChevronUp className="w-4 h-4 text-slate-400" />
            ) : (
                <HiChevronDown className="w-4 h-4 text-slate-400" />
            )}
        </button>
        
        {seccionesAbiertas.filiacion && (
            <div className="p-5 bg-white dark:bg-[#111827] animate-fade-in">
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Tarjeta: Nombre Completo */}
                    <div className="p-3.5 rounded-xl bg-slate-50/50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 space-y-1 transition-colors hover:border-slate-200/80 dark:hover:border-slate-700/60">
                        <p className="text-slate-400 dark:text-slate-500 text-[9px] uppercase font-black tracking-widest">
                            Nombre Completo
                        </p>
                        <p className="font-bold text-slate-900 dark:text-white uppercase tracking-wide truncate">
                            {obtenerNombrePaciente(patientResource.name)}
                        </p>
                    </div>

                    {/* Tarjeta: Identificación */}
                    <div className="p-3.5 rounded-xl bg-slate-50/50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 space-y-1 transition-colors hover:border-slate-200/80 dark:hover:border-slate-700/60">
                        <p className="text-slate-400 dark:text-slate-500 text-[9px] uppercase font-black tracking-widest">
                            Documento de Identidad
                        </p>
                        <p className="font-mono font-black text-slate-800 dark:text-slate-200 tracking-wide text-xs">
                            <span className="text-slate-400 dark:text-slate-500 font-sans font-bold mr-1">{tipoDocumento}</span> 
                            {documento}
                        </p>
                    </div>

                    {/* Tarjeta: Género Sincronizado */}
                    <div className="p-3.5 rounded-xl bg-slate-50/50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 space-y-1 transition-colors hover:border-slate-200/80 dark:hover:border-slate-700/60">
                        <p className="text-slate-400 dark:text-slate-500 text-[9px] uppercase font-black tracking-widest">
                            Género Clínico
                        </p>
                        <div className="flex items-center gap-1.5">
                            <span className={`h-2 w-2 rounded-full ${patientResource.gender === 'female' ? 'bg-pink-400' : patientResource.gender === 'male' ? 'bg-blue-400' : 'bg-slate-400'}`} />
                            <p className="font-bold text-slate-900 dark:text-white capitalize">
                                {patientResource.gender || 'N/A'}
                            </p>
                        </div>
                    </div>

                    {/* Tarjeta: Fecha de Nacimiento */}
                    <div className="p-3.5 rounded-xl bg-slate-50/50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 space-y-1 transition-colors hover:border-slate-200/80 dark:hover:border-slate-700/60">
                        <p className="text-slate-400 dark:text-slate-500 text-[9px] uppercase font-black tracking-widest">
                            Fecha de Nacimiento
                        </p>
                        <p className="font-mono font-bold text-slate-800 dark:text-slate-200 text-xs">
                            {patientResource.birthDate || 'N/A'}
                        </p>
                    </div>
                </div>
            </div>
        )}
    </div>
)} 

                        {/* FILTRO INTERNO DE ATENCIONES (BARRA DE BÚSQUEDA GLOBAL) */}
                        {compositions.length > 0 && (
                            <div className="max-w-md my-1 relative">
                                <div className="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                                    <HiSearch className="w-4 h-4" />
                                </div>
                                <input 
                                    type="text" 
                                    value={searchTerm} 
                                    onChange={e => setSearchTerm(e.target.value)}
                                    placeholder="Filtrar atenciones por diagnóstico, título o ID lógico..."
                                    className="w-full text-xs bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 rounded-xl pl-9 pr-4 py-2.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 text-slate-800 dark:text-slate-100 shadow-3xs placeholder:text-slate-400 font-medium"
                                />
                            </div>
                        )}

                        {/* ACORDEÓN 3-A: CONSULTAS Y EVOLUCIONES MÉDICAS (RDA CONSULTA) */}
                        <div className="bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                            <button 
                                type="button"
                                onClick={() => toggleSeccion('consultasRda')}
                                className="w-full flex items-center justify-between p-4 bg-slate-800 dark:bg-slate-900 text-left font-black text-xs uppercase tracking-wider text-white border-b border-slate-700 shadow-xs"
                            >
                                <span className="flex items-center gap-2.5">
                                    <FaBriefcaseMedical className="w-4 h-4 text-emerald-400" /> 
                                    <span>Consultas y Evoluciones Médicas (RDA Consulta)</span>
                                    <span className="ml-1.5 px-2.5 py-0.5 bg-slate-700 text-emerald-400 dark:bg-emerald-950 text-[10px] rounded-full font-black tracking-normal">
                                        {filteredCompositions.filter(comp => !(comp.title || '').toLowerCase().includes('antecedentes')).length}
                                    </span>
                                </span>
                                {seccionesAbiertas.consultasRda ? <HiChevronUp className="w-4 h-4 text-slate-400" /> : <HiChevronDown className="w-4 h-4 text-slate-400" />}
                            </button>

                            {seccionesAbiertas.consultasRda && (
                                <div className="p-5 bg-white dark:bg-[#111827] space-y-4 animate-fade-in">
                                    <div className="grid grid-cols-1 gap-4">
                                        {filteredCompositions
                                            .filter(comp => !(comp.title || '').toLowerCase().includes('antecedentes'))
                                            .map((comp, index) => {
                                                const isExpanded = expandedCompositionId === comp.id;
                                                return (
                                                    <div 
                                                        key={comp.id || index} 
                                                        className="group relative bg-white dark:bg-[#111827] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 hover:border-emerald-500/40 dark:hover:border-emerald-500/30 transition-all duration-200 hover:shadow-md dark:hover:shadow-emerald-950/10 hover:scale-[1.002]"
                                                    >
                                                        <div className="absolute left-0 top-6 w-1 h-10 bg-emerald-500 rounded-r-full transition-transform group-hover:scale-y-110" />

                                                        <div className="flex flex-col md:flex-row md:items-start justify-between gap-4">
                                                            <div className="flex items-start gap-4 w-full">
                                                                <div className="h-10 w-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 shadow-3xs group-hover:bg-emerald-500 group-hover:text-white group-hover:border-emerald-500 transition-all duration-300">
                                                                    <FaBriefcaseMedical className="w-4 h-4" />
                                                                </div>
                                                                
                                                                <div className="space-y-1 w-full">
                                                                    <h4 className="text-xs font-black text-slate-800 dark:text-slate-100 uppercase tracking-wide leading-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                                                        {comp.title || 'RDA Consulta / Evolución'}
                                                                    </h4>
                                                                    
                                                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                                                                        <span className="flex items-center gap-1.5 font-mono text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800/80 px-2 py-0.5 rounded-md">
                                                                            <HiClock className="w-3.5 h-3.5 text-slate-400" />
                                                                            {comp.date ? new Date(comp.date).toLocaleDateString() : 'Sin fecha'}
                                                                        </span>
                                                                        <span className="text-slate-300 dark:text-slate-700">•</span>
                                                                        <span className="flex items-center gap-1">
                                                                            IPS: <b className="text-slate-700 dark:text-slate-200 font-bold">{resolverOrganizacion(comp.custodian)}</b>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <span className="self-start md:self-center text-[9px] font-mono uppercase font-black px-2.5 py-1 rounded-lg border border-emerald-200/60 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 tracking-wider shadow-4xs">
                                                                {comp.status || 'final'}
                                                            </span>
                                                        </div>

                                                        {/* Bloque Técnico Avanzado de Metadatos Homologado */}
                                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4 p-3 rounded-xl bg-slate-50/60 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/60 text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                                                            <div className="flex items-center gap-1.5 truncate">
                                                                <span className="font-sans font-bold uppercase text-[9px] text-slate-400 tracking-wider">ID Nodo:</span>
                                                                <span className="text-slate-700 dark:text-slate-300 font-bold select-all">{comp.id}</span>
                                                            </div>
                                                            <div className="flex items-center gap-1.5 truncate sm:border-l sm:border-slate-200 dark:sm:border-slate-800 sm:pl-3">
                                                                <FaUserMd className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                                                <span className="font-sans font-bold uppercase text-[9px] text-slate-400 tracking-wider mr-0.5">Profesional:</span>
                                                                <span className="text-slate-700 dark:text-slate-300 font-semibold font-sans">{resolverMedico(comp.author)}</span>
                                                            </div>
                                                        </div>

                                                        {isExpanded && (
                                                            <div className="mt-4 pt-4 border-t border-dashed border-slate-200 dark:border-slate-800 space-y-2.5 animate-fade-in">
                                                                <span className="text-[9px] font-black uppercase text-emerald-600 dark:text-emerald-400 tracking-widest block">
                                                                    Recursos FHIR Indexados:
                                                                </span>
                                                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                                    {comp.section?.flatMap(s => s.entry || []).map((entryRef, eIdx) => (
                                                                        <React.Fragment key={eIdx}>
                                                                            {renderizarRecursoReferenciado(entryRef)}
                                                                        </React.Fragment>
                                                                    ))}
                                                                </div>
                                                            </div>
                                                        )}

                                                        <div className="flex justify-between items-center mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/50 text-[10px]">
                                                            <button 
                                                                type="button" 
                                                                onClick={() => setExpandedCompositionId(isExpanded ? null : comp.id)}
                                                                className="flex items-center gap-1 font-mono font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors uppercase tracking-wider focus:outline-none"
                                                            >
                                                                {isExpanded ? '[-] Ocultar Detalles' : '[+] Desplegar Recursos'}
                                                            </button>
                                                            
                                                            <button 
                                                                type="button" 
                                                                onClick={() => abrirDetalleModal(comp)}
                                                                className="font-black text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center gap-1.5 transition-colors uppercase tracking-wider text-[11px] bg-slate-100/80 dark:bg-slate-800 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 px-3 py-1.5 rounded-xl border border-slate-200/40 dark:border-slate-700/60"
                                                            >
                                                                <HiEye className="w-4 h-4 text-slate-400 group-hover:text-emerald-500 transition-colors" /> 
                                                                <span>Inspección Clínica</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                );
                                            })
                                        }
                                        {filteredCompositions.filter(comp => !(comp.title || '').toLowerCase().includes('antecedentes')).length === 0 && (
                                            <p className="text-center text-xs italic text-slate-400 py-4 font-medium bg-slate-50/50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-100 dark:border-slate-800">
                                                No se encontraron consultas médicas o notas de evolución clínica registradas.
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* ACORDEÓN 3-B: HISTORIAL DE ANTECEDENTES MANIFESTADOS */}
                        <div className="bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                            <button 
                                type="button"
                                onClick={() => toggleSeccion('antecedentes')}
                                className="w-full flex items-center justify-between p-4 bg-indigo-600 dark:bg-indigo-700 text-left font-black text-xs uppercase tracking-wider text-white border-b border-indigo-700 shadow-xs"
                            >
                                <span className="flex items-center gap-2.5">
                                    <HiClipboardList className="w-4 h-4 text-indigo-200" /> 
                                    <span>Historial de Antecedentes Manifestados por el Paciente</span>
                                    <span className="ml-1.5 px-2.5 py-0.5 bg-white/20 text-white text-[10px] rounded-full font-black tracking-normal">
                                        {filteredCompositions.filter(comp => (comp.title || '').toLowerCase().includes('antecedentes')).length}
                                    </span>
                                </span>
                                {seccionesAbiertas.antecedentes ? <HiChevronUp className="w-4 h-4 text-indigo-200" /> : <HiChevronDown className="w-4 h-4 text-indigo-200" />}
                            </button>

                            {seccionesAbiertas.antecedentes && (
                                <div className="p-5 bg-white dark:bg-[#111827] space-y-4 animate-fade-in">
                                    <div className="grid grid-cols-1 gap-4">
                                        {filteredCompositions
                                            .filter(comp => (comp.title || '').toLowerCase().includes('antecedentes'))
                                            .map((comp, index) => {
                                                const isExpanded = expandedCompositionId === comp.id;
                                                return (
                                                    <div 
                                                        key={comp.id || index} 
                                                        className="group relative bg-white dark:bg-[#111827] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-5 hover:border-indigo-500/40 dark:hover:border-indigo-500/30 transition-all duration-200 hover:shadow-md dark:hover:shadow-indigo-950/10 hover:scale-[1.002]"
                                                    >
                                                        <div className="absolute left-0 top-6 w-1 h-10 bg-indigo-500 rounded-r-full transition-transform group-hover:scale-y-110" />

                                                        <div className="flex flex-col md:flex-row md:items-start justify-between gap-4">
                                                            <div className="flex items-start gap-4 w-full">
                                                                <div className="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100/70 dark:border-indigo-900 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 shadow-3xs group-hover:bg-indigo-600 group-hover:text-white group-hover:border-indigo-600 transition-all duration-300">
                                                                    <HiClipboardList className="w-5 h-5" />
                                                                </div>
                                                                
                                                                <div className="space-y-1 w-full">
                                                                    <h4 className="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wide leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                                        {comp.title || 'Antecedente Manifestado'}
                                                                    </h4>
                                                                    
                                                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                                                                        <span className="flex items-center gap-1.5 font-mono text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800/80 px-2 py-0.5 rounded-md">
                                                                            <HiClock className="w-3.5 h-3.5 text-slate-400" />
                                                                            {comp.date ? new Date(comp.date).toLocaleDateString() : 'Sin fecha'}
                                                                        </span>
                                                                        <span className="text-slate-300 dark:text-slate-700">•</span>
                                                                        <span className="flex items-center gap-1">
                                                                            IPS: <b className="text-slate-600 dark:text-slate-200 font-bold">{resolverOrganizacion(comp.custodian)}</b>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <span className="self-start md:self-center text-[9px] font-mono uppercase font-black px-2.5 py-1 rounded-lg border border-indigo-200/60 dark:border-indigo-900/50 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-400 tracking-wider shadow-4xs">
                                                                {comp.status || 'final'}
                                                            </span>
                                                        </div>

                                                        {/* Bloque Técnico Avanzado de Metadatos Homologado */}
                                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4 p-3 rounded-xl bg-slate-50/60 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/60 text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                                                            <div className="flex items-center gap-1.5 truncate">
                                                                <span className="font-sans font-bold uppercase text-[9px] text-slate-400 tracking-wider">ID Nodo:</span>
                                                                <span className="text-slate-700 dark:text-slate-300 font-bold select-all">{comp.id}</span>
                                                            </div>
                                                            <div className="flex items-center gap-1.5 truncate sm:border-l sm:border-slate-200 dark:sm:border-slate-800 sm:pl-3">
                                                                <FaUserMd className="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                                                <span className="font-sans font-bold uppercase text-[9px] text-slate-400 tracking-wider mr-0.5">Profesional:</span>
                                                                <span className="text-slate-700 dark:text-slate-300 font-semibold font-sans">{resolverMedico(comp.author)}</span>
                                                            </div>
                                                        </div>

                                                        {isExpanded && (
                                                            <div className="mt-4 pt-4 border-t border-dashed border-slate-200 dark:border-slate-800 space-y-2.5 animate-fade-in">
                                                                <span className="text-[9px] font-black uppercase text-indigo-500 dark:text-indigo-400 tracking-widest block">Recursos FHIR Indexados:</span>
                                                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                                    {comp.section?.flatMap(s => s.entry || []).map((entryRef, eIdx) => (
                                                                        <React.Fragment key={eIdx}>
                                                                            {renderizarRecursoReferenciado(entryRef)}
                                                                        </React.Fragment>
                                                                    ))}
                                                                </div>
                                                            </div>
                                                        )}

                                                        <div className="flex justify-between items-center mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/50 text-[10px]">
                                                            <button 
                                                                type="button" 
                                                                onClick={() => setExpandedCompositionId(isExpanded ? null : comp.id)}
                                                                className="flex items-center gap-1 font-mono font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors uppercase tracking-wider focus:outline-none"
                                                            >
                                                                {isExpanded ? '[-] Ocultar Detalles' : '[+] Desplegar Recursos'}
                                                            </button>
                                                            
                                                            <button 
                                                                type="button" 
                                                                onClick={() => abrirDetalleModal(comp)}
                                                                className="font-black text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-1.5 transition-colors uppercase tracking-wider text-[11px] bg-slate-100/80 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 px-3 py-1.5 rounded-xl border border-slate-200/40 dark:border-slate-700/60"
                                                            >
                                                                <HiEye className="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors" /> 
                                                                <span>Inspeccionar Clínicamente</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                );
                                            })}

                                        {filteredCompositions.filter(comp => (comp.title || '').toLowerCase().includes('antecedentes')).length === 0 && (
                                            <p className="text-center text-xs italic text-slate-400 py-4 font-medium bg-slate-50/50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-100 dark:border-slate-800">
                                                No se encontraron antecedentes manifestados con los criterios ingresados.
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* ACORDEÓN 4: INMUNIZACIONES (PAIWEB) */}
                        <div className="bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
                            <button 
                                type="button"
                                onClick={() => toggleSeccion('vacunas')}
                                className="w-full flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-left font-black text-xs uppercase tracking-wider text-slate-700 dark:text-slate-200"
                            >
                                <span className="flex items-center gap-2.5">
                                    <FaSyringe className="w-4 h-4 text-amber-500" /> 
                                    <span>Historial de Inmunizaciones (PAIWEB)</span>
                                    <span className="ml-1.5 px-2.5 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 text-[10px] rounded-full font-black tracking-normal">
                                        {inmunizaciones.length}
                                    </span>
                                </span>
                                {seccionesAbiertas.vacunas ? <HiChevronUp className="w-4 h-4 text-slate-400" /> : <HiChevronDown className="w-4 h-4 text-slate-400" />}
                            </button>

                            {seccionesAbiertas.vacunas && (
                                <div className="p-5 bg-white dark:bg-[#111827] space-y-4 animate-fade-in">
                                    {inmunizaciones.length > 0 ? (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {inmunizaciones.map((vac, idx) => (
                                                <div 
                                                    key={vac.id || idx}
                                                    className="group relative bg-white dark:bg-[#111827] border border-slate-200/90 dark:border-slate-800 rounded-2xl p-4 hover:border-amber-500/40 dark:hover:border-amber-500/30 transition-all duration-200 hover:shadow-md dark:hover:shadow-amber-950/10 hover:scale-[1.002]"
                                                >
                                                    {/* Indicador lateral sutil de tipo inmunización */}
                                                    <div className="absolute left-0 top-6 w-1 h-10 bg-amber-500 rounded-r-full transition-transform group-hover:scale-y-110" />

                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="flex items-start gap-3.5 w-full">
                                                            {/* Icono de jeringa adaptativo */}
                                                            <div className="h-9 w-9 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 shadow-3xs group-hover:bg-amber-500 group-hover:text-white group-hover:border-amber-500 transition-all duration-300">
                                                                <FaSyringe className="w-4 h-4" />
                                                            </div>

                                                            <div className="space-y-1 w-full">
                                                                <h4 className="text-xs font-black text-slate-800 dark:text-slate-100 uppercase tracking-wide leading-tight group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                                                    {vac.vaccineCode?.coding?.[0]?.display || vac.vaccineCode?.text || 'Biológico Esquema Nacional'}
                                                                </h4>
                                                                
                                                                <div className="flex flex-wrap items-center gap-x-2.5 text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                                                                    <span className="flex items-center gap-1 font-mono text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800/80 px-2 py-0.5 rounded-md">
                                                                        <HiClock className="w-3.5 h-3.5 text-slate-400" />
                                                                        {vac.occurrenceDateTime ? new Date(vac.occurrenceDateTime).toLocaleDateString() : 'Sin fecha'}
                                                                    </span>
                                                                    <span>•</span>
                                                                    <span className="truncate max-w-[180px]">
                                                                        Fab: <b className="text-slate-600 dark:text-slate-300 font-bold">{vac.manufacturer?.display || 'N/A'}</b>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {/* Badge indicador de Dosis Aplicada */}
                                                        <span className="shrink-0 text-[10px] font-mono uppercase font-black px-2 py-0.5 rounded-md border border-amber-200/60 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 tracking-wider shadow-4xs">
                                                            Dos: {vac.protocolApplied?.[0]?.doseNumberString || vac.protocolApplied?.[0]?.doseNumberPositiveInt || '1'}
                                                        </span>
                                                    </div>

                                                    {/* Fila inferior técnica: Lote e ID interno del reporte */}
                                                    <div className="mt-3.5 pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                                                        <div className="flex items-center gap-1">
                                                            <span className="font-sans font-bold uppercase text-[9px] text-slate-400 tracking-wider">Lote / Batch:</span>
                                                            <span className="text-slate-700 dark:text-slate-300 font-bold tracking-wide select-all">{vac.lotNumber || 'N/A'}</span>
                                                        </div>
                                                        <span className="text-[9px] text-slate-300 dark:text-slate-700 font-sans uppercase tracking-widest">PAIWEB Verified</span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-center text-xs italic text-slate-400 py-6 bg-slate-50/50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-100 dark:border-slate-800">
                                            No se reportan vacunas o inmunizaciones activas registradas en la base de datos PAIWEB.
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* VENTANA MODAL: INSPECCIÓN CLÍNICA PROFUNDA */}
            {isModalOpen && selectedComposition && (
                <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
                    <div className="bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 rounded-2xl max-w-4xl w-full max-h-[85vh] flex flex-col shadow-2xl overflow-hidden animate-scale-in">
                        
                        {/* Cabecera Modal */}
                        <div className="p-4 bg-slate-900 text-white flex justify-between items-center">
                            <div>
                                <h3 className="text-xs font-black uppercase tracking-wider text-indigo-400">Inspección de Documento Clínico</h3>
                                <p className="text-sm font-bold truncate max-w-xl uppercase">{selectedComposition.title || 'Resumen de Atención'}</p>
                            </div>
                            <button 
                                type="button" onClick={() => setIsModalOpen(false)}
                                className="p-1 rounded-lg hover:bg-slate-800 transition-colors text-slate-400 hover:text-white"
                            >
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        {/* Cuerpo Modal */}
                        <div className="p-6 overflow-y-auto flex-1 space-y-6 text-xs text-slate-700 dark:text-slate-300">
                            {/* Metadatos de la Institución */}
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 p-3.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800/80 font-medium">
                                <div>
                                    <span className="text-[10px] text-slate-400 block font-bold">IPS Custodia / Emisora</span>
                                    <span className="text-slate-800 dark:text-slate-200 font-bold">{resolverOrganizacion(selectedComposition.custodian)}</span>
                                </div>
                                <div>
                                    <span className="text-[10px] text-slate-400 block font-bold">Médico Autor</span>
                                    <span className="text-slate-800 dark:text-slate-200 font-bold">{resolverMedico(selectedComposition.author)}</span>
                                </div>
                                <div>
                                    <span className="text-[10px] text-slate-400 block font-bold">Fecha de Registro</span>
                                    <span className="font-mono text-slate-800 dark:text-slate-200 font-bold">{selectedComposition.date ? new Date(selectedComposition.date).toLocaleString() : 'N/A'}</span>
                                </div>
                            </div>

                            {/* Secciones Estructuradas de la Historia */}
                            <div className="space-y-4">
                                <h4 className="text-xs font-black uppercase text-slate-400 tracking-widest flex items-center gap-2">
                                    <FaNotesMedical /> Secciones Clínicas Homologadas
                                </h4>

                                {selectedComposition.section && selectedComposition.section.length > 0 ? (
                                    selectedComposition.section.map((sec, sIdx) => (
                                        <div key={sIdx} className="border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden bg-white dark:bg-slate-900/40">
                                            <div className="px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2 font-bold text-slate-800 dark:text-slate-200">
                                                {obtenerIconoSeccion(sec.title || '')}
                                                <span className="uppercase text-[11px]">{sec.title || 'Nota Médica'}</span>
                                            </div>
                                            
                                            <div className="p-4 space-y-3">
                                                {/* Narrativa Clínica en Texto Libre */}
                                                {sec.text?.div && (
                                                    <div 
                                                        className="prose prose-sm dark:prose-invert max-w-none text-xs text-slate-600 dark:text-slate-400 font-sans leading-relaxed parsed-fhir-html"
                                                        dangerouslySetInnerHTML={{ __html: sec.text.div }}
                                                    />
                                                )}

                                                {/* Recursos Clínicos Referenciados */}
                                                {sec.entry && sec.entry.length > 0 && (
                                                    <div className="mt-3 pt-2 border-t border-dashed border-slate-100 dark:border-slate-800/60">
                                                        <span className="text-[9px] font-black uppercase text-slate-400 tracking-wider block mb-2">Entradas Diagnósticas Relacionadas:</span>
                                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                            {sec.entry.map((entryRef, eIdx) => (
                                                                <React.Fragment key={eIdx}>
                                                                    {renderizarRecursoReferenciado(entryRef)}
                                                                </React.Fragment>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <p className="text-xs italic text-slate-400 text-center py-4">Este documento no tiene secciones o notas estructuradas adjuntas.</p>
                                )}
                            </div>
                        </div>

                        {/* Pie Modal */}
                        <div className="p-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                            <button 
                                type="button" onClick={() => setIsModalOpen(false)}
                                className="bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs px-5 py-2 rounded-xl transition-all"
                            >
                                Cerrar Registro
                            </button>
                        </div>

                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}