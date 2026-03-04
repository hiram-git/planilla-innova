# Sistema de Tolerancias y Cálculo Avanzado de Almuerzo

**Estado**: ✅ **COMPLETADO 100%**
**Fecha de Implementación**: 18-20 de Noviembre, 2025
**Versión**: V3.5.x (Integrado en módulo de asistencias)

## 🎯 Objetivo

Sistema completo de tolerancias para entrada/salida/almuerzo + elegibilidad horas extras + corrección horas nocturnas en turnos diurnos.

## 📋 Commits Principales

- **90b0c51** (18-Nov-2025): `feat(attendance,employee,schedule): add overtime approvals, eligibility and schedule tolerances`
- **7d8b567** (18-Nov-2025): `fix(attendance): apply schedule tolerances and correct night hours`
- **b89f227** (20-Nov-2025): `fix: apply lunch tolerances with base date in attendance calculations`

**Archivos Modificados**: 20 archivos | +2,413 líneas | -156 líneas

---

## 1. Sistema de Tolerancias en Horarios ✅

### Tabla `schedules` - 8 Campos Nuevos

```sql
-- Tolerancias de Entrada
time_in_tolerance_before INT DEFAULT 0,  -- Minutos permitidos ANTES de hora programada
time_in_tolerance_after INT DEFAULT 0,   -- Minutos permitidos DESPUÉS de hora programada

-- Tolerancias de Salida
time_out_tolerance_before INT DEFAULT 0, -- Puede salir X minutos ANTES sin penalización
time_out_tolerance_after INT DEFAULT 0,  -- Puede salir X minutos DESPUÉS (para extras)

-- Tolerancias de Almuerzo
lunch_out_tolerance_before INT DEFAULT 0,
lunch_out_tolerance_after INT DEFAULT 0,
lunch_in_tolerance_before INT DEFAULT 0,
lunch_in_tolerance_after INT DEFAULT 0
```

**Migración**: `database/migrations/2025_11_15_add_tolerance_fields_to_schedules.sql`

### Casos de Uso

**Ejemplo 1: Tolerancia de Entrada**
- Horario programado: 08:00
- Tolerancia después: 10 minutos
- Empleado llega: 08:07
- Resultado: ✅ No hay tardanza (dentro de tolerancia)

**Ejemplo 2: Tardanza Real**
- Horario programado: 08:00
- Tolerancia después: 10 minutos
- Empleado llega: 08:15
- Resultado: ❌ Tardanza de 5 minutos (15 - 10 = 5)

---

## 2. Cálculo de Tardanzas con Tolerancias ✅

### Métodos Implementados

**WorkScheduleResolver.php**:
- `calculateTardinessWithTolerance()` (líneas 304-321)
- `calculateEarlyDepartureWithTolerance()` (líneas 355-376)

### Lógica de Cálculo

```php
// Si marcó DESPUÉS de la hora programada PERO dentro de tolerancia → No hay tardanza
// Si marcó ANTES de la hora programada + tolerancia → No hay salida anticipada
// Ajusta time_in/time_out efectivos para calcular horas trabajadas correctamente
```

### Integración en AttendanceCalculator.php (líneas 136-184)

El sistema:
1. Calcula tardanzas **CON tolerancia** antes de calcular horas trabajadas
2. Ajusta `$adjustedTimeIn` y `$adjustedTimeOut` si está dentro de ventana de tolerancia
3. Las horas trabajadas se calculan usando las horas ajustadas

**Ejemplo Práctico**:
```
Horario: 08:00 - 17:00
Tolerancia entrada: 10 minutos
Empleado marca: 08:07

Resultado:
- Tardanza: 0 minutos (dentro de tolerancia)
- adjustedTimeIn: 08:00 (se normaliza a hora programada)
- Horas trabajadas: Se calculan desde 08:00
```

---

## 3. Cálculo Avanzado de Almuerzo con Tolerancias ✅

### WorkScheduleResolver::calculateLunchWithTolerance() (líneas 126-192)

#### Parámetros
- `$actualOut` / `$actualIn`: Marcaciones reales de almuerzo
- `$scheduledOut` / `$scheduledIn`: Horas programadas de almuerzo
- 4 parámetros de tolerancia (before/after para salida y entrada)
- `$baseDate`: Fecha base para normalizar timestamps

#### Reglas de Negocio

