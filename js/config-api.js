/**
 * config-api.js
 * Clase que maneja todas las peticiones AJAX a la API para la configuración.
 */
class ConfigAPI {
    constructor() {
        // Obtenemos la base URL global del sistema
        let base = window.API_BASE_URL || "apis_marvi/public/api/";
        
        // Robustez premium: Si la base URL no contiene index.php, forzamos su inclusión para asegurar
        // compatibilidad absoluta en servidores locales (como XAMPP) donde mod_rewrite/AllowOverride está desactivado.
        // Esto replica exactamente el flujo funcional de EdificiosAPI.js sin alterar las variables globales del sistema.
        if (!base.includes('index.php')) {
            base = base.replace('public/api/', 'public/index.php/api/');
        }
        
        this.API_BASE = base;
        console.log("[ConfigAPI] Inicializado con API_BASE:", this.API_BASE);
    }

    /**
     * Obtiene todos los periodos oficiales registrados.
     */
    getPeriodos() {
        const url = this.API_BASE + 'config/periodos';
        console.log("[ConfigAPI] GET a:", url);
        return $.get(url);
    }

    /**
     * Registra un nuevo periodo oficial.
     */
    savePeriodo(data) {
        const url = this.API_BASE + 'config/periodo';
        console.log("[ConfigAPI] POST a:", url, "con data:", data);
        return $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data)
        });
    }

    /**
     * Obtiene la lista de todos los edificios activos.
     */
    getEdificios() {
        const url = this.API_BASE + 'edificios';
        console.log("[ConfigAPI] GET a:", url);
        return $.get(url);
    }

    /**
     * Obtiene la configuración comercial vigente de un edificio.
     */
    getEdificioConfig(id) {
        const url = `${this.API_BASE}edificio/${id}/config`;
        console.log("[ConfigAPI] GET a:", url);
        return $.get(url);
    }

    /**
     * Obtiene la bitácora histórica de precios, factores y cuotas de un edificio.
     */
    getEdificioHistory(id) {
        const url = `${this.API_BASE}edificio/${id}/history`;
        console.log("[ConfigAPI] GET a:", url);
        return $.get(url);
    }

    /**
     * Guarda la nueva configuración comercial de un edificio.
     */
    saveEdificioConfig(id, data) {
        const url = `${this.API_BASE}edificio/${id}/config`;
        console.log("[ConfigAPI] POST a:", url, "con data:", data);
        return $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data)
        });
    }
}
