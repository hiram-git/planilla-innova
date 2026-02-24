<?php
use App\Helpers\PermissionHelper;

$page_title = 'Empleados Dados de Baja';

// Obtener tipo de empresa para mostrar columna condicional
$companyModel = new \App\Models\Company();
$companyConfig = $companyModel->getCompanyConfig();
$isEmpresaConPosiciones = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
$columnHeader = 'Cargo';

$content = '
<!-- Botón para volver a empleados activos -->
<div class="row mb-3">
    <div class="col-12">
        <a href="' . url('/panel/employees') . '" class="btn btn-primary btn-sm">
            <i class="fas fa-arrow-left mr-2"></i>
            Volver a Empleados Activos
        </a>
    </div>
</div>

<!-- Información sobre empleados terminados -->
<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>
    <strong>Empleados Terminados:</strong> Esta vista muestra únicamente los empleados que han sido dados de baja o terminados.
    Los empleados activos se encuentran en la <a href="' . url('/panel/employees') . '" class="alert-link">vista principal de empleados</a>.
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>
                    Lista de Empleados Terminados
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="terminatedEmployeesTable">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>ID Empleado</th>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>' . $columnHeader . '</th>
                                <th>Fecha Terminación</th>
                                <th>Motivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán vía AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>';

// Scripts para el módulo usando sistema modular
use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();

// Cargar plugins DataTables adicionales PRIMERO
$scripts = $jsConfig . '
<script src="' . url('/plugins/datatables-buttons/js/dataTables.buttons.min.js') . '"></script>
<script src="' . url('/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') . '"></script>
<script src="' . url('/plugins/jszip/jszip.min.js') . '"></script>
<script src="' . url('/plugins/pdfmake/pdfmake.min.js') . '"></script>
<script src="' . url('/plugins/pdfmake/vfs_fonts.js') . '"></script>
<script src="' . url('/plugins/datatables-buttons/js/buttons.html5.min.js') . '"></script>
';
// Cargar el módulo de empleados terminados
$scripts .= "\n" . '<script src="' . url('/assets/javascript/modules/employees/terminated.js') . '"></script>';

// GSAP ya está cargado en el layout global, solo definir funciones de animación
$scripts .= '
<script>
// Flag global para controlar animación inicial
window.terminatedEmployeesTableIsInitialLoad = true;

// Función global para animar filas del DataTable
window.animateTerminatedEmployeesTableRows = function() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const rows = $("#terminatedEmployeesTable tbody tr");

    // Si no hay filas, no hacer nada
    if (rows.length === 0) {
        return;
    }

    // Primero, asegurar que las filas estén ocultas
    gsap.set(rows, { opacity: 0, y: 0 });

    if (window.terminatedEmployeesTableIsInitialLoad) {
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

        window.terminatedEmployeesTableIsInitialLoad = false;
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
    setupTerminatedEmployeeActionButtonAnimations();
};

// Función para animar botones de acción
function setupTerminatedEmployeeActionButtonAnimations() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const badges = $("#terminatedEmployeesTable .badge");
    const buttons = $("#terminatedEmployeesTable .btn-sm");
    const icons = $("#terminatedEmployeesTable .btn-sm i");

    // Animar badges de estado
    badges.each(function(index) {
        gsap.from(this, {
            scale: 0,
            duration: 0.4,
            delay: index * 0.02,
            ease: "back.out(2)"
        });
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
</script>
';


$styles = '
<link rel="stylesheet" href="' . url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<link rel="stylesheet" href="' . url('/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') . '">
<style>
/* GSAP - Ocultar solo elementos de paginación antes de animar */
.dataTables_info,
.dataTables_paginate {
    opacity: 0;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>