<?php
use App\Helpers\PermissionHelper;

$page_title = 'Gestión de Empleados';

// Obtener tipo de empresa para mostrar columna condicional
$companyModel = new \App\Models\Company();
$companyConfig = $companyModel->getCompanyConfig();
$isEmpresaConPosiciones = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
$columnHeader = 'Cargo';

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Empleados</h3>
                <div class="card-tools">
                    ' . PermissionHelper::createButton('employees', [
                        'url' => url('/panel/employees/create'),
                        'text' => 'Agregar Empleado',
                        'class' => 'btn btn-primary btn-sm'
                    ]) . '
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="employeesTable">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>ID Empleado</th>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>' . $columnHeader . '</th>
                                <th>Horario</th>
                                <th>Fecha Creación</th>
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
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmar Eliminación</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar este empleado?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>';

// Scripts para el módulo usando sistema modular
use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();

// GSAP ya está cargado en el layout global, solo definir funciones de animación
$scripts = $jsConfig . '
<script>
// Flag global para controlar animación inicial
window.employeesTableIsInitialLoad = true;

// Función global para animar filas del DataTable
window.animateEmployeesTableRows = function() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const rows = $("#employeesTable tbody tr");

    // Si no hay filas, no hacer nada
    if (rows.length === 0) {
        return;
    }

    // Primero, asegurar que las filas estén ocultas
    gsap.set(rows, { opacity: 0, y: 0 });

    if (window.employeesTableIsInitialLoad) {
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

        window.employeesTableIsInitialLoad = false;
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
    setupEmployeeActionButtonAnimations();
};

// Función para animar botones de acción
function setupEmployeeActionButtonAnimations() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const badges = $("#employeesTable .badge");
    const buttons = $("#employeesTable .btn-sm");
    const icons = $("#employeesTable .btn-sm i");

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

// Efecto hover en botón "Agregar Empleado" (mismo estilo que login)
$(document).ready(function() {
    const addEmployeeBtn = $(".card-tools .btn-primary");

    if (addEmployeeBtn.length > 0) {
        addEmployeeBtn.on("mouseenter", function() {
            gsap.to(this, {
                scale: 1.05,
                boxShadow: "0 5px 15px rgba(0,123,255,0.4)",
                duration: 0.3,
                ease: "power2.out"
            });
        });

        addEmployeeBtn.on("mouseleave", function() {
            gsap.to(this, {
                scale: 1,
                boxShadow: "none",
                duration: 0.3,
                ease: "power2.out"
            });
        });
    }
});
</script>
';

// Cargar el módulo de empleados (DataTables ya está en el layout global)
$scripts .= "\n" . '<script src="' . url('/assets/javascript/modules/employees/index.js') . '"></script>';

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