/**
 * historial.js
 * Lógica de negocio y peticiones del módulo de Historial.
 * Clase principal que gestiona datos, peticiones API y renderizado.
 */

window.Historial = class Historial {
    constructor() {
        this.edificios = [];
        this.data = [];  // Lecturas del periodo actual
        this.selectedBuilding = '';
        this.selectedBuildingId = null;
        this.searchTerm = '';

        // Estado del panel de detalles
        this.currentDeptoId = null;
        this.currentDeptoNum = '';
        this.currentLecturaId = null;
        this.currentSaldo = 0;
        this.currentHistory = [];

        this.selectedDeptos = new Set();
        this.currentNotasGlobales = [];
        this.sidebarNotesReady = false;

        // Flags de Control de Redundancia
        this._isFetching = false;
        this._isSelecting = false;
    }

    // Contenido del Manual Integrado (Sin redundancias)
    static HELP_TOPICS = {
        'master-switch': {
            title: 'Modo de envío automático de PDFs',
            body: `
                <div class="space-y-5">
                    <!-- SITUACIÓN ACTIVA -->
                    <div class="p-5 bg-green-50 rounded-[1.5rem] border border-green-100 flex items-start space-x-4">
                        <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-green-200">
                            <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-xs font-black text-green-800 uppercase tracking-widest mb-1 font-sans">Modo Activo</h4>
                            <p class="text-[11px] font-medium text-green-700 leading-relaxed font-sans">
                                Cuando esta opción está <b>activada</b>, el sistema enviará los PDFs de forma automática, siempre que se seleccionen correctamente las opciones de envío correspondientes.
                            </p>
                        </div>
                    </div>

                    <!-- SITUACIÓN DESACTIVADA -->
                    <div class="p-5 bg-amber-50 rounded-[1.5rem] border border-amber-100 flex items-start space-x-4">
                        <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-amber-200">
                            <i data-lucide="alert-triangle" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-xs font-black text-amber-800 uppercase tracking-widest mb-1 font-sans">Modo Desactivado</h4>
                            <p class="text-[11px] font-medium text-amber-700 leading-relaxed font-sans">
                                Ninguna de las opciones enviará el PDF, incluso si seleccionas <b>“Generar y enviar”</b>. En este estado, los PDFs solo se generarán localmente, pero no se enviarán hasta que el modo automático sea activado nuevamente.
                            </p>
                        </div>
                    </div>
                </div>
            `
        }
    };

    // ───────────────────────────────────────────
    //  PETICIONES API
    // ───────────────────────────────────────────

    async fetchEdificios() {
        if (this._isFetchingEdificios) return this.edificios;
        this._isFetchingEdificios = true;
        const url = API_BASE_URL + 'edificios';
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Error: ' + response.statusText);
            const data = await response.json();
            this.edificios = data;
            return data;
        } catch (error) {
            console.error('Error al cargar edificios:', error);
            return [];
        } finally {
            this._isFetchingEdificios = false;
        }
    }

    async fetchHistorial(id_edificio) {
        const idStr = String(id_edificio);
        if (!id_edificio || this._isFetchingHistorial === idStr) return;
        
        this._isFetchingHistorial = idStr;
        this.selectedBuildingId = id_edificio;
        this.isSearchMode = false; // Reset búsqueda al cambiar edificio
        
        const url = API_BASE_URL + 'historial/edificio/' + id_edificio;
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Error API Historial: ' + response.statusText);
            const result = await response.json();
            this.data = result.data || [];
            this.renderAll();
        } catch (error) {
            console.error('Error cargando historial:', error);
            this.showToast('Error de Carga', error.message, 'error');
            this.data = [];
            this.renderAll();
        } finally {
            this._isFetchingHistorial = null;
        }
    }

    // ───────────────────────────────────────────
    //  RENDERIZADO
    // ───────────────────────────────────────────

    renderAll() {
        this.renderSidebar();
        this.renderTable();
        this.renderKPIs();
        $('#current-building-name').text(this.selectedBuilding || 'Seleccione un edificio');
        lucide.createIcons();
    }

    renderSidebar() {
        const container = $('#building-list-container');
        if (!container.length) return;

        const filtered = this.edificios.filter(ed =>
            ed.num_edificio.toLowerCase().includes(this.searchTerm.toLowerCase())
        );

        $('#total-buildings-count').text(this.edificios.length);

        const html = filtered.map(ed => {
            const name = ed.num_edificio;
            const isSelected = this.selectedBuilding === name;
            return `
                <button class="building-select-btn w-full flex items-center justify-between px-3 py-2.5 rounded-lg transition-all text-left ${isSelected ? 'bg-blue-50 border-blue-200 shadow-sm border' : 'hover:bg-gray-50 border border-transparent hover:border-gray-200'}" data-edificio="${name}" data-id="${ed.id_edificio}">
                    <div class="flex items-center truncate mr-2">
                        <div class="w-2 h-2 rounded-full mr-3 flex-shrink-0 ${isSelected ? 'bg-blue-500' : 'bg-gray-300'}"></div>
                        <span class="text-sm truncate ${isSelected ? 'font-bold text-blue-900' : 'font-medium text-gray-600'}">${name}</span>
                    </div>
                </button>`;
        }).join('');

        container.html(html || '<div class="p-4 text-center text-gray-400 text-sm">No hay edificios</div>');
    }

    renderTable() {
        const $tbody = $('#data-table-body');
        const q = $('#search-omni-input').val().toLowerCase();

        // Filtrar localmente si no estamos en modo búsqueda profunda de servidor
        const filtered = this.data;

        if (filtered.length === 0) {
            $tbody.html('<tr><td colspan="11" class="p-12 text-center text-gray-400 font-medium">No se encontraron registros para los criterios ingresados.</td></tr>');
            return;
        }

        const html = filtered.map(item => {
            const isLocal = String(item.id_edificio) === String(this.selectedBuildingId);
            const titular = item.nombre
                ? `${item.nombre} ${item.ape_pat || ''} ${item.ape_mat || ''}`.trim()
                : '<span class="text-gray-300 italic">Sin titular</span>';
            const correo = item.correo
                ? item.correo
                : '<span class="text-gray-300">Sin correo</span>';

            const hasReading = !!item.id_lectura;
            const cons   = hasReading ? parseFloat(item.consumo_m3 || 0).toFixed(2) : '—';
            const lt     = hasReading ? parseFloat(item.consumos_litros || 0).toFixed(0) : '—';
            
            const totalPeriodo = parseFloat(item.total_a_pagar || 0);
            const saldoTotal   = parseFloat(item.saldo_total   || 0);

            let statusBadge;
            if (!hasReading) {
                statusBadge = '<span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">Sin Lectura</span>';
            } else if (saldoTotal <= 0) {
                statusBadge = '<span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-green-100 text-green-700 border border-green-200">Pagado</span>';
            } else {
                statusBadge = '<span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">Pendiente</span>';
            }

            const isSelected = this.selectedDeptos.has(item.id_departamento);

            // Evidencia de búsqueda (IA Feedback - Transparencia Total)
            const badges = [];
            if (this.isSearchMode && q) {
                if (item.nota && item.nota.toLowerCase().includes(q)) {
                    badges.push(`<span class="bg-indigo-50 text-indigo-600 text-[8px] font-black px-1.5 py-0.5 rounded border border-indigo-100 uppercase">Nota coincidente</span>`);
                }
                if (item.total_abonos && String(item.total_abonos).includes(q)) {
                    badges.push(`<span class="bg-emerald-50 text-emerald-600 text-[8px] font-black px-1.5 py-0.5 rounded border border-emerald-100 uppercase">Abono coincidente</span>`);
                }
                if (item.total_a_pagar && String(item.total_a_pagar).includes(q)) {
                    badges.push(`<span class="bg-blue-50 text-blue-600 text-[8px] font-black px-1.5 py-0.5 rounded border border-blue-100 uppercase">Monto coincidente</span>`);
                }
                if (item.lectura_fin && String(item.lectura_fin).includes(q)) {
                    badges.push(`<span class="bg-slate-100 text-slate-600 text-[8px] font-black px-1.5 py-0.5 rounded border border-slate-200 uppercase">Lectura coincidente</span>`);
                }
            }
            const matchFeedback = badges.length > 0 ? `<div class="flex flex-wrap gap-1 mt-1">${badges.join('')}</div>` : '';

            return `
                <tr class="hover:bg-slate-50/80 transition-all group ${isSelected ? 'bg-blue-50 border-l-4 border-l-blue-500 shadow-sm' : ''} ${!isLocal ? 'opacity-70 bg-gray-50/30' : ''}">
                    <!-- CHECKBOX -->
                    <td class="px-4 py-4 pl-6 w-10">
                        <input type="checkbox" class="depto-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4" 
                               data-id="${item.id_departamento}" 
                               data-nombre="${titular}" 
                               data-num="${item.num_departamento}"
                               ${isSelected ? 'checked' : ''}>
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex flex-col">
                            <div class="flex items-center space-x-2">
                                <button class="btn-open-detail font-black text-gray-900 hover:text-blue-600 transition-colors" data-id="${item.id_departamento}">
                                    #${item.num_departamento}
                                </button>
                            </div>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-tighter mt-1 italic">${item.nombre_edificio || 'Edificio'}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex flex-col">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-bold text-gray-900">${titular}</span>
                                ${matchFeedback}
                            </div>
                            <span class="text-[10px] font-medium text-gray-400 tracking-tight">${correo}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-center">
                        ${hasReading
                            ? `<button class="btn-view-evidence group/m3 relative inline-flex items-center justify-center" data-foto="${item.foto || ''}">
                                 <span class="text-xs font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-md border border-blue-100 group-hover/m3:bg-blue-600 group-hover/m3:text-white transition-all shadow-sm">
                                    ${cons}
                                 </span>
                                 <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/m3:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">Ver Evidencia</div>
                               </button>`
                            : '<span class="text-gray-300">—</span>'}
                    </td>
                    <td class="px-4 py-4 text-center text-xs font-bold text-slate-500">${hasReading ? lt : '—'}</td>
                    <td class="px-4 py-4 text-center font-bold text-gray-700 text-sm">${hasReading ? '$' + totalPeriodo.toFixed(2) : '—'}</td>
                    <td class="px-4 py-4 text-center font-black ${saldoTotal > 0 ? 'text-rose-600' : 'text-emerald-600'} text-sm">
                        ${hasReading ? (saldoTotal != 0 ? '$' + saldoTotal.toFixed(2) : '$0.00') : '—'}
                    </td>
                    <td class="px-4 py-4 text-center">${statusBadge}</td>
                    <td class="px-4 py-4 pr-6 text-right">
                        <div class="flex items-center justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                            <!-- PDF Status Indicator -->
                            <div class="relative">
                                <button class="p-2 rounded-lg transition-all shadow-sm border-2 ${item.pdf_exists ? 'bg-green-600 text-white border-green-700 hover:bg-green-700' : 'bg-red-600 text-white border-red-700 hover:bg-red-700'} btn-pdf-context" 
                                        data-id="${item.id_departamento}" 
                                        data-exists="${item.pdf_exists}"
                                        title="Click Izq: Ver/Generar | Click Der: Opciones">
                                    <i data-lucide="${item.pdf_exists ? 'file-check' : 'file-minus'}" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        $tbody.html(html);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    renderKPIs() {
        const totalDeptos = this.data.length;
        if (totalDeptos === 0) return;

        // 1. Facturado = Sumatoria de lo que hay que cobrar (saldo_pendiente) de TODOS los deptos
        // La suma de la columna "A Pagar" de la tabla.
        const facturado = this.data.reduce((acc, i) => acc + Math.max(0, parseFloat(i.saldo_pendiente || 0)), 0);

        // 2. Cobranza = % de departamentos que están al corriente (saldo <= 0)
        const alCorriente = this.data.filter(i => parseFloat(i.saldo_pendiente || 0) <= 0).length;
        const cobranza = (alCorriente / totalDeptos) * 100;

        // 3. Consumo Total = Sumatoria de consumo_m3 (solo los que tienen lectura)
        const consumoM3 = this.data.reduce((acc, i) => acc + parseFloat(i.consumo_m3 || 0), 0);

        // 4. Ingresos Recaudados = Suma de abonos aplicados en este periodo (o simplemente facturado - pendiente)
        // Para simplificar y ser consistente: Mostramos cuánto dinero real ha entrado.
        // Si el saldo pendiente es menor al total facturado histórico, la diferencia es recaudo.
        // Nota: Esta métrica es proyectada sobre el saldo actual.
        const saldoPendienteTotal = this.data.reduce((acc, i) => acc + Math.max(0, parseFloat(i.saldo_pendiente || 0)), 0);
        
        // El acumulado real que ha entrado es el total de cargos - total pendiente
        // Pero para este periodo usaremos una lógica de "Avance de Cobranza en $"
        const recaudado = Math.max(0, facturado - saldoPendienteTotal); 

        // Progreso visual
        const progressPercent = facturado > 0 ? (recaudado / facturado) * 100 : 0;

        $('#kpi-facturado').text(`$${facturado.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        $('#kpi-consumo').html(`${consumoM3.toFixed(2)} <span class="text-sm text-gray-400 font-medium">m³</span>`);
        $('#kpi-cobranza').text(`${cobranza.toFixed(0)}%`);
        $('#kpi-progreso').text(`$${recaudado.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        $('#kpi-progress-bar').css('width', `${progressPercent}%`);
    }

    /**
     * Seleccionar un edificio por nombre e ID y disparar la carga de datos
     */
    async selectBuilding(name, id) {
        const idStr = String(id);
        if (String(this.selectedBuildingId) === idStr || this._isSelecting) return;
        
        this._isSelecting = true;
        try {
            this.selectedBuilding = name;
            this.isSearchMode = false; // Reset búsqueda
            $('#search-omni-input').val(''); // Limpiar buscador
            $('#search-omni-results').addClass('hidden');
            
            this.showToast('Edificio', name, 'success');
            await this.fetchHistorial(id);
        } finally {
            this._isSelecting = false;
        }
    }

    // ───────────────────────────────────────────
    //  GESTIÓN DEL PANEL DE DETALLES
    // ───────────────────────────────────────────

    async openDetailPanel(id_departamento) {
        if (!id_departamento) return;
        this.currentDeptoId = id_departamento;
        this.sidebarNotesReady = false; // Bloquear notas hasta que carguen
        
        const selectors = HISTORIAL_CONFIG.PANEL;
        const $panel = $(selectors.id);
        const $backdrop = $(selectors.backdrop);

        // 1. ABRIR PANEL INMEDIATAMENTE (UX Instantánea)
        $backdrop.removeClass('hidden');
        setTimeout(() => {
            $backdrop.addClass('opacity-100');
            $panel.removeClass('translate-x-full');
        }, 10);

        // 2. MOSTRAR ESTADOS DE CARGA (Skeletons/Spinners)
        $('#history-table-body').html('<tr><td colspan="6" class="text-center py-10"><i data-lucide="loader-2" class="w-6 h-6 animate-spin mx-auto text-slate-300"></i></td></tr>');
        lucide.createIcons();

        try {
            // 3. CARGA FASE 1: DATOS CORE (Inputs y Saldos)
            const response = await fetch(API_BASE_URL + 'historial/detalle/' + id_departamento);
            if (!response.ok) throw new Error('Error al cargar detalles');
            
            const res = await response.json();
            const { depto, lectura, saldo, abonosPeriodo, periodo, periodo_data, config, lec_ant_sugerida } = res;

            this.currentLecturaId = lectura ? lectura.id_lectura : null;
            this.currentDeptoNum = depto.num_departamento;
            this.currentSaldo = saldo;
            this.currentAbonosPeriodo = abonosPeriodo || 0;
            this.currentConfig = config || { precioLitro: 0, factor: 1, cuotaAdmin: 0 };
            
            this.initialBalance = saldo;
            if (lectura) this.initialBalance -= parseFloat(lectura.total_a_pagar || 0);
            
            this.periodAdeudos = lectura ? parseFloat(lectura.adeudos || 0) : 0;
            this.periodSaldoFavor = lectura ? parseFloat(lectura.saldo_favor || 0) : 0;

            // Poblar campos base
            $(selectors.unitName).text(`Depto ${depto.num_departamento}`);
            $(selectors.periodLabel).text(periodo || '---');
            $('#lbl-val-precio').text(`$${this.currentConfig.precioLitro.toFixed(2)}`);
            $('#lbl-val-factor').text(`${this.currentConfig.factor.toFixed(3)}`);
            $('#lbl-val-cuota').text(`$${this.currentConfig.cuotaAdmin.toFixed(2)}`);

            $(selectors.inputLecAnt).val(lectura ? lectura.lectura_ini : (lec_ant_sugerida || '0.00'));
            $(selectors.inputLecAct).val(lectura ? lectura.lectura_fin : '');
            $(selectors.inputAdd).val(lectura ? lectura.cargos_add : '0.00');
            $(selectors.inputAjuste).val(lectura ? lectura.ajuste : '0.00');
            // 'Total a Pagar' en el panel ahora siempre muestra el Subtotal del Periodo (Exclusivo de esta lectura)
            $(selectors.inputTotal).val(lectura ? parseFloat(lectura.total_a_pagar || 0).toFixed(2) : '0.00');
            $(selectors.saldoActual).text(`$${saldo.toFixed(2)}`);
            $('#panel-balance-final').text(`$${saldo.toFixed(2)}`);
            $(selectors.inputPago).val('');

            $(selectors.lblConsumom3).text(lectura ? parseFloat(lectura.consumo_m3 || 0).toFixed(2) : '0.00');
            $(selectors.lblConsumolt).text(lectura ? parseFloat(lectura.consumos_litros || 0).toFixed(2) : '0.00');
            $(selectors.lblMontoGas).text(lectura ? '$' + parseFloat(lectura.monto || 0).toFixed(2) : '$0.00');

            // Las columnas 'adeudos' y 'saldo_favor' de la tabla lectura están descartadas.
            // Siempre mostraremos el saldo actual calculado desde movimientos.
            $(selectors.lblSaldoFavor).text(saldo < 0 ? '$' + Math.abs(saldo).toFixed(2) : '$0.00');
            $(selectors.lblAdeudos).text(saldo > 0 ? '$' + saldo.toFixed(2) : '$0.00');

            // 4. CARGA FASE 2: HISTORIAL Y NOTAS (Paralelo y Asíncrono)
            this.loadSidebarExtras(id_departamento, periodo);

            lucide.createIcons();
        } catch (error) {
            this.showToast('Error', error.message, 'error');
        }
    }

    /**
     * Carga asincrónica de Historial y Notas para no bloquear el panel
     */
    async loadSidebarExtras(id_departamento, periodo) {
        // Cargar Histórico
        fetch(API_BASE_URL + 'historial/sidebar-history/' + id_departamento)
            .then(r => r.json())
            .then(history => {
                this.currentHistory = history || [];
                this.renderHistory(this.currentHistory);
            });

        // Cargar Notas
        fetch(API_BASE_URL + 'historial/sidebar-notes/' + id_departamento)
            .then(r => r.json())
            .then(notes => {
                this.currentNotasGlobales = notes || [];
                this.sidebarNotesReady = true;

                const $headerActions = $('#panel-header-actions');
                const count = this.currentNotasGlobales.length;
                
                $headerActions.html(`
                    <button class="btn-open-notes relative p-2 rounded-full transition-all group ${count > 0 ? 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100' : 'text-slate-300 hover:text-slate-500 hover:bg-slate-100'}" 
                            data-id-lectura="${this.currentLecturaId}" 
                            data-periodo="${periodo}"
                            title="Ver Notas (${count})">
                        <i data-lucide="message-square" class="w-4.5 h-4.5 group-hover:scale-110 transition-transform"></i>
                        ${count > 0 ? `
                            <span class="absolute top-0 right-0 w-3 h-3 bg-indigo-600 text-white text-[7px] font-black rounded-full flex items-center justify-center ring-2 ring-slate-50">
                                ${count}
                            </span>
                        ` : ''}
                    </button>
                `);
                lucide.createIcons();
            });
    }



    closeDetailPanel() {
        const selectors = HISTORIAL_CONFIG.PANEL;
        const $panel = $(selectors.id);
        const $backdrop = $(selectors.backdrop);

        $panel.addClass('translate-x-full');
        $backdrop.removeClass('opacity-100');
        setTimeout(() => {
            $backdrop.addClass('hidden');
            $('#panel-header-actions').empty();
            this.currentDeptoId = null;
            this.currentLecturaId = null;
        }, 300);
    }

    updatePanelTotal() {
        this.calculateLocal();
    }

    calculateLocal() {
        const selectors = HISTORIAL_CONFIG.PANEL;
        const config = this.currentConfig || { precioLitro: 0, factor: 1, cuotaAdmin: 0 };

        const lecAnt = parseFloat($(selectors.inputLecAnt).val() || 0);
        const lecAct = parseFloat($(selectors.inputLecAct).val() || 0);
        const add = parseFloat($(selectors.inputAdd).val() || 0);
        const ajuste = parseFloat($(selectors.inputAjuste).val() || 0);

        // Consumos
        const m3 = Math.max(0, lecAct - lecAnt);
        const lt = m3 * config.factor;
        const montoGas = lt * config.precioLitro;

        // Mostrar consumos
        $(selectors.lblConsumom3).text(m3.toFixed(2));
        $(selectors.lblConsumolt).text(lt.toFixed(2));
        $(selectors.lblMontoGas).text('$' + montoGas.toFixed(2));

        // 1. Total del Periodo (Solo lo correspondiente a esta lectura/mes)
        const totalPeriodo = montoGas + config.cuotaAdmin + add + ajuste;
        $(selectors.inputTotal).val(totalPeriodo.toFixed(2));

        // 2. Saldo Neto Final (Deuda anterior + este periodo)
        const totalBalance = (this.initialBalance || 0) + totalPeriodo;
        $('#panel-balance-final').text('$' + totalBalance.toFixed(2));
        
        // Actualizar colores del balance
        $('#panel-balance-final').removeClass('text-rose-600 text-emerald-600')
            .addClass(totalBalance > 0 ? 'text-rose-600' : 'text-emerald-600');
    }

    async recalculateTotal() {
        const selectors = HISTORIAL_CONFIG.PANEL;
        const id_lectura = this.currentLecturaId;
        const lecAct = $(selectors.inputLecAct).val();
        const add = $(selectors.inputAdd).val();

        if (!id_lectura || !lecAct) {
            showToast('Capture lectura actual para recalcular', 'warning');
            return;
        }

        $(selectors.btnRecalculate).addClass('animate-spin');

        try {
            // Reutilizamos el endpoint de actualización para obtener el cálculo del servidor
            // pero sin enviar un total manual, así el servidor lo calcula según reglas de negocio.
            const response = await fetch(API_BASE_URL + 'historial/actualizar-lectura', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_lectura: id_lectura,
                    lectura_fin: lecAct,
                    cargos_add: add,
                    ajuste: $(selectors.inputAjuste).val()
                })
            });

            if (!response.ok) throw new Error('Error al recalcular');
            const res = await response.json();
            const l = res.lectura || {}; // El backend debería retornar la lectura full o los campos
            
            // Actualizamos el input del total
            $(selectors.inputTotal).val(res.total);

            // Actualizamos etiquetas de resumen (si el backend las envía)
            if (res.lectura_data) {
                const d = res.lectura_data;
                $(selectors.lblConsumom3).text(parseFloat(d.consumo_m3 || 0).toFixed(2));
                $(selectors.lblConsumolt).text(parseFloat(d.consumos_litros || 0).toFixed(2));
                $(selectors.lblMontoGas).text('$' + parseFloat(d.monto || 0).toFixed(2));
            }

            showToast('Recalculado con tarifas vigentes', 'info');

        } catch (error) {
            this.showToast('Fallo al Recalcular', error.message, 'error');
        } finally {
            $(selectors.btnRecalculate).removeClass('animate-spin');
        }
    }

    async saveReadingUpdate() {
        if (!this.currentLecturaId) {
            this.showToast('Atención', 'No hay una lectura capturada en este periodo para editar.', 'warning');
            return;
        }

        const selectors = HISTORIAL_CONFIG.PANEL;
        const payload = {
            id_lectura: this.currentLecturaId,
            lectura_fin: $(selectors.inputLecAct).val(),
            cargos_add: $(selectors.inputAdd).val(),
            ajuste: $(selectors.inputAjuste).val(),
            total_a_pagar: $(selectors.inputTotal).val(), // Envío del total (manual o calculado)
            nota: $(selectors.inputNota).val()
        };

        $(selectors.btnUpdate).prop('disabled', true).text('GUARDANDO...');

        try {
            const response = await fetch(API_BASE_URL + 'historial/actualizar-lectura', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error('Error al actualizar');

            this.showToast('¡Éxito!', 'Lectura y notas actualizadas correctamente', 'success');
            
            // Recargar datos de la tabla principal
            await this.fetchHistorial(this.selectedBuildingId);
            this.renderTable();
            // Recargar datos para el panel (saldo, etc)
            await this.openDetailPanel(this.currentDeptoId);

        } catch (error) {
            this.showToast('Error al Guardar', error.message, 'error');
        } finally {
            $(selectors.btnUpdate).prop('disabled', false).text('GUARDAR CAMBIOS');
        }
    }

    async submitPayment() {
        const selectors = HISTORIAL_CONFIG.PANEL;
        const monto = parseFloat($(selectors.inputPago).val() || 0);
        const tipo = $(selectors.inputMovTipo).val() || 'pago'; 

        if (monto <= 0) {
            this.showToast('Monto Inválido', 'Ingrese un valor mayor a cero', 'warning');
            return;
        }

        $(selectors.btnPayment).prop('disabled', true);

        try {
            // Decidir endpoint según tipo
            const endpoint = (tipo === 'pago') ? 'registrar-pago' : 'registrar-ajuste';
            const payload = {
                id_departamento: this.currentDeptoId,
                monto: (tipo === 'ajuste') ? -monto : monto, // Ajuste (rebaja) se envía negativo al endpoint de ajustes
                descripcion: (tipo === 'pago') ? 'Pago de Gas / Abono' : 
                             (tipo === 'cargo') ? 'Recargo / Ajuste manual' : 'Rebaja / Ajuste manual'
            };

            // Nota: Si es tipo 'cargo', usamos registrar-ajuste con monto positivo
            if (tipo === 'cargo') {
                payload.monto = monto;
            }

            // Incluimos la lectura actual si existe para vincular el pago
            if (this.currentLecturaId) {
                payload.id_lectura = this.currentLecturaId;
            }

            const response = await fetch(API_BASE_URL + 'historial/' + endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error(`Error al registrar ${tipo}`);

            showToast(`${tipo === 'abono' ? 'Pago' : 'Ajuste'} registrado correctamente`, 'success');
            
            $(selectors.inputPago).val('');

            // Recargar datos en cascada: Tabla Principal + Panel de Detalle
            await this.fetchHistorial(this.selectedBuildingId);
            this.renderTable();
            await this.openDetailPanel(this.currentDeptoId);

        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            $(selectors.btnPayment).prop('disabled', false);
        }
    }

    renderHistory(historico) {
        const $tbody = $(HISTORIAL_CONFIG.PANEL.historyBody);
        
        if (!historico || historico.length === 0) {
            $tbody.html('<tr><td colspan="8" class="py-4 text-center text-gray-400">Sin historial previo</td></tr>');
            return;
        }

        const html = historico.map(h => {
            const adeudo = parseFloat(h.adeudos || 0);
            const saldoFavor = parseFloat(h.saldo_favor || 0);
            
            return `
                <tr class="hover:bg-gray-100 transition-colors border-b border-gray-100">
                    <td class="px-1 py-1.5 text-left text-gray-500 font-medium truncate border-r border-gray-100">${h.periodo}</td>
                    <td class="px-1 py-1.5 text-right text-gray-400 bg-blue-100/30 border-r border-gray-100">${parseFloat(h.lectura_ini || 0).toFixed(1)}</td>
                    <td class="px-1 py-1.5 text-right text-slate-700 font-bold bg-blue-100/30 border-r border-gray-100">${parseFloat(h.lectura_fin || 0).toFixed(1)}</td>
                    <td class="px-1 py-1.5 text-right text-blue-600 font-black bg-indigo-100/30 border-r border-gray-100">${parseFloat(h.consumo_m3 || 0).toFixed(1)}</td>
                    <td class="px-1 py-1.5 text-right text-slate-400 bg-indigo-100/30 border-r border-gray-100">${parseFloat(h.consumos_litros || 0).toFixed(0)}</td>
                    <td class="px-1 py-1.5 text-right text-rose-600 font-bold bg-slate-100/30 border-r border-gray-100">${adeudo > 0 ? '$' + adeudo.toFixed(0) : '-'}</td>
                    <td class="px-1 py-1.5 text-right text-green-600 font-bold bg-slate-100/30 border-r border-gray-100">${saldoFavor > 0 ? '$' + saldoFavor.toFixed(0) : '-'}</td>
                    <td class="px-1 py-1.5 text-right text-gray-900 font-black bg-green-100/30">
                        <div class="flex items-center justify-end space-x-1.5">
                            <button class="btn-open-notes p-1 rounded-md transition-all ${h.nota && h.nota !== '[]' ? 'text-indigo-600 bg-indigo-50 shadow-sm' : 'text-slate-200 hover:text-slate-400'}" 
                                    data-id-lectura="${h.id_lectura}" 
                                    data-periodo="${h.periodo}">
                                <i data-lucide="message-square" class="w-3 h-3 ${h.nota && h.nota !== '[]' ? 'fill-current' : ''}"></i>
                            </button>
                            <span>$${parseFloat(h.total_a_pagar || 0).toFixed(1)}</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        $tbody.html(html);
    }

    // ───────────────────────────────────────────
    //  MODAL DE HISTÓRICO EXPANDIDO
    // ───────────────────────────────────────────

    openHistoryModal() {
        const config = HISTORIAL_CONFIG.MODAL_HISTORY;
        const $modal = $(config.id);
        const $backdrop = $(config.backdrop);
        const $content = $(config.content);
        const $body = $(config.body);

        $(config.deptoLabel).text(`Depto ${this.currentDeptoNum}`);

        if (!this.currentHistory || this.currentHistory.length === 0) {
            $body.html('<tr><td colspan="8" class="py-10 text-center text-gray-400">Sin historial registrado</td></tr>');
        } else {
            const html = this.currentHistory.map(h => {
                const adeudo = parseFloat(h.adeudos || 0);
                const saldoFavor = parseFloat(h.saldo_favor || 0);
                
                return `
                    <tr class="hover:bg-slate-50 transition-colors border-b border-gray-50">
                        <td class="px-4 py-4 text-slate-600 font-bold">${h.periodo}</td>
                        <td class="px-4 py-4 text-right text-slate-400 font-mono">${parseFloat(h.lectura_ini || 0).toFixed(2)}</td>
                        <td class="px-4 py-4 text-right text-slate-800 font-black font-mono">${parseFloat(h.lectura_fin || 0).toFixed(2)}</td>
                        <td class="px-4 py-4 text-right text-blue-600 font-black">${parseFloat(h.consumo_m3 || 0).toFixed(2)}</td>
                        <td class="px-4 py-4 text-right text-slate-500 font-bold">${parseFloat(h.consumos_litros || 0).toFixed(0)}</td>
                        <td class="px-4 py-4 text-right text-rose-600 font-black bg-rose-50/20">${adeudo > 0 ? '$' + adeudo.toFixed(2) : '-'}</td>
                        <td class="px-4 py-4 text-right text-green-600 font-black bg-green-50/20">${saldoFavor > 0 ? '$' + saldoFavor.toFixed(2) : '-'}</td>
                        <td class="px-4 py-4 text-right text-slate-900 font-black text-sm bg-slate-50/30">$${parseFloat(h.total_a_pagar || 0).toFixed(2)}</td>
                    </tr>
                `;
            }).join('');
            $body.html(html);
        }

        $modal.removeClass('hidden');
        setTimeout(() => {
            $backdrop.removeClass('opacity-0');
            $content.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 50);

        lucide.createIcons();
    }

    closeHistoryModal() {
        const config = HISTORIAL_CONFIG.MODAL_HISTORY;
        const $modal = $(config.id);
        const $backdrop = $(config.backdrop);
        const $content = $(config.content);

        $backdrop.addClass('opacity-0');
        $content.addClass('scale-95 opacity-0').removeClass('scale-100 opacity-100');
        
        setTimeout(() => {
            $modal.addClass('hidden');
        }, 300);
    }

    // ───────────────────────────────────────────
    //  MODAL LOG DE MOVIMIENTOS — ESTADO DE CUENTA
    // ───────────────────────────────────────────

    async openMovementsLog() {
        if (!this.currentDeptoId) return;

        const $modal = $('#modal-movements-log');
        const $backdrop = $('#modal-log-backdrop');
        const $content = $('#modal-log-content');

        $('#modal-log-title').text('Estado de Cuenta');
        $('#modal-log-icon').removeClass('bg-rose-600 shadow-rose-200').addClass('bg-blue-600 shadow-blue-200');
        $('#modal-log-depto').text(`Depto ${this.currentDeptoNum}`);

        // Mostrar modal
        $modal.removeClass('hidden');
        setTimeout(() => {
            $backdrop.removeClass('opacity-0');
            $content.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 50);

        // Fetch data
        try {
            const url = API_BASE_URL + 'historial/movimientos/' + this.currentDeptoId;
            const response = await fetch(url);
            if (!response.ok) throw new Error('Error al cargar movimientos');
            const data = await response.json();

            // Populate summaries
            $('#log-sum-cargos').text('$' + data.totalCargos.toFixed(2));
            $('#log-sum-abonos').text('$' + data.totalAbonos.toFixed(2));
            const neto = data.saldoNeto;
            $('#log-sum-neto').text((neto >= 0 ? '$' : '-$') + Math.abs(neto).toFixed(2));
            $('#log-sum-neto').removeClass('text-green-700 text-rose-700 text-slate-900')
                .addClass(neto > 0 ? 'text-rose-700' : neto < 0 ? 'text-green-700' : 'text-slate-900');

            const movs = data.movimientos || [];
            $('#log-count').text(`${movs.length} movimiento${movs.length !== 1 ? 's' : ''}`);
            this.renderMovementsTimeline(movs);
            lucide.createIcons();

        } catch (error) {
            console.error('Error movimientos:', error);
            $('#modal-log-body').html('<div class="text-center py-10 text-rose-400 text-sm">Error al cargar movimientos</div>');
        }
    }

    renderMovementsTimeline(movimientos) {
        const $body = $('#modal-log-body');

        if (!movimientos || movimientos.length === 0) {
            $body.html(`
                <div class="text-center py-12 text-gray-300">
                    <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
                    <p class="text-sm font-bold text-gray-400">Sin movimientos registrados</p>
                    <p class="text-xs text-gray-300 mt-1">Los cargos y pagos aparecerán aquí</p>
                </div>
            `);
            return;
        }

        // Calculate running balance (oldest to newest)
        const sorted = [...movimientos].reverse();
        let saldo = 0;
        const balances = sorted.map(m => {
            if (m.tipo === 'cargo') saldo += parseFloat(m.monto);
            else saldo -= parseFloat(m.monto);
            return saldo;
        });
        balances.reverse();

        // Group by period for section headers
        let lastPeriodo = null;

        const html = movimientos.map((m, idx) => {
            const monto = parseFloat(m.monto || 0);
            const bal = balances[idx];
            const fecha = m.fecha ? new Date(m.fecha) : null;
            const fechaStr = fecha ? fecha.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }) : '---';
            const horaStr = fecha ? fecha.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) : '';

            // Type config
            let icon, color, label, signIcon;
            const desc = (m.descripcion || '').toUpperCase();
            
            if (m.tipo === 'cargo') {
                icon = 'circle-arrow-up'; 
                color = 'rose';
                signIcon = '+';
                if (desc.includes('ADICIONAL')) label = 'ADICIONAL';
                else if (desc.includes('RECARGO') || desc.includes('AJUSTE')) label = 'AJUSTE';
                else label = 'CONSUMO';
            } else if (m.tipo === 'pago') {
                icon = 'circle-arrow-down'; color = 'emerald'; label = 'PAGO'; signIcon = '-';
            } else if (m.tipo === 'ajuste') {
                icon = 'wrench'; color = 'amber'; label = 'AJUSTE'; signIcon = '-';
            } else {
                icon = 'help-circle'; color = 'slate'; label = 'MOV'; signIcon = '±';
            }

            // Status badge for this movement's balance
            let statusBadge, statusColor;
            if (bal > 0.01) {
                statusBadge = `Adeudo: $${bal.toFixed(2)}`;
                statusColor = 'bg-rose-50 text-rose-700 border-rose-200';
            } else if (bal < -0.01) {
                statusBadge = `A favor: $${Math.abs(bal).toFixed(2)}`;
                statusColor = 'bg-green-50 text-green-700 border-green-200';
            } else {
                statusBadge = 'Saldo en cero';
                statusColor = 'bg-slate-50 text-slate-500 border-slate-200';
            }

            // Period separator logic
            let periodHeader = '';
            let currentPeriodo = m.periodo_nombre || 'General';

            if (currentPeriodo !== lastPeriodo) {
                lastPeriodo = currentPeriodo;
                const totalPer = m.total_a_pagar ? `$${parseFloat(m.total_a_pagar).toFixed(2)}` : '';
                periodHeader = `
                    <div class="flex items-center space-x-3 mb-4 ${idx > 0 ? 'mt-8 pt-6 border-t border-slate-100' : 'mt-2'}">
                        <div class="w-8 h-8 bg-slate-900 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-slate-200">
                            <i data-lucide="calendar" class="w-4 h-4 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-xs font-black text-slate-800 uppercase tracking-widest">Periodo ${currentPeriodo}</h4>
                            ${totalPer ? `<p class="text-[10px] text-slate-400 font-bold mt-0.5">Cargo inicial en este periodo: ${totalPer}</p>` : ''}
                        </div>
                    </div>
                `;
            }

            return `
                ${periodHeader}
                <div class="flex items-start group relative ml-2">
                    <!-- Timeline Dot + Line -->
                    <div class="flex flex-col items-center mr-3 flex-shrink-0">
                        <div class="w-7 h-7 rounded-full bg-${color}-100 border-2 border-${color}-300 flex items-center justify-center z-10 shadow-sm">
                            <i data-lucide="${icon}" class="w-3.5 h-3.5 text-${color}-600"></i>
                        </div>
                        ${idx < movimientos.length - 1 ? '<div class="w-0.5 flex-1 bg-slate-100 my-1 min-h-[16px]"></div>' : ''}
                    </div>
                    <!-- Card -->
                    <div class="flex-1 bg-white border border-slate-100 rounded-xl p-3 mb-2.5 hover:shadow-md transition-all hover:border-${color}-200">
                        <!-- Row 1: Type + Amount -->
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center space-x-2">
                                <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded-md bg-${color}-100 text-${color}-700 border border-${color}-200">${label}</span>
                                <h5 class="text-[11px] font-black text-slate-800 uppercase tracking-tight">${m.descripcion || 'Sin descripción'}</h5>
                            </div>
                            <span class="text-sm font-black text-${color}-700 font-mono">${signIcon}$${monto.toFixed(2)}</span>
                        </div>
                        <!-- Row 2: Date + Balance Status -->
                        <div class="flex items-center justify-between mt-1.5">
                            <div class="flex items-center space-x-2 text-[9px] text-slate-400">
                                <i data-lucide="clock" class="w-3 h-3 flex-shrink-0"></i>
                                <span class="font-bold">${fechaStr}</span>
                                <span class="text-slate-300">|</span>
                                <span class="font-mono text-slate-300">${horaStr}</span>
                                ${m.tipo !== 'cargo' ? `
                                    <span class="text-slate-300">|</span>
                                    <button class="btn-delete-mov text-rose-400 hover:text-rose-600 font-black uppercase text-[8px] tracking-widest ml-2" 
                                            data-id="${m.id_movimiento}">
                                        Eliminar
                                    </button>
                                ` : ''}
                            </div>
                            <span class="text-[8px] font-black px-1.5 py-0.5 rounded border ${statusColor}">${statusBadge}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        $body.html(html);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    closeMovementsLog() {
        const $modal = $('#modal-movements-log');
        const $backdrop = $('#modal-log-backdrop');
        const $content = $('#modal-log-content');

        $backdrop.addClass('opacity-0');
        $content.addClass('scale-95 opacity-0').removeClass('scale-100 opacity-100');
        
        setTimeout(() => {
            $modal.addClass('hidden');
        }, 300);
    }

    async deleteMovement(id) {
        if (!confirm('¿Realmente deseas eliminar este movimiento financiero? El saldo se recalculará automáticamente.')) return;

        try {
            const response = await fetch(API_BASE_URL + 'historial/movimiento/' + id, {
                method: 'DELETE'
            });

            if (!response.ok) {
                const res = await response.json();
                throw new Error(res.message || 'Fallo al eliminar movimiento');
            }

            this.showToast('¡Movimiento Eliminado!', 'El saldo ha sido actualizado.', 'success');
            
            // Recargar datos en el panel del depto y en la tabla principal
            await this.fetchHistorial(this.selectedBuildingId);
            this.renderTable();
            await this.openDetailPanel(this.currentDeptoId);
            await this.openMovementsLog(); // Refrescar el log abierto

        } catch (error) {
            this.showToast('Error', error.message, 'error');
        }
    }

    /**
     * Muestra una notificación visual elegante (Toast) - FIJADO Y ALINEADO
     */
    showToast(title, message, type = 'info') {
        const id = 'toast-' + Math.random().toString(36).substr(2, 9);
        let bgColor, icon, textColor;

        switch (type) {
            case 'success':
                bgColor = 'bg-green-600'; icon = 'check-circle'; textColor = 'text-green-50';
                break;
            case 'error':
                bgColor = 'bg-rose-600'; icon = 'alert-triangle'; textColor = 'text-rose-50';
                break;
            case 'warning':
                bgColor = 'bg-amber-500'; icon = 'alert-circle'; textColor = 'text-amber-50';
                break;
            default:
                bgColor = 'bg-blue-600'; icon = 'info'; textColor = 'text-blue-50';
        }

        const toastHtml = `
            <div id="${id}" class="flex items-center w-full max-w-xs p-4 mb-4 ${bgColor} rounded-2xl shadow-2xl opacity-0 transform translate-x-8 transition-all duration-500 ease-out z-[99999]">
                <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 ${textColor} bg-white/20 rounded-lg">
                    <i data-lucide="${icon}" class="w-4 h-4 text-white"></i>
                </div>
                <div class="ml-3">
                    <h4 class="text-xs font-black text-white uppercase tracking-tight">${title}</h4>
                    <p class="text-[10px] font-medium text-white/90 leading-tight">${message}</p>
                </div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-transparent text-white/50 hover:text-white rounded-lg p-1.5 inline-flex h-8 w-8" onclick="document.getElementById('${id}').remove()">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        `;

        // Contenedor de toasts si no existe (Asegurando posición absoluta en pantalla)
        let $container = $('#toast-container');
        if ($container.length === 0) {
            $('body').append('<div id="toast-container" class="fixed top-6 right-6 w-auto max-w-xs space-y-3 z-[999999]" style="pointer-events: none;"></div>');
            $container = $('#toast-container');
        }

        const $toast = $(toastHtml);
        $toast.css('pointer-events', 'auto'); // El toast sí debe recibir clics, el contenedor no para no bloquear
        $container.append($toast);
        
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Pequeño delay para disparar la animación de entrada
        setTimeout(() => {
            $toast.removeClass('opacity-0 translate-x-8').addClass('opacity-100 translate-x-0');
        }, 50);

        // Eliminar automáticamente después de 5 segundos
        setTimeout(() => {
            $toast.removeClass('opacity-100 translate-x-0').addClass('opacity-0 translate-x-8');
            setTimeout(() => $toast.remove(), 600);
        }, 5000);
    }

    // ───────────────────────────────────────────
    //  SISTEMA DE CHAT DE NOTAS
    // ───────────────────────────────────────────

    async openNotesModal(id_lectura, periodo = 'Actual') {
        this.currentActiveLecturaId = id_lectura;
        
        const $modal = $('#modal-notes-chat');
        const $backdrop = $('#modal-notes-backdrop');
        const $content = $('#modal-notes-content');
        const $body = $('#notes-chat-body');

        $('#modal-notes-subtitle').text(`Historial de Notas del Depto`);
        $body.html(`
            <div class="flex flex-col items-center justify-center py-10 space-y-4">
                <i data-lucide="loader-2" class="w-8 h-8 animate-spin text-indigo-600"></i>
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Sincronizando Muro Histórico...</p>
            </div>
        `);
        lucide.createIcons();

        // Mostrar UI
        $modal.removeClass('hidden');
        setTimeout(() => {
            $backdrop.removeClass('opacity-0');
            $content.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 50);

        // Si ya tenemos las notas cargadas del panel (Lazy loading terminado)
        if (this.sidebarNotesReady && this.currentNotasGlobales) {
            this.renderChatBubbles(this.currentNotasGlobales);
        } else {
            // Sincronización de emergencia: Si no están listas, las pedimos ahora mismo
            try {
                const r = await fetch(API_BASE_URL + 'historial/sidebar-notes/' + this.currentDeptoId);
                const notes = await r.json();
                this.currentNotasGlobales = notes || [];
                this.sidebarNotesReady = true;
                this.renderChatBubbles(this.currentNotasGlobales);
            } catch (e) {
                $body.html('<div class="text-center py-10 text-rose-500 font-bold uppercase text-xs">Error al sincronizar historial</div>');
            }
        }
    }

    renderChatBubbles(notas) {
        const $body = $('#notes-chat-body');
        if (!notas || notas.length === 0) {
            $body.html(`
                <div class="flex flex-col items-center justify-center py-12 text-slate-300">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="message-square-dashed" class="w-8 h-8"></i>
                    </div>
                    <p class="text-sm font-bold uppercase tracking-widest">Sin comentarios históricos</p>
                    <p class="text-xs italic mt-1 text-slate-400">Las notas aparecerán aquí consolidadas</p>
                </div>
            `);
            lucide.createIcons();
            return;
        }

        let lastPeriod = null;
        const html = notas.map((n) => {
            const date  = n.date ? new Date(n.date) : null;
            const timeStr = date ? date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) : '';
            const dateStr = date ? date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' }) : '';
            const per   = n.periodo || 'N/A';
            
            // Comparación robusta
            const canDelete = Number(n.id_lectura) === Number(this.currentActiveLecturaId);

            let periodHeader = '';
            if (per !== lastPeriod) {
                periodHeader = `
                    <div class="flex items-center justify-center my-6">
                        <div class="h-px bg-slate-100 flex-1"></div>
                        <span class="mx-4 text-[10px] font-black text-slate-400 bg-slate-50 px-3 py-1 rounded-full uppercase tracking-[0.2em] border border-slate-100 shadow-sm">
                            ${per}
                        </span>
                        <div class="h-px bg-slate-100 flex-1"></div>
                    </div>
                `;
                lastPeriod = per;
            }

            return `
                ${periodHeader}
                <div class="flex flex-col items-end space-y-1 group">
                    <div class="bg-indigo-600 text-white p-4 rounded-3xl rounded-tr-none shadow-md max-w-[85%] relative">
                        <p class="text-sm font-medium leading-relaxed">${n.text}</p>
                        
                        <div class="flex items-center justify-end space-x-3 mt-2 pt-2 border-t border-white/10">
                            <!-- Botón Borrar (Siempre visible dentro de la burbuja si es el periodo actual) -->
                            ${canDelete ? `
                                <button class="btn-delete-note flex items-center space-x-1 text-rose-200 hover:text-rose-400 transition-colors" 
                                        data-id-lectura="${n.id_lectura}"
                                        data-index="${n.index}" 
                                        title="Eliminar nota">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                    <span class="text-[8px] font-black uppercase">Borrar</span>
                                </button>
                                <span class="w-px h-2 bg-white/20"></span>
                            ` : ''}

                            <span class="text-[9px] font-black uppercase tracking-tighter opacity-70">${n.user || 'Admin'}</span>
                            <span class="text-[9px] font-bold opacity-50">${dateStr} ${timeStr}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        $body.html(html);
        // Scroll al final
        $body.scrollTop($body[0].scrollHeight);
    }

    async refreshChat() {
        if (!this.currentDeptoId) return;
        try {
            const r = await fetch(API_BASE_URL + 'historial/sidebar-notes/' + this.currentDeptoId);
            const notes = await r.json();
            this.currentNotasGlobales = notes || [];
            this.renderChatBubbles(this.currentNotasGlobales);
        } catch (e) {
            console.error('Error al refrescar chat:', e);
        }
    }

    async sendNote() {
        const $input = $('#input-new-note');
        const text = $input.val().trim();
        if (!text || !this.currentActiveLecturaId) return;

        const $btn = $('#btn-send-note');
        $btn.prop('disabled', true);

        try {
            const response = await fetch(API_BASE_URL + 'historial/add-nota', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_lectura: this.currentActiveLecturaId,
                    texto: text
                })
            });

            if (!response.ok) throw new Error('Fallo al guardar nota');

            $input.val('');
            this.showToast('¡Nota Guardada!', 'El comentario se añadió al historial.', 'success');
            
            // Recargar datos y refrescar la burbujas del chat inmediatamente
            await this.refreshChat();
            
            // También refrescar el fondo (Panel y Tabla) para sincronizar iconos
            this.fetchHistorial(this.selectedBuildingId).then(() => this.renderTable());
            if (this.currentDeptoId) this.openDetailPanel(this.currentDeptoId);

        } catch (error) {
            this.showToast('Error', error.message, 'error');
        } finally {
            $btn.prop('disabled', false);
        }
    }

    async deleteNote(id_lectura, index) {
        if (!confirm('¿Realmente deseas eliminar este comentario?')) return;

        try {
            const response = await fetch(API_BASE_URL + 'historial/delete-nota', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_lectura: id_lectura,
                    index: index
                })
            });

            if (!response.ok) throw new Error('Fallo al eliminar nota');

            this.showToast('¡Nota Eliminada!', 'El comentario ha sido removido.', 'info');
            
            // Refrescar el chat inmediatamente
            await this.refreshChat();
            
            // Sincronizar el resto de la UI
            this.fetchHistorial(this.selectedBuildingId).then(() => this.renderTable());
            if (this.currentDeptoId) this.openDetailPanel(this.currentDeptoId);

        } catch (error) {
            this.showToast('Error', error.message, 'error');
        }
    }

    closeNotesModal() {
        const $modal = $('#modal-notes-chat');
        const $backdrop = $('#modal-notes-backdrop');
        const $content = $('#modal-notes-content');

        $backdrop.addClass('opacity-0');
        $content.addClass('scale-95 opacity-0').removeClass('scale-100 opacity-100');
        
        setTimeout(() => {
            $modal.addClass('hidden');
            this.currentActiveLecturaId = null;
        }, 300);
    }

    /**
     * Abrir modal de evidencia fotográfica
     */
    viewEvidence(fotoPath) {
        if (!fotoPath) {
            this.showToast('Sin Evidencia', 'No se subió foto para esta lectura.', 'warning');
            return;
        }

        const $modal = $('#modal-evidence-viewer');
        const $img = $('#evidence-image-preview');
        const $backdrop = $('#modal-evidence-backdrop');
        const $content = $('#modal-evidence-content');

        // Construir URL completa
        const fullPath = `apis_marvi/public/uploads/lecturas/${fotoPath}`;
        $img.attr('src', fullPath);

        $modal.removeClass('hidden');
        setTimeout(() => {
            $backdrop.removeClass('opacity-0');
            $content.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 50);

        lucide.createIcons();
    }

    closeEvidenceModal() {
        const $modal = $('#modal-evidence-viewer');
        const $backdrop = $('#modal-evidence-backdrop');
        const $content = $('#modal-evidence-content');

        $backdrop.addClass('opacity-0');
        $content.addClass('scale-95 opacity-0').removeClass('scale-100 opacity-100');
        
        setTimeout(() => {
            $modal.addClass('hidden');
        }, 300);
    }

    /**
     * Descarga masiva de PDFs en un paquete ZIP
     */
    async downloadZip() {
        if (!this.selectedBuildingId) {
            showToast('Seleccione un edificio primero', 'warning');
            return;
        }

        const $btn = $('#download-all-pdfs-btn');
        const originalHtml = $btn.html();
        
        showToast('Preparando paquete de recibos...', 'info');
        $btn.prop('disabled', true).addClass('opacity-50');
        $btn.find('span').text('Generando Lote...');

        try {
            const response = await fetch(API_BASE_URL + 'historial/descargar-zip/' + this.selectedBuildingId);
            
            const contentType = response.headers.get("content-type");
            
            // Caso 1: Error o Diagnóstico (JSON)
            if (contentType && contentType.includes("application/json")) {
                const res = await response.json();
                if (res.status === 'error') {
                    this.showZipFeedback(res.details);
                    return;
                }
                throw new Error(res.message || 'Error en el servidor');
            }

            // Caso 2: Éxito (Stream de Datos / ZIP)
            if (!response.ok) throw new Error('No se pudo establecer conexión con el motor de descargas');

            // NUEVO: Verificar si hay un reporte de diagnóstico en los headers (incluso si la descarga es exitosa)
            const diagnosisHeader = response.headers.get("X-Diagnosis-Report");
            if (diagnosisHeader) {
                try {
                    const diagnosisData = JSON.parse(atob(diagnosisHeader));
                    if ((diagnosisData.missing_readings && diagnosisData.missing_readings.length > 0) || 
                        (diagnosisData.missing_files && diagnosisData.missing_files.length > 0)) {
                        this.showZipFeedback(diagnosisData);
                    }
                } catch (e) { console.error("Error al decodificar diagnóstico:", e); }
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `Recibos_${this.selectedBuilding.replace(/\s+/g, '_')}_Periodo_Actual.zip`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();

            showToast('¡Descarga iniciada con éxito!', 'success');

        } catch (error) {
            console.error('Error ZIP:', error);
            showToast(error.message, 'error');
        } finally {
            $btn.prop('disabled', false).removeClass('opacity-50');
            $btn.html(originalHtml);
            lucide.createIcons();
        }
    }

    /**
     * Muestra el feedback de por qué no se pudo generar el ZIP
     */
    showZipFeedback(details) {
        const $modal = $('#modal-zip-diagnosis');
        const $backdrop = $('#modal-zip-backdrop');
        const $content = $('#modal-zip-content');
        const $body = $('#zip-diagnosis-body');

        let html = '';

        if (details.missing_readings && details.missing_readings.length > 0) {
            html += `
                <div class="space-y-3">
                    <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center">
                        <span class="w-1.5 h-1.5 bg-rose-500 rounded-full mr-2"></span>
                        Departamentos sin lectura capturada
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        ${details.missing_readings.map(d => `<span class="px-3 py-1 bg-rose-50 text-rose-700 text-[10px] font-black rounded-lg border border-rose-100 italic">Depto ${d}</span>`).join('')}
                    </div>
                </div>
            `;
        }

        if (details.missing_files && details.missing_files.length > 0) {
            html += `
                <div class="space-y-3 pt-4 border-t border-dashed border-gray-100">
                    <h4 class="text-[10px] font-black text-amber-600 uppercase tracking-widest flex items-center">
                        <span class="w-1.5 h-1.5 bg-amber-400 rounded-full mr-2"></span>
                        PDFs no generados (Pendiente clic "Generar")
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        ${details.missing_files.map(d => `<span class="px-3 py-1 bg-amber-50 text-amber-700 text-[10px] font-black rounded-lg border border-amber-100 italic">Depto ${d}</span>`).join('')}
                    </div>
                </div>
            `;
        }

        $body.html(html || '<p class="text-center text-slate-400 text-sm italic">Error de inconsistencia de datos.</p>');

        $modal.removeClass('hidden');
        setTimeout(() => {
            $backdrop.removeClass('opacity-0');
            $content.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 50);

        lucide.createIcons();
    }

    closeZipModal() {
        const $modal = $('#modal-zip-diagnosis');
        const $backdrop = $('#modal-zip-backdrop');
        const $content = $('#modal-zip-content');

        $backdrop.addClass('opacity-0');
        $content.addClass('scale-95 opacity-0').removeClass('scale-100 opacity-100');
        
        setTimeout(() => {
            $modal.addClass('hidden');
        }, 300);
    }

    /**
     * Motor de Ayuda Universal
     */
    showHelp(topic) {
        const data = Historial.HELP_TOPICS[topic];
        if (!data) return;

        const $modal = $('#modal-global-help');
        const $backdrop = $('#modal-help-backdrop');
        const $content = $('#modal-help-content');
        
        $modal.find('h2').text(data.title);
        $('#help-modal-body').html(data.body);

        $modal.removeClass('hidden');
        setTimeout(() => {
            $backdrop.removeClass('opacity-0');
            $content.removeClass('scale-95 opacity-0').addClass('scale-100 opacity-100');
        }, 10);
        lucide.createIcons();
    }

    closeHelpModal() {
        const $modal = $('#modal-global-help');
        const $backdrop = $('#modal-help-backdrop');
        const $content = $('#modal-help-content');

        $backdrop.addClass('opacity-0');
        $content.addClass('scale-95 opacity-0').removeClass('scale-100 opacity-100');
        setTimeout(() => $modal.addClass('hidden'), 300);
    }

    /**
     * Motor de Búsqueda de Última Tecnología (IA Style)
     * Ahora actualiza la tabla principal en tiempo real.
     */
    async performOmniSearch(q) {
        const $dropdown = $('#search-omni-results');
        const $body = $('#search-results-body');

        if (!q || q.length < 2) {
            $dropdown.addClass('hidden');
            $body.empty();
            // Restaurar datos originales del edificio si se limpia la búsqueda
            if (this.selectedBuildingId) {
                this.fetchHistorial(this.selectedBuildingId);
            }
            return;
        }

        // Indicador de búsqueda activa en el dropdown (opcional como preview)
        $body.html('<div class="p-4 text-center"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mx-auto"></div></div>');
        $dropdown.removeClass('hidden');

        try {
            const response = await fetch(API_BASE_URL + 'historial/buscar?q=' + encodeURIComponent(q));
            if (!response.ok) throw new Error('Error en búsqueda');
            const results = await response.json();
            
            // Actualizar el estado de datos global para que la tabla renderice estos resultados
            this.data = results;
            this.isSearchMode = true; // Flag para renderTable
            this.renderTable();
            
            // También mostramos un resumen rápido en el dropdown
            this.renderOmniResults(results);
        } catch (e) { 
            console.error("Error OmniSearch:", e);
        }
    }

    renderOmniResults(results) {
        const $dropdown = $('#search-omni-results');
        const $body = $('#search-results-body');
        
        if (!results || results.length === 0) {
            $body.html('<div class="p-4 text-center text-[10px] font-bold text-slate-400 uppercase">Sin hallazgos globales</div>');
            return;
        }

        $body.html(`
            <div class="px-4 py-2 bg-blue-50/50">
                <span class="text-[9px] font-black text-blue-600 uppercase tracking-tighter">Se encontraron ${results.length} coincidencias en la tabla</span>
            </div>
        `);
    }

    /**
     * Ejecuta una acción de PDF (Generar, Recrear, Enviar Email)
     */
    async executePdfAction(id, options = {}) {
        const { force = 0, nosend = 0 } = options;
        const url = `${API_BASE_URL}historial/pdf/${id}?ajax=1&force=${force}&nosend=${nosend}`;
        
        if (window.showToast) window.showToast('Procesando PDF...', 'info');
        
        try {
            const response = await fetch(url);
            const res = await response.json();
            
            if (!response.ok) throw new Error(res.message || 'Error al generar PDF');

            if (window.showToast) window.showToast(res.message || 'PDF Generado', 'success');
            
            if (res.email && !res.email.status) {
                if (window.showToast) window.showToast(res.email.message, 'error');
            }

            this.fetchHistorial(this.selectedBuildingId).then(() => this.renderTable());
        } catch (error) {
            if (window.showToast) window.showToast(error.message, 'error');
        }
    }
}
