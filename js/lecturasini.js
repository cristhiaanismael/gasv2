/**
 * lecturasini.js
 * Orquestador de eventos e inicialización del módulo de Lecturas.
 */

$(document).ready(function () {
    // 1. Instanciar la lógica de negocio e inicializar datos
    const lecturador = new Lectura();
    
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
        if (files.length > 25) {
            showToast("Máximo 25 imágenes permitidas por lote", "error");
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

        if (files.length > 25) {
            showToast("Máximo 25 imágenes permitidas por lote", "error");
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
});
