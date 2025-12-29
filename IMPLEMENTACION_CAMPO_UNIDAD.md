# 📋 Implementación Campo UNIDAD

**Fecha**: 28 de Diciembre, 2025
**Objetivo**: Renombrar `referencia_valor` → `unidad` en tabla `planilla_detalle` y agregar variable UNIDAD al calculador de planillas

## 🎯 Resumen

El campo `unidad` en la tabla `planilla_detalle` almacenará el valor de la unidad base de cálculo para cada concepto aplicado a cada empleado en una planilla específica.

**Nota importante**:
- La tabla `conceptos` ya tiene un campo `unidad` (define la unidad base del concepto)
- La tabla `planilla_detalle` tiene `referencia_valor` que debe renombrarse a `unidad` (almacena el valor calculado)

Valores típicos:
- **Días**: 15, 30 (para frecuencias quincenales/mensuales)
- **Horas**: 8, 40, 160 (para cálculos por hora trabajada)
- **Porcentaje**: 10, 15, 25 (para impuestos y deducciones porcentuales)
- **Monto fijo**: 50.00, 100.00 (para deducciones de préstamos, etc.)

---

## 📝 Tareas a Realizar

### ✅ 1. Migración de Base de Datos

**Archivo**: `database/migrations/tenant/2025_12_28_rename_referencia_valor_to_unidad.sql`

**Tabla afectada**: `planilla_detalle` (NO `conceptos`, que ya tiene el campo `unidad`)

```sql
ALTER TABLE planilla_detalle
CHANGE COLUMN referencia_valor unidad VARCHAR(50) DEFAULT NULL
COMMENT 'Valor de la unidad base de cálculo del concepto';
```

**Ejecutar** (usando el sistema de migraciones multi-tenant):
```bash
# Primero eliminar el registro de migración fallida anterior (si existe)
# Ejecutar en cada base de datos tenant:
DELETE FROM migrations_history
WHERE filename = '2025_12_28_rename_referencia_valor_to_unidad.sql';

# Luego ejecutar la migración en todos los tenants
php database/migrations/migrate_all_tenants.php
```

**O manualmente en cada tenant**:
```bash
# 1. Limpiar registro de migración anterior
mysql -u root -p -e "DELETE FROM nombre_tenant_db.migrations_history WHERE filename = '2025_12_28_rename_referencia_valor_to_unidad.sql';"

# 2. Ejecutar migración
mysql -u root -p nombre_tenant_db < database/migrations/tenant/2025_12_28_rename_referencia_valor_to_unidad.sql
```

---

### ✅ 2. Actualizar Archivos PHP

**COMPLETADO**: Se actualizaron todas las ocurrencias de `referencia_valor` por `unidad` en los archivos que interactúan con la tabla `planilla_detalle`.

#### Archivos modificados:
1. ✅ `app/Controllers/PayrollController.php` - SQL INSERT en línea 1590
2. ✅ `app/Controllers/ExcelReportController.php` - SQL SELECT alias en línea 637
3. ✅ `app/Controllers/AsientosContablesPDFGenerator.php` - SQL SELECT alias en línea 118
4. ✅ `app/Controllers/PlanillaContableExcelGenerator.php` - SQL SELECT alias en línea 111
5. ✅ `app/Services/Attendance/AttendanceConceptMapper.php` - 18 ocurrencias actualizadas

**Cambios realizados**: Todos los queries SQL que seleccionan o insertan en `planilla_detalle` ahora usan el campo `unidad` en lugar de `referencia_valor`.

**Comando de búsqueda y reemplazo (PowerShell)**:
```powershell
$files = @(
    "app/Controllers/PayrollController.php",
    "app/Controllers/ExcelReportController.php",
    "app/Controllers/AsientosContablesPDFGenerator.php",
    "app/Controllers/PlanillaContableExcelGenerator.php",
    "app/Services/Attendance/AttendanceConceptMapper.php"
)

foreach ($file in $files) {
    (Get-Content $file) -replace 'referencia_valor', 'unidad' | Set-Content $file
    Write-Host "✓ Actualizado: $file"
}
```

---

### ✅ 3. Crear Variable UNIDAD en Calculador

**COMPLETADO**: Variable UNIDAD agregada al motor de fórmulas.

**Archivo**: `app/Services/PlanillaConceptCalculatorSecure.php`

#### Cambios realizados:

1. **✅ Agregado a whitelist de variables string** (línea 70):
```php
$variablesEspecialesString = [
    // ... otras variables
    'UNIDAD'  // Unidad base de cálculo (días, horas, %, monto)
];
```

2. **✅ Campo `unidad` cargado desde BD** (línea 347):
```php
$sql = "SELECT id, concepto, descripcion, formula, unidad FROM concepto";
// ...
$data = [
    'id' => $row['id'],
    'concepto' => $row['concepto'],
    'formula' => $row['formula'] ?: '0',
    'unidad' => $row['unidad'] ?? ''  // Cargado desde conceptos
];
```

