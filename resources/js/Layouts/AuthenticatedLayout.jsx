import { useState, useEffect, useRef } from 'react';
import { Link, router } from '@inertiajs/react';
import { 
    HiChartBar, 
    HiUsers, 
    HiTerminal, 
    HiAdjustments, 
    HiMenu, 
    HiLogout, 
    HiX, 
    HiSun, 
    HiMoon, 
    HiUser, 
    HiShieldCheck, 
    HiChevronDown 
} from 'react-icons/hi';

export default function AuthenticatedLayout({ user, header, children }) {
    // Estados separados: uno para PC y otro para Móvil
    const [isSidebarOpen, setIsSidebarOpen] = useState(true); // Controla escritorio
    const [isMobileOpen, setIsMobileOpen] = useState(false);   // Controla móvil
    
    const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
    const userMenuRef = useRef(null);

    // Estado para la persistencia del Modo Oscuro
    const [darkMode, setDarkMode] = useState(() => {
        if (typeof window !== 'undefined') {
            return localStorage.getItem('theme') === 'dark' || 
                (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
        }
        return false;
    });

    useEffect(() => {
        if (darkMode) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    }, [darkMode]);

    // Cerrar el menú flotante del usuario al hacer clic fuera
    useEffect(() => {
        function handleClickOutside(event) {
            if (userMenuRef.current && !userMenuRef.current.contains(event.target)) {
                setIsUserMenuOpen(false);
            }
        }
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, [userMenuRef]);

    const handleLogout = (e) => {
        e.preventDefault();
        router.post(route('logout'));
    };

    return (
        <div className="min-h-screen bg-[#f8fafc] dark:bg-[#0b0f19] flex flex-col font-sans antialiased text-slate-900 dark:text-slate-100 transition-colors duration-200">
            
            {/* 1. TOPBAR FIX SUPERIOR */}
            <header className="bg-white/90 dark:bg-[#111827]/90 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/60 h-14 px-4 flex items-center justify-between sticky top-0 z-50 shadow-[0_1px_2px_0_rgba(0,0,0,0.02)]">
                <div className="flex items-center gap-4">
                    {/* Botón de Hamburguesa Inteligente */}
                    <button 
                        onClick={() => {
                            if (window.innerWidth >= 768) {
                                setIsSidebarOpen(!isSidebarOpen);
                            } else {
                                setIsMobileOpen(!isMobileOpen);
                            }
                        }}
                        className="p-1.5 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none"
                        title="Alternar menú lateral"
                    >
                        <HiMenu className="w-5 h-5" />
                    </button>
                    
                    <Link href="/dashboard" className="flex items-center gap-2 group">
                        <div className="h-6 w-6 rounded-md bg-indigo-600 group-hover:scale-105 transition-transform flex items-center justify-center text-white text-[10px] font-black shadow-sm">DH</div>
                        <span className="text-xs font-bold tracking-wider text-slate-800 dark:text-slate-200">
                            D-HEALTH <span className="text-indigo-600 dark:text-indigo-400 font-medium">CORE IHCE</span>
                        </span>
                    </Link>
                </div>

                {/* Controles de Barra Derecha */}
                <div className="flex items-center gap-2 md:gap-4">
                    <div className="hidden sm:flex items-center gap-2 bg-emerald-50 dark:bg-emerald-950/20 px-2.5 py-1 rounded-full border border-emerald-100 dark:border-emerald-900/30">
                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span className="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Sync Activa</span>
                    </div>

                    {/* DROPDOWN INTERACTIVO DEL NOMBRE DE USUARIO */}
                    <div className="relative border-l border-slate-200 dark:border-slate-800 pl-2 md:pl-4 h-6 flex items-center" ref={userMenuRef}>
                        <button 
                            onClick={() => setIsUserMenuOpen(!isUserMenuOpen)}
                            className="flex items-center gap-2 group focus:outline-none select-none py-1 px-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <div className="text-right hidden md:block">
                                <p className="text-xs font-semibold text-slate-700 dark:text-slate-200 leading-none group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {user?.name || 'Antonio Gonzalez'}
                                </p>
                                <p className="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5">Console Admin</p>
                            </div>
                            <HiChevronDown className={`w-3 h-3 text-slate-400 group-hover:text-slate-600 transition-transform duration-200 ${isUserMenuOpen ? 'rotate-180' : ''}`} />
                            
                            <div className="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-900/50 flex items-center justify-center text-xs font-bold shadow-sm">
                                {(user?.name || 'A').charAt(0).toUpperCase()}
                            </div>
                        </button>

                        {/* Menú Desplegable Enriquecido con Toggle Dark/Light */}
                        {isUserMenuOpen && (
                            <div className="absolute right-0 top-10 w-56 bg-white dark:bg-[#111827] rounded-xl border border-slate-200/60 dark:border-slate-800/80 shadow-xl py-1 z-50 origin-top-right">
                                <div className="px-3 py-2 border-b border-slate-100 dark:border-slate-800/60">
                                    <p className="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{user?.name || 'Antonio Gonzalez'}</p>
                                    <p className="text-[10px] text-slate-400 truncate">{user?.email || 'admin@dhealth.com'}</p>
                                </div>
                                
                                {/* INTERACTIVO PARA CAMBIO DE MODO DENTRO DEL MENU */}
                                <div className="px-3 py-2 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-800/20 flex items-center justify-between">
                                    <span className="text-[11px] font-medium text-slate-500 dark:text-slate-400">Aspecto Visual</span>
                                    <button 
                                        onClick={() => setDarkMode(!darkMode)}
                                        className="flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-bold border transition-all shadow-xs focus:outline-none
                                            bg-white hover:bg-slate-100 border-slate-200 text-slate-700
                                            dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-slate-700 dark:text-slate-300"
                                    >
                                        {darkMode ? (
                                            <>
                                                <HiSun className="w-3.5 h-3.5 text-amber-400" />
                                                <span>Modo Claro</span>
                                            </>
                                        ) : (
                                            <>
                                                <HiMoon className="w-3.5 h-3.5 text-indigo-500" />
                                                <span>Modo Oscuro</span>
                                            </>
                                        )}
                                    </button>
                                </div>

                                <a href="#" className="flex items-center gap-2 px-3 py-2 text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-slate-200 transition-all">
                                    <HiUser className="w-4 h-4 text-slate-400" />
                                    <span>Mi Perfil</span>
                                </a>
                                <a href="#" className="flex items-center gap-2 px-3 py-2 text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/40 hover:text-slate-900 dark:hover:text-slate-200 transition-all">
                                    <HiShieldCheck className="w-4 h-4 text-slate-400" />
                                    <span>Seguridad (MFA)</span>
                                </a>
                                <div className="border-t border-slate-100 dark:border-slate-800/60 my-1" />
                                <button onClick={handleLogout} className="w-full flex items-center gap-2 px-3 py-2 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 font-medium transition-all">
                                    <HiLogout className="w-4 h-4" />
                                    <span>Cerrar Sesión</span>
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            {/* CONTENEDOR DE ARQUITECTURA DE PANTALLA */}
            <div className="flex flex-1 relative overflow-hidden h-[calc(100vh-56px)]">
                
                {/* 2. SIDEBAR CON ALTURA AJUSTADA PARA MÓVIL Y ESCRITORIO */}
                <aside className={`
                    bg-white dark:bg-[#111827] border-r border-slate-200/80 dark:border-slate-800/60 flex flex-col pt-4 select-none z-40
                    fixed top-14 bottom-0 left-0 w-60 px-4 shadow-xl transition-transform duration-300 ease-in-out md:shadow-none md:pt-4 md:transition-all
                    ${isMobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'}
                    md:static ${isSidebarOpen ? 'md:w-60 md:px-4' : 'md:w-0 md:p-0 md:border-r-0 overflow-hidden'}
                `}>
                    
                    {/* Botón X de cierre móvil */}
                    <div className="flex md:hidden justify-end mb-2">
                        <button onClick={() => setIsMobileOpen(false)} className="p-1 rounded-md text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800">
                            <HiX className="w-5 h-5" />
                        </button>
                    </div>

                    <nav className="flex-1 space-y-1">
                        <p className="text-[9px] font-bold text-slate-400 dark:text-slate-500 tracking-widest uppercase px-3 mb-2 whitespace-nowrap">Monitoreo</p>
                        
                        <Link 
                            href="/dashboard" 
                            className={`flex items-center rounded-xl text-xs font-medium px-3 py-2.5 gap-3 transition-all whitespace-nowrap ${
                                route().current('dashboard') 
                                    ? 'bg-indigo-50/60 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400 font-bold shadow-[inset_0_0_0_1px_rgba(79,70,229,0.1)]' 
                                    : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-800/30'
                            }`}
                            onClick={() => setIsMobileOpen(false)}
                        >
                            <HiChartBar className="w-4 h-4 shrink-0" />
                            <span>Métricas de Red</span>
                        </Link>
                        
                        {/* ========================================================================= */}
                        {/* NUEVO GRUPO: INTEROPERABILIDAD IHCE (CON CONTROL DE ACCESO PRE-DISEÑADO)  */}
                        {/* ========================================================================= */}
                        {/* Control temporal 'true'. En el futuro cambiará a una validación de roles de usuario */}
                        {true && ( 
                            <>
                                <p className="text-[9px] font-bold text-slate-400 dark:text-slate-500 tracking-widest uppercase px-3 pt-4 mb-2 whitespace-nowrap">
                                    Interoperabilidad IHCE
                                </p>

                                <Link 
                                    href={route('ihce.transmision.rdapacientes')} 
                                    className={`flex items-center rounded-xl text-xs font-medium px-3 py-2.5 gap-3 transition-all whitespace-nowrap ${
                                        route().current('ihce.transmision.rdapacientes') 
                                            ? 'bg-indigo-50/60 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400 font-bold shadow-[inset_0_0_0_1px_rgba(79,70,229,0.1)]' 
                                            : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-800/30'
                                    }`}
                                    onClick={() => setIsMobileOpen(false)}
                                >
                                    <div className="relative">
                                        <HiUsers className="w-4 h-4 shrink-0" />
                                        <span className="absolute top-0 right-0 h-1.5 w-1.5 rounded-full bg-emerald-500 ring-1 ring-white dark:ring-[#111827]"></span>
                                    </div>
                                    <span>Nodo pacientes</span>
                                </Link>

                                <Link 
                                    href={route('ihce.transmision.rdaconsulta')} 
                                    className={`flex items-center rounded-xl text-xs font-medium px-3 py-2.5 gap-3 transition-all whitespace-nowrap ${
                                        route().current('ihce.transmision.rdaconsulta') 
                                            ? 'bg-indigo-50/60 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400 font-bold shadow-[inset_0_0_0_1px_rgba(79,70,229,0.1)]' 
                                            : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-800/30'
                                    }`}
                                    onClick={() => setIsMobileOpen(false)}
                                >
                                    <div className="relative">
                                        <HiTerminal className="w-4 h-4 shrink-0" />
                                        <span className="absolute top-0 right-0 h-1.5 w-1.5 rounded-full bg-emerald-500 ring-1 ring-white dark:ring-[#111827]"></span>
                                    </div>
                                    <span>Nodo consulta externa</span>
                                </Link>

                                <Link 
                                    href={route('ihce.consultas.index')} 
                                    className={`flex items-center rounded-xl text-xs font-medium px-3 py-2.5 gap-3 transition-all whitespace-nowrap ${
                                        route().current('ihce.consultas.index') 
                                            ? 'bg-indigo-50/60 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400 font-bold shadow-[inset_0_0_0_1px_rgba(79,70,229,0.1)]' 
                                            : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-800/30'
                                    }`}
                                    onClick={() => setIsMobileOpen(false)}
                                >
                                    <div className="relative">
                                        {/* Usamos el icono HiSearch o HiUsers importado arriba */}
                                        <HiUsers className="w-4 h-4 shrink-0" /> 
                                        <span className="absolute top-0 right-0 h-1.5 w-1.5 rounded-full bg-blue-500 ring-1 ring-white dark:ring-[#111827]"></span>
                                    </div>
                                    <span>Consultas MinSalud</span>
                                </Link>

                                <p className="text-[9px] font-bold text-slate-400 dark:text-slate-500 tracking-widest uppercase px-3 pt-4 mb-2 whitespace-nowrap">Configuraciones</p>
                                
                                <Link 
                                    href={route('ihce.config.index')} 
                                    className={`flex items-center rounded-xl text-xs font-medium px-3 py-2.5 gap-3 transition-all whitespace-nowrap ${
                                        route().current('ihce.config.index') 
                                            ? 'bg-indigo-50/60 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400 font-bold shadow-[inset_0_0_0_1px_rgba(79,70,229,0.1)]' 
                                            : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-800/30'
                                    }`}
                                    onClick={() => setIsMobileOpen(false)}
                                >
                                    <HiShieldCheck className="w-4 h-4 shrink-0" />
                                    <span>Credenciales</span>
                                </Link>
                                
                                <Link 
                                    href={route('ihce.ambiente.index')} 
                                    className={`flex items-center rounded-xl text-xs font-medium px-3 py-2.5 gap-3 transition-all whitespace-nowrap ${
                                        route().current('ihce.ambiente.index') 
                                            ? 'bg-indigo-50/60 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400 font-bold shadow-[inset_0_0_0_1px_rgba(79,70,229,0.1)]' 
                                            : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-50/50 dark:hover:bg-slate-800/30'
                                    }`}
                                    onClick={() => setIsMobileOpen(false)}
                                >
                                    <HiAdjustments className="w-4 h-4 shrink-0" />
                                    <span>Gestión de ambientes</span>
                                </Link>
                            </>
                        )}

                    </nav>

                    <div className="p-2 text-center border-t border-slate-100 dark:border-slate-800/60 text-[10px] text-slate-400 dark:text-slate-500 font-mono">
                        v1.0.0
                    </div>
                </aside>

                {/* 3. CAPA OSCURA DE FONDO (BACKDROP) EN MÓVILES */}
                {isMobileOpen && (
                    <div 
                        onClick={() => setIsMobileOpen(false)}
                        className="fixed inset-x-0 bottom-0 top-14 bg-slate-900/40 backdrop-blur-xs z-30 md:hidden"
                    />
                )}

                {/* 4. CONTENIDO PRINCIPAL ADAPTABLE */}
                <main className="flex-1 p-4 md:p-6 lg:p-8 overflow-y-auto min-w-0 w-full transition-all duration-300">
                    {header && (
                        <div className="mb-6">
                            <span className="text-[10px] font-bold tracking-widest text-indigo-600 dark:text-indigo-400 uppercase">Consola de Operaciones</span>
                            <h1 className="text-xl font-bold tracking-tight text-slate-900 dark:text-white mt-0.5">{header}</h1>
                        </div>
                    )}
                    <div className="w-full">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}