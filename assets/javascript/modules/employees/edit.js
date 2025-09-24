/**
 * Módulo JavaScript para edición de empleados
 * Maneja la validación de campos y UI dinámica
 */

$(document).ready(function() {
    console.log('Employees Edit Module Loading...');

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

            // Hacer obligatorios los campos de empresa privada
            $('#edit_cargo_id, #edit_funcion_id, #edit_partida_id, #edit_sueldo_individual').prop('required', true);
            $('#edit_position').prop('required', false);

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

    console.log('Employees Edit Module Loaded Successfully');
});