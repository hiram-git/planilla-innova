<?php
$styles = '
<style>
    body {
        background-image: url("' . url('images/portada.jpg', false) . '");
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        height: 100vh;
        overflow: hidden;
    }
    .login-box {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }

    /* Ocultar elementos antes de animar (evitar flash) */
    .login-card-body {
        overflow: hidden; /* Evitar desbordamiento durante animaciones */
    }

    .login-box-msg {
        opacity: 0;
        transform: translateY(-20px);
    }

    .input-group {
        opacity: 0;
    }

    .text-muted {
        opacity: 0;
    }

    .btn-primary {
        opacity: 0;
        transform: scale(0.95);
    }

    .callout {
        opacity: 0;
    }
</style>';

$bodyClass = 'hold-transition login-page';

// Recuperar datos de sesión para UX mejorada
$saved_username = $_SESSION['login_username'] ?? '';
$saved_company_code = $_SESSION['login_company_code'] ?? '';

// Limpiar datos de sesión después de recuperarlos (solo una vez)
unset($_SESSION['login_username'], $_SESSION['login_company_code']);

// Prioridad: datos de sesión > parámetros GET
$username_value = !empty($saved_username) ? htmlspecialchars($saved_username) : '';
$company_value = !empty($saved_company_code) ? htmlspecialchars($saved_company_code) : ($_GET['company'] ?? '');

$content = '
<div class="row">
    <div class="col-md-9"></div>
    <div class="col-md-3">
        <div class="login-box">
            <div class="card">
                <div class="card-body login-card-body">
                    <p class="login-box-msg">
                        <h1><b>Planilla Simple</b></h1>
                        <p>Gestión de Recursos Humanos</p>
                    </p>

                    <form action="' . \App\Core\UrlHelper::panel('login') . '" method="POST">
                        <input type="hidden" name="csrf_token" value="' . $csrf_token . '">

                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="username" placeholder="Usuario" value="' . $username_value . '" required autofocus>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-user"></span>
                                </div>
                            </div>
                        </div>

                        <div class="input-group mb-3">
                            <input type="password" class="form-control" name="password" placeholder="Clave" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>

                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="company_code" placeholder="Licencia" value="' . $company_value . '">
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-key"></span>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mb-3">
                            <i class="fas fa-info-circle"></i> Ingresa tu código de licencia (formato: PINN1234567890) o deja vacío para BD principal
                        </small>

                        <div class="row text-right">
                            <div class="col-12 pull-right">
                                <button type="submit" class="btn btn-primary" name="login">
                                    <i class="fas fa-sign-in-alt"></i> Entrar
                                </button>
                            </div>
                        </div>
                    </form>';

if (isset($_SESSION['error'])) {
    $content .= '
                    <div class="callout callout-danger mt-3">
                        <p>' . $_SESSION['error'] . '</p>
                    </div>';
    unset($_SESSION['error']);
}

$content .= '
                </div>
            </div>
        </div>
    </div>
</div>';

$scripts = '
<!-- GSAP Library -->
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>

<script src="' . url('js/tenant-storage-manager.js') . '"></script>
<script>
    // Limpiar storage al cargar la página de login
    document.addEventListener("DOMContentLoaded", function() {
        console.log("[Login] Limpiando storage al cargar página de login");

        // Verificar si viene de logout
        const urlParams = new URLSearchParams(window.location.search);
        const fromLogout = urlParams.get("logout") === "1";

        if (fromLogout) {
            console.log("[Login] Usuario viene de logout - limpieza completa");
            TenantStorageManager.clearOnLogout();
        } else {
            console.log("[Login] Carga normal - limpieza de datos de tenant");
            TenantStorageManager.clearTenantData();
        }

        // ========================================
        // GSAP ANIMATIONS - Login Page (2 Bloques Simultáneos)
        // ========================================

        // Verificar si existe callout de error/mensaje
        const errorCallout = document.querySelector(".callout-danger, .callout-warning, .callout-info, .callout-success");

        // Timeline principal con animaciones rápidas
        const tl = gsap.timeline({
            defaults: {
                ease: "power2.out",
                duration: 0.3
            }
        });

        // ========================================
        // BLOQUE 1: Formulario (logo + campos + texto ayuda + botón)
        // ========================================

        // 1. Logo/título - entrada desde arriba
        tl.to(".login-box-msg", {
            y: 0,
            opacity: 1,
            clearProps: "transform",
            duration: 0.4,
            ease: "power2.out"
        }, 0) // Inicia en t=0

        // 2. Campos del formulario
        .to(".input-group", {
            opacity: 1,
            y: 0,
            stagger: 0.05,
            duration: 0.3,
            ease: "power2.out"
        }, 0.1) // Inicia en t=0.1 (overlap con logo)

        // 3. Texto de ayuda
        .to(".text-muted", {
            opacity: 1,
            duration: 0.2
        }, 0.2) // Inicia en t=0.2

        // 4. Botón de Entrar - scale up
        .to(".btn-primary", {
            opacity: 1,
            scale: 1,
            clearProps: "transform",
            ease: "back.out(1.5)",
            duration: 0.3
        }, 0.25); // Inicia en t=0.25

        // ========================================
        // BLOQUE 2: Callout de errores/mensajes (SI EXISTE)
        // Se anima SIMULTÁNEAMENTE con el Bloque 1
        // ========================================
        if (errorCallout) {
            // Fade-in del callout
            tl.to(errorCallout, {
                opacity: 1,
                duration: 0.4,
                ease: "power2.out"
            }, 0) // Inicia en t=0 (simultáneo con Bloque 1)

            // Shake effect EXAGERADO para llamar atención (después de aparecer)
            .to(errorCallout, {
                keyframes: [
                    { x: -12, duration: 0.05 },
                    { x: 12, duration: 0.05 },
                    { x: -10, duration: 0.05 },
                    { x: 10, duration: 0.05 },
                    { x: -8, duration: 0.05 },
                    { x: 8, duration: 0.05 },
                    { x: -5, duration: 0.05 },
                    { x: 5, duration: 0.05 },
                    { x: 0, duration: 0.1 }
                ],
                ease: "power2.inOut"
            }, 0.4); // Inicia en t=0.4 (después del fade-in)
        }

        // Hover effect en el botón (animación continua)
        const loginBtn = document.querySelector(".btn-primary");
        if (loginBtn) {
            loginBtn.addEventListener("mouseenter", function() {
                gsap.to(this, {
                    scale: 1.05,
                    boxShadow: "0 5px 15px rgba(0,123,255,0.4)",
                    duration: 0.3
                });
            });

            loginBtn.addEventListener("mouseleave", function() {
                gsap.to(this, {
                    scale: 1,
                    boxShadow: "none",
                    duration: 0.3
                });
            });
        }
    });
</script>';

include __DIR__ . '/../layouts/main.php';
?>