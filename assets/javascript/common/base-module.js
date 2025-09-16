/**
 * Base Module Class
 * Clase base para todos los módulos JavaScript del sistema
 * 
 * Proporciona:
 * - Gestión de configuración
 * - Manejo de peticiones AJAX
 * - Gestión de estado común
 * - Métodos de limpieza
 */

class BaseModule {
    constructor() {
        this.config = {};
        this.state = {};
        this.initialized = false;
    }

    /**
     * Establecer configuración del módulo
     */
    setConfig(config) {
        this.config = { ...this.config, ...config };
        return this;
    }

    /**
     * Obtener valor de configuración
     */
    getConfig(key, defaultValue = null) {
        return this.config[key] || defaultValue;
    }

    /**
     * Obtener token CSRF desde la configuración
     */
    getCsrfToken() {
        return this.getConfig('csrf_token') || (window.PAYROLL_CONFIG && window.PAYROLL_CONFIG.csrfToken) || '';
    }

    /**
     * Realizar petición AJAX con jQuery (para compatibilidad con código existente)
     */
    makeAjaxRequest(url, method = 'GET', data = {}, options = {}) {
        return new Promise((resolve, reject) => {
            const ajaxConfig = {
                url: url,
                type: method.toUpperCase(),
                data: {
                    ...data,
                    csrf_token: this.getCsrfToken()
                },
                dataType: 'json',
                xhrFields: {
                    withCredentials: true
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('X-CSRF-Token', this.getCsrfToken());
                }.bind(this),
                success: function(response) {
                    resolve(response);
                },
                error: function(xhr, status, error) {
                    reject({
                        xhr: xhr,
                        status: status,
                        error: error
                    });
                }.bind(this),
                ...options
            };

            $.ajax(ajaxConfig);
        });
    }

    /**
     * Mostrar alerta personalizada
     */
    showAlert(message, type = 'info') {
        // Por ahora usar alert, puede ser mejorado con un sistema de notificaciones
        alert(message);
    }

    /**
     * Enlazar evento a un selector
     */
    bindEvent(selector, event, handler) {
        $(document).off(`${event}.${this.constructor.name}`, selector);
        $(document).on(`${event}.${this.constructor.name}`, selector, handler);
    }

    /**
     * Desenlazar todos los eventos del módulo
     */
    unbindEvents() {
        $(document).off(`.${this.constructor.name}`);
    }

    /**
     * Manejar errores AJAX de forma estándar
     */
    handleAjaxError(xhr, error, customMessage = 'Error en la petición') {
        console.error(`[${this.constructor.name}] AJAX Error:`, error);
        console.error('Response:', xhr.responseText);
        console.error('Status:', xhr.status);
        
        const errorMessage = xhr.responseJSON?.message || xhr.responseJSON?.error || error || customMessage;
        this.showError(errorMessage, { xhr, error });
    }

    /**
     * Inicializar el módulo
     * Debe ser sobreescrito por las clases hijas
     */
    init() {
        this.initialized = true;
        console.log(`[${this.constructor.name}] Initialized`);
    }

    /**
     * Verificar si el módulo está inicializado
     */
    isInitialized() {
        return this.initialized;
    }

    /**
     * Realizar petición AJAX con configuración base
     */
    async request(url, data = {}, options = {}) {
        const defaultOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.getConfig('csrf_token', '')
            },
            credentials: 'include'
        };

        const mergedOptions = { ...defaultOptions, ...options };

        // Si es POST y tenemos datos, convertir a FormData o URLSearchParams
        if (mergedOptions.method === 'POST' && data) {
            if (data instanceof FormData) {
                mergedOptions.body = data;
                // Remover Content-Type para que el navegador establezca el boundary
                delete mergedOptions.headers['Content-Type'];
            } else {
                const formData = new URLSearchParams();
                Object.keys(data).forEach(key => {
                    formData.append(key, data[key]);
                });
                mergedOptions.body = formData.toString();
            }
        }

        try {
            const response = await fetch(url, mergedOptions);
            
            // Verificar si la respuesta es exitosa
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Intentar parsear como JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }

        } catch (error) {
            console.error(`[${this.constructor.name}] Request failed:`, error);
            throw error;
        }
    }

    /**
     * Petición GET simplificada
     */
    async get(url, params = {}) {
        const urlObj = new URL(url);
        Object.keys(params).forEach(key => {
            urlObj.searchParams.append(key, params[key]);
        });
        
        return this.request(urlObj.toString(), {}, { method: 'GET' });
    }

    /**
     * Petición POST simplificada
     */
    async post(url, data = {}) {
        return this.request(url, data, { method: 'POST' });
    }

    /**
     * Mostrar mensaje de error al usuario
     */
    showError(message, error = null) {
        console.error(`[${this.constructor.name}] Error:`, message, error);
        
        // Mostrar alerta (puede ser mejorado con un sistema de notificaciones más sofisticado)
        alert(`Error: ${message}`);
    }

    /**
     * Mostrar mensaje de éxito
     */
    showSuccess(message) {
        console.log(`[${this.constructor.name}] Success:`, message);
        // Puede ser mejorado con un sistema de notificaciones
        alert(message);
    }


    /**
     * Verificar si un elemento existe en el DOM
     */
    elementExists(selector) {
        return $(selector).length > 0;
    }

    /**
     * Obtener elemento jQuery con validación
     */
    getElement(selector) {
        const element = $(selector);
        if (element.length === 0) {
            console.warn(`[${this.constructor.name}] Element not found: ${selector}`);
        }
        return element;
    }

    /**
     * Limpiar recursos del módulo
     * Debe ser sobreescrito por las clases hijas si necesitan limpieza específica
     */
    destroy() {
        this.unbindEvents();
        this.initialized = false;
        console.log(`[${this.constructor.name}] Destroyed`);
    }

    /**
     * Log de debug
     */
    log(...args) {
        console.log(`[${this.constructor.name}]`, ...args);
    }

    /**
     * Log de advertencia
     */
    warn(...args) {
        console.warn(`[${this.constructor.name}]`, ...args);
    }

    /**
     * Log de error
     */
    error(...args) {
        console.error(`[${this.constructor.name}]`, ...args);
    }
}

export { BaseModule };