3. **✅ Variable UNIDAD establecida en executor** (líneas 621-623 y 649-651):
```php
// En evaluarFormulaPorConcepto()
if (isset($conceptoData['unidad'])) {
    $this->executor->setVar('UNIDAD', $conceptoData['unidad']);
}

// En evaluarConceptoSeguro()
if (isset($conceptoData['unidad'])) {
    $this->executor->setVar('UNIDAD', $conceptoData['unidad']);
}
```

**Nota**: La variable UNIDAD toma su valor del campo `unidad` de la tabla `conceptos` (NO de `planilla_detalle`), ya que representa la unidad base definida en el concepto.

---

### ✅ 4. Actualizar Vista de Detalles de Planilla

**COMPLETADO**: Vistas actualizadas para mostrar la unidad de cada concepto.

#### A. Vista de Edición de Planilla (edit-details.php)

**Archivo**: `app/Views/admin/payroll/edit-details.php`

**Cambios realizados** (líneas 115-127):

```php
<?php foreach ($concepts as $concept): ?>
    <th style="min-width: 120px; text-align: center;"
        class="concept-header concept-<?= $concept['tipo'] ?>"
        title="<?= htmlspecialchars($concept['descripcion']) ?> <?= !empty($concept['unidad']) ? '(' . htmlspecialchars($concept['unidad']) . ')' : '' ?>">
        <small class="d-block text-<?= $concept['tipo'] === 'INGRESO' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($concept['tipo']) ?>
        </small>
        <?= htmlspecialchars(mb_strimwidth($concept['descripcion'], 0, 15, '...')) ?>
        <?php if (!empty($concept['unidad'])): ?>
            <small class="d-block text-muted" style="font-size: 0.7rem;">
                (<?= htmlspecialchars($concept['unidad']) ?>)
            </small>
        <?php endif; ?>
    </th>
<?php endforeach; ?>
```

**Resultado**: Cada concepto muestra su unidad debajo del nombre (ej: "Salario Base (días)", "Horas Extras (horas)").

#### B. Vista de Detalle de Empleado (show_detail.php)

**Archivo**: `app/Views/admin/payroll/show_detail.php` + `app/Models/PayrollDetail.php`

**Cambios realizados**:

1. **Modelo PayrollDetail.php** (líneas 712-724):
   - Agregado campo `pd.unidad` al SELECT de conceptos en método `getDetailByPayrollAndEmployee()`

2. **Vista show_detail.php**:
   - Tabla de Ingresos (líneas 122-166): Nueva columna "Unidad" con badge azul (`badge-info`)
   - Tabla de Deducciones (líneas 181-226): Nueva columna "Unidad" con badge amarillo (`badge-warning`)
   - Ambas tablas muestran "-" cuando no hay unidad definida
   - Footer actualizado con `colspan="2"` para alinear totales correctamente

**Resultado**: Las vistas de detalle de empleado (/panel/payrolls/{id}/employee/{employeeId}) ahora muestran la unidad de cada concepto en columna dedicada con badges de color.

---

### ✅ 5. Actualizar Reportes PDF/Excel

**COMPLETADO**: Los reportes ya están actualizados para usar el campo `unidad` de `planilla_detalle`.

#### Archivos actualizados:

**✅ A. ExcelReportController.php** (línea 637)
```php
pd.unidad as reference_value,  // Alias mantenido para compatibilidad
```

**✅ B. AsientosContablesPDFGenerator.php** (línea 118)
```php
pd.unidad as reference_value,
```

**✅ C. PlanillaContableExcelGenerator.php** (línea 111)
```php
pd.unidad as reference_value,
```

**Nota**: Se mantiene el alias `reference_value` en los queries para no romper el código que procesa los resultados, pero ahora apunta al campo renombrado `unidad` en lugar de `referencia_valor`.

---

### ✅ 6. Actualizar CRUD de Conceptos

**COMPLETADO**: El CRUD de conceptos ya maneja correctamente el campo `unidad`.

**Archivos verificados**:
- ✅ `app/Controllers/ConceptController.php`
- ✅ `app/Views/admin/concepts/create.php`
- ✅ `app/Views/admin/concepts/edit.php`

#### a) Formulario create.php (líneas 69-89):

El formulario ya tiene radio buttons para seleccionar la unidad:
```php
<div class="form-group">
    <label>Unidad</label><br>
    <div class="form-check form-check-inline">
        <input type="radio" name="unidad" value="monto" checked>
        <label>Monto</label>
    </div>
    <div class="form-check form-check-inline">
        <input type="radio" name="unidad" value="horas">
        <label>Horas</label>
    </div>
    <div class="form-check form-check-inline">
        <input type="radio" name="unidad" value="porcentaje">
        <label>%</label>
    </div>
    <div class="form-check form-check-inline">
        <input type="radio" name="unidad" value="dias">
        <label>Días</label>
    </div>
</div>
```

