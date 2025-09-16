/**
 * Módulo JavaScript para creación de empleados
 * Maneja la sincronización con navbar, validaciones y UI
 */

$(document).ready(function() {
    console.log('Employees Create Module Loading...');
    
    // Obtener tipo de institución de la empresa
    var companyType = window.APP_CONFIG?.company?.tipo_institucion || 'privada';
    
    // Mostrar/ocultar campos según tipo de institución
    function toggleFieldsByCompanyType() {
        if (companyType === 'privada') {
            // Empresa privada: mostrar cargos, funciones, partidas y sueldo individual (SIN posición)
            $('#private-company-fields').show();
            $('#salary-section').show();
            $('#public-institution-fields').hide();
            
            // Hacer obligatorios los campos de empresa privada
            $('#cargo_id, #funcion_id, #partida_id, #sueldo_individual').prop('required', true);
            $('#position').prop('required', false);
            
        } else {
            // Institución pública: mostrar solo posición
            $('#public-institution-fields').show();
            $('#private-company-fields').hide();
            $('#salary-section').hide();
            
            // Hacer obligatorio solo el campo de posición
            $('#position').prop('required', true);
            $('#cargo_id, #funcion_id, #partida_id, #sueldo_individual').prop('required', false);
        }
    }
    
    // Ejecutar al cargar la página
    toggleFieldsByCompanyType();
    
    // Sincronizar tipo de planilla desde navbar
    syncPayrollTypeFromNavbar();
    
    // Escuchar cambios en el tipo de planilla del navbar
    window.addEventListener('payrollTypeChanged', function(event) {
        const payrollType = event.detail;
        if (payrollType && payrollType.id) {
            $('#tipo_planilla').val(payrollType.id);
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
                $('#tipo_planilla').val(selectedPayrollType.id);
                showSyncNotification(selectedPayrollType.name);
                console.log('Tipo de planilla preseleccionado:', selectedPayrollType.name);
            }
        } else {
            // Si no está disponible, intentar después de un breve delay
            setTimeout(function() {
                if (typeof window.getSelectedPayrollType === 'function') {
                    const selectedPayrollType = window.getSelectedPayrollType();
                    if (selectedPayrollType && selectedPayrollType.id) {
                        $('#tipo_planilla').val(selectedPayrollType.id);
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