<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Backoffice | <?= $_ENV['APP_NAME'] ?? 'Planilla Innova' ?></title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">

    <style>
        .content-wrapper {
            background-color: #f4f6f9;
        }
        .info-box {
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            border-radius: .25rem;
        }
        .table-responsive {
            border-radius: .25rem;
        }
        .btn-group-sm .btn {
            padding: .25rem .5rem;
            font-size: .875rem;
        }

        /* GSAP - Hide pagination elements before animating */
        .dataTables_info,
        .dataTables_paginate {
            opacity: 0;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?= \App\Core\UrlHelper::backoffice('dashboard') ?>" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="navbar-text mr-3">
                    <i class="fas fa-shield-alt text-success"></i>
                    <small class="text-muted">Backoffice Mode</small>
                </span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= \App\Core\UrlHelper::backoffice('logout') ?>">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="<?= \App\Core\UrlHelper::backoffice('dashboard') ?>" class="brand-link">
            <i class="fas fa-shield-alt brand-image"></i>
            <span class="brand-text font-weight-light">Backoffice</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <i class="fas fa-user-shield fa-2x text-white"></i>
                </div>
                <div class="info">
                    <a href="#" class="d-block">Administrador</a>
                    <small class="text-muted">Super Admin</small>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="<?= \App\Core\UrlHelper::backoffice('dashboard') ?>" class="nav-link active">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#empresas-section" class="nav-link" onclick="scrollToEmpresas(event)">
                            <i class="nav-icon fas fa-building"></i>
                            <p>Empresas</p>
                        </a>
                    </li>
                    <li class="nav-header">CONFIGURACIÓN</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Configuración</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>Logs de Seguridad</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::backoffice('dashboard') ?>">Backoffice</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Info boxes -->
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-building"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Empresas</span>
                                <span class="info-box-number"><?= $stats['total'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Crítico (&lt;3 días)</span>
                                <span class="info-box-number"><?= $stats['red'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Alerta (&lt;7 días)</span>
                                <span class="info-box-number"><?= $stats['yellow'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Vigente (≥7 días)</span>
                                <span class="info-box-number"><?= $stats['green'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info box para licencias expiradas -->
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-times-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Licencias Expiradas</span>
                                <span class="info-box-number"><?= $stats['expired'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Empresas -->
                <div class="row" id="empresas-section">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-list"></i> Listado de Empresas</h3>
                                <div class="card-tools">
                                    <a href="<?= \App\Core\UrlHelper::route('crear-empresa') ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-plus"></i> Crear Empresa
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="empresasTable" class="table table-bordered table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Empresa</th>
                                                <th>RUC</th>
                                                <th>Licencia</th>
                                                <th>BD</th>
                                                <th>Estado</th>
                                                <th>Expiración</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- DataTables AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            <strong>Version</strong> 3.5.22
        </div>
        <strong>&copy; 2026 <?= $_ENV['APP_NAME'] ?? 'Planilla Innova' ?></strong>
    </footer>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<!-- AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- GSAP -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

<script>
// ==================== GSAP ANIMATIONS ====================

// Flag global para controlar animación inicial
window.empresasTableIsInitialLoad = true;

// Función global para animar filas del DataTable
window.animateEmpresasTableRows = function() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const rows = $("#empresasTable tbody tr");

    // Si no hay filas, no hacer nada
    if (rows.length === 0) {
        return;
    }

    // Primero, asegurar que las filas estén ocultas
    gsap.set(rows, { opacity: 0, y: 0 });

    if (window.empresasTableIsInitialLoad) {
        // Primera carga: animación más elaborada
        gsap.set(rows, { y: 20 });
        gsap.to(rows, {
            opacity: 1,
            y: 0,
            duration: 0.4,
            stagger: 0.05,
            ease: "power2.out",
            clearProps: "all"
        });

        // Animar controles de paginación
        gsap.to(".dataTables_info, .dataTables_paginate", {
            opacity: 1,
            duration: 0.5,
            delay: 0.3
        });

        window.empresasTableIsInitialLoad = false;
    } else {
        // Recargas/filtros: fade rápido
        gsap.to(rows, {
            opacity: 1,
            duration: 0.3,
            stagger: 0.02,
            ease: "power1.out",
            clearProps: "all"
        });
    }

    // Animar iconos de acciones
    setupEmpresasActionButtonAnimations();
};

// Función para animar botones de acción
function setupEmpresasActionButtonAnimations() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const badges = $("#empresasTable .badge");
    const buttons = $("#empresasTable .btn-sm");
    const icons = $("#empresasTable .btn-sm i");

    // Animar badges de estado - USAR fromTo para evitar que queden pequeños
    badges.each(function(index) {
        gsap.fromTo(this,
            {
                scale: 0,
                opacity: 0
            },
            {
                scale: 1,
                opacity: 1,
                duration: 0.4,
                delay: index * 0.02,
                ease: "back.out(2)",
                clearProps: "all"
            }
        );
    });

    // Hover effects en botones de acción
    buttons.off("mouseenter.gsap mouseleave.gsap").on({
        "mouseenter.gsap": function() {
            if (!$(this).prop("disabled")) {
                gsap.to(this, {
                    scale: 1.15,
                    duration: 0.2,
                    ease: "power2.out"
                });
            }
        },
        "mouseleave.gsap": function() {
            gsap.to(this, {
                scale: 1,
                duration: 0.2,
                ease: "power2.out"
            });
        }
    });

    // Animación para iconos dentro de botones
    icons.off("mouseenter.gsap").on("mouseenter.gsap", function() {
        if (!$(this).closest(".btn").prop("disabled")) {
            gsap.to(this, {
                rotation: 360,
                duration: 0.5,
                ease: "power2.inOut"
            });
        }
    });
}

// Función para animar info-boxes
function animateEmpresasInfoBoxes() {
    if (typeof gsap === "undefined") return;

    const infoBoxes = $('.info-box');
    if (infoBoxes.length > 0) {
        gsap.set(infoBoxes, { opacity: 0, y: 30 });
        gsap.to(infoBoxes, {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power2.out',
            clearProps: 'all'
        });
    }
}

// Función para animar iconos de info-boxes
function setupEmpresasInfoBoxIconAnimations() {
    if (typeof gsap === "undefined") return;

    const icons = $('.info-box-icon i');
    icons.on('mouseenter', function() {
        gsap.to(this, {
            rotation: 360,
            scale: 1.2,
            duration: 0.5,
            ease: 'power2.inOut'
        });
    });

    icons.on('mouseleave', function() {
        gsap.to(this, {
            rotation: 0,
            scale: 1,
            duration: 0.3,
            ease: 'power2.out'
        });
    });
}

// Función para animar botones del header
function setupEmpresasHeaderButtonAnimations() {
    if (typeof gsap === "undefined") return;

    const headerButtons = $('.card-tools .btn');

    headerButtons.on('mouseenter', function() {
        let shadowColor = 'rgba(40,167,69,0.4)'; // Green for "Crear Empresa"

        if ($(this).hasClass('btn-primary')) {
            shadowColor = 'rgba(0,123,255,0.4)';
        } else if ($(this).hasClass('btn-danger')) {
            shadowColor = 'rgba(220,53,69,0.4)';
        } else if ($(this).hasClass('btn-warning')) {
            shadowColor = 'rgba(255,193,7,0.4)';
        }

        gsap.to(this, {
            scale: 1.05,
            boxShadow: `0 5px 15px ${shadowColor}`,
            duration: 0.3,
            ease: 'power2.out'
        });
    });

    headerButtons.on('mouseleave', function() {
        gsap.to(this, {
            scale: 1,
            boxShadow: 'none',
            duration: 0.3,
            ease: 'power2.out'
        });
    });
}

// Al cargar la página, ejecutar animaciones iniciales
if (typeof gsap !== 'undefined') {
    $(document).ready(function() {
        animateEmpresasInfoBoxes();
        setupEmpresasInfoBoxIconAnimations();
        setupEmpresasHeaderButtonAnimations();
    });
}

// ==================== DATATABLE INITIALIZATION ====================

$(document).ready(function() {
    // Inicializar DataTable
    const table = $('#empresasTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= \App\Core\UrlHelper::backoffice('tenants/data') ?>',
            type: 'GET'
        },
        columns: [
            { data: 'id' },
            { data: 'company_name' },
            { data: 'ruc' },
            { data: 'license_key' },
            { data: 'db_name' },
            { data: 'status_badge', orderable: false },
            { data: 'license_expiration_badge', orderable: false },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        language: {
            processing: 'Procesando...',
            lengthMenu: 'Mostrar _MENU_ registros',
            zeroRecords: 'No se encontraron resultados',
            emptyTable: 'No hay empresas registradas',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            search: 'Buscar:',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            }
        },
        pageLength: 25,
        responsive: true,
        drawCallback: function(settings) {
            // GSAP: Animate rows after each draw
            setTimeout(function() {
                animateEmpresasTableRows();
            }, 50);
        }
    });
});

// Función para scroll suave a la sección de empresas
function scrollToEmpresas(event) {
    event.preventDefault();
    const empresasSection = document.getElementById('empresas-section');
    if (empresasSection) {
        empresasSection.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Funciones de acciones
function viewTenant(id) {
    Swal.fire({
        title: 'Ver Empresa #' + id,
        html: '<p>Funcionalidad en desarrollo...</p>',
        icon: 'info'
    });
}

function editTenant(id) {
    Swal.fire({
        title: 'Editar Empresa #' + id,
        html: '<p>Funcionalidad en desarrollo...</p>',
        icon: 'info'
    });
}

function deleteTenant(id) {
    Swal.fire({
        title: '¿Eliminar Empresa?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire(
                'En desarrollo',
                'Funcionalidad de eliminación en desarrollo',
                'info'
            );
        }
    });
}
</script>

</body>
</html>
