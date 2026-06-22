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
        
        // Cache-buster: fuerza petición fresca ignorando caché del navegador/proxy
        const url = API_BASE_URL + 'historial/edificio/' + id_edificio + '?_t=' + Date.now();
        try {
            const response = await fetch(url, { cache: 'no-store' });
            if (!response.ok) throw new Error('Error API Historial: ' + response.statusText);
            const result = await response.json();
            this.data = result.data || [];
            this.renderAll();
            
            // Asynchronously fetch and populate the previous period's M3 readings
            this.fetchPreviousHistorial(id_edificio);
        } catch (error) {
            console.error('Error cargando historial:', error);
            this.showToast('Error de Carga', error.message, 'error');
            this.data = [];
            this.renderAll();
        } finally {
            this._isFetchingHistorial = null;
        }
    }

    async fetchPreviousHistorial(id_edificio) {
        const url = API_BASE_URL + 'historial/edificio-anterior/' + id_edificio;
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Error API Anterior: ' + response.statusText);
            const prevData = await response.json();
            
            // Create a Map for fast lookup
            const prevMap = new Map();
            prevData.forEach(item => {
                prevMap.set(String(item.id_departamento), {
                    ltAnt: parseFloat(item.consumos_litros_ant || 0),
                    saldoAntRecibo: parseFloat(item.saldo_anterior_recibo || 0),
                    saldoIni: parseFloat(item.saldo_inicial || 0)
                });
            });

            // Update local state for future rendering/filtering
            this.data.forEach(item => {
                const prev = prevMap.get(String(item.id_departamento)) || { ltAnt: 0, saldoAntRecibo: 0, saldoIni: 0 };
                item.consumos_litros_ant = prev.ltAnt;
                item.saldo_anterior_recibo = prev.saldoAntRecibo;
                item.saldo_inicial = prev.saldoIni;
            });

            // Dynamically paint the DOM cells in-place for blazing fast response
            prevMap.forEach((prev, deptId) => {

                const $ltCell = $(`.lt-ant-cell[data-dept-id="${deptId}"]`);
                if ($ltCell.length) {
                    $ltCell.html(prev.ltAnt > 0 ? `<span class="text-xs font-bold text-gray-500">${prev.ltAnt.toFixed(0)}</span>` : `<span class="text-gray-300">—</span>`);
                }

                const $saldoCell = $(`.saldo-ant-cell[data-dept-id="${deptId}"]`);
                if ($saldoCell.length) {
                    const badge = `<span class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-bold text-gray-600 bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors relative" onclick="window.historial.showReciboBreakdown(${deptId}, this)" title="Ver desglose del periodo anterior">$${prev.saldoAntRecibo.toFixed(2)}</span>`;
                    $saldoCell.html(badge);
                }

                const $saldoIniCell = $(`.saldo-inicial-cell[data-dept-id="${deptId}"]`);
                if ($saldoIniCell.length) {
                    let badge = '';
                    if (prev.saldoIni > 0.05) {
                        badge = `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-100 uppercase tracking-tighter cursor-pointer hover:bg-rose-100 transition-colors group relative" onclick="window.historial.showSaldoBreakdown(${deptId}, this)">Adeudo $${prev.saldoIni.toFixed(2)}</span>`;
                    } else if (prev.saldoIni < -0.05) {
                        badge = `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-tighter cursor-pointer hover:bg-emerald-100 transition-colors group relative" onclick="window.historial.showSaldoBreakdown(${deptId}, this)">A Favor $${Math.abs(prev.saldoIni).toFixed(2)}</span>`;
                    } else {
                        badge = `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200 cursor-pointer hover:bg-slate-200 transition-colors group relative" onclick="window.historial.showSaldoBreakdown(${deptId}, this)">0.00</span>`;
                    }
                    $saldoIniCell.html(badge);
                }
            });

            // Paint fallback zeros for departments without records
            this.data.forEach(item => {
                if (!prevMap.has(String(item.id_departamento))) {

                    const $ltCell = $(`.lt-ant-cell[data-dept-id="${item.id_departamento}"]`);
                    if ($ltCell.length) {
                        $ltCell.html(`<span class="text-gray-300 font-medium text-xs">—</span>`);
                    }

                    const $saldoCell = $(`.saldo-ant-cell[data-dept-id="${item.id_departamento}"]`);
                    if ($saldoCell.length) {
                        $saldoCell.html(`<span class="text-gray-300 font-medium text-xs">—</span>`);
                    }

                    const $saldoIniCell = $(`.saldo-inicial-cell[data-dept-id="${item.id_departamento}"]`);
                    if ($saldoIniCell.length) {
                        $saldoIniCell.html(`<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">0.00</span>`);
                    }
                }
            });

        } catch (error) {
            console.error('Error al cargar consumo y saldo anterior:', error);
            // Replace loaders with 0.00 / '-' in case of failure
            $(`.lt-ant-cell`).each((idx, el) => {
                const $cell = $(el);
                if ($cell.find('.animate-pulse').length) {
                    $cell.html(`<span class="text-gray-300 font-medium text-xs">—</span>`);
                }
            });
            $(`.saldo-ant-cell`).each((idx, el) => {
                const $cell = $(el);
                if ($cell.find('.animate-pulse').length) {
                    $cell.html(`<span class="text-gray-300 font-medium text-xs">—</span>`);
                }
            });
            $(`.saldo-inicial-cell`).each((idx, el) => {
                const $cell = $(el);
                if ($cell.find('.animate-pulse').length) {
                    $cell.html(`<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">0.00</span>`);
                }
            });
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
            $tbody.html('<tr><td colspan="14" class="p-12 text-center text-gray-400 font-medium">No se encontraron registros para los criterios ingresados.</td></tr>');
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
            const correo2 = item.correo_2
                ? `<span class="block text-[9px] text-gray-400/70 tracking-tight mt-0.5" title="Correo secundario">alt: ${item.correo_2}</span>`
                : '';

            const hasReading = !!item.id_lectura;
            const cons   = hasReading ? parseFloat(item.consumo_m3 || 0).toFixed(2) : '—';
            const lt     = hasReading ? parseFloat(item.consumos_litros || 0).toFixed(0) : '—';
            const lectura_ini = hasReading ? parseFloat(item.lectura_ini || 0).toFixed(2) : '—';
            const lectura_fin = hasReading ? parseFloat(item.lectura_fin || 0).toFixed(2) : '—';
            
            const totalPeriodo = parseFloat(item.total_a_pagar || 0);
            const saldoTotal   = parseFloat(item.saldo_total   || 0);

            // NUEVO: Consumo Lt Anterior, Saldo Anterior de Recibo y Saldo Inicial
            const hasPrevData = item.saldo_anterior_recibo !== undefined;
            const consumoLtAnt = hasPrevData ? parseFloat(item.consumos_litros_ant || 0).toFixed(0) : null;
            const saldoAntRecibo = hasPrevData ? parseFloat(item.saldo_anterior_recibo || 0) : null;
            const saldoInicial = hasPrevData ? parseFloat(item.saldo_inicial || 0) : null;

            let ltAntBadge;
            if (hasPrevData) {
                ltAntBadge = consumoLtAnt !== null && parseFloat(consumoLtAnt) > 0
                    ? `<span class="text-xs font-bold text-gray-500">${consumoLtAnt}</span>`
                    : '<span class="text-gray-300">—</span>';
            } else {
                ltAntBadge = `<span class="inline-block px-2.5 py-1 rounded bg-slate-50 border border-slate-100 text-slate-300 text-[10px] font-bold animate-pulse">...</span>`;
            }

            let prevBalanceBadge;
            if (hasPrevData) {
                prevBalanceBadge = `<span class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-bold text-gray-600 bg-gray-50 border border-gray-200 cursor-pointer hover:bg-gray-100 transition-colors relative" onclick="window.historial.showReciboBreakdown(${item.id_departamento}, this)" title="Ver desglose del periodo anterior">$${saldoAntRecibo.toFixed(2)}</span>`;
            } else {
                prevBalanceBadge = `<span class="inline-block px-2.5 py-1 rounded bg-slate-50 border border-slate-100 text-slate-300 text-[10px] font-bold animate-pulse">...</span>`;
            }

            let initialBalanceBadge;
            if (hasPrevData) {
                if (saldoInicial > 0.05) {
                    initialBalanceBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-100 uppercase tracking-tighter cursor-pointer hover:bg-rose-100 transition-colors group relative" onclick="window.historial.showSaldoBreakdown(${item.id_departamento}, this)">Adeudo $${saldoInicial.toFixed(2)}</span>`;
                } else if (saldoInicial < -0.05) {
                    initialBalanceBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-tighter cursor-pointer hover:bg-emerald-100 transition-colors group relative" onclick="window.historial.showSaldoBreakdown(${item.id_departamento}, this)">A Favor $${Math.abs(saldoInicial).toFixed(2)}</span>`;
                } else {
                    initialBalanceBadge = `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200 cursor-pointer hover:bg-slate-200 transition-colors group relative" onclick="window.historial.showSaldoBreakdown(${item.id_departamento}, this)">0.00</span>`;
                }
            } else {
                initialBalanceBadge = `<span class="inline-block px-2.5 py-1 rounded bg-slate-50 border border-slate-100 text-slate-300 text-[10px] font-bold animate-pulse">...</span>`;
            }

            const ultimoAbonoMonto = item.ultimo_abono_monto ? parseFloat(item.ultimo_abono_monto).toFixed(2) : null;
            let ultimoAbonoFecha = '';
            if (item.ultimo_abono_fecha) {
                // Remove time part if exists and format
                const datePart = item.ultimo_abono_fecha.split(' ')[0];
                const [year, month, day] = datePart.split('-');
                ultimoAbonoFecha = `${day}/${month}/${year}`;
            }

            let ultimoAbonoBadge;
            if (ultimoAbonoMonto) {
                ultimoAbonoBadge = `<div class="flex flex-col items-center leading-tight">
                                        <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">$${ultimoAbonoMonto}</span>
                                        <span class="text-[9px] text-gray-400 font-medium mt-0.5">${ultimoAbonoFecha}</span>
                                    </div>`;
            } else {
                ultimoAbonoBadge = `<span class="text-gray-300 text-xs font-medium">—</span>`;
            }

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
                            <span class="text-[10px] sm:text-xs font-black text-blue-700 bg-blue-100 uppercase tracking-widest mt-1.5 px-2 py-0.5 rounded-md border border-blue-200 inline-block shadow-sm">
                                ${item.nombre_edificio || 'Edificio'}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex flex-col">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-bold text-gray-900">${titular}</span>
                                ${matchFeedback}
                            </div>
                            <span class="text-[10px] font-medium text-gray-400 tracking-tight">${correo}</span>
                            ${correo2}
                        </div>
                    </td>
                    
                    <!-- LECTURAS -->
                    <td class="px-4 py-4 text-center font-bold text-gray-500 text-sm">
                        ${lectura_ini}
                    </td>
                    <td class="px-4 py-4 text-center font-bold text-gray-700 text-sm">
                        ${lectura_fin}
                    </td>
                    <td class="px-4 py-4 text-center lt-ant-cell font-bold text-gray-500 text-sm" data-dept-id="${item.id_departamento}">
                        ${ltAntBadge}
                    </td>
                    <td class="px-4 py-4 text-center saldo-ant-cell" data-dept-id="${item.id_departamento}">
                        ${prevBalanceBadge}
                    </td>
                    <td class="px-4 py-4 text-center saldo-inicial-cell" data-dept-id="${item.id_departamento}">
                        ${initialBalanceBadge}
                    </td>

                    <td class="px-4 py-4 text-center">
                        ${hasReading
                            ? `<button class="btn-view-evidence group/m3 relative inline-flex items-center justify-center" data-foto="${item.foto || ''}">
                                 <span class="text-xs font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-md border border-blue-100 group-hover/m3:bg-blue-600 group-hover/m3:text-white transition-all shadow-sm">
                                    ${lt}
                                 </span>
                                 <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/m3:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">Ver Evidencia</div>
                               </button>`
                            : '<span class="text-gray-300">—</span>'}
                    </td>
                    <td class="px-4 py-4 text-center font-bold text-gray-700 text-sm">${hasReading ? '$' + totalPeriodo.toFixed(2) : '—'}</td>
                    <td class="px-4 py-4 text-center font-black ${saldoTotal > 0 ? 'text-rose-600' : 'text-emerald-600'} text-sm saldo-actual-cell">
                        ${hasReading ? (saldoTotal != 0 ? '$' + saldoTotal.toFixed(2) : '$0.00') : '—'}
                    </td>
                    <td class="px-4 py-4 text-center ultimo-abono-cell">
                        ${ultimoAbonoBadge}
                    </td>
                    <td class="px-4 py-4 text-center estado-cell">${statusBadge}</td>
                    <td class="px-4 py-4 pr-6 text-right">
                        <div class="flex items-center justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                            <!-- Comunication / Warning Button -->
                            <div class="relative mr-2">
                                <button class="p-2 rounded-lg transition-all shadow-sm border-2 ${saldoTotal > 0 ? 'bg-rose-50 text-rose-600 border-rose-200 hover:bg-rose-100 hover:text-rose-700' : 'bg-indigo-50 text-indigo-600 border-indigo-200 hover:bg-indigo-100 hover:text-indigo-700'} btn-notify-context" 
                                        data-id="${item.id_departamento}"
                                        data-correo="${item.correo && item.correo !== '0' ? item.correo : ''}"
                                        data-correo2="${item.correo_2 && item.correo_2 !== '0' ? item.correo_2 : ''}"
                                        data-nombre="${item.nombre ? (item.nombre + ' ' + (item.ape_pat || '')).trim() : 'Cliente'}"
                                        data-num-depto="${item.num_departamento || ''}"
                                        data-periodo="${item.periodo || ''}"
                                        data-saldo="${saldoTotal || 0}"
                                        title="Enviar Recordatorio/Estado de Cuenta">
                                    <i data-lucide="${saldoTotal > 0 ? 'alert-circle' : 'mail'}" class="w-4 h-4"></i>
                                </button>
                            </div>
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

        // 1. Facturado = Suma de total_a_pagar (solo los que tienen lectura)
        const facturado = this.data.reduce((acc, i) => acc + parseFloat(i.total_a_pagar || 0), 0);

        // 2. Cobranza = % de departamentos con saldo_total <= 0 (liquidados)
        const alCorriente = this.data.filter(i => parseFloat(i.saldo_total || 0) <= 0 && !!i.id_lectura).length;
        const conLectura = this.data.filter(i => !!i.id_lectura).length;
        const cobranza = conLectura > 0 ? (alCorriente / conLectura) * 100 : 0;

        // 3. Consumo Total = Sumatoria de consumos_litros (solo los que tienen lectura)
        const consumoLt = this.data.reduce((acc, i) => acc + parseFloat(i.consumos_litros || 0), 0);

        // 4. Pendiente Total = suma de saldos positivos (lo que aún se debe)
        const saldoPendienteTotal = this.data.reduce((acc, i) => acc + Math.max(0, parseFloat(i.saldo_total || 0)), 0);
        
        // Ingresos recaudados: lo que se facturó menos lo que queda pendiente
        const recaudado = Math.max(0, facturado - saldoPendienteTotal); 

        // Progreso visual
        const progressPercent = facturado > 0 ? (recaudado / facturado) * 100 : 0;

        $('#kpi-facturado').text(`$${facturado.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        $('#kpi-consumo').html(`${consumoLt.toFixed(0)} <span class="text-sm text-gray-400 font-medium">lt</span>`);
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
            // Cache-buster para obtener saldo y abonos siempre frescos del servidor
            const response = await fetch(API_BASE_URL + 'historial/detalle/' + id_departamento + '?_t=' + Date.now(), { cache: 'no-store' });
            if (!response.ok) throw new Error('Error al cargar detalles');

            
            const res = await response.json();
            const { depto, lectura, saldo, abonosPeriodo, periodo, periodo_data, config, lec_ant_sugerida } = res;

            this.currentLecturaId = lectura ? lectura.id_lectura : null;
            this.currentDeptoNum = depto.num_departamento;
            this.currentSaldo = saldo;
            this.currentAbonosPeriodo = res.abonos_periodo !== undefined ? parseFloat(res.abonos_periodo) : (abonosPeriodo || 0);
            this.currentConfig = config || { precioLitro: 0, factor: 1, cuotaAdmin: 0 };
            
            // Usar el saldo inicial real calculado con precisión por el backend
            this.initialBalance = res.saldo_inicial !== undefined ? parseFloat(res.saldo_inicial) : (saldo - (lectura ? parseFloat(lectura.total_a_pagar || 0) : 0));
            
            this.periodAdeudos = lectura ? parseFloat(lectura.adeudos || 0) : 0;
            this.periodSaldoFavor = lectura ? parseFloat(lectura.saldo_favor || 0) : 0;

            // Poblar campos base
            $(selectors.unitName).html(`Depto ${depto.num_departamento} <span class="text-xs text-slate-500 font-bold ml-2 uppercase tracking-widest bg-slate-200/50 px-2 py-1 rounded-md border border-slate-200">${depto.num_edificio || 'Edificio'}</span>`);
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
            // Siempre mostraremos el saldo anterior calculado antes del periodo.
            $(selectors.lblSaldoFavor).text(this.initialBalance < 0 ? '$' + Math.abs(this.initialBalance).toFixed(2) : '$0.00');
            $(selectors.lblAdeudos).text(this.initialBalance > 0 ? '$' + this.initialBalance.toFixed(2) : '$0.00');

            // Inyectar abonos del periodo si es mayor a cero (Conflicto 9)
            if (this.currentAbonosPeriodo > 0.05) {
                $('#lbl-abonos-periodo-container').html(`
                    <div class="bg-emerald-50 border border-emerald-200 p-2.5 rounded-xl flex items-center justify-between px-3 shadow-sm mt-2 animate-in fade-in duration-300">
                        <span class="text-[8px] font-black text-emerald-700 uppercase tracking-wider">Abonos este Periodo:</span>
                        <span class="text-[10px] font-black text-emerald-800">$${this.currentAbonosPeriodo.toFixed(2)}</span>
                    </div>
                `);
            } else {
                $('#lbl-abonos-periodo-container').empty();
            }

            // 4. CARGA FASE 2: HISTORIAL Y NOTAS (Paralelo y Asíncrono)
            this.loadSidebarExtras(id_departamento, periodo);

            lucide.createIcons();
        } catch (error) {
            this.showToast('Error', error.message, 'error');
        }
    }

    /**
     * Refresca solo las celdas que cambian en la fila de un departamento:
     * Saldo Actual, Estado y Último Abono.
     * Actualiza this.data en memoria y pinta quirúrgicamente las celdas
     * SIN re-renderizar toda la tabla (para no perder los datos del periodo anterior).
     */
    async refreshMainTableRow(id_departamento) {
        try {
            const res = await fetch(API_BASE_URL + 'historial/detalle/' + id_departamento + '?_t=' + Date.now());
            if (!res.ok) return;
            const data = await res.json();

            const saldoFresh = parseFloat(data.saldo || 0);
            const lectura = data.lectura;

            // 1. Actualizar this.data en memoria
            const idx = this.data.findIndex(i => String(i.id_departamento) === String(id_departamento));
            if (idx !== -1) {
                this.data[idx].saldo_total = saldoFresh;
                if (lectura) {
                    this.data[idx].id_lectura       = lectura.id_lectura;
                    this.data[idx].consumo_m3       = lectura.consumo_m3;
                    this.data[idx].consumos_litros  = lectura.consumos_litros;
                    this.data[idx].total_a_pagar    = lectura.total_a_pagar;
                    this.data[idx].lectura_ini      = lectura.lectura_ini;
                    this.data[idx].lectura_fin      = lectura.lectura_fin;
                    this.data[idx].cargos_add       = lectura.cargos_add;
                    this.data[idx].foto             = lectura.foto;
                }
                if (data.ultimo_abono) {
                    this.data[idx].ultimo_abono_monto = data.ultimo_abono.monto;
                    this.data[idx].ultimo_abono_fecha = data.ultimo_abono.fecha;
                }
            }

            // 2. Actualizar quirúrgicamente las celdas en el DOM (sin re-renderizar toda la tabla)
            const $row = $(`.depto-checkbox[data-id="${id_departamento}"]`).closest('tr');
            if ($row.length) {
                // Celda: Saldo Actual (buscar por clase específica dentro de la fila)
                const colorClass = saldoFresh > 0 ? 'text-rose-600' : 'text-emerald-600';
                $row.find('td.saldo-actual-cell')
                    .attr('class', `px-4 py-4 text-center font-black ${colorClass} text-sm saldo-actual-cell`)
                    .text(saldoFresh !== 0 ? '$' + saldoFresh.toFixed(2) : '$0.00');

                // Celda: Estado
                let statusHtml;
                const hasReading = !!( lectura ? lectura.id_lectura : (idx !== -1 ? this.data[idx].id_lectura : null) );
                if (!hasReading) {
                    statusHtml = '<span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">Sin Lectura</span>';
                } else if (saldoFresh <= 0) {
                    statusHtml = '<span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-green-100 text-green-700 border border-green-200">Pagado</span>';
                } else {
                    statusHtml = '<span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-100 text-amber-700 border border-amber-200">Pendiente</span>';
                }
                $row.find('td.estado-cell').html(statusHtml);

                // Celda: Último Abono
                if (data.ultimo_abono) {
                    const monto = parseFloat(data.ultimo_abono.monto).toFixed(2);
                    const datePart = (data.ultimo_abono.fecha || '').split(' ')[0];
                    const [year, month, day] = datePart.split('-');
                    const fechaFmt = day && month && year ? `${day}/${month}/${year}` : '';
                    $row.find('td.ultimo-abono-cell').html(`
                        <div class="flex flex-col items-center leading-tight">
                            <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">$${monto}</span>
                            <span class="text-[9px] text-gray-400 font-medium mt-0.5">${fechaFmt}</span>
                        </div>`);
                }
            }

            // 3. Actualizar KPIs (esto sí puede hacerse siempre)
            this.renderKPIs();

        } catch (e) {
            console.error('Error refreshMainTableRow:', e);
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
        $backdrop.addClass('hidden');
        $('#panel-header-actions').empty();
        this.currentDeptoId = null;
        this.currentLecturaId = null;
    }

    /**
     * Muestra el desglose elegante del Saldo Cierre(ant) — lo que arrastró al periodo actual.
     * Agrupa movimientos en Cargos y Abonos, mostrando la fórmula de cierre.
     */
    async showSaldoBreakdown(id_departamento, targetElement) {
        if (!id_departamento) return;
        
        if (this._isFetchingBreakdown) return;
        this._isFetchingBreakdown = true;
        
        const $target = $(targetElement);
        const originalHtml = $target.html();
        $target.html('<i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i>');
        if (typeof lucide !== 'undefined') lucide.createIcons();

        try {
            const response = await fetch(API_BASE_URL + 'historial/breakdown-saldo/' + id_departamento);
            if (!response.ok) throw new Error('Error al cargar desglose');
            
            const movs = await response.json();
            
            $('.saldo-popover').remove();
            $target.html(originalHtml);

            if (!movs || movs.length === 0) {
                this.showToast('Info', 'Sin movimientos en el periodo anterior.', 'info');
                return;
            }

            // Separar y calcular totales
            const cargos  = movs.filter(m => m.tipo === 'cargo');
            const abonos  = movs.filter(m => m.tipo !== 'cargo');
            const totalCargos = cargos.reduce((s, m) => s + parseFloat(m.monto), 0);
            const totalAbonos = abonos.reduce((s, m) => s + parseFloat(m.monto), 0);
            const saldoCierre = totalCargos - totalAbonos;
            const saldoColor  = saldoCierre > 0.05 ? 'text-rose-300' : saldoCierre < -0.05 ? 'text-emerald-300' : 'text-slate-300';
            const saldoLabel  = saldoCierre > 0.05 ? 'Adeudo arrastrado' : saldoCierre < -0.05 ? 'A favor arrastrado' : 'Liquidado';

            const renderRow = (m, isCargo) => {
                const icon  = isCargo ? 'trending-up' : 'trending-down';
                const color = isCargo ? 'text-rose-400' : 'text-emerald-400';
                const bg    = isCargo ? 'bg-rose-500/10 border-rose-500/20' : 'bg-emerald-500/10 border-emerald-500/20';
                const sign  = isCargo ? '+' : '−';
                const date  = m.fecha ? new Date(m.fecha).toLocaleDateString('es-MX', {day: '2-digit', month: 'short'}) : '';
                return `
                    <div class="flex items-center justify-between px-2 py-1.5 rounded-lg hover:bg-slate-800/60 transition-colors group">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-5 h-5 rounded-md flex items-center justify-center flex-shrink-0 border ${bg}">
                                <i data-lucide="${icon}" class="w-2.5 h-2.5 ${color}"></i>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-[9px] font-semibold text-slate-200 truncate leading-tight">${m.descripcion || (isCargo ? 'Cargo' : 'Abono')}</span>
                                ${date ? `<span class="text-[8px] text-slate-500">${date}</span>` : ''}
                            </div>
                        </div>
                        <span class="text-[10px] font-black ${color} ml-2 flex-shrink-0">${sign}$${parseFloat(m.monto).toFixed(2)}</span>
                    </div>
                `;
            };

            let html = `
                <div class="saldo-popover absolute z-[200] bottom-full left-1/2 -translate-x-1/2 mb-3 w-80 bg-slate-900 rounded-2xl shadow-2xl border border-slate-700/80 overflow-hidden" onclick="event.stopPropagation()" style="backdrop-filter: blur(16px);">

                    <!-- Header -->
                    <div class="px-3 pt-3 pb-2 border-b border-slate-700/60 flex items-center justify-between bg-gradient-to-r from-slate-800 to-slate-900">
                        <div class="flex items-center gap-2">
                            <div class="w-5 h-5 bg-slate-700 rounded-lg flex items-center justify-center">
                                <i data-lucide="git-branch" class="w-3 h-3 text-slate-400"></i>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-slate-300 uppercase tracking-widest leading-none">Saldo Cierre</p>
                                <p class="text-[8px] text-slate-500 leading-none mt-0.5">Periodo anterior</p>
                            </div>
                        </div>
                        <button onclick="$(this).closest('.saldo-popover').remove(); event.stopPropagation();"
                                class="w-5 h-5 rounded-md flex items-center justify-center text-slate-500 hover:text-white hover:bg-slate-700 transition-all">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>

                    <div class="p-2 space-y-1 max-h-52 overflow-y-auto custom-scrollbar">

                        <!-- CARGOS -->
                        ${cargos.length > 0 ? `
                        <div class="px-1 pt-1">
                            <p class="text-[8px] font-black text-rose-400/80 uppercase tracking-widest mb-1 px-1">
                                Cargos <span class="text-rose-500/60 font-semibold">+$${totalCargos.toFixed(2)}</span>
                            </p>
                            <div class="space-y-0.5">
                                ${cargos.map(m => renderRow(m, true)).join('')}
                            </div>
                        </div>
                        ` : ''}

                        <!-- ABONOS -->
                        ${abonos.length > 0 ? `
                        <div class="px-1 pt-1">
                            <p class="text-[8px] font-black text-emerald-400/80 uppercase tracking-widest mb-1 px-1">
                                Abonos <span class="text-emerald-500/60 font-semibold">−$${totalAbonos.toFixed(2)}</span>
                            </p>
                            <div class="space-y-0.5">
                                ${abonos.map(m => renderRow(m, false)).join('')}
                            </div>
                        </div>
                        ` : ''}
                    </div>

                    <!-- Footer: Resultado -->
                    <div class="px-3 py-2.5 bg-slate-800/70 border-t border-slate-700/60 flex items-center justify-between">
                        <div>
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider">${saldoLabel}</p>
                            <p class="text-[8px] text-slate-600 mt-0.5">= $${totalCargos.toFixed(2)} − $${totalAbonos.toFixed(2)}</p>
                        </div>
                        <span class="text-sm font-black ${saldoColor}">$${Math.abs(saldoCierre).toFixed(2)}</span>
                    </div>

                    <!-- Flecha -->
                    <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-900 border-b border-r border-slate-700 transform rotate-45"></div>
                </div>
            `;

            $target.append(html);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            setTimeout(() => {
                $(document).one('click', function(e) {
                    if (!$(e.target).closest('.saldo-popover').length) {
                        $('.saldo-popover').remove();
                    }
                });
            }, 100);
            
        } catch (error) {
            $target.html(originalHtml);
            this.showToast('Error', error.message, 'error');
        } finally {
            this._isFetchingBreakdown = false;
        }
    }

    /**
     * Muestra el desglose del recibo anterior (para la columna Recibo Ant.)
     */
    async showReciboBreakdown(id_departamento, targetElement) {
        if (!id_departamento) return;
        
        if (this._isFetchingBreakdown) return;
        this._isFetchingBreakdown = true;
        
        const $target = $(targetElement);
        const originalHtml = $target.html();
        $target.html('<i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i>');
        if (typeof lucide !== 'undefined') lucide.createIcons();

        try {
            const response = await fetch(API_BASE_URL + 'historial/breakdown-recibo-ant/' + id_departamento);
            if (!response.ok) throw new Error('Error al cargar desglose');
            
            const data = await response.json();
            
            // Remove existing popovers
            $('.saldo-popover').remove();
            
            $target.html(originalHtml);

            if (!data || !data.desglose || data.desglose.length === 0) {
                this.showToast('Info', 'No hay detalles del recibo anterior.', 'info');
                return;
            }

            let html = `
                <div class="saldo-popover absolute z-[100] bottom-full left-1/2 -translate-x-1/2 mb-2 w-72 bg-slate-900 rounded-xl shadow-2xl border border-slate-700 animate-in fade-in slide-in-from-bottom-2 duration-200" onclick="event.stopPropagation()">
                    <div class="p-3 border-b border-slate-700 bg-slate-800/50 rounded-t-xl flex justify-between items-center">
                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Desglose de Recibo Ant.</span>
                        <button onclick="$(this).closest('.saldo-popover').remove(); event.stopPropagation();" class="text-slate-400 hover:text-white">
                            <i data-lucide="x" class="w-3 h-3"></i>
                        </button>
                    </div>
                    <div class="p-2 max-h-48 overflow-y-auto custom-scrollbar space-y-1">
            `;
            
            data.desglose.forEach(m => {
                const isCargo = m.tipo === 'cargo';
                const icon = isCargo ? 'plus' : 'minus';
                const color = isCargo ? 'text-rose-400' : 'text-emerald-400';
                const bg = isCargo ? 'bg-rose-500/10' : 'bg-emerald-500/10';
                const sign = isCargo ? '+' : '-';
                
                html += `
                    <div class="flex items-center justify-between p-1.5 rounded-lg hover:bg-slate-800 transition-colors">
                        <div class="flex items-center space-x-2">
                            <div class="w-5 h-5 rounded flex items-center justify-center ${bg}">
                                <i data-lucide="${icon}" class="w-3 h-3 ${color}"></i>
                            </div>
                            <span class="text-[10px] font-bold text-slate-200">${m.descripcion}</span>
                        </div>
                        <span class="text-[10px] font-black ${color}">${sign}$${parseFloat(m.monto).toFixed(2)}</span>
                    </div>
                `;
            });
            
            html += `
                    </div>
                    <div class="p-2 bg-slate-800/80 border-t border-slate-700 rounded-b-xl flex justify-between items-center">
                        <span class="text-[10px] font-black text-slate-400 uppercase">Total Recibo</span>
                        <span class="text-xs font-black text-white">$${parseFloat(data.total).toFixed(2)}</span>
                    </div>
                    <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-900 border-b border-r border-slate-700 transform rotate-45"></div>
                </div>
            `;
            
            $target.append(html);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            setTimeout(() => {
                $(document).one('click', function(e) {
                    if (!$(e.target).closest('.saldo-popover').length) {
                        $('.saldo-popover').remove();
                    }
                });
            }, 100);
            
        } catch (error) {
            $target.html(originalHtml);
            this.showToast('Error', error.message, 'error');
        } finally {
            this._isFetchingBreakdown = false;
        }
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
        const lt = Number((m3 * config.factor).toFixed(3));
        const montoGas = Number((lt * config.precioLitro).toFixed(3));

        // Mostrar consumos
        $(selectors.lblConsumom3).text(m3);
        $(selectors.lblConsumolt).text(lt.toFixed(2));
        $(selectors.lblMontoGas).text('$' + montoGas.toFixed(2));

        // 1. Total del Periodo (Solo lo correspondiente a esta lectura/mes)
        let totalPeriodo = montoGas + config.cuotaAdmin + add + ajuste;
        
        // REGLA DE NEGOCIO: Si la lectura actual es igual a la anterior, el pago es 0
        if (m3 === 0 && lecAct > 0) {
            totalPeriodo = 0;
        }

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
            
            // Recargar panel lateral con datos frescos
            await this.openDetailPanel(this.currentDeptoId);
            // Actualizar la fila de este depto en la tabla principal (Saldo Actual + Estado)
            await this.refreshMainTableRow(this.currentDeptoId);

        } catch (error) {
            this.showToast('Error al Guardar', error.message, 'error');
        } finally {
            $(selectors.btnUpdate).prop('disabled', false).text('GUARDAR CAMBIOS');
        }
    }

    async submitPayment() {
        if (this._isSubmittingPayment) return;
        
        const selectors = HISTORIAL_CONFIG.PANEL;
        const monto = parseFloat($(selectors.inputPago).val() || 0);
        const tipo = $(selectors.inputMovTipo).val() || 'pago';

        if (monto <= 0) {
            this.showToast('Monto Inválido', 'Ingrese un valor mayor a cero', 'warning');
            return;
        }

        this._isSubmittingPayment = true;

        const $btn = $(selectors.btnPayment);
        const originalHtml = $btn.html();

        // -- Bloquear botón con spinner --
        $btn.prop('disabled', true)
            .html('<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>')
            .addClass('opacity-70 cursor-not-allowed');
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const restoreBtn = () => {
            setTimeout(() => {
                $btn.prop('disabled', false)
                    .html(originalHtml)
                    .removeClass('opacity-70 cursor-not-allowed');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 3000);
        };

        try {
            const endpoint = (tipo === 'pago') ? 'registrar-pago' : 'registrar-ajuste';
            const payload = {
                id_departamento: this.currentDeptoId,
                monto: (tipo === 'ajuste') ? -monto : monto,
                descripcion: (tipo === 'pago') ? 'Pago de Gas / Abono' :
                             (tipo === 'cargo') ? 'Recargo / Ajuste manual' : 'Rebaja / Ajuste manual'
            };

            if (tipo === 'cargo') {
                payload.monto = monto;
            }

            if (this.currentLecturaId) {
                payload.id_lectura = this.currentLecturaId;
            }

            const response = await fetch(API_BASE_URL + 'historial/' + endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error(`Error al registrar ${tipo}`);

            const result = await response.json();

            const tipoLabel = tipo === 'pago' ? 'Pago / Abono' : tipo === 'cargo' ? 'Recargo' : 'Ajuste';
            this.showToast(`✅ ${tipoLabel} Registrado`, `$${monto.toFixed(2)} aplicado correctamente`, 'success');

            $(selectors.inputPago).val('');

            // Refrescar fila en la tabla principal correctamente
            await this.refreshMainTableRow(this.currentDeptoId);

            // Cerrar el panel lateral
            this.closeDetailPanel();

        } catch (error) {
            this.showToast('❌ Error al Registrar', error.message || 'No se pudo conectar con el servidor', 'error');
        } finally {
            this._isSubmittingPayment = false;
            // Restaurar botón siempre 3 segundos después de recibir respuesta
            restoreBtn();
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
        
        $modal.addClass('hidden');
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
                                ${(m.tipo === 'pago' || m.referencia_tipo !== 'lectura') ? `
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
        
        $modal.addClass('hidden');
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
            
            // Recargar datos quirúrgicamente para no perder el estado de prevHistorial
            await this.refreshMainTableRow(this.currentDeptoId);
            
            await this.openDetailPanel(this.currentDeptoId);
            await this.openMovementsLog(); // Refrescar el log abierto

        } catch (error) {
            this.showToast('Error', error.message, 'error');
        }
    }

    /**
     * Muestra una notificación visual elegante (Toast) - FIJADO Y ALINEADO
     */
    /**
     * Muestra una Alerta Modal Centrada de Alta Visibilidad
     */
    showToast(title, message, type = 'info') {
        const id = 'alert-' + Math.random().toString(36).substr(2, 9);
        let bgIconColor, icon, textColor;

        switch (type) {
            case 'success':
                bgIconColor = 'bg-emerald-100 text-emerald-600'; icon = 'check-circle'; textColor = 'text-emerald-700';
                break;
            case 'error':
                bgIconColor = 'bg-rose-100 text-rose-600'; icon = 'alert-triangle'; textColor = 'text-rose-700';
                break;
            case 'warning':
                bgIconColor = 'bg-amber-100 text-amber-600'; icon = 'alert-circle'; textColor = 'text-amber-700';
                break;
            default:
                bgIconColor = 'bg-blue-100 text-blue-600'; icon = 'info'; textColor = 'text-blue-700';
        }

        const DURATION = 3500;

        const alertHtml = `
            <div id="${id}" class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 999999999;">
                <!-- Fondo oscuro bloqueador -->
                <div class="absolute inset-0 bg-slate-900/40 opacity-0 transition-opacity duration-300" id="${id}-backdrop"></div>
                
                <!-- Caja de Alerta -->
                <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-sm p-6 text-center transform scale-90 opacity-0 transition-all duration-300 flex flex-col items-center" id="${id}-box">
                    
                    <div class="w-16 h-16 ${bgIconColor} rounded-full flex items-center justify-center mb-4 shadow-inner">
                        <i data-lucide="${icon}" class="w-8 h-8"></i>
                    </div>
                    
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight mb-2">${title}</h3>
                    <p class="text-sm font-medium text-slate-500 mb-6 leading-relaxed">${message || ''}</p>
                    
                    <button type="button" class="w-full py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors uppercase tracking-wider text-xs" onclick="document.getElementById('${id}').remove()">
                        Entendido
                    </button>
                </div>
            </div>
        `;

        // Añadir directamente al body
        $('body').append(alertHtml);
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Quitar el foco de cualquier input para que el Enter sirva para cerrar la alerta sin re-enviar formularios
        if (document.activeElement) {
            document.activeElement.blur();
        }

        const $modal = $(`#${id}`);
        const $backdrop = $(`#${id}-backdrop`);
        const $box = $(`#${id}-box`);

        // Animar entrada
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                $backdrop.removeClass('opacity-0').addClass('opacity-100');
                $box.removeClass('scale-90 opacity-0').addClass('scale-100 opacity-100');
            });
        });

        // Auto-eliminar
        const timer = setTimeout(() => {
            $backdrop.removeClass('opacity-100').addClass('opacity-0');
            $box.removeClass('scale-100 opacity-100').addClass('scale-90 opacity-0');
            $modal.remove();
        }, DURATION);
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
        
        $modal.addClass('hidden');
        this.currentActiveLecturaId = null;
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
        
        $modal.addClass('hidden');
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
        
        $modal.addClass('hidden');
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
        $modal.addClass('hidden');
    }

    /**
     * Búsqueda OmniSearch (Frontend Logic)
     */
    async performOmniSearch(query, filters) {
        if (!query || query.length < 3) return;

        console.log("=== Iniciando Búsqueda ===");
        console.log("Query:", query, "Filtros:", filters);

        const $btn = $('#search-omni-go');
        const originalText = $btn.text();

        try {
            // Estado de carga en el botón
            $btn.prop('disabled', true).html('<i data-lucide="loader-2" class="w-3 h-3 animate-spin mx-auto"></i>');
            lucide.createIcons();

            // Construir parámetros de la URL
            const params = new URLSearchParams({
                q: query,
                filters: filters.join(',') // ej: edificio,cliente,lt
            });

            const url = `${API_BASE_URL}historial/omnisearch?${params.toString()}`;
            console.log("Realizando Fetch a:", url);

            // Llamada al backend (endpoint pendiente de crear)
            const response = await fetch(url);
            
            // Si el backend no existe lanzará error de red o 404
            if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
            
            const res = await response.json();

            if (!res.status) throw new Error(res.message || 'Error en la búsqueda');

            console.log("Datos recibidos del servidor:", res.data);
            
            // Inyectar los resultados en la tabla principal
            this.isSearchMode = true;
            this.data = res.data || [];
            this.renderAll();

            // Cargar los saldos anteriores para los edificios involucrados
            const uniqueEdificios = [...new Set(this.data.map(item => item.id_edificio))];
            uniqueEdificios.forEach(id_edif => {
                if (id_edif) this.fetchPreviousHistorial(id_edif);
            });
            
            if (window.showToast) window.showToast(`Búsqueda completada. ${this.data.length} coincidencias.`, 'success');

        } catch (error) {
            console.error("Error ejecutando OmniSearch:", error);
            if (window.showToast) window.showToast('Error de conexión o Endpoint no encontrado', 'error');
        } finally {
            // Restaurar botón
            $btn.prop('disabled', false).text('GO');
        }
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

    // ==========================================
    // LÓGICA DE CORREO PERSONALIZADO (MODAL)
    // ==========================================

    openEmailModal(data) {
        // Establecer el ID en el input oculto
        $('#email-dept-id').val(data.id);
        
        // Cargar opciones dinámicas basadas en el HTML de la fila
        const $select = $('#email-recipient-type');
        $select.empty();

        const pEmail = data.correo || '';
        const sEmail = data.correo2 || '';

        const pLabel = pEmail ? pEmail : 'No registrado';
        const sLabel = sEmail ? sEmail : 'No registrado';

        $select.append(`<option value="ambos">Enviar a ambos (${pLabel}, ${sLabel})</option>`);
        $select.append(`<option value="primario">Solo primario (${pLabel})</option>`);
        $select.append(`<option value="secundario">Solo secundario (${sLabel})</option>`);
        $select.append(`<option value="otro">Enviar a otro correo...</option>`);
        
        // Si no tiene ninguno, seleccionar "otro" por defecto
        // Modificado por petición del usuario: seleccionar siempre "ambos" por defecto
        $select.val('ambos');
        
        // Reset inputs
        $('#email-custom-address').val('');
        this.handleEmailTypeChange();

        // Reset radio button to preconfigurado
        $('input[name="email-msg-type"][value="preconfigurado"]').prop('checked', true);
        
        // Poner texto de carga
        $('#email-subject').val('Cargando plantilla...');
        $('#email-message').val('Por favor espere, obteniendo configuración...');
        $('#btn-send-email').prop('disabled', true);

        // Mostrar Modal
        $('#modal-send-email').removeClass('hidden');
        setTimeout(() => {
            $('#modal-email-backdrop').removeClass('opacity-0');
            $('#modal-email-content').removeClass('scale-95 opacity-0');
        }, 10);

        // Obtener plantilla dinámica
        fetch(API_BASE_URL + 'historial/email-template?id_departamento=' + data.id)
            .then(response => response.json())
            .then(res => {
                if (res.status && res.data) {
                    this.defaultEmailSubject = res.data.asunto;
                    this.defaultEmailMessage = res.data.mensaje;
                } else {
                    this.defaultEmailSubject = `Recibo de Gas - Depto ${data.numDepto}`;
                    this.defaultEmailMessage = `Hola ${data.nombre},\nAdjuntamos tu recibo de gas.`;
                }
                
                if ($('input[name="email-msg-type"]:checked').val() === 'preconfigurado') {
                    $('#email-subject').val(this.defaultEmailSubject);
                    $('#email-message').val(this.defaultEmailMessage);
                }
            })
            .catch(() => {
                this.defaultEmailSubject = `Recibo de Gas - Depto ${data.numDepto}`;
                this.defaultEmailMessage = `Hola ${data.nombre},\nAdjuntamos tu recibo de gas.`;
                
                if ($('input[name="email-msg-type"]:checked').val() === 'preconfigurado') {
                    $('#email-subject').val(this.defaultEmailSubject);
                    $('#email-message').val(this.defaultEmailMessage);
                }
            })
            .finally(() => {
                $('#btn-send-email').prop('disabled', false);
            });
    }

    closeEmailModal() {
        $('#modal-email-backdrop').addClass('opacity-0');
        $('#modal-email-content').addClass('scale-95 opacity-0');
        $('#modal-send-email').addClass('hidden');
    }

    handleEmailTypeChange() {
        const val = $('#email-recipient-type').val();
        if (val === 'otro') {
            $('#custom-email-container').removeClass('hidden');
            $('#email-custom-address').focus();
        } else {
            $('#custom-email-container').addClass('hidden');
        }
    }

    handleEmailMsgTypeChange() {
        const tipo = $('input[name="email-msg-type"]:checked').val();
        const instance = window.historial || this;
        
        if (tipo === 'personalizado') {
            $('#email-subject').val('');
            $('#email-message').val('');
            $('#email-subject').focus();
        } else {
            $('#email-subject').val(instance.defaultEmailSubject || 'Recibo de Gas');
            $('#email-message').val(instance.defaultEmailMessage || 'Adjuntamos su recibo de gas.');
        }
    }

    async sendCustomEmail() {
        const id = $('#email-dept-id').val();
        const tipo = $('#email-recipient-type').val();
        const customEmail = $('#email-custom-address').val().trim();
        const subject = $('#email-subject').val().trim();
        const message = $('#email-message').val().trim();
        const adjuntarRecibo = $('#email-attach-pdf').is(':checked') ? '1' : '0';

        if (tipo === 'otro' && !customEmail) {
            if (window.showToast) window.showToast('Debe escribir un correo válido', 'error');
            return;
        }

        // Cerrar el modal inmediatamente para que se puedan ver las notificaciones
        this.closeEmailModal();
        if (window.showToast) window.showToast('Enviando correo, por favor espere...', 'info');

        try {
            const formData = new FormData();
            formData.append('id_departamento', id);
            formData.append('tipo_envio', tipo);
            formData.append('custom_email', customEmail);
            formData.append('subject', subject);
            formData.append('message', message);
            formData.append('adjuntar_recibo', adjuntarRecibo);

            const url = `${API_BASE_URL}historial/enviar-custom-email`;
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const res = await response.json();
            if (!response.ok) throw new Error(res.message || 'Error al enviar correo');

            if (res.status) {
                if (window.showToast) window.showToast(res.message || 'Correo enviado con éxito', 'success');
            } else {
                throw new Error(res.message || 'Error del servidor');
            }
        } catch (error) {
            console.error(error);
            if (window.showToast) window.showToast(error.message, 'error');
        }
    }
}

