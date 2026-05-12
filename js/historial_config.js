/**
 * historial_config.js
 * Configuración y constantes del módulo de historial.
 * Se carga antes de historial.js e historialini.js.
 */

window.HISTORIAL_CONFIG = window.HISTORIAL_CONFIG || {
    // Animación de transiciones
    animationDuration: 300,

    // Formato de moneda
    currency: {
        locale: 'es-MX',
        prefix: '$'
    },

    // Selectores del panel de detalles
    PANEL: {
        id: '#unit-detail-panel',
        backdrop: '#panel-backdrop',
        unitName: '#panel-unit-name',
        periodLabel: '#panel-period-label',
        inputLecAnt: '#panel-input-lec-ant',
        inputLecAct: '#panel-input-lec-act',
        inputAdd: '#panel-input-add',
        inputAjuste: '#panel-input-ajuste',
        inputNota: '#panel-input-nota',
        inputPago: '#panel-input-pago',
        inputTotal: '#panel-input-total',
        inputMovTipo: '#panel-select-mov-tipo',
        btnRecalculate: '#btn-recalculate-total',
        lblConsumom3: '#lbl-consumo-m3',
        lblConsumolt: '#lbl-consumo-lt',
        lblMontoGas: '#lbl-monto-gas',
        lblSaldoFavor: '#lbl-saldo-favor',
        lblAdeudos: '#lbl-adeudos',
        saldoActual: '#panel-saldo-actual',
        historyBody: '#panel-history-body',
        btnUpdate: '#btn-update-reading',
        btnPayment: '#btn-submit-payment'
    },
    MODAL_HISTORY: {
        id: '#modal-history-expanded',
        backdrop: '#modal-history-backdrop',
        content: '#modal-history-content',
        body: '#modal-history-body',
        deptoLabel: '#modal-history-depto',
        btnExpand: '#btn-expand-history',
        btnClose: '#btn-close-history-modal',
        btnCloseFooter: '#btn-close-history-modal-footer'
    },
    
    // Contenido del Manual de Ayuda Directa
    HELP_CONTENT: {
        'master-switch': {
            title: 'Notificaciones Automáticas',
            body: `
                <div class="space-y-4">
                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start space-x-3">
                        <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="zap" class="w-5 h-5 text-white"></i>
                        </div>
                        <p class="text-xs font-medium text-amber-800 leading-relaxed">
                            Cuando esta opción está <b>ACTIVA</b>, el sistema enviará un correo electrónico automáticamente al cliente en cuanto hagas clic en el botón "GENERAR PDF".
                        </p>
                    </div>
                    <ul class="space-y-3">
                        <li class="flex items-center space-x-3 text-slate-600">
                            <div class="w-1.5 h-1.5 bg-blue-600 rounded-full"></div>
                            <span class="text-xs font-bold font-sans">Ahorras tiempo: Un solo clic captura y notifica.</span>
                        </li>
                        <li class="flex items-center space-x-3 text-slate-600">
                            <div class="w-1.5 h-1.5 bg-blue-600 rounded-full"></div>
                            <span class="text-xs font-bold font-sans">Transparencia: El cliente recibe su recibo al instante.</span>
                        </li>
                        <li class="flex items-center space-x-3 text-slate-600">
                            <div class="w-1.5 h-1.5 bg-blue-600 rounded-full"></div>
                            <span class="text-xs font-bold font-sans text-rose-600">Si lo desactivas, los recibos se guardarán pero NO se enviarán.</span>
                        </li>
                    </ul>
                </div>
            `
        },
        'zip-download': {
            title: 'Descarga Masiva (Lote)',
            body: `
                <div class="space-y-4">
                    <p class="text-xs font-medium text-slate-600 leading-relaxed">
                        Esta herramienta permite descargar todos los recibos del edificio seleccionado en un solo archivo comprimido (ZIP).
                    </p>
                    <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                        <h4 class="text-[10px] font-black text-blue-800 uppercase mb-2">Pasos recomendados:</h4>
                        <ol class="space-y-2 list-decimal list-inside text-xs font-bold text-blue-700">
                            <li>Captura todas las lecturas del edificio.</li>
                            <li>Asegúrate de que todos tengan el ícono de PDF generado.</li>
                            <li>Ejecuta "Descargar Todo" en este panel.</li>
                        </ol>
                    </div>
                </div>
            `
        }
    }
};

console.log("Historial Config Loaded");
