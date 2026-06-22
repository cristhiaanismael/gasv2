/**
 * config-dom.js
 * Clase encargada exclusivamente del renderizado y manipulación visual del DOM.
 */
class ConfigDOM {
    constructor() {
        // Elementos de Periodos
        this.$fechaInicio = $('#p-fecha-inicio');
        this.$fechaFin = $('#p-fecha-fin');
        this.$preview = $('#periodo-preview');
        this.$periodoGlobalSelect = $('#periodo-global-select');
        this.$periodosList = $('#periodos-list');
        this.$btnSavePeriodo = $('#btn-save-periodo');

        // Elementos de Edificios / Config
        this.$edificioSelect = $('#config-edificio-select');
        this.$emptyState = $('#config-empty-state');
        this.$mainContainer = $('#config-main-container');
        this.$btnSaveConfig = $('#btn-save-config');

        // Tarjetas de Valores Vigentes
        this.$valPrecioLitro = $('#val-precio-litro');
        this.$valFactor = $('#val-factor');
        this.$valCuotaAdmin = $('#val-cuota-admin');

        // Inputs del Formulario
        this.$inputPrecioLitro = $('#input-precio-litro');
        this.$inputFactor = $('#input-factor');
        this.$inputCuotaAdmin = $('#input-cuota-admin');

        // Paneles de Historial
        this.$historyListPrecios = $('#history-list-precios');
        this.$historyListFactores = $('#history-list-factores');
        this.$historyListCuotas = $('#history-list-cuotas');
    }

    /**
     * Actualiza la previsualización del periodo con base en las fechas seleccionadas.
     */
    updatePeriodoPreview() {
        const start = this.$fechaInicio.val();
        const end = this.$fechaFin.val();
        if (start && end) {
            const fmtStart = start.split('-').reverse().join('-');
            const fmtEnd = end.split('-').reverse().join('-');
            const finalString = `${fmtStart} ${fmtEnd}`.trim();
            this.$preview.text(finalString).addClass('text-blue-900').removeClass('text-slate-400');
            return finalString;
        } else {
            this.$preview.text('-- - --').addClass('text-slate-400');
            return null;
        }
    }

    /**
     * Limpia los campos de entrada de periodos.
     */
    clearPeriodoForm() {
        this.$fechaInicio.val('');
        this.$fechaFin.val('');
        this.updatePeriodoPreview();
    }

    /**
     * Genera la lista de opciones para el selector global de periodos.
     */
    renderPeriodosSelect(periodos) {
        const options = periodos.map(p => `<option value="${p.id_corte}">${p.periodo}</option>`).join('');
        this.$periodoGlobalSelect.html('<option value="">-- Ver todos los periodos --</option>' + options);
    }

    /**
     * Renderiza las tarjetas del historial de periodos oficiales.
     */
    renderPeriodosList(periodos) {
        const html = periodos.map(p => `
            <div class="bg-white p-4 rounded-2xl flex justify-between items-center border border-slate-100 hover:border-blue-200 transition-all shadow-sm">
                <div>
                    <p class="text-sm font-black text-slate-800">${p.periodo}</p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Rango: ${p.fecha_inicio || '--'} a ${p.fecha_fin || '--'}</p>
                </div>
            </div>
        `).join('');
        this.$periodosList.html(html || '<p class="text-center py-10 text-slate-400 text-xs font-bold italic">No hay registros</p>');
    }

    /**
     * Carga las opciones de edificios en el selector.
     */
    renderEdificiosSelect(edificios) {
        const options = ['<option value="">-- Seleccionar --</option>']
            .concat(edificios.map(e => `<option value="${e.id_edificio}">Edificio ${e.num_edificio}</option>`));
        this.$edificioSelect.html(options.join(''));
    }

    /**
     * Muestra u oculta la vista principal de configuración con base en si hay un edificio seleccionado.
     */
    toggleConfigView(hasSelection) {
        if (hasSelection) {
            this.$emptyState.addClass('hidden');
            this.$mainContainer.removeClass('hidden');
        } else {
            this.$emptyState.removeClass('hidden');
            this.$mainContainer.addClass('hidden');
        }
    }

