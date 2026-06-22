<?php require_once 'includes/auth_check.php';
$module_title = "Configuración del Sistema"; 
$active_menu_id = "ajustes";
$hide_building_toggle = true;
include 'includes/head.php'; 
?>
<body class="h-screen bg-[#F8FAFC] flex font-sans overflow-hidden text-gray-800">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <?php include 'includes/sidebar.php'; ?>

    <!-- ÁREA DE TRABAJO PRINCIPAL -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <?php include 'includes/navbar.php'; ?>

        <div class="flex-1 flex overflow-hidden">
            <!-- Sidebar de Navegación Config (Local del módulo) -->
            <aside class="w-64 bg-white border-r border-slate-200 flex flex-col p-4 shadow-sm z-10">
                <nav class="space-y-1">
                    <button data-tab="periodos" class="tab-link w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-slate-50 text-blue-600 bg-blue-50">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>Gestión de Periodos</span>
                    </button>
                    <button data-tab="edificios" class="tab-link w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-slate-50 text-slate-600">
                        <i data-lucide="building" class="w-4 h-4"></i>
                        <span>Tarifas y Cuotas</span>
                    </button>
                    <button data-tab="cobranza" class="tab-link w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-slate-50 text-slate-600">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        <span>Configuración Cobranza</span>
                    </button>
                </nav>
            </aside>

            <!-- Contenido de Configuración -->
            <main class="flex-1 overflow-y-auto custom-scrollbar p-10 bg-slate-50/30">
                <!-- SECCIÓN PERIODOS -->
                <section id="section-periodos" class="tab-section space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="flex justify-between items-end">
                        <div>
                            <h1 class="text-3xl font-extrabold tracking-tight text-slate-1000">Periodos de Facturación</h1>
                            <p class="text-slate-500 mt-1 font-medium">Define los rangos de fechas oficiales para las lecturas.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Formulario de Nuevo Periodo -->
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 space-y-6">
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <i data-lucide="plus" class="w-5 h-5"></i>
                                </div>
                                <h3 class="text-xl font-bold">Crear Nuevo Periodo</h3>
                            </div>

                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Fecha Inicio</label>
                                        <input type="date" id="p-fecha-inicio" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none font-medium">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Fecha Fin</label>
                                        <input type="date" id="p-fecha-fin" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none font-medium">
                                    </div>
                                </div>
                                
                                <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                                    <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest block mb-1">Nombre Generado (Automático)</label>
                                    <div id="periodo-preview" class="text-xl font-black text-blue-900 font-mono italic">-- - --</div>
                                </div>

                                <button id="btn-save-periodo" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold shadow-lg hover:bg-slate-800 transition-all active:scale-95 flex items-center justify-center space-x-2">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    <span>Registrar Periodo Oficial</span>
                                </button>
                            </div>
                        </div>

                        <!-- Lista de Periodos -->
                        <div class="space-y-4">
                            <div class="flex justify-between items-center pl-2">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Selector de Periodos</h3>
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                            </div>
                            <div class="glass-panel p-2 rounded-2xl border border-slate-200">
                                <select id="periodo-global-select" class="w-full px-4 py-3 bg-transparent outline-none font-bold text-slate-700">
                                    <option value="">-- Ver todos los periodos --</option>
                                </select>
                            </div>

                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest pl-2 pt-4">Historial Reciente</h3>
                            <div id="periodos-list" class="space-y-3">
                                <!-- JS Generado -->
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SECCIÓN TARIFAS Y CONFIGURACIÓN -->
                <section id="section-edificios" class="tab-section hidden space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Tarifas y Cuotas de Edificio</h1>
                            <p class="text-slate-500 mt-1 font-medium">Configura de manera elegante los parámetros comerciales de facturación.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Edificio Activo:</span>
                            <div class="bg-white border border-slate-200 rounded-2xl px-4 py-2.5 shadow-sm flex items-center gap-2">
                                <i data-lucide="building" class="w-4 h-4 text-blue-500"></i>
                                <select id="config-edificio-select" class="bg-transparent outline-none font-bold text-slate-700 text-sm cursor-pointer pr-4">
                                    <option value="">Cargando edificios...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Estado Vacío o Cargando -->
                    <div id="config-empty-state" class="bg-white rounded-3xl p-16 text-center border border-slate-200 shadow-sm flex flex-col items-center justify-center space-y-4">
                        <div class="w-20 h-20 rounded-full bg-blue-50 flex items-center justify-center text-blue-500">
                            <i data-lucide="building-2" class="w-10 h-10 animate-bounce"></i>
                        </div>
                        <h2 class="text-xl font-bold text-slate-800">Selecciona un Edificio</h2>
                        <p class="text-slate-400 max-w-sm text-sm font-medium">Por favor, elige un edificio en el selector superior para visualizar y actualizar su configuración técnica.</p>
                    </div>

                    <!-- Contenedor Principal (Oculto hasta cargar) -->
                    <div id="config-main-container" class="hidden grid grid-cols-1 xl:grid-cols-12 gap-8">
                        
                        <!-- Columna Izquierda: Valores Vigentes y Formulario (7 cols) -->
                        <div class="xl:col-span-7 space-y-8">
                            
                            <!-- Panel de Valores Vigentes -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                
                                <!-- Costo Litro -->
                                <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500/10 to-blue-500/5 border border-indigo-100 rounded-3xl p-6 shadow-sm group hover:shadow-md transition-all">
                                    <div class="absolute top-4 right-4 text-indigo-500 opacity-20 group-hover:opacity-40 transition-all">
                                        <i data-lucide="droplet" class="w-12 h-12"></i>
                                    </div>
                                    <h4 class="text-xs font-black text-indigo-600 uppercase tracking-widest">Precio de Gas</h4>
                                    <div class="mt-4 flex items-baseline gap-1">
                                        <span class="text-3xl font-black text-indigo-950 font-mono" id="val-precio-litro">$0.00</span>
                                        <span class="text-xs font-bold text-indigo-500 font-mono">/ lt</span>
                                    </div>
                                    <p class="text-xs font-medium text-slate-500 mt-2">Costo por litro de gas consumido.</p>
                                </div>

                                <!-- Factor de Conversión -->
                                <div class="relative overflow-hidden bg-gradient-to-br from-amber-500/10 to-orange-500/5 border border-amber-100 rounded-3xl p-6 shadow-sm group hover:shadow-md transition-all">
                                    <div class="absolute top-4 right-4 text-amber-500 opacity-20 group-hover:opacity-40 transition-all">
                                        <i data-lucide="scaling" class="w-12 h-12"></i>
                                    </div>
                                    <h4 class="text-xs font-black text-amber-600 uppercase tracking-widest">Factor de Conversión</h4>
                                    <div class="mt-4 flex items-baseline gap-1">
                                        <span class="text-3xl font-black text-amber-950 font-mono" id="val-factor">1.00</span>
                                        <span class="text-xs font-bold text-amber-500 font-mono">m³ ➜ lt</span>
                                    </div>
                                    <p class="text-xs font-medium text-slate-500 mt-2">Factor para convertir m³ a litros.</p>
                                </div>

                                <!-- Cuota de Administración -->
                                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500/10 to-teal-500/5 border border-emerald-100 rounded-3xl p-6 shadow-sm group hover:shadow-md transition-all">
                                    <div class="absolute top-4 right-4 text-emerald-500 opacity-20 group-hover:opacity-40 transition-all">
                                        <i data-lucide="credit-card" class="w-12 h-12"></i>
                                    </div>
                                    <h4 class="text-xs font-black text-emerald-600 uppercase tracking-widest">Cuota de Admón.</h4>
                                    <div class="mt-4 flex items-baseline gap-1">
                                        <span class="text-3xl font-black text-emerald-950 font-mono" id="val-cuota-admin">$0.00</span>
                                        <span class="text-xs font-bold text-emerald-500 font-mono">/ mes</span>
                                    </div>
                                    <p class="text-xs font-medium text-slate-500 mt-2">Cuota administrativa fija mensual.</p>
                                </div>

                            </div>

                            <!-- Formulario de Modificación -->
                            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 space-y-6 relative overflow-hidden">
                                <div class="flex items-center space-x-3 mb-2">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-700">
                                        <i data-lucide="edit-3" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-extrabold text-slate-800">Actualizar Configuración Comercial</h3>
                                        <p class="text-xs text-slate-400 font-medium">Al guardar, se guardará un registro histórico para proteger la integridad contable.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                                    
                                    <!-- Input Precio -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider block">Precio de Gas (Litro)</label>
                                        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 focus-within:ring-2 focus-within:ring-blue-500 transition-all">
                                            <span class="text-slate-400 font-bold mr-2">$</span>
                                            <input type="number" step="0.01" min="0" id="input-precio-litro" class="bg-transparent outline-none w-full font-mono font-bold text-slate-700" placeholder="0.00">
                                        </div>
                                    </div>

                                    <!-- Input Factor -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider block">Factor de Conversión</label>
                                        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 focus-within:ring-2 focus-within:ring-blue-500 transition-all">
                                            <input type="number" step="0.0001" min="0.0001" id="input-factor" class="bg-transparent outline-none w-full font-mono font-bold text-slate-700" placeholder="1.0000">
                                            <span class="text-slate-400 font-bold ml-2">x</span>
                                        </div>
                                    </div>

                                    <!-- Input Cuota Admin -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider block">Cuota Administración</label>
                                        <div class="flex items-center bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3.5 focus-within:ring-2 focus-within:ring-blue-500 transition-all">
                                            <span class="text-slate-400 font-bold mr-2">$</span>
                                            <input type="number" step="0.01" min="0" id="input-cuota-admin" class="bg-transparent outline-none w-full font-mono font-bold text-slate-700" placeholder="0.00">
                                        </div>
                                    </div>

                                </div>

                                <div class="pt-4 flex items-center justify-between border-t border-slate-100 gap-4">
                                    <div class="hidden md:flex items-center gap-2 text-slate-400">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
                                        <span class="text-[10px] font-bold uppercase tracking-wider">Histórico seguro activado</span>
                                    </div>
                                    <button id="btn-save-config" class="w-full md:w-auto bg-slate-900 text-white px-8 py-4 rounded-2xl font-bold shadow-lg hover:bg-slate-800 transition-all active:scale-95 flex items-center justify-center space-x-2">
                                        <i data-lucide="save" class="w-4 h-4"></i>
                                        <span>Guardar Configuración</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Historial de Cambios / Línea de Tiempo (5 cols) -->
                        <div class="xl:col-span-5 space-y-6">
                            <div class="flex justify-between items-center pl-2">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Historial Auditado de Cambios</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-[10px] font-black text-slate-500 uppercase tracking-wider">Bitácora</span>
                            </div>

                            <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm overflow-hidden flex flex-col h-[520px]">
                                <!-- Pestañas de Historial -->
                                <div class="flex bg-slate-50 p-1 rounded-2xl border border-slate-100 mb-6 shrink-0">
                                    <button data-history-tab="precios" class="history-tab-link flex-1 text-center py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all bg-white text-slate-800 shadow-sm border border-slate-200">
                                        Gas
                                    </button>
                                    <button data-history-tab="factores" class="history-tab-link flex-1 text-center py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all text-slate-500 hover:text-slate-800">
                                        Factor
                                    </button>
                                    <button data-history-tab="cuotas" class="history-tab-link flex-1 text-center py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all text-slate-500 hover:text-slate-800">
                                        Cuota
                                    </button>
                                </div>

                                <!-- Contenedor de Listas (Scrollable) -->
                                <div class="flex-1 overflow-y-auto custom-scrollbar pr-1" id="history-lists-container">
                                    
                                    <!-- Lista de Precios Gas -->
                                    <div id="history-list-precios" class="history-pane space-y-4">
                                        <!-- Dinámico JS -->
                                    </div>

                                    <!-- Lista de Factores -->
                                    <div id="history-list-factores" class="history-pane hidden space-y-4">
                                        <!-- Dinámico JS -->
                                    </div>

                                    <!-- Lista de Cuotas -->
                                    <div id="history-list-cuotas" class="history-pane hidden space-y-4">
                                        <!-- Dinámico JS -->
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </section>

                <!-- SECCIÓN CONFIGURACIÓN COBRANZA -->
                <section id="section-cobranza" class="tab-section hidden space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Plantillas de Correo de Cobranza</h1>
                        <p class="text-slate-500 mt-1 font-medium">Configura dinámicamente los correos de estado de cuenta utilizando etiquetas.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        <!-- Configurador Izquierdo -->
                        <div class="lg:col-span-8 bg-white p-8 rounded-3xl shadow-sm border border-slate-200 space-y-6">
                            
                            <!-- Selector de Alcance -->
                            <div class="space-y-4 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-widest">Nivel de Configuración</h3>
                                <div class="flex items-center space-x-6">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="cobranza-nivel" value="global" checked class="w-4 h-4 text-indigo-600 bg-white border-slate-300 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm font-bold text-slate-700">Global</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="cobranza-nivel" value="edificio" class="w-4 h-4 text-indigo-600 bg-white border-slate-300 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm font-bold text-slate-700">Por Edificio</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="cobranza-nivel" value="departamento" class="w-4 h-4 text-indigo-600 bg-white border-slate-300 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm font-bold text-slate-700">Por Departamento</span>
                                    </label>
                                </div>

                                <!-- Selectores Dinámicos -->
                                <div id="cobranza-dynamic-selectors" class="hidden mt-4 pt-4 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div id="cobranza-sel-edificio-container" class="hidden">
                                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider block mb-2">Seleccione Edificio</label>
                                        <select id="cobranza-edificio-select" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></select>
                                    </div>
                                    <div id="cobranza-sel-depto-container" class="hidden">
                                        <label class="text-xs font-black text-slate-500 uppercase tracking-wider block mb-2">Seleccione Depto</label>
                                        <select id="cobranza-depto-select" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                            <option value="">Seleccione un edificio primero</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Editor de Plantilla -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Asunto del Correo</label>
                                    <input type="text" id="cobranza-asunto" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" />
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Cuerpo del Mensaje</label>
                                    <textarea id="cobranza-mensaje" rows="8" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"></textarea>
                                </div>
                            </div>

                            <button id="btn-save-cobranza" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg hover:bg-indigo-700 transition-all active:scale-95 flex items-center justify-center space-x-2">
                                <i data-lucide="save" class="w-5 h-5"></i>
                                <span>Guardar Plantilla</span>
                            </button>
                        </div>

                        <!-- Panel Derecho (Etiquetas) -->
                        <div class="lg:col-span-4 space-y-6">
                            <div class="bg-indigo-50 border border-indigo-100 rounded-3xl p-6">
                                <h3 class="text-sm font-bold text-indigo-900 flex items-center mb-4">
                                    <i data-lucide="tags" class="w-4 h-4 mr-2"></i>
                                    Etiquetas Disponibles
                                </h3>
                                <p class="text-xs text-indigo-700 mb-4 font-medium">Haz clic en cualquier etiqueta para copiarla al portapapeles y pégala en el asunto o mensaje.</p>
                                
                                <div class="flex flex-wrap gap-2" id="cobranza-tags-container">
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{nombre_titular}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{edificio}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{numero_departamento}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{saldo_actual}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{total_periodo}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{corte}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{mes_curso}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{mes_inicio_periodo}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{mes_fin_periodo}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{mes_siguiente}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{total_periodo_ant}}</button>
                                    <button class="cobranza-tag bg-white border border-indigo-200 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-colors">{{saldo_cierre_ant}}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="js/config-api.js?v=<?php echo time(); ?>"></script>
    <script src="js/config-dom.js?v=<?php echo time(); ?>"></script>
    <script src="js/configuracion.js?v=<?php echo time(); ?>"></script>
    <script>lucide.createIcons();</script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
