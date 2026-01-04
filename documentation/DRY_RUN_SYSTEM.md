# Sistema Dry-Run para Estimados de Liquidaciones

## 📋 Descripción General

El **sistema dry-run** implementado en el módulo de estimados de liquidaciones permite ejecutar cálculos y simulaciones de liquidación **sin afectar la base de datos ni el estado de los empleados**.

## 🎯 Objetivo

Proporcionar una funcionalidad segura para:
- **Estimar montos de liquidación** de empleados activos sin darlos de baja
- **Generar reportes financieros** para provisiones anuales
- **Planificar costos laborales** futuros
- **Evitar modificaciones accidentales** a la base de datos

## 🔧 Implementación Técnica

### Parámetro `dry_run`

**Ubicación**: `EstimateReportController.php`

**Métodos afectados**:
- `estimadoAnualLiquidaciones($dryRun = true)`
- `estimadoAnualLiquidacionesPdf($dryRun = true)`

**Comportamiento por defecto**: `true` (modo simulación activo)

### Flujo de Ejecución

```php
// 1. Parámetro por defecto (seguro)
public function estimadoAnualLiquidaciones($dryRun = true)

// 2. Validación desde URL (opcional)
if (isset($_GET['dry_run'])) {
    $dryRun = filter_var($_GET['dry_run'], FILTER_VALIDATE_BOOLEAN);
}

// 3. Logging diferenciado
$modeLog = $dryRun ? '[DRY-RUN MODE]' : '[LIVE MODE]';
error_log("{$modeLog} Iniciando estimado anual de liquidaciones...");
```

## 🚦 Modos de Operación

### Modo Dry-Run (Por Defecto)

**Estado**: `dry_run = true`

**Características**:
- ✅ **NO** crea registros en `employee_terminations`
- ✅ **NO** modifica estado de empleados
- ✅ **NO** genera planillas reales
- ✅ Solo calcula montos estimados
- ✅ Validación de situación deshabilitada (`validateSituacion = false`)
- ✅ Logs con prefijo `[DRY-RUN MODE]`

**Uso**:
```
# URL normal (dry-run activo por defecto)
/panel/reports/estimado-anual-liquidaciones

# URL explícita
/panel/reports/estimado-anual-liquidaciones?dry_run=1
```

### Modo Live (Deshabilitado por seguridad)

**Estado**: `dry_run = false`

**Características**:
- ⚠️ Requiere pasar explícitamente `?dry_run=0` en URL
- ⚠️ Logs con prefijo `[LIVE MODE]`
- ⚠️ **Actualmente solo logging**, no implementa escritura real

**Nota**: El modo live está documentado pero **NO implementa escritura** a BD. El reporte es inherentemente no-destructivo.

## 📊 Indicadores Visuales

### Vista Web (`estimado_liquidaciones.php`)

**Badge en título**:
```html
<span class="badge badge-warning ml-2">
    <i class="fas fa-flask"></i> MODO SIMULACIÓN
</span>
```

**Alerta informativa**:
```html
<div class="alert alert-warning">
    <h5><i class="fas fa-flask"></i> Modo Simulación Activo (DRY-RUN)</h5>
    <p>Este reporte NO modifica ningún dato en la base de datos...</p>
</div>
```

### PDF (`generateEstimadoLiquidacionesPDF`)

**Header con indicador**:
```php
if ($estimateData['dry_run_mode'] ?? true) {
    $pdf->SetTextColor(255, 152, 0); // Naranja
    $pdf->Cell(0, 4, '[MODO SIMULACIÓN - NO AFECTA DATOS REALES]', 0, 1, 'C');
}
```

## 🔒 Características de Seguridad

### 1. Seguridad por Defecto
- El parámetro `$dryRun` tiene valor por defecto `true`
- **Nunca** ejecutará modo live sin intervención explícita

### 2. Validación de Entrada
```php
// Filtrado seguro de parámetro GET
$dryRun = filter_var($_GET['dry_run'], FILTER_VALIDATE_BOOLEAN);
```

### 3. Logging Completo
```php
// Log inicio
error_log("{$modeLog} Iniciando estimado - Usuario: {$username}");

// Log finalización
error_log("{$modeLog} Completado - Empleados: {$count} - Total: ${$total}");
```

### 4. Deshabilitación de Validaciones Restrictivas
```php
// NO validar situación de empleado (son empleados activos simulando baja)
$payrollModel->validateConceptConditions($concept, $payrollSimulation, 1, false);
//                                                                          ^^^^
//                                                                    validateSituacion = false
```

## 📁 Archivos Modificados

### Controller
- `app/Controllers/EstimateReportController.php`
  - Líneas 13-35: Parámetro `$dryRun` + validación + logging (método HTML)
  - Líneas 196-220: Log finalización + flag a vista
  - Líneas 232-253: Parámetro `$dryRun` + validación + logging (método PDF)
  - Líneas 412-424: Log finalización + flag a PDF
  - Líneas 491-497: Badge dry-run en header PDF

### Vista
- `app/Views/admin/reports/estimado_liquidaciones.php`
  - Líneas 28-44: Variable `$isDryRunMode` + badge en título
  - Líneas 59-74: Alerta informativa modo simulación

### Documentación
- `documentation/DRY_RUN_SYSTEM.md` (este archivo)

## 🧪 Casos de Uso

### 1. Estimado Anual Regular (Dry-Run)
```php
// Usuario accede a reporte normal
GET /panel/reports/estimado-anual-liquidaciones

// Sistema ejecuta con dry_run = true (por defecto)
// - Calcula estimados
// - NO modifica BD
// - Muestra indicadores visuales
```

### 2. Generación PDF (Dry-Run)
```php
// Usuario genera PDF
GET /panel/reports/estimado-anual-liquidaciones-pdf

// Sistema ejecuta con dry_run = true (por defecto)
// - Calcula estimados
// - Genera PDF con badge [MODO SIMULACIÓN]
// - NO modifica BD
```

### 3. Modo Live Explícito (Solo Logging)
```php
// URL con parámetro explícito
GET /panel/reports/estimado-anual-liquidaciones?dry_run=0

// Sistema ejecuta con dry_run = false
// - Logs con [LIVE MODE]
// - Calcula estimados (sin modificar BD, no implementado)
```

## 📌 Notas Importantes

1. **Por Diseño No-Destructivo**: El reporte de estimados **nunca** debería modificar BD, independientemente del flag `dry_run`. El flag es una medida adicional de seguridad y documentación.

2. **Diferencia con Liquidaciones Reales**:
   - Estimados: Usa `EstimateReportController` (dry-run)
   - Liquidaciones: Usa `LiquidationController` (modifica BD via `employee_terminations`)

3. **Compatibilidad**: El sistema es retrocompatible. Si no se pasa el parámetro, asume `true` (modo seguro).

4. **Futuras Extensiones**: El parámetro permite implementar variantes futuras si se requiere (ej: guardar estimados históricos).

## 🔗 Referencias

- **Controller**: `app/Controllers/EstimateReportController.php`
- **Vista**: `app/Views/admin/reports/estimado_liquidaciones.php`
- **Liquidaciones Reales**: `app/Controllers/LiquidationController.php`
- **Ruta**: `/panel/reports/estimado-anual-liquidaciones`

---

**Versión**: 3.5.18 (Implementación Dry-Run System)
**Fecha**: 04 de Enero, 2026
**Autor**: Sistema de Planillas MVC - Claude Code