1. **Duración Base**: Usa duración programada del horario
2. **Excesos Positivos** (extienden almuerzo):
   - Salir ANTES de `(hora_programada - tolerancia_before)`
   - Regresar DESPUÉS de `(hora_programada + tolerancia_after)`
3. **Excesos Negativos** (acortan almuerzo):
   - Salir DESPUÉS de `(hora_programada + tolerancia_after)`
   - Regresar ANTES de `(hora_programada - tolerancia_before)`
4. **Resultado**: `scheduledMinutes + positiveExtra - negativeExtra`

#### Ejemplos Prácticos

**Configuración Base**:
```
Horario Programado: Salida 12:00, Entrada 13:00 (60 minutos)
Tolerancias: 5 minutos before/after en ambas
```

**Caso 1: Salida Anticipada**
```
Salió: 11:50
Regresó: 13:00
Resultado: 60 + 10 = 70 minutos
Explicación: Salió 10 min antes de la ventana (11:50 vs 11:55)
```

**Caso 2: Regreso Tardío**
```
Salió: 12:00
Regresó: 13:10
Resultado: 60 + 10 = 70 minutos
Explicación: Regresó 10 min después de la ventana (13:10 vs 13:05)
```

**Caso 3: Dentro de Tolerancias**
```
Salió: 12:05
Regresó: 12:55
Resultado: 60 minutos
Explicación: Ambas marcaciones dentro de ventanas, usa duración programada
```

**Caso 4: Almuerzo Acortado**
```
Salió: 12:10 (5 min después de ventana)
Regresó: 12:50 (5 min antes de ventana)
Resultado: 60 - 5 - 5 = 50 minutos
Explicación: Excesos negativos se restan
```

### Integración OvertimeCalculator.php (líneas 301-321)

- Llama a `calculateLunchWithTolerance()` cuando hay horario y marcaciones de almuerzo
- Usa minutos calculados para descontar de horas trabajadas
- Aplica fecha base para evitar problemas con solo HH:MM:SS

---

## 4. Elegibilidad de Horas Extras por Empleado ✅

### Tabla `employees` - Campo Nuevo

```sql
permite_horas_extras TINYINT(1) DEFAULT 1  -- 1=Permite, 0=Exento (gerentes/directores)
```

**Migración**: `database/migrations/2025_11_15_add_overtime_eligible_to_employees.sql`

### Implementación AttendanceCalculator.php

**Método**: `getEmployeeOvertimeEligibility()` (líneas 1238-1259)
- Consulta campo `permite_horas_extras` de la tabla employees

**Lógica de Aplicación** (líneas 226-234):
Si `permite_horas_extras = 0`:
- ❌ `overtime_hours = 0`
- ❌ `overtime_25_hours = 0`
- ❌ `overtime_50_hours = 0`
- ❌ `night_hours = 0`
- ✅ `regular_hours = total_hours` (todas las horas se consideran regulares)

### Casos de Uso

**Empleados Exentos** (permite_horas_extras = 0):
- Gerentes
- Directores
- Personal ejecutivo
- No reciben pago de horas extras por política empresarial

**Empleados Elegibles** (permite_horas_extras = 1):
- Personal operativo
- Empleados por hora
- Staff general

---

## 5. Estado de Aprobación de Horas Extras ✅

### Tabla `attendance_calculations` - Campo Nuevo

```sql
overtime_status ENUM('NOT_APPLICABLE', 'PENDING', 'APPROVED', 'REJECTED')
DEFAULT 'NOT_APPLICABLE'
```

**Migración**: `database/migrations/2025_11_15_add_overtime_approval_to_attendance_calculations.sql`

### AttendanceCalculator::determineOvertimeStatus() (líneas 1216-1230)

**Estados**:

1. **NOT_APPLICABLE**:
   - No hay horas extras registradas, O
   - El empleado tiene `permite_horas_extras = 0`

2. **PENDING**:
   - Hay horas extras registradas, Y
   - El empleado tiene `permite_horas_extras = 1`
   - Requiere aprobación de supervisor

3. **APPROVED**:
   - Horas extras aprobadas por supervisor
   - Se consideran para pago en planilla

4. **REJECTED**:
   - Horas extras rechazadas
   - No se pagan

### Integración Futura

**Módulo de Aprobación de Horas Extras** (OvertimeApprovalController):
- Dashboard para supervisores
- Aprobación/rechazo masivo
- Comentarios y justificaciones
- Reportes de horas extras pendientes

