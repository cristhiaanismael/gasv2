<?php require_once 'includes/auth_check.php';
$module_title = "Historial de Lecturas";
$active_menu_id = "historial";
include 'includes/head.php';
?>
<body class="h-screen bg-[#F8FAFC] flex font-sans overflow-hidden text-gray-800">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <?php include 'includes/sidebar.php'; ?>

    <!-- ÁREA DE TRABAJO PRINCIPAL -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <?php include 'includes/navbar.php'; ?>

        <!-- Contenido Flexible -->
        <div class="flex-1 flex overflow-hidden relative">
            <?php include 'includes/aside.php'; ?>

            <!-- MAIN DATA VIEW -->
            <main id="main-scroll-area" class="flex-1 overflow-y-auto p-4 sm:p-8 w-full bg-slate-50/50 transition-all duration-300">
                <div class="w-full mx-auto">
                    <!-- Header del Edificio Actual -->
                    <div class="mb-8 flex flex-col lg:flex-row lg:justify-between lg:items-end gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div>
                            <div class="flex items-center space-x-2 mb-1">
                                <i data-lucide="building-2" class="w-5 h-5 text-blue-600"></i>
                                <span class="text-sm font-bold text-blue-600 uppercase tracking-wider">Edificio en captura</span>
                            </div>
                            <h2 class="text-3xl sm:text-4xl font-black text-gray-900 flex items-center">
                                <span id="current-building-name">Tamarindos</span>
                                <span id="building-complete-badge" class="hidden ml-4 items-center text-sm font-bold bg-green-100 text-green-700 px-3 py-1.5 rounded-lg border border-green-200">
                                    <i data-lucide="check-circle" class="w-4 h-4 mr-1.5"></i>
                                    100% Listo
                                </span>
                            </h2>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-4 w-full lg:w-auto">
                            <div class="relative flex flex-col items-center group z-50">
                                <div id="search-omni-container" class="flex items-center bg-white/80 border border-gray-200/80 rounded-2xl overflow-hidden transition-all duration-300 w-full sm:w-80 shadow-sm focus-within:shadow-md focus-within:border-blue-400 focus-within:ring-4 focus-within:ring-blue-500/10 p-1">
                                    <div id="search-omni-icon-btn" class="w-10 h-10 flex items-center justify-center flex-shrink-0 text-gray-400 cursor-text">
                                        <i data-lucide="search" class="w-4 h-4"></i>
                                    </div>
                                    <input type="text" id="search-omni-input" 
                                           placeholder="Buscar: Nombre, Depto, Email..." 
                                           autocomplete="nope"
                                           spellcheck="false"
                                           class="w-full bg-transparent border-none focus:ring-0 text-xs font-bold text-gray-700 placeholder-gray-400 px-1" />
                                    <button id="search-omni-go" disabled
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-black px-4 py-2 rounded-xl ml-1 transition-all duration-300 uppercase tracking-widest active:scale-95 flex-shrink-0 disabled:opacity-50 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed">
                                        Go
                                    </button>
                                </div>

                                <div id="search-filters-container" class="absolute top-full right-0 mt-2 w-full sm:w-[28rem] bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-100 p-4 hidden transition-all z-[120] transform origin-top">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Buscar por:</span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-3 gap-y-2">
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="edificio" checked>
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Edificio</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="departamento" checked>
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Depto</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="cliente" checked>
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Nombre Titular</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="correo" checked>
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Correo</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="lt_ant">
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Lt Ant.</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="recibo_ant">
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Total Periodo(ant)</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="saldo_ant" checked>
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Saldo Ant.</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="lt">
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Lt</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="total_periodo" checked>
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Total Periodo</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="saldo_actual" checked>
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Saldo Actual</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="adeudos">
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Adeudos</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="saldo_favor">
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Saldo a Favor</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 cursor-pointer">
                                            <input type="checkbox" class="search-filter-cb w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500" value="abonos">
                                            <span class="text-[10px] font-bold text-gray-700 truncate">Abonos</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Dropdown de Resultados Inteligentes -->
                                <div id="search-omni-results" class="hidden absolute top-full right-0 mt-3 w-full sm:w-80 bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-100 z-[100] overflow-hidden transition-all duration-300">
                                    <div class="p-3 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Sugerencias AI</span>
                                        <div class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-ping"></div>
                                    </div>
                                    <div id="search-results-body" class="max-h-96 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                        <!-- Inyectado por JS -->
                                    </div>
                                </div>
                            </div>

                            <!-- Botón Toggle Herramientas Avanzadas -->
                            <button id="btn-toggle-advanced" class="flex items-center space-x-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-[10px] font-black transition-all border border-slate-200 group uppercase tracking-widest">
                                <i data-lucide="settings-2" class="w-4 h-4 group-hover:rotate-45 transition-transform"></i>
                                <span>Herramientas Avanzadas</span>
                                <i data-lucide="chevron-down" id="advanced-chevron" class="w-4 h-4 transition-transform"></i>
                            </button>
                        </div>
                    </div>

                    <!-- PANEL DE HERRAMIENTAS AVANZADAS (COLAPSABLE) -->
                    <div id="advanced-tools-panel" class="hidden mb-8 bg-slate-900 border border-slate-800 p-6 rounded-[2rem] shadow-2xl overflow-hidden transition-all duration-300">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                            <div class="flex flex-col sm:flex-row items-center gap-6">
                                <!-- MASTER EMAIL SWITCH -->
                                <div class="flex items-center bg-white/5 p-2 rounded-2xl border border-white/10 group hover:bg-white/10 transition-all">
                                    <div class="flex items-center space-x-4 px-3 mr-4 border-r border-white/10">
                                        <button id="btn-help-master-switch" class="w-10 h-10 bg-amber-500/20 rounded-xl flex items-center justify-center hover:bg-amber-500/30 transition-all border border-amber-500/30" title="Ayuda sobre el envío automático">
                                            <i data-lucide="lightbulb" class="w-5 h-5 text-amber-400 animate-pulse"></i>
                                        </button>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black text-white uppercase tracking-wider leading-none mb-1">Envío Automático</span>
                                            <span id="master-switch-desc-label" class="text-[9px] font-bold text-green-400 uppercase">Estado: Activo</span>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="master-email-toggle" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                </div>

                                <!-- DESCARGA MASIVA -->
                                <div class="flex items-center bg-white/5 p-2 rounded-2xl border border-white/10 group hover:bg-white/10 transition-all">
                                    <div class="flex items-center space-x-4 px-3 mr-4 border-r border-white/10">
                                        <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
                                            <i data-lucide="file-archive" class="w-5 h-5 text-blue-400"></i>
                                        </div>
                                        <div class="flex flex-col text-left">
                                            <span class="text-[10px] font-black text-white uppercase tracking-wider leading-none mb-1">Gestión de Lotes</span>
                                            <span class="text-[8px] font-bold text-slate-400 uppercase">Exportación ZIP de todos los PDFs</span>
                                        </div>
                                    </div>
                                    <button id="download-all-pdfs-btn" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black rounded-xl transition-all shadow-lg active:scale-95 uppercase tracking-widest">
                                        Descargar Todo
                                    </button>
                                </div>
                            </div>
                            <div class="hidden lg:block">
                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest text-right max-w-[180px] leading-relaxed">
                                    Configuraciones globales aplicables a <br> <span class="text-slate-300">Todas las unidades del edificio</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Dashboard -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                            <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Facturado</h3>
                            <p id="kpi-facturado" class="text-xl sm:text-2xl font-black text-gray-900">$0.00</p>
                        </div>
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                            <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Cobranza</h3>
                            <p id="kpi-cobranza" class="text-xl sm:text-2xl font-black text-gray-900">0%</p>
                        </div>
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                            <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Consumo Total</h3>
                            <p id="kpi-consumo" class="text-xl sm:text-2xl font-black text-gray-900">0 <span class="text-sm text-gray-400 font-medium">lt</span></p>
                        </div>
                        <div class="bg-indigo-50 p-5 rounded-xl border border-indigo-100 shadow-sm relative overflow-hidden">
                            <div class="relative z-10">
                                <h3 class="text-[10px] font-bold text-indigo-800 uppercase tracking-wider mb-1">Ingresos Recaudados</h3>
                                <p id="kpi-progreso" class="text-xl sm:text-2xl font-black text-indigo-900">$0.00</p>
                            </div>
                            <div class="absolute bottom-0 left-0 h-1.5 bg-indigo-100 w-full">
                                <div id="kpi-progress-bar" class="h-full bg-indigo-500 transition-all duration-1000" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Datos Tradicional -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse whitespace-nowrap">
                                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 pl-6 w-10">
                                            <input type="checkbox" id="select-all-deptos" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                        </th>
                                        <th class="px-4 py-3">Depto</th>
                                        <th class="px-4 py-3">Nombre del Titular</th>
                                        <th class="px-4 py-3 text-center text-slate-400" title="Lectura Inicial">Lec. Ant.</th>
                                        <th class="px-4 py-3 text-center text-slate-400" title="Lectura Final">Lec. Act.</th>
                                        <th class="px-4 py-3 text-center text-slate-400" title="Litros del periodo anterior">Lt(ant)</th>
                                        <th class="px-4 py-3 text-center text-slate-400" title="Total a pagar del mes anterior">Total Periodo(ant)</th>
                                        <th class="px-4 py-3 text-center text-slate-400" title="Saldo con el que cerró el periodo anterior (arrastre al periodo actual)">Saldo Cierre(ant)</th>
                                        <th class="px-4 py-3 text-center">Lt</th>
                                        <th class="px-4 py-3 text-center font-bold text-gray-700">Total Periodo</th>
                                        <th class="px-4 py-3 text-center font-bold text-blue-600">Saldo Actual</th>
                                        <th class="px-4 py-3 text-center text-slate-400">Último abono realizado</th>
                                        <th class="px-4 py-3 text-center">Estado</th>
                                        <th class="px-4 py-3 pr-6 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="data-table-body" class="divide-y divide-gray-100">
                                    <!-- Filas inyectadas por JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Success State & Auto-Advance -->
                    <div id="success-state-container" class="mt-8 bg-gradient-to-r from-[#0F172A] to-[#1E293B] rounded-2xl p-8 flex-col items-center justify-center text-center shadow-xl relative overflow-hidden hidden">
                        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-blue-500 rounded-full blur-3xl opacity-20"></div>
                        <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mb-4 border border-white/20 mx-auto">
                            <i data-lucide="check-circle" class="w-8 h-8 text-green-400"></i>
                        </div>
                        <h3 class="text-2xl font-black text-white mb-2">¡<span id="success-building-name"></span> al 100%!</h3>
                        <p class="text-gray-300 mb-8 font-medium">Todas las lecturas de este edificio han sido procesadas.</p>
                        <div id="next-building-action">
                            <!-- Botón inyectado por JS -->
                        </div>
                    </div>

