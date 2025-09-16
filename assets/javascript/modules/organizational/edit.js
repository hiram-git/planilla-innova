/**
 * Módulo Organizacional - Vista de Edición
 * Gestión del formulario de edición de elementos organizacionales
 */

class OrganizationalEditModule {
    constructor(config = {}) {
        this.baseUrl = config.baseUrl || '';
        this.element = config.element || {};
        this.elements = config.elements || [];
        this.isLoading = false;
        this.init();
    }

    init() {
        this.createLoadingOverlay();
        this.initializeSelect2();
        this.bindEvents();
    }

    initializeSelect2() {
        // Inicializar Select2 si está disponible
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: '-- Seleccione un elemento padre --',
                allowClear: true
            });
        }
    }

    bindEvents() {
        // Vista previa del path en tiempo real
        const descripcionInput = document.getElementById('descripcion');
        const parentSelect = document.getElementById('id_padre');

        if (descripcionInput) {
            descripcionInput.addEventListener('input', () => this.updatePathPreview());
        }

        if (parentSelect) {
            parentSelect.addEventListener('change', () => this.updatePathPreview());
        }

        // Bloquear formulario durante envío
        this.blockFormOnSubmit();
    }

    // Crear overlay de loading
    createLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'organizational-loading-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        `;
        
        overlay.innerHTML = `
            <div style="text-align: center; color: white;">
                <div class="spinner-border text-light mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="sr-only">Cargando...</span>
                </div>
                <h4>Procesando...</h4>
                <p>Por favor espere, no cierre ni actualice la página</p>
            </div>
        `;
        
        document.body.appendChild(overlay);
    }

    // Mostrar loading
    showLoading(message = 'Procesando...') {
        if (this.isLoading) return;
        
        this.isLoading = true;
        const overlay = document.getElementById('organizational-loading-overlay');
        const messageElement = overlay.querySelector('h4');
        
        if (messageElement) {
            messageElement.textContent = message;
        }
        
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    // Ocultar loading
    hideLoading() {
        this.isLoading = false;
        const overlay = document.getElementById('organizational-loading-overlay');
        
        if (overlay) {
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // Bloquear formulario durante envío
    blockFormOnSubmit() {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (this.isLoading) {
                    e.preventDefault();
                    toastr.warning('Ya hay una operación en proceso', 'Espere por favor');
                    return false;
                }
                
                // Validar campos requeridos
                const descripcion = document.getElementById('descripcion')?.value?.trim();
                if (!descripcion) {
                    e.preventDefault();
                    toastr.error('La descripción es requerida', 'Campo Obligatorio');
                    return false;
                }
                
                // Mostrar loading
                this.showLoading('Actualizando elemento organizacional...');
            });
        }
    }

    updatePathPreview() {
        const descripcion = document.getElementById('descripcion')?.value || '';
        const parentId = document.getElementById('id_padre')?.value || '';
        
        if (!descripcion) return;

        // Simular nuevo path
        let newPath = '';
        if (parentId) {
            // Buscar el path del padre seleccionado
            const parentElement = this.elements.find(el => el.id == parentId);
            if (parentElement) {
                newPath = parentElement.path;
            }
        } else {
            newPath = '/';
        }

        // Generar slug de la descripción
        const slug = descripcion.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
            
        newPath += slug + '/';

        // Actualizar la vista previa
        const previewContainer = document.getElementById('path-preview');
        if (previewContainer) {
            previewContainer.innerHTML = `
                <div>
                    <strong>Path actual:</strong><br>
                    <code>${this.element.path || ''}</code>
                </div>
                <div class="mt-2">
                    <strong>Nuevo path:</strong><br>
                    <code class="text-success">${newPath}</code>
                </div>
            `;
        }
    }

    confirmDelete(id) {
        if (this.isLoading) {
            toastr.warning('Ya hay una operación en proceso', 'Espere por favor');
            return;
        }

        if (this.element.hasChildren) {
            toastr.error('No se puede eliminar este elemento porque tiene elementos hijos. Elimine primero los elementos hijos.', 'No se puede eliminar');
            return;
        }

        if (confirm('¿Está seguro de que desea eliminar este elemento?\n\nEsta acción no se puede deshacer.')) {
            this.showLoading('Eliminando elemento...');
            
            // Crear formulario para envío POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${this.baseUrl}/panel/organizational/delete/${id}`;
            
            // Agregar token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = csrfToken.getAttribute('content');
                form.appendChild(csrfInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    }
}

// Función global para mantener compatibilidad con onclick
function confirmDelete(id) {
    if (window.organizationalEditModule) {
        window.organizationalEditModule.confirmDelete(id);
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // La configuración se pasará desde PHP mediante ClaudeConfig
    window.organizationalEditModule = new OrganizationalEditModule(window.ClaudeConfig || {});
});