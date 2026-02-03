<!-- Stats Cards -->
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="stat-pending-count"><?= $pending_count ?? 0 ?></h3>
                <p>Solicitudes Pendientes</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="stat-pending-hours"><?= number_format($pending_totals['total_hours'] ?? 0, 2) ?></h3>
                <p>Horas Pendientes</p>
            </div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="stat-pending-amount">$<?= number_format($pending_totals['total_amount'] ?? 0, 2) ?></h3>
                <p>Monto Pendiente</p>
            </div>
            <div class="icon"><i class="fas fa-dollar-sign"></i></div>
        </div>
    </div>
</div>

<!-- Main Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Mis Solicitudes Pendientes</h3>
    </div>
    <div class="card-body">
        <table id="pending-table" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Empleado</th>
                    <th>Período</th>
                    <th>Horas 25%</th>
                    <th>Horas 50%</th>
                    <th>Total Horas</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Días Pendiente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Solicitud</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
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

<script>
$(document).ready(function() {
    const table = $('#pending-table').DataTable({
        ajax: '<?= url('/panel/overtime-approvals/pending-data') ?>',
        columns: [
            { data: 'employee_code' },
            { data: 'employee_name' },
            { data: function(row) { return row.period_start + ' - ' + row.period_end; }},
            { data: 'total_overtime_25' },
            { data: 'total_overtime_50' },
            { data: 'total_hours' },
            { data: function(row) { return '$' + row.total_amount; }},
            { data: 'status_badge', orderable: false },
            { data: 'days_pending' },
            { data: function(row) {
                return `<button class="btn btn-sm btn-info view-detail" data-id="${row.id}">
                    <i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-success approve-btn" data-id="${row.id}">
                    <i class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-danger reject-btn" data-id="${row.id}">
                    <i class="fas fa-times"></i></button>`;
            }, orderable: false }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' }
    });

    // View detail
    $(document).on('click', '.view-detail', function() {
        const id = $(this).data('id');
        $('#detail-content').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
        $('#detailModal').modal('show');
        $.get('<?= url('/panel/overtime-approvals/detail/') ?>' + id, function(resp) {
            if(resp.success) {
                const d = resp.data;
                $('#detail-content').html(`
                    <h5>Empleado: ${d.employee_full_name}</h5>
                    <p><strong>Período:</strong> ${d.period_start_formatted} - ${d.period_end_formatted}</p>
                    <p><strong>Horas Extras 25%:</strong> ${d.total_overtime_25_formatted}</p>
                    <p><strong>Horas Extras 50%:</strong> ${d.total_overtime_50_formatted}</p>
                    <p><strong>Total Horas:</strong> ${d.total_hours_formatted}</p>
                    <p><strong>Tarifa Horaria:</strong> $${d.hourly_rate_formatted}</p>
                    <p><strong>Monto Total:</strong> $${d.total_amount_formatted}</p>
                    <p><strong>Estado:</strong> ${d.status_badge}</p>
                `);
            }
        });
    });

    // Approve
    $(document).on('click', '.approve-btn', function() {
        const id = $(this).data('id');
        if(confirm('¿Aprobar esta solicitud?')) {
            $.post('<?= url('/panel/overtime-approvals/approve') ?>', {approval_id: id}, function(resp) {
                if(resp.success) {
                    table.ajax.reload();
                    alert('Aprobado exitosamente');
                } else {
                    alert('Error: ' + (resp.error || 'Desconocido'));
                }
            });
        }
    });

    // Reject
    $(document).on('click', '.reject-btn', function() {
        const id = $(this).data('id');
        const reason = prompt('Razón del rechazo:');
        if(reason) {
            $.post('<?= url('/panel/overtime-approvals/reject') ?>', {approval_id: id, reason: reason}, function(resp) {
                if(resp.success) {
                    table.ajax.reload();
                    alert('Rechazado exitosamente');
                } else {
                    alert('Error: ' + (resp.error || 'Desconocido'));
                }
            });
        }
    });
});
</script>
