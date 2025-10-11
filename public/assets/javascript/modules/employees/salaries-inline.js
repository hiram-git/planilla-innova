/**
 * Employee Salaries - Inline Version (No ES6 Modules)
 * Maneja la generación dinámica de campos de salario según tipos de planilla seleccionados
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
        var tipoPlanillaSelect = $('#tipo_planilla');
        var salariesSection = $('#salaries-section');
        var salariesContainer = $('#salaries-container');
        var salariesBadge = $('#salaries-count-badge');
        var tiposPlanillaData = {};

        // Verificar que el select existe
        if (tipoPlanillaSelect.length === 0) {
            console.warn('⚠️ Select tipo_planilla no encontrado');
            return;
        }

        console.log('✅ Select tipo_planilla encontrado');

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
                                           placeholder="0.00">\
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

        // Inicializar Select2
        if ($.fn.select2) {
            tipoPlanillaSelect.select2({
                placeholder: 'Seleccione uno o más tipos de planilla',
                allowClear: true,
                width: '100%',
                language: 'es'
            });
            console.log('✅ Select2 inicializado');
        }

        // Cargar datos
        loadTiposPlanillaData();

        // Adjuntar event listener
        tipoPlanillaSelect.on('change', handleTipoPlanillaChange);
        console.log('✅ Event listener adjuntado');

        // Trigger inicial si ya hay valores seleccionados (modo edición)
        var initialValues = tipoPlanillaSelect.val();
        if (initialValues && initialValues.length > 0) {
            console.log('🔄 Valores iniciales detectados:', initialValues);
            handleTipoPlanillaChange();
        }

        console.log('✅ Employee Salaries Module inicializado correctamente');
    });

})(jQuery);
