/**
 * lecturasini.js
 * Orquestador de eventos e inicialización del módulo de Lecturas.
 */

$(document).ready(function () {
    // 1. Instanciar la lógica de negocio e inicializar datos
    const lecturador = new Lectura();
    
    // --- LÓGICA DE LUPA (MAGNIFIER ZOOM) ---
    const imgVisor = document.getElementById('inspector-image');
    const imgContainer = document.getElementById('image-magnifier-container');
    
    if (imgVisor && imgContainer) {
        // Al mover el ratón, calculamos el porcentaje X y Y para enfocar
        imgContainer.addEventListener('mousemove', function(e) {
            const rect = imgContainer.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const xPercent = (x / rect.width) * 100;
            const yPercent = (y / rect.height) * 100;
            
            // Movemos el origen de la transformación para que apunte a donde está el ratón
            imgVisor.style.transformOrigin = `${xPercent}% ${yPercent}%`;
        });

        // Al entrar el ratón, se aplica la escala
        imgContainer.addEventListener('mouseenter', function() {
            imgVisor.style.transform = 'scale(2.5)';
        });

        // Al salir, se restaura
        imgContainer.addEventListener('mouseleave', function() {
            imgVisor.style.transform = 'scale(1)';
            // Volvemos a poner el origen al centro suavemente
            setTimeout(() => { imgVisor.style.transformOrigin = 'center center'; }, 150);
        });
        
        window.resetImageZoom = () => {
            imgVisor.style.transform = 'scale(1)';
            imgVisor.style.transformOrigin = 'center center';
        };
    } else {
        window.resetImageZoom = () => {};
    }

    // --- INICIALIZACIÓN SELECTORES (TOM SELECT) ---
    const tsEdificio = new TomSelect('#input-edificio', {
        ...LECTURAS_CONFIG.TOM_SELECT.edificio,
        onChange: async (id) => {
            if (!id) {
                tsDepto.clear();
                tsDepto.clearOptions();
                tsDepto.disable();
                return;
            }
            tsDepto.clear();
            tsDepto.clearOptions();
            tsDepto.disable();
            const deptos = await lecturador.fetchDepartamentos(id);
            tsDepto.enable();
            tsDepto.addOptions(deptos);
            tsDepto.focus();
        }
    });

    const tsDepto = new TomSelect('#input-depto', {
        ...LECTURAS_CONFIG.TOM_SELECT.departamento,
        render: {
            option: function(data, escape) {
                const bId = tsEdificio.getValue();
                const bData = tsEdificio.options[bId];
                const bName = bData ? bData.num_edificio : '---';
                return LECTURAS_CONFIG.TOM_SELECT.departamento.render.option(data, escape, bName);
            }
        },
        onChange: (id) => {
            if (!id) return;
            const item = lecturador.uploadQueue[lecturador.activeIndex];
            if (item) {
                const bId = tsEdificio.getValue();
                const bData = tsEdificio.options[bId];
                const dData = tsDepto.options[id];
                
                item.ocr.edificio = bData.num_edificio;
                item.ocr.depto = dData.num_departamento;
                item.ocr.deptId = id;
                lecturador.linkData(id, item);
                $('#input-lectura').focus();
            }
        }
    });

    // Exponer para sincronización desde lecturador.loadActiveInspection()
    window.Selectors = { tsEdificio, tsDepto };

    // Cargar datos iniciales
    lecturador.fetchEdificios().then(data => {
        tsEdificio.addOptions(data);
    });

    // Registrar listener de selección global de edificio (Sidebar)
    document.addEventListener('building-selected', (e) => {
        tsEdificio.setValue(e.detail.id);
    });

    // 2. Handlers de Eventos de Carga
    const $fileInput = $('#real-file-input');
    const $uploadBtn = $('#main-upload-btn');
    const $uploadZone = $('#upload-state');

    $uploadBtn.on('click', () => $fileInput.click());

    $fileInput.on('change', function (e) {
        const files = e.target.files;
        if (files.length > 40) {
            showToast("Máximo 40 imágenes permitidas por lote", "error");
            $fileInput.val(''); // Limpiar selección
            return;
        }

        if (files.length > 0) {
            $uploadZone.addClass('hidden');
            $('#workspace-state').removeClass('hidden');
            lecturador.processFiles(files);
        }
    });

    // Soporte para Drag & Drop nativo
    $uploadZone.on('dragover', (e) => {
        e.preventDefault();
        $uploadZone.addClass('border-blue-500 bg-blue-50/50');
    }).on('dragleave', () => {
        $uploadZone.removeClass('border-blue-500 bg-blue-50/50');
    }).on('drop', (e) => {
        e.preventDefault();
        $uploadZone.removeClass('border-blue-500 bg-blue-50/50');
        const files = e.originalEvent.dataTransfer.files;

        if (files.length > 40) {
            showToast("Máximo 40 imágenes permitidas por lote", "error");
            return;
        }

        if (files.length > 0) {
            $uploadZone.addClass('hidden');
            $('#workspace-state').removeClass('hidden');
            lecturador.processFiles(files);
        }
    });

    // Delegación de eventos para items de la cola
    $(document).on('click', '.queue-item', function () {
        const index = $(this).data('index');
        lecturador.selectIndex(index);
    });

    // Input de lectura
    $('#input-lectura').on('input', function() {
        lecturador.calcularConsumo();
    });

    // Botón Descartar
    $('#btn-descartar').on('click', function () {
        lecturador.descartar();
    });

    // Botón Forzar Cálculo
    $(document).on('click', '#btn-forzar-calculo', function () {
        const item = lecturador.uploadQueue[lecturador.activeIndex];
        if (item) {
            item.ocr.forzarCalculo = true;
            lecturador.calcularConsumo();
        }
    });

    // Botón Confirmar y Siguiente
    $('#btn-guardar').on('click', function () {
        lecturador.saveAndNext();
    });

    // Atajos de teclado (Enter para guardar)
    $(document).on('keydown', function (e) {
        if (!$('#workspace-state').hasClass('hidden') && 
            $('#active-inspection').is(':visible') && 
            e.key === 'Enter') {
            e.preventDefault();
            lecturador.saveAndNext();
        }
    });

    // Exponer a global para depuración (opcional)
    window.AppLecturas = lecturador;

    // --- LÓGICA DEL PANEL DE PROGRESO ---
    const $btnProgreso = $('#btn-open-progress');
    const $panelProgreso = $('#progress-panel');
    const $overlayProgreso = $('#progress-overlay');
    const $btnCloseProgreso = $('#close-progress-panel');

    function toggleProgressPanel(show) {
        if (show) {
            $overlayProgreso.removeClass('hidden');
            // Timeout pequeño para la animación
            setTimeout(() => {
                $overlayProgreso.removeClass('opacity-0');
                $panelProgreso.removeClass('translate-x-full');
            }, 10);
            cargarProgreso();
        } else {
            $overlayProgreso.addClass('opacity-0');
            $panelProgreso.addClass('translate-x-full');
            setTimeout(() => {
                $overlayProgreso.addClass('hidden');
            }, 300);
        }
    }

    $btnProgreso.on('click', () => toggleProgressPanel(true));
    $btnCloseProgreso.on('click', () => toggleProgressPanel(false));
    $overlayProgreso.on('click', () => toggleProgressPanel(false));

    async function cargarProgreso() {
        $('#progress-loader').removeClass('hidden');
        $('#progress-buildings').addClass('hidden').empty();

        try {
            const data = await lecturador.fetchProgresoGeneral();

            $('#progress-periodo').text(data.periodo);

            if (data.progreso && data.progreso.length > 0) {
                let html = '';
                data.progreso.forEach(edif => {
                    const total = parseInt(edif.total_deptos);
                    const conLectura = parseInt(edif.deptos_con_lectura);
                    const porcentaje = total > 0 ? Math.round((conLectura / total) * 100) : 0;
                    
                    let colorClass = porcentaje === 100 ? 'bg-green-500' : (porcentaje > 0 ? 'bg-blue-500' : 'bg-gray-300');
                    let textClass = porcentaje === 100 ? 'text-green-600' : 'text-slate-600';

                    html += `
                        <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm hover:shadow-md transition-shadow cursor-pointer edificio-progreso" data-id="${edif.id_edificio}">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-bold text-slate-800 text-sm flex items-center">
                                    <i data-lucide="building-2" class="w-4 h-4 mr-1 text-slate-400"></i> ${edif.nombre_edificio}
                                </h4>
                                <span class="text-xs font-black ${textClass}">${porcentaje}%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-1.5 mb-2 overflow-hidden">
                                <div class="${colorClass} h-1.5 rounded-full" style="width: ${porcentaje}%"></div>
                            </div>
                            <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                <span>${conLectura} / ${total} Deptos</span>
                                <span class="text-blue-500 flex items-center group-hover:underline">Ver Detalle <i data-lucide="chevron-down" class="w-3 h-3 ml-0.5"></i></span>
                            </div>
                            <div class="detalle-deptos hidden mt-3 pt-3 border-t border-slate-100 grid grid-cols-4 gap-2" id="detalle-edif-${edif.id_edificio}">
                                <!-- Se llena dinámicamente -->
                                <div class="col-span-4 text-center py-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin text-slate-400 mx-auto"></i></div>
                            </div>
                        </div>
                    `;
                });
                
                $('#progress-buildings').html(html).removeClass('hidden');
                lucide.createIcons();
            } else {
                $('#progress-buildings').html('<div class="text-center p-4 text-slate-500 font-medium">No hay edificios registrados.</div>').removeClass('hidden');
            }
        } catch (error) {
            console.error(error);
            showToast('Error al obtener progreso', 'error');
            $('#progress-buildings').html('<div class="text-center p-4 text-red-500 font-medium">Error al cargar datos.</div>').removeClass('hidden');
        } finally {
            $('#progress-loader').addClass('hidden');
        }
    }

    $(document).on('click', '.edificio-progreso', async function() {
        const idEdificio = $(this).data('id');
        const $detalleContenedor = $(`#detalle-edif-${idEdificio}`);
        const icon = $(this).find('i[data-lucide="chevron-down"], i[data-lucide="chevron-up"]');

        if ($detalleContenedor.hasClass('hidden')) {
            $detalleContenedor.removeClass('hidden');
            icon.attr('data-lucide', 'chevron-up');
            lucide.createIcons();

            // Evitar re-fetch si ya se cargó
            if ($detalleContenedor.find('.depto-badge').length > 0) return;

            try {
                const deptos = await lecturador.fetchProgresoEdificio(idEdificio);

                let html = '';
                deptos.forEach(d => {
                    const tiene = parseInt(d.tiene_lectura) === 1;
                    const bgClass = tiene ? 'bg-green-100 border-green-200 text-green-700' : 'bg-slate-100 border-slate-200 text-slate-500';
                    const iconHtml = tiene ? '<i data-lucide="check" class="w-3 h-3 ml-1"></i>' : '';
                    
                    html += `
                        <div class="depto-badge flex items-center justify-center border rounded-md py-1 px-1 text-[10px] font-bold ${bgClass}">
                            ${d.num_departamento} ${iconHtml}
                        </div>
                    `;
                });
                $detalleContenedor.html(html);
                lucide.createIcons();
            } catch (err) {
                $detalleContenedor.html('<div class="col-span-4 text-center text-xs text-red-500">Error al cargar detalle</div>');
            }
        } else {
            $detalleContenedor.addClass('hidden');
            icon.attr('data-lucide', 'chevron-down');
            lucide.createIcons();
        }
    });

});
