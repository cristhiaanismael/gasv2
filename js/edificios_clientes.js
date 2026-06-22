document.addEventListener('DOMContentLoaded', () => {
    // Inicializar clases
    const api = new EdificiosAPI();
    const ui = new EdificiosUI();

    // Cache local de datos cargados para evitar peticiones redundantes al editar
    let localEdificios = [];
    let localDeptos = [];

    // Inicializar íconos
    lucide.createIcons();

    // Cargar datos iniciales
    cargarDatosIniciales();

    // Mostrar un toast simple
    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) return;
        
        const toast = document.createElement('div');
        toast.className = `flex items-center gap-3 p-4 rounded-2xl shadow-lg border text-sm font-semibold pointer-events-auto transition-all transform translate-y-2 opacity-0 duration-300 ${
            type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
        }`;
        
        const icon = type === 'success' ? 'check-circle' : 'alert-circle';
        toast.innerHTML = `<i data-lucide="${icon}" class="w-5 h-5 flex-shrink-0"></i> <span>${message}</span>`;
        toastContainer.appendChild(toast);
        
        lucide.createIcons();
        
        // Animación entrada
        setTimeout(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
        }, 10);
        
        // Salida automática
        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    async function cargarDatosIniciales() {
        try {
            const edificios = await api.getEdificios();
            localEdificios = edificios;
            ui.renderEdificios(edificios);

            // Poblar dropdown de selección
            const selectFiltro = document.getElementById('select-filtro-edificio');
            const selectMigrar = document.getElementById('form-migrar-destino');
            
            if (selectFiltro) {
                selectFiltro.innerHTML = '';
                edificios.forEach(ed => {
                    const opt = document.createElement('option');
                    opt.value = ed.id;
                    opt.innerText = ed.nombre;
                    selectFiltro.appendChild(opt);
                });
            }

            if (selectMigrar) {
                selectMigrar.innerHTML = '<option value="">-- Selecciona Destino --</option>';
                edificios.forEach(ed => {
                    const opt = document.createElement('option');
                    opt.value = ed.id;
                    opt.innerText = ed.nombre;
                    selectMigrar.appendChild(opt);
                });
            }

            // Cargar departamentos del primer edificio
            if (edificios.length > 0) {
                const firstId = selectFiltro ? selectFiltro.value : edificios[0].id;
                await cargarDepartamentos(firstId);
            } else {
                ui.renderDepartamentos([]);
            }

            // Inicializar Drag and Drop vinculando callbacks de API
            ui.initDragAndDrop(
                async (evt) => {
                    const rows = Array.from(document.querySelectorAll('#sortable-edificios tr'));
                    const ids = rows.map(tr => parseInt(tr.getAttribute('data-id')));
                    try {
                        await api.updateEdificioOrden(ids);
                        
                        // Update visual Order column and local cache
                        rows.forEach((tr, index) => {
                            const newOrden = (index + 1) * 10;
                            const cell = tr.querySelector('.col-orden');
                            if (cell) cell.innerText = newOrden;
                            
                            const id = parseInt(tr.getAttribute('data-id'));
                            const ed = localEdificios.find(x => x.id === id);
                            if (ed) ed.orden = newOrden;
                        });

                        showToast('Orden de edificios actualizado');
                    } catch (e) {
                        showToast('Error al actualizar orden', 'error');
                    }
                }
            );
        } catch (e) {
            console.error(e);
            showToast('Error al cargar edificios', 'error');
        }
    }

    async function cargarDepartamentos(idEdificio) {
        if (!idEdificio) return;
        try {
            const deptos = await api.getDepartamentos(idEdificio);
            localDeptos = deptos;
            ui.renderDepartamentos(deptos);
        } catch (e) {
            console.error(e);
            showToast('Error al cargar departamentos', 'error');
        }
    }

    // --- EVENT LISTENERS ---

    // 1. Tabs
    document.querySelectorAll('[data-tab]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const tabName = e.currentTarget.getAttribute('data-tab');
            ui.switchTab(tabName);
        });
    });

    // 2. Filtro de Edificio para Departamentos
    const selectFiltro = document.getElementById('select-filtro-edificio');
    if (selectFiltro) {
        selectFiltro.addEventListener('change', (e) => {
            cargarDepartamentos(e.target.value);
        });
    }

    // 3. Overlay / Cerrar Modales
    document.getElementById('modal-overlay').addEventListener('click', (e) => {
        if (e.target.id === 'modal-overlay' || e.target.closest('.btn-close-modal')) {
            ui.closeModals();
        }
    });
    
    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', () => ui.closeModals());
    });

    // 4. Botones de Alta
    document.getElementById('btn-nuevo-edificio').addEventListener('click', () => {
        ui.clearEdificioModal();
        ui.openModal('modal-edificio');
    });
    
    document.getElementById('btn-nuevo-depto').addEventListener('click', () => {
        const selectFiltro = document.getElementById('select-filtro-edificio');
        if (!selectFiltro || !selectFiltro.value) {
            alert('Por favor selecciona o da de alta un edificio primero.');
            return;
        }
        document.getElementById('form-depto-numero').value = '';
        ui.openModal('modal-departamento');
    });
    
    document.getElementById('btn-migrar-deptos').addEventListener('click', () => {
        const checked = Array.from(document.querySelectorAll('.chk-depto:checked'));
        if (checked.length === 0) {
            alert('Por favor selecciona al menos un departamento para migrar.');
            return;
        }
        ui.openModal('modal-migrar');
    });

    // 5. Delegación de eventos para botones generados dinámicamente en tablas
    document.addEventListener('click', async (e) => {
        const target = e.target.closest('button');
        if (!target) return;

        // --- Edificios ---
        if (target.classList.contains('btn-bulk-qr')) {
            const id = parseInt(target.getAttribute('data-id'));
            const nombre = target.getAttribute('data-nombre');
            ui.openBulkQRModal(id, nombre);
        } else if (target.classList.contains('btn-edit-edificio')) {
            const id = parseInt(target.getAttribute('data-id'));
            const ed = localEdificios.find(x => x.id === id);
            if (ed) {
                ui.populateEdificioModal(ed);
                ui.openModal('modal-edificio');
            }
        } else if (target.classList.contains('btn-delete-edificio')) {
            const id = target.getAttribute('data-id');
            if (confirm('¿Seguro que deseas eliminar este edificio? Se realizará un borrado lógico.')) {
                try {
                    await api.deleteEdificio(id);
                    showToast('Edificio eliminado correctamente');
                    cargarDatosIniciales();
                } catch (err) {
                    showToast('Error al eliminar edificio', 'error');
                }
            }
        }

        // --- Departamentos ---
        else if (target.classList.contains('btn-qr-depto')) {
            const id = parseInt(target.getAttribute('data-id'));
            const depto = localDeptos.find(x => x.id === id);
            if (depto) {
                ui.showQR(depto);
            }
        } else if (target.classList.contains('btn-edit-cliente')) {
            const id = parseInt(target.getAttribute('data-id'));
            const depto = localDeptos.find(x => x.id === id);
            if (depto) {
                ui.populateClienteModal(depto);
                ui.openModal('modal-cliente');
            }
        } else if (target.classList.contains('btn-assign-cliente')) {
            const id = parseInt(target.getAttribute('data-id'));
            ui.clearClienteModal(id);
            ui.openModal('modal-cliente');
        } else if (target.classList.contains('btn-delete-depto')) {
            const id = target.getAttribute('data-id');
            if (confirm('¿Seguro que deseas eliminar este departamento? Se realizará un borrado lógico.')) {
                try {
                    await api.deleteDepartamento(id);
                    showToast('Departamento eliminado correctamente');
                    const selectFiltro = document.getElementById('select-filtro-edificio');
                    if (selectFiltro) cargarDepartamentos(selectFiltro.value);
                } catch (err) {
                    showToast('Error al eliminar departamento', 'error');
                }
            }
        }
    });

    // 6. Botones de Acción dentro de Modales
    document.getElementById('btn-guardar-edificio').addEventListener('click', async () => {
        const id_edificio = document.getElementById('form-edificio-id').value;
        const num_edificio = document.getElementById('form-edificio-nombre').value.trim();
        const id_cuenta = document.getElementById('form-edificio-cuenta').value;
        const calle = document.getElementById('form-edificio-calle').value.trim();
        const num_ext = document.getElementById('form-edificio-num-ext').value.trim();
        const colonia = document.getElementById('form-edificio-colonia').value.trim();
        const municipio = document.getElementById('form-edificio-municipio').value.trim();
        const codigo_p = document.getElementById('form-edificio-codigo-postal').value.trim();
        const orden = document.getElementById('form-edificio-orden').value;

        if (!num_edificio || !calle) {
            alert('Nombre del edificio y Calle son requeridos.');
            return;
        }

        const payload = {
            num_edificio,
            id_cuenta,
            calle,
            num_ext,
            colonia,
            municipio,
            codigo_p,
            orden
        };
        if (id_edificio) payload.id_edificio = id_edificio;

        try {
            const res = await api.saveEdificio(payload);
            if (res.success) {
                showToast(res.message);
                ui.closeModals();
                cargarDatosIniciales();
            }
        } catch (e) {
            showToast('Error al guardar edificio', 'error');
        }
    });

    document.getElementById('btn-guardar-depto').addEventListener('click', async () => {
        const selectFiltro = document.getElementById('select-filtro-edificio');
        const id_edificio = selectFiltro ? selectFiltro.value : null;
        const num_departamento = document.getElementById('form-depto-numero').value.trim();

        if (!num_departamento || !id_edificio) {
            alert('Por favor ingresa un número de departamento válido.');
            return;
        }

        try {
            const res = await api.saveDepartamento({
                id_edificio,
                num_departamento
            });
            if (res.success) {
                showToast('Departamento guardado correctamente');
                ui.closeModals();
                cargarDepartamentos(id_edificio);
            }
        } catch (e) {
            showToast('Error al guardar departamento', 'error');
        }
    });

    document.getElementById('btn-guardar-cliente').addEventListener('click', async () => {
        const id_departamento = document.getElementById('form-cliente-depto-id').value;
        const id_cliente = document.getElementById('form-cliente-id').value;
        const nombre = document.getElementById('form-cliente-nombre').value.trim();
        const ape_pat = document.getElementById('form-cliente-ape-pat').value.trim();
        const ape_mat = document.getElementById('form-cliente-ape-mat').value.trim();
        const correo = document.getElementById('form-cliente-correo').value.trim();
        const correo_2 = document.getElementById('form-cliente-correo-2').value.trim();
        const correo_admin = document.getElementById('form-cliente-correo-admin').value.trim();
        const telefono = document.getElementById('form-cliente-telefono').value.trim();
        const telefono_2 = document.getElementById('form-cliente-telefono-2').value.trim();
        const convenio = document.getElementById('form-cliente-convenio').value.trim();
        const referencia = document.getElementById('form-cliente-referencia').value.trim();

        if (!nombre || !ape_pat || !id_departamento) {
            alert('Nombre y Apellido Paterno son campos obligatorios.');
            return;
        }

        const payload = {
            id_departamento,
            nombre,
            ape_pat,
            ape_mat,
            correo,
            correo_2,
            correo_admin,
            telefono,
            telefono_2,
            convenio,
            referencia
        };
        if (id_cliente) payload.id_cliente = id_cliente;

        try {
            const res = await api.saveCliente(payload);
            if (res.success) {
                showToast(res.message);
                ui.closeModals();
                const selectFiltro = document.getElementById('select-filtro-edificio');
                if (selectFiltro) cargarDepartamentos(selectFiltro.value);
            }
        } catch (e) {
            showToast('Error al guardar cliente', 'error');
        }
    });

    document.getElementById('btn-ejecutar-migracion').addEventListener('click', async () => {
        const selectFiltro = document.getElementById('select-filtro-edificio');
        const selectMigrar = document.getElementById('form-migrar-destino');
        const edificioDestino = selectMigrar ? selectMigrar.value : null;

        if (!edificioDestino) {
            alert('Por favor selecciona un edificio de destino.');
            return;
        }

        const checked = Array.from(document.querySelectorAll('.chk-depto:checked')).map(chk => parseInt(chk.value));
        if (checked.length === 0) {
            alert('Por favor selecciona al menos un departamento.');
            return;
        }

        try {
            const res = await api.migrateDepartamentos({
                edificioDestino,
                deptos: checked
            });
            if (res.success) {
                showToast('Departamentos migrados correctamente');
                ui.closeModals();
                if (selectFiltro) cargarDepartamentos(selectFiltro.value);
            }
        } catch (e) {
            showToast('Error al migrar departamentos', 'error');
        }
    });

    document.getElementById('btn-print-qrs').addEventListener('click', () => {
        ui.executeBulkQRPrint(api);
    });

    document.getElementById('btn-print-individual-qr').addEventListener('click', () => {
        ui.executeIndividualQRPrint();
    });

});
