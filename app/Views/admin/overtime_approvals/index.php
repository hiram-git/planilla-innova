<?php
/**
 * Vista: Aprobación de Horas Extras - Registros Diarios
 * Ruta: /panel/overtime-approvals
 */
?>

<!-- Main content -->
<div class="content">
    <div class="container-fluid">

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">HE Pendientes</span>
                        <span class="info-box-number"><?= number_format($pending_stats['total_hours'] ?? 0, 2) ?> hrs</span>
                        <span class="info-box-text"><?= $pending_stats['total_records'] ?? 0 ?> registros</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">HE Aprobadas</span>
                        <span class="info-box-number"><?= number_format($approved_stats['total_hours'] ?? 0, 2) ?> hrs</span>
                        <span class="info-box-text"><?= $approved_stats['total_records'] ?? 0 ?> registros</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-orange">
                    <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">HE 25% Pendientes</span>
                        <span class="info-box-number"><?= number_format($pending_stats['total_overtime_25'] ?? 0, 2) ?> hrs</span>
                        <span class="info-box-text">&nbsp;</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-check-double"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">HE 25% Aprobadas</span>
                        <span class="info-box-number"><?= number_format($approved_stats['total_overtime_25'] ?? 0, 2) ?> hrs</span>
                        <span class="info-box-text">&nbsp;</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="filterForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Estado</label>
                                        <select class="form-control" id="filter_status" name="status">
                                            <option value="" selected>Todos</option>
                                            <option value="PENDING">Pendientes</option>
                                            <option value="APPROVED">Aprobados</option>
                                            <option value="REJECTED">Rechazados</option>
                                            <option value="NOT_APPLICABLE">No Aplica</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Empleado</label>
                                        <select class="form-control select2" id="filter_employee" name="employee_id">
                                            <option value="">Todos</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Fecha Desde</label>
                                        <input type="date" class="form-control" id="filter_date_from" name="date_from">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Fecha Hasta</label>
                                        <input type="date" class="form-control" id="filter_date_to" name="date_to">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="clearFilters">
                                        <i class="fas fa-eraser"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Registros Diarios de Horas Extras</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="pending-table" class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Empleado</th>
                                        <th>Horario</th>
                                        <th>Fecha</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>H. Regulares</th>
                                        <th>H. Extras 25%</th>
                                        <th>H. Extras 50%</th>
                                        <th>Total HE</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Detalle del Registro</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="detail-content">
                <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> Aprobar Horas Extras</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="approveForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="calculation_id" id="approve_calculation_id">
                <div class="modal-body">
                    <div id="approve_summary" class="alert alert-info"></div>
                    <div class="form-group">
                        <label>Notas (opcional)</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Comentarios adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Aprobar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title"><i class="fas fa-times-circle"></i> Rechazar Horas Extras</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="rejectForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="calculation_id" id="reject_calculation_id">
                <div class="modal-body">
                    <div id="reject_summary" class="alert alert-warning"></div>
                    <div class="form-group">
                        <label>Razón del Rechazo *</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Explique la razón del rechazo..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Rechazar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Iniciar captura de scripts
