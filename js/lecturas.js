/**
 * Lectura.js
 * Lógica de negocio y manejo de estado para el módulo de captura OCR.
 */
class Lectura {
    constructor() {
        this.uploadQueue = [];
        this.activeIndex = 0;
        this.isProcessing = false;
        this.edificios = []; // Catálogo de edificios
        this.selectedBuilding = null; // Edificio seleccionado globalmente
        this.currentBuildingConfig = { precioLitro: 0, factor: 1, cuotaAdmin: 0 };
    }

    /**
     * Obtiene el catálogo de edificios desde la API
     */
    async fetchEdificios() {
        const url = API_BASE_URL + "edificios";
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error("Error en API: " + response.statusText);
            this.edificios = await response.json();
            return this.edificios;
        } catch (error) {
            console.error("Error al cargar edificios:", error);
            showToast("Error al sincronizar edificios", "error");
            return [];
        }
    }

    /**
     * Obtiene los departamentos de un edificio específico (Petición)
     */
    async fetchDepartamentos(edificioId) {
        if (!edificioId) return [];
        const url = `${API_BASE_URL}edificio/${edificioId}/departamentos`;
        
        // Al cambiar de edificio, también traemos su configuración (precios/tarifas)
        await this.fetchBuildingConfig(edificioId);

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error("Error en API Deptos");
            return await response.json();
        } catch (error) {
            console.error("Error al cargar departamentos:", error);
            return [];
        }
    }

    /**
     * Obtiene las tarifas (precio litro, factor, cuota admin) del edificio seleccionado
     */
    async fetchBuildingConfig(edificioId) {
        if (!edificioId) return;
        
        // Si ya es el edificio actual, no re-consultar (opcional, pero ayuda)
        if (this.currentBuildingConfig && this.currentBuildingConfig.id_edificio == edificioId) return;

        const url = `${API_BASE_URL}edificio/${edificioId}/config`;
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error("Error en API Config");
            const config = await response.json();
            
            // Verificación doble por asincronía
            this.currentBuildingConfig = config;
            this.calcularConsumo(); 
        } catch (error) {
            console.error("Error al cargar config edificio:", error);
            this.currentBuildingConfig = { precioLitro: 0, factor: 1, cuotaAdmin: 0 };
        }
    }

    /**
     * Envía la lectura al backend para persistirla y generar el movimiento financiero.
     * Soporta envío de archivos mediante FormData.
     * POST api/lectura
     */
    async guardarLectura(formData) {
        const url = API_BASE_URL + "lectura";
        try {
            const response = await fetch(url, {
                method: 'POST',
                // Cuidad: No poner content-type al usar FormData para que el navegador ponga el boundary
                body: formData 
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.messages?.error || 'Error al guardar lectura');
            }
            return data;
        } catch (error) {
            console.error("Error guardando lectura:", error);
            throw error;
        }
    }

    /**
     * Procesa una lista de archivos (desde Dropzone o Input)
     * @param {FileList|Array} files 
     */
    async processFiles(files) {
        showToast(`Procesando ${files.length} imágenes...`, 'info');
        
        for (const file of files) {
            const imgUrl = URL.createObjectURL(file);
            
            // Crear item base
            const item = {
                file: file.name,
                originalFile: file,
                imgUrl: imgUrl,
                status: 'pending',
                ocr: {
                    edificio: this.selectedBuilding || '',
                    depto: '',
                    lectura: '',
                    lecAnterior: 0,
                    deptId: null
                }
            };

            this.uploadQueue.push(item);
            this.renderQueue();

            // Intentar detectar QR
            item.status = 'scanning';
            this.renderQueue();
            this.scanQR(file, item);
        }

        if (this.uploadQueue.length > 0 && !this.isProcessing) {
            this.loadActiveInspection();
        }
    }

    /**
     * Escanea un QR y comprime la imagen para optimizar almacenamiento
     */
    async scanQR(file, item) {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // 1. DIMENSIONES PARA ESCANEO (Optimizado)
            const maxScanDim = 1200;
            let width = img.width;
            let height = img.height;
            if (width > maxScanDim || height > maxScanDim) {
                const ratio = Math.min(maxScanDim / width, maxScanDim / height);
                width *= ratio;
                height *= ratio;
            }

            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(img, 0, 0, width, height);
            
            // 2. DETECCIÓN DE QR
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height);

            if (code) {
                console.log(`QR Detectado en ${file.name}:`, code.data);
                this.parseAndLinkQR(code.data, item);
            } else {
                console.warn("No se detectó QR en", file.name);
                item.status = 'pending';
                this.renderQueue();
            }

            // 3. COMPRESIÓN (Reducción de peso para el servidor)
            const maxUploadDim = 1280; // Estándar para evidencias nítidas y ligeras
            let finalWidth = img.width;
            let finalHeight = img.height;
            
            if (finalWidth > maxUploadDim || finalHeight > maxUploadDim) {
                const ratio = Math.min(maxUploadDim / finalWidth, maxUploadDim / finalHeight);
                finalWidth *= ratio;
                finalHeight *= ratio;
            }

            canvas.width = finalWidth;
            canvas.height = finalHeight;
            ctx.drawImage(img, 0, 0, finalWidth, finalHeight);

            // Exportar a Blob comprimido (JPEG 0.7)
            canvas.toBlob((blob) => {
                if (blob) {
                    const oldUrl = item.imgUrl;
                    item.compressedBlob = blob;
                    item.imgUrl = URL.createObjectURL(blob);
                    
                    // Actualizar el visor si es la imagen activa
                    if (this.uploadQueue[this.activeIndex] === item) {
                        $('#inspector-image').attr('src', item.imgUrl);
                    }
                    this.renderQueue();
                    
                    // Liberar URL vieja (opcional, cuidado con miniaturas)
                    // URL.revokeObjectURL(oldUrl); 
                    console.log(`Imagen comprimida: ${(file.size / 1024).toFixed(1)}KB -> ${(blob.size / 1024).toFixed(1)}KB`);
                }
            }, 'image/jpeg', 0.7);

            URL.revokeObjectURL(img.src);
        };

        img.onerror = () => {
            console.error("Error cargando imagen:", file.name);
            item.status = 'pending';
            this.renderQueue();
            URL.revokeObjectURL(img.src);
        };

        img.src = URL.createObjectURL(file);
    }

    /**
     * Lógica segregada de parseo de QR extraído
     */
    parseAndLinkQR(qrData, item) {
        try {
            const cleanData = qrData.includes('?') ? qrData.split('?')[1] : qrData;
            const params = new URLSearchParams(cleanData);
            
            let deptId = params.get('id_departamento');
            let deptoName = params.get('num_departamento');
            let edificioName = params.get('num_edi');

            if (!deptId) {
                const matchId = qrData.match(/id_departamento=([^&]+)/);
                if (matchId) deptId = matchId[1];
                const matchNum = qrData.match(/num_departamento=([^&]+)/);
                if (matchNum) deptoName = matchNum[1];
                const matchEdi = qrData.match(/num_edi=([^&]+)/);
                if (matchEdi) edificioName = matchEdi[1];
            }

            if (deptId) {
                item.ocr.qrDetected = {
                    edificio: edificioName || 'Edificio Detectado',
                    depto: deptoName || '?'
                };
                this.linkData(deptId, item);
            } else {
                item.status = 'pending';
                this.renderQueue();
            }
        } catch (e) {
            console.error("Error parsing QR:", e);
            item.status = 'pending';
            this.renderQueue();
        }
    }

    /**
     * Vincula el ID del departamento con los datos reales de la DB
     */
    async linkData(deptId, item) {
        const url = API_BASE_URL + "departamento/" + deptId;
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error("Dept no encontrado");
            const data = await response.json();

            // Actualizar datos del item
            item.ocr.depto = data.num_departamento;
            item.ocr.edificio = data.num_edificio; 
            item.ocr.deptId = deptId;
            // Guardar contexto técnico
            item.ocr.lecAnterior = data.lectura_actual ? data.lectura_actual.lectura_ini : (data.ultima_lectura || 0);
            item.ocr.lecturaActual = data.lectura_actual || null; // <-- Guardar lectura existente si existe
            item.ocr.saldoAnterior = data.saldo_actual || 0;
            item.ocr.cuotaAdmin = data.cuota_admin || 0;
            item.status = 'pending'; // Liberar el loader

            // Detectar Conflicto de Edificio
            if (this.selectedBuilding && 
                this.selectedBuilding.toLowerCase() !== data.num_edificio.toLowerCase()) {
                item.ocr.hasConflict = true;
                showToast(`Conflicto: La foto es de ${data.num_edificio}, pero seleccionaste ${this.selectedBuilding}`, "error");
            } else {
                item.ocr.hasConflict = false;
            }

            this.renderQueue();
            if (this.uploadQueue[this.activeIndex] === item) {
                this.loadActiveInspection();
                // Asegurar que el depto se vea incluso si loadActiveInspection ya corrió
                $('#depto-number').text(item.ocr.depto || '---');
            }
            
            if (!item.ocr.hasConflict) {
                showToast(`QR Vinculado: ${data.num_departamento || 'Depto'}`);
            }
        } catch (error) {
            console.error("Error vinculando datos:", error);
            item.status = 'pending'; // Evitar que se quede en loading
            this.renderQueue();
        }
    }

    /**
     * Inicializa la cola con datos reales o resultados de carga
     * @param {Array} items 
     */
    setQueue(items) {
        this.uploadQueue = items;
        this.activeIndex = 0;
        this.renderQueue();
        this.loadActiveInspection();
    }

    /**
     * Dibuja los elementos en la barra lateral de imágenes
     */
    renderQueue() {
        const completados = this.uploadQueue.filter(item => item.status === 'completed').length;
        const total = this.uploadQueue.length;

        $('#queue-counter').text(`${completados}/${total}`);
        $('#queue-progress').css('width', `${(completados / total) * 100}%`);

        const html = this.uploadQueue.map((item, index) => {
            const isActive = index === this.activeIndex;
            const isCompleted = item.status === 'completed';
            const isScanning = item.status === 'scanning';
            const hasConflict = item.ocr.hasConflict;
            
            let statusHtml = '';
            if (isCompleted) {
                statusHtml = `<div class="absolute inset-0 bg-green-500/20 flex items-center justify-center rounded-lg"><i data-lucide="check-circle-2" class="w-6 h-6 text-green-600 bg-white rounded-full"></i></div>`;
            } else if (isScanning) {
                statusHtml = `<div class="absolute inset-0 bg-blue-600/40 flex items-center justify-center rounded-lg"><i data-lucide="loader-2" class="w-6 h-6 text-white animate-spin"></i></div>`;
            } else if (hasConflict) {
                statusHtml = `<div class="absolute top-1 right-1 z-10"><i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 fill-red-100"></i></div>`;
            }
            
            let borderClass = isActive ? 'border-blue-500 shadow-md bg-blue-50' : (hasConflict ? 'border-red-300 bg-red-200/60' : 'border-transparent hover:border-gray-300');

            return `
                <button class="queue-item w-full flex items-center p-2 rounded-xl border-2 transition-all ${borderClass} relative" data-index="${index}">
                    <div class="w-14 h-14 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0 relative">
                        <img src="${item.imgUrl}" class="w-full h-full object-cover opacity-80" />
                        ${statusHtml}
                    </div>
                    <div class="ml-3 text-left flex-1 min-w-0">
                        <p class="text-xs font-bold text-gray-900 truncate">${item.ocr.edificio || 'Desconocido'}</p>
                        <p class="text-[10px] font-medium text-gray-500 uppercase">Dpto. ${item.ocr.depto || '?'}</p>
                    </div>
                </button>`;
        }).join('');

        $('#image-queue-container').html(html);
        lucide.createIcons();
    }

    /**
     * Carga el elemento seleccionado en el visor principal
     */
    async loadActiveInspection() {
        const item = this.uploadQueue[this.activeIndex];
        
        if (!item || this.allCompleted()) {
            $('#active-inspection').addClass('hidden');
            $('#empty-inspection').removeClass('hidden');
            return;
        }

        $('#active-inspection').removeClass('hidden');
        $('#empty-inspection').addClass('hidden');
        
        // Indicador sutil de QR
        if (item.ocr.qrDetected) {
            const conflictInfo = item.ocr.hasConflict ? 
                `<span class="ml-2 text-red-700 bg-red-100 px-2 py-0.5 rounded flex items-center"><i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i> ¡Conflicto de Edificio!</span>` : '';
                
            $('#qr-detected-badge')
                .html(`<i data-lucide="qr-code" class="w-3 h-3 mr-1"></i> Detectado: ${item.ocr.qrDetected.edificio} - ${item.ocr.qrDetected.depto} ${conflictInfo}`)
                .removeClass('hidden');
        } else {
            $('#qr-detected-badge').addClass('hidden');
        }

        // Fondo de conflicto en el formulario
        if (item.ocr.hasConflict) {
            $('#inspector-form').addClass('bg-red-200/40 border-l-4 border-red-500');
        } else {
            $('#inspector-form').removeClass('bg-red-200/40 border-l-4 border-red-500');
        }

        // Actualizar UI - Ahora sincronizado con Selectores Globales
        $('#inspector-image').attr('src', item.imgUrl);
        $('#inspector-filename').text(item.file);
        
        if (window.Selectors) {
            const { tsEdificio, tsDepto } = window.Selectors;
            tsEdificio.clear(true); // Silent
            tsDepto.clear(true);

            if (item.ocr.edificio) {
                // Encontrar ID por nombre
                const b = this.edificios.find(x => x.num_edificio === item.ocr.edificio);
                if (b) {
                    tsEdificio.setValue(b.id_edificio, true);
                    const deptos = await this.fetchDepartamentos(b.id_edificio);
                    tsDepto.clearOptions();
                    tsDepto.addOptions(deptos);
                    if (item.ocr.depto) {
                        const d = deptos.find(x => x.num_departamento === item.ocr.depto);
                        if (d) tsDepto.setValue(d.id_departamento, true);
                    }
                }
            }
        }
        $('#input-lectura').val(item.ocr.lectura || '');
        $('#lectura-anterior').text(item.ocr.lecAnterior);

        this.calcularConsumo();
        
        // UI State: Detectar si ya existe lectura (Modo Edición)
        const badge = $('#badge-exists');
        const saveBtn = $('#save-btn');
        const inputLectura = $('#input-lectura');
        const headerReg = $('#header-registration');

        // IMPORTANTE: Imprimir el número de departamento en el encabezado
        $('#depto-number').text(item.ocr.depto || '---');

        if (item.ocr.lecturaActual) {
            badge.show();
            headerReg.addClass('mode-edition-warning');
            saveBtn.html('<i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i> Actualizar y Siguiente');
            saveBtn.removeClass('bg-[#0F172A]').addClass('bg-indigo-600 hover:bg-indigo-700');
            
            // Pre-cargar la lectura si ya fue registrada (y no hemos editado aún en esta sesión)
            if (!item.ocr.lecturaFinal) {
                inputLectura.val(item.ocr.lecturaActual.lectura_fin);
            }
        } else {
            badge.hide();
            headerReg.removeClass('mode-edition-warning');
            saveBtn.html('<i data-lucide="check-circle-2" class="w-4 h-4 mr-2"></i> Confirmar y Siguiente');
            saveBtn.addClass('bg-[#0F172A]').removeClass('bg-indigo-600 hover:bg-indigo-700');
        }

        // Inicializar iconos (para el tooltip de saldo)
        lucide.createIcons();

        // Focus para entrada rápida
        setTimeout(() => $('#input-lectura').focus().select(), 100);
    }

    /**
     * Realiza el cálculo de consumo y financiero en tiempo real
     */
    calcularConsumo() {
        const item = this.uploadQueue[this.activeIndex];
        if (!item) return;

        const actual = parseFloat($('#input-lectura').val());
        const anterior = item.ocr.lecAnterior || 0;
        const saldoAnterior = item.ocr.saldoAnterior || 0;
        const config = this.currentBuildingConfig || { precioLitro: 0, factor: 1, cuotaAdmin: 0 };

        if (!isNaN(actual) && actual >= anterior) {
            const m3 = actual - anterior;
            const lt = m3 * config.factor;
            const montoGas = lt * config.precioLitro;
            const totalCaptura = montoGas + config.cuotaAdmin;
            const totalFinal = totalCaptura + saldoAnterior;

            // M3
            $('#consumo-calculado')
                .text(`${m3.toFixed(2)} m³ cons.`)
                .removeClass('text-red-600 bg-red-50 border-red-100')
                .addClass('text-green-600 bg-green-50 border-green-100');

            // Totales
            const total = montoGas + config.cuotaAdmin + saldoAnterior;

            // Actualizar UI - Dashboard principal
            $('#prev-litros').text(lt.toFixed(2) + ' Lt');
            $('#prev-monto').text('$' + montoGas.toFixed(2));
            $('#prev-total').text('$' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            // Actualizar UI - Tooltip Premium
            $('#tt-cuota').text('$' + config.cuotaAdmin.toFixed(2));
            
            // Separar saldo en Adeudo y Saldo a Favor
            if (saldoAnterior > 0) {
                $('#tt-adeudo').text('$' + saldoAnterior.toFixed(2));
                $('#tt-favor').text('$0.00');
            } else if (saldoAnterior < 0) {
                $('#tt-adeudo').text('$0.00');
                $('#tt-favor').text('$' + Math.abs(saldoAnterior).toFixed(2));
            } else {
                $('#tt-adeudo').text('$0.00');
                $('#tt-favor').text('$0.00');
            }

        } else {
            $('#consumo-calculado')
                .text(isNaN(actual) ? 'Ingresa lectura' : 'Inválido')
                .removeClass('text-green-600 bg-green-50 border-green-100')
                .addClass('text-red-600 bg-red-50 border-red-100');
            
            // Reset dash
            $('#prev-litros').text('0.00 Lt');
            $('#prev-monto').text('$0.00');
            $('#prev-cuota').text(`$${config.cuotaAdmin.toFixed(2)}`);
            $('#prev-saldo').text(`$${saldoAnterior.toFixed(2)}`);
            $('#prev-total').text('$0.00');
        }
    }

    /**
     * Guarda la lectura actual en el servidor y avanza a la siguiente disponible.
     * Flujo: Validar → POST api/lectura → Marcar como completed → Avanzar
     */
    async saveAndNext() {
        const item = this.uploadQueue[this.activeIndex];
        if (!item) return;

        // ── Validaciones Frontend ──
        const lectura_fin = parseFloat($('#input-lectura').val());
        if (isNaN(lectura_fin) || lectura_fin < item.ocr.lecAnterior) {
            showToast('Lectura inválida: debe ser mayor o igual a la anterior', 'error');
            return;
        }

        if (!item.ocr.deptId) {
            showToast('Selecciona un departamento antes de guardar', 'error');
            return;
        }

        // ── Estado de carga en el botón ──
        const $btn = $('#btn-guardar');
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html(
            '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i><span>Guardando...</span>'
        );
        lucide.createIcons();

        // ── Construir payload en FormData ──
        const formData = new FormData();
        formData.append('id_departamento', item.ocr.deptId);
        formData.append('lectura_ini',     item.ocr.lecAnterior);
        formData.append('lectura_fin',     lectura_fin);
        formData.append('fecha_registro',  new Date().toISOString().split('T')[0]);

        // Agregar archivo (blobs comprimidos o archivo original si falló comprimir)
        if (item.compressedBlob) {
            formData.append('foto', item.compressedBlob, item.file);
        } else if (item.originalFile) {
            formData.append('foto', item.originalFile);
        }

        try {
            const result = await this.guardarLectura(formData);

            // ── Éxito: marcar como completado ──
            item.status = 'completed';
            item.ocr.lecturaFinal = lectura_fin;

            // Mostrar resumen del servidor (ahora incluye m3 gracias al refactor)
            showToast(
                `✓ ${result.num_edificio || item.ocr.edificio} - ${result.num_departamento || item.ocr.depto}: ${result.consumo_m3 || 0} m³ | $${(result.total_cargo || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}`,
                'success'
            );

            // Animación y salto a la siguiente
            $('#active-inspection').animate({ opacity: 0.5 }, 150, () => {
                $('#active-inspection').css({ opacity: 1 });

                const nextPending = this.uploadQueue.findIndex(i => i.status === 'pending');
                if (nextPending !== -1) {
                    this.activeIndex = nextPending;
                }

                this.renderQueue();
                this.loadActiveInspection();
            });

        } catch (error) {
            showToast(`Error: ${error.message}`, 'error');
        } finally {
            $btn.prop('disabled', false).html(originalHtml);
            lucide.createIcons();
        }
    }

    /**
     * Elimina la imagen actual de la cola y avanza a la siguiente
     */
    descartar() {
        if (!confirm('¿Seguro que deseas descartar esta evidencia fotográfica?')) return;

        const indexToRemove = this.activeIndex;
        
        // Revocar URL para liberar memoria
        URL.revokeObjectURL(this.uploadQueue[indexToRemove].imgUrl);
        
        // Remover de la cola
        this.uploadQueue.splice(indexToRemove, 1);
        
        // Ajustar índice activo
        if (this.activeIndex >= this.uploadQueue.length) {
            this.activeIndex = Math.max(0, this.uploadQueue.length - 1);
        }

        this.renderQueue();
        this.loadActiveInspection();
        
        showToast('Evidencia descartada', 'info');
    }

    /**
     * Cambia el índice activo manualmente
     */
    selectIndex(index) {
        if (this.uploadQueue[index]) {
            this.activeIndex = index;
            this.renderQueue();
            this.loadActiveInspection();
        }
    }

    allCompleted() {
        return this.uploadQueue.length > 0 && this.uploadQueue.every(i => i.status === 'completed');
    }

    /**
     * Reacciona a la selección global de un edificio
     */
    handleBuildingSelection(id, name) {
        console.log(`GasData Capture: Edificio pre-seleccionado -> ${name}`);
        this.selectedBuilding = name;
        
        // Si hay una inspección activa que es desconocida, le ponemos este edificio
        const item = this.uploadQueue[this.activeIndex];
        if (item && item.status === 'pending' && !item.ocr.edificio) {
            $('#input-edificio').val(name);
            item.ocr.edificio = name;
        }
    }
}