---

## 6. Corrección de Horas Nocturnas para Turnos Diurnos ✅

### Problema Previo

Turnos diurnos (07:00-17:00) mostraban horas nocturnas incorrectamente si el empleado salía después de 18:00.

### Solución AttendanceCalculator.php (líneas 201-223)

```php
// Si el horario NO es nocturno Y hay horas nocturnas detectadas
if (!$isNightShift && $nightHours > 0) {
    $adjIn = new DateTime($adjustedTimeIn);
    $adjOut = new DateTime($adjustedTimeOut);
    $schedOut = new DateTime($schedule['time_out']);
    $tolOutAfter = (int)($schedule['time_out_tolerance_after'] ?? 0);

    $inHour = (int)$adjIn->format('H');
    $inIsDay = ($inHour >= 6 && $inHour < 18); // Ventana diurna: 06:00-18:00

    // Permitir salida hasta dentro de tolerancia sin contar nocturnas
    $outWithinTolerance = $adjOut <= ($schedOut + $tolOutAfter);

    if ($inIsDay && $outWithinTolerance) {
        $nightHours = 0; // Forzar 0 si cumple condiciones
    }
}
```

### WorkScheduleResolver::isNightShift() (líneas 402-413)

**Criterios para Jornada Nocturna**:
- Entrada entre 18:00 y 06:00
- Basado en legislación panameña: Jornada nocturna 6PM-6AM (Art. 38)

**Ejemplos**:
```
Turno 1: 07:00 - 17:00 → NO es nocturno
Turno 2: 22:00 - 06:00 → SÍ es nocturno
Turno 3: 18:00 - 02:00 → SÍ es nocturno
```

---

## 7. Integración con Motor de Fórmulas ✅

### Funciones de Horas Extras

**Funciones Existentes** (todas las horas extras):
- `HORAS_EXTRAS_25()`: Incluye horas extras pendientes + aprobadas
- `HORAS_EXTRAS_50()`: Incluye horas extras pendientes + aprobadas

**Funciones Nuevas** (solo aprobadas):
- `HORAS_EXTRAS_APROBADAS()`: Total aprobadas (25% + 50%)
- `HORAS_EXTRAS_APROBADAS_25()`: Solo horas extras 25% aprobadas
- `HORAS_EXTRAS_APROBADAS_50()`: Solo horas extras 50% aprobadas

### Implementación PlanillaConceptCalculatorSecure.php

```php
private function HORAS_EXTRAS_APROBADAS($employeeId, $periodStart, $periodEnd) {
    // Query con WHERE overtime_status = 'APPROVED'
    // Suma overtime_25_hours + overtime_50_hours
}

private function HORAS_EXTRAS_APROBADAS_25($employeeId, $periodStart, $periodEnd) {
    // Query con WHERE overtime_status = 'APPROVED'
    // Solo suma overtime_25_hours
}

private function HORAS_EXTRAS_APROBADAS_50($employeeId, $periodStart, $periodEnd) {
    // Query con WHERE overtime_status = 'APPROVED'
    // Solo suma overtime_50_hours
}
```

### Uso en Fórmulas de Conceptos de Planilla

**Opción 1: Pagar todas las horas extras** (pendientes + aprobadas)
```php
HORAS_EXTRAS_25() * (SUELDO / 220) * 1.25
```

**Opción 2: Pagar SOLO las aprobadas** (desde módulo de aprobación)
```php
HORAS_EXTRAS_APROBADAS_25() * (SUELDO / 220) * 1.25
```

**Opción 3: Combinación condicional**
```php
SI(REQUIERE_APROBACION,
   HORAS_EXTRAS_APROBADAS() * (SUELDO / 220) * 1.25,
   HORAS_EXTRAS() * (SUELDO / 220) * 1.25
)
```

---

## 8. Interfaz de Usuario ✅

### Formularios de Empleados

**Archivos**: `app/Views/admin/employees/create.php` y `edit.php`

**Campo Agregado** (21 líneas):
```html
<div class="form-group">
    <div class="custom-control custom-checkbox">
        <input type="checkbox"
               class="custom-control-input"
               id="permite_horas_extras"
               name="permite_horas_extras"
               value="1"
               checked>
        <label class="custom-control-label" for="permite_horas_extras">
            ¿Permite Horas Extras?
        </label>
    </div>
    <small class="form-text text-muted">
        Marque si el empleado es elegible para cobro de horas extras
    </small>
</div>
```