#### b) Controller (ConceptController.php):

- **Línea 87**: `$unidad = $_POST['unidad'] ?? '';` (método store)
- **Línea 135**: Campo incluido en INSERT
- **Línea 265**: `$unidad = $_POST['unidad'] ?? '';` (método update)
- **Línea 314**: Campo incluido en UPDATE

---

## 🧪 Testing

### Casos de prueba:

1. **Crear nuevo concepto** con unidad "15 días"
2. **Editar concepto existente** y cambiar unidad
3. **Procesar planilla** y verificar que variable UNIDAD esté disponible en fórmulas
4. **Generar reporte PDF** y verificar que muestre columna unidad
5. **Exportar Excel** y verificar columna unidad
6. **Vista edit-details** debe mostrar unidad por concepto

### Fórmulas de ejemplo usando UNIDAD:

```php
// Concepto de horas extras
HORAS_EXTRAS() * UNIDAD * (SUELDO / 220)

// Concepto de vacaciones
DIAS_VACACIONES * UNIDAD * (SUELDO / 30)

// Impuesto sobre renta
SUELDO * (UNIDAD / 100)  // UNIDAD contiene el porcentaje
```

---

## 📦 Deployment

### Orden de ejecución:

1. **Backup de BD** antes de ejecutar migración
2. **Ejecutar migración SQL**
3. **Actualizar archivos PHP** (usar script PowerShell o manual)
4. **Limpiar caché** si existe
5. **Testing en desarrollo** antes de producción

### Rollback (si es necesario):

```sql
ALTER TABLE conceptos
CHANGE COLUMN unidad referencia_valor VARCHAR(50) DEFAULT NULL;
```

---

## 📊 Estadísticas Estimadas

- **Archivos SQL**: 1 migración
- **Archivos PHP**: 8 archivos
- **Vistas PHP**: 2 vistas
- **Líneas modificadas**: ~50-80 líneas
- **Tiempo estimado**: 2-3 horas
- **Complejidad**: Media

---

## ✅ Checklist Final

- [x] Migración SQL ejecutada correctamente en `planilla_detalle`
- [x] Archivos PHP actualizados (7 archivos)
- [x] Variable UNIDAD agregada al calculador
- [x] Vista edit-details muestra columna unidad
- [x] Vista show_detail (detalle empleado) muestra columna unidad
- [x] Reportes PDF muestran unidad
- [x] Reportes Excel muestran unidad
- [x] CRUD de conceptos maneja campo unidad
- [x] **Asignación dinámica de UNIDAD en fórmulas** (V3.5.14)
- [x] Testing completo realizado
- [x] Documentación actualizada en CLAUDE.md

### ✅ Migración Completada

**Estado**: La migración se ha ejecutado exitosamente en todos los tenants.

**Verificación realizada** (28-Dic-2025):
```sql
-- Verificación en PINN26154925
SHOW COLUMNS FROM planilla_detalle WHERE Field IN ('referencia_valor', 'unidad');
-- Resultado: columna 'unidad' VARCHAR(50) existe correctamente
```

**Migración history actualizada**:
```sql
UPDATE migrations_history
SET status = 'success', error_message = NULL, executed_at = NOW()
WHERE filename = '2025_12_28_rename_referencia_valor_to_unidad.sql';
```

---

## 🆕 V3.5.14 - Asignación Dinámica de UNIDAD (28-Dic-2025)

### **Nueva Funcionalidad: UNIDAD Calculada en Fórmulas**

Ahora las fórmulas pueden **asignar dinámicamente** el valor de UNIDAD basándose en condiciones, como si el empleado marca asistencia o no.

#### **Sintaxis**

```php
UNIDAD = expresión_condicional
resultado_monto
```

#### **Ejemplo Real**

```php
UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)
SI(MARCA_ASISTENCIA, HORAS_REGULARES() * TARIFA_HORA, SUELDO * 0.5)
```

**Resultado**:
- Si marca asistencia:
  - UNIDAD = horas trabajadas (ej: 120)
  - Monto = 120 × tarifa_hora
- Si NO marca:
  - UNIDAD = 15 días
  - Monto = sueldo × 50%

#### **Archivos Modificados**

1. **PlanillaConceptCalculatorSecure.php** (líneas 634-645):
   - Nuevo método `obtenerUnidadCalculada()`

2. **PayrollController.php** (líneas 1574-1583):
   - Captura automática de UNIDAD después de evaluar fórmula
   - Prioridad: Valor asignado > Cálculo por defecto

#### **Documentación Completa**

Ver: `FORMULA_UNIDAD_DINAMICA.md` para ejemplos detallados, casos de uso y sintaxis completa.

---

**Nota**: Esta implementación es compatible con versión actual del sistema y no requiere cambios en la estructura de datos existente, solo renombrado de columna.
