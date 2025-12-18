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
        this.loadDepartmentData();
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
            Swal.fire({
                title: 'No se puede eliminar',
                html: '<p>No se puede eliminar este elemento porque tiene elementos hijos.</p><p class="text-muted"><small>Elimine primero los elementos hijos.</small></p>',
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: '<i class="fas fa-times mr-2"></i>Entendido'
            });
            return;
        }

        // Usar SweetAlert2 para confirmación de eliminación
        Swal.fire({
            title: '¿Eliminar elemento?',
            html: '<p>¿Está seguro de que desea eliminar este elemento?</p><p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash mr-2"></i>Sí, eliminar',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
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
        });
    }

    /**
     * Cargar datos del departamento (cargos, funciones y empleados)
     */
    loadDepartmentData() {
        const departamentoId = this.element.id || 0;
        console.log("[ORG] Loading department data for ID:", departamentoId);

        if (departamentoId > 0) {
            this.loadAllCargosForMultiselect();
            this.loadCargos(departamentoId);
            this.loadAllFuncionesForMultiselect();
            this.loadFunciones(departamentoId);
            this.loadEmployees(departamentoId);
            this.bindSaveButton();
        }
    }

    /**
     * Cargar cargos del departamento
     */
    loadCargos(departamentoId) {
        $.ajax({
            url: `${this.baseUrl}/panel/organizational/getCargosByDepartamento/${departamentoId}`,
            method: "GET",
            dataType: "json",
            success: (response) => {
                console.log("[ORG] Cargos response:", response);

                const container = $('#cargos-list-container');
                const countBadge = $('#cargos-count');

                if (response.success && response.cargos && response.cargos.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-striped table-hover">';
                    html += '<thead><tr>';
                    html += '<th style="width: 20%">Código</th>';
                    html += '<th style="width: 50%">Nombre</th>';
                    html += '<th style="width: 15%" class="text-center">Estado</th>';
                    html += '<th style="width: 15%" class="text-center">Acciones</th>';
                    html += '</tr></thead><tbody>';

                    response.cargos.forEach((cargo) => {
                        const statusBadge = cargo.activo == 1
                            ? '<span class="badge badge-success">Activo</span>'
                            : '<span class="badge badge-secondary">Inactivo</span>';

                        html += '<tr>';
                        html += `<td><strong>${cargo.codigo}</strong></td>`;
                        html += `<td>${cargo.nombre}</td>`;
                        html += `<td class="text-center">${statusBadge}</td>`;
                        html += '<td class="text-center">';
                        html += `<button type="button" class="btn btn-sm btn-info btn-manage-funciones mr-1" data-cargo-id="${cargo.id}" data-cargo-nombre="${cargo.nombre}" title="Gestionar funciones">`;
                        html += '<i class="fas fa-tasks"></i>';
                        html += '</button>';
                        html += `<a href="${this.baseUrl}/panel/cargos/edit/${cargo.id}" class="btn btn-sm btn-primary" title="Editar cargo">`;
                        html += '<i class="fas fa-edit"></i>';
                        html += '</a>';
                        html += '</td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div>';
                    container.html(html);

                    const cargoText = response.cargos.length === 1 ? 'cargo' : 'cargos';
                    countBadge.text(`${response.cargos.length} ${cargoText}`);

                    // Bind event listeners para botones de gestionar funciones
                    $('.btn-manage-funciones').off('click').on('click', (e) => {
                        const cargoId = $(e.currentTarget).data('cargo-id');
                        const cargoNombre = $(e.currentTarget).data('cargo-nombre');
                        this.openFuncionesModal(cargoId, cargoNombre);
                    });
                } else {
                    container.html('<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>No hay cargos asociados a este departamento.</div>');
                    countBadge.text('0 cargos').removeClass('badge-primary').addClass('badge-secondary');
                }
            },
            error: (xhr, status, error) => {
                console.error("[ORG] Error loading cargos:", error, xhr, status);
                $('#cargos-list-container').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Error al cargar los cargos.</div>');
                $('#cargos-count').text('Error').removeClass('badge-primary').addClass('badge-danger');
            }
        });
    }

    /**
     * Cargar empleados del departamento
     */
    loadEmployees(departamentoId) {
        const container = $('#employees-list-container');
        const countBadge = $('#employees-count');

        // Mostrar información con enlace al módulo de empleados
        container.html(
            '<div class="callout callout-info">' +
            '<h6><i class="fas fa-info-circle mr-2"></i>Información de Empleados</h6>' +
            '<p class="mb-0">Para ver los empleados de este departamento, visite el módulo de ' +
            `<a href="${this.baseUrl}/panel/employees">Gestión de Empleados</a> y filtre por departamento.</p>` +
            '</div>'
        );

        countBadge.text('Ver módulo').removeClass('badge-info').addClass('badge-secondary');
    }

    /**
     * Cargar todos los cargos activos para el multiselect
     */
    loadAllCargosForMultiselect() {
        $.ajax({
            url: `${this.baseUrl}/panel/organizational/all-cargos`,
            method: "GET",
            dataType: "json",
            success: (response) => {
                console.log("[ORG] All cargos response:", response);

                const select = $('#cargos-multiselect');

                if (response.success && response.cargos && response.cargos.length > 0) {
                    // Limpiar el select
                    select.empty();

                    // Agregar opciones
                    response.cargos.forEach((cargo) => {
                        const selected = cargo.departamento_id == this.element.id ? 'selected' : '';
                        select.append(`<option value="${cargo.id}" ${selected}>${cargo.codigo} - ${cargo.nombre}</option>`);
                    });

                    // Reinicializar Select2
                    if (typeof $.fn.select2 !== 'undefined') {
                        select.select2({
                            theme: 'bootstrap4',
                            placeholder: 'Seleccione los cargos',
                            allowClear: true
                        });
                    }
                } else {
                    select.html('<option value="">No hay cargos disponibles</option>');
                    toastr.warning('No se encontraron cargos activos', 'Aviso');
                }
            },
            error: (xhr, status, error) => {
                console.error("[ORG] Error loading all cargos:", error, xhr, status);
                $('#cargos-multiselect').html('<option value="">Error al cargar cargos</option>');
                toastr.error('Error al cargar la lista de cargos', 'Error');
            }
        });
    }

    /**
     * Vincular evento al botón de guardar asociaciones
     */
    bindSaveButton() {
        const btnSave = $('#btn-save-cargos');
        const departamentoId = this.element.id;

        btnSave.off('click').on('click', () => {
            const selectedCargos = $('#cargos-multiselect').val() || [];
            console.log('[ORG] Selected cargos:', selectedCargos);

            // Usar SweetAlert2 en lugar de confirm nativo
            Swal.fire({
                title: '¿Confirmar asociación?',
                html: `<p>¿Desea asociar <strong>${selectedCargos.length} cargo(s)</strong> a este departamento?</p>
                       <p class="text-muted"><small><i class="fas fa-exclamation-triangle mr-1"></i>Nota: Los cargos que no seleccione serán desasociados.</small></p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check mr-2"></i>Sí, asociar',
                cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
                focusConfirm: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.saveCargoAssociations(departamentoId, selectedCargos);
                }
            });
        });
    }

    /**
     * Guardar asociaciones de cargos
     */
    saveCargoAssociations(departamentoId, cargoIds) {
        // Mostrar loading
        const btnSave = $('#btn-save-cargos');
        const originalHtml = btnSave.html();
        btnSave.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...');

        $.ajax({
            url: `${this.baseUrl}/panel/organizational/update-cargos/${departamentoId}`,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                cargo_ids: cargoIds
            }),
            dataType: "json",
            success: (response) => {
                console.log("[ORG] Save response:", response);

                if (response.success) {
                    toastr.success(response.message || 'Asociaciones guardadas exitosamente', 'Éxito');

                    // Recargar la lista de cargos asociados
                    this.loadCargos(departamentoId);
                } else {
                    toastr.error(response.error || 'Error al guardar asociaciones', 'Error');
                }
            },
            error: (xhr, status, error) => {
                console.error("[ORG] Error saving associations:", error, xhr, status);
                toastr.error('Error al guardar las asociaciones', 'Error');
            },
            complete: () => {
                // Restaurar botón
                btnSave.prop('disabled', false).html(originalHtml);
            }
        });
    }

    /**
     * Cargar todas las funciones activas para el multiselect
     */
    loadAllFuncionesForMultiselect() {
        $.ajax({
            url: `${this.baseUrl}/panel/organizational/all-funciones`,
            method: "GET",
            dataType: "json",
            success: (response) => {
                console.log("[ORG] All funciones response:", response);

                const select = $('#funciones-multiselect');

                if (response.success && response.funciones && response.funciones.length > 0) {
                    // Limpiar el select
                    select.empty();

                    // Agregar opciones
                    response.funciones.forEach((funcion) => {
                        select.append(`<option value="${funcion.id}">${funcion.codigo} - ${funcion.nombre}</option>`);
                    });

                    // Reinicializar Select2
                    if (typeof $.fn.select2 !== 'undefined') {
                        select.select2({
                            theme: 'bootstrap4',
                            placeholder: 'Seleccione las funciones',
                            allowClear: true
                        });
                    }
                } else {
                    select.html('<option value="">No hay funciones disponibles</option>');
                    toastr.warning('No se encontraron funciones activas', 'Aviso');
                }
            },
            error: (xhr, status, error) => {
                console.error("[ORG] Error loading all funciones:", error, xhr, status);
                $('#funciones-multiselect').html('<option value="">Error al cargar funciones</option>');
                toastr.error('Error al cargar la lista de funciones', 'Error');
            }
        });
    }

    /**
     * Cargar funciones del departamento
     */
    loadFunciones(departamentoId) {
        $.ajax({
            url: `${this.baseUrl}/panel/organizational/getFuncionesByDepartamento/${departamentoId}`,
            method: "GET",
            dataType: "json",
            success: (response) => {
                console.log("[ORG] Funciones response:", response);

                const container = $('#funciones-list-container');
                const countBadge = $('#funciones-count');

                if (response.success && response.funciones && response.funciones.length > 0) {
                    let html = '<div class="table-responsive"><table class="table table-striped table-hover">';
                    html += '<thead><tr>';
                    html += '<th style="width: 20%">Código</th>';
                    html += '<th style="width: 50%">Nombre</th>';
                    html += '<th style="width: 15%" class="text-center">Estado</th>';
                    html += '<th style="width: 15%" class="text-center">Acciones</th>';
                    html += '</tr></thead><tbody>';

                    response.funciones.forEach((funcion) => {
                        const statusBadge = funcion.activo == 1
                            ? '<span class="badge badge-success">Activo</span>'
                            : '<span class="badge badge-secondary">Inactivo</span>';

                        html += '<tr>';
                        html += `<td><strong>${funcion.codigo}</strong></td>`;
                        html += `<td>${funcion.nombre}</td>`;
                        html += `<td class="text-center">${statusBadge}</td>`;
                        html += '<td class="text-center">';
                        html += `<a href="${this.baseUrl}/panel/funciones/edit/${funcion.id}" class="btn btn-sm btn-primary" title="Editar función">`;
                        html += '<i class="fas fa-edit"></i>';
                        html += '</a>';
                        html += '</td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div>';
                    container.html(html);

                    const funcionText = response.funciones.length === 1 ? 'función' : 'funciones';
                    countBadge.text(`${response.funciones.length} ${funcionText}`);
                } else {
                    container.html('<div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i>No hay funciones asociadas a este departamento.</div>');
                    countBadge.text('0 funciones').removeClass('badge-primary').addClass('badge-secondary');
                }
            },
            error: (xhr, status, error) => {
                console.error("[ORG] Error loading funciones:", error, xhr, status);
                $('#funciones-list-container').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Error al cargar las funciones.</div>');
                $('#funciones-count').text('Error').removeClass('badge-primary').addClass('badge-danger');
            }
        });
    }

    /**
     * Abrir modal de gestión de funciones para un cargo específico
     */
    openFuncionesModal(cargoId, cargoNombre) {
        console.log('[ORG] Opening funciones modal for cargo:', cargoId, cargoNombre);

        // Cargar todas las funciones
        $.ajax({
            url: `${this.baseUrl}/panel/organizational/all-funciones`,
            method: "GET",
            dataType: "json",
            success: (response) => {
                if (response.success && response.funciones && response.funciones.length > 0) {
                    // Crear opciones del select
                    let optionsHtml = '';
                    response.funciones.forEach((funcion) => {
                        const selected = funcion.cargo_id == cargoId ? 'selected' : '';
                        optionsHtml += `<option value="${funcion.id}" ${selected}>${funcion.codigo} - ${funcion.nombre}</option>`;
                    });

                    // Mostrar modal con SweetAlert2
                    Swal.fire({
                        title: `Gestionar Funciones`,
                        html: `
                            <div class="text-left">
                                <h5 class="mb-3"><i class="fas fa-user-tie mr-2"></i>${cargoNombre}</h5>
                                <div class="form-group">
                                    <label for="swal-funciones-select"><i class="fas fa-tasks mr-1"></i>Funciones asignadas al cargo:</label>
                                    <select id="swal-funciones-select" class="form-control" multiple style="width: 100%; height: 200px;">
                                        ${optionsHtml}
                                    </select>
                                    <small class="form-text text-muted mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>Use Ctrl+Click (Cmd+Click en Mac) para seleccionar múltiples funciones
                                    </small>
                                </div>
                            </div>
                        `,
                        icon: null,
                        width: '600px',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-save mr-2"></i>Guardar Funciones',
                        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
                        focusConfirm: true,
                        didOpen: () => {
                            // Inicializar Select2 después de que el modal se abra
                            const select = $('#swal-funciones-select');
                            if (typeof $.fn.select2 !== 'undefined') {
                                select.select2({
                                    theme: 'bootstrap4',
                                    placeholder: 'Seleccione las funciones',
                                    allowClear: true,
                                    width: '100%',
                                    dropdownParent: $('.swal2-popup')
                                });
                            }
                        },
                        preConfirm: () => {
                            const selected = $('#swal-funciones-select').val() || [];
                            return selected;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.saveFuncionAssociations(cargoId, result.value);
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Sin funciones disponibles',
                        text: 'No hay funciones activas disponibles para asignar',
                        icon: 'info',
                        confirmButtonColor: '#6c757d'
                    });
                }
            },
            error: (xhr, status, error) => {
                console.error("[ORG] Error loading funciones for modal:", error);
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudieron cargar las funciones',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    }

    /**
     * Guardar asociaciones de funciones a un cargo
     */
    saveFuncionAssociations(cargoId, funcionIds) {
        const btnSave = $('#btn-save-funciones');
        const originalHtml = btnSave.html();
        btnSave.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...');

        $.ajax({
            url: `${this.baseUrl}/panel/organizational/update-funciones/${cargoId}`,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                funcion_ids: funcionIds
            }),
            dataType: "json",
            success: (response) => {
                console.log("[ORG] Save funciones response:", response);

                if (response.success) {
                    toastr.success(response.message || 'Funciones asociadas exitosamente', 'Éxito');

                    // Recargar la lista de funciones del departamento
                    this.loadFunciones(this.element.id);
                } else {
                    toastr.error(response.error || 'Error al guardar funciones', 'Error');
                }
            },
            error: (xhr, status, error) => {
                console.error("[ORG] Error saving funciones:", error, xhr, status);
                toastr.error('Error al guardar las funciones', 'Error');
            },
            complete: () => {
                btnSave.prop('disabled', false).html(originalHtml);
            }
        });
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
    // La configuración se pasará desde PHP mediante appConfig
    const config = window.appConfig || window.ClaudeConfig || {};
    console.log('[ORG] Inicializando módulo con config:', config);
    window.organizationalEditModule = new OrganizationalEditModule(config);
});