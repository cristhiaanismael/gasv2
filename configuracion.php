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
                    <button data-tab="departamentos" class="tab-link w-full flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-slate-50 text-slate-600">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Departamentos (Masivo)</span>
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

                <!-- SECCIÓN DEPARTAMENTOS -->
                <section id="section-departamentos" class="tab-section hidden space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Carga Masiva de Departamentos</h1>
                        <p class="text-slate-500 mt-1 font-medium">Pega una lista de departamentos para darlos de alta rápidamente.</p>
                    </div>
                    
                    <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 space-y-6 max-w-3xl">
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Seleccionar Edificio Destino</label>
                                <select id="bulk-edificio-select" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl outline-none font-bold">
                                    <!-- JS Generado -->
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Lista de Departamentos (Uno por línea)</label>
                                <textarea id="bulk-depto-list" rows="10" class="w-full px-4 py-4 bg-slate-900 text-green-400 font-mono text-sm rounded-2xl outline-none focus:ring-2 focus:ring-green-500/50" placeholder="101&#10;102&#10;201&#10;202..."></textarea>
                                <p class="text-[10px] text-slate-400 font-medium">Tip: Puedes copiar desde Excel o un bloc de notas directamente aquí.</p>
                            </div>

                            <button id="btn-save-bulk" class="w-full bg-green-600 text-white py-4 rounded-2xl font-bold shadow-lg hover:bg-green-700 transition-all active:scale-95">
                                Procesar y Dar de Alta
                            </button>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="js/configuracion.js"></script>
    <script>lucide.createIcons();</script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
