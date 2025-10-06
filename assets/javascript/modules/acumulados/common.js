/**
 * Módulo Común para Acumulados
 * Maneja filtrado automático por tipo de planilla desde sessionStorage
 */

class AcumuladosCommon {
    constructor() {
        this.init();
    }

    init() {
        // Aplicar filtro de tipo de planilla desde sessionStorage al cargar la página
        this.applyTipoPlanillaFilter();

        // Escuchar cambios en el tipo de planilla desde navbar
        window.addEventListener('payrollTypeChanged', (event) => {
            console.log('AcumuladosCommon: Tipo de planilla cambió', event.detail);
            this.handlePayrollTypeChange(event.detail.tipoPlanillaId);
        });
    }

    /**
     * Aplicar filtro de tipo de planilla al cargar la página
     */
    applyTipoPlanillaFilter() {
        try {
            // Leer tipo de planilla desde sessionStorage
            const tipoPlanillaData = sessionStorage.getItem('selectedPayrollType');

            console.log('AcumuladosCommon: Tipo planilla desde sessionStorage:', tipoPlanillaData);

            if (!tipoPlanillaData || tipoPlanillaData === '' || tipoPlanillaData === 'null') {
                return;
            }

            // Extraer solo el ID del objeto JSON
            let tipoPlanillaId = null;
            try {
                const parsed = JSON.parse(tipoPlanillaData);
                tipoPlanillaId = parsed.id || parsed;
            } catch (e) {
                // Si no es JSON, usar el valor directo
                tipoPlanillaId = tipoPlanillaData;
            }

            console.log('AcumuladosCommon: ID extraído:', tipoPlanillaId);

            // Si hay tipo de planilla seleccionado y no está en la URL
            if (tipoPlanillaId && tipoPlanillaId !== '' && tipoPlanillaId !== 'null') {
                const urlParams = new URLSearchParams(window.location.search);

                // Si la URL no tiene el parámetro tipo_planilla, agregarlo
                if (!urlParams.has('tipo_planilla')) {
                    urlParams.set('tipo_planilla', tipoPlanillaId);

                    // Recargar la página con el nuevo parámetro
                    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
                    console.log('AcumuladosCommon: Redirigiendo a:', newUrl);
                    window.location.href = newUrl;
                }
            }
        } catch (error) {
            console.error('Error aplicando filtro de tipo planilla:', error);
        }
    }

    /**
     * Manejar cambio de tipo de planilla desde navbar
     */
    handlePayrollTypeChange(tipoPlanillaData) {
        try {
            // Extraer solo el ID
            let tipoPlanillaId = null;
            if (tipoPlanillaData && typeof tipoPlanillaData === 'object') {
                tipoPlanillaId = tipoPlanillaData.id;
            } else {
                tipoPlanillaId = tipoPlanillaData;
            }

            console.log('AcumuladosCommon: Cambio a tipo planilla ID:', tipoPlanillaId);

            const urlParams = new URLSearchParams(window.location.search);

            if (tipoPlanillaId && tipoPlanillaId !== '' && tipoPlanillaId !== 'null') {
                // Agregar o actualizar parámetro tipo_planilla
                urlParams.set('tipo_planilla', tipoPlanillaId);
            } else {
                // Remover parámetro si se deseleccionó
                urlParams.delete('tipo_planilla');
            }

            // Recargar la página con los nuevos parámetros
            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
            console.log('AcumuladosCommon: Recargando con nuevo tipo planilla:', newUrl);
            window.location.href = newUrl;
        } catch (error) {
            console.error('Error manejando cambio de tipo planilla:', error);
        }
    }

    /**
     * Obtener tipo de planilla seleccionado desde sessionStorage
     */
    static getTipoPlanillaId() {
        try {
            const tipoPlanillaData = sessionStorage.getItem('selectedPayrollType');

            if (!tipoPlanillaData || tipoPlanillaData === 'null') {
                return null;
            }

            // Extraer solo el ID del objeto JSON
            try {
                const parsed = JSON.parse(tipoPlanillaData);
                return parsed.id || null;
            } catch (e) {
                // Si no es JSON, usar el valor directo
                return tipoPlanillaData;
            }
        } catch (error) {
            console.error('Error obteniendo tipo planilla:', error);
            return null;
        }
    }

    /**
     * Agregar parámetro tipo_planilla a un formulario
     */
    static addTipoPlanillaToForm(formElement) {
        const tipoPlanillaId = AcumuladosCommon.getTipoPlanillaId();

        if (tipoPlanillaId) {
            // Buscar si ya existe el campo
            let input = formElement.querySelector('input[name="tipo_planilla"]');

            if (!input) {
                // Crear nuevo campo hidden
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tipo_planilla';
                formElement.appendChild(input);
            }

            input.value = tipoPlanillaId;
        }
    }

    /**
     * Agregar parámetro tipo_planilla a una URL
     */
    static addTipoPlanillaToUrl(url) {
        const tipoPlanillaId = AcumuladosCommon.getTipoPlanillaId();

        if (!tipoPlanillaId) {
            return url;
        }

        try {
            const urlObj = new URL(url, window.location.origin);
            urlObj.searchParams.set('tipo_planilla', tipoPlanillaId);
            return urlObj.toString();
        } catch (error) {
            console.error('Error agregando tipo planilla a URL:', error);
            return url;
        }
    }
}

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', function() {
    new AcumuladosCommon();
});

// Exportar para uso en otros módulos
window.AcumuladosCommon = AcumuladosCommon;
