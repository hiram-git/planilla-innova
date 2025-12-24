/**
 * Módulo JavaScript para edición de empleados
 * Maneja la validación de campos y UI dinámica
 */

$(document).ready(function() {
    console.log('Employees Edit Module Loading...');

    // Inicializar Select2 para el campo de tipo de planilla
    $('#edit_tipo_planilla').select2({
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
    console.log('Company type detected:', companyType);

    // Mostrar/ocultar campos según tipo de institución
    function toggleFieldsByCompanyType() {
        if (companyType === 'privada') {
            // Empresa privada: mostrar cargos, funciones, partidas y sueldo individual (SIN posición)
            $('#edit-private-company-fields').show();
            $('#edit-salary-section').show();
            $('#edit-public-institution-fields').hide();

            // Hacer obligatorios los campos de empresa privada (función y partida son opcionales)
            $('#edit_cargo_id, #edit_sueldo_individual').prop('required', true);
            $('#edit_position, #edit_funcion_id, #edit_partida_id').prop('required', false);

        } else {
            // Institución pública: mostrar solo posición
            $('#edit-public-institution-fields').show();
            $('#edit-private-company-fields').hide();
            $('#edit-salary-section').hide();

            // Hacer obligatorio solo el campo de posición
            $('#edit_position').prop('required', true);
            $('#edit_cargo_id, #edit_funcion_id, #edit_partida_id, #edit_sueldo_individual').prop('required', false);
        }
    }

    // Ejecutar al cargar la página
    toggleFieldsByCompanyType();

    // Manejo de forma de pago y campos bancarios
    $('#edit_forma_pago').change(function() {
        var formaPago = $(this).val();
        var bankDetails = $('#edit-bank-details');
        var bancoField = $('#edit_banco');
        var numeroCuentaField = $('#edit_numero_cuenta');
        var tipoCuentaField = $('#edit_tipo_cuenta');

        if (formaPago === 'CHEQUE' || formaPago === 'ACH') {
            // Mostrar campos bancarios y hacerlos obligatorios
            bankDetails.show();
            bancoField.prop('required', true);
            numeroCuentaField.prop('required', true);
            tipoCuentaField.prop('required', true);
        } else {
            // Ocultar campos bancarios y quitarles la obligatoriedad
            bankDetails.hide();
            bancoField.prop('required', false);
            numeroCuentaField.prop('required', false);
            tipoCuentaField.prop('required', false);
        }
    });

    // Manejo de tipo de contrato y campos relacionados
    $('#edit_tipo_contrato').change(function() {
        var tipoContrato = $(this).val();
        var contractNumberSection = $('#edit-contract-number-section');
        var contractDatesSection = $('#edit-contract-dates-section');
        var numeroContratoField = $('#edit_numero_contrato');
        var fechaInicioField = $('#edit_fecha_inicio_contrato');
        var fechaVencimientoField = $('#edit_fecha_vencimiento_contrato');

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
    $('#edit_forma_pago').trigger('change');
    $('#edit_tipo_contrato').trigger('change');

    // Validación del formulario antes de envío
    $('form').on('submit', function(e) {
        var formaPago = $('#edit_forma_pago').val();
        var tipoContrato = $('#edit_tipo_contrato').val();
        var errors = [];

        // Validar campos bancarios si la forma de pago lo requiere
        if (formaPago === 'CHEQUE' || formaPago === 'ACH') {
            if (!$('#edit_banco').val().trim()) {
                errors.push('El banco es requerido para la forma de pago seleccionada');
            }
            if (!$('#edit_numero_cuenta').val().trim()) {
                errors.push('El número de cuenta es requerido para la forma de pago seleccionada');
            }
            if (!$('#edit_tipo_cuenta').val()) {
                errors.push('El tipo de cuenta es requerido para la forma de pago seleccionada');
            }
        }

        // Validar campos de contrato para contratos específicos (no indefinidos)
        if (tipoContrato === 'DEFINIDO' || tipoContrato === 'PROYECTO' || tipoContrato === 'TEMPORAL') {
            if (!$('#edit_numero_contrato').val().trim()) {
                errors.push('El número de contrato es requerido para el tipo de contrato seleccionado');
            }
            if (!$('#edit_fecha_inicio_contrato').val()) {
                errors.push('La fecha de inicio de contrato es requerida para el tipo de contrato seleccionado');
            }
            if (!$('#edit_fecha_vencimiento_contrato').val()) {
                errors.push('La fecha de vencimiento es requerida para el tipo de contrato seleccionado');
            }
        }

        // Mostrar errores si existen
        if (errors.length > 0) {
            e.preventDefault();

            // Remover alertas anteriores
            $('.validation-alert').remove();

            var alertHtml = '<div class="alert alert-danger alert-dismissible fade show validation-alert" role="alert">' +
                '<h5><i class="fas fa-exclamation-triangle"></i> Errores de validación:</h5>' +
                '<ul class="mb-0">';

            errors.forEach(function(error) {
                alertHtml += '<li>' + error + '</li>';
            });

            alertHtml += '</ul>' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>';

            // Insertar alerta al inicio del formulario
            $('form').prepend(alertHtml);

            // Scroll hacia arriba para ver el error
            $('html, body').animate({scrollTop: 0}, 300);
        }
    });

    // ============================================================================
    // SISTEMA JERÁRQUICO DE DEPARTAMENTOS (EDIT MODE)
    // ============================================================================

    // Inicializar sistema de departamentos jerárquicos solo para empresas privadas
    var departamentosJerarquicos = null;

    if (companyType === 'privada') {
        // Verificar si el módulo DepartamentosJerarquicos está disponible
        if (typeof window.DepartamentosJerarquicos !== 'undefined') {
            console.log('[Edit] Inicializando sistema jerárquico de departamentos...');

            // Obtener departamento seleccionado del empleado
            var selectedDepartamentoId = $('#edit_departamento_id_private').val() || null;

            // Inicializar contador para detectar carga inicial vs cambios posteriores
            let isInitialLoad = true;

            departamentosJerarquicos = new window.DepartamentosJerarquicos('edit-departamentos-jerarquicos-container', {
                fieldName: 'edit_departamento_id',
                selectedDepartamentoId: selectedDepartamentoId,
                requiredField: true,
                showLabels: true,
                labelText: 'Departamento',
                onSelectionChange: function(data) {
                    console.log('[Edit] Departamento seleccionado:', data, 'isInitialLoad:', isInitialLoad);

                    // Cuando cambia el departamento, cargar cargos correspondientes
                    if (data.value) {
                        // En la carga inicial, preservar valores pre-seleccionados
                        if (isInitialLoad) {
                            const cargoIdPreSelected = window.APP_CONFIG?.old_data?.edit_cargo_id || null;
                            const funcionIdPreSelected = window.APP_CONFIG?.old_data?.edit_funcion_id || null;

                            console.log('[Edit] Initial load - preserving values:', {
                                cargo: cargoIdPreSelected,
                                funcion: funcionIdPreSelected
                            });

                            loadCargosByDepartamento(data.value, cargoIdPreSelected);
                            isInitialLoad = false; // Marcar que ya no es carga inicial
                        } else {
                            // Cambio manual del usuario - resetear selecciones
                            console.log('[Edit] User changed departamento - resetting cargo/funcion');
                            loadCargosByDepartamento(data.value);
                        }
                    } else {
                        // Resetear cargos y funciones
                        $('#edit_cargo_id').html('<option value="">Primero seleccione un departamento...</option>').prop('disabled', true);
                        $('#edit_funcion_id').html('<option value="">Primero seleccione un cargo...</option>').prop('disabled', true);
                        isInitialLoad = false;
                    }
                }
            });

            console.log('[Edit] Sistema jerárquico inicializado correctamente');
        } else {
            console.warn('[Edit] Módulo DepartamentosJerarquicos no disponible, usando sistema legacy');
        }
    }

    // ============================================================================
    // FUNCIONES AUXILIARES - Cargos y Funciones
    // ============================================================================

    /**
     * Cargar cargos según departamento seleccionado
     */
    function loadCargosByDepartamento(departamentoId, selectedCargoId = null) {
        console.log('[loadCargosByDepartamento] Called with:', { departamentoId, selectedCargoId });

        const $cargoSelect = $("#edit_cargo_id");
        const $funcionSelect = $("#edit_funcion_id");

        console.log('[loadCargosByDepartamento] Cargo select found:', $cargoSelect.length > 0);
        console.log('[loadCargosByDepartamento] Funcion select found:', $funcionSelect.length > 0);

        if (!departamentoId) {
            console.log('[loadCargosByDepartamento] No departamento ID provided, resetting selects');
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

        console.log('[loadCargosByDepartamento] Making AJAX request to:', url);

        // AJAX request
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('[loadCargosByDepartamento] AJAX Success:', response);

                if (response.success && response.cargos) {
                    console.log('[loadCargosByDepartamento] Found', response.cargos.length, 'cargos');
                    let options = '<option value="">Seleccionar cargo...</option>';

                    response.cargos.forEach(function(cargo) {
                        const selected = (selectedCargoId && cargo.id == selectedCargoId) ? ' selected' : '';
                        options += `<option value="${cargo.id}"${selected}>${cargo.codigo} - ${cargo.nombre}</option>`;
                        console.log('[loadCargosByDepartamento] Added cargo option:', cargo.codigo, selected ? '(SELECTED)' : '');
                    });

                    $cargoSelect.html(options).prop('disabled', false);
                    console.log('[loadCargosByDepartamento] Cargo select populated and enabled');

                    // Si hay un cargo seleccionado, cargar sus funciones
                    if (selectedCargoId) {
                        console.log('[loadCargosByDepartamento] Loading funciones for selected cargo:', selectedCargoId);
                        // ✅ CORREGIDO: Pasar funcion_id pre-seleccionada si está disponible
                        const funcionIdPreSelected = window.APP_CONFIG?.old_data?.edit_funcion_id || null;
                        loadFuncionesByCargo(selectedCargoId, funcionIdPreSelected);
                    }
                } else {
                    console.warn('[loadCargosByDepartamento] No cargos found or unsuccessful response');
                    $cargoSelect.html('<option value="">No hay cargos en este departamento</option>').prop('disabled', true);
                    toastr.warning('No se encontraron cargos para este departamento', 'Advertencia');
                }
            },
            error: function(xhr, status, error) {
                console.error('[loadCargosByDepartamento] AJAX Error:', { xhr, status, error });
                console.error('[loadCargosByDepartamento] Response text:', xhr.responseText);
                $cargoSelect.html('<option value="">Error al cargar cargos</option>').prop('disabled', true);
                toastr.error('Error al cargar los cargos del departamento', 'Error');
            }
        });
    }

    /**
     * Cargar funciones según cargo seleccionado (incluye genéricas)
     */
    function loadFuncionesByCargo(cargoId, selectedFuncionId = null) {
        const $funcionSelect = $("#edit_funcion_id");

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
    $("#edit_cargo_id").on("change", function() {
        const cargoId = $(this).val();
        loadFuncionesByCargo(cargoId);
    });

    // ============================================================================
    // INICIALIZACIÓN - Cargar valores pre-seleccionados
    // ============================================================================

    /**
     * Cargar cargos y funciones si hay valores pre-seleccionados (para edición)
     *
     * NOTA: Si hay sistema jerárquico, este se encarga automáticamente de la carga inicial
     * mediante el callback onSelectionChange con isInitialLoad=true
     */
    const departamentoIdSelected = $('#edit_departamento_id_private').val();

    // Obtener valores de old_data desde PHP (si están disponibles en window)
    const cargoIdSelected = window.APP_CONFIG?.old_data?.edit_cargo_id || null;
    const funcionIdSelected = window.APP_CONFIG?.old_data?.edit_funcion_id || null;

    console.log('[Edit] Initial values:', {
        departamentoIdSelected,
        cargoIdSelected,
        funcionIdSelected,
        hasDepartamentosJerarquicos: !!departamentosJerarquicos
    });

    // Solo cargar manualmente si NO hay sistema jerárquico (fallback legacy)
    if (!departamentosJerarquicos) {
        if (departamentoIdSelected) {
            console.log('[Edit] No hierarchical system - loading cargos manually for departamento:', departamentoIdSelected);
            loadCargosByDepartamento(departamentoIdSelected, cargoIdSelected);
        }

        // Si hay cargo seleccionado pero no departamento (caso de error), cargar funciones genéricas
        if (cargoIdSelected && !departamentoIdSelected) {
            console.log('[Edit] Loading funciones for cargo without departamento:', cargoIdSelected);
            loadFuncionesByCargo(cargoIdSelected, funcionIdSelected);
        }
    } else {
        console.log('[Edit] Hierarchical system active - onSelectionChange will handle cargo loading');
    }

    console.log('Employees Edit Module Loaded Successfully');
});