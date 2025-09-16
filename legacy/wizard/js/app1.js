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
                finProceso: false, // Nueva variable para indicar que el proceso terminÃ³
                mensajeResultado: '',
                empresa: {
                    nombre: '',
                    tipo: 'Innova Hoteles',
                    ruc: '',
                    direccion: '',
                    resultado: '',
                    email: ''
                },
                usuario: {
                    user: '',
                    password: ''
                },
                nombreErrors: [],
                tipoErrors: [],
                rucErrors: [],
                direccionErrors: [],
                emailErrors: [],
                userErrors: [],
                passwordErrors: [],
                hostErrors: [],
                databaseErrors: []
            },
            methods: {
                async validarLogin() {
                    this.loading = true;
                    try {
                        const response = await axios({
                            method: 'post',
                            url: 'https://web.innovasoftlatam.com:8443/ajax/validar_login.php',
                            data: {
                                usuario: this.usuario.user,
                                password: this.usuario.password
                            },
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            transformRequest: [(data) => {
                                return Object.keys(data).map(key => `${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`).join('&');
                            }],
                            responseType: 'json'
                        });

                        this.loading = false;
                        if (response.data.success) {
                            this.empresa.email = response.data.email;
                            Swal.fire({
                                title: '',
                                text: 'Distribuidor encontrado',
                                icon: 'success',
                                timer: 600, // Desaparece en 2 segundos
                                showConfirmButton: false // Sin botÃ³n de confirmaciÃ³n
                            });

                            return true;
                        } else {
                            Swal.fire({
                                title: 'Advertencia',
                                text: response.data.message,
                                icon: 'warning',
                                timer: 600, // Desaparece en 3 segundos
                                showConfirmButton: false
                            });
                            return false;
                        }
                    } catch (error) {
                        this.loading = false;
                        Swal.fire({
                            title: 'Error',
                            text: 'OcurriÃ³ un problema al intentar validar el login',
                            icon: 'error',
                            timer: 600,
                            showConfirmButton: false
                        });
                        console.error('Error en la peticiÃ³n:', error);
                        return false;
                    }
                },
                async guardarEmpresa() {
                    this.loading = true;
                    this.finProceso = false; // Reiniciar antes de empezar
                    this.mensajeResultado = ''; // Reiniciar el mensaje
                    try {
                        const response = await axios.post('./backend/guardar.php', { empresa: this.empresa, usuario: this.usuario });
                        this.loading = false;
                        this.finProceso = true; // Indicar que el proceso terminÃ³
                        this.mensajeResultado = response.data.message; // Guardar el mensaje del servidor

                        if (response.data.status === 'success') {
                            Swal.fire({
                                title: 'Empresa Creada',
                                text: response.data.message,
                                icon: 'success',
                                timer: 600,
                                showConfirmButton: false
                            });
                            return true;
                        } else {
                            Swal.fire({
                                title: 'Advertencia',
                                text: 'Respuesta inesperada del servidor',
                                icon: 'warning',
                                timer: 600,
                                showConfirmButton: false
                            });
                            return false;
                        }
                    } catch (error) {
                        this.loading = false;
                        this.finProceso = true; // Indicar que el proceso terminÃ³, incluso si hay error
                        if (error.response) {
                            const { status, message } = error.response.data;
                            this.mensajeResultado = message; // Guardar el mensaje de error
                            switch (error.response.status) {
                                case 400:
                                    Swal.fire('Error de ValidaciÃ³n', message, 'error');
                                    break;
                                case 405:
                                    Swal.fire('Error', message, 'error');
                                    break;
                                case 409:
                                    Swal.fire('Conflicto', message, 'warning');
                                    break;
                                case 500:
                                    Swal.fire('Error del Servidor', message, 'error');
                                    break;
                                default:
                                    Swal.fire('Error', 'Error desconocido al crear la empresa', 'error');
                                    this.mensajeResultado = 'Error desconocido al crear la empresa';
                            }
                        } else {
                            Swal.fire('Error', 'No se pudo conectar al servidor', 'error');
                            this.mensajeResultado = 'No se pudo conectar al servidor';
                        }
                        return false;
                    }
                },
                async validarPaso1() {
                    this.userErrors = [];
                    this.passwordErrors = [];

                    if (!this.usuario.user) {
                        this.userErrors.push('El usuario es obligatorio.');
                    }
                    if (!this.usuario.password) {
                        this.passwordErrors.push('El password es obligatorio.');
                    }

                    if (this.userErrors.length === 0 && this.passwordErrors.length === 0) {
                        const loginValido = await this.validarLogin();
                        if (loginValido) {
                            this.step = 2;
                        }
                    }
                },
                async validarPaso2() {  
                    this.nombreErrors = [];
                    this.tipoErrors = [];

                    if (!this.empresa.nombre) {
                        this.nombreErrors.push('El nombre es obligatorio.');
                    }
                    if (!this.empresa.tipo) {
                        this.tipoErrors.push('El tipo es obligatorio.');
                    } else if (this.empresa.tipo !== 'Innova Hoteles') {
                        this.tipoErrors.push('Solo se permite el tipo "Innova Hoteles".');
                    }

                    if (this.nombreErrors.length === 0 && this.tipoErrors.length === 0) {
                        this.step = 3;// Mostrar confirmaciÃ³n antes de grabar
                        const result = await Swal.fire({
                            title: 'Â¿Guardar la empresa?',
                            text: 'Â¿EstÃ¡s seguro de que deseas crear la empresa?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'SÃ­, guardar',
                            cancelButtonText: 'No',
                            confirmButtonColor: '#FF5722',
                            cancelButtonColor: '#grey'
                        });

                        if (result.isConfirmed) {
                            const exito = await this.guardarEmpresa();
                            if (exito) {

                                window.location.href="https://hoteles.innovasoftlatam.com:8081/";
                                this.step = 3;
                            }
                        }
                        
                    }  
                },
                reiniciarFormulario() {
                    this.step = 1;
                    this.empresa = { nombre: '', ruc: '',tipo: 'Innova Hoteles', direccion: '', email: '' };
                    this.usuario = { user: '', password: '' };
                    this.empresaResultado = { nombreBaseDatos: '', num_fiscal: '', direccion: '', email: '', usuarioNombre: '', token: '' };
                }
            }   
        });