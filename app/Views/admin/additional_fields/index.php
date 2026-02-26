<?php
use App\Helpers\PermissionHelper;

$page_title = 'Campos Adicionales';

$content = '
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-square"></i> Gestión de Campos Adicionales Personalizados</h3>
                <div class="card-tools">';

if (PermissionHelper::canWrite('additional-fields')) {
    $content .= '
                    <a href="' . url('/panel/additional-fields/create') . '" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Agregar Campo
                    </a>';
}

$content .= '
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Información:</strong> Los campos adicionales permiten personalizar la información de los empleados.
                    Pueden ser utilizados en fórmulas de conceptos usando la función <code>ADICIONALES("CODIGO")</code>.
                </div>';

if (isset($_SESSION['success'])) {
    $content .= '
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> ' . $_SESSION['success'] . '
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $content .= '
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error'] . '
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>';
    unset($_SESSION['error']);
}

$content .= '
                <table id="fieldsTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 30px;"><input type="checkbox" id="checkAll"></th>
                            <th>Código</th>
                            <th>Etiqueta</th>
                            <th>Tipo</th>
                            <th>Valor Defecto</th>
                            <th style="width: 80px;">Orden</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Cargado dinámicamente por DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';

$scripts = '
<script>
// GSAP: Flag global para controlar animación inicial
window.additionalFieldsTableIsInitialLoad = true;

// GSAP: Función para animar filas del DataTable
window.animateAdditionalFieldsTableRows = function() {
    if (typeof gsap === "undefined") {
        return;
    }

    const rows = $("#fieldsTable tbody tr");
    if (rows.length === 0) {
        return;
    }

    // Ocultar filas inicialmente
    gsap.set(rows, { opacity: 0, y: 0 });

    if (window.additionalFieldsTableIsInitialLoad) {
        // Primera carga: animación elaborada
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

        window.additionalFieldsTableIsInitialLoad = false;
    } else {
        // Recargas: fade rápido
        gsap.to(rows, {
            opacity: 1,
            duration: 0.3,
            stagger: 0.02,
            ease: "power1.out",
            clearProps: "all"
        });
    }

    // Animar elementos de la tabla
    setupAdditionalFieldsActionAnimations();
};

// GSAP: Función para animar botones y badges
function setupAdditionalFieldsActionAnimations() {
    if (typeof gsap === "undefined") {
        return;
    }

    const badges = $("#fieldsTable .badge");
    const buttons = $("#fieldsTable .btn-sm");
    const icons = $("#fieldsTable .btn-sm i");

    // Animar badges
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

$(document).ready(function() {
    // Inicializar DataTables
    var table = $("#fieldsTable").DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "' . url('/panel/additional-fields/datatables-ajax') . '",
            type: "GET"
        },
        columns: [
            { data: 0, orderable: false, searchable: false },
            { data: 1 },
            { data: 2 },
            { data: 3, orderable: false },
            { data: 4, orderable: false },
            { data: 5 },
            { data: 6 },
            { data: 7, orderable: false, searchable: false }
        ],
        order: [[5, "asc"]], // Ordenar por orden
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
        },
        pageLength: 25,
        stateSave: true,
        responsive: true,
        drawCallback: function(settings) {
            // GSAP: Animar filas después de cada draw
            if (typeof window.animateAdditionalFieldsTableRows === "function") {
                setTimeout(function() {
                    window.animateAdditionalFieldsTableRows();
                }, 50);
            }
        }
    });

    // Check all checkboxes
    $("#checkAll").on("click", function() {
        $(".field-checkbox").prop("checked", this.checked);
    });

    // Manejar eliminación
    $("#fieldsTable").on("click", ".delete-btn", function() {
        var fieldId = $(this).data("id");
        var fieldName = $(this).data("name");

        Swal.fire({
            title: "¿Eliminar campo adicional?",
            html: `¿Está seguro de eliminar el campo <strong>${fieldName}</strong>?<br>
                   <small class="text-danger">Se eliminarán también todos los valores asociados a este campo.</small>`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar",
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario para enviar POST
                var form = $("<form>", {
                    "method": "POST",
                    "action": "' . url('/panel/additional-fields/delete/') . '" + fieldId
                });

                form.append($("<input>", {
                    "type": "hidden",
                    "name": "csrf_token",
                    "value": "' . ($csrf_token ?? '') . '"
                }));

                $("body").append(form);
                form.submit();
            }
        });
    });

    // GSAP: Efecto hover en botón "Agregar Campo"
    if (typeof gsap !== "undefined") {
        const addButton = $(".card-tools .btn-primary");

        addButton.on("mouseenter", function() {
            gsap.to(this, {
                scale: 1.05,
                boxShadow: "0 5px 15px rgba(0,123,255,0.4)",
                duration: 0.3,
                ease: "power2.out"
            });
        });

        addButton.on("mouseleave", function() {
            gsap.to(this, {
                scale: 1,
                boxShadow: "none",
                duration: 0.3,
                ease: "power2.out"
            });
        });
    }
});
</script>';

$styles = '
<style>
/* GSAP - Ocultar elementos de paginación antes de animar */
.dataTables_info,
.dataTables_paginate {
    opacity: 0;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
