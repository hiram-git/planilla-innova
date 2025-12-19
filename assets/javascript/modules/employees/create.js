/**
 * Módulo JavaScript para creación de empleados
 * Maneja la sincronización con navbar, validaciones y UI
 */

$(document).ready(function() {
    console.log('Employees Create Module Loading...');

    // Inicializar Select2 para el campo de tipo de planilla
    $('#tipo_planilla').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccione uno o más tipos de planilla',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            },
            searching: function() {
                return "Buscando...";
            }
        }
    });

    // Obtener tipo de institución de la empresa
    var companyType = window.APP_CONFIG?.company?.tipo_institucion || 'privada';

    // Mostrar/ocultar campos según tipo de institución
    function toggleFieldsByCompanyType() {
        if (companyType === 'privada') {
            // Empresa privada: mostrar cargos, funciones, partidas y sueldo individual (SIN posición)
            $('#private-company-fields').show();
            $('#salary-section').show();
            $('#salary-tarifa-section').show();
            $('#public-institution-fields').hide();

            // Hacer obligatorios los campos de empresa privada (excepto funcion_id que es opcional)
            $('#cargo_id, #partida_id, #sueldo_individual').prop('required', true);
            $('#funcion_id').prop('required', false);
            $('#position').prop('required', false);

        } else {
            // Institución pública: mostrar solo posición
            $('#public-institution-fields').show();
            $('#private-company-fields').hide();
            $('#salary-section').hide();
            $('#salary-tarifa-section').hide();

            // Hacer obligatorio solo el campo de posición
            $('#position').prop('required', true);
            $('#cargo_id, #partida_id, #sueldo_individual').prop('required', false);
            $('#funcion_id').prop('required', false);
        }
    }
    
    // Ejecutar al cargar la página
    toggleFieldsByCompanyType();

    // ============================================================================
    // SISTEMA JERÁRQUICO DE DEPARTAMENTOS
    // ============================================================================

    // Inicializar sistema de departamentos jerárquicos solo para empresas privadas
    var departamentosJerarquicos = null;

    if (companyType === 'privada') {
        // Verificar si el módulo DepartamentosJerarquicos está disponible
        if (typeof window.DepartamentosJerarquicos !== 'undefined') {
            console.log('[Create] Inicializando sistema jerárquico de departamentos...');

            // Obtener departamento seleccionado previo (para validación de errores)
            var selectedDepartamentoId = $('#departamento_id_private').val() || null;

            departamentosJerarquicos = new window.DepartamentosJerarquicos('departamentos-jerarquicos-container', {
                fieldName: 'departamento_id',
                selectedDepartamentoId: selectedDepartamentoId,
                requiredField: true,
                showLabels: true,
                labelText: 'Departamento',
                onSelectionChange: function(data) {
                    console.log('[Create] Departamento seleccionado:', data);

                    // Cuando cambia el departamento, cargar cargos correspondientes
                    if (data.value) {
                        loadCargosByDepartamento(data.value);
                    } else {
                        // Resetear cargos y funciones
                        $('#cargo_id').html('<option value="">Primero seleccione un departamento...</option>').prop('disabled', true);
                        $('#funcion_id').html('<option value="">Primero seleccione un cargo...</option>').prop('disabled', true);
                    }
                }
            });

            console.log('[Create] Sistema jerárquico inicializado correctamente');
        } else {
            console.warn('[Create] Módulo DepartamentosJerarquicos no disponible, usando sistema legacy');
        }
    }

    // ============================================================================
    // FUNCIONES AUXILIARES - Cargos y Funciones
    // ============================================================================

    /**
     * Cargar cargos según departamento seleccionado
     */
    function loadCargosByDepartamento(departamentoId, selectedCargoId = null) {
        const $cargoSelect = $("#cargo_id");
        const $funcionSelect = $("#funcion_id");

        if (!departamentoId) {
            // Resetear ambos selects
            $cargoSelect.html('<option value="">Primero seleccione un departamento...</option>').prop('disabled', true);
            $funcionSelect.html('<option value="">Primero seleccione un cargo...</option>').prop('disabled', true);
            return;
        }

        // Mostrar loading en cargo
        $cargoSelect.html('<option value="">Cargando cargos...</option>').prop('disabled', true);
        $funcionSelect.html('<option value="">Primero seleccione un cargo...</option>').prop('disabled', true);

        // Construir URL usando APP_CONFIG si está disponible
        const baseUrl = window.APP_CONFIG?.urls?.organizationalApi || '/panel/organizational';
        const url = `${baseUrl}/getCargosByDepartamento/${departamentoId}`;

        // AJAX request
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.cargos) {
                    let options = '<option value="">Seleccionar cargo...</option>';

                    response.cargos.forEach(function(cargo) {
                        const selected = (selectedCargoId && cargo.id == selectedCargoId) ? ' selected' : '';
                        options += `<option value="${cargo.id}"${selected}>${cargo.codigo} - ${cargo.nombre}</option>`;
                    });

                    $cargoSelect.html(options).prop('disabled', false);

                    // Si hay un cargo seleccionado, cargar sus funciones
                    if (selectedCargoId) {
                        loadFuncionesByCargo(selectedCargoId);
                    }
                } else {
                    $cargoSelect.html('<option value="">No hay cargos en este departamento</option>').prop('disabled', true);
                    toastr.warning('No se encontraron cargos para este departamento', 'Advertencia');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading cargos:', error);
                $cargoSelect.html('<option value="">Error al cargar cargos</option>').prop('disabled', true);
                toastr.error('Error al cargar los cargos del departamento', 'Error');
            }
        });
    }

    /**
     * Cargar funciones según cargo seleccionado (incluye genéricas)
     */
    function loadFuncionesByCargo(cargoId, selectedFuncionId = null) {
        const $funcionSelect = $("#funcion_id");

        if (!cargoId) {
            $funcionSelect.html('<option value="">Primero seleccione un cargo...</option>').prop('disabled', true);
            return;
        }

        // Mostrar loading
        $funcionSelect.html('<option value="">Cargando funciones...</option>').prop('disabled', true);

        // Construir URL usando APP_CONFIG si está disponible
        const baseUrl = window.APP_CONFIG?.urls?.organizationalApi || '/panel/organizational';
        const url = `${baseUrl}/getFuncionesByCargo/${cargoId}`;

        // AJAX request
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.funciones) {
                    let options = '<option value="">Seleccionar función (opcional)...</option>';

                    response.funciones.forEach(function(funcion) {
                        const selected = (selectedFuncionId && funcion.id == selectedFuncionId) ? ' selected' : '';
                        const label = funcion.cargo_id === null ? ' (Genérica)' : '';
                        options += `<option value="${funcion.id}"${selected}>${funcion.codigo} - ${funcion.nombre}${label}</option>`;
                    });

                    $funcionSelect.html(options).prop('disabled', false);
                } else {
                    $funcionSelect.html('<option value="">No hay funciones disponibles</option>').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading funciones:', error);
                $funcionSelect.html('<option value="">Error al cargar funciones</option>').prop('disabled', true);
                toastr.error('Error al cargar las funciones del cargo', 'Error');
            }
        });
    }

    // ============================================================================
    // EVENTOS - Selects Dependientes
    // ============================================================================

    /**
     * Evento: Cambio de cargo
     */
    $("#cargo_id").on("change", function() {
        const cargoId = $(this).val();
        loadFuncionesByCargo(cargoId);
    });

    // ============================================================================
    // INICIALIZACIÓN - Cargar valores pre-seleccionados
    // ============================================================================

    /**
     * Cargar cargos y funciones si hay valores pre-seleccionados (para validación de errores)
     */
    const departamentoIdSelected = $('#departamento_id_private').val();

    // Obtener valores de old_data desde PHP (si están disponibles en window)
    const cargoIdSelected = window.APP_CONFIG?.old_data?.cargo_id || null;
    const funcionIdSelected = window.APP_CONFIG?.old_data?.funcion_id || null;

    if (departamentoIdSelected) {
        loadCargosByDepartamento(departamentoIdSelected, cargoIdSelected);
    }

    // Si hay cargo seleccionado pero no departamento (caso de error), cargar funciones genéricas
    if (cargoIdSelected && !departamentoIdSelected) {
        loadFuncionesByCargo(cargoIdSelected, funcionIdSelected);
    }

    // Sincronizar tipo de planilla desde navbar
    syncPayrollTypeFromNavbar();
    
    // Escuchar cambios en el tipo de planilla del navbar
    window.addEventListener('payrollTypeChanged', function(event) {
        const payrollType = event.detail;
        if (payrollType && payrollType.id) {
            $('#tipo_planilla').val(payrollType.id).trigger('change'); // trigger change para Select2
            showSyncNotification(payrollType.name);
            console.log('Tipo de planilla sincronizado desde navbar:', payrollType.name);
        }
    });

    // Función para sincronizar el tipo de planilla desde el navbar
    function syncPayrollTypeFromNavbar() {
        // Verificar si existe la función global para obtener el tipo seleccionado
        if (typeof window.getSelectedPayrollType === 'function') {
            const selectedPayrollType = window.getSelectedPayrollType();
            if (selectedPayrollType && selectedPayrollType.id) {
                $('#tipo_planilla').val(selectedPayrollType.id).trigger('change'); // trigger change para Select2
                showSyncNotification(selectedPayrollType.name);
                console.log('Tipo de planilla preseleccionado:', selectedPayrollType.name);
            }
        } else {
            // Si no está disponible, intentar después de un breve delay
            setTimeout(function() {
                if (typeof window.getSelectedPayrollType === 'function') {
                    const selectedPayrollType = window.getSelectedPayrollType();
                    if (selectedPayrollType && selectedPayrollType.id) {
                        $('#tipo_planilla').val(selectedPayrollType.id).trigger('change'); // trigger change para Select2
                        showSyncNotification(selectedPayrollType.name);
                        console.log('Tipo de planilla preseleccionado (delayed):', selectedPayrollType.name);
                    }
                }
            }, 500);
        }
    }
    
    // Función para mostrar notificación de sincronización
    function showSyncNotification(payrollTypeName) {
        // Remover notificaciones anteriores
        $('.payroll-sync-notification').remove();
        
        const notification = $('<div class="alert alert-info alert-dismissible fade show mt-2 payroll-sync-notification" role="alert">' +
            '<i class="fas fa-sync-alt"></i> ' +
            '<strong>Sincronizado:</strong> Tipo de planilla establecido como "' + payrollTypeName + '"' +
            '<button type="button" class="close" data-dismiss="alert">' +
                '<span aria-hidden="true">&times;</span>' +
            '</button>' +
        '</div>');
        
        // Insertar la notificación después del campo de tipo de planilla
        $('#tipo_planilla').parent().after(notification);
        
        // Auto-eliminar después de 3 segundos
        setTimeout(function() {
            notification.fadeOut();
        }, 3000);
    }
    
    // Validación del formulario
    $('#position').change(function() {
        var positionId = $(this).val();
        if (positionId) {
            // Aquí podrías hacer una llamada AJAX para obtener detalles de la posición
            console.log('Posición seleccionada: ' + positionId);
        }
    });
    
    // Manejo de forma de pago y campos bancarios
    $('#forma_pago').change(function() {
        var formaPago = $(this).val();
        var bankDetails = $('#bank-details');
        var bancoField = $('#banco');
        var numeroCuentaField = $('#numero_cuenta');
        var tipoCuentaField = $('#tipo_cuenta');

        if (formaPago === 'CHEQUE' || formaPago === 'ACH') {
            // Mostrar campos bancarios y hacerlos obligatorios
            bankDetails.show();
            bancoField.prop('required', true);
            numeroCuentaField.prop('required', true);
            tipoCuentaField.prop('required', true);
        } else {
            // Ocultar campos bancarios y quitarles la obligatoriedad
            bankDetails.hide();
            bancoField.prop('required', false).val('');
            numeroCuentaField.prop('required', false).val('');
            tipoCuentaField.prop('required', false).val('');
        }
    });

    // Manejo de tipo de contrato y campos relacionados
    $('#tipo_contrato').change(function() {
        var tipoContrato = $(this).val();
        var contractNumberSection = $('#contract-number-section');
        var contractDatesSection = $('#contract-dates-section');
        var numeroContratoField = $('#numero_contrato');
        var fechaInicioField = $('#fecha_inicio_contrato');
        var fechaVencimientoField = $('#fecha_vencimiento_contrato');

        if (tipoContrato === 'INDEFINIDO') {
            // Ocultar todos los campos de contrato específico y quitar obligatoriedad
            contractNumberSection.hide();
            contractDatesSection.hide();
            numeroContratoField.prop('required', false);
            fechaInicioField.prop('required', false);
            fechaVencimientoField.prop('required', false);
        } else {
            // Mostrar campos de contrato específico
            contractNumberSection.show();
            contractDatesSection.show();

            if (tipoContrato === 'DEFINIDO' || tipoContrato === 'PROYECTO' || tipoContrato === 'TEMPORAL') {
                // Hacer obligatorios los campos para contratos específicos
                numeroContratoField.prop('required', true);
                fechaInicioField.prop('required', true);
                fechaVencimientoField.prop('required', true);
                fechaVencimientoField.parent().find('small').html('Requerido para contratos definidos, por proyecto o temporales <span class="text-danger">*</span>');
            }
        }
    });

    // Ejecutar al cargar para mostrar campos si ya hay valores seleccionados
    $('#forma_pago').trigger('change');
    $('#tipo_contrato').trigger('change');

    // Previsualización de imagen
    $('#photo').change(function() {
        var file = this.files[0];
        if (file) {
            // Validar tipo de archivo
            var validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Por favor seleccione una imagen válida (JPG, PNG o GIF)');
                this.value = '';
                $('#photo-preview').hide();
                return;
            }
            
            // Validar tamaño (2MB)
            if (file.size > 2097152) {
                alert('La imagen es demasiado grande. El tamaño máximo es 2MB.');
                this.value = '';
                $('#photo-preview').hide();
                return;
            }
            
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#photo-preview img').attr('src', e.target.result);
                $('#photo-preview').show();
            }
            reader.readAsDataURL(file);
        } else {
            $('#photo-preview').hide();
        }
    });
});