<!-- Backdrop para el panel lateral -->
    <div id="panel-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden transition-opacity duration-300 opacity-0"></div>

    <!-- PANEL LATERAL DE DETALLES Y PAGOS -->
    <aside id="unit-detail-panel" class="fixed top-0 right-0 h-full w-full sm:w-[550px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out border-l border-gray-200 flex flex-col">
        <!-- Header del Panel -->
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between bg-slate-50">
            <h2 id="panel-unit-name" class="text-lg font-black text-slate-800 uppercase tracking-tight">Depto ---</h2>
            <div class="flex items-center space-x-2">
                <div id="panel-header-actions"></div>
                <button id="close-panel-btn" class="p-1.5 hover:bg-gray-200 rounded-full transition-colors group">
                    <i data-lucide="x" class="w-5 h-5 text-gray-400 group-hover:text-gray-600"></i>
                </button>
            </div>
        </div>

        <!-- Contenido Scrollable -->
        <div class="flex-1 overflow-y-auto p-4 space-y-5 custom-scrollbar">
            
            <!-- Sección: Registrar Pago (Ultra Compacta) -->
            <div class="bg-amber-50 border border-amber-200 p-2.5 rounded-xl">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-amber-900 uppercase tracking-tighter px-1">Registrar Movimiento</span>
                        <span id="panel-saldo-actual" class="text-[8px] font-bold text-amber-600 px-1">Saldo: $0.00</span>
                    </div>
                    <select id="panel-select-mov-tipo" class="bg-amber-100 border-none text-[9px] font-black text-amber-900 uppercase rounded-lg px-2 py-1 focus:ring-0 cursor-pointer">
                        <option value="pago">Pago (Abono)</option>
                        <option value="ajuste">Ajuste (Rebaja)</option>
                        <option value="cargo">Recargo (Cargo)</option>
                    </select>
                </div>
                <div class="bg-white p-0.5 rounded-lg flex items-center border border-amber-200 shadow-sm">
                    <input type="number" id="panel-input-pago" placeholder="Monto $" class="flex-1 bg-transparent border-none px-3 py-1.5 text-sm font-bold text-amber-900 focus:ring-0 placeholder-amber-200" />
                    <button id="btn-submit-payment" class="bg-amber-600 hover:bg-amber-700 text-white w-8 h-8 rounded-md flex items-center justify-center transition-all shadow-sm active:scale-90" title="Registrar movimiento">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Sección: Lectura Actual (Edición) -->
            <div class="space-y-3">
                <h4 class="text-xs font-black text-slate-800 uppercase tracking-tight">Lectura del Periodo</h4>
                
                <!-- Barra de Periodo y Tarifas -->
                <div class="flex items-center justify-between bg-slate-100 border border-slate-200 rounded-lg px-3 py-1.5 shadow-inner">
                    <div class="flex items-center space-x-2">
                        <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                        <span id="panel-period-label" class="text-[10px] font-black text-slate-800 uppercase tracking-tight">---</span>
                    </div>
                    <div class="flex items-center space-x-3 border-l border-slate-300 pl-3">
                        <div class="group relative cursor-help flex items-center space-x-1" title="Precio x Litro">
                            <i data-lucide="droplets" class="w-3.5 h-3.5 text-blue-500"></i>
                            <span id="lbl-val-precio" class="text-[10px] font-black text-slate-700">$0.00</span>
                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-slate-800 text-white text-[8px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">Precio x Litro</span>
                        </div>
                        <div class="group relative cursor-help flex items-center space-x-1" title="Factor de Conversión">
                            <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-500"></i>
                            <span id="lbl-val-factor" class="text-[10px] font-black text-slate-700">0.000</span>
                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-slate-800 text-white text-[8px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">Factor Conv.</span>
                        </div>
                        <div class="group relative cursor-help flex items-center space-x-1" title="Cuota Admin">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-orange-500"></i>
                            <span id="lbl-val-cuota" class="text-[10px] font-black text-slate-700">$0.00</span>
                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-slate-800 text-white text-[8px] rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50">Cuota Admin</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase">Lec. Anterior</label>
                        <input type="text" id="panel-input-lec-ant" class="w-full bg-gray-100 border-none rounded-xl px-3 py-1.5 text-sm font-bold text-gray-500" disabled />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-gray-900 uppercase">Lec. Actual</label>
                        <input type="number" id="panel-input-lec-act" step="0.001" class="w-full bg-blue-50 border border-blue-100 rounded-xl px-3 py-1.5 text-sm font-black text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                </div>

                <!-- Resumen de Consumo Compacto (Debajo de lecturas) -->
                <div class="flex items-center gap-2 px-1">
                    <div class="flex-1 flex items-center justify-between bg-white border-2 border-slate-300 rounded-lg px-2 py-1 shadow-sm">
                        <span class="text-[8px] font-black text-slate-500 uppercase tracking-tighter">M3:</span>
                        <span id="lbl-consumo-m3" class="text-[10px] font-black text-slate-900">0.00</span>
                    </div>
                    <div class="flex-1 flex items-center justify-between bg-white border-2 border-slate-300 rounded-lg px-2 py-1 shadow-sm">
                        <span class="text-[8px] font-black text-slate-500 uppercase tracking-tighter">LT:</span>
                        <span id="lbl-consumo-lt" class="text-[10px] font-black text-slate-700">0.00</span>
                    </div>
                    <div class="flex-1 flex items-center justify-between bg-white border-2 border-slate-300 rounded-lg px-2 py-1 shadow-sm">
                        <span class="text-[8px] font-black text-slate-600 uppercase tracking-tighter">GAS:</span>
                        <span id="lbl-monto-gas" class="text-[10px] font-black text-slate-900">$0.00</span>
                    </div>
                </div>

                <div class="mt-1">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-gray-900 uppercase">Adicionales ($)</label>
                        <input type="number" id="panel-input-add" step="0.01" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 text-sm font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                </div>

                <!-- Saldos y Adeudos Compactos (Arriba del total) -->
                <div class="grid grid-cols-2 gap-2 mt-1">
                    <div class="bg-white border-2 border-green-500/30 p-2 rounded-lg flex items-center justify-between px-3 shadow-sm ring-1 ring-green-500/10">
                        <span class="text-[8px] font-black text-green-700 uppercase">S. a Favor</span>
                        <div class="flex items-center space-x-1.5">
                            <span id="lbl-saldo-favor" class="text-[10px] font-black text-green-800">$0.00</span>
                            <button id="btn-log-abonos" class="p-0.5 rounded text-green-400 hover:text-green-700 hover:bg-green-50 transition-all" title="Ver detalle de abonos">
                                <i data-lucide="file-search" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                    <div class="bg-white border-2 border-rose-500/30 p-2 rounded-lg flex items-center justify-between px-3 shadow-sm ring-1 ring-rose-500/10">
                        <span class="text-[8px] font-black text-rose-700 uppercase">Adeudo Prev:</span>
                        <div class="flex items-center space-x-1.5">
                            <span id="lbl-adeudos" class="text-[10px] font-black text-rose-800">$0.00</span>
                            <button id="btn-log-adeudos" class="p-0.5 rounded text-rose-400 hover:text-rose-700 hover:bg-rose-50 transition-all" title="Ver detalle de adeudos">
                                <i data-lucide="file-search" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="lbl-abonos-periodo-container"></div>
                <div class="space-y-1 mt-1">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-bold text-gray-900 uppercase">Subtotal del Periodo</label>
                        <div class="flex items-center space-x-2">
                            <span class="text-[9px] font-black text-slate-400 uppercase">Saldo Neto Final:</span>
                            <span id="panel-balance-final" class="text-xs font-black text-slate-900">$0.00</span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <div class="relative flex-1 group">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-600 font-bold">$</span>
                            <input type="number" id="panel-input-total" step="0.01" class="w-full bg-blue-50/50 border border-blue-100 rounded-xl pl-8 pr-3 py-2 text-base font-black text-blue-700 focus:outline-none transition-all shadow-inner cursor-not-allowed" readonly />
                        </div>
                        <button id="btn-recalculate-total" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 rounded-xl transition-all shadow-sm flex items-center justify-center group" title="Recalcular con tarifas vigentes">
                            <i data-lucide="calculator" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                        </button>
                    </div>
                </div>


                <button id="btn-update-reading" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-3 rounded-xl transition-all shadow-lg active:scale-95 disabled:opacity-50">
                    GUARDAR CAMBIOS
                </button>
            </div>

            <!-- Divisor -->
            <hr class="border-gray-100" />

            <!-- Sección: Histórico de 12 meses -->
            <div class="space-y-3 pb-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-black text-gray-900 uppercase">Histórico (Últimos 12 meses)</h4>
                    <button id="btn-expand-history" class="p-1.5 hover:bg-gray-100 rounded-lg text-gray-400 hover:text-blue-600 transition-all group" title="Expandir Histórico">
                        <i data-lucide="maximize-2" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                    </button>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                    <table class="w-full border-collapse text-[10px] font-mono border-l border-r border-gray-100" style="table-layout:fixed;">
                        <thead class="bg-gray-200 text-gray-600 font-bold uppercase border-b border-gray-300">
                            <tr>
                                <th class="px-1 py-1.5 text-left border-r border-gray-200" style="width:13%">PER.</th>
                                <th class="px-1 py-1.5 text-right border-r border-gray-200 bg-blue-100/50" style="width:12%">L.ANT</th>
                                <th class="px-1 py-1.5 text-right border-r border-gray-200 bg-blue-100/50" style="width:12%">L.ACT</th>
                                <th class="px-1 py-1.5 text-right border-r border-gray-200 bg-indigo-100/50" style="width:11%">M3</th>
                                <th class="px-1 py-1.5 text-right border-r border-gray-200 bg-indigo-100/50 text-slate-500" style="width:11%">LT</th>
                                <th class="px-1 py-1.5 text-right border-r border-gray-200 bg-slate-100/50 text-rose-500" style="width:12%">AD.</th>
                                <th class="px-1 py-1.5 text-right border-r border-gray-200 bg-slate-100/50 text-green-600" style="width:12%">S.F.</th>
                                <th class="px-1 py-1.5 text-right bg-green-100/50" style="width:17%">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody id="panel-history-body" class="divide-y divide-gray-50">
                            <!-- Inyectado por JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </aside>

    <div class="h-16"></div>
                </div>
            </main>
        </div>
    </div>

    <script src="js/historial_config.js"></script>
    <script src="js/historial.js"></script>
    <script src="js/historialini.js"></script>
    <!-- MODAL DE HISTÓRICO EXPANDIDO -->
    <div id="modal-history-expanded" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 transition-opacity opacity-0" id="modal-history-backdrop"></div>
        
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col z-[70]" id="modal-history-content">
            <!-- Header Modal -->
            <div class="px-8 py-5 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
                        <i data-lucide="history" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 tracking-tight leading-none mb-1">Histórico Detallado</h2>
                        <p id="modal-history-depto" class="text-sm font-bold text-blue-600">Depto ---</p>
                    </div>
                </div>
                <button id="btn-close-history-modal" class="bg-white p-2.5 rounded-xl border border-gray-200 text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-all shadow-sm">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Tabla Modal -->
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm bg-white">
                    <table class="w-full border-collapse text-xs font-mono" style="table-layout:fixed;">
                        <thead class="bg-slate-100 text-slate-600 font-black uppercase border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-4 text-left border-r border-gray-200" style="width:12%">PERIODO</th>
                                <th class="px-4 py-4 text-right border-r border-gray-200 bg-blue-50/40" style="width:12%">LECT. ANT</th>
                                <th class="px-4 py-4 text-right border-r border-gray-200 bg-blue-50/40" style="width:12%">LECT. ACT</th>
                                <th class="px-4 py-4 text-right border-r border-gray-200 bg-indigo-50/40" style="width:11%">M3</th>
                                <th class="px-4 py-4 text-right border-r border-gray-200 bg-indigo-50/40 text-slate-500" style="width:11%">LITROS</th>
                                <th class="px-4 py-4 text-right border-r border-gray-200 bg-rose-50/40 text-rose-500" style="width:12%">ADEUDO</th>
                                <th class="px-4 py-4 text-right border-r border-gray-200 bg-green-50/40 text-green-600" style="width:12%">S. FAVOR</th>
                                <th class="px-4 py-4 text-right bg-green-50/40" style="width:18%">TOTAL PAGAR</th>
                            </tr>
                        </thead>
                        <tbody id="modal-history-body" class="divide-y divide-gray-50">
                            <!-- Inyectado por JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="px-8 py-5 border-t border-gray-100 bg-slate-50/50 flex justify-end">
                <button id="btn-close-history-modal-footer" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-xl text-sm font-black transition-all shadow-lg active:scale-95">
                    CERRAR VISTA
                </button>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    
    <!-- GLOBAL CONTEXT MENU (PDF) -->
    <div id="global-pdf-context-menu" class="fixed hidden bg-slate-900 border border-slate-700 shadow-2xl rounded-xl p-2 z-[9999] min-w-[220px] animate-in fade-in zoom-in-95 duration-100">
        <button id="ctx-ver-pdf" class="w-full text-left px-4 py-2 text-xs font-black text-white hover:bg-slate-800 rounded-lg flex items-center mb-1 border border-transparent hover:border-slate-700">
            <i data-lucide="eye" class="w-4 h-4 mr-3 text-blue-400"></i> VER RECIBO ACTUAL
        </button>
        <button id="ctx-solo-reenviar" class="w-full text-left px-4 py-2 text-xs font-black text-emerald-400 hover:bg-emerald-950/30 rounded-lg flex items-center mb-1 border border-transparent hover:border-emerald-800">
            <i data-lucide="send" class="w-4 h-4 mr-3"></i> SOLO REENVIAR (YA CREADO)
        </button>
        <div class="h-px bg-slate-800 my-1"></div>
        <button id="ctx-recrear-enviar" class="w-full text-left px-4 py-2 text-xs font-black text-slate-300 hover:bg-slate-800 rounded-lg flex items-center mb-1 border border-transparent hover:border-slate-700">
            <i data-lucide="zap" class="w-4 h-4 mr-3 text-yellow-400"></i> REGENERAR Y ENVIAR
        </button>
        <button id="ctx-recrear-nosend" class="w-full text-left px-4 py-2 text-xs font-black text-rose-400 hover:bg-rose-950/30 rounded-lg flex items-center border border-transparent hover:border-rose-800">
            <i data-lucide="refresh-ccw-dot" class="w-4 h-4 mr-3"></i> REGENERAR SIN ENVIAR
        </button>
    </div>

    <!-- MODAL LOG DE MOVIMIENTOS (Pagos / Adeudos) -->
    <div id="modal-movements-log" class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 transition-opacity opacity-0" id="modal-log-backdrop"></div>
        
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col z-[80]" id="modal-log-content">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center space-x-3">
                    <div id="modal-log-icon" class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
                        <i data-lucide="receipt" class="w-4 h-4 text-white"></i>
                    </div>
                    <div>
                        <h2 id="modal-log-title" class="text-lg font-black text-slate-800 tracking-tight leading-none mb-0.5">Estado de Cuenta</h2>
                        <p id="modal-log-depto" class="text-xs font-bold text-slate-400">Depto ---</p>
                    </div>
                </div>
                <button id="btn-close-log-modal" class="bg-white p-2 rounded-xl border border-gray-200 text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-all shadow-sm">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Resumen Rápido -->
            <div class="px-6 py-3 bg-gradient-to-r from-slate-50 to-white border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="text-center">
                            <span class="block text-[8px] font-bold text-slate-400 uppercase">Total Cargos</span>
                            <span id="log-sum-cargos" class="text-sm font-black text-rose-700">$0.00</span>
                        </div>
                        <div class="text-center">
                            <span class="block text-[8px] font-bold text-slate-400 uppercase">Total Abonos</span>
                            <span id="log-sum-abonos" class="text-sm font-black text-green-700">$0.00</span>
                        </div>
                        <div class="w-px h-6 bg-slate-200"></div>
                        <div class="text-center">
                            <span class="block text-[8px] font-bold text-slate-400 uppercase">Saldo Neto</span>
                            <span id="log-sum-neto" class="text-sm font-black text-slate-900">$0.00</span>
                        </div>
                    </div>
                    <span id="log-count" class="text-[9px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">0 movimientos</span>
                </div>
            </div>

            <!-- Timeline de Movimientos -->
            <div class="flex-1 overflow-y-auto px-6 py-4 custom-scrollbar">
                <div id="modal-log-body" class="relative space-y-0">
                    <!-- Timeline inyectada por JS -->
                    <div class="text-center py-10 text-gray-400 text-sm">Cargando movimientos...</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-slate-50/50 flex justify-end">
                <button id="btn-close-log-footer" class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2 rounded-xl text-xs font-black transition-all shadow-lg active:scale-95">
                    CERRAR
                </button>
            </div>
        </div>
    </div>

    <!-- GLOBAL HELP MODAL / SMART MANUAL -->
    <div id="modal-global-help" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 transition-opacity opacity-0" id="modal-help-backdrop"></div>
        
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col z-[110]" id="modal-help-content">
            <!-- Header Premium -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-blue-600 to-indigo-700">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center border border-white/30">
                        <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-white tracking-tight leading-none mb-1">Manual de Operación</h2>
                        <p class="text-blue-100 text-xs font-bold uppercase tracking-widest opacity-80">GasData Finance v1.0</p>
                    </div>
                </div>
                <button id="btn-close-help-modal" class="bg-white/10 p-2.5 rounded-xl text-white hover:bg-white/20 transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Contenido Dinámico con Diseño -->
            <div id="help-modal-body" class="p-8 space-y-6">
                <!-- Aquí se inyecta el contenido según el foquito -->
                <div class="text-center py-10">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                </div>
            </div>

            <!-- Footer con cierre -->
            <div class="px-8 py-5 border-t border-gray-100 bg-slate-50 flex justify-end">
                <button id="btn-close-help-modal-footer" class="bg-slate-900 hover:bg-slate-800 text-white px-8 py-3 rounded-2xl text-sm font-black transition-all shadow-lg active:scale-95">
                    ENTENDIDO
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE NOTAS / CHAT INTERNO -->
    <div id="modal-notes-chat" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 transition-opacity opacity-0" id="modal-notes-backdrop"></div>
        
        <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg max-h-[85vh] overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col z-[110]" id="modal-notes-content">
            <!-- Header Chat -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-slate-50/80">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-200">
                        <i data-lucide="message-square" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800 tracking-tight leading-none mb-1">Notas de Lectura</h2>
                        <p id="modal-notes-subtitle" class="text-xs font-bold text-indigo-600 uppercase tracking-widest">Periodo ---</p>
                    </div>
                </div>
                <button id="btn-close-notes-modal" class="bg-white p-2.5 rounded-xl border border-gray-200 text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-all shadow-sm">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Área de Mensajes (Chat) -->
            <div id="notes-chat-body" class="flex-1 overflow-y-auto p-8 space-y-6 bg-white custom-scrollbar">
                <!-- Burbujas inyectadas por JS -->
                <div class="text-center py-10 text-slate-300 italic text-sm">Cargando comentarios...</div>
            </div>

            <!-- Editor de Comentario Rápido -->
            <div class="p-6 bg-slate-50 border-t border-gray-100">
                <div class="relative group">
                    <textarea 
                        id="input-new-note" 
                        rows="2" 
                        class="w-full bg-white border-2 border-gray-100 rounded-2xl p-4 pr-16 text-sm font-medium text-slate-700 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-0 transition-all resize-none shadow-sm"
                        placeholder="Escribe un comentario interno..."
                    ></textarea>
                    <button 
                        id="btn-send-note" 
                        class="absolute right-3 bottom-3 w-10 h-10 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200 transition-all active:scale-95 disabled:bg-slate-300 disabled:shadow-none"
                    >
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </button>
                </div>
                <p class="mt-3 text-[10px] text-slate-400 font-bold text-center uppercase tracking-widest">
                    Visibilidad: Solo personal administrativo
                </p>
            </div>
        </div>
    </div>

    <!-- BARRA DE ACCIONES MASIVAS (Gamma Style) -->
    <div id="massive-action-bar" class="fixed bottom-8 left-1/2 transform -translate-x-1/2 z-[80] hidden translate-y-20 transition-all duration-500 ease-out">
        <div class="bg-slate-900/90 backdrop-blur-xl border border-slate-700 shadow-2xl rounded-2xl px-6 py-4 flex items-center space-x-6 min-w-[500px]">
            <div class="flex items-center space-x-3 pr-6 border-r border-slate-700">
                <span id="selected-count" class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white font-black text-xs rounded-full">0</span>
                <span class="text-xs font-bold text-slate-300 uppercase tracking-widest">Seleccionados</span>
            </div>
            
            <div class="flex items-center space-x-3 flex-1">
                <button id="btn-massive-smart" class="group flex items-center space-x-2 bg-white/10 hover:bg-white/20 text-white px-4 py-2.5 rounded-xl transition-all border border-white/5 active:scale-95">
                    <i data-lucide="zap" class="w-4 h-4 text-amber-400"></i>
                    <div class="text-left">
                        <span class="block text-[10px] font-black uppercase leading-none mb-0.5">Completar y Enviar</span>
                        <span class="block text-[8px] text-slate-400 font-medium">Genera faltantes + Envía todo</span>
                    </div>
                </button>
                
                <button id="btn-massive-force" class="group flex items-center space-x-2 bg-rose-600/20 hover:bg-rose-600/30 text-rose-100 px-4 py-2.5 rounded-xl transition-all border border-rose-500/20 active:scale-95">
                    <i data-lucide="refresh-ccw" class="w-4 h-4 text-rose-500"></i>
                    <div class="text-left">
                        <span class="block text-[10px] font-black uppercase leading-none mb-0.5 tracking-tighter">Regenerar Todo</span>
                        <span class="block text-[8px] text-rose-300/60 font-medium uppercase">Reemplaza y Notifica</span>
                    </div>
                </button>
            </div>

            <div class="w-px h-8 bg-slate-700 mx-2"></div>

            <button id="btn-massive-cancel" class="p-2 text-slate-500 hover:text-white transition-colors" title="Limpiar Selección">
                <i data-lucide="x-circle" class="w-5 h-5"></i>
            </button>
        </div>
    </div>

    <!-- CONSOLA DE TRANSMISIÓN MASIVA (Progress Monitor) -->
    <div id="modal-transmission-console" class="fixed inset-0 z-[110] hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-950/80 transition-opacity opacity-0" id="transmission-backdrop"></div>
        
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col z-[120]" id="transmission-content">
            <!-- Header -->
            <div class="px-8 py-6 bg-slate-900 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-900/50">
                        <i data-lucide="rocket" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-white tracking-tight leading-none mb-1 uppercase">Centro de Transmisión</h2>
                        <div class="flex items-center space-x-2">
                            <span id="console-progress-text" class="text-blue-400 text-[10px] font-black uppercase tracking-widest">Iniciando proceso...</span>
                            <span class="text-slate-600 text-[10px]">•</span>
                            <span id="console-count" class="text-slate-400 text-[10px] uppercase font-bold">0 / 0 Deptos</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar Master -->
            <div class="h-1.5 w-full bg-slate-800 overflow-hidden relative border-y border-white/5">
                <div id="console-progress-bar" class="absolute top-0 left-0 h-full bg-blue-500 transition-all duration-500 ease-out" style="width: 0%"></div>
            </div>

            <!-- Lista de Resultados en Tiempo Real -->
            <div id="console-log-container" class="flex-1 overflow-y-auto max-h-[400px] p-6 space-y-2 bg-slate-50 custom-scrollbar min-h-[300px]">
                <div class="text-center py-10 text-slate-300 text-xs font-bold italic uppercase tracking-widest animate-pulse">
                    Esperando instrucciones...
                </div>
            </div>

            <!-- Footer con cierre -->
            <div id="console-footer" class="px-8 py-5 border-t border-gray-100 bg-white flex justify-end items-center hidden">
                <button id="btn-close-console" class="bg-slate-900 hover:bg-slate-800 text-white px-10 py-3 rounded-2xl text-xs font-black transition-all shadow-lg active:scale-95 uppercase tracking-widest">
                    FINALIZAR PROCESO
                </button>
            </div>
        </div>
    </div>

    <!-- SCRIPTS MÓDULO HISTORIAL -->
    <script src="js/app.js"></script>
    <!-- MODAL VISOR DE EVIDENCIA (M3) -->
    <div id="modal-evidence-viewer" class="fixed inset-0 z-[110] flex items-center justify-center p-4 hidden">
        <div id="modal-evidence-backdrop" class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300 opacity-0"></div>
        <div id="modal-evidence-content" class="bg-white rounded-[32px] shadow-2xl w-full max-w-2xl overflow-hidden relative z-10 transform transition-all duration-300 scale-95 opacity-0 border border-white/20">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i data-lucide="camera" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-slate-800 tracking-tight">Evidencia de Lectura</h3>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Captura del Medidor</p>
                    </div>
                </div>
                <button id="btn-close-evidence" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-rose-50 hover:text-rose-500 transition-all text-slate-400">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <!-- Image Area -->
            <div class="p-4 bg-slate-100/50">
                <div class="relative rounded-2xl overflow-hidden bg-slate-900 shadow-inner min-h-[300px] flex items-center justify-center">
                    <img id="evidence-image-preview" src="" alt="Evidencia" class="max-w-full max-h-[70vh] object-contain transition-transform hover:scale-105 cursor-zoom-in" />
                </div>
            </div>
            <!-- Footer -->
            <div class="px-8 py-5 bg-white flex justify-end">
                <button id="btn-close-evidence-footer" class="px-8 py-3 bg-slate-900 text-white text-xs font-black rounded-2xl hover:bg-blue-600 transition-all shadow-lg active:scale-95 uppercase tracking-widest">Cerrar Visor</button>
            </div>
        </div>
    </div>

    <!-- MODAL ENVIAR NOTIFICACIÓN / CORREO -->
    <div id="modal-send-email" class="fixed inset-0 z-[120] flex items-center justify-center p-4 hidden">
        <div id="modal-email-backdrop" class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300 opacity-0" onclick="historial.closeEmailModal()"></div>
        <div id="modal-email-content" class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-xl overflow-hidden relative z-10 transform transition-all duration-300 scale-95 opacity-0 border border-white/20 flex flex-col max-h-[90vh]">
            
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-indigo-50/50 flex-shrink-0">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                        <i data-lucide="mail" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-indigo-900 tracking-tight">Comunicación con el Cliente</h3>
                        <p class="text-xs font-bold text-indigo-400 uppercase tracking-widest">Enviar Recibo y Notificación</p>
                    </div>
                </div>
                <button onclick="historial.closeEmailModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-indigo-100 text-indigo-400 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-8 space-y-6 overflow-y-auto flex-1 custom-scrollbar">
                <input type="hidden" id="email-dept-id" value="">
                
                <!-- Destinatario -->
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Destinatario(s)</label>
                    <select id="email-recipient-type" onchange="historial.handleEmailTypeChange()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer">
                        <option value="ambos">Enviar a los correos registrados (Ambos)</option>
                        <option value="primario">Enviar al correo primario</option>
                        <option value="secundario">Enviar al correo secundario</option>
                        <option value="otro">Enviar a otro correo...</option>
                    </select>
                </div>
                
                <!-- Custom Email Input (Hidden by default) -->
                <div id="custom-email-container" class="hidden">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Escriba el correo destinatario</label>
                    <input type="email" id="email-custom-address" placeholder="ejemplo@correo.com" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" />
                </div>
                
                <!-- Tipo de Mensaje -->
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Tipo de Mensaje</label>
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="email-msg-type" value="preconfigurado" checked onchange="historial.handleEmailMsgTypeChange()" class="w-4 h-4 text-indigo-600 bg-slate-50 border-slate-300 focus:ring-indigo-500">
                            <span class="ml-2 text-sm font-bold text-slate-700">Mensaje Preconfigurado</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="email-msg-type" value="personalizado" onchange="historial.handleEmailMsgTypeChange()" class="w-4 h-4 text-indigo-600 bg-slate-50 border-slate-300 focus:ring-indigo-500">
                            <span class="ml-2 text-sm font-bold text-slate-700">Mensaje Personalizado</span>
                        </label>
                    </div>
                </div>
                
                <!-- Asunto -->
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Asunto del Correo</label>
                    <input type="text" id="email-subject" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" />
                </div>
                
                <!-- Mensaje -->
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Mensaje</label>
                    <textarea id="email-message" rows="5" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all resize-none"></textarea>
                </div>
                
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl flex items-start space-x-3">
                    <i data-lucide="paperclip" class="w-5 h-5 text-blue-500 mt-0.5"></i>
                    <div class="flex-1">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="email-attach-pdf" checked class="w-4 h-4 text-blue-600 bg-white border-blue-300 rounded focus:ring-blue-500 focus:ring-2 mr-2 cursor-pointer">
                            <span class="text-xs font-bold text-blue-800 uppercase tracking-widest">Adjuntar recibo de este periodo</span>
                        </label>
                        <p class="text-[11px] font-medium text-blue-600 leading-relaxed mt-1">
                            Al estar seleccionado, el sistema generará el PDF al vuelo y lo adjuntará automáticamente al correo.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-8 py-5 bg-slate-50 flex justify-end space-x-3 flex-shrink-0">
                <button onclick="historial.closeEmailModal()" class="px-6 py-3 bg-white text-slate-600 text-xs font-black rounded-2xl hover:bg-slate-100 transition-all border border-slate-200 uppercase tracking-widest">Cancelar</button>
                <button id="btn-send-custom-email" onclick="historial.sendCustomEmail()" class="px-8 py-3 bg-indigo-600 text-white text-xs font-black rounded-2xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 flex items-center space-x-2 uppercase tracking-widest">
                    <span>Enviar Correo</span>
                    <i data-lucide="send" class="w-4 h-4 ml-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DIAGNÓSTICO DE DESCARGA ZIP -->
    <div id="modal-zip-diagnosis" class="fixed inset-0 z-[120] flex items-center justify-center p-4 hidden">
        <div id="modal-zip-backdrop" class="absolute inset-0 bg-slate-900/60 transition-opacity duration-300 opacity-0"></div>
        <div id="modal-zip-content" class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-xl overflow-hidden relative z-10 transform transition-all duration-300 scale-95 opacity-0 border border-white/20">
            <!-- Header Alerta -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-rose-50/50">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-rose-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-rose-900 tracking-tight">Documentación Incompleta</h3>
                        <p class="text-xs font-bold text-rose-400 uppercase tracking-widest">No se puede generar el ZIP</p>
                    </div>
                </div>
                <button onclick="historial.closeZipModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-rose-100 text-rose-400 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <div class="p-8 space-y-6 flex-1 overflow-y-auto max-h-[60vh] custom-scrollbar">
                <div id="zip-diagnosis-body" class="space-y-6"></div>
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-2xl flex items-start space-x-3">
                    <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5"></i>
                    <p class="text-xs font-medium text-blue-700 leading-relaxed">
                        Para descargar el lote completo, asegúrese de haber capturado todas las lecturas y haber hecho clic en "Generar PDF".
                    </p>
                </div>
            </div>

            <div class="px-8 py-5 bg-slate-50 flex justify-end">
                <button onclick="historial.closeZipModal()" class="px-8 py-3 bg-slate-900 text-white text-xs font-black rounded-2xl hover:bg-rose-600 transition-all shadow-lg uppercase tracking-widest">Entendido</button>
            </div>
        </div>
    </div>

    <script src="js/historial_config.js?v=<?php echo time(); ?>"></script>
    <script src="js/historial.js?v=<?php echo time(); ?>"></script>
    <script src="js/historialini.js?v=<?php echo time(); ?>"></script>
</body>
</html>
