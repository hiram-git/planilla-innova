<?php
$page_title = 'Documentos laborales';

$companyModel = new \App\Models\Company();
$companyConfig = $companyModel->getCompanyConfig();
$isEmpresaConPosiciones = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
$columnHeader = $isEmpresaConPosiciones ? 'Posicion' : 'Cargo';

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Documentos laborales por empleado</h3>
                <div class="card-tools">
                    <span class="badge badge-success"><i class="fas fa-check"></i> Sistema Activo</span>
                </div>
            </div>
            <div class="card-body">
                <div class="callout callout-success">
                    <h5><i class="icon fas fa-check"></i> Generación de Documentos Disponible</h5>
                    <p class="mb-0">Seleccione un empleado de la tabla y haga clic en el botón "Documentos" para generar cartas de trabajo y contratos en formato PDF o Word.</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="employeeDocumentsTable">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>ID Empleado</th>
                                <th>Nombre</th>
                                <th>Cedula</th>
                                <th>' . $columnHeader . '</th>
                                <th>Documentos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargaran via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>';

use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();

// GSAP: Funciones globales de animación para DataTable
$gsapAnimationScript = '
<script>
// Flag global para controlar animación inicial
window.employeeDocumentsTableIsInitialLoad = true;

// Función global para animar filas del DataTable
window.animateEmployeeDocumentsTableRows = function() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const rows = $("#employeeDocumentsTable tbody tr");

    // Si no hay filas, no hacer nada
    if (rows.length === 0) {
        return;
    }

    // Primero, asegurar que las filas estén ocultas
    gsap.set(rows, { opacity: 0, y: 0 });

    if (window.employeeDocumentsTableIsInitialLoad) {
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

        window.employeeDocumentsTableIsInitialLoad = false;
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
    setupEmployeeDocumentActionButtonAnimations();
};

// Función para animar botones de acción
function setupEmployeeDocumentActionButtonAnimations() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const badges = $("#employeeDocumentsTable .badge");
    const buttons = $("#employeeDocumentsTable .btn-sm, #employeeDocumentsTable .generate-document");
    const icons = $("#employeeDocumentsTable .btn-sm i, #employeeDocumentsTable .generate-document i");

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

// Cargar módulo de employee-documents
$scripts = $jsConfig . "\n" . $gsapAnimationScript . "\n" . '<script src="' . url('/assets/javascript/modules/employee-documents/index.js') . '"></script>';

$styles = '
<link rel="stylesheet" href="' . url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<style>
/* GSAP - Ocultar solo elementos de paginación antes de animar */
.dataTables_info,
.dataTables_paginate {
    opacity: 0;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>