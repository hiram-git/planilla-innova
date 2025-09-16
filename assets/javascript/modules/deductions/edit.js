/**
 * Módulo JavaScript específico para edición de deducciones
 */

// Definir restricciones por defecto si no existen en el backend
var editRestrictions = window.EDIT_RESTRICTIONS || {
    canEditEmployee: true,
    canEditCreditor: true,
    canEditDescription: true,
    canEditAmount: true,
    inGeneratedPayroll: false,
    reason: 'Sin restricciones'
};

$(document).ready(function() {
    console.log('Deductions Edit Module Loading...');
    console.log('URLs disponibles:', window.DEDUCTIONS_URLS);
    console.log('Current Employee:', window.CURRENT_EMPLOYEE);
    console.log('Edit Restrictions:', window.EDIT_RESTRICTIONS);
    
    // Verificar que las URLs estén disponibles
    if (typeof window.DEDUCTIONS_URLS === 'undefined') {
        console.error('DEDUCTIONS_URLS no está definido');
        return;
    }
    
    // Verificar que jQuery esté disponible
    if (typeof $ === 'undefined') {
        console.error('jQuery no está cargado');
        return;
    }
    
    // Cargar información del empleado automáticamente
    if (typeof window.CURRENT_EMPLOYEE !== 'undefined' && window.CURRENT_EMPLOYEE.id) {
        loadEmployeeInfoEdit(window.CURRENT_EMPLOYEE.id, window.CURRENT_EMPLOYEE.deductionId);
    }
    
    // Configurar módulo para edit mode
    if (typeof DeductionsModule !== "undefined") {
        // Configurar URLs para el módulo
        if (typeof window.DEDUCTIONS_URLS !== 'undefined') {
            DeductionsModule.setUrls(window.DEDUCTIONS_URLS);
        }
    }
    
    // Aplicar restricciones de edición basadas en el backend
    applyEditRestrictions();
});


/**
 * Función para aplicar restricciones de edición
 */
function applyEditRestrictions() {
    if (!editRestrictions.canEditDescription) {
        $("#description").prop("readonly", true).addClass("readonly-field");
    }
    
    if (!editRestrictions.canEditAmount) {
        $("#amount").prop("readonly", true).addClass("readonly-field");
    }
    
    if (!editRestrictions.canEditEmployee) {
        $("#employee_id").prop("disabled", true).addClass("readonly-field");
    }
    
    if (!editRestrictions.canEditCreditor) {
        $("#creditor_id").prop("disabled", true).addClass("readonly-field");
    }
    
    // Mostrar información sobre restricciones si es necesario
    if (editRestrictions.inGeneratedPayroll) {
        showRestrictionMessage(editRestrictions.reason);
    }
}

/**
 * Mostrar mensaje de restricción
 */
function showRestrictionMessage(reason) {
    var message = '<div class="alert alert-warning mt-2"><i class="fas fa-exclamation-triangle"></i> ' + reason + '</div>';
    $('.card-body').prepend(message);
}

/**
 * Cargar información del empleado para edición
 */
function loadEmployeeInfoEdit(employeeId, deductionId) {
    if (!employeeId || !window.DEDUCTIONS_URLS || !window.DEDUCTIONS_URLS.employeeInfo) {
        $("#employeeInfo").hide();
        return;
    }
    
    $.ajax({
        url: window.DEDUCTIONS_URLS.employeeInfo,
        method: "GET",
        data: { 
            employee_id: employeeId,
            deduction_id: deductionId
        },
        success: function(data) {
            console.log('Employee info response:', data);
            
            if (data.success && data.data) {
                var emp = data.data;
                
                // Actualizar solo información básica del empleado
                $("#empName").text(emp.name || "N/A");
                $("#empCode").text(emp.code || "N/A");  
                $("#empPosition").text(emp.position || "Sin puesto");
                
                $("#employeeInfo").show();
            } else {
                console.error('Error loading employee info:', data.message || 'Unknown error');
                // Mostrar mensaje de error pero mantener la sección visible
                $("#empName").text("Error al cargar");
                $("#empCode").text("Error");
                $("#empPosition").text("Error al cargar");
            }
        },
        error: function() {
            $("#employeeInfo").hide();
            console.log("Error cargando información del empleado");
        }
    });
}