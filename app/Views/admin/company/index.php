<?php
$page_title = 'Configuración de Empresa';

// Scripts para el módulo usando sistema modular
$scriptFiles = [
    '/assets/javascript/modules/company/index.js'
];

use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();
$scripts = $jsConfig . "\n" . JavaScriptHelper::renderScriptTags($scriptFiles);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building text-primary"></i>
                    <?= $company ? 'Editar Configuración de Empresa' : 'Configurar Empresa' ?>
                </h3>
                
                <?php if ($stats['configured']): ?>
                    <div class="card-tools">
                        <div class="badge badge-<?= $stats['completion'] == 100 ? 'success' : 'warning' ?>">
                            <?= $stats['completion'] ?>% Completado
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <form id="companyForm" action="<?= \App\Core\UrlHelper::url('/panel/company/store') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Security::generateToken() ?>">
                
                <div class="card-body">
                    <?php if (!empty($stats['missing_fields']) && $stats['configured']): ?>
                        <div class="callout callout-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Configuración incompleta</h5>
                            <p>Faltan los siguientes campos: <?= implode(', ', $stats['missing_fields']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Información Básica -->
                        <div class="col-md-6">
                            <h5 class="text-primary">
                                <i class="fas fa-info-circle"></i> Información Básica
                            </h5>
                            
                            <div class="form-group">
                                <label for="company_name">Nombre de la Empresa *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="company_name" 
                                       name="company_name" 
                                       placeholder="Ingrese el nombre de la empresa"
                                       value="<?= htmlspecialchars($company['company_name'] ?? '') ?>"
                                       required
                                       maxlength="255">
                            </div>
                            
                            <div class="form-group">
                                <label for="ruc">RUC/NIT *</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="ruc" 
                                       name="ruc" 
                                       placeholder="Ingrese el RUC o NIT"
                                       value="<?= htmlspecialchars($company['ruc'] ?? '') ?>"
                                       required
                                       maxlength="20">
                            </div>
                            
                            <div class="form-group">
                                <label for="legal_representative">Representante Legal</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="legal_representative" 
                                       name="legal_representative" 
                                       placeholder="Nombre del representante legal"
                                       value="<?= htmlspecialchars($company['legal_representative'] ?? '') ?>"
                                       maxlength="255">
                            </div>
                        </div>
                        
                        <!-- Información de Contacto -->
                        <div class="col-md-6">
                            <h5 class="text-primary">
                                <i class="fas fa-address-book"></i> Información de Contacto
                            </h5>
                            
                            <div class="form-group">
                                <label for="address">Dirección</label>
                                <textarea class="form-control" 
                                          id="address" 
                                          name="address" 
                                          rows="3"
                                          placeholder="Dirección completa de la empresa"
                                          maxlength="500"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Teléfono</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="phone" 
                                       name="phone" 
                                       placeholder="Número de teléfono"
                                       value="<?= htmlspecialchars($company['phone'] ?? '') ?>"
                                       maxlength="15">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="correo@empresa.com"
                                       value="<?= htmlspecialchars($company['email'] ?? '') ?>"
                                       maxlength="100">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Tipo de Institución -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-primary">
                                <i class="fas fa-building"></i> Tipo de Institución
                            </h5>
                            <p class="text-muted">Determina el comportamiento del sistema para estructuras organizacionales y cálculo de sueldos.</p>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_institucion">Tipo de Institución *</label>
                                <select class="form-control" id="tipo_institucion" name="tipo_institucion" required>
                                    <option value="privada" <?= ($company['tipo_institucion'] ?? 'privada') == 'privada' ? 'selected' : '' ?>>
                                        Empresa Privada
                                    </option>
                                    <option value="publica" <?= ($company['tipo_institucion'] ?? 'privada') == 'publica' ? 'selected' : '' ?>>
                                        Institución Pública
                                    </option>
                                </select>
                                <small class="form-text text-muted">
                                    <strong>Privada:</strong> Sueldos individuales por empleado<br>
                                    <strong>Pública:</strong> Sueldos por posiciones presupuestarias
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="callout callout-info">
                                <h5><i class="fas fa-info-circle"></i> Importante</h5>
                                <p>• <strong>Empresa Privada:</strong> Los sueldos se configuran individualmente por empleado<br>
                                • <strong>Institución Pública:</strong> Los sueldos se toman de las posiciones organizacionales</p>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Configuración de Moneda -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-primary">
                                <i class="fas fa-money-bill-wave"></i> Configuración de Moneda
                            </h5>
                            <p class="text-muted">Esta configuración afectará todos los reportes y documentos del sistema.</p>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currency_code">Moneda</label>
                                <select class="form-control" id="currency_code" name="currency_code">
                                    <?php foreach ($currencies as $currency): ?>
                                        <option value="<?= $currency['code'] ?>" 
                                                data-symbol="<?= $currency['symbol'] ?>"
                                                <?= ($company['currency_code'] ?? 'GTQ') == $currency['code'] ? 'selected' : '' ?>>
                                            <?= $currency['name'] ?> (<?= $currency['code'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currency_symbol">Símbolo de Moneda</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="currency_symbol" 
                                       name="currency_symbol" 
                                       placeholder="<?= currency_symbol() ?>"
                                       value="<?= htmlspecialchars($company['currency_symbol'] ?? currency_symbol()) ?>"
                                       maxlength="5"
                                       readonly>
                                <small class="form-text text-muted">
                                    El símbolo se actualiza automáticamente al seleccionar la moneda
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Configuración de Firmas para Reportes -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-primary">
                                <i class="fas fa-signature"></i> Firmas para Reportes de Planilla
                            </h5>
                            <p class="text-muted">Esta información aparecerá en las firmas de los reportes PDF de planillas.</p>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="elaborado_por">Elaborado por</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="elaborado_por" 
                                       name="elaborado_por" 
                                       placeholder="Nombre de quien elabora la planilla"
                                       value="<?= htmlspecialchars($company['elaborado_por'] ?? '') ?>"
                                       maxlength="255">
                                <small class="form-text text-muted">Nombre completo de la persona que elabora las planillas</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="cargo_elaborador">Cargo del Elaborador</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="cargo_elaborador" 
                                       name="cargo_elaborador" 
                                       placeholder="Especialista en Nóminas"
                                       value="<?= htmlspecialchars($company['cargo_elaborador'] ?? 'Especialista en Nóminas') ?>"
                                       maxlength="255">
                                <small class="form-text text-muted">Título o cargo de quien elabora las planillas</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jefe_recursos_humanos">Jefe de Recursos Humanos</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="jefe_recursos_humanos" 
                                       name="jefe_recursos_humanos" 
                                       placeholder="Nombre del jefe de RRHH"
                                       value="<?= htmlspecialchars($company['jefe_recursos_humanos'] ?? '') ?>"
                                       maxlength="255">
                                <small class="form-text text-muted">Nombre completo del jefe de recursos humanos</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="cargo_jefe_rrhh">Cargo del Jefe de RRHH</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="cargo_jefe_rrhh" 
                                       name="cargo_jefe_rrhh" 
                                       placeholder="Jefe de Recursos Humanos"
                                       value="<?= htmlspecialchars($company['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos') ?>"
                                       maxlength="255">
                                <small class="form-text text-muted">Título o cargo del jefe de recursos humanos</small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="callout callout-info">
                                <h5><i class="fas fa-info-circle"></i> Vista previa de firmas en reportes</h5>
                                <div class="row mt-2">
                                    <div class="col-md-6 text-center">
                                        <div class="border-top pt-2" style="border-top: 1px solid #000 !important; width: 200px; margin: 0 auto;">
                                            <strong id="preview_elaborador"><?= htmlspecialchars($company['elaborado_por'] ?? 'Por definir') ?></strong><br>
                                            <small id="preview_cargo_elaborador"><?= htmlspecialchars($company['cargo_elaborador'] ?? 'Especialista en Nóminas') ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-center">
                                        <div class="border-top pt-2" style="border-top: 1px solid #000 !important; width: 200px; margin: 0 auto;">
                                            <strong id="preview_jefe_rrhh"><?= htmlspecialchars($company['jefe_recursos_humanos'] ?? 'Por definir') ?></strong><br>
                                            <small id="preview_cargo_jefe"><?= htmlspecialchars($company['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos') ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?= $company ? 'Actualizar Configuración' : 'Guardar Configuración' ?>
                    </button>
                    
                    <?php if ($company): ?>
                        <a href="<?= \App\Core\UrlHelper::url('/panel/dashboard') ?>" class="btn btn-secondary ml-2">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!$stats['configured']): ?>
<div class="row mt-3">
    <div class="col-12">
        <div class="callout callout-info">
            <h5><i class="fas fa-lightbulb"></i> Configuración Inicial</h5>
            <p class="mb-0">
                Esta es la configuración inicial de tu empresa. Los datos ingresados aquí se utilizarán en todos los reportes
                y documentos generados por el sistema. Puedes modificar esta información en cualquier momento.
            </p>
        </div>
    </div>
</div>
<?php endif; ?>