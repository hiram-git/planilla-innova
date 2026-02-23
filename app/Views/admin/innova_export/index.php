<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fa fa-file-export"></i> Exportación a ERP INNOVA</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= \App\Core\UrlHelper::route('/panel/dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Exportación INNOVA</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Información del módulo -->
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5><i class="icon fa fa-info-circle"></i> Exportación a ERP INNOVA</h5>
                    Este módulo permite exportar planillas procesadas o cerradas al formato de texto plano requerido por el sistema ERP INNOVA.
                    El archivo generado incluye movimientos individuales, netos por empleado y totales por área.
                </div>
            </div>
        </div>

        <!-- Tabla de planillas -->
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

    </div>
</div>

<?php
// Capturar scripts
ob_start();
?>
<script>
$(document).ready(function() {
    const baseUrl = '<?= \App\Core\UrlHelper::base() ?>';

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
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]]
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
?>
