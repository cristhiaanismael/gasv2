/**
 * configuracion.js
 * Orquestador principal que enlaza los eventos de la UI y coordina las llamadas AJAX y las actualizaciones visuales.
 */

$(document).ready(function () {
    console.log("[Configuracion] Inicializando módulo...");

    // Inicializar las Clases
    const api = new ConfigAPI();
    const dom = new ConfigDOM();

    let selectedEdificioId = null;

    // --- 1. ENTRADA/SUGERENCIA DE PERIODOS ---
    function suggestNextPeriod(allPeriods) {
        if (!allPeriods || allPeriods.length === 0) return;

        const latest = allPeriods[0];
        if (!latest.fecha_inicio) return;

        try {
            const lastStartParts = latest.fecha_inicio.split('-');
            const lastStart = new Date(lastStartParts[0], lastStartParts[1] - 1, lastStartParts[2]);
            
            const nextTargetMonth = (lastStart.getMonth() + 1) % 12;
            const nextTargetYear = lastStart.getFullYear() + (lastStart.getMonth() === 11 ? 1 : 0);

            const historicalMatch = allPeriods.find(p => {
                if (!p.fecha_inicio) return false;
                const pParts = p.fecha_inicio.split('-');
                return parseInt(pParts[1]) === (nextTargetMonth + 1);
            });

            if (historicalMatch) {
                const hStartParts = historicalMatch.fecha_inicio.split('-');
                const hEndParts = historicalMatch.fecha_fin.split('-');
                const pad = (n) => n.toString().padStart(2, '0');
                const yearDiff = parseInt(hEndParts[0]) - parseInt(hStartParts[0]);
                
                const suggestedStart = `${nextTargetYear}-${pad(hStartParts[1])}-${pad(hStartParts[2])}`;
                const suggestedEnd = `${nextTargetYear + yearDiff}-${pad(hEndParts[1])}-${pad(hEndParts[2])}`;

                dom.$fechaInicio.val(suggestedStart);
                dom.$fechaFin.val(suggestedEnd);
                dom.updatePeriodoPreview();
                showToast("Sugerencia basada en ciclo anterior", "info");
            } else {
                const nextStart = new Date(lastStart);
                nextStart.setMonth(lastStart.getMonth() + 1);
                const nextEnd = new Date(nextStart);
                nextEnd.setMonth(nextStart.getMonth() + 1);
                nextEnd.setDate(nextEnd.getDate() - 1);

                const pad = (n) => n.toString().padStart(2, '0');
                dom.$fechaInicio.val(`${nextStart.getFullYear()}-${pad(nextStart.getMonth()+1)}-${pad(nextStart.getDate())}`);
                dom.$fechaFin.val(`${nextEnd.getFullYear()}-${pad(nextEnd.getMonth()+1)}-${pad(nextEnd.getDate())}`);
                dom.updatePeriodoPreview();
            }
        } catch (e) {
            console.error("Error en sugerencia:", e);
        }
    }

    function fetchPeriodos() {
        console.log("[Configuracion] Cargando periodos...");
        api.getPeriodos()
            .done((res) => {
                console.log("[Configuracion] Periodos devueltos:", res);
                let data = res;
                if (typeof res === 'string') {
                    try { data = JSON.parse(res); } catch(e) { console.error("Error parsing periodos:", e); }
                }
                if (!Array.isArray(data)) return;

                dom.renderPeriodosSelect(data);
                dom.renderPeriodosList(data);

                if (!dom.$fechaInicio.val()) {
                    suggestNextPeriod(data);
                }
            })
            .fail((err) => {
                console.error("[Configuracion] Falló carga de periodos:", err);
                showToast("Error al cargar periodos", "error");
            });
    }

    // Bind Eventos de Periodo
    dom.$fechaInicio.on('change', () => dom.updatePeriodoPreview());
    dom.$fechaFin.on('change', () => dom.updatePeriodoPreview());

    dom.$btnSavePeriodo.on('click', function () {
        const nombre = dom.updatePeriodoPreview();
        if (!nombre) {
            showToast("Selecciona fechas", "error");
            return;
        }

        const data = { 
            nombre_periodo: nombre, 
            fecha_inicio: dom.$fechaInicio.val(), 
            fecha_fin: dom.$fechaFin.val() 
        };

        dom.setLoadingState('#btn-save-periodo', true);

        api.savePeriodo(data)
            .done((res) => {
                showToast("Corte registrado correctamente", "success");
                dom.clearPeriodoForm();
                fetchPeriodos();
            })
            .fail((jqXHR) => {
                const msg = jqXHR.responseJSON?.messages?.error || "Error al guardar corte";
                showToast(msg, "error");
            })
            .always(() => {
                const defaultHtml = '<i data-lucide="save" class="w-4 h-4"></i><span>Registrar Periodo Oficial</span>';
                dom.setLoadingState('#btn-save-periodo', false, defaultHtml);
            });
    });

    // --- 2. MANEJO DE PESTAÑAS PRINCIPALES ---
    $('.tab-link').on('click', function () {
        const tab = $(this).data('tab');
        console.log("[Configuracion] Tab clickeado:", tab);
        
        $('.tab-link').removeClass('text-blue-600 bg-blue-50').addClass('text-slate-600');
        $(this).removeClass('text-slate-600').addClass('text-blue-600 bg-blue-50');
        
        $('.tab-section').addClass('hidden');
        $(`#section-${tab}`).removeClass('hidden');
        
        if (tab === 'edificios') {
            loadEdificiosForConfig();
        }
    });

    // --- 3. GESTIÓN DE TARIFAS DE EDIFICIOS ---
    function loadEdificiosForConfig() {
        console.log("[Configuracion] Cargando catálogo de edificios para configuración...");
        api.getEdificios()
            .done((res) => {
                console.log("[Configuracion] Edificios devueltos:", res);
                let data = res;
                if (typeof res === 'string') {
                    try { data = JSON.parse(res); } catch(e) { console.error("Error parsing edificios:", e); }
                }
                if (!Array.isArray(data)) {
                    console.error("[Configuracion] El formato de edificios no es un array:", data);
                    return;
                }
                dom.renderEdificiosSelect(data);
                if (selectedEdificioId) {
                    dom.$edificioSelect.val(selectedEdificioId);
                }
            })
            .fail((err) => {
                console.error("[Configuracion] Falló carga de edificios:", err);
                showToast("Error al cargar edificios", "error");
            });
    }

    $(document).on('change', '#config-edificio-select', function () {
        const id = $(this).val();
        console.log("[Configuracion] Edificio seleccionado en evento change:", id);
        selectedEdificioId = id;

        const hasSelection = !!id;
        dom.toggleConfigView(hasSelection);

        if (hasSelection) {
            fetchEdificioConfig(id);
        }
    });

    function fetchEdificioConfig(id) {
        console.log("[Configuracion] Cargando configuraciones del edificio:", id);
        
        // Cargar Configuración Vigente
        api.getEdificioConfig(id)
            .done((res) => {
                console.log("[Configuracion] Config vigente devuelta:", res);
                let data = res;
                if (typeof res === 'string') {
                    try { data = JSON.parse(res); } catch(e) { console.error("Error parsing config:", e); }
                }
                dom.renderVigenteConfig(data);
            })
            .fail((err) => {
                console.error("[Configuracion] Falló obtener config vigente:", err);
                showToast("Error al obtener la configuración vigente", "error");
            });

        // Cargar Historial
        api.getEdificioHistory(id)
            .done((res) => {
                console.log("[Configuracion] Historial devuelto:", res);
                let data = res;
                if (typeof res === 'string') {
                    try { data = JSON.parse(res); } catch(e) { console.error("Error parsing history:", e); }
                }
                dom.renderHistoryLists(data);
            })
            .fail((err) => {
                console.error("[Configuracion] Falló obtener historial:", err);
                showToast("Error al obtener historial", "error");
            });
    }

    // Cambiar de pestaña en el Historial
    $('.history-tab-link').on('click', function () {
        const tab = $(this).data('history-tab');
        console.log("[Configuracion] Historial tab clickeado:", tab);
        
        $('.history-tab-link')
            .removeClass('bg-white text-slate-800 shadow-sm border border-slate-200')
            .addClass('text-slate-500 hover:text-slate-800');
        $(this)
            .removeClass('text-slate-500 hover:text-slate-800')
            .addClass('bg-white text-slate-800 shadow-sm border border-slate-200');

        $('.history-pane').addClass('hidden');
        $(`#history-list-${tab}`).removeClass('hidden');
    });

    // Guardar Configuración Comercial
    dom.$btnSaveConfig.on('click', function () {
        if (!selectedEdificioId) {
            showToast("Selecciona un edificio primero", "error");
            return;
        }

        const precioLitro = parseFloat(dom.$inputPrecioLitro.val());
        const factor      = parseFloat(dom.$inputFactor.val());
        const cuotaAdmin  = parseFloat(dom.$inputCuotaAdmin.val());

        console.log("[Configuracion] Intentando guardar tarifas:", { precioLitro, factor, cuotaAdmin });

        if (isNaN(precioLitro) || isNaN(factor) || isNaN(cuotaAdmin)) {
            showToast("Por favor, rellene todos los campos con valores numéricos", "warning");
            return;
        }

        if (precioLitro < 0 || factor <= 0 || cuotaAdmin < 0) {
            showToast("Los valores deben ser válidos y mayores a cero", "warning");
            return;
        }

        dom.setLoadingState('#btn-save-config', true);

        api.saveEdificioConfig(selectedEdificioId, { precioLitro, factor, cuotaAdmin })
            .done((res) => {
                showToast(res.message, "success");
                fetchEdificioConfig(selectedEdificioId);
            })
            .fail((jqXHR) => {
                const msg = jqXHR.responseJSON?.messages?.error || "Error al actualizar configuración";
                showToast(msg, "error");
            })
            .always(() => {
                const defaultHtml = '<i data-lucide="save" class="w-4 h-4"></i><span>Guardar Configuración</span>';
                dom.setLoadingState('#btn-save-config', false, defaultHtml);
            });
    });

    // --- 4. CONFIGURACIÓN COBRANZA ---
    let cobranzaConfigTipo = 'global';
    let cobranzaConfigEntidadId = null;

    $('input[name="cobranza-nivel"]').on('change', function() {
        const val = $(this).val();
        cobranzaConfigTipo = val;
        cobranzaConfigEntidadId = null;

        if (val === 'global') {
            $('#cobranza-dynamic-selectors').addClass('hidden');
            $('#cobranza-sel-edificio-container').addClass('hidden');
            $('#cobranza-sel-depto-container').addClass('hidden');
            fetchCobranzaTemplate();
        } else if (val === 'edificio') {
            $('#cobranza-dynamic-selectors').removeClass('hidden');
            $('#cobranza-sel-edificio-container').removeClass('hidden');
            $('#cobranza-sel-depto-container').addClass('hidden');
            loadCobranzaEdificios();
        } else if (val === 'departamento') {
            $('#cobranza-dynamic-selectors').removeClass('hidden');
            $('#cobranza-sel-edificio-container').removeClass('hidden');
            $('#cobranza-sel-depto-container').removeClass('hidden');
            loadCobranzaEdificios();
        }
    });

    function loadCobranzaEdificios() {
        $.get(api.API_BASE + 'configuracion-cobranza/get-options', { tipo: 'edificios' })
        .done(res => {
            if(res.status) {
                const options = ['<option value="">Seleccione Edificio</option>'].concat(res.data.map(e => `<option value="${e.id_edificio}">Edificio ${e.num_edificio}</option>`));
                $('#cobranza-edificio-select').html(options.join(''));
                $('#cobranza-depto-select').html('<option value="">Seleccione un edificio primero</option>');
                $('#cobranza-asunto').val('');
                $('#cobranza-mensaje').val('');
            }
        });
    }

    $('#cobranza-edificio-select').on('change', function() {
        const val = $(this).val();
        if (!val) {
            $('#cobranza-depto-select').html('<option value="">Seleccione un edificio primero</option>');
            $('#cobranza-asunto').val('');
            $('#cobranza-mensaje').val('');
            cobranzaConfigEntidadId = null;
            return;
        }

        if (cobranzaConfigTipo === 'edificio') {
            cobranzaConfigEntidadId = val;
            fetchCobranzaTemplate();
        } else if (cobranzaConfigTipo === 'departamento') {
            $.get(api.API_BASE + 'configuracion-cobranza/get-options', { tipo: 'departamentos', parent_id: val })
            .done(res => {
                if(res.status) {
                    const options = ['<option value="">Seleccione Departamento</option>'].concat(res.data.map(d => `<option value="${d.id_departamento}">Depto ${d.numero}</option>`));
                    $('#cobranza-depto-select').html(options.join(''));
                    $('#cobranza-asunto').val('');
                    $('#cobranza-mensaje').val('');
                }
            });
        }
    });

    $('#cobranza-depto-select').on('change', function() {
        const val = $(this).val();
        if (!val) {
            $('#cobranza-asunto').val('');
            $('#cobranza-mensaje').val('');
            cobranzaConfigEntidadId = null;
            return;
        }
        cobranzaConfigEntidadId = val;
        fetchCobranzaTemplate();
    });

    function fetchCobranzaTemplate() {
        const tipo = cobranzaConfigTipo;
        const entidad_id = cobranzaConfigEntidadId;

        if (tipo !== 'global' && !entidad_id) return;

        $('#btn-save-cobranza').prop('disabled', true).html('<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i><span>Cargando...</span>');
        lucide.createIcons();

        $.get(api.API_BASE + 'configuracion-cobranza/get-template', { tipo_entidad: tipo, entidad_id: entidad_id })
        .done(res => {
            if (res.status && res.data) {
                $('#cobranza-asunto').val(res.data.asunto);
                $('#cobranza-mensaje').val(res.data.mensaje);
            } else {
                $('#cobranza-asunto').val('');
                $('#cobranza-mensaje').val('');
                showToast("No hay plantilla configurada para este nivel. Puede crear una.", "info");
            }
        })
        .always(() => {
            $('#btn-save-cobranza').prop('disabled', false).html('<i data-lucide="save" class="w-5 h-5"></i><span>Guardar Plantilla</span>');
            lucide.createIcons();
        });
    }

    $('#btn-save-cobranza').on('click', function() {
        const asunto = $('#cobranza-asunto').val().trim();
        const mensaje = $('#cobranza-mensaje').val().trim();

        if (!asunto || !mensaje) {
            showToast("Asunto y Mensaje son requeridos", "error");
            return;
        }

        if (cobranzaConfigTipo !== 'global' && !cobranzaConfigEntidadId) {
            showToast("Seleccione un edificio o departamento", "error");
            return;
        }

        $('#btn-save-cobranza').prop('disabled', true).html('<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i><span>Guardando...</span>');
        lucide.createIcons();

        $.post(api.API_BASE + 'configuracion-cobranza/save-template', {
            tipo_entidad: cobranzaConfigTipo,
            entidad_id: cobranzaConfigEntidadId,
            asunto: asunto,
            mensaje: mensaje
        })
        .done(res => {
            if (res.status) showToast(res.message, "success");
            else showToast(res.message, "error");
        })
        .always(() => {
            $('#btn-save-cobranza').prop('disabled', false).html('<i data-lucide="save" class="w-5 h-5"></i><span>Guardar Plantilla</span>');
            lucide.createIcons();
        });
    });

    // Etiquetas (Click para copiar)
    $('.cobranza-tag').on('click', function() {
        const text = $(this).text();
        navigator.clipboard.writeText(text).then(() => {
            showToast(`Etiqueta ${text} copiada al portapapeles. ¡Pégala en el Asunto o Mensaje!`, "success");
        }).catch(() => {
            showToast("No se pudo copiar al portapapeles", "error");
        });
    });

    // Cargar la global por defecto
    fetchCobranzaTemplate();

    // --- 5. INTEGRACIÓN CON EL SELECTOR GLOBAL (QUICK SELECTOR) ---
    document.addEventListener('building-selected', function (e) {
        const id = e.detail.id;
        console.log("[Configuracion] Selector global seleccionó edificio ID:", id);
        
        // Activar la pestaña de Tarifas y Cuotas
        const $tabLink = $('button[data-tab="edificios"]');
        if ($tabLink.length) {
            $tabLink.click();
        }

        // Seleccionar en el dropdown
        if (id) {
            selectedEdificioId = id;
            // Si el select ya tiene opciones cargadas, lo asignamos y disparamos el cambio
            if (dom.$edificioSelect.find(`option[value="${id}"]`).length) {
                dom.$edificioSelect.val(id).trigger('change');
            } else {
                // Si aún no está cargado (caso muy raro), precargamos y luego seleccionamos
                loadEdificiosForConfig();
                // Usamos un pequeño delay para esperar a que termine el done render
                setTimeout(() => {
                    dom.$edificioSelect.val(id).trigger('change');
                }, 400);
            }
        }
    });

    // Cargar periodos al inicio
    fetchPeriodos();
    
    // PRE-CARGAR edificios al iniciar para que ya estén disponibles sin esperar a clickear la pestaña
    loadEdificiosForConfig();

    lucide.createIcons();
});

function showToast(msg, type = "info") {
    if (window.parent && typeof window.parent.showToast === 'function') {
        window.parent.showToast(msg, type);
    } else {
        console.log(msg);
    }
}
