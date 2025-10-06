<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Página no encontrada | Sistema de Planillas</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= \App\Core\UrlHelper::asset('plugins/fontawesome-free/css/all.min.css') ?>">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= \App\Core\UrlHelper::asset('dist/css/adminlte.min.css') ?>">
</head>
<body class="hold-transition">
    <div class="content-wrapper" style="margin-left: 0;">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12">
                        <h1>404 Error</h1>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="error-page">
                <h2 class="headline text-warning"> 404</h2>

                <div class="error-content">
                    <h3><i class="fas fa-exclamation-triangle text-warning"></i> Oops! Página no encontrada.</h3>

                    <p>
                        No pudimos encontrar la página que estás buscando.
                        Mientras tanto, puedes <a href="<?= \App\Core\UrlHelper::route('panel') ?>">regresar al dashboard</a> o intentar usar el menú de navegación.
                    </p>

                    <?php if (isset($url) && !empty($url)): ?>
                    <div class="callout callout-info">
                        <h5><i class="fas fa-info"></i> Información de la solicitud:</h5>
                        <p><strong>URL solicitada:</strong> /<?= htmlspecialchars(implode('/', $url ?? [])) ?></p>
                        <?php if (isset($controller)): ?>
                        <p><strong>Controlador buscado:</strong> <?= htmlspecialchars(is_object($controller) ? get_class($controller) : (is_string($controller) ? $controller : 'No definido')) ?></p>
                        <?php endif; ?>
                        <?php if (isset($method)): ?>
                        <p><strong>Método buscado:</strong> <?= htmlspecialchars($method ?? 'No definido') ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['debug']) && isset($availableControllers)): ?>
                    <div class="callout callout-warning">
                        <h5><i class="fas fa-code"></i> Modo Debug - Controladores disponibles:</h5>
                        <ul>
                            <?php foreach ($availableControllers as $ctrl): ?>
                                <li><?= htmlspecialchars($ctrl) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="<?= \App\Core\UrlHelper::route('panel') ?>" class="btn btn-primary">
                            <i class="fas fa-home"></i> Ir al Dashboard
                        </a>
                        <a href="javascript:history.back()" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Regresar
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- jQuery -->
    <script src="<?= \App\Core\UrlHelper::asset('plugins/jquery/jquery.min.js') ?>"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= \App\Core\UrlHelper::asset('plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <!-- AdminLTE App -->
    <script src="<?= \App\Core\UrlHelper::asset('dist/js/adminlte.min.js') ?>"></script>
</body>
</html>
