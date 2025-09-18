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
         * Initialize DataTable
         */
        initializeDataTable: function() {
            $("#payrollsTable").DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "pageLength": 25,
                "language": {
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron planillas",
                    "info": "Mostrando página _PAGE_ de _PAGES_",
                    "infoEmpty": "No hay registros disponibles",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "order": [[0, "desc"]],
                "columnDefs": [
                    { "orderable": false, "targets": [6] }
                ]
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
                const description = $(this).data("description");
                $("#processPayrollName").text(description);
                $("#processModal").modal("show");
            });

            // Confirm process
            $("#confirmProcess").click(function() {
                if (self.state.currentPayrollId) {
                    self.loadInitialProcessData(self.state.currentPayrollId, function() {
                        self.startPayrollProcessing(self.state.currentPayrollId);
                    });
                }
            });

            // Reprocess payroll button
            $(document).on("click", ".reprocess-btn", function() {
                self.state.currentPayrollId = $(this).data("id");
                const description = $(this).data("description");

                $("#reprocessPayrollName").text(description);
                $("#reprocessModal").modal("show");
            });

            // Confirm reprocess
            $("#confirmReprocess").click(function() {
                if (self.state.currentPayrollId) {
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
                    self.submitFormAction("close");
                }
            });

            // Reopen payroll button (for CERRADA state)
            $(document).on("click", ".reopen-btn", function() {
                self.state.currentPayrollId = $(this).data("id");
                const description = $(this).data("description");
                $("#reopenPayrollName").text(description);
                $("#reopenModal").modal("show");
            });

            // Confirm reopen
            $("#confirmReopen").click(function() {
                const motivo = $("#reopenMotivo").val().trim();
                if (!motivo) {
                    alert("El motivo es obligatorio para reabrir una planilla.");
                    return;
                }
                if (self.state.currentPayrollId) {
                    self.submitFormAction("reopen", { motivo: motivo });
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
                    alert("El motivo es obligatorio para marcar la planilla como pendiente.");
                    return;
                }
                if (self.state.currentPayrollId) {
                    self.submitFormAction("markPending", { motivo: motivo });
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
                    self.submitFormAction("cancel");
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
        },

        /**
         * Submit form action (close, cancel, delete)
         */
        submitFormAction: function(action, data = {}) {
            const form = $("<form>", {
                method: "POST",
                action: `${this.config.urls.payrolls}/${this.state.currentPayrollId}/${action}`
            });
            
            form.append($("<input>", {
                type: "hidden",
                name: "csrf_token",
                value: this.config.csrfToken
            }));
            
            // Add additional data
            for (const [key, value] of Object.entries(data)) {
                form.append($("<input>", {
                    type: "hidden",
                    name: key,
                    value: value
                }));
            }
            
            $("body").append(form);
            form.submit();
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
                    
                    alert("Error: No se ha seleccionado un tipo de planilla. Por favor:\n\n" +
                          "1. Seleccione un tipo en el dropdown de la barra de navegación (superior derecha)\n" +
                          "2. Espere a que se cargue completamente\n" +
                          "3. Intente procesar la planilla nuevamente\n\n" +
                          "Debug: Revise la consola del navegador para más detalles");
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
            
            // Show completion phase
            $("#processingPhase").hide();
            $("#processingButtons").hide();
            $("#completedPhase").show();
            $("#completedButtons").show();
            
            // Show final statistics with generation time
            const statsHtml = `
                <div class="row text-center">
                    <div class="col-md-4">
                        <strong>${response && response.stats ? response.stats.employees : this.state.totalEmployees}</strong><br>
                        <small>Empleados Procesados</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${response && response.stats ? response.stats.concepts : $("#conceptsProgress").text()}</strong><br>
                        <small>Conceptos Calculados</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${timeDisplay}</strong><br>
                        <small>Tiempo de Generación</small>
                    </div>
                </div>
            `;
            $("#completionStats").html(statsHtml);
            
            // Re-enable modal close
            $("#modalCloseBtn").prop("disabled", false);
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
                alert("Error: No se ha seleccionado un tipo de planilla. Por favor:\n\n" +
                      "1. Seleccione un tipo en el dropdown de la barra de navegación (superior derecha)\n" +
                      "2. Espere a que se cargue completamente\n" +
                      "3. Intente reprocesar la planilla nuevamente\n\n" +
                      "Debug: Revise la consola del navegador para más detalles");
                return;
            }
            
            
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
                csrf_token: this.config.csrfToken
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
            
            // Show completion phase
            $("#reprocessProcessingPhase").hide();
            $("#reprocessProcessingButtons").hide();
            $("#reprocessCompletedPhase").show();
            $("#reprocessCompletedButtons").show();
            
            // Show final statistics with generation time
            const statsHtml = `
                <div class="row text-center">
                    <div class="col-md-4">
                        <strong>${response && response.stats ? response.stats.employees : this.state.totalEmployees}</strong><br>
                        <small>Empleados Reprocesados</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${response && response.stats ? response.stats.concepts : $("#reprocessConceptsProgress").text()}</strong><br>
                        <small>Conceptos Recalculados</small>
                    </div>
                    <div class="col-md-4">
                        <strong>${timeDisplay}</strong><br>
                        <small>Tiempo de Generación</small>
                    </div>
                </div>
            `;
            $("#reprocessCompletionStats").html(statsHtml);
            
            // Re-enable modal close
            $("#reprocessModalCloseBtn").prop("disabled", false);
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

})(window);