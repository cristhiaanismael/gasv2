class EdificiosUI {
    constructor() {
        this.overlay = document.getElementById('modal-overlay');
        this.modals = ['modal-edificio', 'modal-departamento', 'modal-cliente', 'modal-migrar', 'modal-qr', 'modal-bulk-qr'];
        this.currentQR = null;
        this.currentBulkBuildingId = null;
        this.currentBulkBuilding = "";
        this.currentIndividualDepto = null;
    }

    // --- Tabs ---
    switchTab(tab) {
        const btnEdificios = document.querySelector('button[data-tab="edificios"]');
        const btnDeptos = document.querySelector('button[data-tab="departamentos"]');
        const viewEdificios = document.getElementById('tab-edificios');
        const viewDeptos = document.getElementById('tab-departamentos');

        const activeClass = 'pb-3 tab-active focus:outline-none transition-colors';
        const inactiveClass = 'pb-3 tab-inactive focus:outline-none transition-colors';

        if (tab === 'edificios') {
            btnEdificios.className = activeClass;
            btnDeptos.className = inactiveClass;
            viewEdificios.classList.remove('hidden');
            viewDeptos.classList.add('hidden');
        } else {
            btnDeptos.className = activeClass;
            btnEdificios.className = inactiveClass;
            viewDeptos.classList.remove('hidden');
            viewEdificios.classList.add('hidden');
        }
    }

    // --- Modales ---
    openModal(id) {
        this.overlay.classList.remove('hidden');
        setTimeout(() => {
            this.overlay.classList.add('opacity-100');
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('scale-95', 'opacity-0');
                modal.classList.add('scale-100', 'opacity-100');
            }, 10);
        }, 10);
    }

    closeModals() {
        this.modals.forEach(id => {
            const modal = document.getElementById(id);
            if (!modal.classList.contains('hidden')) {
                modal.classList.remove('scale-100', 'opacity-100');
                modal.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }
        });
        this.overlay.classList.remove('opacity-100');
        setTimeout(() => {
            this.overlay.classList.add('hidden');
        }, 300);
    }

    // --- Renderizado de Tablas ---
    renderEdificios(data) {
        const tbody = document.getElementById('sortable-edificios');
        tbody.innerHTML = '';
        
        data.forEach(ed => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-id', ed.id);
            tr.className = 'hover:bg-gray-50/50 transition-colors group cursor-move';
            tr.innerHTML = `
                <td class="p-4 text-gray-400 group-hover:text-blue-500 transition-colors w-10"><i data-lucide="grip-vertical" class="w-4 h-4"></i></td>
                <td class="p-4 text-gray-500">${ed.id}</td>
                <td class="p-4 font-bold text-slate-800">${ed.nombre}</td>
                <td class="p-4">${ed.direccion}</td>
                <td class="p-4"><span class="bg-blue-50 text-blue-700 px-2 py-1 rounded-md text-xs font-bold">${ed.cuenta}</span></td>
                <td class="p-4 col-orden">${ed.orden}</td>
                <td class="p-4 text-center space-x-2 flex justify-center items-center">
                    <button class="btn-bulk-qr text-emerald-600 bg-emerald-50 px-2 py-1 rounded text-xs font-bold hover:bg-emerald-100 transition-colors inline-flex items-center" data-id="${ed.id}" data-nombre="${ed.nombre}" title="Descargar QRs del Edificio"><i data-lucide="folder-down" class="w-3 h-3 mr-1"></i> QRs</button>
                    <button class="btn-edit-edificio text-gray-400 hover:text-blue-600 transition-colors" data-id="${ed.id}"><i data-lucide="edit" class="w-4 h-4"></i></button>
                    <button class="btn-delete-edificio text-gray-400 hover:text-rose-600 transition-colors" data-id="${ed.id}"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        lucide.createIcons();
    }

    renderDepartamentos(data) {
        const tbody = document.getElementById('sortable-deptos');
        tbody.innerHTML = '';

        data.forEach(depto => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-id', depto.id);
            tr.className = 'hover:bg-gray-50/50 transition-colors group';
            
            let clienteHtml = '';
            let accionClienteHtml = '';

            if (depto.cliente) {
                clienteHtml = `
                    <div class="font-bold text-slate-800">${depto.cliente}</div>
                    <div class="text-xs text-gray-500">Ref: ${depto.ref || '--'}</div>
                `;
                accionClienteHtml = `<button class="btn-edit-cliente text-indigo-600 bg-indigo-50 px-2 py-1 rounded text-xs font-bold hover:bg-indigo-100 transition-colors inline-flex items-center" data-id="${depto.id}"><i data-lucide="user-cog" class="w-3 h-3 mr-1"></i> Editar</button>`;
            } else {
                clienteHtml = `<span class="text-gray-400 italic">Sin asignar</span>`;
                accionClienteHtml = `<button class="btn-assign-cliente text-blue-600 bg-blue-50 px-2 py-1 rounded text-xs font-bold hover:bg-blue-100 transition-colors inline-flex items-center" data-id="${depto.id}"><i data-lucide="user-plus" class="w-3 h-3 mr-1"></i> Asignar</button>`;
            }

            tr.innerHTML = `
                <td class="p-4"><input type="checkbox" class="chk-depto rounded border-gray-300 text-blue-600" value="${depto.id}"></td>
                <td class="p-4 font-black text-slate-800 text-lg">${depto.numero}</td>
                <td class="p-4">${clienteHtml}</td>
                <td class="p-4 ${!depto.contacto ? 'text-gray-400' : 'text-gray-600'}">${depto.contacto || '--'}</td>
                <td class="p-4">${depto.convenio ? `<span class="bg-green-50 text-green-700 px-2 py-1 rounded-md text-xs font-bold border border-green-200">${depto.convenio}</span>` : '--'}</td>
                <td class="p-4 text-center space-x-2 flex justify-center items-center">
                    <button class="btn-qr-depto text-slate-500 bg-slate-100 px-2 py-1 rounded text-xs font-bold hover:bg-slate-200 hover:text-slate-800 transition-colors inline-flex items-center" data-id="${depto.id}" title="Generar QR"><i data-lucide="qr-code" class="w-3 h-3 mr-1"></i> QR</button>
                    ${accionClienteHtml}
                    <button class="btn-delete-depto text-gray-400 hover:text-rose-600 transition-colors px-1" data-id="${depto.id}"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        lucide.createIcons();
    }

    // --- Form Populators ---
    populateEdificioModal(ed) {
        document.getElementById('form-edificio-id').value = ed.id || '';
        document.getElementById('form-edificio-nombre').value = ed.nombre || '';
        document.getElementById('form-edificio-cuenta').value = ed.id_cuenta || '1';
        document.getElementById('form-edificio-calle').value = ed.calle || '';
        document.getElementById('form-edificio-num-ext').value = ed.num_ext || '';
        document.getElementById('form-edificio-colonia').value = ed.colonia || '';
        document.getElementById('form-edificio-municipio').value = ed.municipio || '';
        document.getElementById('form-edificio-codigo-postal').value = ed.codigo_p || '';
        document.getElementById('form-edificio-orden').value = ed.orden || '0';
    }

    clearEdificioModal() {
        document.getElementById('form-edificio-id').value = '';
        document.getElementById('form-edificio-nombre').value = '';
        document.getElementById('form-edificio-cuenta').value = '1';
        document.getElementById('form-edificio-calle').value = '';
        document.getElementById('form-edificio-num-ext').value = '';
        document.getElementById('form-edificio-colonia').value = '';
        document.getElementById('form-edificio-municipio').value = '';
        document.getElementById('form-edificio-codigo-postal').value = '';
        document.getElementById('form-edificio-orden').value = '0';
    }

    populateClienteModal(depto) {
        document.getElementById('form-cliente-depto-id').value = depto.id || '';
        document.getElementById('form-cliente-id').value = depto.id_cliente || '';
        document.getElementById('form-cliente-nombre').value = depto.cliente_nombre || '';
        document.getElementById('form-cliente-ape-pat').value = depto.cliente_ape_pat || '';
        document.getElementById('form-cliente-ape-mat').value = depto.cliente_ape_mat || '';
        document.getElementById('form-cliente-correo').value = depto.cliente_correo || '';
        document.getElementById('form-cliente-correo-2').value = depto.cliente_correo_2 || '';
        document.getElementById('form-cliente-correo-admin').value = depto.cliente_correo_admin || '';
        document.getElementById('form-cliente-telefono').value = depto.cliente_telefono || '';
        document.getElementById('form-cliente-telefono-2').value = depto.cliente_telefono_2 || '';
        document.getElementById('form-cliente-convenio').value = depto.convenio || '';
        document.getElementById('form-cliente-referencia').value = depto.ref || '';
    }

    clearClienteModal(idDepto) {
        document.getElementById('form-cliente-depto-id').value = idDepto || '';
        document.getElementById('form-cliente-id').value = '';
        document.getElementById('form-cliente-nombre').value = '';
        document.getElementById('form-cliente-ape-pat').value = '';
        document.getElementById('form-cliente-ape-mat').value = '';
        document.getElementById('form-cliente-correo').value = '';
        document.getElementById('form-cliente-correo-2').value = '';
        document.getElementById('form-cliente-correo-admin').value = '';
        document.getElementById('form-cliente-telefono').value = '';
        document.getElementById('form-cliente-telefono-2').value = '';
        document.getElementById('form-cliente-convenio').value = '';
        document.getElementById('form-cliente-referencia').value = '';
    }

    // --- Drag and Drop ---
    initDragAndDrop(onEdificiosReorder) {
        const sortEdificios = document.getElementById('sortable-edificios');
        if(sortEdificios) {
            new Sortable(sortEdificios, {
                animation: 150,
                handle: '.cursor-move',
                ghostClass: 'bg-blue-50',
                onEnd: onEdificiosReorder
            });
        }
    }

    showQR(depto) {
        this.currentIndividualDepto = depto;
        document.getElementById('qr-label').innerText = `DEPTO-${depto.numero}`;
        const qrContainer = document.getElementById("qr-container");
        qrContainer.innerHTML = ""; 
        
        // Exact detailed format specified by the user with empty fallback values to minimize URL length
        const qrString = `id_edificio=${depto.id_edificio}&num_edi=${encodeURIComponent(depto.num_edi || '')}&calle=${encodeURIComponent(depto.calle || '')}&num_ext=${encodeURIComponent(depto.num_ext || '')}&municipio=${encodeURIComponent(depto.municipio || '')}&colonia=${encodeURIComponent(depto.colonia || '')}&codigo_p=${encodeURIComponent(depto.codigo_p || '')}&id_departamento=${depto.id}&num_departamento=${encodeURIComponent(depto.numero || '')}&convenio=${encodeURIComponent(depto.convenio || '')}&referencia=${encodeURIComponent(depto.ref || '')}`;

        this.currentQR = new QRCode(qrContainer, {
            text: qrString,
            width: 200,
            height: 200,
            colorDark : "#0f172a",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.L
        });
        
        this.openModal('modal-qr');
    }

    async executeIndividualQRPrint() {
        const depto = this.currentIndividualDepto;
        if (!depto) return;

        const size = parseInt(document.querySelector('input[name="individual_qr_size"]:checked').value);
        
        const qrString = `id_edificio=${depto.id_edificio}&num_edi=${encodeURIComponent(depto.num_edi || '')}&calle=${encodeURIComponent(depto.calle || '')}&num_ext=${encodeURIComponent(depto.num_ext || '')}&municipio=${encodeURIComponent(depto.municipio || '')}&colonia=${encodeURIComponent(depto.colonia || '')}&codigo_p=${encodeURIComponent(depto.codigo_p || '')}&id_departamento=${depto.id}&num_departamento=${encodeURIComponent(depto.numero || '')}&convenio=${encodeURIComponent(depto.convenio || '')}&referencia=${encodeURIComponent(depto.ref || '')}`;
        
        const hiddenContainer = document.getElementById('hidden-qr-generator');
        hiddenContainer.innerHTML = "";
        
        new QRCode(hiddenContainer, {
            text: qrString,
            width: size,
            height: size,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.L
        });

        await new Promise(r => setTimeout(r, 50)); 
        const canvas = hiddenContainer.querySelector('canvas');
        if(!canvas) return;
        const imgData = canvas.toDataURL("image/png");

        let printWidth = "3.5cm";
        if(size === 300) printWidth = "6cm";
        if(size === 600) printWidth = "10cm";

        const printContent = `
            <html>
            <head>
                <title>Etiqueta - Depto ${depto.numero}</title>
                <style>
                    body { font-family: sans-serif; text-align: center; margin: 0; padding: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; }
                    .qr-card { border: 1px dashed #ccc; padding: 15px; border-radius: 8px; display: flex; flex-direction: column; align-items: center; }
                    .qr-card img { width: ${printWidth}; height: ${printWidth}; }
                    .qr-card p { margin: 10px 0 0 0; font-weight: bold; font-size: 14px; letter-spacing: 1px; }
                </style>
            </head>
            <body>
                <div class="qr-card">
                    <img src="${imgData}" alt="QR">
                    <p>DEPTO - ${depto.numero}</p>
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
            this.closeModals();
        }, 500);
    }

    openBulkQRModal(buildingId, buildingName) {
        this.currentBulkBuildingId = buildingId;
        this.currentBulkBuilding = buildingName;
        document.getElementById('bulk-edificio-name').innerText = buildingName;
        this.openModal('modal-bulk-qr');
    }

    async executeBulkQRPrint(api) {
        const btn = document.getElementById('btn-print-qrs');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i> Generando...';
        btn.disabled = true;
        lucide.createIcons();

        const size = parseInt(document.querySelector('input[name="qr_size"]:checked').value);
        
        let deptos = [];
        try {
            deptos = await api.getDepartamentos(this.currentBulkBuildingId);
        } catch (e) {
            console.error(e);
            alert('Error al cargar departamentos para la impresión');
            btn.innerHTML = originalText;
            btn.disabled = false;
            lucide.createIcons();
            return;
        }

        if (deptos.length === 0) {
            alert('Este edificio no tiene departamentos registrados.');
            btn.innerHTML = originalText;
            btn.disabled = false;
            lucide.createIcons();
            return;
        }

        const hiddenContainer = document.getElementById('hidden-qr-generator');
        let qrImagesHTML = '';

        let printWidth = "3.5cm";
        if(size === 300) printWidth = "6cm";
        if(size === 600) printWidth = "10cm";

        for(let i = 0; i < deptos.length; i++) {
            const depto = deptos[i];
            const qrString = `id_edificio=${depto.id_edificio}&num_edi=${encodeURIComponent(depto.num_edi || '')}&calle=${encodeURIComponent(depto.calle || '')}&num_ext=${encodeURIComponent(depto.num_ext || '')}&municipio=${encodeURIComponent(depto.municipio || '')}&colonia=${encodeURIComponent(depto.colonia || '')}&codigo_p=${encodeURIComponent(depto.codigo_p || '')}&id_departamento=${depto.id}&num_departamento=${encodeURIComponent(depto.numero || '')}&convenio=${encodeURIComponent(depto.convenio || '')}&referencia=${encodeURIComponent(depto.ref || '')}`;
            
            hiddenContainer.innerHTML = "";
            
            new QRCode(hiddenContainer, {
                text: qrString,
                width: size,
                height: size,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.L
            });

            await new Promise(r => setTimeout(r, 50)); 
            const canvas = hiddenContainer.querySelector('canvas');
            if(canvas) {
                const imgData = canvas.toDataURL("image/png");
                qrImagesHTML += `
                    <div class="qr-item">
                        <img src="${imgData}" alt="QR">
                        <p>DEPTO - ${depto.numero}</p>
                    </div>
                `;
            }
        }

        const printContent = `
            <html>
            <head>
                <title>QRs - ${this.currentBulkBuilding}</title>
                <style>
                    body { font-family: sans-serif; text-align: center; margin: 0; padding: 20px; }
                    h1 { font-size: 24px; margin-bottom: 20px; color: #333; }
                    .grid-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; }
                    .qr-item { border: 1px dashed #ccc; padding: 10px; border-radius: 8px; display: flex; flex-direction: column; align-items: center; page-break-inside: avoid; }
                    .qr-item img { width: ${printWidth}; height: ${printWidth}; }
                    .qr-item p { margin: 8px 0 0 0; font-weight: bold; font-size: 14px; letter-spacing: 1px; }
                    @media print { body { padding: 0; } h1 { display: none; } }
                </style>
            </head>
            <body>
                <h1>${this.currentBulkBuilding} - Códigos QR</h1>
                <div class="grid-container">
                    ${qrImagesHTML}
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
            
            btn.innerHTML = originalText;
            btn.disabled = false;
            lucide.createIcons();
            this.closeModals();
        }, 500);
    }
}
