/**
 * Módulo para manejo de logos de empresa con Dropzone.js
 * Versión refactorizada completamente desde cero
 */

(function() {
    'use strict';

    // Variables locales del módulo
    let dropzoneInstances = {};
    let isInitialized = false;

    // Verificar si ya está inicializado para evitar doble carga
    if (window.CompanyLogosModule) {
        console.log('Company Logos Module already loaded');
        return;
    }

    // Esperar a que el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        if (isInitialized) {
            console.log('Company logos already initialized');
            return;
        }

        // Desactivar auto-discovery globalmente
        if (typeof Dropzone !== 'undefined') {
            Dropzone.autoDiscover = false;
        }

        // Debug: verificar configuración
        console.log('=== DEBUGGING LOGO CONFIG ===');
        console.log('window.LogoConfig:', window.LogoConfig);
        console.log('Current location:', window.location);
        console.log('Current pathname:', window.location.pathname);

        // Inicializar después de un pequeño delay para asegurar que todo esté cargado
        setTimeout(function() {
            initializeCompanyLogos();
            isInitialized = true;
        }, 200);
    });

    /**
     * Inicializar todos los dropzones de logos
     */
    function initializeCompanyLogos() {
        console.log('=== Inicializando Company Logos ===');

        // Definir configuración de logos
        const logoConfigs = [
            {
                elementId: 'logo_empresa_dropzone',
                fieldName: 'logo_empresa',
                label: 'Logo Principal'
            },
            {
                elementId: 'logo_izquierdo_dropzone',
                fieldName: 'logo_izquierdo_reportes',
                label: 'Logo Izquierdo'
            },
            {
                elementId: 'logo_derecho_dropzone',
                fieldName: 'logo_derecho_reportes',
                label: 'Logo Derecho'
            }
        ];

        // Crear cada dropzone
        logoConfigs.forEach(function(config) {
            createDropzoneInstance(config);
        });
    }

    /**
     * Crear una instancia de Dropzone
     */
    function createDropzoneInstance(config) {
        const element = document.getElementById(config.elementId);

        if (!element) {
            console.warn(`Elemento no encontrado: ${config.elementId}`);
            return;
        }

        console.log(`Creando Dropzone para: ${config.label}`);

        try {
            // Limpiar cualquier instancia previa
            if (dropzoneInstances[config.elementId]) {
                dropzoneInstances[config.elementId].destroy();
                delete dropzoneInstances[config.elementId];
            }

            // Remover clase dropzone si existe para evitar auto-discovery
            element.classList.remove('dropzone');

            // Detectar path base dinámicamente para upload
            const path = window.location.pathname;
            const panelIndex = path.indexOf("/panel/");
            const basePath = panelIndex > 0 ? path.substring(0, panelIndex) : '';
            const uploadUrl = basePath + '/panel/company/upload-logo';
            console.log(`=== DYNAMIC URL DETECTION ===`);
            console.log(`Path actual: ${window.location.pathname}`);
            console.log(`Base path: ${basePath}`);
            console.log(`Upload URL: ${uploadUrl}`);

            // Crear nueva instancia
            const dropzone = new Dropzone(element, {
                url: uploadUrl,
                method: 'POST',
                paramName: 'logo',
                maxFiles: 1,
                maxFilesize: 2, // 2MB
                acceptedFiles: 'image/jpeg,image/jpg,image/png,image/svg+xml',
                addRemoveLinks: true,
                clickable: true,
                // Textos en español
                dictDefaultMessage: 'Arrastra el archivo aquí o haz clic para seleccionar',
                dictRemoveFile: 'Eliminar archivo',
                dictFileTooBig: 'El archivo es muy grande (máximo 2MB)',
                dictInvalidFileType: 'Tipo de archivo no válido. Solo JPG, PNG, SVG',
                dictMaxFilesExceeded: 'Solo se permite un archivo',

                // Configuración de preview
                previewTemplate: getPreviewTemplate(),

                // Eventos
                init: function() {
                    const dz = this;

                    // Enviar datos adicionales
                    this.on('sending', function(file, xhr, formData) {
                        // Configurar para enviar cookies de sesión
                        xhr.withCredentials = true;

                        // CSRF token
                        const csrfToken = document.querySelector('input[name="csrf_token"]');
                        if (csrfToken) {
                            formData.append('csrf_token', csrfToken.value);
                        }

                        // Nombre del campo
                        formData.append('field_name', config.fieldName);

                        console.log(`Subiendo archivo para ${config.fieldName}`);
                        console.log('CSRF Token:', csrfToken ? csrfToken.value : 'NOT FOUND');
                    });

                    // Manejar éxito
                    this.on('success', function(file, response) {
                        console.log('=== UPLOAD SUCCESS DEBUG ===');
                        console.log('Raw response:', response);
                        console.log('Response type:', typeof response);

                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            console.log('Parsed result:', result);

                            if (result.success) {
                                console.log('Upload successful, filename:', result.filename);

                                // Actualizar campo hidden
                                const hiddenField = document.getElementById(config.fieldName);
                                console.log('Hidden field found:', hiddenField);
                                if (hiddenField) {
                                    hiddenField.value = result.filename;
                                    console.log('Hidden field updated to:', result.filename);
                                }

                                // Mostrar preview
                                console.log('Calling showLogoPreview for:', config.fieldName, result.filename);
                                showLogoPreview(config.fieldName, result.filename);

                                // Mostrar mensaje de éxito
                                showNotification('success', 'Logo subido correctamente. Recuerda guardar la configuración.');
                            } else {
                                console.error('Server returned error:', result.message);
                                showNotification('error', result.message || 'Error al subir el logo');
                                dz.removeFile(file);
                            }
                        } catch (error) {
                            console.error('Error procesando respuesta:', error);
                            console.error('Response was:', response);
                            showNotification('error', 'Error procesando la respuesta del servidor');
                            dz.removeFile(file);
                        }
                    });

                    // Manejar errores
                    this.on('error', function(file, errorMessage) {
                        console.error('Error en upload:', errorMessage);
                        let message = 'Error al subir el archivo';

                        if (typeof errorMessage === 'object' && errorMessage.message) {
                            message = errorMessage.message;
                        } else if (typeof errorMessage === 'string') {
                            message = errorMessage;
                        }

                        showNotification('error', message);
                    });

                    // Limpiar al agregar nuevo archivo
                    this.on('addedfile', function(file) {
                        if (this.files.length > 1) {
                            this.removeFile(this.files[0]);
                        }

                        // Ocultar preview existente
                        hideLogoPreview(config.fieldName);
                    });

                    // Verificar si ya hay logo cargado
                    const existingLogo = document.getElementById(config.fieldName);
                    if (existingLogo && existingLogo.value) {
                        // Deshabilitar dropzone si ya hay logo
                        this.options.maxFiles = 0;
                    }
                }
            });

            // Guardar instancia
            dropzoneInstances[config.elementId] = dropzone;

            console.log(`Dropzone creado exitosamente para ${config.label}`);

        } catch (error) {
            console.error(`Error creando Dropzone para ${config.label}:`, error);
        }
    }

    /**
     * Template personalizado para preview
     */
    function getPreviewTemplate() {
        return `
            <div class="dz-preview dz-file-preview">
                <div class="dz-details">
                    <div class="dz-filename"><span data-dz-name></span></div>
                    <div class="dz-size" data-dz-size></div>
                </div>
                <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
                <div class="dz-success-mark"><span>✓</span></div>
                <div class="dz-error-mark"><span>✗</span></div>
                <div class="dz-error-message"><span data-dz-errormessage></span></div>
                <a class="dz-remove" href="javascript:undefined;" data-dz-remove>Eliminar</a>
            </div>
        `;
    }

    /**
     * Mostrar preview del logo
     */
    function showLogoPreview(fieldName, filename) {
        console.log('=== PREVIEW DEBUG ===');
        console.log('Field name:', fieldName);
        console.log('Filename:', filename);

        const previewId = `${fieldName}_preview`;
        console.log('Looking for preview element with ID:', previewId);

        const previewElement = document.getElementById(previewId);
        console.log('Preview element found:', previewElement);

        if (previewElement) {
            // Detectar path base dinámicamente para imágenes
            const path = window.location.pathname;
            const panelIndex = path.indexOf("/panel/");
            const basePath = panelIndex > 0 ? path.substring(0, panelIndex) : '';
            const imageUrl = basePath + '/images/logos/' + filename;
            console.log('Image URL:', imageUrl);

            const img = previewElement.querySelector('img');
            console.log('IMG element found:', img);

            if (img) {
                img.src = imageUrl;
                previewElement.style.display = 'block';
                console.log('Preview updated successfully');
            } else {
                console.error('No IMG element found in preview container');
            }
        } else {
            console.error('No preview element found with ID:', previewId);
            console.log('Available elements:', document.querySelectorAll('[id*="preview"]'));
        }
    }

    /**
     * Ocultar preview del logo
     */
    function hideLogoPreview(fieldName) {
        const previewId = `${fieldName}_preview`;
        const previewElement = document.getElementById(previewId);
        if (previewElement) {
            previewElement.style.display = 'none';
        }
    }

    /**
     * Eliminar logo (función global para ser llamada desde HTML)
     */
    function removeLogo(fieldName) {
        if (!confirm('¿Estás seguro de que quieres eliminar este logo?')) {
            return;
        }

        const hiddenField = document.getElementById(fieldName);
        const currentLogo = hiddenField ? hiddenField.value : '';

        if (!currentLogo) {
            return;
        }

        // Detectar path base dinámicamente para delete
        const path = window.location.pathname;
        const panelIndex = path.indexOf("/panel/");
        const basePath = panelIndex > 0 ? path.substring(0, panelIndex) : '';
        const deleteUrl = basePath + '/panel/company/delete-logo';
        console.log(`Dynamic delete URL:`, deleteUrl);

        // Hacer petición AJAX para eliminar
        fetch(deleteUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                csrf_token: document.querySelector('input[name="csrf_token"]').value,
                field_name: fieldName,
                filename: currentLogo
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Limpiar campo hidden
                hiddenField.value = '';

                // Ocultar preview
                hideLogoPreview(fieldName);

                // Reactivar dropzone correspondiente
                const dropzoneId = getDropzoneIdByFieldName(fieldName);
                if (dropzoneInstances[dropzoneId]) {
                    dropzoneInstances[dropzoneId].options.maxFiles = 1;
                }

                showNotification('success', 'Logo eliminado correctamente');
            } else {
                showNotification('error', result.message || 'Error al eliminar el logo');
            }
        })
        .catch(error => {
            console.error('Error eliminando logo:', error);
            showNotification('error', 'Error de conexión al servidor');
        });
    }

    /**
     * Obtener ID de dropzone por nombre de campo
     */
    function getDropzoneIdByFieldName(fieldName) {
        const mapping = {
            'logo_empresa': 'logo_empresa_dropzone',
            'logo_izquierdo_reportes': 'logo_izquierdo_dropzone',
            'logo_derecho_reportes': 'logo_derecho_dropzone'
        };
        return mapping[fieldName] || null;
    }

    /**
     * Mostrar notificación
     */
    function showNotification(type, message) {
        // Usar toastr si está disponible
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            // Fallback a alert nativo
            alert(message);
        }
    }

    // Hacer funciones disponibles globalmente
    window.removeLogo = removeLogo;

    // Exponer el módulo para evitar doble carga
    window.CompanyLogosModule = {
        initialized: true,
        instances: dropzoneInstances,
        removeLogo: removeLogo
    };

})(); // Cerrar IIFE