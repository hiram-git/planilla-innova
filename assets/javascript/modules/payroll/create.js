
// Verificar si $ está disponible
if (typeof $ === 'undefined') {
    console.error('$ no está disponible en archivo externo. Esperando...');

    var checkJQuery = setInterval(function() {
        if (typeof $ !== 'undefined') {
            console.log('$ disponible en archivo externo, inicializando...');
            clearInterval(checkJQuery);
            inicializarModulo();
        }
    }, 100);
} else {
    $(document).ready(function() {
        inicializarModulo();
    });
}

function inicializarModulo() {

    // Función principal para generar descripción
    function generarDescripcion() {

        // Obtener valores
        const tipoPlanilla = window.getSelectedPayrollType ? window.getSelectedPayrollType() : null;
        const frecuenciaTexto = $('#frecuencia_id option:selected').text().trim();
        const fechaInicio = $('#periodo_inicio').val();
        const fechaFin = $('#periodo_fin').val();


        // Generar descripción con formato: PLANILLA [TIPO_PLANILLA] [FRECUENCIA] DESDE [FECHA_INICIO] HASTA [FECHA_FIN]
        let descripcion = 'PLANILLA';

        // Agregar tipo de planilla
        if (tipoPlanilla && tipoPlanilla.name) {
            descripcion += ' ' + tipoPlanilla.name.toUpperCase();
        }

        // Agregar frecuencia
        if (frecuenciaTexto) {
            descripcion += ' ' + frecuenciaTexto.toUpperCase();
        }

        // Agregar fechas
        if (fechaInicio) {
            descripcion += ' DESDE ' + fechaInicio;
        }

        if (fechaFin) {
            descripcion += ' HASTA ' + fechaFin;
        }


        // Aplicar al campo descripción
        $('#descripcion').val(descripcion);

        // Actualizar campo hidden tipo_planilla_id
        if (tipoPlanilla && tipoPlanilla.id) {
            $('#tipo_planilla_id').val(tipoPlanilla.id);
        }

    }

    // Event listeners
    $('#frecuencia_id').on('change', function() {

        // Calcular fechas automáticamente
        const tipo = $(this).find('option:selected').data('codigo');

        if (tipo === 'quincenal') {
            const hoy = new Date();
            const dia = hoy.getDate();

            let inicio, fin;
            if (dia <= 15) {
                // Primera quincena
                inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                fin = new Date(hoy.getFullYear(), hoy.getMonth(), 15);
            } else {
                // Segunda quincena
                inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 16);
                fin = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            }

            $('#periodo_inicio').val(inicio.toISOString().split('T')[0]);
            $('#periodo_fin').val(fin.toISOString().split('T')[0]);
        }

        setTimeout(generarDescripcion, 100);
    });

    $('#fecha, #periodo_inicio, #periodo_fin').on('change input', function() {
        setTimeout(generarDescripcion, 100);
    });

    // Event listener para cambios de tipo de planilla desde navbar
    window.addEventListener('payrollTypeChanged', function(event) {
        setTimeout(generarDescripcion, 100);
    });

    // Inicialización
    function inicializar() {

        // Establecer frecuencia quincenal por defecto
        const quincenal = $('#frecuencia_id option[data-codigo="quincenal"]').first();
        if (quincenal.length) {
            $('#frecuencia_id').val(quincenal.val()).trigger('change');
        }

        // Generar descripción inicial
        setTimeout(generarDescripcion, 200);
    }

    // Ejecutar inicialización
    setTimeout(inicializar, 100);
    setTimeout(inicializar, 500);
    setTimeout(inicializar, 1000);

}