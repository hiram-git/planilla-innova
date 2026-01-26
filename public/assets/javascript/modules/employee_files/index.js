/**
 * Employee Files Module - Simple Version
 * Solo inicializa datepickers y file inputs
 */

(function() {
    'use strict';

    // Esperar a que jQuery esté disponible
    function waitForjQuery() {
        if (typeof window.jQuery !== 'undefined' && typeof window.$ !== 'undefined') {
            $(document).ready(function() {
                initEmployeeFilesModule();
            });
        } else {
            setTimeout(waitForjQuery, 100);
        }
    }

    function initEmployeeFilesModule() {
        console.log('[Employee Files] Inicializando módulo');

        // Inicializar datepickers
        initDatePickers();

        // Inicializar datetime pickers
        initDateTimePickers();

        // Inicializar file inputs
        initFileInputs();

        console.log('[Employee Files] Módulo inicializado');
    }

    /**
     * Inicializar datepickers usando daterangepicker como single date picker
     */
    function initDatePickers() {
        if (typeof moment === 'undefined' || typeof $.fn.daterangepicker === 'undefined') {
            console.warn('[Employee Files] Moment.js o daterangepicker no están disponibles');
            return;
        }

        // Configurar moment en español
        moment.locale('es');

        // Configuración común para datepickers
        const datePickerConfig = {
            singleDatePicker: true,
            showDropdowns: true,
            autoApply: true,
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                applyLabel: 'Aplicar',
                cancelLabel: 'Cancelar',
                fromLabel: 'Desde',
                toLabel: 'Hasta',
                customRangeLabel: 'Personalizado',
                weekLabel: 'S',
                daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                monthNames: [
                    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                ],
                firstDay: 1
            }
        };

        // Aplicar datepicker a todos los campos con clase .date-picker
        $('.date-picker').each(function() {
            const $input = $(this);

            // Si ya está dentro de un input-group, no envolver de nuevo
            if ($input.parent().hasClass('input-group')) {
                return;
            }

            // Configurar datepicker
            $input.daterangepicker(datePickerConfig);

            // Si hay un valor inicial, establecerlo
            const initialValue = $input.val();
            if (initialValue && moment(initialValue, 'YYYY-MM-DD', true).isValid()) {
                $input.data('daterangepicker').setStartDate(moment(initialValue));
                $input.val(initialValue);
            }

            // Actualizar input cuando se selecciona una fecha
            $input.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD'));
                $(this).trigger('change');
            });

            // Limpiar cuando se cancela
            $input.on('cancel.daterangepicker', function() {
                $(this).val('');
            });

            // Agregar icono de calendario si no está en un input-group
            if (!$input.parent().hasClass('input-group')) {
                $input.wrap('<div class="input-group"></div>');
                $input.after(`
                    <div class="input-group-append">
                        <span class="input-group-text" style="cursor: pointer;">
                            <i class="far fa-calendar-alt"></i>
                        </span>
                    </div>
                `);

                // Hacer que el icono también abra el datepicker
                $input.next('.input-group-append').on('click', function() {
                    $input.trigger('click');
                });
            }
        });

        console.log('[Employee Files] Datepickers inicializados:', $('.date-picker').length);
    }

    /**
     * Inicializar datetime pickers
     */
    function initDateTimePickers() {
        if (typeof moment === 'undefined' || typeof $.fn.daterangepicker === 'undefined') {
            return;
        }

        const dateTimePickerConfig = {
            singleDatePicker: true,
            timePicker: true,
            timePicker24Hour: true,
            showDropdowns: true,
            autoApply: true,
            autoUpdateInput: false,
            locale: {
                format: 'YYYY-MM-DD HH:mm',
                separator: ' - ',
                applyLabel: 'Aplicar',
                cancelLabel: 'Cancelar',
                fromLabel: 'Desde',
                toLabel: 'Hasta',
                customRangeLabel: 'Personalizado',
                weekLabel: 'S',
                daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                monthNames: [
                    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                ],
                firstDay: 1
            }
        };

        $('.datetime-picker').each(function() {
            const $input = $(this);

            // Si ya está dentro de un input-group, no envolver de nuevo
            if ($input.parent().hasClass('input-group')) {
                return;
            }

            $input.daterangepicker(dateTimePickerConfig);

            const initialValue = $input.val();
            if (initialValue && moment(initialValue, 'YYYY-MM-DD HH:mm', true).isValid()) {
                $input.data('daterangepicker').setStartDate(moment(initialValue));
                $input.val(initialValue);
            }

            $input.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm'));
                $(this).trigger('change');
            });

            $input.on('cancel.daterangepicker', function() {
                $(this).val('');
            });

            // Agregar icono de reloj
            if (!$input.parent().hasClass('input-group')) {
                $input.wrap('<div class="input-group"></div>');
                $input.after(`
                    <div class="input-group-append">
                        <span class="input-group-text" style="cursor: pointer;">
                            <i class="far fa-clock"></i>
                        </span>
                    </div>
                `);

                // Hacer que el icono también abra el picker
                $input.next('.input-group-append').on('click', function() {
                    $input.trigger('click');
                });
            }
        });

        console.log('[Employee Files] DateTime pickers inicializados:', $('.datetime-picker').length);
    }

    /**
     * Inicializar custom file inputs
     */
    function initFileInputs() {
        if (typeof bsCustomFileInput !== 'undefined') {
            bsCustomFileInput.init();
            console.log('[Employee Files] File inputs inicializados');
        }
    }

    // Iniciar cuando el documento esté listo
    waitForjQuery();
})();
