/**
 * Tenant Storage Manager
 * Gestiona sessionStorage y localStorage por tenant
 * Limpia datos automáticamente cuando cambia de empresa
 */

const TenantStorageManager = {
    /**
     * Prefijo para keys de storage
     */
    TENANT_KEY: 'current_tenant_id',
    TENANT_LICENSE: 'current_tenant_license',

    /**
     * Limpiar todo el storage (sessionStorage y localStorage)
     */
    clearAll: function() {
        console.log('[TenantStorageManager] Limpiando todo el storage...');

        // Limpiar sessionStorage
        sessionStorage.clear();

        // Limpiar localStorage
        localStorage.clear();

        console.log('[TenantStorageManager] Storage limpiado completamente');
    },

    /**
     * Limpiar solo datos específicos del tenant
     */
    clearTenantData: function() {
        console.log('[TenantStorageManager] Limpiando datos del tenant...');

        // Lista de keys que deben limpiarse al cambiar de tenant
        const keysToRemove = [
            'selectedPayrollType',
            'payroll_type_filter',
            'tipo_planilla_id',
            'dashboard_filters',
            'last_viewed_employee',
            'last_payroll_filter',
            'acumulados_filter',
            'vacation_filter',
            'attendance_filter'
        ];

        // Limpiar sessionStorage
        keysToRemove.forEach(key => {
            sessionStorage.removeItem(key);
        });

        // Limpiar localStorage
        keysToRemove.forEach(key => {
            localStorage.removeItem(key);
        });

        console.log('[TenantStorageManager] Datos del tenant limpiados');
    },

    /**
     * Establecer el tenant actual
     */
    setCurrentTenant: function(tenantId, tenantLicense) {
        console.log(`[TenantStorageManager] Estableciendo tenant: ${tenantId} (${tenantLicense})`);

        sessionStorage.setItem(this.TENANT_KEY, tenantId);
        sessionStorage.setItem(this.TENANT_LICENSE, tenantLicense);

        // También guardar en localStorage para persistencia
        localStorage.setItem(this.TENANT_KEY, tenantId);
        localStorage.setItem(this.TENANT_LICENSE, tenantLicense);
    },

    /**
     * Obtener el tenant actual
     */
    getCurrentTenant: function() {
        return {
            id: sessionStorage.getItem(this.TENANT_KEY) || localStorage.getItem(this.TENANT_KEY),
            license: sessionStorage.getItem(this.TENANT_LICENSE) || localStorage.getItem(this.TENANT_LICENSE)
        };
    },

    /**
     * Verificar si el tenant cambió
     */
    hasTenantChanged: function(newTenantId, newTenantLicense) {
        const current = this.getCurrentTenant();

        // Si no hay tenant actual, no ha cambiado
        if (!current.id && !current.license) {
            return false;
        }

        // Verificar si alguno cambió
        const idChanged = current.id && current.id !== newTenantId;
        const licenseChanged = current.license && current.license !== newTenantLicense;

        return idChanged || licenseChanged;
    },

    /**
     * Validar y limpiar si es necesario
     */
    validateAndClean: function(tenantId, tenantLicense) {
        console.log(`[TenantStorageManager] Validando tenant: ${tenantId} (${tenantLicense})`);

        if (this.hasTenantChanged(tenantId, tenantLicense)) {
            console.warn('[TenantStorageManager] ¡Tenant cambió! Limpiando datos...');
            this.clearTenantData();
        }

        // Actualizar tenant actual
        this.setCurrentTenant(tenantId, tenantLicense);
    },

    /**
     * Limpiar al hacer logout
     */
    clearOnLogout: function() {
        console.log('[TenantStorageManager] Limpiando datos de logout...');
        this.clearAll();
    },

    /**
     * Inicializar en login
     */
    initOnLogin: function(tenantId, tenantLicense) {
        console.log(`[TenantStorageManager] Inicializando para tenant: ${tenantId} (${tenantLicense})`);

        // Limpiar datos del tenant anterior si existe
        this.clearTenantData();

        // Establecer nuevo tenant
        this.setCurrentTenant(tenantId, tenantLicense);
    },

    /**
     * Obtener información de debug
     */
    getDebugInfo: function() {
        const current = this.getCurrentTenant();

        return {
            currentTenant: current,
            sessionStorageKeys: Object.keys(sessionStorage),
            localStorageKeys: Object.keys(localStorage),
            sessionStorageSize: JSON.stringify(sessionStorage).length,
            localStorageSize: JSON.stringify(localStorage).length
        };
    }
};

// Exponer globalmente
window.TenantStorageManager = TenantStorageManager;

// Log de inicialización
console.log('[TenantStorageManager] Módulo cargado y listo');
