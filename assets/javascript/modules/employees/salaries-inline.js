/**
 * Employee Salaries - Inline Version
 * Maneja SOLO la generación dinámica de campos de salario según tipos de planilla seleccionados
 *
 * IMPORTANTE: Este módulo se integra con create.js y NO duplica funcionalidades
 *
 * @package Planilla-Innova
 * @version 3.4.1
 */

(function($) {
    'use strict';

    // Esperar a que el DOM esté listo
    $(document).ready(function() {

        console.log('🚀 Initializing Employee Salaries Module...');

        // Variables
        var tipoPlanillaSelect = $('#tipo_planilla').length > 0 ? $('#tipo_planilla') : $('#edit_tipo_planilla');
        var salariesSection = $('#salaries-section');
        var salariesContainer = $('#salaries-container');
        var salariesBadge = $('#salaries-count-badge');
        var tiposPlanillaData = {};
        var existingSalaries = window.EMPLOYEE_SALARIES || {};

        // Verificar que el select existe
        if (tipoPlanillaSelect.length === 0) {
            console.warn('⚠️ Select tipo_planilla no encontrado');
            return;
        }

        console.log('✅ Select tipo_planilla encontrado');
        console.log('📦 Salarios existentes:', existingSalaries);

        // Cargar datos de tipos de planilla desde el select
        function loadTiposPlanillaData() {
            tipoPlanillaSelect.find('option').each(function() {
                var $option = $(this);
                var id = $option.val();
                var descripcion = $option.text();

                if (id && id !== '') {
                    tiposPlanillaData[id] = {
                        id: parseInt(id),
                        descripcion: descripcion
                    };
                }
            });

            console.log('📋 Tipos de planilla cargados:', tiposPlanillaData);
        }

        // Crear fila de salario para un tipo de planilla
        function createSalaryRow(tipoPlanilla, index) {
            // Obtener valores existentes si los hay
            var existingSalary = existingSalaries[tipoPlanilla.id] || {};
            var sueldoBase = existingSalary.sueldo_base || '';
            var gastosRep = existingSalary.gastos_representacion || '';

            console.log('💰 Creando fila para tipo', tipoPlanilla.id, 'con valores:', {sueldoBase: sueldoBase, gastosRep: gastosRep});
            var rowHtml = '\
                <div class="salary-row mb-4 p-3 border rounded" data-tipo-planilla-id="' + tipoPlanilla.id + '" style="background-color: #f8f9fa;">\
                    <div class="row">\
                        <div class="col-md-12 mb-3">\
                            <h5 class="text-primary mb-0">\
                                <i class="fas fa-file-invoice-dollar"></i>\
                                ' + tipoPlanilla.descripcion + '\
                            </h5>\
                            <hr class="mt-2 mb-3">\
                        </div>\
                        <div class="col-md-6">\
                            <div class="form-group">\
                                <label for="sueldo_base_' + tipoPlanilla.id + '">\
                                    <i class="fas fa-money-bill-wave text-success"></i>\
                                    Sueldo Base <span class="text-danger">*</span>\
                                </label>\
                                <div class="input-group">\
                                    <div class="input-group-prepend">\
                                        <span class="input-group-text">B/.</span>\
                                    </div>\
                                    <input type="number"\
                                           class="form-control salary-input"\
                                           id="sueldo_base_' + tipoPlanilla.id + '"\
                                           name="salaries[' + tipoPlanilla.id + '][sueldo_base]"\
                                           step="0.01"\
                                           min="0"\
                                           placeholder="0.00"\
                                           value="' + sueldoBase + '"\
                                           required>\
                                </div>\
                                <small class="form-text text-muted">\
                                    Salario base para planilla de ' + tipoPlanilla.descripcion + '\
                                </small>\
                            </div>\
                        </div>\
                        <div class="col-md-6">\
                            <div class="form-group">\
                                <label for="gastos_rep_' + tipoPlanilla.id + '">\
                                    <i class="fas fa-briefcase text-info"></i>\
                                    Gastos de Representación\
                                </label>\
                                <div class="input-group">\
                                    <div class="input-group-prepend">\
                                        <span class="input-group-text">B/.</span>\
                                    </div>\
                                    <input type="number"\
                                           class="form-control salary-input"\
                                           id="gastos_rep_' + tipoPlanilla.id + '"\
                                           name="salaries[' + tipoPlanilla.id + '][gastos_representacion]"\
                                           step="0.01"\
                                           min="0"\
                                           placeholder="0.00"\
                                           value="' + gastosRep + '">\
                                </div>\
                                <small class="form-text text-muted">\
                                    Gastos adicionales (opcional)\
                                </small>\
                            </div>\
                        </div>\
                    </div>\
                </div>\
            ';

            return rowHtml;
        }

        // Renderizar campos de salario
        function renderSalaryFields(selectedIds) {
            console.log('🎨 Renderizando campos para:', selectedIds);

            salariesContainer.empty();

            if (!selectedIds || selectedIds.length === 0) {
                salariesContainer.html('\
                    <div class="alert alert-info">\
                        <i class="fas fa-info-circle"></i>\
                        Seleccione uno o más tipos de planilla para configurar salarios.\
                    </div>\
                ');
                return;
            }

            selectedIds.forEach(function(tipoPlanillaId, index) {
                var tipoPlanilla = tiposPlanillaData[tipoPlanillaId];

                if (!tipoPlanilla) {
                    console.warn('⚠️ Tipo de planilla no encontrado:', tipoPlanillaId);
                    return;
                }

                var salaryRow = createSalaryRow(tipoPlanilla, index);
                salariesContainer.append(salaryRow);
            });

            // Aplicar formato a los inputs
            $('.salary-input').on('focus', function() {
                $(this).select();
            });

            console.log('✅ Campos renderizados exitosamente');
        }

        // Actualizar badge
        function updateSalariesBadge(count) {
            if (salariesBadge.length > 0) {
                salariesBadge.text(count);
            }
        }

        // Manejar cambio en selección de tipos de planilla
        function handleTipoPlanillaChange() {
            var selectedIds = tipoPlanillaSelect.val() || [];

            console.log('🔄 Cambio detectado, tipos seleccionados:', selectedIds);

            if (selectedIds.length === 0) {
                salariesSection.slideUp(300);
                salariesContainer.empty();
                updateSalariesBadge(0);
                return;
            }

            salariesSection.slideDown(300);
            renderSalaryFields(selectedIds);
            updateSalariesBadge(selectedIds.length);
        }

        // Cargar datos
        loadTiposPlanillaData();

        // Adjuntar event listener
        // NOTA: No inicializamos Select2 aquí porque ya lo hace create.js
        tipoPlanillaSelect.on('change', handleTipoPlanillaChange);
        console.log('✅ Event listener adjuntado');

        // Trigger inicial si ya hay valores seleccionados (modo edición)
        var initialValues = tipoPlanillaSelect.val();
        if (initialValues && initialValues.length > 0) {
            console.log('🔄 Valores iniciales detectados:', initialValues);
            setTimeout(function() {
                handleTipoPlanillaChange();
            }, 500); // Delay para dar tiempo a Select2 de create.js
        }

        // Validación adicional en el formulario
        $('form').on('submit', function(e) {
            var selectedTipos = tipoPlanillaSelect.val();
            if (selectedTipos && selectedTipos.length > 0) {
                var salariesValid = true;
                $('.salary-row').each(function() {
                    var sueldoBase = $(this).find('input[name*="[sueldo_base]"]').val();
                    if (!sueldoBase || parseFloat(sueldoBase) <= 0) {
                        $(this).find('input[name*="[sueldo_base]"]').addClass('is-invalid');
                        salariesValid = false;
                    }
                });

                if (!salariesValid) {
                    e.preventDefault();
                    toastr.error('Debe ingresar un sueldo base válido para todos los tipos de planilla seleccionados.', 'Validación');
                    return false;
                }
            }
        });

        console.log('✅ Employee Salaries Module inicializado correctamente');
    });

})(jQuery);
