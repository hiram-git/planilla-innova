<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <title><?= $pageTitle ?? 'Crear Empresa - Sistema de Planillas' ?></title>
    
    <!-- Base URL for JavaScript -->
    <script>
        window.BASE_URL = '<?= getBaseUrl() ?>';
    </script>
    
    <!-- Vuetify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        .v-application {
            font-family: 'Roboto', sans-serif !important;
        }
        
        .empresa-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #FF5722 0%, #FF9800 100%);
        }
        
        .empresa-card {
            margin: 20px;
            border-radius: 20px !important;
            box-shadow: 0 25px 80px rgba(255,87,34,0.2) !important;
        }
        
        .empresa-header {
            background: linear-gradient(135deg, #FF5722 0%, #FF9800 100%) !important;
            border-radius: 20px 20px 0 0 !important;
            padding: 40px 30px !important;
        }
        
        .step-content {
            padding: 30px 40px;
        }
        
        .company-summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            border-left: 5px solid #FF5722 !important;
        }
        
        .result-message {
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        
        .v-stepper__step--active .v-stepper__label {
            color: #FF5722 !important;
        }
        
        .v-stepper__step--complete .v-icon {
            color: #4CAF50 !important;
        }
        
        .floating-action {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        
        .wizard-animation {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-animation {
            animation: bounceIn 0.8s ease-out;
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <v-app>
            <v-main>
                <div class="empresa-container d-flex align-center justify-center">
                    <v-card 
                        class="empresa-card wizard-animation" 
                        max-width="900" 
                        width="100%"
                        elevation="24"
                    >
                        <!-- Header -->
                        <div class="empresa-header text-center">
                            <v-icon size="60" color="white" class="mb-3">mdi-domain-plus</v-icon>
                            <h1 class="display-2 font-weight-light white--text mb-2">Crear Nueva Empresa</h1>
                            <p class="headline font-weight-light white--text mb-0 opacity-90">
                                Configure su empresa en el Sistema de Planillas
                            </p>
                        </div>

                        <!-- Loading Overlay -->
                        <v-overlay :value="loading" color="rgba(255,255,255,0.95)" z-index="1000">
                            <div class="text-center">
                                <v-progress-circular
                                    :size="80"
                                    :width="8"
                                    color="primary"
                                    indeterminate
                                    class="mb-4"
                                ></v-progress-circular>
                                <h2 class="primary--text mb-2" v-html="loadingMessage">Procesando...</h2>
                                <p class="grey--text">Por favor espere mientras configuramos su empresa</p>
                            </div>
                        </v-overlay>

                        <!-- Stepper -->
                        <v-stepper v-model="step" vertical elevation="0">
                            <!-- Step 1: Distributor Validation -->
                            <v-stepper-step 
                                step="1" 
                                :complete="step > 1"
                                color="primary"
                                :rules="[() => step > 1 || (usuario.user && usuario.password)]"
                            >
                                <div class="d-flex align-center">
                                    <v-icon class="mr-3" :color="step > 1 ? 'success' : 'primary'">
                                        {{ step > 1 ? 'mdi-check-circle' : 'mdi-account-tie' }}
                                    </v-icon>
                                    <div>
                                        <div class="subtitle-1 font-weight-bold">Validación de Distribuidor</div>
                                        <small class="grey--text">Ingrese sus credenciales de distribuidor autorizado</small>
                                    </div>
                                </div>
                            </v-stepper-step>

                            <v-stepper-content step="1" class="pa-0">
                                <div class="step-content">
                                    <v-row justify="center" class="mb-8">
                                        <div class="text-center">
                                            <v-avatar size="120" color="primary" class="elevation-8">
                                                <v-icon size="60" color="white">mdi-account-tie</v-icon>
                                            </v-avatar>
                                            <h3 class="mt-4 primary--text">Acceso de Distribuidor</h3>
                                            <p class="grey--text">Valide sus credenciales para continuar</p>
                                        </div>
                                    </v-row>

                                    <v-form @submit.prevent="validarLogin" ref="loginForm">
                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="usuario.user"
                                                    label="Usuario Distribuidor"
                                                    :error-messages="userErrors"
                                                    prepend-icon="mdi-account-circle"
                                                    required
                                                    outlined
                                                    color="primary"
                                                    hide-details="auto"
                                                    class="mb-3"
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
                                                    hide-details="auto"
                                                    class="mb-3"
                                                ></v-text-field>
                                            </v-col>
                                        </v-row>

                                        <v-row justify="center" class="mt-6">
                                            <v-btn
                                                type="submit"
                                                color="primary"
                                                x-large
                                                :loading="loading"
                                                :disabled="!usuario.user || !usuario.password"
                                                elevation="6"
                                                min-width="200"
                                            >
                                                <v-icon left>mdi-shield-check</v-icon>
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
                                :rules="[() => step > 2 || isStep2Valid]"
                            >
                                <div class="d-flex align-center">
                                    <v-icon class="mr-3" :color="step > 2 ? 'success' : 'primary'">
                                        {{ step > 2 ? 'mdi-check-circle' : 'mdi-domain' }}
                                    </v-icon>
                                    <div>
                                        <div class="subtitle-1 font-weight-bold">Información de la Empresa</div>
                                        <small class="grey--text">Configure los datos de su empresa y usuario administrador</small>
                                    </div>
                                </div>
                            </v-stepper-step>

                            <v-stepper-content step="2" class="pa-0">
                                <div class="step-content">
                                    <v-form @submit.prevent="validarPaso2" ref="companyForm">
                                        <!-- Company Section -->
                                        <v-card flat class="mb-6">
                                            <v-card-title class="primary--text pa-0 pb-4">
                                                <v-icon color="primary" class="mr-3">mdi-domain</v-icon>
                                                <span class="headline">Datos de la Empresa</span>
                                            </v-card-title>
                                            <v-divider class="mb-6"></v-divider>

                                            <v-row>
                                                <v-col cols="12" md="8">
                                                    <v-text-field
                                                        v-model="empresa.nombre"
                                                        label="Nombre de la Empresa *"
                                                        :error-messages="nombreErrors"
                                                        prepend-icon="mdi-office-building"
                                                        required
                                                        outlined
                                                        color="primary"
                                                        hide-details="auto"
                                                        class="mb-3"
                                                    ></v-text-field>
                                                </v-col>
                                                <v-col cols="12" md="4">
                                                    <v-text-field
                                                        v-model="empresa.ruc"
                                                        label="RUC *"
                                                        :error-messages="rucErrors"
                                                        prepend-icon="mdi-identifier"
                                                        required
                                                        outlined
                                                        color="primary"
                                                        hide-details="auto"
                                                        class="mb-3"
                                                    ></v-text-field>
                                                </v-col>
                                            </v-row>
                                        </v-card>

                                        <!-- Admin User Section -->
                                        <v-card flat>
                                            <v-card-title class="primary--text pa-0 pb-4">
                                                <v-icon color="primary" class="mr-3">mdi-account-cog</v-icon>
                                                <span class="headline">Usuario Administrador</span>
                                            </v-card-title>
                                            <v-divider class="mb-6"></v-divider>

                                            <v-row>
                                                <v-col cols="12" md="6">
                                                    <v-text-field
                                                        v-model="adminUser.firstname"
                                                        label="Nombres *"
                                                        prepend-icon="mdi-account"
                                                        required
                                                        outlined
                                                        color="primary"
                                                        hide-details="auto"
                                                        class="mb-3"
                                                    ></v-text-field>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <v-text-field
                                                        v-model="adminUser.lastname"
                                                        label="Apellidos *"
                                                        prepend-icon="mdi-account"
                                                        required
                                                        outlined
                                                        color="primary"
                                                        hide-details="auto"
                                                        class="mb-3"
                                                    ></v-text-field>
                                                </v-col>
                                            </v-row>

                                            <v-row>
                                                <v-col cols="12" md="6">
                                                    <v-text-field
                                                        v-model="adminUser.username"
                                                        label="Nombre de Usuario *"
                                                        prepend-icon="mdi-at"
                                                        required
                                                        outlined
                                                        color="primary"
                                                        hide-details="auto"
                                                        class="mb-3"
                                                    ></v-text-field>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <v-text-field
                                                        v-model="empresa.email"
                                                        label="Email *"
                                                        :error-messages="emailErrors"
                                                        prepend-icon="mdi-email"
                                                        type="email"
                                                        required
                                                        outlined
                                                        color="primary"
                                                        readonly
                                                        hide-details="auto"
                                                        class="mb-3"
                                                        hint="Email obtenido del distribuidor"
                                                        persistent-hint
                                                    ></v-text-field>
                                                </v-col>
                                            </v-row>

                                            <v-row>
                                                <v-col cols="12" md="6">
                                                    <v-text-field
                                                        v-model="adminUser.password"
                                                        label="Contraseña *"
                                                        :type="showAdminPassword ? 'text' : 'password'"
                                                        prepend-icon="mdi-lock"
                                                        :append-icon="showAdminPassword ? 'mdi-eye' : 'mdi-eye-off'"
                                                        @click:append="showAdminPassword = !showAdminPassword"
                                                        required
                                                        outlined
                                                        color="primary"
                                                        hide-details="auto"
                                                        class="mb-3"
                                                    ></v-text-field>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <v-text-field
                                                        v-model="adminUser.passwordConfirm"
                                                        label="Confirmar Contraseña *"
                                                        :type="showAdminPassword ? 'text' : 'password'"
                                                        prepend-icon="mdi-lock-check"
                                                        required
                                                        outlined
                                                        color="primary"
                                                        :error-messages="passwordConfirmErrors"
                                                        hide-details="auto"
                                                        class="mb-3"
                                                    ></v-text-field>
                                                </v-col>
                                            </v-row>
                                        </v-card>

                                        <v-row justify="space-between" class="mt-8">
                                            <v-btn
                                                @click="step = 1"
                                                color="grey"
                                                text
                                                x-large
                                            >
                                                <v-icon left>mdi-arrow-left</v-icon>
                                                Anterior
                                            </v-btn>
                                            
                                            <v-btn
                                                type="submit"
                                                color="primary"
                                                x-large
                                                :disabled="!isStep2Valid"
                                                elevation="6"
                                            >
                                                Continuar
                                                <v-icon right>mdi-arrow-right</v-icon>
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
                                <div class="d-flex align-center">
                                    <v-icon class="mr-3" :color="finProceso ? 'success' : 'warning'">
                                        {{ finProceso ? 'mdi-check-circle' : 'mdi-rocket-launch' }}
                                    </v-icon>
                                    <div>
                                        <div class="subtitle-1 font-weight-bold">
                                            {{ finProceso ? 'Proceso Completado' : 'Confirmación y Creación' }}
                                        </div>
                                        <small class="grey--text">
                                            {{ finProceso ? 'Empresa creada exitosamente' : 'Revise y confirme la información' }}
                                        </small>
                                    </div>
                                </div>
                            </v-stepper-step>

                            <v-stepper-content step="3" class="pa-0">
                                <div class="step-content">
                                    <!-- Pre-creation summary -->
                                    <div v-if="!finProceso">
                                        <v-row justify="center" class="mb-8">
                                            <div class="text-center">
                                                <v-avatar size="120" color="warning" class="elevation-8">
                                                    <v-icon size="60" color="white">mdi-clipboard-check</v-icon>
                                                </v-avatar>
                                                <h3 class="mt-4 warning--text">Confirmar Creación</h3>
                                                <p class="grey--text">Verifique los datos antes de crear la empresa</p>
                                            </div>
                                        </v-row>

                                        <!-- Company Summary -->
                                        <v-card class="company-summary-card mb-6" outlined elevation="4">
                                            <v-card-title class="pb-2">
                                                <v-icon color="primary" class="mr-3">mdi-clipboard-list</v-icon>
                                                <span class="headline">Resumen de Configuración</span>
                                            </v-card-title>
                                            <v-card-text>
                                                <v-simple-table>
                                                    <tbody>
                                                        <tr>
                                                            <td class="font-weight-bold">Empresa:</td>
                                                            <td class="text-right">{{ empresa.nombre }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="font-weight-bold">RUC:</td>
                                                            <td class="text-right">{{ empresa.ruc }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="font-weight-bold">Administrador:</td>
                                                            <td class="text-right">{{ adminUser.firstname }} {{ adminUser.lastname }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="font-weight-bold">Email:</td>
                                                            <td class="text-right">{{ empresa.email }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="font-weight-bold">Base de Datos:</td>
                                                            <td class="text-right">
                                                                <code class="grey--text text--darken-2">
                                                                    planilla_empresa_{{ empresa.ruc }}
                                                                </code>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </v-simple-table>
                                            </v-card-text>
                                        </v-card>

                                        <v-alert
                                            type="info"
                                            outlined
                                            prominent
                                            class="mb-6"
                                        >
                                            <v-row align="center">
                                                <v-col class="grow">
                                                    <div class="subtitle-1 font-weight-bold">
                                                        ¿Qué sucederá al confirmar?
                                                    </div>
                                                    <ul class="mt-2 mb-0">
                                                        <li>Se creará una base de datos exclusiva para su empresa</li>
                                                        <li>Se configurarán las tablas y datos iniciales</li>
                                                        <li>Se generará y validará la licencia del sistema</li>
                                                        <li>Se creará el usuario administrador con acceso completo</li>
                                                    </ul>
                                                </v-col>
                                            </v-row>
                                        </v-alert>

                                        <v-row justify="space-between" class="mt-8">
                                            <v-btn
                                                @click="step = 2"
                                                color="grey"
                                                text
                                                x-large
                                            >
                                                <v-icon left>mdi-arrow-left</v-icon>
                                                Anterior
                                            </v-btn>
                                            
                                            <v-btn
                                                @click="confirmarCreacion"
                                                color="success"
                                                x-large
                                                :loading="loading"
                                                elevation="8"
                                                min-width="200"
                                            >
                                                <v-icon left>mdi-rocket</v-icon>
                                                Crear Empresa
                                            </v-btn>
                                        </v-row>
                                    </div>

                                    <!-- Post-creation result -->
                                    <div v-else class="success-animation">
                                        <v-row justify="center" class="mb-8">
                                            <div class="text-center">
                                                <v-avatar size="150" color="success" class="elevation-12">
                                                    <v-icon size="80" color="white">mdi-check-circle</v-icon>
                                                </v-avatar>
                                                <h2 class="success--text mt-6 mb-4">¡Empresa Creada Exitosamente!</h2>
                                                <p class="title grey--text">Su empresa ha sido configurada correctamente en el sistema</p>
                                            </div>
                                        </v-row>

                                        <v-alert
                                            type="success"
                                            prominent
                                            outlined
                                            class="mb-6"
                                        >
                                            <div class="subtitle-1 font-weight-bold mb-3">
                                                <v-icon color="success" class="mr-2">mdi-information</v-icon>
                                                Información Importante
                                            </div>
                                            <v-row>
                                                <v-col cols="12" md="6">
                                                    <div class="font-weight-bold">Base de Datos:</div>
                                                    <code class="success--text">{{ resultadoCreacion.database_name || 'planilla_empresa_' + empresa.ruc }}</code>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <div class="font-weight-bold">ID Empresa:</div>
                                                    <code class="success--text">{{ resultadoCreacion.company_id || 'Generado automáticamente' }}</code>
                                                </v-col>
                                            </v-row>
                                        </v-alert>

                                        <v-card 
                                            v-if="mensajeResultado" 
                                            class="mb-6" 
                                            outlined
                                            elevation="2"
                                        >
                                            <v-card-title>
                                                <v-icon color="success" class="mr-2">mdi-console</v-icon>
                                                Detalles del Proceso
                                            </v-card-title>
                                            <v-card-text>
                                                <pre class="result-message">{{ mensajeResultado }}</pre>
                                            </v-card-text>
                                        </v-card>

                                        <v-row justify="center" class="mt-8">
                                            <v-col cols="auto">
                                                <v-btn
                                                    :href="loginUrl"
                                                    color="primary"
                                                    x-large
                                                    elevation="8"
                                                    min-width="200"
                                                    class="mr-4"
                                                >
                                                    <v-icon left>mdi-login</v-icon>
                                                    Ir al Sistema
                                                </v-btn>
                                            </v-col>
                                            
                                            <v-col cols="auto">
                                                <v-btn
                                                    @click="reiniciarFormulario"
                                                    color="grey"
                                                    outlined
                                                    x-large
                                                    min-width="200"
                                                >
                                                    <v-icon left>mdi-plus</v-icon>
                                                    Crear Otra Empresa
                                                </v-btn>
                                            </v-col>
                                        </v-row>
                                    </div>
                                </div>
                            </v-stepper-content>
                        </v-stepper>
                    </v-card>
                </div>

                <!-- Floating Action Button for Help -->
                <v-btn
                    fab
                    large
                    color="secondary"
                    class="floating-action"
                    @click="showHelp"
                >
                    <v-icon>mdi-help</v-icon>
                </v-btn>
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
                        this.loadingMessage = 'Validando distribuidor...<br><small>Conectando con servidor de licencias</small>';
                        
                        try {
                            const response = await axios({
                                method: 'post',
                                url: window.BASE_URL + '/setup/wizard/validate-distributor',
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
                                
                                await Swal.fire({
                                    title: '¡Distribuidor Encontrado!',
                                    text: 'Credenciales válidas. Puede continuar con el registro.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                await Swal.fire({
                                    title: 'Error de Validación',
                                    text: response.data.message,
                                    icon: 'warning',
                                    confirmButtonText: 'Intentar de nuevo',
                                    confirmButtonColor: '#FF5722'
                                });
                            }
                        } catch (error) {
                            this.loading = false;
                            await Swal.fire({
                                title: 'Error de Conexión',
                                text: 'No se pudo validar el distribuidor. Verifique su conexión a internet.',
                                icon: 'error',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#FF5722'
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
                        title: '¿Crear la empresa?',
                        html: `
                            <div class="text-left">
                                <p><strong>Empresa:</strong> ${this.empresa.nombre}</p>
                                <p><strong>RUC:</strong> ${this.empresa.ruc}</p>
                                <p><strong>Admin:</strong> ${this.adminUser.firstname} ${this.adminUser.lastname}</p>
                                <p class="text-muted">Esta acción creará una nueva base de datos y configurará el sistema.</p>
                            </div>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, crear empresa',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#FF5722',
                        cancelButtonColor: '#grey',
                        reverseButtons: true
                    });
                    
                    if (result.isConfirmed) {
                        await this.guardarEmpresa();
                    }
                },
                
                async guardarEmpresa() {
                    this.loading = true;
                    this.finProceso = false;
                    this.mensajeResultado = '';
                    this.loadingMessage = 'Creando empresa y base de datos...<br><small>Este proceso puede tardar varios minutos</small>';
                    
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
                        this.loadingMessage = 'Registrando datos de la empresa...<br><small>Validando información</small>';
                        const registerResponse = await axios({
                            method: 'post',
                            url: window.BASE_URL + '/setup/wizard/register-company',
                            data: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        if (registerResponse.data.success) {
                            // Step 2: Create company
                            this.loadingMessage = 'Creando base de datos y configurando sistema...<br><small>Generando licencia y configurando tablas</small>';
                            const createResponse = await axios({
                                method: 'post',
                                url: window.BASE_URL + '/setup/wizard/create-company',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            
                            this.loading = false;
                            this.finProceso = true;
                            
                            if (createResponse.data.success) {
                                this.resultadoCreacion = createResponse.data;
                                this.loginUrl = createResponse.data.login_url || '/panel/login';
                                this.mensajeResultado = `✅ Empresa creada exitosamente
📊 Base de datos: ${createResponse.data.database_name}
🔑 Licencia generada y validada
👤 Usuario administrador configurado
🚀 Sistema listo para usar`;
                                
                                await Swal.fire({
                                    title: '¡Empresa Creada!',
                                    text: 'Su empresa ha sido configurada correctamente y está lista para usar.',
                                    icon: 'success',
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            } else {
                                this.mensajeResultado = '❌ Error: ' + createResponse.data.message;
                            }
                        }
                    } catch (error) {
                        this.loading = false;
                        this.finProceso = true;
                        
                        if (error.response) {
                            const { status, data } = error.response;
                            this.mensajeResultado = `❌ Error ${status}: ${data.message || 'Error desconocido al crear la empresa'}`;
                            
                            let title = 'Error';
                            let text = data.message || 'Error desconocido';
                            
                            switch (status) {
                                case 400:
                                    title = 'Error de Validación';
                                    break;
                                case 409:
                                    title = 'Conflicto';
                                    break;
                                case 500:
                                    title = 'Error del Servidor';
                                    break;
                            }
                            
                            await Swal.fire({
                                title: title,
                                text: text,
                                icon: 'error',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#FF5722'
                            });
                        } else {
                            await Swal.fire({
                                title: 'Error de Conexión',
                                text: 'No se pudo conectar al servidor. Verifique su conexión.',
                                icon: 'error',
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#FF5722'
                            });
                            this.mensajeResultado = '❌ No se pudo conectar al servidor';
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
                    if (this.adminUser.password && this.adminUser.password.length < 6) {
                        this.passwordConfirmErrors.push('La contraseña debe tener al menos 6 caracteres');
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
                },
                
                showHelp() {
                    Swal.fire({
                        title: 'Ayuda - Crear Empresa',
                        html: `
                            <div class="text-left">
                                <h4>¿Necesita ayuda?</h4>
                                <ul>
                                    <li><strong>Paso 1:</strong> Ingrese sus credenciales de distribuidor autorizado</li>
                                    <li><strong>Paso 2:</strong> Complete la información de su empresa y usuario administrador</li>
                                    <li><strong>Paso 3:</strong> Confirme y espere mientras se crea su base de datos</li>
                                </ul>
                                <p class="mt-3"><strong>Nota:</strong> El proceso puede tardar varios minutos. No cierre esta ventana.</p>
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#FF5722'
                    });
                }
            }
        });
    </script>
</body>
</html>