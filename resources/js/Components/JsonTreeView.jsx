import { useState, useEffect } from 'react';
import { HiChevronDown, HiChevronRight } from 'react-icons/hi';

export default function JsonTreeView({ data, isLast = true, level = 0, isInitiallyCollapsed = false }) {
    const [isCollapsed, setIsCollapsed] = useState(isInitiallyCollapsed);

    // Sincroniza el estado si el padre fuerza un cambio global
    useEffect(() => {
        setIsCollapsed(isInitiallyCollapsed);
    }, [isInitiallyCollapsed]);

    const type = typeof data;

    // 1. Escenario cuando el valor es NULL
    if (data === null) {
        return (
            <span className="font-mono text-xs selection:bg-rose-500/30">
                <span className="text-rose-400 font-black drop-shadow-[0_0_6px_rgba(244,63,94,0.2)]">null</span>
                {!isLast && <span className="text-slate-600 font-sans mx-px">,</span>}
            </span>
        );
    }

    // 2. Escenario cuando es un OBJETO o ARREGLO
    if (type === 'object') {
        const isArray = Array.isArray(data);
        const keys = Object.keys(data);
        
        if (keys.length === 0) {
            return (
                <span className="font-mono text-xs text-slate-500 tracking-wide select-all">
                    {isArray ? '[]' : '{}'}{!isLast && <span className="text-slate-600 font-sans">,</span>}
                </span>
            );
        }

        return (
            <div className="font-mono text-xs select-text">
                {/* Cabecera del nodo colapsable */}
                <span 
                    onClick={() => setIsCollapsed(!isCollapsed)} 
                    className="inline-flex items-center gap-1 cursor-pointer select-none text-slate-400 hover:text-indigo-300 rounded px-1 -mx-1 py-0.5 hover:bg-indigo-950/20 transition-all duration-150 group/node"
                >
                    {isCollapsed ? (
                        <HiChevronRight className="w-3.5 h-3.5 text-indigo-400/80 group-hover/node:scale-110 group-hover/node:text-indigo-400 transition-transform" />
                    ) : (
                        <HiChevronDown className="w-3.5 h-3.5 text-indigo-400/80 group-hover/node:scale-110 group-hover/node:text-indigo-400 transition-transform" />
                    )}
                    <span className="text-slate-400 font-medium">{isArray ? '[' : '{'}</span>
                    
                    {isCollapsed && (
                        <span className="text-[10px] font-sans font-semibold bg-slate-900/90 text-indigo-400/90 px-1.5 py-px rounded border border-indigo-950/60 mx-1 shadow-2xs">
                            {keys.length} {keys.length === 1 ? 'propiedad' : 'propiedades'}
                        </span>
                    )}
                    
                    {isCollapsed && (isArray ? ']' : '}')}
                    {isCollapsed && !isLast && <span className="text-slate-600 font-sans">,</span>}
                </span>

                {/* Contenido del nodo (Indentación con guías de línea estilizadas) */}
                {!isCollapsed && (
                    <div className="relative pl-4 ml-1.5 my-0.5 border-l border-slate-800/60 hover:border-indigo-500/30 transition-colors duration-200">
                        {keys.map((key, index) => {
                            const isLastKey = index === keys.length - 1;
                            return (
                                <div key={key} className="py-0.5 flex flex-wrap items-start">
                                    {!isArray && (
                                        <span className="text-emerald-400/90 font-bold mr-1.5 select-all hover:text-emerald-300 transition-colors tracking-wide drop-shadow-[0_0_8px_rgba(52,211,153,0.05)]">
                                            "{key}":
                                        </span>
                                    )}
                                    <JsonTreeView 
                                        data={data[key]} 
                                        isLast={isLastKey} 
                                        level={level + 1} 
                                        isInitiallyCollapsed={isInitiallyCollapsed}
                                    />
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Cierre del nodo */}
                {!isCollapsed && (
                    <span className="text-slate-400 block pl-3.5 select-none font-medium">
                        {isArray ? ']' : '}'}{!isLast && <span className="text-slate-600 font-sans">,</span>}
                    </span>
                )}
            </div>
        );
    }

    // 3. Escenario para valores PRIMITIVOS (Syntax Highlighting Pro)
    let valueElement = null;
    if (type === 'string') {
        valueElement = <span className="text-amber-200/90 font-normal break-all select-all tracking-wide">"{data}"</span>;
    } else if (type === 'number') {
        valueElement = <span className="text-sky-400 font-bold select-all drop-shadow-[0_0_6px_rgba(56,189,248,0.1)]">{data}</span>;
    } else if (type === 'boolean') {
        valueElement = (
            <span className={`font-extrabold uppercase text-[10px] tracking-wider select-all px-1.5 py-0.2 rounded border ${
                data 
                    ? 'bg-purple-950/30 border-purple-800/40 text-purple-400' 
                    : 'bg-slate-900 border-slate-800 text-slate-400'
            }`}>
                {data ? 'true' : 'false'}
            </span>
        );
    } else {
        valueElement = <span className="text-slate-300 select-all">{String(data)}</span>;
    }

    return (
        <span className="font-mono text-xs leading-relaxed selection:bg-indigo-500/30">
            {valueElement}
            {!isLast && <span className="text-slate-600 font-sans mx-px">,</span>}
        </span>
    );
}