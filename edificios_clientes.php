<?php require_once 'includes/auth_check.php';
$module_title = "Edificios y Clientes";
$active_menu_id = "infraestructura";
include 'includes/head.php';
?>
<body class="h-screen bg-[#F8FAFC] flex font-sans overflow-hidden text-gray-800">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <?php include 'includes/sidebar.php'; ?>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .tab-active { border-bottom: 2px solid #3b82f6; color: #1d4ed8; font-weight: 700; }
        .tab-inactive { color: #64748b; font-weight: 500; }
        .tab-inactive:hover { color: #111827; }
    </style>

    <!-- ÁREA DE TRABAJO PRINCIPAL -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <?php include 'includes/navbar.php'; ?>

        <div class="flex-1 flex flex-col overflow-hidden p-6 gap-6 bg-slate-50/50">
            <!-- Header section -->
            <div class="flex justify-between items-center mb-2">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight">Gestión Inmobiliaria</h2>
                    <p class="text-slate-500 font-medium text-sm mt-1">Administra edificios, departamentos y clientes asignados.</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex space-x-8 border-b border-gray-200">
                <button data-tab="edificios" class="pb-3 tab-active focus:outline-none transition-colors">
                    Edificios
                </button>
                <button data-tab="departamentos" class="pb-3 tab-inactive focus:outline-none transition-colors">
                    Departamentos y Clientes
                </button>
            </div>

            <!-- CONTENIDO TAB: EDIFICIOS -->
            <div id="tab-edificios" class="flex-1 flex flex-col overflow-hidden">
                <div class="flex justify-end mb-4">
                    <button id="btn-nuevo-edificio" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md shadow-blue-600/20 hover:bg-blue-700 transition-colors flex items-center">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Nuevo Edificio
                    </button>
                </div>
                
                <div class="flex-1 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="overflow-x-auto flex-1 custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                    <th class="p-4 w-10"></th>
                                    <th class="p-4">ID</th>
                                    <th class="p-4">Edificio / Nombre</th>
                                    <th class="p-4">Dirección</th>
                                    <th class="p-4">ID Cuenta</th>
                                    <th class="p-4">Orden</th>
                                    <th class="p-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-edificios" class="divide-y divide-gray-100 text-sm font-medium text-gray-700">
                                <!-- Data will be loaded via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- CONTENIDO TAB: DEPARTAMENTOS Y CLIENTES -->
            <div id="tab-departamentos" class="flex-1 flex flex-col overflow-hidden hidden">
                <div class="flex justify-between items-end mb-4">
                    <div class="w-1/3">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1 block">Seleccionar Edificio</label>
                        <select id="select-filtro-edificio" class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-sm font-bold text-gray-800 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            <!-- Edificios dinámicos -->
                        </select>
                    </div>
                    <div class="space-x-3 flex">
                        <button id="btn-migrar-deptos" class="bg-white text-slate-700 border border-slate-300 px-4 py-2 rounded-xl text-sm font-bold shadow-sm hover:bg-slate-50 transition-colors flex items-center">
                            <i data-lucide="arrow-right-left" class="w-4 h-4 mr-1"></i> Migrar Seleccionados
                        </button>
                        <button id="btn-nuevo-depto" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md shadow-indigo-600/20 hover:bg-indigo-700 transition-colors flex items-center">
                            <i data-lucide="plus" class="w-4 h-4 mr-1"></i> Nuevo Depto
                        </button>
                    </div>
                </div>

                <div class="flex-1 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="overflow-x-auto flex-1 custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                    <th class="p-4 w-12"><input type="checkbox" class="rounded border-gray-300 text-blue-600"></th>
                                    <th class="p-4">Depto</th>
                                    <th class="p-4">Cliente Asignado</th>
                                    <th class="p-4">Contacto</th>
                                    <th class="p-4">Convenio</th>
                                    <th class="p-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-deptos" class="divide-y divide-gray-100 text-sm font-medium text-gray-700">
                                <!-- Data will be loaded via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALES -->
    <div id="modal-overlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[60] hidden flex items-center justify-center transition-opacity">
        
        <!-- Modal Alta Edificio -->
        <div id="modal-edificio" class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden hidden transform transition-all scale-95 opacity-0 duration-300">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-xl font-black text-slate-800">Alta de Edificio</h3>
                <button class="btn-close-modal text-gray-400 hover:text-gray-600 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <input type="hidden" id="form-edificio-id" value="">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Nombre / Núm Edificio</label>
                        <input type="text" id="form-edificio-nombre" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Cuenta / Titular</label>
                        <select id="form-edificio-cuenta" class="w-full mt-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all font-medium">
                            <option value="1">1 - ZAIRA ABIGAIL VILLA GARCIA</option>
                            <option value="2">2 - LIZZETTE VILLA GARCIA</option>
                        </select>
                    </div>
                    <div class="col-span-2 grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="text-xs font-bold text-gray-500 uppercase">Calle</label>
                            <input type="text" id="form-edificio-calle" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Num Ext</label>
                            <input type="text" id="form-edificio-num-ext" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Colonia</label>
                        <input type="text" id="form-edificio-colonia" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Municipio</label>
                        <input type="text" id="form-edificio-municipio" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Código Postal</label>
                        <input type="text" id="form-edificio-codigo-postal" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Orden (Visualización)</label>
                        <input type="number" id="form-edificio-orden" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium" value="0">
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 flex justify-end space-x-3 bg-gray-50/50">
                <button class="btn-close-modal px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors">Cancelar</button>
                <button id="btn-guardar-edificio" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl shadow-md hover:bg-blue-700 transition-colors">Guardar Edificio</button>
            </div>
        </div>

        <!-- Modal Alta Departamento -->
        <div id="modal-departamento" class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden hidden transform transition-all scale-95 opacity-0 duration-300">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-xl font-black text-slate-800">Alta Depto</h3>
                <button class="btn-close-modal text-gray-400 hover:text-gray-600 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6">
                <label class="text-xs font-bold text-gray-500 uppercase">Núm de Departamento</label>
                <input type="text" id="form-depto-numero" class="w-full mt-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-lg font-black focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all text-center">
            </div>
            <div class="p-6 border-t border-gray-100 flex justify-end space-x-3 bg-gray-50/50">
                <button class="btn-close-modal px-4 py-2 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors">Cancelar</button>
                <button id="btn-guardar-depto" class="px-5 py-2 bg-indigo-600 text-white font-bold rounded-xl shadow-md hover:bg-indigo-700 transition-colors">Añadir</button>
            </div>
        </div>

        <!-- Modal Alta/Asignar Cliente -->
        <div id="modal-cliente" class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden hidden transform transition-all scale-95 opacity-0 duration-300">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-xl font-black text-slate-800">Datos del Cliente</h3>
                <button class="btn-close-modal text-gray-400 hover:text-gray-600 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <input type="hidden" id="form-cliente-depto-id" value="">
                <input type="hidden" id="form-cliente-id" value="">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Nombre</label>
                    <input type="text" id="form-cliente-nombre" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Apellido Paterno</label>
                        <input type="text" id="form-cliente-ape-pat" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Apellido Materno</label>
                        <input type="text" id="form-cliente-ape-mat" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Correo Electrónico Principal</label>
                    <input type="email" id="form-cliente-correo" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Correo Secundario</label>
                        <input type="email" id="form-cliente-correo-2" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Correo Administrativo</label>
                        <input type="email" id="form-cliente-correo-admin" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Teléfono Principal</label>
                        <input type="text" id="form-cliente-telefono" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Teléfono Secundario</label>
                        <input type="text" id="form-cliente-telefono-2" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Convenio</label>
                        <input type="text" id="form-cliente-convenio" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Referencia</label>
                        <input type="text" id="form-cliente-referencia" class="w-full mt-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 transition-all font-medium">
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 flex justify-end space-x-3 bg-gray-50/50">
                <button class="btn-close-modal px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors">Cancelar</button>
                <button id="btn-guardar-cliente" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl shadow-md hover:bg-blue-700 transition-colors">Guardar Cliente</button>
            </div>
        </div>

        <!-- Modal Migrar -->
        <div id="modal-migrar" class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden hidden transform transition-all scale-95 opacity-0 duration-300">
             <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-xl font-black text-slate-800">Migrar Deptos</h3>
                <button class="btn-close-modal text-gray-400 hover:text-gray-600 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-500 mb-4">Selecciona el edificio destino para los departamentos seleccionados.</p>
                <label class="text-xs font-bold text-gray-500 uppercase">Edificio Destino</label>
                <select id="form-migrar-destino" class="w-full mt-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold focus:bg-white focus:border-blue-500 transition-all">
                    <!-- Edificios dinámicos -->
                </select>
            </div>
            <div class="p-6 border-t border-gray-100 flex justify-end space-x-3 bg-gray-50/50">
                <button class="btn-close-modal px-4 py-2 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors">Cancelar</button>
                <button id="btn-ejecutar-migracion" class="px-5 py-2 bg-slate-800 text-white font-bold rounded-xl shadow-md hover:bg-slate-900 transition-colors">Ejecutar Migración</button>
            </div>
        </div>

        <!-- Modal QR -->
        <div id="modal-qr" class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden hidden transform transition-all scale-95 opacity-0 duration-300">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-xl font-black text-slate-800">Código QR del Depto</h3>
                <button class="btn-close-modal text-gray-400 hover:text-gray-600 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-8 flex flex-col items-center justify-center">
                <div id="qr-container" class="bg-white p-4 rounded-xl border-2 border-gray-100 shadow-sm mb-4 flex items-center justify-center min-h-[200px] min-w-[200px]">
                    <!-- QR se inyecta aquí -->
                </div>
                <p class="text-sm font-bold text-gray-600 uppercase tracking-widest mb-4" id="qr-label">DEPTO-XXX</p>
                
                <div class="w-full border-t border-gray-100 pt-4 mt-2">
                    <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block text-center mb-2">Tamaño de Etiqueta</label>
                    <div class="flex justify-center gap-2">
                        <label class="flex-1 flex flex-col items-center p-2 border border-gray-200 rounded-xl hover:bg-blue-50 cursor-pointer transition-colors group">
                            <input type="radio" name="individual_qr_size" value="150" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-gray-700 mt-1">Chico</span>
                        </label>
                        <label class="flex-1 flex flex-col items-center p-2 border border-gray-200 rounded-xl hover:bg-blue-50 cursor-pointer transition-colors group">
                            <input type="radio" name="individual_qr_size" value="300" checked class="text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-gray-700 mt-1">Mediano</span>
                        </label>
                        <label class="flex-1 flex flex-col items-center p-2 border border-gray-200 rounded-xl hover:bg-blue-50 cursor-pointer transition-colors group">
                            <input type="radio" name="individual_qr_size" value="600" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-xs font-bold text-gray-700 mt-1">Grande</span>
                        </label>
                    </div>
                </div>
                
                <button id="btn-print-individual-qr" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl text-sm font-bold shadow-md shadow-blue-600/10 hover:shadow-blue-600/20 transition-all flex items-center justify-center">
                    <i data-lucide="printer" class="w-4 h-4 mr-2"></i> Imprimir Etiqueta
                </button>
            </div>
        </div>

        <!-- Modal Bulk QR (Impresión) -->
        <div id="modal-bulk-qr" class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden hidden transform transition-all scale-95 opacity-0 duration-300">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-xl font-black text-slate-800">Imprimir QRs: <span id="bulk-edificio-name" class="text-indigo-600"></span></h3>
                <button class="btn-close-modal text-gray-400 hover:text-gray-600 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 space-y-5">
                <p class="text-sm text-gray-500">Se generará una plantilla lista para imprimir con los códigos QR de todos los departamentos. Selecciona el tamaño deseado:</p>
                
                <div class="space-y-3">
                    <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition-colors group">
                        <input type="radio" name="qr_size" value="150" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-gray-800 group-hover:text-blue-700">Chico (150x150 px)</span>
                            <span class="block text-xs text-gray-500">Ideal para stickers pequeños</span>
                        </div>
                    </label>
                    <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition-colors group">
                        <input type="radio" name="qr_size" value="300" checked class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-gray-800 group-hover:text-blue-700">Mediano (300x300 px)</span>
                            <span class="block text-xs text-gray-500">Tamaño estándar recomendado</span>
                        </div>
                    </label>
                    <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition-colors group">
                        <input type="radio" name="qr_size" value="600" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500">
                        <div class="ml-3">
                            <span class="block text-sm font-bold text-gray-800 group-hover:text-blue-700">Grande (600x600 px)</span>
                            <span class="block text-xs text-gray-500">Alta resolución para impresiones grandes</span>
                        </div>
                    </label>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 flex justify-end space-x-3 bg-gray-50/50">
                <button class="btn-close-modal px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition-colors">Cancelar</button>
                <button id="btn-print-qrs" class="flex items-center px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl shadow-md hover:bg-emerald-700 transition-colors">
                    <i data-lucide="printer" class="w-4 h-4 mr-2"></i> Preparar Impresión
                </button>
            </div>
        </div>

    </div>

    <!-- Div oculto para generar QRs temporales -->
    <div id="hidden-qr-generator" style="display:none;"></div>

    <!-- Librerías Externas y Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <script src="js/EdificiosAPI.js?v=<?= time(); ?>"></script>
    <script src="js/EdificiosUI.js?v=<?= time(); ?>"></script>
    <script src="js/edificios_clientes.js?v=<?= time(); ?>"></script>



    <?php include 'includes/footer.php'; ?>
</body>
</html>
