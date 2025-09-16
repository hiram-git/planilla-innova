/**
 * Módulo Organizacional - Vista de Creación
 * Gestión del formulario de creación de elementos organizacionales
 */

class OrganizationalCreateModule {
    constructor(config = {}) {
        this.baseUrl = config.baseUrl || '';
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
        // Vista previa dinámica
        const descripcionInput = document.getElementById('descripcion');
        const parentSelect = document.getElementById('id_padre');

        if (descripcionInput) {
            descripcionInput.addEventListener('input', () => this.updatePreview());
        }

        if (parentSelect) {
            parentSelect.addEventListener('change', () => this.updatePreview());
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
                this.showLoading('Creando elemento organizacional...');
            });
        }
    }

    updatePreview() {
        const descripcion = document.getElementById('descripcion')?.value || '';
        const parentId = document.getElementById('id_padre')?.value || '';
        
        if (!descripcion) {
            this.removePreview();
            return;
        }

        // Simular path basado en el padre seleccionado
        let previewPath = '';
        if (parentId) {
            const parentOption = document.querySelector(`#id_padre option[value="${parentId}"]`);
            if (parentOption) {
                const parentText = parentOption.text.replace(/^└─\s*/, '').trim();
                previewPath = parentText + ' → ';
            }
        }
        previewPath += descripcion;

        // Remover vista previa anterior
        this.removePreview();

        // Mostrar vista previa
        const previewContainer = document.getElementById('preview-structure');
        if (previewContainer) {
            const previewElement = document.createElement('div');
            previewElement.className = 'alert alert-success mt-2';
            previewElement.id = 'new-element-preview';
            previewElement.innerHTML = `
                <strong>Nuevo elemento:</strong><br>
                <i class="fas fa-plus-circle mr-1"></i> ${previewPath}
            `;
            previewContainer.appendChild(previewElement);
        }
    }

    removePreview() {
        const existingPreview = document.getElementById('new-element-preview');
        if (existingPreview) {
            existingPreview.remove();
        }
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Configurar la URL base desde el helper de PHP
    const config = {
        baseUrl: window.appConfig?.baseUrl || ''
    };
    
    window.organizationalCreateModule = new OrganizationalCreateModule(config);
});