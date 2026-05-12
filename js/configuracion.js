/**
 * configuracion.js
 * Lógica para la gestión de periodos y carga masiva de departamentos.
 */

$(document).ready(function () {
    const API_BASE = "apis_marvi/public/api/";

    // 1. Manejo de Pestañas
    $('.tab-link').on('click', function () {
        const tab = $(this).data('tab');
        $('.tab-link').removeClass('text-blue-600 bg-blue-50').addClass('text-slate-600');
        $(this).removeClass('text-slate-600').addClass('text-blue-600 bg-blue-50');
        $('.tab-section').addClass('hidden');
        $(`#section-${tab}`).removeClass('hidden');
        if(tab === 'departamentos') loadEdificiosForSelect();
    });

    // 2. Lógica de Periodos
    const $fechaInicio = $('#p-fecha-inicio');
    const $fechaFin = $('#p-fecha-fin');
    const $preview = $('#periodo-preview');

    function updatePeriodoPreview() {
        const start = $fechaInicio.val();
        const end = $fechaFin.val();
        if (start && end) {
            const fmtStart = start.split('-').reverse().join('-');
            const fmtEnd = end.split('-').reverse().join('-');
            const finalString = `${fmtStart} ${fmtEnd}`.trim();
            $preview.text(finalString).addClass('text-blue-900').removeClass('text-slate-400');
            return finalString;
        } else {
            $preview.text('-- - --').addClass('text-slate-400');
            return null;
        }
    }

    $fechaInicio.on('change', updatePeriodoPreview);
    $fechaFin.on('change', updatePeriodoPreview);

    $('#btn-save-periodo').on('click', function () {
        const nombre = updatePeriodoPreview();
        if (!nombre) {
            showToast("Selecciona fechas", "error");
            return;
        }
        const data = { nombre_periodo: nombre, fecha_inicio: $fechaInicio.val(), fecha_fin: $fechaFin.val() };
        $(this).prop('disabled', true).html('<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>Guardando...</span>');
        lucide.createIcons();

        $.ajax({
            url: API_BASE + 'config/periodo',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: (res) => {
                showToast("Corte registrado", "success");
                $fechaInicio.val(''); $fechaFin.val('');
                updatePeriodoPreview();
                fetchPeriodos();
            },
            error: (jqXHR) => {
                const msg = jqXHR.responseJSON?.messages?.error || "Error al guardar corte";
                showToast(msg, "error");
            },
            complete: () => {
                $(this).prop('disabled', false).html('<i data-lucide="save" class="w-4 h-4"></i><span>Registrar Periodo Oficial</span>');
                lucide.createIcons();
            }
        });
    });

    /**
     * SUGERENCIA BASADA EN EL AÑO ANTERIOR
     * Toma el último periodo, detecta cuál sigue, y busca ese mismo mes el año pasado.
     */
    function suggestNextPeriod(allPeriods) {
        if (!allPeriods || allPeriods.length === 0) return;

        // 1. Encontrar el último periodo registrado (el de id más alto o fecha más reciente)
        const latest = allPeriods[0];
        if (!latest.fecha_inicio) return;

        try {
            const lastStartParts = latest.fecha_inicio.split('-');
            const lastStart = new Date(lastStartParts[0], lastStartParts[1] - 1, lastStartParts[2]);
            
            // 2. Determinar el mes y año que sigue
            const nextTargetMonth = (lastStart.getMonth() + 1) % 12;
            const nextTargetYear = lastStart.getFullYear() + (lastStart.getMonth() === 11 ? 1 : 0);

            // 3. Buscar en el historial el mismo mes pero del año pasado (o cualquier año previo)
            // Queremos un periodo que haya empezado en nextTargetMonth
            const historicalMatch = allPeriods.find(p => {
                if (!p.fecha_inicio) return false;
                const pParts = p.fecha_inicio.split('-');
                return parseInt(pParts[1]) === (nextTargetMonth + 1); // Meses en JS son 0-11, en DB son 1-12
            });

            if (historicalMatch) {
                // 4. Clonar los días pero usar el año proyectado
                const hStartParts = historicalMatch.fecha_inicio.split('-');
                const hEndParts = historicalMatch.fecha_fin.split('-');

                const pad = (n) => n.toString().padStart(2, '0');
                
                // Calculamos si el periodo termina en el mismo año o en el siguiente
                const yearDiff = parseInt(hEndParts[0]) - parseInt(hStartParts[0]);
                
                const suggestedStart = `${nextTargetYear}-${pad(hStartParts[1])}-${pad(hStartParts[2])}`;
                const suggestedEnd = `${nextTargetYear + yearDiff}-${pad(hEndParts[1])}-${pad(hEndParts[2])}`;

                $fechaInicio.val(suggestedStart);
                $fechaFin.val(suggestedEnd);
                updatePeriodoPreview();
                showToast("Sugerencia basada en ciclo anterior", "info");
            } else {
                // Fallback: Sugerencia mensual simple si no hay historial para ese mes
                const nextStart = new Date(lastStart);
                nextStart.setMonth(lastStart.getMonth() + 1);
                const nextEnd = new Date(nextStart);
                nextEnd.setMonth(nextStart.getMonth() + 1);
                nextEnd.setDate(nextEnd.getDate() - 1);

                const pad = (n) => n.toString().padStart(2, '0');
                $fechaInicio.val(`${nextStart.getFullYear()}-${pad(nextStart.getMonth()+1)}-${pad(nextStart.getDate())}`);
                $fechaFin.val(`${nextEnd.getFullYear()}-${pad(nextEnd.getMonth()+1)}-${pad(nextEnd.getDate())}`);
                updatePeriodoPreview();
            }
        } catch (e) {
            console.error("Error en sugerencia:", e);
        }
    }

    function fetchPeriodos() {
        $.get(API_BASE + 'config/periodos', (res) => {
            if (!Array.isArray(res)) return;

            // 1. Selector Global
            const options = res.map(p => `<option value="${p.id_corte}">${p.periodo}</option>`).join('');
            $('#periodo-global-select').html('<option value="">-- Ver todos los periodos --</option>' + options);

            // 2. Lista
            const html = res.map(p => `
                <div class="bg-white p-4 rounded-2xl flex justify-between items-center border border-slate-100 hover:border-blue-200 transition-all shadow-sm">
                    <div>
                        <p class="text-sm font-black text-slate-800">${p.periodo}</p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase">Rango: ${p.fecha_inicio || '--'} a ${p.fecha_fin || '--'}</p>
                    </div>
                </div>
            `).join('');
            $('#periodos-list').html(html || '<p class="text-center py-10 text-slate-400 text-xs font-bold italic">No hay registros</p>');

            // 3. Sugerencia proactiva
            if (!$fechaInicio.val()) {
                suggestNextPeriod(res);
            }
        });
    }

    function loadEdificiosForSelect() {
        $.get(API_BASE + 'edificios', (res) => {
            const html = res.map(e => `<option value="${e.id_edificio}">${e.num_edificio}</option>`).join('');
            $('#bulk-edificio-select').html(html);
        });
    }

    $('#btn-save-bulk').on('click', function () {
        const id_edi = $('#bulk-edificio-select').val();
        const text = $('#bulk-depto-list').val();
        const deptos = text.split('\n').map(d => d.trim()).filter(d => d.length > 0);
        if (!id_edi || deptos.length === 0) {
            showToast("Completa los datos", "warning"); return;
        }
        $(this).prop('disabled', true).text('Procesando...');
        $.ajax({
            url: API_BASE + 'config/departamentos/bulk',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id_edificio: id_edi, departamentos: deptos }),
            success: (res) => { showToast(res.message, "success"); $('#bulk-depto-list').val(''); },
            error: () => showToast("Error", "error"),
            complete: () => $(this).prop('disabled', false).text('Procesar y Dar de Alta')
        });
    });

    fetchPeriodos();
    lucide.createIcons();
});

function showToast(msg, type = "info") {
    if (window.parent && typeof window.parent.showToast === 'function') {
        window.parent.showToast(msg, type);
    } else {
        console.log(msg);
    }
}
