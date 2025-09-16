/**
 * Módulo Organizacional - Vista Principal
 * Gestión del árbol jerárquico y funcionalidades de organigrama
 */

class OrganizationalModule {
    constructor(config = {}) {
        this.baseUrl = config.baseUrl || '';
        this.searchTimeout = null;
        this.isLoading = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.createLoadingOverlay();
    }

    bindEvents() {
        // Búsqueda en tiempo real
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => this.searchElements(), 300);
            });
        }
        
        // Bloquear formularios durante envío
        this.blockFormsOnSubmit();
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

    // Bloquear formularios durante envío
    blockFormsOnSubmit() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (this.isLoading) {
                    e.preventDefault();
                    return false;
                }
                
                // Mostrar loading después de un pequeño delay para formularios
                setTimeout(() => {
                    this.showLoading('Guardando cambios...');
                }, 100);
            });
        });
    }

    // Funciones de navegación del árbol
    toggleNode(icon) {
        const li = icon.closest('li');
        if (li) {
            li.classList.toggle('collapsed');
        }
    }

    expandAll() {
        document.querySelectorAll('.tree-item').forEach(item => {
            item.classList.remove('collapsed');
        });
    }

    collapseAll() {
        document.querySelectorAll('.tree-item').forEach(item => {
            if (item.querySelector('ul')) {
                item.classList.add('collapsed');
            }
        });
    }

    // Función de eliminación
    deleteElement(id) {
        // Prevenir múltiples eliminaciones
        if (this.isLoading) {
            toastr.warning('Ya hay una operación en proceso', 'Espere por favor');
            return;
        }
        
        console.log('Eliminando elemento ID:', id);
        console.log('Base URL configurada:', this.baseUrl);
        
        const deleteUrl = `${this.baseUrl}/panel/organizational/delete/${id}`;
        console.log('URL de eliminación:', deleteUrl);
        
        if (confirm('¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.')) {
            this.showLoading('Eliminando elemento...');
            
            fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Respuesta del servidor:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data);
                this.hideLoading();
                
                if (data.success) {
                    toastr.success(
                        data.message || 'Elemento eliminado exitosamente',
                        'Eliminación Exitosa'
                    );
                    // Pequeño delay para que se vea la notificación antes de recargar
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(
                        data.error || 'Error desconocido',
                        'Error al Eliminar'
                    );
                }
            })
            .catch(error => {
                console.error('Error en la eliminación:', error);
                this.hideLoading();
                toastr.error(
                    'Error de conexión: ' + error.message,
                    'Error de Conexión'
                );
            });
        }
    }

    // Función de búsqueda
    searchElements() {
        if (this.isLoading) return;
        
        const query = document.getElementById('searchInput').value.trim();
        const resultsDiv = document.getElementById('searchResults');
        
        if (!resultsDiv) return;

        if (query.length < 2) {
            resultsDiv.innerHTML = '<small class="text-muted">Escriba al menos 2 caracteres para buscar</small>';
            return;
        }

        resultsDiv.innerHTML = '<small class="text-info"><i class="fas fa-spinner fa-spin"></i> Buscando...</small>';

        fetch(`${this.baseUrl}/panel/organizational/search?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.results.length > 0) {
                    let html = '';
                    data.results.forEach(result => {
                        html += `<div class="search-result" onclick="organizationalModule.highlightElement(${result.id})">
                            <strong>${result.descripcion}</strong><br>
                            <small class="text-muted">${result.path}</small>
                        </div>`;
                    });
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = '<small class="text-muted">No se encontraron resultados</small>';
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = '<small class="text-danger">Error en la búsqueda</small>';
            });
    }

    // Resaltar elemento encontrado
    highlightElement(id) {
        const element = document.querySelector(`[data-id="${id}"]`);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const node = element.querySelector('.tree-node');
            if (node) {
                node.style.backgroundColor = '#fff3cd';
                setTimeout(() => {
                    node.style.backgroundColor = '';
                }, 2000);
            }
        }
    }

    // Exportar organigrama
    exportChart() {
        if (this.isLoading) {
            toastr.warning('Ya hay una operación en proceso', 'Espere por favor');
            return;
        }
        
        const format = prompt('Seleccione formato de exportación:\n- json\n- csv', 'json');
        if (format && ['json', 'csv'].includes(format.toLowerCase())) {
            this.showLoading('Generando archivo de exportación...');
            
            // Para exportación, solo mostrar loading por un momento
            setTimeout(() => {
                window.open(`${this.baseUrl}/panel/organizational/export/${format}`, '_blank');
                this.hideLoading();
                toastr.success('Exportación iniciada', 'Éxito');
            }, 500);
        }
    }

    // Imprimir organigrama
    printChart() {
        window.print();
    }
}

// Funciones globales para mantener compatibilidad con onclick
function toggleNode(icon) {
    if (window.organizationalModule) {
        window.organizationalModule.toggleNode(icon);
    }
}

function expandAll() {
    if (window.organizationalModule) {
        window.organizationalModule.expandAll();
    }
}

function collapseAll() {
    if (window.organizationalModule) {
        window.organizationalModule.collapseAll();
    }
}

function deleteElement(id) {
    if (window.organizationalModule) {
        window.organizationalModule.deleteElement(id);
    }
}

function searchElements() {
    if (window.organizationalModule) {
        window.organizationalModule.searchElements();
    }
}

function exportChart() {
    if (window.organizationalModule) {
        window.organizationalModule.exportChart();
    }
}

function printChart() {
    if (window.organizationalModule) {
        window.organizationalModule.printChart();
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Configurar la URL base desde el helper de PHP
    console.log('window.appConfig:', window.appConfig);
    
    const config = {
        baseUrl: window.appConfig?.baseUrl || ''
    };
    
    console.log('Configuración para OrganizationalModule:', config);
    
    window.organizationalModule = new OrganizationalModule(config);
});