### Formularios de Horarios

**Archivos**: `app/Views/admin/templates/reference_create.php` y `reference_edit.php`

**Campos de Tolerancias**:

**Grupo 1: Tolerancias de Entrada**
```html
<div class="col-md-6">
    <label>Tolerancia Antes (min)</label>
    <input type="number" name="time_in_tolerance_before"
           class="form-control" value="0" min="0">
</div>
<div class="col-md-6">
    <label>Tolerancia Después (min)</label>
    <input type="number" name="time_in_tolerance_after"
           class="form-control" value="0" min="0">
</div>
```

**Grupo 2: Tolerancias de Salida**
```html
<div class="col-md-6">
    <label>Tolerancia Antes (min)</label>
    <input type="number" name="time_out_tolerance_before"
           class="form-control" value="0" min="0">
</div>
<div class="col-md-6">
    <label>Tolerancia Después (min)</label>
    <input type="number" name="time_out_tolerance_after"
           class="form-control" value="0" min="0">
</div>
```

**Grupo 3: Tolerancias de Almuerzo** (4 campos similares)

---

## 📊 Estadísticas del Sistema

### Modificaciones de Base de Datos
- **3 tablas modificadas**: `schedules`, `employees`, `attendance_calculations`
- **9 campos nuevos**:
  - 8 en `schedules` (tolerancias)
  - 1 en `employees` (permite_horas_extras)
  - 1 en `attendance_calculations` (overtime_status)
- **4 migraciones SQL**:
  - Tolerancias en schedules
  - Overtime approval status
  - Elegibilidad horas extras
  - Setup completo

### Código Implementado
- **6 métodos nuevos**:
  1. `calculateLunchWithTolerance()`
  2. `calculateTardinessWithTolerance()`
  3. `calculateEarlyDepartureWithTolerance()`
  4. `determineOvertimeStatus()`
  5. `getEmployeeOvertimeEligibility()`
  6. `isNightShift()`

- **3 funciones de fórmulas nuevas**:
  1. `HORAS_EXTRAS_APROBADAS()`
  2. `HORAS_EXTRAS_APROBADAS_25()`
  3. `HORAS_EXTRAS_APROBADAS_50()`

### Archivos Afectados
- **20 archivos modificados**
- **+2,413 líneas agregadas**
- **-156 líneas eliminadas**
- **~2,257 líneas netas agregadas**

---

## 🔗 Referencias

### Commits
- [90b0c51](https://github.com/repo/commit/90b0c51) - Overtime Approvals + Eligibility + Tolerances
- [7d8b567](https://github.com/repo/commit/7d8b567) - Schedule Tolerances + Night Hours Fix
- [b89f227](https://github.com/repo/commit/b89f227) - Lunch Tolerances with Base Date

### Archivos Principales
- `app/Services/Attendance/WorkScheduleResolver.php`
- `app/Services/Attendance/AttendanceCalculator.php`
- `app/Services/Attendance/OvertimeCalculator.php`
- `app/Services/Planillas/PlanillaConceptCalculatorSecure.php`

### Legislación Aplicada
- **Art. 31**: Jornada ordinaria 8h/48h semanales
- **Art. 38**: Jornada nocturna 6PM-6AM +50%
- **Art. 39**: Horas extras +25%/+50%
- **Art. 35**: Almuerzo 30min mínimo
- **Art. 48**: Domingos/feriados +50%

---

## 🚀 Próximos Pasos

### Módulos Pendientes
1. **OvertimeApprovalController**: Dashboard para aprobación de horas extras
2. **Reportes de Tolerancias**: Análisis de uso de tolerancias por empleado
3. **Alertas Automáticas**: Notificaciones cuando se exceden tolerancias frecuentemente
4. **Estadísticas Gerenciales**: Dashboard de tendencias de tardanzas y almuerzo

### Mejoras Futuras
- Tolerancias variables por día de semana
- Reglas de tolerancia por departamento
- Aprobación automática de horas extras según políticas
- Integración con sistema de notificaciones por email/SMS

---

**Última Actualización**: 04 de Marzo, 2026
**Estado**: Sistema en producción, funcionando correctamente
**Versión**: V3.5.x (Módulo de Asistencias)
