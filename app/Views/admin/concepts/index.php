<?php
$title = 'Conceptos de Nómina';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <a href="<?= url('/panel/concepts/create') ?>" class="btn btn-primary btn-sm ml-3">
                        <i class="fas fa-plus"></i> Nuevo Concepto
                    </a>
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <select id="filterType" class="form-control" style="width: 120px;">
                            <option value="">Todos los tipos</option>
                            <option value="INGRESO">Ingresos</option>
                            <option value="DEDUCCION">Deducciones</option>
                        </select>
                        <input type="text" id="searchInput" class="form-control float-right" placeholder="Buscar concepto...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="conceptsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Descripción</th>
                                <th>Tipo</th>
                                <th>Fórmula</th>
                                <th>Uso</th>
                                <th>Total Aplicado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (!empty($concepts)): ?>
    <?php foreach ($concepts as $concept): 
        // Determinar clase y icono del tipo
        $typeClass = ($concept['tipo_concepto'] ?? '') === 'A' ? 'badge-success' : (($concept['tipo_concepto'] ?? '') === 'D' ? 'badge-danger' : 'badge-info');
        $typeIcon = ($concept['tipo_concepto'] ?? '') === 'A' ? 'fas fa-plus' : (($concept['tipo_concepto'] ?? '') === 'D' ? 'fas fa-minus' : 'fas fa-cog');
        
        // Estado impresión
        $statusClass = ($concept['imprime_detalles'] ?? 0) ? 'badge-success' : 'badge-secondary';
        $statusText = ($concept['imprime_detalles'] ?? 0) ? 'Se imprime' : 'No se imprime';
        $statusIcon = ($concept['imprime_detalles'] ?? 0) ? 'fas fa-print' : 'fas fa-times';
        
        // Fórmula (mostrar resumida si es muy larga)
        $formula = $concept['formula'] ?? '';
        $formulaDisplay = empty($formula) ? 
            '<span class="text-muted"><i class="fas fa-exclamation-triangle"></i> Sin fórmula</span>' : 
            (strlen($formula) > 30 ? substr($formula, 0, 30) . '...' : $formula);
        
        // Estadísticas de uso
        $vecesUsado = intval($concept['veces_usado'] ?? 0);
        $totalMonto = floatval($concept['total_monto'] ?? 0);
        $promedioMonto = floatval($concept['promedio_monto'] ?? 0);
    ?>
                            <tr>
                                <td><?= htmlspecialchars($concept['id']) ?></td>
                                <td>
                                    <div class="concept-info">
                                        <strong><?= htmlspecialchars($concept['descripcion']) ?></strong>
                                        <?php if (!empty($concept['abreviatura'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($concept['abreviatura']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $typeClass ?>">
                                        <i class="<?= $typeIcon ?>"></i> 
                                        <?= ($concept['tipo_concepto'] ?? '') === 'A' ? 'Ingreso' : (($concept['tipo_concepto'] ?? '') === 'D' ? 'Deducción' : 'Mixto') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="formula-container">
                                        <?php if (!empty($formula)): ?>
                                            <code class="formula-text" data-full-formula="<?= htmlspecialchars($formula) ?>">
                                                <?= strlen($formula) > 30 ? htmlspecialchars(substr($formula, 0, 30)) . '...' : htmlspecialchars($formula) ?>
                                            </code>
                                            <?php if (strlen($formula) > 30): ?>
                                                <br><small><a href="#" class="text-info show-formula">Ver completa</a></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> Sin fórmula de cálculo
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <!--<td>
                                    <span class="badge <?= $statusClass ?>">
                                        <i class="<?= $statusIcon ?>"></i> <?= $statusText ?>
                                    </span>
                                </td>-->
                                <td class="text-center">
                                    <div class="usage-stats">
                                        <strong class="usage-count"><?= $vecesUsado ?></strong>
                                        <br><small class="text-muted">veces</small>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <?php if ($totalMonto > 0): ?>
                                        <strong class="<?= ($concept['tipo_concepto'] ?? '') === 'A' ? 'text-success' : 'text-danger' ?>">
                                            <?= currency_symbol(); ?> <?= number_format($totalMonto, 2) ?>
                                        </strong>
                                        <br><small class="text-muted">Promedio: <?= currency_symbol(); ?> <?= number_format($promedioMonto, 2) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted"><?= currency_symbol(); ?> 0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <?php if (!empty($formula)): ?>
                                            <button type="button" class="btn btn-info btn-sm test-formula" 
                                                    data-id="<?= $concept['id'] ?>" 
                                                    data-formula="<?= htmlspecialchars($formula) ?>"
                                                    data-toggle="tooltip" title="Probar Fórmula">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?= url('/panel/concepts/show/' . $concept['id']) ?>" 
                                           class="btn btn-primary btn-sm"
                                           data-toggle="tooltip" title="Ver Detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= url('/panel/concepts/edit/' . $concept['id']) ?>" 
                                           class="btn btn-warning btn-sm"
                                           data-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm delete-concept" 
                                                data-id="<?= $concept['id'] ?>" 
                                                data-description="<?= htmlspecialchars($concept['descripcion']) ?>"
                                                data-toggle="tooltip" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
    <?php endforeach; ?>
<?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> No hay conceptos registrados
                                </td>
                            </tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Probar Fórmula -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Probar Fórmula</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="testEmployee">Empleado para Prueba</label>
                    <select class="form-control select2" id="testEmployee" style="width: 100%;">
                        <option value="">Seleccione un empleado...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Fórmula a Probar</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-code"></i></span>
                        </div>
                        <input type="text" class="form-control" id="testFormula" readonly>
                    </div>
                </div>
                
                <div id="testResult" class="alert" style="display: none;"></div>
                
                <div id="employeeVariables" style="display: none;">
                    <h6>Variables del Empleado:</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>SALARIO:</strong> <span id="varSalario"><?= currency_symbol(); ?> 0.00</span>
                        </div>
                        <div class="col-md-4">
                            <strong>HORAS:</strong> <span id="varHoras">0</span>
                        </div>
                        <div class="col-md-4">
                            <strong>ANTIGUEDAD:</strong> <span id="varAntiguedad">0</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="runTest">Ejecutar Prueba</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmar Eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmar Eliminación</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el concepto <strong id="deleteConceptName"></strong>?</p>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Esta acción no se puede deshacer y podría afectar planillas existentes.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Ensure jQuery is loaded before executing
(function checkjQuery() {
    if (typeof $ === 'undefined') {
        setTimeout(checkjQuery, 50);
        return;
    }
    
    $(document).ready(function() {
    // Initialize DataTable
    var table = $('#conceptsTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            "decimal": "",
            "emptyTable": "No hay datos disponibles en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
            "infoFiltered": "(filtrado de _MAX_ entradas totales)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar la columna de manera ascendente",
                "sortDescending": ": activar para ordenar la columna de manera descendente"
            }
        },
        columnDefs: [
            { orderable: false, targets: [6] } // Disable ordering on actions column (adjusted for removed column)
        ]
    });

    // Filtro por tipo
    $('#filterType').on('change', function() {
        var filterValue = $(this).val();
        if (filterValue) {
            table.column(2).search(filterValue).draw();
        } else {
            table.column(2).search('').draw();
        }
    });

    // Búsqueda personalizada
    $('#searchInput').on('keyup', function() {
        table.search($(this).val()).draw();
    });

    // Mostrar fórmula completa
    $(document).on('click', '.show-formula', function(e) {
        e.preventDefault();
        var fullFormula = $(this).closest('td').find('.formula-text').data('full-formula');
        alert('Fórmula completa:\n\n' + fullFormula);
    });

    // Probar fórmula
    $(document).on('click', '.test-formula', function() {
        var conceptId = $(this).data('id');
        var formula = $(this).data('formula');
        
        $('#testFormula').val(formula);
        $('#testModal').modal('show');
        
        // Initialize Select2 for employees
        initEmployeeSelect2();
    });

    // Ejecutar prueba de fórmula
    $('#runTest').click(function() {
        var employeeId = $('#testEmployee').val();
        var formula = $('#testFormula').val();
        
        if (!employeeId) {
            alert('Por favor seleccione un empleado');
            return;
        }

        if (!formula) {
            alert('No hay fórmula para probar');
            return;
        }

        testFormula(employeeId, formula);
    });

    // Eliminar concepto
    $(document).on('click', '.delete-concept', function() {
        var conceptId = $(this).data('id');
        var description = $(this).data('description');
        
        $('#deleteConceptName').text(description);
        $('#deleteModal').modal('show');
        
        $('#confirmDelete').off('click').on('click', function() {
            deleteConcept(conceptId);
        });
    });

    // Initialize Select2 for employees
    function initEmployeeSelect2() {
        $('#testEmployee').select2({
            theme: 'bootstrap4',
            placeholder: 'Buscar empleado...',
            allowClear: true,
            ajax: {
                url: '<?= url('/panel/employees/options') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // término de búsqueda
                        page: params.page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results,
                        pagination: {
                            more: data.pagination.more
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 0,
            dropdownParent: $('#testModal')
        });
    }

    // Test formula function
    function testFormula(employeeId, formula) {
        $('#testResult').hide();
        $('#employeeVariables').hide();
        
        $.ajax({
            url: '<?= url('/panel/concepts/test-formula') ?>',
            method: 'POST',
            data: {
                employee_id: employeeId,
                formula: formula,
                csrf_token: '<?= \App\Core\Security::generateToken() ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#testResult')
                        .removeClass('alert-danger')
                        .addClass('alert-success')
                        .html('<i class="fas fa-check"></i> <strong>Resultado:</strong> <?= currency_symbol() ?>' + response.result)
                        .show();
                    
                    if (response.variables) {
                        $('#varSalario').text('<?= currency_symbol() ?> ' + (response.variables.SALARIO || '0.00'));
                        $('#varHoras').text(response.variables.HORAS || '0');
                        $('#varAntiguedad').text(response.variables.ANTIGUEDAD || '0');
                        $('#employeeVariables').show();
                    }
                } else {
                    $('#testResult')
                        .removeClass('alert-success')
                        .addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> ' + response.message)
                        .show();
                }
            },
            error: function() {
                $('#testResult')
                    .removeClass('alert-success')
                    .addClass('alert-danger')
                    .html('<i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> No se pudo probar la fórmula')
                    .show();
            }
        });
    }

    // Delete concept function
    function deleteConcept(conceptId) {
        $.ajax({
            url: '<?= url('/panel/concepts/delete/') ?>' + conceptId,
            method: 'POST',
            data: {
                csrf_token: '<?= \App\Core\Security::generateToken() ?>'
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataType: 'json',
            success: function(response) {
                console.log('Delete response:', response);
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    location.reload();
                } else {
                    // Si hay un redirect, redirigir al login
                    if (response.redirect) {
                        alert(response.message || 'Sesión expirada. Redirigiendo al login...');
                        window.location.href = response.redirect;
                        return;
                    }
                    alert('Error: ' + (response.message || 'No se pudo eliminar el concepto'));
                }
            },
            error: function(xhr, status, error) {
                console.log('Delete error:', xhr.responseText);
                console.log('Status:', status, 'Error:', error);
                
                // Error real de conexión
                alert('Error de conexión. No se pudo eliminar el concepto.');
            }
        });
    }

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    });
})();
</script>

<style>
.concept-info {
    min-height: 40px;
}

.formula-container {
    max-width: 200px;
}

.formula-text {
    background-color: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 11px;
}

.usage-stats {
    text-align: center;
}

.usage-count {
    font-size: 1.2em;
    color: #007bff;
}

.btn-group .btn {
    border-radius: 0.25rem;
}
</style>

