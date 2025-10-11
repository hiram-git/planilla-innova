/**
 * Employee Salaries Module
 * Maneja la generación dinámica de campos de salario según tipos de planilla seleccionados
 *
 * @package Planilla-Innova
 * @version 3.4.1
 */

import BaseModule from '../base-module.js';

class EmployeeSalariesModule extends BaseModule {
    constructor() {
        super('employee-salaries');

        // Elementos DOM
        this.tipoPlanillaSelect = null;
        this.salariesSection = null;
        this.salariesContainer = null;
        this.salariesBadge = null;

        // Datos de tipos de planilla disponibles
        this.tiposPlanillaData = {};

        // Salarios actuales (para modo edición)
        this.currentSalaries = {};
    }

    /**
     * Inicializar módulo
     */
    init() {
        console.log('Initializing Employee Salaries Module...');

        this.cacheDOMElements();
        this.loadTiposPlanillaData();
        this.attachEventListeners();
    }

    /**
     * Cachear elementos DOM
     */
    cacheDOMElements() {
        this.tipoPlanillaSelect = $('#tipo_planilla');
        this.salariesSection = $('#salaries-section');
        this.salariesContainer = $('#salaries-container');
        this.salariesBadge = $('#salaries-count-badge');
    }

    /**
     * Cargar datos de tipos de planilla desde el select existente
     */
    loadTiposPlanillaData() {
        if (this.tipoPlanillaSelect.length === 0) {
            console.warn('Select tipo_planilla no encontrado');
            return;
        }

        // Extraer opciones del select
        this.tipoPlanillaSelect.find('option').each((index, option) => {
            const $option = $(option);
            const id = $option.val();
            const descripcion = $option.text();

            if (id && id !== '') {
                this.tiposPlanillaData[id] = {
                    id: parseInt(id),
                    descripcion: descripcion
                };
            }
        });

        console.log('Tipos de planilla cargados:', this.tiposPlanillaData);
    }

    /**
     * Adjuntar event listeners
     */
    attachEventListeners() {
        if (this.tipoPlanillaSelect.length === 0) {
            return;
        }

        // Listener para cambios en select de tipos de planilla
        this.tipoPlanillaSelect.on('change', (e) => {
            this.handleTipoPlanillaChange(e);
        });

        // Trigger inicial si ya hay valores seleccionados (modo edición)
        if (this.tipoPlanillaSelect.val() && this.tipoPlanillaSelect.val().length > 0) {
            this.handleTipoPlanillaChange();
        }
    }

    /**
     * Manejar cambio en selección de tipos de planilla
     */
    handleTipoPlanillaChange(e) {
        const selectedIds = this.tipoPlanillaSelect.val() || [];

        console.log('Tipos seleccionados:', selectedIds);

        if (selectedIds.length === 0) {
            this.hideSalariesSection();
            return;
        }

        this.showSalariesSection();
        this.renderSalaryFields(selectedIds);
        this.updateSalariesBadge(selectedIds.length);
    }

    /**
     * Mostrar sección de salarios
     */
    showSalariesSection() {
        this.salariesSection.slideDown(300);
    }

    /**
     * Ocultar sección de salarios
     */
    hideSalariesSection() {
        this.salariesSection.slideUp(300);
        this.salariesContainer.empty();
    }

    /**
     * Renderizar campos de salario para tipos seleccionados
     */
    renderSalaryFields(selectedIds) {
        this.salariesContainer.empty();

        if (selectedIds.length === 0) {
            this.salariesContainer.html(`
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Seleccione uno o más tipos de planilla para configurar salarios.
                </div>
            `);
            return;
        }

        selectedIds.forEach((tipoPlanillaId, index) => {
            const tipoPlanilla = this.tiposPlanillaData[tipoPlanillaId];

            if (!tipoPlanilla) {
                console.warn(`Tipo de planilla ${tipoPlanillaId} no encontrado en datos`);
                return;
            }

            const salaryRow = this.createSalaryRow(tipoPlanilla, index);
            this.salariesContainer.append(salaryRow);
        });

        // Inicializar máscaras de moneda
        this.initializeCurrencyMasks();
    }

