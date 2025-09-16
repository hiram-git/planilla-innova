<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <title><?= $pageTitle ?? 'Setup Inicial - Sistema de Planillas' ?></title>
    
    <!-- Vuetify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@5.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        .v-application {
            font-family: 'Roboto', sans-serif !important;
        }
        
        .wizard-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #FF5722 0%, #FF9800 100%);
        }
        
        .wizard-card {
            margin: 20px;
            border-radius: 15px !important;
            box-shadow: 0 20px 60px rgba(255,87,34,0.15) !important;
        }
        
        .wizard-header {
            background: linear-gradient(135deg, #FF5722 0%, #FF9800 100%) !important;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .company-info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .step-content {
            padding: 30px;
        }
        
        .result-message {
            white-space: pre-wrap;
        }
        
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.5s;
        }
        
        .fade-enter, .fade-leave-to {
            opacity: 0;
        }
    </style>
</head>
<body>
    <div id="app">
        <v-app>
            <v-main>
                <div class="wizard-container d-flex align-center justify-center">
                    <v-card 
                        class="wizard-card" 
                        max-width="800" 
                        width="100%"
                        elevation="12"
                    >
                        <!-- Header -->
                        <v-card-title class="wizard-header white--text text-center d-block py-6">
                            <v-icon large color="white" class="mb-2">mdi-magic-staff</v-icon>
                            <h1 class="display-1 font-weight-light">Setup Inicial</h1>
                            <p class="subtitle-1 mb-0">Configure su empresa en el Sistema de Planillas</p>
                        </v-card-title>

                        <!-- Loading Overlay -->
                        <v-overlay :value="loading" color="white" opacity="0.9">
                            <div class="text-center">
                                <v-progress-circular
                                    :size="70"
                                    :width="7"
                                    color="primary"
                                    indeterminate
                                ></v-progress-circular>
                                <h3 class="mt-4" v-html="loadingMessage">Procesando...</h3>
                            </div>
                        </v-overlay>

                        <!-- Stepper -->
                        <v-stepper v-model="step" vertical>
                            <!-- Step 1: Distributor Validation -->
                            <v-stepper-step 
                                step="1" 
                                :complete="step > 1"
                                color="primary"
                            >
                                Validación de Distribuidor
                                <small>Ingrese sus credenciales autorizadas</small>
                            </v-stepper-step>

                            <v-stepper-content step="1">
                                <div class="step-content">
                                    <v-row justify="center" class="mb-6">
                                        <v-icon size="80" color="primary">mdi-account-tie</v-icon>
                                    </v-row>

                                    <v-form @submit.prevent="validarLogin" ref="loginForm">
                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="usuario.user"
                                                    label="Usuario Distribuidor"
                                                    :error-messages="userErrors"
                                                    prepend-icon="mdi-account"
                                                    required
                                                    outlined
                                                    color="primary"
                                                ></v-text-field>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="usuario.password"
                                                    label="Contraseña"
                                                    :type="showPassword ? 'text' : 'password'"
                                                    :error-messages="passwordErrors"
                                                    prepend-icon="mdi-lock"
                                                    :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                                    @click:append="showPassword = !showPassword"
                                                    required
                                                    outlined
                                                    color="primary"
                                                ></v-text-field>
                                            </v-col>
                                        </v-row>

                                        <v-row justify="center" class="mt-4">
                                            <v-btn
                                                type="submit"
                                                color="primary"
                                                large
                                                :loading="loading"
                                                :disabled="!usuario.user || !usuario.password"
                                            >
                                                <v-icon left>mdi-check</v-icon>
                                                Validar Distribuidor
                                            </v-btn>
                                        </v-row>
                                    </v-form>
                                </div>
                            </v-stepper-content>

                            <!-- Step 2: Company Information -->
                            <v-stepper-step 
                                step="2" 
                                :complete="step > 2"
                                color="primary"
                            >
                                Información de la Empresa
                                <small>Configure los datos de su empresa</small>
                            </v-stepper-step>

                            <v-stepper-content step="2">
                                <div class="step-content">
                                    <v-row justify="center" class="mb-6">
                                        <v-icon size="80" color="success">mdi-domain</v-icon>
                                    </v-row>

                                    <v-form @submit.prevent="validarPaso2" ref="companyForm">
                                        <!-- Company Data Section -->
                                        <v-row>
                                            <v-col cols="12">
                                                <h3 class="primary--text mb-4">
                                                    <v-icon color="primary" class="mr-2">mdi-domain</v-icon>
                                                    Datos de la Empresa
                                                </h3>
                                                <v-divider class="mb-4"></v-divider>
                                            </v-col>
                                        </v-row>

                                        <v-row>
                                            <v-col cols="12" md="8">
                                                <v-text-field
                                                    v-model="empresa.nombre"
                                                    label="Nombre de la Empresa"
                                                    :error-messages="nombreErrors"
                                                    prepend-icon="mdi-office-building"
                                                    required
                                                    outlined
                                                    color="primary"
                                                ></v-text-field>
                                            </v-col>
                                            <v-col cols="12" md="4">
                                                <v-text-field
                                                    v-model="empresa.ruc"
                                                    label="RUC"
                                                    :error-messages="rucErrors"
                                                    prepend-icon="mdi-identifier"
                                                    required
                                                    outlined
                                                    color="primary"
                                                ></v-text-field>
                                            </v-col>
                                        </v-row>

                                        <!-- Admin User Section -->
                                        <v-row>
                                            <v-col cols="12">
                                                <h3 class="primary--text mb-4 mt-4">
                                                    <v-icon color="primary" class="mr-2">mdi-account-cog</v-icon>
                                                    Usuario Administrador
                                                </h3>
                                                <v-divider class="mb-4"></v-divider>
                                            </v-col>
                                        </v-row>

                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="adminUser.firstname"
                                                    label="Nombres"
                                                    prepend-icon="mdi-account"
                                                    required
                                                    outlined
                                                    color="primary"
                                                ></v-text-field>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="adminUser.lastname"
                                                    label="Apellidos"
                                                    prepend-icon="mdi-account"
                                                    required
                                                    outlined
                                                    color="primary"
                                                ></v-text-field>
                                            </v-col>
                                        </v-row>

                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="adminUser.username"
                                                    label="Nombre de Usuario"
                                                    prepend-icon="mdi-at"
                                                    required
                                                    outlined
                                                    color="primary"
                                                ></v-text-field>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="empresa.email"
                                                    label="Email"
                                                    :error-messages="emailErrors"
                                                    prepend-icon="mdi-email"
                                                    type="email"
                                                    required
                                                    outlined
                                                    color="primary"
                                                    readonly
                                                ></v-text-field>
                                            </v-col>
                                        </v-row>

                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="adminUser.password"
                                                    label="Contraseña"
                                                    :type="showAdminPassword ? 'text' : 'password'"
                                                    prepend-icon="mdi-lock"
                                                    :append-icon="showAdminPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                                    @click:append="showAdminPassword = !showAdminPassword"
                                                    required
                                                    outlined
                                                    color="primary"
                                                ></v-text-field>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="adminUser.passwordConfirm"
                                                    label="Confirmar Contraseña"
                                                    :type="showAdminPassword ? 'text' : 'password'"
                                                    prepend-icon="mdi-lock-check"
                                                    required
                                                    outlined
                                                    color="primary"
                                                    :error-messages="passwordConfirmErrors"
                                                ></v-text-field>
                                            </v-col>
                                        </v-row>

                                        <v-row justify="space-between" class="mt-6">
                                            <v-btn
                                                @click="step = 1"
                                                color="grey"
                                                text
                                                large
                                            >
                                                <v-icon left>mdi-arrow-left</v-icon>
                                                Anterior
                                            </v-btn>
                                            
                                            <v-btn
                                                type="submit"
                                                color="primary"
                                                large
                                                :disabled="!isStep2Valid"
                                            >
                                                <v-icon left>mdi-arrow-right</v-icon>
                                                Continuar
                                            </v-btn>
                                        </v-row>
                                    </v-form>
                                </div>
                            </v-stepper-content>

                            <!-- Step 3: Confirmation & Creation -->
                            <v-stepper-step 
                                step="3" 
                                :complete="finProceso"
                                color="primary"
                            >
                                {{ finProceso ? 'Proceso Completado' : 'Confirmación y Creación' }}
                                <small>{{ finProceso ? 'Empresa creada exitosamente' : 'Revise y confirme la información' }}</small>
                            </v-stepper-step>

                            <v-stepper-content step="3">
                                <div class="step-content">
                                    <!-- Pre-creation summary -->
                                    <div v-if="!finProceso">
                                        <v-row justify="center" class="mb-6">
                                            <v-icon size="80" color="warning">mdi-check-circle</v-icon>
                                        </v-row>

                                        <!-- Company Summary Card -->
                                        <v-card class="company-info-card" outlined>
                                            <v-card-title class="pb-2">
                                                <v-icon color="primary" class="mr-2">mdi-clipboard-list</v-icon>
                                                Resumen de Configuración
                                            </v-card-title>
                                            <v-card-text>
                                                <v-simple-table dense>
                                                    <tbody>
                                                        <tr>
                                                            <td><strong>Empresa:</strong></td>
                                                            <td>{{ empresa.nombre }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>RUC:</strong></td>
                                                            <td>{{ empresa.ruc }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Administrador:</strong></td>
                                                            <td>{{ adminUser.firstname }} {{ adminUser.lastname }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Email:</strong></td>
                                                            <td>{{ empresa.email }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Base de Datos:</strong></td>
                                                            <td>planilla_empresa_{{ empresa.ruc }}</td>
                                                        </tr>
                                                    </tbody>
                                                </v-simple-table>
                                            </v-card-text>
                                        </v-card>

                                        <v-alert
                                            type="info"
                                            outlined
                                            class="my-4"
                                        >
                                            <strong>¿Qué sucederá al confirmar?</strong>
                                            <ul class="mt-2 mb-0">
                                                <li>Se creará una base de datos exclusiva para su empresa</li>
                                                <li>Se configurarán las tablas y datos iniciales</li>
                                                <li>Se generará y validará la licencia del sistema</li>
                                                <li>Se creará el usuario administrador</li>
                                            </ul>
                                        </v-alert>

                                        <v-row justify="space-between" class="mt-6">
                                            <v-btn
                                                @click="step = 2"
                                                color="grey"
                                                text
                                                large
                                            >
                                                <v-icon left>mdi-arrow-left</v-icon>
                                                Anterior
                                            </v-btn>
                                            
                                            <v-btn
                                                @click="confirmarCreacion"
                                                color="success"
                                                large
                                                :loading="loading"
                                            >
                                                <v-icon left>mdi-rocket</v-icon>
                                                Crear Empresa
                                            </v-btn>
                                        </v-row>
                                    </div>

                                    <!-- Post-creation result -->
                                    <div v-else>
                                        <v-row justify="center" class="mb-6">
                                            <v-icon size="120" color="success">mdi-check-circle</v-icon>
                                        </v-row>

                                        <div class="text-center">
                                            <h2 class="success--text mb-4">¡Empresa Creada Exitosamente!</h2>
                                            <p class="subtitle-1">Su empresa ha sido configurada correctamente en el sistema</p>
                                        </div>

                                        <v-alert
                                            type="success"
                                            outlined
                                            class="my-4"
                                        >
                                            <h4><v-icon color="success" class="mr-2">mdi-information</v-icon>Información Importante</h4>
                                            <div class="mt-3">
                                                <v-row>
                                                    <v-col cols="12" md="6">
                                                        <strong>Base de Datos:</strong><br>
                                                        <code>{{ resultadoCreacion.database_name || 'planilla_empresa_' + empresa.ruc }}</code>
                                                    </v-col>
                                                    <v-col cols="12" md="6">
                                                        <strong>ID Empresa:</strong><br>
                                                        <code>{{ resultadoCreacion.company_id || 'Generado automáticamente' }}</code>
                                                    </v-col>
                                                </v-row>
                                            </div>
                                        </v-alert>

                                        <v-card 
                                            v-if="mensajeResultado" 
                                            class="my-4" 
                                            outlined
                                        >
                                            <v-card-text>
                                                <pre class="result-message">{{ mensajeResultado }}</pre>
                                            </v-card-text>
                                        </v-card>

                                        <v-row justify="center" class="mt-6">
                                            <v-btn
                                                :href="loginUrl"
                                                color="primary"
                                                x-large
                                                class="mr-4"
                                            >
                                                <v-icon left>mdi-login</v-icon>
                                                Ir al Sistema
                                            </v-btn>
                                            
                                            <v-btn
                                                @click="reiniciarFormulario"
                                                color="grey"
                                                outlined
                                                large
                                            >
                                                <v-icon left>mdi-plus</v-icon>
                                                Crear Otra Empresa
                                            </v-btn>
                                        </v-row>
                                    </div>
                                </div>
                            </v-stepper-content>
                        </v-stepper>
                    </v-card>
                </div>
            </v-main>
        </v-app>
    </div>

    <!-- Vue.js 2.x -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
    <!-- Vuetify 2.x -->
    <script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Vue Application -->
    <script>
        new Vue({
            el: '#app',
            vuetify: new Vuetify({
                theme: {
                    themes: {
                        light: {
                            primary: '#FF5722',
                            secondary: '#FF9800',
                            accent: '#F4511E',
                            error: '#D81B60',
                            info: '#FFA726',
                            success: '#4CAF50',
                            warning: '#FB8C00'
                        }
                    }
                }
            }),
            data: {
                step: 1,
                loading: false,
                showPassword: false,
                showAdminPassword: false,
                finProceso: false,
                mensajeResultado: '',
                loadingMessage: 'Procesando...',
                loginUrl: '#',
                resultadoCreacion: {},
                
                empresa: {
                    nombre: '',
                    ruc: '',
                    email: ''
                },
                
                usuario: {
                    user: '',
                    password: ''
                },
                
                adminUser: {
                    firstname: '',
                    lastname: '',
                    username: '',
                    password: '',
                    passwordConfirm: ''
                },
                
                // Error arrays
                nombreErrors: [],
                rucErrors: [],
                emailErrors: [],
                userErrors: [],
                passwordErrors: [],
                passwordConfirmErrors: []
            },
            
            computed: {
                isStep2Valid() {
                    return this.empresa.nombre && 
                           this.empresa.ruc && 
                           this.adminUser.firstname &&
                           this.adminUser.lastname &&
                           this.adminUser.username &&
                           this.adminUser.password &&
                           this.adminUser.passwordConfirm &&
                           this.adminUser.password === this.adminUser.passwordConfirm;
                }
            },
            
            watch: {
                'adminUser.password': function() {
                    this.validatePasswordConfirm();
                },
                'adminUser.passwordConfirm': function() {
                    this.validatePasswordConfirm();
                }
            },
            
            methods: {
                async validarLogin() {
                    this.clearErrors();
                    
                    if (!this.usuario.user) {
                        this.userErrors.push('El usuario es obligatorio.');
                    }
                    if (!this.usuario.password) {
                        this.passwordErrors.push('La contraseña es obligatoria.');
                    }
                    
                    if (this.userErrors.length === 0 && this.passwordErrors.length === 0) {
                        this.loading = true;
                        this.loadingMessage = 'Validando distribuidor...';
                        
                        try {
                            const response = await axios({
                                method: 'post',
                                url: '/setup/wizard/validate-distributor',
                                data: {
                                    distributor_username: this.usuario.user,
                                    distributor_password: this.usuario.password
                                },
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                transformRequest: [(data) => {
                                    return Object.keys(data).map(key => `${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`).join('&');
                                }],
                                responseType: 'json'
                            });
                            
                            this.loading = false;
                            if (response.data.success) {
                                if (response.data.email) {
                                    this.empresa.email = response.data.email;
                                }
                                this.step = 2;
                                
                                Swal.fire({
                                    title: 'Distribuidor encontrado',
                                    text: 'Credenciales válidas',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.data.message,
                                    icon: 'warning',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            }
                        } catch (error) {
                            this.loading = false;
                            Swal.fire({
                                title: 'Error de conexión',
                                text: 'No se pudo validar el distribuidor',
                                icon: 'error',
                                timer: 3000,
                                showConfirmButton: false
                            });
                            console.error('Error en validación:', error);
                        }
                    }
                },
                
                async validarPaso2() {
                    this.clearErrors();
                    
                    if (!this.validatePasswordConfirm()) {
                        return;
                    }
                    
                    // Additional validations
                    if (!this.empresa.nombre) {
                        this.nombreErrors.push('El nombre es obligatorio.');
                    }
                    if (!this.empresa.ruc) {
                        this.rucErrors.push('El RUC es obligatorio.');
                    }
                    
                    if (this.nombreErrors.length === 0 && this.rucErrors.length === 0) {
                        this.step = 3;
                    }
                },
                
                async confirmarCreacion() {
                    const result = await Swal.fire({
                        title: '¿Guardar la empresa?',
                        text: '¿Está seguro de que desea crear la empresa?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, guardar',
                        cancelButtonText: 'No',
                        confirmButtonColor: '#FF5722',
                        cancelButtonColor: '#grey'
                    });
                    
                    if (result.isConfirmed) {
                        await this.guardarEmpresa();
                    }
                },
                
                async guardarEmpresa() {
                    this.loading = true;
                    this.finProceso = false;
                    this.mensajeResultado = '';
                    this.loadingMessage = 'Creando empresa y base de datos...<br><small>Este proceso puede tardar unos momentos</small>';
                    
                    try {
                        const formData = new FormData();
                        formData.append('company_name', this.empresa.nombre);
                        formData.append('company_ruc', this.empresa.ruc);
                        formData.append('admin_firstname', this.adminUser.firstname);
                        formData.append('admin_lastname', this.adminUser.lastname);
                        formData.append('admin_username', this.adminUser.username);
                        formData.append('admin_email', this.empresa.email);
                        formData.append('admin_password', this.adminUser.password);
                        
                        // Step 1: Register company
                        const registerResponse = await axios({
                            method: 'post',
                            url: '/setup/wizard/register-company',
                            data: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        if (registerResponse.data.success) {
                            // Step 2: Create company
                            const createResponse = await axios({
                                method: 'post',
                                url: '/setup/wizard/create-company',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            
                            this.loading = false;
                            this.finProceso = true;
                            
                            if (createResponse.data.success) {
                                this.resultadoCreacion = createResponse.data;
                                this.loginUrl = createResponse.data.login_url || '/panel/login';
                                this.mensajeResultado = 'Empresa creada exitosamente con base de datos: ' + createResponse.data.database_name;
                                
                                Swal.fire({
                                    title: '¡Empresa Creada!',
                                    text: 'Su empresa ha sido configurada correctamente',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                this.mensajeResultado = 'Error: ' + createResponse.data.message;
                            }
                        }
                    } catch (error) {
                        this.loading = false;
                        this.finProceso = true;
                        
                        if (error.response) {
                            const { status, data } = error.response;
                            this.mensajeResultado = data.message || 'Error desconocido al crear la empresa';
                            
                            switch (status) {
                                case 400:
                                    Swal.fire('Error de Validación', data.message, 'error');
                                    break;
                                case 409:
                                    Swal.fire('Conflicto', data.message, 'warning');
                                    break;
                                case 500:
                                    Swal.fire('Error del Servidor', data.message, 'error');
                                    break;
                                default:
                                    Swal.fire('Error', 'Error desconocido al crear la empresa', 'error');
                            }
                        } else {
                            Swal.fire('Error', 'No se pudo conectar al servidor', 'error');
                            this.mensajeResultado = 'No se pudo conectar al servidor';
                        }
                        
                        console.error('Error creando empresa:', error);
                    }
                },
                
                validatePasswordConfirm() {
                    this.passwordConfirmErrors = [];
                    
                    if (this.adminUser.password !== this.adminUser.passwordConfirm) {
                        this.passwordConfirmErrors.push('Las contraseñas no coinciden');
                        return false;
                    }
                    return true;
                },
                
                clearErrors() {
                    this.userErrors = [];
                    this.passwordErrors = [];
                    this.nombreErrors = [];
                    this.rucErrors = [];
                    this.emailErrors = [];
                    this.passwordConfirmErrors = [];
                },
                
                reiniciarFormulario() {
                    this.step = 1;
                    this.finProceso = false;
                    this.mensajeResultado = '';
                    this.resultadoCreacion = {};
                    
                    this.empresa = { nombre: '', ruc: '', email: '' };
                    this.usuario = { user: '', password: '' };
                    this.adminUser = { 
                        firstname: '', 
                        lastname: '', 
                        username: '', 
                        password: '', 
                        passwordConfirm: '' 
                    };
                    
                    this.clearErrors();
                }
            }
        });
    </script>
</body>
</html>