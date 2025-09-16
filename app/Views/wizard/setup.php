<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? 'Setup Inicial - Sistema de Planillas' ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/assets/plugins/fontawesome-free/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="/assets/dist/css/adminlte.min.css">
    <!-- Custom Wizard Styles -->
    <style>
        .wizard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #FF5722 0%, #FF9800 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .wizard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(255,87,34,0.15);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
        }
        .wizard-header {
            background: linear-gradient(135deg, #FF5722 0%, #FF9800 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .wizard-body {
            padding: 40px;
        }
        .step-progress {
            margin-bottom: 30px;
        }
        .step-progress .step {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            line-height: 40px;
            text-align: center;
            margin: 0 10px;
            position: relative;
            background: #e9ecef;
            color: #6c757d;
            font-weight: bold;
        }
        .step-progress .step.active {
            background: #FF5722;
            color: white;
        }
        .step-progress .step.completed {
            background: #4CAF50;
            color: white;
        }
        .step-progress .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 60px;
            height: 2px;
            background: #e9ecef;
            margin-top: -1px;
        }
        .step-progress .step.completed:not(:last-child):after {
            background: #4CAF50;
        }
        .wizard-step {
            display: none;
        }
        .wizard-step.active {
            display: block;
        }
        .form-group label {
            font-weight: 600;
            color: #495057;
        }
        .btn-wizard {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
        }
        .company-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .company-summary .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .company-summary .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .loading-overlay.show {
            display: flex;
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-card">
            <!-- Header -->
            <div class="wizard-header">
                <h1><i class="fas fa-magic"></i> Setup Inicial</h1>
                <p class="mb-0">Configure su empresa en el Sistema de Planillas</p>
            </div>

            <!-- Body -->
            <div class="wizard-body position-relative">
                <!-- Loading Overlay -->
                <div class="loading-overlay" id="loadingOverlay">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <div>
                            <h5 id="loadingMessage">Procesando...</h5>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="step-progress text-center">
                    <span class="step active" id="step1">1</span>
                    <span class="step" id="step2">2</span>
                    <span class="step" id="step3">3</span>
                </div>

                <!-- Step 1: Distributor Validation -->
                <div class="wizard-step active" id="wizardStep1">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-tie fa-3x mb-3" style="color: #FF5722;"></i>
                        <h3>Validación de Distribuidor</h3>
                        <p class="text-muted">Ingrese sus credenciales de distribuidor autorizado</p>
                    </div>

                    <form id="distributorForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="distributor_username">Usuario Distribuidor</label>
                                    <input type="text" class="form-control form-control-lg" id="distributor_username" name="distributor_username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="distributor_password">Contraseña</label>
                                    <input type="password" class="form-control form-control-lg" id="distributor_password" name="distributor_password" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-wizard" style="background: #FF5722; border-color: #FF5722; color: white;">
                                <i class="fas fa-check"></i> Validar Distribuidor
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 2: Company Registration -->
                <div class="wizard-step" id="wizardStep2">
                    <div class="text-center mb-4">
                        <i class="fas fa-building fa-3x text-success mb-3"></i>
                        <h3>Registro de Empresa</h3>
                        <p class="text-muted">Configure los datos de su empresa y usuario administrador</p>
                    </div>

                    <form id="companyForm">
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-primary"><i class="fas fa-building"></i> Datos de la Empresa</h5>
                                <hr>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="company_name">Nombre de la Empresa *</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="company_ruc">RUC *</label>
                                    <input type="text" class="form-control" id="company_ruc" name="company_ruc" required 
                                           pattern="[0-9]{8,12}" title="RUC debe tener entre 8 y 12 dígitos">
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h5 class="text-primary"><i class="fas fa-user-cog"></i> Usuario Administrador</h5>
                                <hr>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_firstname">Nombres *</label>
                                    <input type="text" class="form-control" id="admin_firstname" name="admin_firstname" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_lastname">Apellidos *</label>
                                    <input type="text" class="form-control" id="admin_lastname" name="admin_lastname" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_username_company">Nombre de Usuario *</label>
                                    <input type="text" class="form-control" id="admin_username_company" name="admin_username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_email">Email *</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_password_company">Contraseña *</label>
                                    <input type="password" class="form-control" id="admin_password_company" name="admin_password" required minlength="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="admin_password_confirm">Confirmar Contraseña *</label>
                                    <input type="password" class="form-control" id="admin_password_confirm" required minlength="6">
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-secondary btn-wizard me-3" onclick="goToPreviousStep()">
                                <i class="fas fa-arrow-left"></i> Anterior
                            </button>
                            <button type="submit" class="btn btn-wizard" style="background: #FF5722; border-color: #FF5722; color: white;">
                                <i class="fas fa-arrow-right"></i> Continuar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 3: Confirmation -->
                <div class="wizard-step" id="wizardStep3">
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle fa-3x text-warning mb-3"></i>
                        <h3>Confirmación y Creación</h3>
                        <p class="text-muted">Revise los datos y confirme la creación de su empresa</p>
                    </div>

                    <div class="company-summary" id="companySummary">
                        <h5 class="mb-3"><i class="fas fa-clipboard-list"></i> Resumen de Configuración</h5>
                        <div class="summary-item">
                            <strong>Empresa:</strong>
                            <span id="summaryCompanyName">-</span>
                        </div>
                        <div class="summary-item">
                            <strong>RUC:</strong>
                            <span id="summaryRuc">-</span>
                        </div>
                        <div class="summary-item">
                            <strong>Administrador:</strong>
                            <span id="summaryAdminName">-</span>
                        </div>
                        <div class="summary-item">
                            <strong>Email:</strong>
                            <span id="summaryAdminEmail">-</span>
                        </div>
                        <div class="summary-item">
                            <strong>Base de Datos:</strong>
                            <span id="summaryDatabase">Se creará automáticamente</span>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>¿Qué sucederá al confirmar?</strong>
                        <ul class="mb-0 mt-2">
                            <li>Se creará una base de datos exclusiva para su empresa</li>
                            <li>Se configurarán las tablas y datos iniciales</li>
                            <li>Se creará el usuario administrador</li>
                            <li>Recibirá un correo con los datos de acceso</li>
                        </ul>
                    </div>

                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-secondary btn-wizard me-3" onclick="goToPreviousStep()">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                        <button type="button" class="btn btn-wizard" id="createCompanyBtn" style="background: #4CAF50; border-color: #4CAF50; color: white;">
                            <i class="fas fa-rocket"></i> Crear Empresa
                        </button>
                    </div>
                </div>

                <!-- Success Step -->
                <div class="wizard-step" id="wizardStepSuccess">
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                        <h2 class="text-success">¡Empresa Creada Exitosamente!</h2>
                        <p class="lead">Su empresa ha sido configurada correctamente en el sistema</p>
                        
                        <div class="alert alert-success mt-4">
                            <h5><i class="fas fa-info-circle"></i> Información Importante</h5>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <strong>Base de Datos:</strong><br>
                                    <code id="successDatabaseName">-</code>
                                </div>
                                <div class="col-md-6">
                                    <strong>ID Empresa:</strong><br>
                                    <code id="successCompanyId">-</code>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="#" class="btn btn-lg btn-wizard" id="goToLoginBtn" style="background: #FF5722; border-color: #FF5722; color: white;">
                                <i class="fas fa-sign-in-alt"></i> Ir al Sistema
                            </a>
                            <button type="button" class="btn btn-outline-secondary btn-wizard ml-3" onclick="location.reload()">
                                <i class="fas fa-plus"></i> Crear Otra Empresa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="/assets/plugins/jquery/jquery.min.js"></script>
    <script src="/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/dist/js/adminlte.min.js"></script>
    
    <!-- Wizard JavaScript -->
    <script>
        // Wizard Manager Object
        const WizardManager = {
            currentStep: 1,
            totalSteps: 3,
            companyData: {},

            init() {
                this.bindEvents();
                this.updateProgress();
            },

            bindEvents() {
                // Step 1 - Distributor validation
                $('#distributorForm').on('submit', (e) => {
                    e.preventDefault();
                    this.validateDistributor();
                });

                // Step 2 - Company registration  
                $('#companyForm').on('submit', (e) => {
                    e.preventDefault();
                    this.registerCompany();
                });

                // Step 3 - Create company
                $('#createCompanyBtn').on('click', () => {
                    this.createCompany();
                });

                // Password confirmation validation
                $('#admin_password_confirm').on('input', () => {
                    this.validatePasswordConfirmation();
                });
            },

            validateDistributor() {
                const formData = new FormData(document.getElementById('distributorForm'));
                this.showLoading('Validando distribuidor...');

                $.ajax({
                    url: '/setup/wizard/validate-distributor',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: (response) => {
                        this.hideLoading();
                        if (response.success) {
                            // Auto-fill distributor email if provided
                            if (response.email) {
                                $('#admin_email').val(response.email);
                            }
                            this.goToStep(2);
                            this.showToast('Distribuidor encontrado', 'success');
                        } else {
                            this.showToast(response.message, 'error');
                        }
                    },
                    error: (xhr) => {
                        this.hideLoading();
                        this.showToast('Error de conexión', 'error');
                        console.error('Error:', xhr);
                    }
                });
            },

            registerCompany() {
                if (!this.validatePasswordConfirmation()) {
                    return;
                }

                const formData = new FormData(document.getElementById('companyForm'));
                this.showLoading('Registrando empresa...');

                $.ajax({
                    url: '/setup/wizard/register-company',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: (response) => {
                        this.hideLoading();
                        if (response.success) {
                            this.companyData = response.company_data;
                            this.updateSummary();
                            this.goToStep(3);
                            this.showToast('Datos registrados correctamente', 'success');
                        } else {
                            this.showToast(response.message, 'error');
                            if (response.errors) {
                                this.showValidationErrors(response.errors);
                            }
                        }
                    },
                    error: (xhr) => {
                        this.hideLoading();
                        this.showToast('Error registrando empresa', 'error');
                        console.error('Error:', xhr);
                    }
                });
            },

            createCompany() {
                this.showLoading('Creando empresa y base de datos...<br><small>Este proceso puede tardar unos momentos</small>');

                $.ajax({
                    url: '/setup/wizard/create-company',
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: (response) => {
                        this.hideLoading();
                        if (response.success) {
                            this.showSuccessStep(response);
                            this.showToast('¡Empresa creada exitosamente!', 'success');
                        } else {
                            this.showToast(response.message, 'error');
                        }
                    },
                    error: (xhr) => {
                        this.hideLoading();
                        this.showToast('Error creando empresa', 'error');
                        console.error('Error:', xhr);
                    }
                });
            },

            goToStep(step) {
                // Hide all steps
                $('.wizard-step').removeClass('active');
                
                // Show target step
                $(`#wizardStep${step}`).addClass('active');
                
                // Update progress
                this.currentStep = step;
                this.updateProgress();
            },

            updateProgress() {
                $('.step').removeClass('active completed');
                
                for (let i = 1; i <= this.totalSteps; i++) {
                    const stepElement = $(`#step${i}`);
                    if (i < this.currentStep) {
                        stepElement.addClass('completed');
                    } else if (i === this.currentStep) {
                        stepElement.addClass('active');
                    }
                }
            },

            updateSummary() {
                $('#summaryCompanyName').text(this.companyData.company_name);
                $('#summaryRuc').text(this.companyData.ruc);
                $('#summaryAdminName').text(this.companyData.admin_name);
                $('#summaryAdminEmail').text(this.companyData.admin_email);
                $('#summaryDatabase').text(`planilla_empresa_${this.companyData.ruc}`);
            },

            showSuccessStep(response) {
                $('#successCompanyId').text(response.company_id);
                $('#successDatabaseName').text(response.database_name);
                $('#goToLoginBtn').attr('href', response.login_url);
                
                // Show success step
                $('.wizard-step').removeClass('active');
                $('#wizardStepSuccess').addClass('active');
                
                // Update all steps as completed
                $('.step').removeClass('active').addClass('completed');
            },

            validatePasswordConfirmation() {
                const password = $('#admin_password_company').val();
                const confirmPassword = $('#admin_password_confirm').val();
                
                if (password !== confirmPassword) {
                    $('#admin_password_confirm').addClass('is-invalid');
                    this.showToast('Las contraseñas no coinciden', 'error');
                    return false;
                } else {
                    $('#admin_password_confirm').removeClass('is-invalid');
                    return true;
                }
            },

            showValidationErrors(errors) {
                Object.keys(errors).forEach(field => {
                    $(`#${field}`).addClass('is-invalid');
                    // TODO: Show specific error message
                });
            },

            showLoading(message) {
                $('#loadingMessage').html(message);
                $('#loadingOverlay').addClass('show');
            },

            hideLoading() {
                $('#loadingOverlay').removeClass('show');
            },

            showToast(message, type) {
                // Simple toast implementation
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
                
                const toast = $(`
                    <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <i class="fas ${icon}"></i> ${message}
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                `);
                
                $('body').append(toast);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    toast.alert('close');
                }, 5000);
            }
        };

        // Global functions for navigation
        function goToPreviousStep() {
            if (WizardManager.currentStep > 1) {
                WizardManager.goToStep(WizardManager.currentStep - 1);
            }
        }

        // Initialize wizard when document ready
        $(document).ready(() => {
            WizardManager.init();
        });
    </script>
</body>
</html>