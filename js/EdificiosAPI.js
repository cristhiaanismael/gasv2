class EdificiosAPI {
    constructor() {
        this.baseUrl = 'apis_marvi/public/index.php/api';
    }

    // --- Edificios ---
    async getEdificios() {
        const response = await fetch(`${this.baseUrl}/edificios`);
        if (!response.ok) throw new Error('Error al obtener edificios');
        const data = await response.json();
        
        // Map database fields to the structure expected by the UI
        return data.map(ed => ({
            id: parseInt(ed.id_edificio),
            nombre: ed.num_edificio,
            direccion: `${ed.calle} ${ed.num_ext}, Col. ${ed.colonia}, ${ed.municipio}, CP ${ed.codigo_p}`,
            calle: ed.calle,
            num_ext: ed.num_ext,
            colonia: ed.colonia,
            municipio: ed.municipio,
            codigo_p: ed.codigo_p,
            cuenta: ed.id_cuenta == 1 ? 'ZAIRA ABIGAIL VILLA GARCIA' : 'LIZZETTE VILLA GARCIA',
            id_cuenta: parseInt(ed.id_cuenta),
            orden: parseInt(ed.orden || 0)
        }));
    }

    async saveEdificio(data) {
        const response = await fetch(`${this.baseUrl}/edificios/save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (!response.ok) throw new Error('Error al guardar edificio');
        return await response.json();
    }

    async deleteEdificio(id) {
        const response = await fetch(`${this.baseUrl}/edificios/${id}`, {
            method: 'DELETE'
        });
        if (!response.ok) throw new Error('Error al eliminar edificio');
        return await response.json();
    }

    async updateEdificioOrden(ids) {
        const response = await fetch(`${this.baseUrl}/edificios/reorder`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        });
        if (!response.ok) throw new Error('Error al reordenar edificios');
        return await response.json();
    }

    // --- Departamentos ---
    async getDepartamentos(idEdificio) {
        const response = await fetch(`${this.baseUrl}/edificio/${idEdificio}/departamentos`);
        if (!response.ok) throw new Error('Error al obtener departamentos');
        return await response.json();
    }

    async saveDepartamento(data) {
        const response = await fetch(`${this.baseUrl}/departamentos/save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (!response.ok) throw new Error('Error al guardar departamento');
        return await response.json();
    }

    async deleteDepartamento(id) {
        const response = await fetch(`${this.baseUrl}/departamentos/${id}`, {
            method: 'DELETE'
        });
        if (!response.ok) throw new Error('Error al eliminar departamento');
        return await response.json();
    }

    async migrateDepartamentos(data) {
        const response = await fetch(`${this.baseUrl}/departamentos/migrate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (!response.ok) throw new Error('Error al migrar departamentos');
        return await response.json();
    }

    // --- Clientes ---
    async saveCliente(data) {
        const response = await fetch(`${this.baseUrl}/clientes/save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if (!response.ok) throw new Error('Error al guardar cliente');
        return await response.json();
    }
}