ob_start();
?>
<script>
$(document).ready(function() {
    const baseUrl = '<?= \App\Core\UrlHelper::base() ?>';
    const csrfToken = '<?= $csrf_token ?>';

    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Cargar empleados para filtro
    $.get(baseUrl + '/panel/employees/get-active', function(employees) {
        $("#filter_employee").empty().append("<option value=''>Todos</option>");
        employees.forEach(function(emp) {
            $("#filter_employee").append(`<option value="${emp.id}">${emp.employee_id} - ${emp.firstname} ${emp.lastname}</option>`);
        });
    });

    // DataTable
    const table = $("#pending-table").DataTable({
        ajax: {
            url: baseUrl + '/panel/overtime-approvals/pending-data',
            data: function(d) {
                d.status = $('#filter_status').val();
                d.employee_id = $('#filter_employee').val();
                d.date_from = $('#filter_date_from').val();
                d.date_to = $('#filter_date_to').val();
            },
            dataSrc: function(json) {
                if (json.error) {
                    if (json.redirect) {
                        window.location.href = json.redirect;
                        return [];
                    }
                    toastr.error(json.error);
                    return [];
                }
                return json.data;
            }
        },
        columns: [
            { data: "employee_code" },
            { data: "employee_name" },
            { data: "schedule_name" },
            { data: "date" },
            { data: "time_in" },
            { data: "time_out" },
            { data: "regular_hours", className: "text-right" },
            { data: "overtime_25_hours", className: "text-right" },
            { data: "overtime_50_hours", className: "text-right" },
            { data: "overtime_hours", className: "text-right font-weight-bold" },
            { data: "status_badge", orderable: false },
            {
                data: function(row) {
                    let buttons = `<button class="btn btn-sm btn-info view-detail" data-id="${row.id}" title="Ver detalle">
                        <i class="fas fa-eye"></i></button> `;

                    if (row.status === 'PENDING') {
                        buttons += `<button class="btn btn-sm btn-success approve-btn" data-id="${row.id}"
                                        data-employee="${row.employee_name}"
                                        data-date="${row.date}"
                                        data-hours="${row.overtime_hours}"
                                        data-amount="${row.total_amount}" title="Aprobar">
                            <i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-danger reject-btn" data-id="${row.id}"
                                        data-employee="${row.employee_name}"
                                        data-date="${row.date}"
                                        data-hours="${row.overtime_hours}" title="Rechazar">
                            <i class="fas fa-times"></i></button>`;
                    }

                    return buttons;
                },
                orderable: false,
                className: "text-nowrap"
            }
        ],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" },
        order: [[3, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm'
            }
        ]
    });

    // Filtrar
    $("#filterForm").on("submit", function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Limpiar filtros
    $("#clearFilters").on("click", function() {
        $("#filterForm")[0].reset();
        $('#filter_status').val('').trigger('change');
        $('#filter_employee').val('').trigger('change');
        table.ajax.reload();
    });

    // Ver detalle
    $(document).on("click", ".view-detail", function() {
        const id = $(this).data("id");
        const records = table.rows().data().toArray();
        const record = records.find(r => r.id == id);

        if (record) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Empleado:</strong> ${record.employee_name}</p>
                        <p><strong>Código:</strong> ${record.employee_code}</p>
                        <p><strong>Horario:</strong> ${record.schedule_name}</p>
                        <p><strong>Fecha:</strong> ${record.date}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Entrada:</strong> ${record.time_in}</p>
                        <p><strong>Salida:</strong> ${record.time_out}</p>
                        <p><strong>Horas Totales:</strong> ${record.total_hours}</p>
                        <p><strong>Horas Regulares:</strong> ${record.regular_hours}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-box bg-info">
                            <div class="info-box-content">
                                <span class="info-box-text">Horas Extras 25%</span>
                                <span class="info-box-number">${record.overtime_25_hours} hrs</span>
                                <span class="info-box-text">$${record.amount_25}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-warning">
                            <div class="info-box-content">
                                <span class="info-box-text">Horas Extras 50%</span>
                                <span class="info-box-number">${record.overtime_50_hours} hrs</span>
                                <span class="info-box-text">$${record.amount_50}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-success">
                            <div class="info-box-content">
                                <span class="info-box-text">Total</span>
                                <span class="info-box-number">${record.overtime_hours} hrs</span>
                                <span class="info-box-text">$${record.total_amount}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <p><strong>Estado:</strong> ${record.status_badge}</p>`;

            if (record.approved_by_name !== '-') {
                html += `<p><strong>Aprobado/Rechazado por:</strong> ${record.approved_by_name}</p>`;
                html += `<p><strong>Fecha:</strong> ${record.approved_at}</p>`;
            }

            if (record.notes) {
                html += `<p><strong>Notas:</strong> ${record.notes}</p>`;
            }

            $("#detail-content").html(html);
            $("#detailModal").modal("show");
        }
    });

    // Mostrar modal de aprobación
    $(document).on("click", ".approve-btn", function() {
        const id = $(this).data("id");
        const employee = $(this).data("employee");
        const date = $(this).data("date");
        const hours = $(this).data("hours");
        const amount = $(this).data("amount");

        $("#approve_calculation_id").val(id);
        $("#approve_summary").html(`
            <strong>Empleado:</strong> ${employee}<br>
            <strong>Fecha:</strong> ${date}<br>
            <strong>Horas Extras:</strong> ${hours}<br>
            <strong>Monto:</strong> $${amount}
        `);

        $("#approveModal").modal("show");
    });

    // Aprobar
    $("#approveForm").on("submit", function(e) {
        e.preventDefault();
        const btn = $(this).find("[type='submit']");
        btn.prop("disabled", true).html("<i class='fas fa-spinner fa-spin'></i> Aprobando...");

        $.post(baseUrl + '/panel/overtime-approvals/approve', $(this).serialize(), function(resp) {
            btn.prop("disabled", false).html("<i class='fas fa-check'></i> Aprobar");

            if(resp.success) {
                $("#approveModal").modal("hide");
                toastr.success(resp.message);
                table.ajax.reload();
                $("#approveForm")[0].reset();
            } else {
                toastr.error(resp.error || "Error desconocido");
            }
        }).fail(function() {
            btn.prop("disabled", false).html("<i class='fas fa-check'></i> Aprobar");
            toastr.error("Error de conexión");
        });
    });

    // Mostrar modal de rechazo
    $(document).on("click", ".reject-btn", function() {
        const id = $(this).data("id");
        const employee = $(this).data("employee");
        const date = $(this).data("date");
        const hours = $(this).data("hours");

        $("#reject_calculation_id").val(id);
        $("#reject_summary").html(`
            <strong>Empleado:</strong> ${employee}<br>
            <strong>Fecha:</strong> ${date}<br>
            <strong>Horas Extras:</strong> ${hours}
        `);

        $("#rejectModal").modal("show");
    });

    // Rechazar
    $("#rejectForm").on("submit", function(e) {
        e.preventDefault();
        const btn = $(this).find("[type='submit']");
        btn.prop("disabled", true).html("<i class='fas fa-spinner fa-spin'></i> Rechazando...");

        $.post(baseUrl + '/panel/overtime-approvals/reject', $(this).serialize(), function(resp) {
            btn.prop("disabled", false).html("<i class='fas fa-times'></i> Rechazar");

            if(resp.success) {
                $("#rejectModal").modal("hide");
                toastr.success(resp.message);
                table.ajax.reload();
                $("#rejectForm")[0].reset();
            } else {
                toastr.error(resp.error || "Error desconocido");
            }
        }).fail(function() {
            btn.prop("disabled", false).html("<i class='fas fa-times'></i> Rechazar");
            toastr.error("Error de conexión");
        });
    });
});
</script>
<?php
$scripts = ob_get_clean();
?>
