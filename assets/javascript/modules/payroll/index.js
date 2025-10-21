/**
 * Payroll Management Module
 * Handles DataTable initialization, filtering, processing, and progress tracking
 */
(function(global) {
    'use strict';

    const PayrollModule = {
        // Configuration
        config: {
            urls: {
                base: APP_CONFIG?.urls?.base || '',
                payrolls: APP_CONFIG?.urls?.payrolls || (function() {
                    // Dynamic fallback based on current location
                    const path = window.location.pathname;
                    const basePath = path.substring(0, path.indexOf('/panel'));
                    return basePath + '/panel/payrolls';
                })(),
            },
            csrfToken: null,
            tiposPlanilla: []
        },

        // State variables
        state: {
            currentPayrollId: null,
            currentPayrollDescription: null,
            totalEmployees: 0,
            startTime: null,
            reprocessStartTime: null,

            // Progress tracking
            progressInterval: null,
            reprocessProgressInterval: null,
            lastProgressPercentage: -1,
            progressStallCounter: 0,
            lastReprocessProgressPercentage: -1,
            reprocessProgressStallCounter: 0,

            // Constants
            MAX_STALL_CYCLES: 20 // 20 cycles of 3 seconds = 60 seconds without change
        },

        // DataTable instance
        dataTable: null,

        /**
         * Initialize the module
         */
        init: function(options = {}) {
            
            // Try multiple sources for CSRF token
            let csrfToken = options.csrfToken || 
                           options.csrf_token || 
                           APP_CONFIG?.csrfToken || 
                           APP_CONFIG?.csrf_token || 
                           APP_CONFIG?.config?.csrf_token || 
                           '';
            
            // Fallback: try to get from meta tag if available
            if (!csrfToken) {
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    csrfToken = metaTag.getAttribute('content');
                }
            }
            
            this.config.csrfToken = csrfToken;
            this.config.tiposPlanilla = options.tiposPlanilla || [];
            
            
            this.initializeDataTable();
            this.initializePayrollFilter();
            this.bindEvents();
            
        },

        /**
         * Initialize DataTable with AJAX
         */
        initializeDataTable: function() {
            const self = this;

            // Get tipo_planilla_id from URL for filter
            const urlParams = new URLSearchParams(window.location.search);
            const tipoPlanillaId = urlParams.get("tipo_planilla_id");

            // Build AJAX URL
            const ajaxUrl = `${this.config.urls.payrolls}/datatables-ajax`;

            console.log('Initializing DataTable with URL:', ajaxUrl);
            console.log('tipoPlanillaId:', tipoPlanillaId);

            this.dataTable = $("#payrollsTable").DataTable({
                "processing": true,
                "serverSide": true,
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "pageLength": 25,
                "ajax": {
                    "url": ajaxUrl,
                    "type": "POST",
                    "headers": {
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    "data": function(d) {
                        // Add filter parameter if exists
                        if (tipoPlanillaId) {
                            d.tipo_planilla_id = tipoPlanillaId;
                        }
                        console.log('DataTables request data:', d);
                        return d;
                    },
                    "error": function(xhr, error, code) {
                        console.error('DataTables AJAX Error:', {
                            xhr: xhr,
                            error: error,
                            code: code,
                            response: xhr.responseText
                        });

                        // If session expired, reload page
                        if (xhr.status === 200 && xhr.responseJSON && xhr.responseJSON.redirect) {
                            window.location.href = xhr.responseJSON.redirect;
                        }
                    }
                },
                "columns": [
                    { "data": "id", "name": "id" },
                    { "data": "descripcion", "name": "descripcion" },
                    { "data": "tipo_planilla", "name": "tipo_planilla_nombre", "className": "d-none d-xl-table-cell" },
                    { "data": "fecha", "name": "fecha" },
                    { "data": "estado", "name": "estado", "className": "text-center" },
                    { "data": "total_empleados", "name": "total_empleados", "className": "text-center d-none d-xl-table-cell" },
                    { "data": "acciones", "name": "acciones", "orderable": false, "searchable": false }
                ],
                "language": {
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron planillas",
                    "info": "Mostrando página _PAGE_ de _PAGES_ (_TOTAL_ registros en total)",
                    "infoEmpty": "No hay registros disponibles",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "loadingRecords": "Cargando...",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "order": [[3, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": [6] }
                ],
                "drawCallback": function(settings) {
                    // Update footer count information
                    const info = this.api().page.info();
                    $("#payrollCount").html(`Total: <strong>${info.recordsTotal}</strong> planillas`);

                    // Update last update timestamp
                    const now = new Date();
                    const timestamp = now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES');
                    $("#lastUpdate").text(timestamp);
                }
            });
        },

        /**
         * Initialize payroll filtering functionality
         */
        initializePayrollFilter: function() {
            // Get selected payroll type from sessionStorage
            const selectedPayrollType = sessionStorage.getItem("selectedPayrollType");
            const urlParams = new URLSearchParams(window.location.search);
            const urlTipoPlanilla = urlParams.get("tipo_planilla_id");
            
            if (selectedPayrollType && !urlTipoPlanilla) {
                // If there's a selected type in sessionStorage but not in URL, reload with filter
                const payrollTypeData = JSON.parse(selectedPayrollType);
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set("tipo_planilla_id", payrollTypeData.id);
                window.location.href = currentUrl.toString();
            } else if (urlTipoPlanilla) {
                // If there's a filter in URL, show filter information
                const tipoActual = this.config.tiposPlanilla.find(t => t.id == urlTipoPlanilla);
                
                if (tipoActual) {
                    $("#filterTypeName").text(tipoActual.descripcion);
                    $("#filterInfo").show();
                }
            }
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            const self = this;

            // Process payroll button
            $(document).on("click", ".process-btn", function() {
                self.state.currentPayrollId = $(this).data("id");
                self.state.currentPayrollDescription = $(this).data("description");
                $("#processPayrollName").text(self.state.currentPayrollDescription);
                // Clean modal before showing
                self.cleanProcessModal();
                $("#processModal").modal("show");
            });

            // Confirm process
            $("#confirmProcess").click(function() {
                if (self.state.currentPayrollId) {
                    // Block user actions immediately
                    self.blockUserActions('Iniciando procesamiento...');

                    self.loadInitialProcessData(self.state.currentPayrollId, function() {
                        self.startPayrollProcessing(self.state.currentPayrollId);
                    });
                }
            });

            // Reprocess payroll button
            $(document).on("click", ".reprocess-btn", function() {
                self.state.currentPayrollId = $(this).data("id");
                self.state.currentPayrollDescription = $(this).data("description");

                $("#reprocessPayrollName").text(self.state.currentPayrollDescription);
                // Clean modal before showing
                self.cleanReprocessModal();
                $("#reprocessModal").modal("show");
            });

            // Confirm reprocess
            $("#confirmReprocess").click(function() {
                if (self.state.currentPayrollId) {
                    // Block user actions immediately
                    self.blockUserActions('Iniciando reprocesamiento...');

                    self.loadInitialProgressData(self.state.currentPayrollId, function() {
                        self.startPayrollReprocessing(self.state.currentPayrollId);
                    });
                }
            });

            // Close payroll button
            $(document).on("click", ".close-btn", function() {
                self.state.currentPayrollId = $(this).data("id");
                const description = $(this).data("description");
                $("#closePayrollName").text(description);
                $("#closeModal").modal("show");
            });

            // Confirm close
            $("#confirmClose").click(function() {
                if (self.state.currentPayrollId) {
                    self.submitCloseAction(self.state.currentPayrollId);
                }
            });

            // Reopen payroll button (for CERRADA state)
            $(document).on("click", ".reopen-btn", function() {
                self.state.currentPayrollId = $(this).data("id");
                const description = $(this).data("description");
                $("#reopenPayrollName").text(description);
                // Clear previous motivo text
                $("#reopenMotivo").val("");
                $("#reopenModal").modal("show");
            });

            // Confirm reopen
            $("#confirmReopen").click(function() {
                const motivo = $("#reopenMotivo").val().trim();
                if (!motivo) {
                    toastr.warning("El motivo es obligatorio para reabrir una planilla.");
                    return;
                }
                if (self.state.currentPayrollId) {
                    // Block user actions
                    self.blockUserActions('Reabriendo planilla...');
                    self.submitFormAction("reopen", { reason: motivo });
                }
            });

            // Mark as pending button (for PROCESADA state)
            $(document).on("click", ".mark-pending-btn", function() {
                self.state.currentPayrollId = $(this).data("id");
                const description = $(this).data("description");
                $("#markPendingPayrollName").text(description);
                $("#markPendingModal").modal("show");
            });

            // Confirm mark as pending
            $("#confirmMarkPending").click(function() {
                const motivo = $("#markPendingMotivo").val().trim();
                if (!motivo) {
                    toastr.warning("El motivo es obligatorio para marcar la planilla como pendiente.");
                    return;
                }
                if (self.state.currentPayrollId) {
                    // Block user actions
                    self.blockUserActions('Marcando planilla como pendiente...');
                    self.submitFormAction("toPending", { motivo: motivo });
                }
            });

            // Cancel payroll button
            $(document).on("click", ".cancel-btn", function() {
                self.state.currentPayrollId = $(this).data("id");
                const description = $(this).data("description");
                $("#cancelPayrollName").text(description);
                $("#cancelModal").modal("show");
            });

            // Confirm cancel
            $("#confirmCancel").click(function() {
                if (self.state.currentPayrollId) {
                    self.submitCancelAction(self.state.currentPayrollId);
                }
            });

            // Delete payroll button
            $(document).on("click", ".delete-btn", function() {
                self.state.currentPayrollId = $(this).data("id");
                const description = $(this).data("description");
                $("#deletePayrollName").text(description);
                $("#deleteModal").modal("show");
            });

            // Confirm delete
            $("#confirmDelete").click(function() {
                if (self.state.currentPayrollId) {
                    // Block user actions
                    self.blockUserActions('Eliminando planilla...');
                    self.submitFormAction("delete");
                }
            });

            // Listen for payroll type changes from navbar
            window.addEventListener("payrollTypeChanged", function(event) {
                const payrollTypeData = event.detail;
                if (payrollTypeData && payrollTypeData.id) {
                    const currentUrl = new URL(window.location);
                    currentUrl.searchParams.set("tipo_planilla_id", payrollTypeData.id);
                    window.location.href = currentUrl.toString();
                }
            });

            // Global clear filter function
            window.clearFilter = function() {
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.delete("tipo_planilla_id");
                window.location.href = currentUrl.toString();
            };

            // Clean modals when they are hidden/closed
            $("#processModal").on("hidden.bs.modal", function() {
                self.cleanProcessModal();
            });

            $("#reprocessModal").on("hidden.bs.modal", function() {
                self.cleanReprocessModal();
            });

            // Auto-focus on motivo field when reopen modal is shown
            $("#reopenModal").on("shown.bs.modal", function() {
                $("#reopenMotivo").focus();
            });

            // Auto-focus on motivo field when mark pending modal is shown
            $("#markPendingModal").on("shown.bs.modal", function() {
                $("#markPendingMotivo").focus();
            });

            // Enable Enter key support for all modals
            this.enableEnterKeySupport();
        },

        /**
         * Reload page maintaining tipo_planilla_id filter
         */
        reloadWithFilter: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tipoPlanillaId = urlParams.get("tipo_planilla_id");

            let reloadUrl = window.location.pathname;
            if (tipoPlanillaId) {
                reloadUrl += '?tipo_planilla_id=' + tipoPlanillaId;
            }

            window.location.href = reloadUrl;
        },

        /**
         * Reload DataTable without page refresh
         */
        reloadDataTable: function() {
            if (this.dataTable && $.fn.DataTable.isDataTable('#payrollsTable')) {
                // Refresh AJAX DataTable
                this.dataTable.ajax.reload(null, false); // false = keep current page
            } else {
                // Fallback to page reload if DataTable is not initialized
                this.reloadWithFilter();
            }
        },

        /**
         * Show success notification and reload DataTable
         */
        showSuccessAndReload: function(message, title = 'Éxito') {
            toastr.success(message, title);
            setTimeout(() => {
                this.reloadDataTable();
            }, 500); // Small delay to let toastr show
        },

        /**
         * Clean process modal to reset all states
         */
        cleanProcessModal: function() {
            // Reset phases visibility
            $("#confirmationPhase").show();
            $("#processingPhase").hide();
            $("#completedPhase").hide();

            // Reset buttons visibility
            $("#confirmationButtons").show();
            $("#processingButtons").hide();
            $("#completedButtons").hide();

            // Reset progress bar
            $("#progressBar").css("width", "0%");
            $("#progressText").text("0%");

            // Reset progress information
            $("#employeesProgress").text("0 / 0");
            $("#conceptsProgress").text("0");
            $("#timeProgress").text("00:00");
            $("#currentPhase").text("Iniciando procesamiento...");

            // Reset completed phase information
            $("#completedPayrollInfo").text("-");
            $("#completionStats").html("");

            // Reset styles for completed phase (in case of previous error)
            $("#completedPhase .text-danger").removeClass("text-danger").addClass("text-success");
            $("#completedPhase h5").text("¡Procesamiento Completado!");
            $("#completedPhase i").removeClass("fa-exclamation-triangle").addClass("fa-check-circle");

            // Re-enable modal close button
            $("#modalCloseBtn").prop("disabled", false);

            // Clear any active intervals
            if (this.state.progressInterval) {
                clearInterval(this.state.progressInterval);
                this.state.progressInterval = null;
            }

            // Reset state variables
            this.state.startTime = null;
            this.state.lastProgressPercentage = -1;
            this.state.progressStallCounter = 0;

            // Ensure user actions are unblocked when cleaning modal
            this.unblockUserActions();
        },

        /**
         * Clean reprocess modal to reset all states
         */
        cleanReprocessModal: function() {
            // Reset phases visibility
            $("#reprocessConfirmationPhase").show();
            $("#reprocessProcessingPhase").hide();
            $("#reprocessCompletedPhase").hide();

            // Reset buttons visibility
            $("#reprocessConfirmationButtons").show();
            $("#reprocessProcessingButtons").hide();
            $("#reprocessCompletedButtons").hide();

            // Reset progress bar
            $("#reprocessProgressBar").css("width", "0%");
            $("#reprocessProgressText").text("0%");

            // Reset progress information
            $("#reprocessEmployeesProgress").text("0 / 0");
            $("#reprocessConceptsProgress").text("0");
            $("#reprocessTimeProgress").text("00:00");
            $("#reprocessCurrentPhase").text("Iniciando reprocesamiento...");

            // Reset completed phase information
            $("#reprocessCompletedPayrollInfo").text("-");
            $("#reprocessCompletionStats").html("");

            // Reset styles for completed phase (in case of previous error)
            $("#reprocessCompletedPhase .text-danger").removeClass("text-danger").addClass("text-success");
            $("#reprocessCompletedPhase h5").text("¡Reprocesamiento Completado!");
            $("#reprocessCompletedPhase i").removeClass("fa-exclamation-triangle").addClass("fa-check-circle");

            // Re-enable modal close button
            $("#reprocessModalCloseBtn").prop("disabled", false);

            // Clear any active intervals
            if (this.state.reprocessProgressInterval) {
                clearInterval(this.state.reprocessProgressInterval);
                this.state.reprocessProgressInterval = null;
            }

            // Reset state variables
            this.state.reprocessStartTime = null;
            this.state.lastReprocessProgressPercentage = -1;
            this.state.reprocessProgressStallCounter = 0;

            // Ensure user actions are unblocked when cleaning modal
            this.unblockUserActions();
        },

        /**
         * Enable Enter key support for all modals with intelligent validation
         */
        enableEnterKeySupport: function() {
            const self = this;

            // Modal configurations with their respective action buttons and validation rules
            const modalConfigs = {
                '#processModal': {
                    actionButton: '#confirmProcess',
                    phases: ['#confirmationPhase'], // Only allow Enter in confirmation phase
                    validate: false,
                    completedPhase: '#completedPhase',
                    completedButton: '#completedButtons .btn[data-dismiss="modal"]'
                },
                '#reprocessModal': {
                    actionButton: '#confirmReprocess',
                    phases: ['#reprocessConfirmationPhase'], // Only allow Enter in confirmation phase
                    validate: false,
                    completedPhase: '#reprocessCompletedPhase',
                    completedButton: '#reprocessCompletedButtons .btn[data-dismiss="modal"]'
                },
                '#closeModal': {
                    actionButton: '#confirmClose',
                    validate: false
                },
                '#cancelModal': {
                    actionButton: '#confirmCancel',
                    validate: false
                },
                '#deleteModal': {
                    actionButton: '#confirmDelete',
                    validate: false
                },
                '#reopenModal': {
                    actionButton: '#confirmReopen',
                    validate: true,
                    validationField: '#reopenMotivo',
                    validationMessage: 'El motivo es obligatorio para reabrir una planilla.'
                },
                '#markPendingModal': {
                    actionButton: '#confirmMarkPending',
                    validate: true,
                    validationField: '#markPendingMotivo',
                    validationMessage: 'El motivo es obligatorio para marcar la planilla como pendiente.'
                }
            };

            // Add keydown event listener to each modal
            Object.keys(modalConfigs).forEach(modalId => {
                const config = modalConfigs[modalId];

                $(modalId).on('keydown', function(e) {
                    // Only trigger on Enter key
                    if (e.which === 13 && !e.shiftKey) {
                        e.preventDefault();

                        // Check if we're in the completed phase (process/reprocess modals)
                        if (config.completedPhase && $(config.completedPhase).is(':visible')) {
                            const $completedButton = $(config.completedButton);
                            if ($completedButton.is(':visible') && !$completedButton.prop('disabled')) {
                                // Don't block actions when closing completed modal
                                $completedButton.trigger('click');
                                return;
                            }
                        }

                        // Check if modal is in the correct phase (for process/reprocess modals)
                        if (config.phases) {
                            let inCorrectPhase = false;
                            config.phases.forEach(phase => {
                                if ($(phase).is(':visible')) {
                                    inCorrectPhase = true;
                                }
                            });

                            if (!inCorrectPhase) {
                                return; // Don't allow Enter if not in correct phase
                            }
                        }

                        // Check if action button is visible and enabled
                        const $actionButton = $(config.actionButton);
                        if (!$actionButton.is(':visible') || $actionButton.prop('disabled')) {
                            return;
                        }

                        // Perform validation if required
                        if (config.validate) {
                            const fieldValue = $(config.validationField).val().trim();
                            if (!fieldValue) {
                                toastr.warning(config.validationMessage, 'Validación');
                                $(config.validationField).focus();
                                return;
                            }
                        }

                        // Block further actions and trigger the button click
                        self.blockUserActions('Procesando acción...');
                        $actionButton.trigger('click');
                    }
                });
            });
        },

        /**
         * Block user actions to prevent accidental clicks
         */
        blockUserActions: function(message = 'Procesando...') {
            // Block DataTable interactions
            if (this.dataTable) {
                $('.dataTables_wrapper').addClass('processing-blocked').css({
                    'pointer-events': 'none',
                    'opacity': '0.6'
                });
            }

            // Block all action buttons in the table
            $('.process-btn, .reprocess-btn, .close-btn, .reopen-btn, .mark-pending-btn, .cancel-btn, .delete-btn').prop('disabled', true);

            // Show loading indicator
            if (!$('#globalLoadingIndicator').length) {
                $('body').append(`
                    <div id="globalLoadingIndicator" style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: 9999;
                        background: #17a2b8;
                        color: white;
                        padding: 10px 15px;
                        border-radius: 5px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                        font-size: 14px;
                    ">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        ${message}
                    </div>
                `);
            }
        },

        /**
         * Unblock user actions after process completion
         */
        unblockUserActions: function() {
            // Unblock DataTable interactions
            if (this.dataTable) {
                $('.dataTables_wrapper').removeClass('processing-blocked').css({
                    'pointer-events': 'auto',
                    'opacity': '1'
                });
            }

            // Re-enable action buttons
            $('.process-btn, .reprocess-btn, .close-btn, .reopen-btn, .mark-pending-btn, .cancel-btn, .delete-btn').prop('disabled', false);

            // Remove loading indicator
            $('#globalLoadingIndicator').remove();
        },

        /**
         * Submit close action via AJAX
         */
        submitCloseAction: function(payrollId) {
            const self = this;

            // Block user actions during close operation
            this.blockUserActions('Cerrando planilla...');

            // Get tipo_planilla_id from URL to maintain filter
            const urlParams = new URLSearchParams(window.location.search);
            const tipoPlanillaId = urlParams.get("tipo_planilla_id");

            const ajaxData = {
                csrf_token: this.config.csrfToken
            };

            // Include tipo_planilla_id if exists
            if (tipoPlanillaId) {
                ajaxData.tipo_planilla_id = tipoPlanillaId;
            }

            $.ajax({
                url: `${this.config.urls.payrolls}/${payrollId}/close`,
                method: "POST",
                data: ajaxData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $("#closeModal").modal("hide");

                        // Show success notification
                        toastr.success(response.message, "Planilla Cerrada");

                        // Reload DataTable after a short delay
                        setTimeout(() => {
                            self.reloadDataTable();
                            // Unblock user actions after reload
                            self.unblockUserActions();
                        }, 500);
                    } else {
                        toastr.error(response.message || "Error al cerrar la planilla", "Error");
                        // Unblock on error
                        self.unblockUserActions();
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = "Error al cerrar la planilla";

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    toastr.error(errorMessage, "Error");
                    // Unblock on error
                    self.unblockUserActions();
                }
            });
        },

        /**
         * Submit cancel action via AJAX
         */
        submitCancelAction: function(payrollId) {
            const self = this;

            // Block user actions during cancel operation
            this.blockUserActions('Anulando planilla...');

            // Get tipo_planilla_id from URL to maintain filter
            const urlParams = new URLSearchParams(window.location.search);
            const tipoPlanillaId = urlParams.get("tipo_planilla_id");

            const ajaxData = {
                csrf_token: this.config.csrfToken
            };

            // Include tipo_planilla_id if exists
            if (tipoPlanillaId) {
                ajaxData.tipo_planilla_id = tipoPlanillaId;
            }

            $.ajax({
                url: `${this.config.urls.payrolls}/${payrollId}/cancel`,
                method: "POST",
                data: ajaxData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $("#cancelModal").modal("hide");

                        // Show success notification
                        toastr.success(response.message, "Planilla Anulada");

                        // Reload DataTable after a short delay
                        setTimeout(() => {
                            self.reloadDataTable();
                            // Unblock user actions after reload
                            self.unblockUserActions();
                        }, 500);
                    } else {
                        toastr.error(response.message || "Error al anular la planilla", "Error");
                        // Unblock on error
                        self.unblockUserActions();
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = "Error al anular la planilla";

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    toastr.error(errorMessage, "Error");
                    // Unblock on error
                    self.unblockUserActions();
                }
            });
        },

        /**
         * Submit form action via AJAX (reopen, delete, toPending)
         */
        submitFormAction: function(action, data = {}) {
            const self = this;

            // Prepare AJAX data
            const ajaxData = {
                csrf_token: this.config.csrfToken,
                ...data
            };

            // Mantener filtro de tipo_planilla_id si existe en la URL
            const urlParams = new URLSearchParams(window.location.search);
            const tipoPlanillaId = urlParams.get("tipo_planilla_id");
            if (tipoPlanillaId) {
                ajaxData.tipo_planilla_id = tipoPlanillaId;
            }

            // Determine which modal to close based on action
            let modalToClose = '';
            let successMessage = 'Operación completada exitosamente';
            let successTitle = 'Éxito';

            switch(action) {
                case 'reopen':
                    modalToClose = '#reopenModal';
                    successMessage = 'Planilla reabierta exitosamente';
                    successTitle = 'Planilla Reabierta';
                    break;
                case 'toPending':
                    modalToClose = '#markPendingModal';
                    successMessage = 'Planilla marcada como pendiente exitosamente';
                    successTitle = 'Estado Actualizado';
                    break;
                case 'delete':
                    modalToClose = '#deleteModal';
                    successMessage = 'Planilla eliminada exitosamente';
                    successTitle = 'Planilla Eliminada';
                    break;
            }

            $.ajax({
                url: `${this.config.urls.payrolls}/${this.state.currentPayrollId}/${action}`,
                method: "POST",
                data: ajaxData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        if (modalToClose) {
                            $(modalToClose).modal("hide");
                        }

                        // Show success notification
                        toastr.success(response.message || successMessage, successTitle);

                        // Reload DataTable after a short delay
                        setTimeout(() => {
                            self.reloadDataTable();
                            // Unblock user actions after reload
                            self.unblockUserActions();
                        }, 500);
                    } else {
                        toastr.error(response.message || "Error en la operación", "Error");
                        // Unblock on error
                        self.unblockUserActions();
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = "Error en la operación";

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    toastr.error(errorMessage, "Error");
                    // Unblock on error
                    self.unblockUserActions();
                }
            });
        },

        /**
         * Load initial process data
         */
        loadInitialProcessData: function(payrollId, callback) {
            const self = this;
            
            $.ajax({
                url: `${this.config.urls.payrolls}/${payrollId}/progress`,
                method: "GET",
                success: function(progress) {
                    self.state.totalEmployees = progress.total || 0;
                    
                    // Initialize process modal with initial data
                    $("#employeesProgress").text("0 / " + self.state.totalEmployees);
                    $("#conceptsProgress").text("0");
                    $("#currentPhase").text("Iniciando procesamiento...");
                    
                    if (callback) callback();
                },
                error: function(xhr, status, error) {
                    // Continue with default values
                    $("#employeesProgress").text("0 / 0");
                    $("#conceptsProgress").text("0");
                    
                    if (callback) callback();
                }
            });
        },

        /**
         * Load initial progress data for reprocessing
         */
        loadInitialProgressData: function(payrollId, callback) {
            const self = this;
            
            $.ajax({
                url: `${this.config.urls.payrolls}/${payrollId}/progress`,
                method: "GET",
                success: function(progress) {
                    self.state.totalEmployees = progress.total || 0;
                    
                    // Initialize reprocess modal with initial data
                    $("#reprocessEmployeesProgress").text("0 / " + self.state.totalEmployees);
                    $("#reprocessConceptsProgress").text("0");
                    $("#reprocessCurrentPhase").text("Iniciando reprocesamiento...");
                    
                    if (callback) callback();
                },
                error: function(xhr, status, error) {
                    // Continue with default values
                    $("#reprocessEmployeesProgress").text("0 / 0");
                    $("#reprocessConceptsProgress").text("0");
                    
                    if (callback) callback();
                }
            });
        },

        /**
         * Start payroll processing with progress tracking
         */
        startPayrollProcessing: function(payrollId) {
            
            // Get selected payroll type from sessionStorage
            let tipoPlanillaId = null;
            let payrollTypeData = null;
            
            try {
                const selectedPayrollType = sessionStorage.getItem("selectedPayrollType");
                if (selectedPayrollType) {
                    payrollTypeData = JSON.parse(selectedPayrollType);
                    tipoPlanillaId = payrollTypeData.id;
                } else {
                }
            } catch (e) {
                console.error("Error parsing selected payroll type from sessionStorage:", e);
            }
            
            // If not found in sessionStorage, try using global navbar function
            if (!tipoPlanillaId && typeof window.getSelectedPayrollType === "function") {
                try {
                    payrollTypeData = window.getSelectedPayrollType();
                    if (payrollTypeData && payrollTypeData.id) {
                        tipoPlanillaId = payrollTypeData.id;
                    } else {
                    }
                } catch (e) {
                    console.error("Error getting payroll type from global function:", e);
                }
            }
            
            // If still no payroll type, try waiting and retry
            if (!tipoPlanillaId) {
                const self = this;
                setTimeout(() => {
                    const retryData = sessionStorage.getItem("selectedPayrollType");
                    if (retryData) {
                        try {
                            const parsed = JSON.parse(retryData);
                            self.startPayrollProcessing(payrollId); // Retry the function
                            return;
                        } catch (e) {
                            console.error("Retry parsing error:", e);
                        }
                    }
                    
                    // If after waiting it still doesn't work, show error
                    toastr.error("No se ha seleccionado un tipo de planilla. Por favor seleccione un tipo en el dropdown de la barra de navegación y vuelva a intentar.", "Error de Configuración");
                }, 2000);
                return;
            }
            
            
            // Switch to processing phase
            $("#confirmationPhase").hide();
            $("#confirmationButtons").hide();
            $("#processingPhase").show();
            $("#processingButtons").show();
            
            // Disable modal close
            $("#modalCloseBtn").prop("disabled", true);
            
            // Start timer
            this.state.startTime = Date.now();
            this.updateTimer();
            
            // Start asynchronous processing with payroll type
            const self = this;
            $.ajax({
                url: `${this.config.urls.payrolls}/${payrollId}/process/${tipoPlanillaId}`,
                method: "POST",
                data: {
                    csrf_token: this.config.csrfToken
                },
                success: function(response) {
                    
                    // Server returns immediately, processing continues in background
                    if (response.success) {
                        // Continue with polling - DON'T call handleProcessingComplete yet
                    } else {
                        self.handleProcessingError({responseJSON: response}, "error", response.message || "Error iniciando procesamiento");
                    }
                },
                error: function(xhr, status, error) {
                    self.handleProcessingError(xhr, status, error);
                }
            });
            
            // Start progress polling immediately after async call
            this.state.lastProgressPercentage = -1; // Reset progress counter
            this.state.progressStallCounter = 0;
            this.startProgressPolling(payrollId);
        },

        /**
         * Start progress polling
         */
        startProgressPolling: function(payrollId) {
            const self = this;
            this.state.progressInterval = setInterval(function() {
                $.ajax({
                    url: `${self.config.urls.payrolls}/${payrollId}/progress`,
                    method: "GET",
                    success: function(progress) {
                        self.updateProgressDisplay(progress);
                    },
                    error: function() {
                        // If there's an error in polling, keep trying
                    }
                });
            }, 3000); // Every 3 seconds
        },

        /**
         * Update progress display
         */
        updateProgressDisplay: function(progress) {
            // Use percentage from backend, don't calculate it again
            const percentage = progress.percentage || 0;
            
            // Validate if progress is stalled (only for active processing)
            if (progress.status !== "completed") {
                if (percentage === this.state.lastProgressPercentage) {
                    this.state.progressStallCounter++;
                    
                    // If progress is stalled for too long, show error
                    if (this.state.progressStallCounter >= this.state.MAX_STALL_CYCLES) {
                        clearInterval(this.state.progressInterval);
                        this.handleProcessingError(null, "stalled", "El procesamiento parece haberse estancado. Verifique el estado manualmente.");
                        return;
                    }
                } else {
                    // Progress advanced, reset counter
                    this.state.progressStallCounter = 0;
                    this.state.lastProgressPercentage = percentage;
                }
            }
            
            // Update progress bar
            $("#progressBar").css("width", percentage + "%");
            $("#progressText").text(percentage + "%");
            
            // Update statistics
            $("#employeesProgress").text(progress.processed + " / " + progress.total);
            $("#conceptsProgress").text(progress.concepts_calculated || 0);
            $("#currentPhase").text(progress.phase || "Procesando empleados...");
            
            // If completed or status is "completed"
            if (percentage >= 100 || progress.status === "completed") {
                clearInterval(this.state.progressInterval);
                
                // Simulate completion response if no data from server
                if (!progress.response) {
                    const fakeResponse = {
                        stats: {
                            employees: progress.processed || this.state.totalEmployees,
                            concepts: progress.concepts_calculated || 0
                        }
                    };
                    this.handleProcessingComplete(fakeResponse);
                }
            }
        },

        /**
         * Update timer
         */
        updateTimer: function() {
            if (!this.state.startTime) return;
            
            setInterval(() => {
                const elapsed = Date.now() - this.state.startTime;
                const minutes = Math.floor(elapsed / 60000);
                const seconds = Math.floor((elapsed % 60000) / 1000);
                $("#timeProgress").text(
                    String(minutes).padStart(2, "0") + ":" + 
                    String(seconds).padStart(2, "0")
                );
            }, 1000);
        },

        /**
         * Handle processing completion
         */
        handleProcessingComplete: function(response) {
            clearInterval(this.state.progressInterval);

            // Calculate total generation time
            const totalTime = this.state.startTime ? Date.now() - this.state.startTime : 0;
            const minutes = Math.floor(totalTime / 60000);
            const seconds = Math.floor((totalTime % 60000) / 1000);
            const timeDisplay = String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");

            const employeesProcessed = response && response.stats ? response.stats.employees : this.state.totalEmployees;
            const conceptsCalculated = response && response.stats ? response.stats.concepts : $("#conceptsProgress").text();

            // Show completion phase
            $("#processingPhase").hide();
            $("#processingButtons").hide();
            $("#completedPhase").show();
            $("#completedButtons").show();

            // Update payroll info in completed phase
            const payrollInfo = `ID #${this.state.currentPayrollId} - ${this.state.currentPayrollDescription}`;
            $("#completedPayrollInfo").text(payrollInfo);

            // Show final statistics with generation time
            const statsHtml = `
                <div class="row text-center">
                    <div class="col-md-4">
                        <strong>${employeesProcessed}</strong><br>
                        <small>Empleados Procesados</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${conceptsCalculated}</strong><br>
                        <small>Conceptos Calculados</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${timeDisplay}</strong><br>
                        <small>Tiempo de Generación</small>
                    </div>
                </div>
            `;
            $("#completionStats").html(statsHtml);

            // Show success notification
            toastr.success(`Planilla procesada exitosamente. ${employeesProcessed} empleados procesados en ${timeDisplay}.`, "Procesamiento Completado");

            // Re-enable modal close
            $("#modalCloseBtn").prop("disabled", false);

            // Auto-reload DataTable after 2 seconds
            setTimeout(() => {
                this.reloadDataTable();
                // Unblock user actions after reload
                this.unblockUserActions();
            }, 2000);
        },

        /**
         * Handle processing errors
         */
        handleProcessingError: function(xhr, status, error) {
            clearInterval(this.state.progressInterval);
            
            $("#processingPhase").hide();
            $("#processingButtons").hide();
            $("#completedPhase").show();
            $("#completedButtons").show();
            
            // Show error
            $("#completedPhase .text-success").removeClass("text-success").addClass("text-danger");
            $("#completedPhase h5").text("Error en el Procesamiento");
            $("#completedPhase i").removeClass("fa-check-circle").addClass("fa-exclamation-triangle");
            
            let errorMessage = "Error desconocido";
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (status === "timeout") {
                errorMessage = "El procesamiento está tardando más de lo esperado. Verifique el estado manualmente.";
            } else if (status === "stalled") {
                errorMessage = error;
            }
            
            $("#completionStats").html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    ${errorMessage}
                </div>
            `);
            
            // Re-enable modal close
            $("#modalCloseBtn").prop("disabled", false);

            // Unblock user actions on error
            this.unblockUserActions();
        },

        // ============= REPROCESSING FUNCTIONS =============

        /**
         * Start payroll reprocessing with progress tracking
         */
        startPayrollReprocessing: function(payrollId) {

            // Get selected payroll type from sessionStorage
            let tipoPlanillaId = null;
            let payrollTypeData = null;

            try {
                const selectedPayrollType = sessionStorage.getItem("selectedPayrollType");
                if (selectedPayrollType) {
                    payrollTypeData = JSON.parse(selectedPayrollType);
                    tipoPlanillaId = payrollTypeData.id;
                } else {
                }
            } catch (e) {
                console.error("Error parsing selected payroll type from sessionStorage for reprocess:", e);
            }

            // If not found in sessionStorage, try using global navbar function
            if (!tipoPlanillaId && typeof window.getSelectedPayrollType === "function") {
                try {
                    payrollTypeData = window.getSelectedPayrollType();
                    if (payrollTypeData && payrollTypeData.id) {
                        tipoPlanillaId = payrollTypeData.id;
                    } else {
                    }
                } catch (e) {
                    console.error("Error getting payroll type from global function for reprocess:", e);
                }
            }

            // If still no payroll type, show error
            if (!tipoPlanillaId) {
                toastr.error("No se ha seleccionado un tipo de planilla. Por favor seleccione un tipo en el dropdown de la barra de navegación y vuelva a intentar.", "Error de Configuración");
                return;
            }

            // Get checkbox value for validateSituacion
            const validateSituacion = $("#validateSituacion").is(":checked") ? 1 : 0;
            console.log("Validar situación del empleado:", validateSituacion ? "SÍ" : "NO");

            // Get checkbox value for usarSalarioPlanilla
            const usarSalarioPlanilla = $("#usarSalarioPlanilla").is(":checked") ? 1 : 0;
            console.log("Usar salario de planilla procesada:", usarSalarioPlanilla ? "SÍ" : "NO");

            // Switch to processing phase
            $("#reprocessConfirmationPhase").hide();
            $("#reprocessConfirmationButtons").hide();
            $("#reprocessProcessingPhase").show();
            $("#reprocessProcessingButtons").show();

            // Disable modal close
            $("#reprocessModalCloseBtn").prop("disabled", true);

            // Start timer
            this.state.reprocessStartTime = Date.now();
            this.updateReprocessTimer();

            // Start asynchronous reprocessing with payroll type
            const self = this;
            const reprocessUrl = `${this.config.urls.payrolls}/${payrollId}/reprocess/${tipoPlanillaId}`;



            // Last resort: try to get token from meta tag with jQuery
            if (!this.config.csrfToken || this.config.csrfToken === '') {
                const metaToken = $('meta[name="csrf-token"]').attr('content');
                if (metaToken) {
                    this.config.csrfToken = metaToken;
                }
            }

            const ajaxData = {
                csrf_token: this.config.csrfToken,
                validate_situacion: validateSituacion,
                usar_salario_planilla: usarSalarioPlanilla
            };

            $.ajax({
                url: reprocessUrl,
                method: "POST",
                data: ajaxData,
                success: function(response) {

                    // Server returns immediately, processing continues in background
                    if (response.success) {
                        // Continue with polling - DON'T call handleReprocessingComplete yet
                    } else {
                        self.handleReprocessingError({responseJSON: response}, "error", response.message || "Error iniciando reprocesamiento");
                    }
                },
                error: function(xhr, status, error) {
                    self.handleReprocessingError(xhr, status, error);
                }
            });

            // Start progress polling immediately after async call
            this.state.lastReprocessProgressPercentage = -1; // Reset progress counter
            this.state.reprocessProgressStallCounter = 0;
            this.startReprocessProgressPolling(payrollId);
        },

        /**
         * Start reprocess progress polling
         */
        startReprocessProgressPolling: function(payrollId) {
            const self = this;
            this.state.reprocessProgressInterval = setInterval(function() {
                $.ajax({
                    url: `${self.config.urls.payrolls}/${payrollId}/progress`,
                    method: "GET",
                    timeout: 8000, // 8 seconds timeout
                    cache: false,
                    headers: {
                        "Cache-Control": "no-cache",
                        "Pragma": "no-cache"
                    },
                    success: function(progress) {
                        self.updateReprocessProgressDisplay(progress);
                    },
                    error: function(xhr, status, error) {
                        // If there's an error in polling, keep trying
                        
                        // If timeout or error is due to server busy, keep trying
                        if (status === "timeout" || xhr.readyState === 0) {
                        }
                    }
                });
            }, 3000); // Every 3 seconds
        },

        /**
         * Update reprocess progress display
         */
        updateReprocessProgressDisplay: function(progress) {
            // Use percentage from backend, don't calculate it again
            const percentage = progress.percentage || 0;
            
            
            // Validate if progress is stalled (only for active reprocessing)
            if (progress.status !== "completed") {
                if (percentage === this.state.lastReprocessProgressPercentage) {
                    this.state.reprocessProgressStallCounter++;
                    
                    // If progress is stalled for too long, show error
                    if (this.state.reprocessProgressStallCounter >= this.state.MAX_STALL_CYCLES) {
                        clearInterval(this.state.reprocessProgressInterval);
                        this.handleReprocessingError(null, "stalled", "El reprocesamiento parece haberse estancado. Verifique el estado manualmente.");
                        return;
                    }
                } else {
                    // Progress advanced, reset counter
                    this.state.reprocessProgressStallCounter = 0;
                    this.state.lastReprocessProgressPercentage = percentage;
                }
            }
            
            // Update progress bar
            $("#reprocessProgressBar").css("width", percentage + "%");
            $("#reprocessProgressText").text(percentage + "%");
            
            // Update statistics
            $("#reprocessEmployeesProgress").text(progress.processed + " / " + progress.total);
            $("#reprocessConceptsProgress").text(progress.concepts_calculated || 0);
            $("#reprocessCurrentPhase").text(progress.phase || "Reprocesando empleados...");
            
            // If completed or status is "completed"
            if (percentage >= 100 || progress.status === "completed") {
                clearInterval(this.state.reprocessProgressInterval);
                
                // Simulate completion response if no data from server
                if (!progress.response) {
                    const fakeResponse = {
                        stats: {
                            employees: progress.processed || this.state.totalEmployees,
                            concepts: progress.concepts_calculated || 0
                        }
                    };
                    this.handleReprocessingComplete(fakeResponse);
                }
            }
        },

        /**
         * Update reprocess timer
         */
        updateReprocessTimer: function() {
            if (!this.state.reprocessStartTime) return;
            
            setInterval(() => {
                const elapsed = Date.now() - this.state.reprocessStartTime;
                const minutes = Math.floor(elapsed / 60000);
                const seconds = Math.floor((elapsed % 60000) / 1000);
                $("#reprocessTimeProgress").text(
                    String(minutes).padStart(2, "0") + ":" + 
                    String(seconds).padStart(2, "0")
                );
            }, 1000);
        },

        /**
         * Handle reprocessing completion
         */
        handleReprocessingComplete: function(response) {
            clearInterval(this.state.reprocessProgressInterval);

            // Calculate total generation time
            const totalTime = this.state.reprocessStartTime ? Date.now() - this.state.reprocessStartTime : 0;
            const minutes = Math.floor(totalTime / 60000);
            const seconds = Math.floor((totalTime % 60000) / 1000);
            const timeDisplay = String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");

            const employeesProcessed = response && response.stats ? response.stats.employees : this.state.totalEmployees;
            const conceptsCalculated = response && response.stats ? response.stats.concepts : $("#reprocessConceptsProgress").text();

            // Show completion phase
            $("#reprocessProcessingPhase").hide();
            $("#reprocessProcessingButtons").hide();
            $("#reprocessCompletedPhase").show();
            $("#reprocessCompletedButtons").show();

            // Show payroll information
            const payrollInfo = `ID #${this.state.currentPayrollId} - ${this.state.currentPayrollDescription}`;
            $("#reprocessCompletedPayrollInfo").text(payrollInfo);

            // Show final statistics with generation time
            const statsHtml = `
                <div class="row text-center">
                    <div class="col-md-4">
                        <strong>${employeesProcessed}</strong><br>
                        <small>Empleados Reprocesados</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${conceptsCalculated}</strong><br>
                        <small>Conceptos Recalculados</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${timeDisplay}</strong><br>
                        <small>Tiempo de Generación</small>
                    </div>
                </div>
            `;
            $("#reprocessCompletionStats").html(statsHtml);

            // Show success notification
            toastr.success(`Planilla reprocesada exitosamente. ${employeesProcessed} empleados reprocesados en ${timeDisplay}.`, "Reprocesamiento Completado");

            // Re-enable modal close
            $("#reprocessModalCloseBtn").prop("disabled", false);

            // Auto-reload DataTable after 2 seconds
            setTimeout(() => {
                this.reloadDataTable();
                // Unblock user actions after reload
                this.unblockUserActions();
            }, 2000);
        },

        /**
         * Handle reprocessing errors
         */
        handleReprocessingError: function(xhr, status, error) {
            clearInterval(this.state.reprocessProgressInterval);
            
            $("#reprocessProcessingPhase").hide();
            $("#reprocessProcessingButtons").hide();
            $("#reprocessCompletedPhase").show();
            $("#reprocessCompletedButtons").show();
            
            // Show error
            $("#reprocessCompletedPhase .text-success").removeClass("text-success").addClass("text-danger");
            $("#reprocessCompletedPhase h5").text("Error en el Reprocesamiento");
            $("#reprocessCompletedPhase i").removeClass("fa-check-circle").addClass("fa-exclamation-triangle");
            
            let errorMessage = "Error desconocido";
            if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (status === "timeout") {
                errorMessage = "El reprocesamiento está tardando más de lo esperado. Verifique el estado manualmente.";
            } else if (status === "stalled") {
                errorMessage = error;
            }
            
            $("#reprocessCompletionStats").html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    ${errorMessage}
                </div>
            `);
            
            // Re-enable modal close
            $("#reprocessModalCloseBtn").prop("disabled", false);

            // Unblock user actions on error
            this.unblockUserActions();
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        
        // Wait for APP_CONFIG to be available
        if (typeof APP_CONFIG !== 'undefined') {
            
            PayrollModule.init({
                csrfToken: APP_CONFIG.csrfToken || APP_CONFIG.csrf_token || '',
                csrf_token: APP_CONFIG.csrf_token || APP_CONFIG.csrfToken || '',
                tiposPlanilla: APP_CONFIG.tiposPlanilla || []
            });
        } else {
            // Fallback initialization
            console.warn('APP_CONFIG not available, using fallback initialization');
            PayrollModule.init();
        }
    });

    // Export module to global scope
    global.PayrollModule = PayrollModule;

    // Export specific functions globally for HTML onclick handlers
    global.PayrollModule.reloadWithFilter = PayrollModule.reloadWithFilter.bind(PayrollModule);
    global.PayrollModule.reloadDataTable = PayrollModule.reloadDataTable.bind(PayrollModule);
    global.PayrollModule.showSuccessAndReload = PayrollModule.showSuccessAndReload.bind(PayrollModule);

})(window);

/**
 * Selector de Empleados para Comprobantes Individuales
 * Funciones globales para dropdown de reportes
 */

// Variables globales para el selector
let currentPayrollId = null;
let currentAction = null; // 'view' o 'email'
let employeesList = [];

/**
 * Mostrar modal selector de empleados
 * @param {number} payrollId - ID de la planilla
 * @param {string} action - 'view' para visualizar, 'email' para enviar por correo
 */
function showEmployeeSelector(payrollId, action) {
    currentPayrollId = payrollId;
    currentAction = action;

    // Actualizar título según acción
    const modalTitle = action === 'email'
        ? '<i class="fas fa-envelope"></i> Enviar Comprobante por Email'
        : '<i class="fas fa-file-pdf"></i> Visualizar Comprobante Individual';

    $('#employeeSelectorModalLabel').html(modalTitle);

    // Cargar empleados de la planilla
    loadPayrollEmployees(payrollId);

    // Mostrar modal
    $('#employeeSelectorModal').modal('show');
}

/**
 * Cargar empleados de la planilla vía AJAX
 */
function loadPayrollEmployees(payrollId) {
    const basePath = window.location.pathname.substring(0, window.location.pathname.indexOf('/panel'));
    const ajaxUrl = basePath + '/panel/payrolls/' + payrollId + '/employees-list-simple';

    $('#employeesListBody').html('<tr><td colspan="4" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando empleados...</td></tr>');

    $.ajax({
        url: ajaxUrl,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                employeesList = response.data.employees || [];
                const payrollDesc = response.data.payroll_descripcion || 'N/A';

                $('#selectedPayrollDesc').text(payrollDesc);

                renderEmployeesList(employeesList);
            } else {
                $('#employeesListBody').html('<tr><td colspan="4" class="text-center text-danger">Error al cargar empleados</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error al cargar empleados:', error);
            $('#employeesListBody').html('<tr><td colspan="4" class="text-center text-danger">Error de conexión</td></tr>');
        }
    });
}

/**
 * Renderizar lista de empleados en la tabla
 */
function renderEmployeesList(employees) {
    if (!employees || employees.length === 0) {
        $('#employeesListBody').html('<tr><td colspan="4" class="text-center text-muted">No hay empleados en esta planilla</td></tr>');
        return;
    }

    let html = '';
    employees.forEach(function(emp) {
        const nombreCompleto = emp.lastname + ', ' + emp.firstname;
        const cargo = emp.cargo_name || emp.position_name || 'N/A';

        const actionBtn = currentAction === 'email'
            ? '<button class="btn btn-success btn-sm" onclick="sendEmployeePayslip(' + currentPayrollId + ', ' + emp.id + ', \'' + emp.email + '\'); return false;" title="Enviar por email"><i class="fas fa-envelope"></i></button>'
            : '<a href="' + getBasePath() + '/panel/reports/comprobante-individual/' + currentPayrollId + '/' + emp.id + '" target="_blank" class="btn btn-primary btn-sm" title="Ver PDF"><i class="fas fa-file-pdf"></i></a>';

        html += '<tr>';
        html += '<td>' + (emp.document_id || 'N/A') + '</td>';
        html += '<td>' + nombreCompleto + '</td>';
        html += '<td>' + cargo + '</td>';
        html += '<td>' + actionBtn + '</td>';
        html += '</tr>';
    });

    $('#employeesListBody').html(html);
}

/**
 * Enviar comprobante por email
 */
function sendEmployeePayslip(payrollId, employeeId, employeeEmail) {
    if (!employeeEmail || employeeEmail === 'null') {
        toastr.error('El empleado no tiene email registrado');
        return;
    }

    // Confirmar envío
    if (!confirm('¿Enviar comprobante al email: ' + employeeEmail + '?')) {
        return;
    }

    const basePath = getBasePath();
    const ajaxUrl = basePath + '/panel/reports/enviar-comprobante-email';

    // Mostrar loader
    toastr.info('Enviando comprobante...', '', {timeOut: 0, extendedTimeOut: 0});

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            payroll_id: payrollId,
            employee_id: employeeId,
            email_to: employeeEmail
        },
        success: function(response) {
            toastr.clear();

            if (response.success) {
                toastr.success(response.message || 'Comprobante enviado exitosamente');
            } else {
                toastr.error(response.message || 'Error al enviar comprobante');
            }
        },
        error: function(xhr, status, error) {
            toastr.clear();
            console.error('Error al enviar comprobante:', error);
            toastr.error('Error de conexión al enviar comprobante');
        }
    });
}

/**
 * Obtener base path de la aplicación
 */
function getBasePath() {
    const path = window.location.pathname;
    return path.substring(0, path.indexOf('/panel'));
}

/**
 * Filtrar empleados en tiempo real
 */
$(document).ready(function() {
    $('#employeeSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();

        if (!searchTerm) {
            renderEmployeesList(employeesList);
            return;
        }

        const filtered = employeesList.filter(function(emp) {
            const nombreCompleto = (emp.lastname + ' ' + emp.firstname).toLowerCase();
            const cedula = (emp.document_id || '').toLowerCase();
            const cargo = (emp.cargo_name || emp.position_name || '').toLowerCase();

            return nombreCompleto.includes(searchTerm) ||
                   cedula.includes(searchTerm) ||
                   cargo.includes(searchTerm);
        });

        renderEmployeesList(filtered);
    });
});