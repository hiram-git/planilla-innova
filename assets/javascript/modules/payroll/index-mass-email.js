/**
 * Funcionalidad de envío masivo de comprobantes por email
 * Este archivo se concatena con el módulo principal de payroll
 */

// Extender funciones existentes
(function() {
    'use strict';

    // Override de showEmployeeSelector para mostrar botón de envío masivo
    const originalShowEmployeeSelector = window.showEmployeeSelector;
    window.showEmployeeSelector = function(payrollId, action) {
        // Llamar a la función original
        originalShowEmployeeSelector(payrollId, action);

        // Mostrar u ocultar botón "Enviar a Todos" según la acción
        if (action === 'email') {
            $('#sendAllPayslips').show();
        } else {
            $('#sendAllPayslips').hide();
        }
    };

    /**
     * Enviar comprobantes a todos los empleados con email válido
     */
    $('#sendAllPayslips').on('click', function() {
        if (!currentPayrollId || !employeesList || employeesList.length === 0) {
            toastr.error('No hay empleados para enviar comprobantes');
            return;
        }

        // Filtrar empleados que tienen email válido
        const employeesWithEmail = employeesList.filter(emp => emp.email && emp.email !== 'null');

        if (employeesWithEmail.length === 0) {
            toastr.warning('Ningún empleado tiene email registrado');
            return;
        }

        // Confirmar envío masivo
        const confirmMsg = `¿Está seguro que desea enviar comprobantes a ${employeesWithEmail.length} empleado(s)?`;
        if (!confirm(confirmMsg)) {
            return;
        }

        // Cerrar modal de selector
        $('#employeeSelectorModal').modal('hide');

        // Mostrar progreso
        showMassEmailProgress(employeesWithEmail.length);

        // Enviar emails secuencialmente
        sendPayslipsBatch(currentPayrollId, employeesWithEmail, 0);
    });

    /**
     * Mostrar modal de progreso de envío masivo
     */
    function showMassEmailProgress(total) {
        // Crear modal de progreso si no existe
        if ($('#massEmailProgressModal').length === 0) {
            const modalHTML = `
                <div class="modal fade" id="massEmailProgressModal" tabindex="-1" data-backdrop="static" data-keyboard="false">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-success">
                                <h4 class="modal-title text-white">
                                    <i class="fas fa-paper-plane"></i> Enviando Comprobantes
                                </h4>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <h5>Enviando comprobantes por email...</h5>
                                    <p class="text-muted">Por favor espere, este proceso puede tomar varios minutos.</p>
                                </div>

                                <!-- Barra de progreso -->
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                         role="progressbar" id="massEmailProgressBar" style="width: 0%">
                                        <span id="massEmailProgressText">0%</span>
                                    </div>
                                </div>

                                <!-- Estadísticas -->
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="card border-success">
                                            <div class="card-body p-2">
                                                <h6 class="card-title mb-1">Enviados</h6>
                                                <span id="emailsSent">0</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-danger">
                                            <div class="card-body p-2">
                                                <h6 class="card-title mb-1">Fallidos</h6>
                                                <span id="emailsFailed">0</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-info">
                                            <div class="card-body p-2">
                                                <h6 class="card-title mb-1">Total</h6>
                                                <span id="emailsTotal">0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empleado actual -->
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i>
                                        <span id="currentEmployee">Iniciando...</span>
                                    </small>
                                </div>

                                <!-- Log de errores -->
                                <div id="errorLogContainer" style="display: none;" class="mt-3">
                                    <div class="alert alert-danger">
                                        <h6><i class="fas fa-exclamation-triangle"></i> Errores detectados:</h6>
                                        <ul id="errorLog" class="mb-0" style="max-height: 150px; overflow-y: auto;"></ul>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" id="closeMassEmailProgress" style="display: none;">
                                    <i class="fas fa-check"></i> Finalizar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHTML);
        }

        // Inicializar valores
        $('#emailsSent').text('0');
        $('#emailsFailed').text('0');
        $('#emailsTotal').text(total);
        $('#massEmailProgressBar').css('width', '0%');
        $('#massEmailProgressText').text('0%');
        $('#currentEmployee').text('Iniciando...');
        $('#errorLogContainer').hide();
        $('#errorLog').empty();
        $('#closeMassEmailProgress').hide();

        // Mostrar modal
        $('#massEmailProgressModal').modal('show');
    }

    /**
     * Enviar lote de comprobantes secuencialmente
     */
    function sendPayslipsBatch(payrollId, employees, index) {
        if (index >= employees.length) {
            // Completado
            completeMassEmail();
            return;
        }

        const employee = employees[index];
        const total = employees.length;
        const sent = parseInt($('#emailsSent').text());
        const failed = parseInt($('#emailsFailed').text());
        const processed = sent + failed;

        // Actualizar progreso
        const percentage = Math.round((processed / total) * 100);
        $('#massEmailProgressBar').css('width', percentage + '%');
        $('#massEmailProgressText').text(percentage + '%');
        $('#currentEmployee').text(`${employee.firstname} ${employee.lastname} (${employee.email})`);

        // Enviar email
        const basePath = getBasePath();
        const ajaxUrl = basePath + '/panel/reports/enviar-comprobante-email';

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                payroll_id: payrollId,
                employee_id: employee.id,
                email_to: employee.email
            },
            success: function(response) {
                if (response.success) {
                    $('#emailsSent').text(sent + 1);
                } else {
                    $('#emailsFailed').text(failed + 1);
                    logError(employee, response.message || 'Error desconocido');
                }
                // Continuar con el siguiente
                setTimeout(() => sendPayslipsBatch(payrollId, employees, index + 1), 500);
            },
            error: function(xhr, status, error) {
                $('#emailsFailed').text(failed + 1);
                logError(employee, error || 'Error de conexión');
                // Continuar con el siguiente
                setTimeout(() => sendPayslipsBatch(payrollId, employees, index + 1), 500);
            }
        });
    }

    /**
     * Registrar error en el log
     */
    function logError(employee, errorMessage) {
        $('#errorLogContainer').show();
        const errorItem = `<li>${employee.firstname} ${employee.lastname} (${employee.email}): ${errorMessage}</li>`;
        $('#errorLog').append(errorItem);
    }

    /**
     * Completar envío masivo
     */
    function completeMassEmail() {
        const sent = parseInt($('#emailsSent').text());
        const failed = parseInt($('#emailsFailed').text());
        const total = parseInt($('#emailsTotal').text());

        $('#massEmailProgressBar').css('width', '100%');
        $('#massEmailProgressText').text('100%');
        $('#currentEmployee').text('Proceso completado');
        $('#closeMassEmailProgress').show();

        // Mensaje final
        if (failed === 0) {
            toastr.success(`Se enviaron exitosamente ${sent} comprobante(s)`, 'Envío Completado');
        } else if (sent === 0) {
            toastr.error(`No se pudo enviar ningún comprobante. ${failed} fallo(s)`, 'Error en Envío');
        } else {
            toastr.warning(`Se enviaron ${sent} comprobante(s) exitosamente y ${failed} fallaron`, 'Envío Parcialmente Completado');
        }

        // Permitir cerrar modal
        $('#closeMassEmailProgress').on('click', function() {
            $('#massEmailProgressModal').modal('hide');
        });
    }

})();
