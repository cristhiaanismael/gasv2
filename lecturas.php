<?php require_once 'includes/auth_check.php';
$module_title = "GasData Capture";
$active_menu_id = "lecturas";
include 'includes/head.php';
?>
<body class="h-screen bg-[#F8FAFC] flex font-sans overflow-hidden text-gray-800">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <?php include 'includes/sidebar.php'; ?>
    <style>
        .badge-edition {
            background-color: #f3e8ff;
            color: #7e22ce;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            border: 1px solid #d8b4fe;
            animation: pulse-purple 2s infinite;
        }
        @keyframes pulse-purple {
            0% { box-shadow: 0 0 0 0 rgba(168, 85, 247, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(168, 85, 247, 0); }
            100% { box-shadow: 0 0 0 0 rgba(168, 85, 247, 0); }
        }
        .inspector-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .depto-title {
            font-size: 1.1rem;
            font-weight: 900;
            color: #1e293b;
        }
        .mode-edition-warning {
            background-color: rgba(251, 191, 36, 0.22) !important;
            border-left: 5px solid #fbbf24 !important;
            transition: all 0.3s ease;
        }
    </style>

    <!-- ÁREA DE TRABAJO PRINCIPAL -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <?php include 'includes/navbar.php'; ?>

        <!-- Contenido Principal: Triaje -->
        <div class="flex-1 flex flex-col overflow-hidden p-6 gap-6 bg-slate-50/50">

            <!-- ESTADO 1: DROPZONE -->
            <div id="upload-state" class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-3xl bg-white transition-all duration-300 relative group overflow-hidden">
                <div class="absolute inset-0 bg-blue-50/50 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10 flex flex-col items-center text-center max-w-lg p-8">
                    <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mb-6 shadow-sm border border-blue-100 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="image-plus" class="w-10 h-10 text-blue-600"></i>
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 mb-3">Sube todas las lecturas</h3>
                    <p class="text-gray-500 mb-8 font-medium">Arrastra aquí todas las fotos de los medidores para iniciar el registro secuencial de lecturas.</p>
                    <input type="file" id="real-file-input" class="hidden" multiple accept="image/*">
                    <button id="main-upload-btn" class="bg-[#0F172A] text-white px-8 py-3.5 rounded-xl text-sm font-bold shadow-lg shadow-slate-900/20 hover:bg-gray-800 hover:shadow-xl hover:-translate-y-0.5 transition-all active:translate-y-0">
                        Explorar Archivos o Arrastrar
                    </button>
                    <p class="text-xs text-gray-400 mt-4 font-semibold uppercase tracking-widest">Soporta JPG, PNG (Max 25 imágenes por lote)</p>
                </div>
            </div>

            <!-- ESTADO 2: WORKSPACE -->
            <div id="workspace-state" class="hidden flex-1 flex overflow-hidden rounded-2xl shadow-sm border border-gray-200 bg-white">
                <!-- Panel Izquierdo: Cola -->
                <aside class="w-80 border-r border-gray-200 bg-gray-50/50 flex flex-col flex-shrink-0">
                    <div class="p-4 border-b border-gray-200 bg-white">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex justify-between items-center">
                            Cola de Revisión
                            <span id="queue-counter" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">0/0</span>
                        </h3>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-3 overflow-hidden">
                            <div id="queue-progress" class="bg-blue-600 h-1.5 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                    <div id="image-queue-container" class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar"></div>
                </aside>

                <!-- Panel Derecho: Inspector -->
                <main class="flex-1 flex flex-col bg-white relative">
                    <div id="empty-inspection" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-50 z-10">
                        <i data-lucide="check-square" class="w-16 h-16 text-green-400 mb-4"></i>
                        <h3 class="text-2xl font-black text-gray-900">¡Lote Procesado!</h3>
                        <p class="text-gray-500 font-medium">Has revisado todas las evidencias.</p>
                        <button onclick="location.reload()" class="mt-6 text-blue-600 font-bold hover:underline">Subir nuevo lote</button>
                    </div>

                    <div id="active-inspection" class="flex-1 flex overflow-hidden">
                        <!-- Visor -->
                        <div class="w-1/2 bg-slate-900 p-6 flex flex-col relative group">
                            <div class="flex-1 rounded-xl overflow-hidden bg-black flex items-center justify-center relative shadow-2xl border border-gray-800">
                                <img id="inspector-image" src="" alt="Evidencia" class="max-w-full max-h-full object-contain">
                            </div>
                            <p class="text-gray-400 text-xs text-center mt-4 font-medium" id="inspector-filename">IMG_20260419_001.jpg</p>
                        </div>

                        <!-- Formulario -->
                        <div id="inspector-form" class="w-1/2 p-8 overflow-y-auto custom-scrollbar flex flex-col justify-center transition-colors duration-500">
                                <div id="qr-detected-badge" class="hidden flex items-center px-2 py-1 bg-green-100 text-green-700 text-[10px] font-black uppercase rounded-md mb-3 w-max animate-pulse">
                                    <!-- Contenido dinámico -->
                                </div>
                                
                                <div id="header-registration" class="p-4 -mx-8 -mt-8 mb-6 transition-all duration-300">
                                    <h2 class="text-3xl font-black text-gray-900 leading-tight">Registro de Lectura</h2>
                                    <p class="text-gray-500 text-sm mt-1 mb-4">Ingresa los datos correspondientes a la evidencia fotográfica.</p>

                                    <div class="inspector-header">
                                        <h2 class="depto-title">Depto: <span id="depto-number">---</span></h2>
                                        <span id="badge-exists" class="badge-edition" style="display: none;">LECTURA YA REGISTRADA</span>
                                    </div>
                                </div>
                            <div class="space-y-5">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-1.5 relative z-10 focus-within:z-40">
                                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Edificio</label>
                                        <div class="relative group">
                                            <select id="input-edificio" placeholder="Seleccionar edificio..." autocomplete="off"></select>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5 relative z-10 focus-within:z-40">
                                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Departamento</label>
                                        <div class="relative group">
                                            <select id="input-depto" placeholder="Seleccionar depto..." autocomplete="off"></select>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-1.5 pt-2">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Lectura del Medidor</label>
                                    <div class="relative group">
                                        <input type="number" id="input-lectura" class="w-full px-5 py-4 text-3xl font-black text-gray-900 bg-blue-50/50 border-2 border-blue-200 rounded-xl focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all shadow-sm" placeholder="0.0">
                                        <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors">m³</div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-xs text-gray-500 font-medium flex items-center">
                                            <i data-lucide="history" class="w-3.5 h-3.5 mr-1"></i> Lec. Anterior: <span class="font-bold text-gray-700 ml-1" id="lectura-anterior">--</span>
                                        </span>
                                        <span class="text-xs text-green-600 font-bold bg-green-50 px-2 py-0.5 rounded border border-green-100" id="consumo-calculado">0 m³ cons.</span>
                                    </div>
                                </div>

                                <!-- Resumen de Cálculo en Tiempo Real -->
                                <div class="mt-8 p-5 bg-slate-50 rounded-2xl border border-slate-100 grid grid-cols-2 gap-y-5 gap-x-4 relative">
                                    <div class="absolute top-0 right-0 p-2 opacity-5 pointer-events-none">
                                        <i data-lucide="calculator" class="w-12 h-12 text-slate-900"></i>
                                    </div>
                                    <div class="relative z-10">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Litros</p>
                                        <p id="prev-litros" class="text-sm font-black text-slate-700">0.00 Lt</p>
                                    </div>
                                    <div class="relative z-10 text-right">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Monto Gas</p>
                                        <p id="prev-monto" class="text-sm font-black text-slate-700">$0.00</p>
                                    </div>
                                    
                                    <!-- Fila única para Total con Tooltip elegante -->
                                    <div class="col-span-2 pt-3 border-t border-slate-200/50 mt-1 flex items-center justify-between relative">
                                        <div class="group relative">
                                            <div class="flex items-center space-x-1.5 cursor-help text-slate-400 hover:text-blue-500 transition-colors">
                                                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                                                <span class="text-[10px] font-bold uppercase tracking-tight">Ver desglose</span>
                                            </div>
                                            
                                            <!-- TOOLTIP PREMIUM -->
                                            <div class="absolute bottom-full left-0 mb-3 w-52 bg-slate-900 text-white rounded-2xl p-4 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 translate-y-2 group-hover:translate-y-0 z-50 border border-slate-700/50 backdrop-blur-md">
                                                <div class="space-y-3">
                                                    <div class="flex justify-between items-center border-b border-slate-700/50 pb-2">
                                                        <span class="text-[9px] font-black text-slate-400 uppercase">Resumen Financiero</span>
                                                        <i data-lucide="receipt" class="w-3 h-3 text-blue-400"></i>
                                                    </div>
                                                    <div class="flex justify-between items-center text-xs">
                                                        <span class="text-slate-400 font-medium">Cuota Admin</span>
                                                        <span id="tt-cuota" class="font-bold text-slate-100">$0.00</span>
                                                    </div>
                                                    <div class="flex justify-between items-center text-xs">
                                                        <span class="text-slate-400 font-medium">Adeudos</span>
                                                        <span id="tt-adeudo" class="font-bold text-rose-400">$0.00</span>
                                                    </div>
                                                    <div class="flex justify-between items-center text-xs">
                                                        <span class="text-slate-400 font-medium">Saldo a Favor</span>
                                                        <span id="tt-favor" class="font-bold text-green-400">$0.00</span>
                                                    </div>
                                                </div>
                                                <!-- Flecha del tooltip -->
                                                <div class="absolute top-full left-6 -mt-1 w-3 h-3 bg-slate-900 transform rotate-45 border-r border-b border-slate-700/50"></div>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <p class="text-[9px] font-black text-indigo-500 uppercase tracking-widest mb-0.5">Total a Pagar</p>
                                            <p id="prev-total" class="text-2xl font-black text-indigo-600 tracking-tighter">$0.00</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-10 flex space-x-3 pt-6 border-t border-gray-100">
                                <button id="btn-descartar" class="px-5 py-3.5 rounded-xl border-2 border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition-colors">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                                <button id="btn-guardar" class="flex-1 flex items-center justify-center space-x-2 bg-blue-600 text-white px-6 py-3.5 rounded-xl font-bold shadow-lg shadow-blue-600/30 hover:bg-blue-700 transition-all active:scale-95">
                                    <span>Confirmar y Siguiente</span>
                                    <div class="flex items-center space-x-1 ml-2 opacity-80">
                                        <kbd class="bg-white/20 px-1.5 py-0.5 rounded text-[10px] font-mono border border-white/30">ENTER</kbd>
                                        <i data-lucide="corner-down-left" class="w-3.5 h-3.5"></i>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Tom Select CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        /* Ajustes para integrar Tom Select con el tema premium */
        .ts-control {
            @apply !bg-gray-50 !border-gray-200 !rounded-xl !py-2.5 !px-4 !shadow-none !transition-colors !text-sm !font-bold !text-gray-800;
        }
        .ts-control input {
            @apply !text-sm !font-bold !text-gray-800;
        }
        .focus .ts-control {
            @apply !bg-white !ring-2 !ring-blue-500 !border-transparent;
        }
        .ts-dropdown {
            @apply !rounded-xl !shadow-2xl !border-gray-100 !mt-2 !animate-in !fade-in !slide-in-from-top-2 !duration-200;
        }
        .ts-dropdown .active {
            @apply !bg-blue-600 !text-white;
        }
        .ts-dropdown .option {
            @apply !py-3 !px-4 !text-sm !font-medium;
        }
    </style>

    <!-- SCRIPTS ESPECÍFICOS DEL MÓDULO -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script src="js/lecturas_config.js"></script>
    <script src="js/lecturas.js"></script>
    <script src="js/lecturasini.js"></script>

    <?php include 'includes/footer.php'; ?>
</body>
