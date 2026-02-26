<?php
$page_title = $data['title'] ?? 'Exportación INNOVA';

$content = '
<div class="row">
    <div class="col-12">
        <!-- Información del módulo -->
        <div class="alert alert-info">
            <h5><i class="icon fa fa-info-circle"></i> Exportación a ERP INNOVA</h5>
            Este módulo permite exportar planillas procesadas o cerradas al formato de texto plano requerido por el sistema ERP INNOVA.
            El archivo generado incluye movimientos individuales, netos por empleado y totales por área.
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-list"></i> Planillas Disponibles para Exportar</h3>
            </div>
            <div class="card-body">
                <table id="payrollsTable" class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Descripción</th>
                            <th>Tipo Planilla</th>
                            <th>Frecuencia</th>
                            <th>Fecha Pago</th>
                            <th>Período</th>
                            <th>Estado</th>
                            <th>Empleados</th>
                            <th>Total Neto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Datos cargados por AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
';

// Capturar scripts
ob_start();
?>
<script>
$(document).ready(function() {
    const baseUrl = '<?= \App\Core\UrlHelper::base() ?>';

    // =========================================================================
    // GSAP ANIMATIONS
    // =========================================================================

    /**
     * 1. Animar alert informativo
     */
    function animateInfoAlert() {
        const alert = $(".alert-info");
        if (alert.length > 0) {
            gsap.from(alert, {
                opacity: 0,
                x: -30,
                duration: 0.6,
                ease: "power2.out",
                clearProps: "all"
            });
        }
    }

    /**
     * 2. Animar card principal
     */
    function animateMainCard() {
        const card = $(".card");
        if (card.length > 0) {
            gsap.from(card, {
                opacity: 0,
                y: 20,
                duration: 0.5,
                delay: 0.2,
                ease: "power2.out",
                clearProps: "all"
            });
        }
    }

    /**
     * 3. Animar filas de la tabla
     */
    function animateTableRows() {
        const rows = $("#payrollsTable tbody tr");
        if (rows.length > 0) {
            gsap.from(rows, {
                opacity: 0,
                y: 10,
                duration: 0.4,
                stagger: 0.03,
                ease: "power2.out",
                clearProps: "all"
            });
        }
    }

    /**
     * 4. Animar badges de estado
     */
    function animateBadges() {
        const badges = $("#payrollsTable tbody .badge");
        if (badges.length > 0) {
            gsap.fromTo(badges,
                {
                    scale: 0.8,
                    opacity: 0
                },
                {
                    scale: 1,
                    opacity: 1,
                    duration: 0.3,
                    stagger: 0.02,
                    ease: "back.out(1.7)",
                    clearProps: "all"
                }
            );
        }
    }

    /**
     * 5. Configurar animaciones hover en botones de exportar
     */
    function setupExportButtonAnimations() {
        $(document).on('mouseenter', '.btn-export', function() {
            gsap.to($(this).find('i'), {
                rotation: 360,
                scale: 1.2,
                duration: 0.4,
                ease: "power2.out"
            });
        });

        $(document).on('mouseleave', '.btn-export', function() {
            gsap.to($(this).find('i'), {
                rotation: 0,
                scale: 1,
                duration: 0.3,
                ease: "power2.inOut"
            });
        });
    }

    /**
     * 6. Configurar animaciones hover en botones de vista
     */
    function setupViewButtonAnimations() {
        $(document).on('mouseenter', '.btn-info', function() {
            gsap.to($(this).find('i'), {
                scale: 1.15,
                duration: 0.3,
                ease: "power2.out"
            });
        });

        $(document).on('mouseleave', '.btn-info', function() {
            gsap.to($(this).find('i'), {
                scale: 1,
                duration: 0.3,
                ease: "power2.inOut"
            });
        });
    }

    /**
     * 7. Animar números (totales empleados y montos)
     */
    function animateNumbers() {
        // Animar columnas numéricas con efecto de conteo
        const numberCells = $("#payrollsTable tbody tr td:nth-child(8), #payrollsTable tbody tr td:nth-child(9)");
        if (numberCells.length > 0) {
            gsap.from(numberCells, {
                opacity: 0,
                scale: 0.9,
                duration: 0.5,
                stagger: 0.02,
                ease: "back.out(1.7)",
                clearProps: "all"
            });
        }
    }

    // Ejecutar animaciones iniciales
    animateInfoAlert();
    animateMainCard();
    setupExportButtonAnimations();
    setupViewButtonAnimations();

    // =========================================================================
    // DATATABLE CONFIGURATION
    // =========================================================================

    // Inicializar DataTable
    const table = $("#payrollsTable").DataTable({
        ajax: {
            url: baseUrl + '/panel/innova-export/data',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id' },
            { data: 'descripcion' },
            { data: 'tipo_planilla' },
            { data: 'frecuencia' },
            { data: 'fecha' },
            { data: 'periodo' },
            {
                data: 'estado',
                render: function(data) {
                    const badges = {
                        'PROCESADA': 'badge-success',
                        'CERRADA': 'badge-secondary'
                    };
                    return '<span class="badge ' + (badges[data] || 'badge-info') + '">' + data + '</span>';
                }
            },
            {
                data: 'total_empleados',
                className: 'text-center'
            },
            {
                data: 'total_neto',
                className: 'text-right',
                render: function(data) {
                    return '$' + data;
                }
            },
            {
                data: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-center'
            }
        ],
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        drawCallback: function(settings) {
            // Animar elementos después de cada redibujado de la tabla
            setTimeout(function() {
                animateTableRows();
                animateBadges();
                animateNumbers();
            }, 50);
        }
    });

    // Manejar clic en botón exportar
    $(document).on('click', '.btn-export', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const payrollId = $(this).data('id');

        // Confirmar exportación
        Swal.fire({
            title: '¿Exportar planilla?',
            text: 'Se generará un archivo TXT con el formato INNOVA',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-file-export"></i> Exportar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Generando archivo...',
                    text: 'Por favor espere',
                    icon: 'info',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Redirigir para descargar
                window.location.href = url;

                // Cerrar loading después de 2 segundos
                setTimeout(() => {
                    Swal.close();
                    toastr.success('Archivo generado exitosamente');
                }, 2000);
            }
        });
    });

    // Mostrar mensajes de sesión
    <?php if (isset($_SESSION['success_message'])): ?>
        toastr.success('<?= $_SESSION['success_message'] ?>');
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        toastr.error('<?= $_SESSION['error_message'] ?>');
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
});
</script>
<?php
$scripts = ob_get_clean();

include __DIR__ . '/../../layouts/admin.php';
?>
