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
</style>';

$bodyClass = 'hold-transition login-page';

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
                            <input type="text" class="form-control" name="username" placeholder="Usuario" required autofocus>
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

$scripts = '';

include __DIR__ . '/../layouts/main.php';
?>