    /**
     * Crear fila de salario para un tipo de planilla
     */
    createSalaryRow(tipoPlanilla, index) {
        const currentSalary = this.currentSalaries[tipoPlanilla.id] || {};
        const sueldoBase = currentSalary.sueldo_base || '';
        const gastosRep = currentSalary.gastos_representacion || '';

        const rowHtml = `
            <div class="salary-row mb-4" data-tipo-planilla-id="${tipoPlanilla.id}">
                <div class="row">
                    <!-- Tipo de Planilla Label -->
                    <div class="col-md-12 mb-2">
                        <h5 class="text-primary">
                            <i class="fas fa-file-invoice-dollar"></i>
                            ${tipoPlanilla.descripcion}
                        </h5>
                        <hr class="mt-2 mb-3">
                    </div>

                    <!-- Sueldo Base -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sueldo_base_${tipoPlanilla.id}">
                                <i class="fas fa-money-bill-wave text-success"></i>
                                Sueldo Base <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">B/.</span>
                                </div>
                                <input type="text"
                                       class="form-control currency-input"
                                       id="sueldo_base_${tipoPlanilla.id}"
                                       name="salaries[${tipoPlanilla.id}][sueldo_base]"
                                       value="${sueldoBase}"
                                       placeholder="0.00"
                                       required>
                            </div>
                            <small class="form-text text-muted">
                                Salario base para planilla de ${tipoPlanilla.descripcion}
                            </small>
                        </div>
                    </div>

                    <!-- Gastos de Representación -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="gastos_rep_${tipoPlanilla.id}">
                                <i class="fas fa-briefcase text-info"></i>
                                Gastos de Representación
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">B/.</span>
                                </div>
                                <input type="text"
                                       class="form-control currency-input"
                                       id="gastos_rep_${tipoPlanilla.id}"
                                       name="salaries[${tipoPlanilla.id}][gastos_representacion]"
                                       value="${gastosRep}"
                                       placeholder="0.00">
                            </div>
                            <small class="form-text text-muted">
                                Gastos adicionales (opcional)
                            </small>
                        </div>
                    </div>

                    <!-- Fecha Inicio (opcional, para futuras versiones) -->
                    <input type="hidden"
                           name="salaries[${tipoPlanilla.id}][fecha_inicio]"
                           value="${new Date().toISOString().split('T')[0]}">
                </div>
            </div>
        `;

        return rowHtml;
    }

    /**
     * Inicializar máscaras de moneda en inputs
     */
    initializeCurrencyMasks() {
        $('.currency-input').each(function() {
            const $input = $(this);

            // Formatear valor inicial si existe
            if ($input.val() && $input.val() !== '') {
                const numValue = parseFloat($input.val().replace(/[^0-9.-]/g, ''));
                if (!isNaN(numValue)) {
                    $input.val(numValue.toFixed(2));
                }
            }

            // Event listeners para formato
            $input.on('blur', function() {
                let value = $(this).val().replace(/[^0-9.-]/g, '');
                if (value && value !== '') {
                    const numValue = parseFloat(value);
                    if (!isNaN(numValue)) {
                        $(this).val(numValue.toFixed(2));
                    } else {
                        $(this).val('');
                    }
                }
            });

            $input.on('focus', function() {
                $(this).select();
            });
        });
    }

    /**
     * Actualizar badge con cantidad de salarios configurados
     */
    updateSalariesBadge(count) {
        if (this.salariesBadge.length > 0) {
            this.salariesBadge.text(count);
        }
    }

    /**
     * Cargar salarios existentes (para modo edición)
     * @param {Object} salaries - Objeto con salarios existentes { tipo_planilla_id: {sueldo_base, gastos_representacion} }
     */
    loadExistingSalaries(salaries) {
        this.currentSalaries = salaries || {};
        console.log('Salarios existentes cargados:', this.currentSalaries);

        // Re-renderizar si ya hay tipos seleccionados
        if (this.tipoPlanillaSelect.val() && this.tipoPlanillaSelect.val().length > 0) {
            this.handleTipoPlanillaChange();
        }
    }

    /**
     * Validar que todos los salarios requeridos estén completos
     * @returns {boolean}
     */
    validateSalaries() {
        let isValid = true;
        const errors = [];

        $('.salary-row').each(function() {
            const $row = $(this);
            const tipoPlanillaId = $row.data('tipo-planilla-id');
            const $sueldoBase = $row.find('input[name*="[sueldo_base]"]');
            const sueldoValue = parseFloat($sueldoBase.val());

            if (!sueldoValue || sueldoValue <= 0) {
                isValid = false;
                $sueldoBase.addClass('is-invalid');
                errors.push(`Debe ingresar un sueldo base válido para tipo de planilla ID ${tipoPlanillaId}`);
            } else {
                $sueldoBase.removeClass('is-invalid');
            }
        });

        if (!isValid) {
            console.error('Validación de salarios falló:', errors);
            toastr.error('Por favor, complete todos los campos de salario base requeridos.', 'Validación de Salarios');
        }

        return isValid;
    }

    /**
     * Obtener datos de salarios para enviar al servidor
     * @returns {Object}
     */
    getSalariesData() {
        const salariesData = {};

        $('.salary-row').each(function() {
            const $row = $(this);
            const tipoPlanillaId = $row.data('tipo-planilla-id');
            const sueldoBase = parseFloat($row.find('input[name*="[sueldo_base]"]').val()) || 0;
            const gastosRep = parseFloat($row.find('input[name*="[gastos_representacion]"]').val()) || 0;

            salariesData[tipoPlanillaId] = {
                sueldo_base: sueldoBase,
                gastos_representacion: gastosRep
            };
        });

        return salariesData;
    }
}

// Exportar clase para uso global
export default EmployeeSalariesModule;

// Auto-inicializar si estamos en página de empleados
$(document).ready(function() {
    // Verificar si estamos en página create o edit de empleados
    const isEmployeePage = $('#employee-form').length > 0 || $('form[action*="employees"]').length > 0;

    if (isEmployeePage && $('#tipo_planilla').length > 0) {
        const salariesModule = new EmployeeSalariesModule();
        salariesModule.init();

        // Hacer disponible globalmente para uso en validaciones de formulario
        window.employeeSalariesModule = salariesModule;
    }
});
