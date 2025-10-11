/**
 * Employee Create Module
 * Maneja la lógica del formulario de creación de empleados
 *
 * @package Planilla-Innova
 * @version 3.4.1
 */

import BaseModule from '../base-module.js';
import EmployeeSalariesModule from './salaries.js';

class EmployeeCreateModule extends BaseModule {
    constructor() {
        super('employee-create');

        // Submódulos
        this.salariesModule = null;

        // Elementos DOM
        this.form = null;
        this.tipoPlanillaSelect = null;
        this.positionField = null;
        this.cargoField = null;
        this.funcionField = null;
        this.partidaField = null;
        this.scheduleField = null;
        this.salarySection = null;
        this.sueldoIndividualField = null;
        this.gastosRepresentacionField = null;
        this.formaPagoSelect = null;
        this.bankDetailsSection = null;
        this.tipoContratoSelect = null;
        this.contractDatesSection = null;

        // Configuración
        this.companyType = window.APP_CONFIG?.company?.tipo_institucion || 'privada';
    }

    /**
     * Inicializar módulo
     */
    init() {
        console.log('Initializing Employee Create Module...');
        console.log('Company type:', this.companyType);

        this.cacheDOMElements();
        this.initializeSelect2();
        this.initializeSalariesModule();
        this.attachEventListeners();
        this.initializeConditionalFields();
        this.initializePhotoPreview();
    }

    /**
     * Cachear elementos DOM
     */
    cacheDOMElements() {
        this.form = $('form[action*="employees/store"]');
        this.tipoPlanillaSelect = $('#tipo_planilla');
        this.positionField = $('#position');
        this.cargoField = $('#cargo_id');
        this.funcionField = $('#funcion_id');
        this.partidaField = $('#partida_id');
        this.scheduleField = $('#schedule');
        this.salarySection = $('#salary-section');
        this.sueldoIndividualField = $('#sueldo_individual');
        this.gastosRepresentacionField = $('#gastos_representacion');
        this.formaPagoSelect = $('#forma_pago');
        this.bankDetailsSection = $('#bank-details');
        this.tipoContratoSelect = $('#tipo_contrato');
        this.contractDatesSection = $('#contract-dates-section');
    }

    /**
     * Inicializar Select2 en campos de selección
     */
    initializeSelect2() {
        // Select2 para tipos de planilla (múltiple)
        if (this.tipoPlanillaSelect.length > 0) {
            this.tipoPlanillaSelect.select2({
                placeholder: 'Seleccione uno o más tipos de planilla',
                allowClear: true,
                width: '100%',
                language: 'es'
            });
        }

        // Select2 para otros campos
        $('#position, #cargo_id, #funcion_id, #partida_id, #schedule, #situacion, #organigrama_id').select2({
            width: '100%',
            language: 'es'
        });
    }

    /**
     * Inicializar módulo de salarios
     */
    initializeSalariesModule() {
        this.salariesModule = new EmployeeSalariesModule();
        this.salariesModule.init();

        // Hacer disponible globalmente
        window.employeeSalariesModule = this.salariesModule;
    }

    /**
     * Adjuntar event listeners
     */
    attachEventListeners() {
        // Forma de pago - mostrar/ocultar campos bancarios
        this.formaPagoSelect.on('change', () => this.handleFormaPagoChange());

        // Tipo de contrato - validar fechas
        this.tipoContratoSelect.on('change', () => this.handleTipoContratoChange());

        // Validación del formulario antes de enviar
        this.form.on('submit', (e) => this.handleFormSubmit(e));
    }

    /**
     * Inicializar campos condicionales según tipo de empresa
     */
    initializeConditionalFields() {
        if (this.companyType === 'publica') {
            // Institución pública: mostrar posición, ocultar cargo/función/partida
            $('#public-institution-fields').show();
            $('#private-company-fields').hide();
            $('#salary-section').hide();

            // Position es requerido
            this.positionField.attr('required', true);

            // Cargo/función/partida no son requeridos
            this.cargoField.attr('required', false);
            this.funcionField.attr('required', false);
            this.partidaField.attr('required', false);

        } else {
            // Empresa privada: mostrar cargo/función/partida, ocultar posición
            $('#public-institution-fields').hide();
            $('#private-company-fields').show();
            $('#salary-section').show();

            // Position no es requerido
            this.positionField.attr('required', false);

            // Cargo/función/partida son requeridos
            this.cargoField.attr('required', true);
            this.funcionField.attr('required', true);
            this.partidaField.attr('required', true);

            // Sueldo individual es requerido para empresa privada
            this.sueldoIndividualField.attr('required', true);
        }
    }

