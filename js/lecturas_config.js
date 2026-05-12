/**
 * lecturas_config.js
 * Configuración y parámetros específicos para el módulo de lecturas OCR.
 */

const LECTURAS_CONFIG = {
    autoNext: true,
    animationDuration: 500,
    
    // Configuración para Tom Select (Selectores Profesionales)
    TOM_SELECT: {
        edificio: {
            valueField: 'id_edificio',
            labelField: 'num_edificio',
            searchField: ['num_edificio', 'calle'],
            placeholder: 'Seleccionar Edificio...',
            allowEmptyOption: true,
            render: {
                option: function(data, escape) {
                    return `<div>
                        <span class="font-bold">${escape(data.num_edificio)}</span>
                        <span class="block text-[10px] text-gray-400 uppercase font-black">${escape(data.calle || 'S/N')}</span>
                    </div>`;
                },
                item: function(data, escape) {
                    return `<div><span class="font-bold">${escape(data.num_edificio)}</span></div>`;
                }
            }
        },
        departamento: {
            valueField: 'id_departamento',
            labelField: 'num_departamento',
            searchField: ['num_departamento'],
            placeholder: 'Seleccionar Depto...',
            allowEmptyOption: true,
            render: {
                option: function(data, escape, BUILDING_NAME = '') {
                    return `<div>
                        <span class="font-bold">${escape(data.num_departamento)}</span>
                        <span class="block text-[10px] text-gray-400 uppercase font-black">${escape(BUILDING_NAME)}</span>
                    </div>`;
                }
            }
        }
    }
};

// Inicialización de componentes de terceros si los hubiera
// Ejemplo: dropzoneConfig, etc.
console.log("Lecturas Config Loaded");