    /**
     * Rellena las tarjetas de valores vigentes y los campos de entrada de modificación.
     */
    renderVigenteConfig(config) {
        // Tarjetas
        this.$valPrecioLitro.text(`$${Number(config.precioLitro || 0).toFixed(2)}`);
        this.$valFactor.text(Number(config.factor || 1).toFixed(4));
        this.$valCuotaAdmin.text(`$${Number(config.cuotaAdmin || 0).toFixed(2)}`);

        // Form Inputs
        this.$inputPrecioLitro.val(config.precioLitro || '');
        this.$inputFactor.val(config.factor || '');
        this.$inputCuotaAdmin.val(config.cuotaAdmin || '');
    }

    /**
     * Muestra una animación de carga en los botones de acción.
     */
    setLoadingState(buttonSelector, isLoading, defaultHtml) {
        const $btn = $(buttonSelector);
        if (isLoading) {
            $btn.prop('disabled', true).html('<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Guardando...</span>');
        } else {
            $btn.prop('disabled', false).html(defaultHtml);
        }
        lucide.createIcons();
    }

    /**
     * Renderiza la línea de tiempo auditada (Timeline) dividida en sus tres paneles.
     */
    renderHistoryLists(history) {
        const formatDate = (dateStr) => {
            if (!dateStr) return '--';
            try {
                const parts = dateStr.split(' ');
                const dateParts = parts[0].split('-');
                return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}` + (parts[1] ? ` a las ${parts[1].substring(0, 5)}` : '');
            } catch(e) {
                return dateStr;
            }
        };

        // Render Precios
        const preciosHtml = (history.precios || []).map((p, idx) => `
            <div class="flex items-start gap-4 p-4 rounded-2xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-all animate-in fade-in duration-300">
                <div class="w-8 h-8 rounded-full ${idx === 0 ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-400'} flex items-center justify-center shrink-0">
                    <i data-lucide="droplet" class="w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-black text-slate-800">$${Number(p.costo || 0).toFixed(2)}</span>
                        ${idx === 0 ? '<span class="px-2 py-0.5 rounded-full bg-indigo-100 text-[8px] font-black text-indigo-600 uppercase tracking-widest">Vigente</span>' : ''}
                    </div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Registrado: ${formatDate(p.fecha_register)}</p>
                </div>
            </div>
        `).join('');
        this.$historyListPrecios.html(preciosHtml || '<p class="text-center py-10 text-slate-400 text-xs font-bold italic">Sin historial de precios</p>');

        // Render Factores
        const factoresHtml = (history.factores || []).map((f, idx) => `
            <div class="flex items-start gap-4 p-4 rounded-2xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-all animate-in fade-in duration-300">
                <div class="w-8 h-8 rounded-full ${idx === 0 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-400'} flex items-center justify-center shrink-0">
                    <i data-lucide="scaling" class="w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-black text-slate-800">${Number(f.factor || 1).toFixed(4)}</span>
                        ${idx === 0 ? '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-[8px] font-black text-amber-600 uppercase tracking-widest">Vigente</span>' : ''}
                    </div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Registrado: ${formatDate(f.fecha_register)}</p>
                </div>
            </div>
        `).join('');
        this.$historyListFactores.html(factoresHtml || '<p class="text-center py-10 text-slate-400 text-xs font-bold italic">Sin historial de factores</p>');

        // Render Cuotas
        const cuotasHtml = (history.cuotas || []).map((c, idx) => `
            <div class="flex items-start gap-4 p-4 rounded-2xl border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-all animate-in fade-in duration-300">
                <div class="w-8 h-8 rounded-full ${idx === 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400'} flex items-center justify-center shrink-0">
                    <i data-lucide="credit-card" class="w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-black text-slate-800">$${Number(c.cuota || 0).toFixed(2)}</span>
                        ${idx === 0 ? '<span class="px-2 py-0.5 rounded-full bg-emerald-100 text-[8px] font-black text-emerald-600 uppercase tracking-widest">Vigente</span>' : ''}
                    </div>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Registrado: ${formatDate(c.fecha_register)}</p>
                </div>
            </div>
        `).join('');
        this.$historyListCuotas.html(cuotasHtml || '<p class="text-center py-10 text-slate-400 text-xs font-bold italic">Sin historial de cuotas</p>');

        lucide.createIcons();
    }
}