    /**
     * Manejar cambio en forma de pago
     */
    handleFormaPagoChange() {
        const formaPago = this.formaPagoSelect.val();

        if (formaPago === 'CHEQUE' || formaPago === 'ACH') {
            this.bankDetailsSection.slideDown();
            $('#banco, #numero_cuenta').attr('required', true);
        } else {
            this.bankDetailsSection.slideUp();
            $('#banco, #numero_cuenta').attr('required', false);
        }
    }

    /**
     * Manejar cambio en tipo de contrato
     */
    handleTipoContratoChange() {
        const tipoContrato = this.tipoContratoSelect.val();

        if (tipoContrato === 'DEFINIDO' || tipoContrato === 'PROYECTO' || tipoContrato === 'TEMPORAL') {
            $('#fecha_vencimiento_contrato').attr('required', true);
            this.contractDatesSection.find('.form-text').addClass('text-danger').removeClass('text-muted');
        } else {
            $('#fecha_vencimiento_contrato').attr('required', false);
            this.contractDatesSection.find('.form-text').removeClass('text-danger').addClass('text-muted');
        }
    }

    /**
     * Manejar envío del formulario
     */
    handleFormSubmit(e) {
        let isValid = true;

        // Validar salarios si hay tipos de planilla seleccionados
        const selectedTipos = this.tipoPlanillaSelect.val();
        if (selectedTipos && selectedTipos.length > 0 && this.salariesModule) {
            if (!this.salariesModule.validateSalaries()) {
                isValid = false;
            }
        }

        // Validar fechas de contrato
        const tipoContrato = this.tipoContratoSelect.val();
        const fechaInicio = $('#fecha_inicio_contrato').val();
        const fechaVencimiento = $('#fecha_vencimiento_contrato').val();

        if ((tipoContrato === 'DEFINIDO' || tipoContrato === 'PROYECTO' || tipoContrato === 'TEMPORAL') && !fechaVencimiento) {
            toastr.error('Debe ingresar una fecha de vencimiento para contratos definidos, por proyecto o temporales.', 'Validación');
            $('#fecha_vencimiento_contrato').addClass('is-invalid');
            isValid = false;
        }

        if (fechaInicio && fechaVencimiento && fechaInicio > fechaVencimiento) {
            toastr.error('La fecha de vencimiento debe ser posterior a la fecha de inicio del contrato.', 'Validación');
            $('#fecha_vencimiento_contrato').addClass('is-invalid');
            isValid = false;
        }

        // Validar campos bancarios si es requerido
        const formaPago = this.formaPagoSelect.val();
        if ((formaPago === 'CHEQUE' || formaPago === 'ACH') && (!$('#banco').val() || !$('#numero_cuenta').val())) {
            toastr.error('Debe ingresar los datos bancarios para la forma de pago seleccionada.', 'Validación');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            return false;
        }

        // Si todo está bien, mostrar mensaje de carga
        Swal.fire({
            title: 'Guardando empleado...',
            text: 'Por favor espere',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        return true;
    }

    /**
     * Inicializar preview de foto
     */
    initializePhotoPreview() {
        $('#photo').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamaño (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    toastr.error('La foto no debe superar 2MB de tamaño.', 'Error');
                    $(this).val('');
                    $('#photo-preview').hide();
                    return;
                }

                // Validar tipo
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    toastr.error('Solo se permiten archivos JPG, PNG o GIF.', 'Error');
                    $(this).val('');
                    $('#photo-preview').hide();
                    return;
                }

                // Mostrar preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#photo-preview img').attr('src', e.target.result);
                    $('#photo-preview').fadeIn();
                };
                reader.readAsDataURL(file);
            } else {
                $('#photo-preview').hide();
            }
        });
    }
}

// Auto-inicializar cuando el DOM esté listo
$(document).ready(function() {
    const module = new EmployeeCreateModule();
    module.init();

    // Hacer disponible globalmente
    window.employeeCreateModule = module;
});

// Exportar para uso como módulo
export default EmployeeCreateModule;
