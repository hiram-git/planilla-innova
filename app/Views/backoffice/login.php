<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice - Acceso Seguro | <?= $_ENV['APP_NAME'] ?? 'Planilla Innova' ?></title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CryptoJS para encriptación cliente -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 400px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            text-align: center;
            padding: 25px;
        }
        .card-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 300;
        }
        .card-header .subtitle {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 5px;
        }
        .card-body {
            padding: 30px;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
            border-right: none;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #667eea;
        }
        .input-group-append .btn {
            border-left: none;
            background-color: #f8f9fa;
            color: #666;
        }
        .input-group-append .btn:hover {
            background-color: #e9ecef;
            color: #333;
        }
        .input-group-append .btn:focus {
            box-shadow: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        .security-badge {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
        .security-badge i {
            color: #28a745;
            margin-right: 5px;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        #loadingSpinner {
            display: none;
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-shield-alt"></i> BACKOFFICE</h1>
            <div class="subtitle">Panel de Administración de Tenants</div>
        </div>

        <div class="card-body">
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div id="errorMessage" class="alert alert-danger" style="display: none;">
                <i class="fas fa-exclamation-circle"></i> <span id="errorText"></span>
            </div>

            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label for="access_key_plain">
                        <i class="fas fa-key"></i> Clave de Acceso
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input
                            type="password"
                            class="form-control"
                            id="access_key_plain"
                            placeholder="Ingrese su clave de acceso"
                            required
                            autocomplete="off"
                        >
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-info-circle"></i> La clave se encripta antes de enviar
                    </small>
                </div>

                <input type="hidden" id="access_key" name="access_key">

                <button type="submit" class="btn btn-primary btn-block" id="loginButton">
                    <span id="loginButtonText">
                        <i class="fas fa-sign-in-alt"></i> Acceder
                    </span>
                    <span id="loadingSpinner">
                        <i class="fas fa-spinner fa-spin"></i> Validando...
                    </span>
                </button>
            </form>

            <div class="security-badge">
                <i class="fas fa-shield-check"></i>
                Conexión segura con encriptación AES-256
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
(function() {
    'use strict';

    // Encryption key derivation (debe coincidir con PHP)
    const APP_KEY = '<?= $_ENV['APP_KEY'] ?? 'default-insecure-key-change-me' ?>';

    // DEBUG: Verificar APP_KEY
    console.log('APP_KEY loaded:', APP_KEY.substring(0, 10) + '...');
    console.log('APP_KEY length:', APP_KEY.length);

    /**
     * Encriptar token usando AES-256-CBC (compatible con PHP)
     */
    function encryptToken(plainText) {
        try {
            console.log('Encrypting plain text:', plainText);
            console.log('Plain text length:', plainText.length);
            // Derivar clave de 256 bits desde APP_KEY
            const key = CryptoJS.SHA256(APP_KEY);

            // Derivar IV de 128 bits desde APP_KEY
            const ivString = CryptoJS.SHA256(APP_KEY + '_iv').toString();
            const iv = CryptoJS.enc.Hex.parse(ivString.substring(0, 32)); // 16 bytes = 32 hex chars

            // Encriptar
            const encrypted = CryptoJS.AES.encrypt(plainText, key, {
                iv: iv,
                mode: CryptoJS.mode.CBC,
                padding: CryptoJS.pad.Pkcs7
            });

            // Retornar en base64
            const encryptedString = encrypted.toString();
            console.log('Encrypted token:', encryptedString.substring(0, 30) + '...');
            console.log('Encrypted token length:', encryptedString.length);

            return encryptedString;

        } catch (error) {
            console.error('Encryption error:', error);
            return null;
        }
    }

    /**
     * Manejar envío del formulario
     */
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const plainToken = document.getElementById('access_key_plain').value.trim();
        const errorDiv = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        const loginButton = document.getElementById('loginButton');
        const loginButtonText = document.getElementById('loginButtonText');
        const loadingSpinner = document.getElementById('loadingSpinner');

        // Validación básica
        if (!plainToken) {
            showError('Por favor ingrese su clave de acceso');
            return;
        }

        // Ocultar errores previos
        errorDiv.style.display = 'none';

        // Mostrar loading
        loginButtonText.style.display = 'none';
        loadingSpinner.style.display = 'inline';
        loginButton.disabled = true;

        // Encriptar token
        const encryptedToken = encryptToken(plainToken);

        if (!encryptedToken) {
            showError('Error al encriptar la clave. Por favor intente nuevamente.');
            resetButton();
            return;
        }

        console.log('Sending encrypted token to server...');

        // Asignar token encriptado al campo oculto
        document.getElementById('access_key').value = encryptedToken;

        // Enviar formulario con AJAX
        const formData = new FormData();
        formData.append('access_key', encryptedToken);
        formData.append('access_key_plain', plainToken); // DEBUG: también enviar en texto plano para verificar

        fetch('<?= \App\Core\UrlHelper::backoffice('authenticate') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Éxito - redirigir
                window.location.href = data.redirect || '<?= \App\Core\UrlHelper::backoffice('dashboard') ?>';
            } else {
                // Error - mostrar mensaje
                showError(data.message || 'Clave de acceso inválida');
                resetButton();
            }
        })
        .catch(error => {
            console.error('Authentication error:', error);
            showError('Error de conexión. Por favor intente nuevamente.');
            resetButton();
        });
    });

    /**
     * Mostrar mensaje de error
     */
    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        errorText.textContent = message;
        errorDiv.style.display = 'block';

        // Auto-hide después de 5 segundos
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    /**
     * Resetear botón de login
     */
    function resetButton() {
        const loginButton = document.getElementById('loginButton');
        const loginButtonText = document.getElementById('loginButtonText');
        const loadingSpinner = document.getElementById('loadingSpinner');

        loginButtonText.style.display = 'inline';
        loadingSpinner.style.display = 'none';
        loginButton.disabled = false;
    }

    // Focus automático en el campo de texto
    document.getElementById('access_key_plain').focus();

    /**
     * Toggle show/hide password
     */
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('access_key_plain');
        const toggleIcon = document.getElementById('togglePasswordIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    });

})();
</script>

</body>
</html>
