/**
 * historialini.js
 * Orquestador de eventos e inicialización del módulo de Historial.
 */

$(document).ready(function () {
    // 1. Instanciar la lógica de negocio (ÚNICA INSTANCIA GLOBAL)
    // Forzamos re-instanciación para asegurar capturar nuevos métodos del prototipo tras reloads parciales
    window.historial = new Historial();

    // 2. BLOQUEO DE DOBLE EJECUCIÓN
    if (window.historialInitialized) {
        console.warn("Módulo Historial ya estaba en ejecución. Bloqueando duplicidad de eventos.");
        return; 
    }

    // Marcamos como inicializado ANTES de disparar nada
    window.historialInitialized = true;
    console.log("Historial: Iniciando secuencia única de eventos y peticiones.");

    // Carga inicial: edificios → seleccionar primero
    window.historial.fetchEdificios().then(data => {
        if (data.length > 0) {
            window.historial.selectBuilding(data[0].num_edificio, data[0].id_edificio);
        } else {
            window.historial.renderAll();
        }
    });

    // 3. VINCULAR EVENTOS SOLO UNA VEZ
    bindHistorialEvents(window.historial);
});

/**
 * Encapsular todos los eventos con delegación única
 */
function bindHistorialEvents(historial) {
    console.log("Estableciendo delegación de eventos única...");

    // Eventos de Navegación y Búsqueda
    $(document).off('click', '.building-select-btn').on('click', '.building-select-btn', function () {
        historial.selectBuilding($(this).data('edificio'), $(this).data('id'));
    });

    document.addEventListener('building-selected', (e) => {
        historial.selectBuilding(e.detail.name, e.detail.id);
    });

    // Buscador Inteligente AI (OmniSearch)
    let searchTimer;
    $(document).on('input', '#search-omni-input', function() {
        clearTimeout(searchTimer);
        const q = $(this).val().trim();
        searchTimer = setTimeout(() => {
            historial.performOmniSearch(q);
        }, 300);
    });

    // Cerrar dropdown al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#search-omni-container, #search-omni-results').length) {
            $('#search-omni-results').addClass('hidden');
        }
    });

    // Acción al pulsar un resultado
    $(document).on('click', '.omni-result-item', function() {
        const id = $(this).data('id');
        const isLocal = $(this).data('local');
        
        $('#search-omni-results').addClass('hidden');
        
        if (isLocal) {
            // Ir al detalle local
            historial.openDetailPanel(id);
            // Autofoco/Scroll a la fila
            const $row = $(`button.btn-open-detail[data-id="${id}"]`).closest('tr');
            if ($row.length) {
                $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                $row.addClass('bg-blue-100 ring-2 ring-blue-400');
                setTimeout(() => $row.removeClass('bg-blue-100 ring-2 ring-blue-400'), 2000);
            }
        } else {
            // Mostrar info discreta para otro edificio
            historial.showToast('Info Global', `Esta unidad pertenece al edificio: ${$(this).data('building-name')}`, 'info');
            // Podríamos implementar un preview modal si se requiere
        }
    });

    $(document).on('input', '#search-building-input', function () {
        const term = $(this).val().toLowerCase();
        const filtered = historial.edificios.filter(ed => ed.num_edificio.toLowerCase().includes(term));
        const container = $('#building-list-container');
        const html = filtered.map(ed => {
            const name = ed.num_edificio;
            const isSelected = historial.selectedBuilding === name;
            return `
                <button class="building-select-btn w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all text-left ${isSelected ? 'bg-blue-50 border-blue-200 shadow-sm border' : 'hover:bg-gray-50 border border-transparent hover:border-gray-200'}" data-edificio="${name}" data-id="${ed.id_edificio}">
                    <div class="flex items-center truncate mr-2">
                        <div class="w-2 h-2 rounded-full mr-3 flex-shrink-0 ${isSelected ? 'bg-blue-500' : 'bg-gray-300'}"></div>
                        <span class="text-sm truncate ${isSelected ? 'font-bold text-blue-900' : 'font-medium text-gray-600'}">${name}</span>
                    </div>
                </button>`;
        }).join('');
        container.html(html || '<div class="p-4 text-center text-gray-400 text-sm">No hay edificios</div>');
        lucide.createIcons();
    });

    // Gestión de Paneles y Modales
    $(document).on('click', '.btn-open-detail', function () {
        historial.openDetailPanel($(this).data('id'));
    });

    $(document).on('click', '#close-panel-btn, #panel-backdrop', function () {
        historial.closeDetailPanel();
    });

    $(document).on('click', '#btn-update-reading', function () {
        historial.saveReadingUpdate();
    });

    $(document).on('click', HISTORIAL_CONFIG.PANEL.btnPayment, () => {
        historial.submitPayment();
    });

    // --- EVIDENCIA Y FOTO ---
    $(document).on('click', '.btn-view-evidence', function () {
        historial.viewEvidence($(this).data('foto'));
    });

    $(document).on('click', '#btn-close-evidence, #btn-close-evidence-footer, #modal-evidence-backdrop', function () {
        historial.closeEvidenceModal();
    });

    // Modal Histórico Expandido
    $(document).on('click', HISTORIAL_CONFIG.MODAL_HISTORY.btnExpand, () => historial.openHistoryModal());
    $(document).on('click', HISTORIAL_CONFIG.MODAL_HISTORY.btnClose + ',' + HISTORIAL_CONFIG.MODAL_HISTORY.btnCloseFooter + ',' + HISTORIAL_CONFIG.MODAL_HISTORY.backdrop, () => historial.closeHistoryModal());

    // Otros Controles del Panel
    $(document).on('input', '#panel-input-lec-act, #panel-input-add, #panel-input-ajuste', function () {
        historial.updatePanelTotal();
    });

    $(document).on('click', '#btn-recalculate-total', function () {
        historial.recalculateTotal();
    });

    $(document).on('click', '#btn-log-abonos, #btn-log-adeudos', () => historial.openMovementsLog());
    $(document).on('click', '#btn-close-log-modal, #btn-close-log-footer, #modal-log-backdrop', () => historial.closeMovementsLog());

    $(document).on('click', '.btn-delete-mov', function() {
        historial.deleteMovement($(this).data('id'));
    });

    // Menú Contextual PDF
    let currentCtxId = null;
    $(document).on('contextmenu', '.btn-pdf-context', function (e) {
        e.preventDefault();
        currentCtxId = $(this).data('id');
        const $menu = $('#global-pdf-context-menu');
        const posX = e.pageX;
        const posY = e.pageY;
        const windowHeight = $(window).height();
        const scrollTop = $(window).scrollTop();
        const menuHeight = $menu.outerHeight() || 150;
        let finalY = posY;
        if (posY + menuHeight > windowHeight + scrollTop) finalY = posY - menuHeight;

        $menu.css({ top: finalY, left: posX - 160 }).removeClass('hidden').addClass('animate-in fade-in zoom-in-95');
        lucide.createIcons();
    });

    $(document).on('click', function () { $('#global-pdf-context-menu').addClass('hidden'); });

    $(document).on('change', '#master-email-toggle', function() {
        const isActive = $(this).is(':checked');
        const $label = $('#master-switch-desc-label');
        if (isActive) {
            $label.text('Activo').removeClass('text-orange-600').addClass('text-green-600');
            historial.showToast('Envío Activado', 'Los recibos se enviarán automáticamente.', 'success');
        } else {
            $label.text('Pausado').removeClass('text-green-600').addClass('text-orange-600');
            historial.showToast('Envío Pausado', 'Generación silenciosa activada.', 'warning');
        }
    });

    $(document).on('click', '#btn-help-master-switch', function() {
        // ... Lógica de ayuda ...
        $('#modal-global-help').removeClass('hidden');
        setTimeout(() => {
            $('#modal-help-backdrop').removeClass('opacity-0').addClass('opacity-100');
            $('#modal-help-content').removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
            lucide.createIcons();
        }, 10);
    });

    $(document).on('click', '#btn-close-help-modal, #btn-close-help-modal-footer, #modal-help-backdrop', function() {
        $('#modal-help-backdrop').removeClass('opacity-100').addClass('opacity-0');
        $('#modal-help-content').removeClass('scale-100 opacity-100').addClass('scale-95 opacity-0');
        setTimeout(() => $('#modal-global-help').addClass('hidden'), 300);
    });

    // PDF Actions
    $(document).on('click', '.btn-pdf-context', function (e) {
        const id = $(this).data('id');
        const isMasterOn = $('#master-email-toggle').is(':checked');
        const exists = $(this).data('exists') == '1' || $(this).data('exists') === true;
        if (exists) {
            window.open(`${API_BASE_URL}historial/pdf/${id}`, '_blank');
        } else {
            historial.executePdfAction(id, { nosend: !isMasterOn });
        }
    });

    $(document).on('click', '#ctx-ver-pdf', function () {
        if (currentCtxId) window.open(`${API_BASE_URL}historial/pdf/${currentCtxId}`, '_blank');
    });

    $(document).on('click', '#ctx-solo-reenviar', function () {
        if (!currentCtxId) return;
        if (!$('#master-email-toggle').is(':checked')) {
            historial.showToast('Bloqueado', 'Envío global desactivado.', 'error');
            return;
        }
        historial.showToast('Enviando...', 'Reenviando recibo...', 'info');
        $.ajax({
            url: `apis_marvi/public/api/historial/notificar/${currentCtxId}`,
            type: 'GET',
            success: (res) => historial.showToast('¡Éxito!', res.message, 'success'),
            error: (err) => historial.showToast('Error', 'Fallo al reenviar.', 'error')
        });
    });

    $(document).on('click', '#ctx-recrear-enviar', function () {
        if (currentCtxId) {
            const isMasterOn = $('#master-email-toggle').is(':checked');
            historial.executePdfAction(currentCtxId, { force: 1, nosend: !isMasterOn });
        }
    });

    $(document).on('click', '#ctx-recrear-nosend', function () {
        if (currentCtxId) historial.executePdfAction(currentCtxId, { force: 1, nosend: 1 });
    });

    // EVENTOS DE NOTAS / CHAT
    $(document).off('click', '.btn-open-notes').on('click', '.btn-open-notes', function(e) {
        e.stopPropagation();
        historial.openNotesModal($(this).data('id-lectura'));
    });

    $(document).on('click', '#btn-close-notes-modal, #modal-notes-backdrop', () => historial.closeNotesModal());
    $(document).on('click', '#btn-send-note', () => historial.sendNote());
    $(document).on('keypress', '#input-new-note', (e) => {
        if (e.which === 13 && !e.shiftKey) { e.preventDefault(); historial.sendNote(); }
    });

    $(document).off('click', '.btn-delete-note').on('click', '.btn-delete-note', function(e) {
        e.stopPropagation();
        historial.deleteNote($(this).data('id-lectura'), $(this).data('index'));
    });

    // Eventos de Visor de Evidencia (M3)
    $(document).off('click', '.btn-view-evidence').on('click', '.btn-view-evidence', function(e) {
        e.stopPropagation();
        historial.viewEvidence($(this).data('foto'));
    });

    $(document).off('click', '#btn-close-evidence, #btn-close-evidence-footer, #modal-evidence-backdrop')
               .on('click', '#btn-close-evidence, #btn-close-evidence-footer, #modal-evidence-backdrop', () => {
        historial.closeEvidenceModal();
    });

    // Eventos Masivos e Interfaz
    $(document).off('click', '#btn-toggle-advanced').on('click', '#btn-toggle-advanced', function() {
        const $panel = $('#advanced-tools-panel');
        const $chevron = $('#advanced-chevron');
        
        if ($panel.hasClass('hidden')) {
            $panel.removeClass('hidden').hide().fadeIn(300);
            $chevron.addClass('rotate-180');
        } else {
            $panel.fadeOut(300, function() { $panel.addClass('hidden'); });
            $chevron.removeClass('rotate-180');
        }
    });

    $(document).off('click', '#download-all-pdfs-btn').on('click', '#download-all-pdfs-btn', function() {
        historial.downloadZip();
    });

    // Sistema de Ayuda y Manual
    $(document).off('click', '#btn-help-master-switch').on('click', '#btn-help-master-switch', () => {
        historial.showHelp('master-switch');
    });

    $(document).off('click', '#btn-close-help-modal, #btn-close-help-modal-footer, #modal-help-backdrop')
               .on('click', '#btn-close-help-modal, #btn-close-help-modal-footer, #modal-help-backdrop', () => {
        historial.closeHelpModal();
    });

    $(document).on('change', '.depto-checkbox', function() {
        const id = $(this).data('id');
        if ($(this).is(':checked')) {
            historial.selectedDeptos.add(id);
            $(this).closest('tr').addClass('bg-blue-50 border-l-4 border-l-blue-500');
        } else {
            historial.selectedDeptos.delete(id);
            $(this).closest('tr').removeClass('bg-blue-50 border-l-4 border-l-blue-500');
            $('#select-all-deptos').prop('checked', false);
        }
        updateMassiveBar();
    });

    $(document).on('change', '#select-all-deptos', function() {
        const isChecked = $(this).is(':checked');
        $('.depto-checkbox').each(function() {
            const id = $(this).data('id').toString();
            $(this).prop('checked', isChecked);
            if (isChecked) {
                historial.selectedDeptos.add(id);
                $(this).closest('tr').addClass('bg-blue-50 border-l-4 border-l-blue-500');
            } else {
                historial.selectedDeptos.delete(id);
                $(this).closest('tr').removeClass('bg-blue-50 border-l-4 border-l-blue-500');
            }
        });
        updateMassiveBar();
    });

    $(document).on('click', '#btn-massive-smart', () => processMassiveQueue('smart'));
    $(document).on('click', '#btn-massive-force', () => processMassiveQueue('force'));
    $(document).on('click', '#btn-massive-cancel', () => {
        historial.selectedDeptos.clear();
        $('.depto-checkbox, #select-all-deptos').prop('checked', false);
        $('tr').removeClass('bg-blue-50 border-l-4 border-l-blue-500');
        updateMassiveBar();
    });

    $(document).on('click', '#btn-close-console', () => {
        $('#transmission-backdrop').addClass('opacity-0');
        $('#transmission-content').addClass('scale-95 opacity-0');
        setTimeout(() => {
            $('#modal-transmission-console').addClass('hidden');
            historial.fetchHistorial(historial.selectedBuildingId);
            historial.selectedDeptos.clear();
            updateMassiveBar();
            $('.depto-checkbox, #select-all-deptos').prop('checked', false);
        }, 300);
    });

    // Helpers
    function updateMassiveBar() {
        const count = historial.selectedDeptos.size;
        const $bar = $('#massive-action-bar');
        const $count = $('#selected-count');
        if (count > 0) {
            $count.text(count);
            $bar.removeClass('hidden translate-y-20').addClass('flex translate-y-0');
        } else {
            $bar.addClass('translate-y-20');
            setTimeout(() => { if(historial.selectedDeptos.size === 0) $bar.addClass('hidden'); }, 500);
        }
    }

    async function processMassiveQueue(mode) {
        // ... Lógica masiva con window.historial ...
        const ids = Array.from(historial.selectedDeptos);
        const total = ids.length;
        $('#modal-transmission-console').removeClass('hidden');
        $('#console-log-container').empty();
        $('#console-progress-bar').css('width', '0%');
        
        for (let i = 0; i < total; i++) {
            const id = ids[i];
            const numDepto = $(`.depto-checkbox[data-id="${id}"]`).data('num') || 'Unidad';
            $('#console-progress-text').text(`Procesando #${numDepto}...`);
            try {
                await $.ajax({ url: `apis_marvi/public/api/historial/pdf/${id}?ajax=1&force=${mode === 'force' ? 1 : 0}`, type: 'GET' });
            } catch (err) {}
            $('#console-progress-bar').css('width', `${((i + 1) / total) * 100}%`);
        }
        historial.showToast('Éxito', 'Proceso masivo terminado', 'success');
    }

    // Exponer para debugging
    window.AppHistorial = historial;
}
