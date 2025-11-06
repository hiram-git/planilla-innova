# 📊 Mapeo de Funciones de Asistencia

**Fecha:** 06 de Noviembre, 2025
**Versión:** v3.5.5
**Componente:** Motor de Fórmulas - Funciones de Asistencias

## 🎯 Objetivo

Documentar el mapeo entre las funciones de asistencia del motor de fórmulas y los campos de la tabla `attendance_calculations`.

## 📋 Tablas Involucradas

### 1. `attendance_calculations`
Tabla principal que almacena cálculos diarios de asistencias (un registro por día por empleado).

**Campos relevantes:**
- `total_hours` - Total horas trabajadas del día
- `regular_hours` - Horas regulares (hasta 8h)
- `overtime_hours` - Total horas extras
- `overtime_25_hours` - Horas extras al 25%
- `overtime_50_hours` - Horas extras al 50%
- `night_hours` - Horas nocturnas (6PM-6AM)
- `holiday_hours` - Horas trabajadas en feriados
- `tardiness_minutes` - Minutos de tardanza
- `is_late` - Booleano si llegó tarde
- `is_absent` - Booleano si estuvo ausente
- `is_perfect_attendance` - Booleano si tuvo asistencia perfecta
- `punctuality_score` - Score de puntualidad (0-100)
- `is_weekend` - Booleano si es fin de semana

### 2. `attendance_absence_log`
Tabla que registra ausencias detectadas con justificaciones.

**Campos relevantes:**
- `absence_date` - Fecha de la ausencia
- `justified` - Booleano si está justificada
- `justification_type` - Tipo de justificación

## 🔗 Mapeo Completo

### **Funciones de Horas Trabajadas (Asignaciones)**

Estas funciones agregan valores con `SUM()` sobre el período de la planilla.

| Función Motor Fórmulas | Campo Base de Datos | Agregación | Tipo Query |
|------------------------|---------------------|------------|------------|
| `HORAS_TRABAJADAS()` | `total_hours` | SUM | Direct |
| `HORAS_REGULARES()` | `regular_hours` | SUM | Direct |
| `HORAS_EXTRAS()` | `overtime_hours` | SUM | Direct |
| `HORAS_EXTRAS_25()` | `overtime_25_hours` | SUM | Direct |
| `HORAS_EXTRAS_50()` | `overtime_50_hours` | SUM | Direct |
| `HORAS_NOCTURNAS()` | `night_hours` | SUM | Direct |
| `HORAS_FERIADOS()` | `holiday_hours` | SUM | Direct |
| `HORAS_DOMINICALES()` | `total_hours` WHERE `is_weekend=1` | SUM | Special |

**Ejemplo de uso en concepto:**
```php
// Concepto: Pago Horas Extras 25%
HORAS_EXTRAS_25() * (SUELDO / 220) * 1.25

// Concepto: Bono Horas Nocturnas
HORAS_NOCTURNAS() * (SUELDO / 220) * 1.5

// Concepto: Pago Dominical
HORAS_DOMINICALES() * (SUELDO / 220) * 1.5
```

---

### **Funciones de Tardanzas (Deducciones)**

| Función Motor Fórmulas | Campo Base de Datos | Agregación | Tipo Query |
|------------------------|---------------------|------------|------------|
| `TARDANZAS()` | `tardiness_minutes` | SUM | Direct |
| `CANTIDAD_TARDANZAS()` | `is_late=1` | COUNT | Count |

**Ejemplo de uso en concepto:**
```php
// Concepto: Descuento por Tardanzas
TARDANZAS() / 60 * (SUELDO / 220)

// Concepto: Penalización por 3+ Tardanzas
SI(CANTIDAD_TARDANZAS() >= 3, SUELDO * 0.02, 0)
```

---

### **Funciones de Ausencias (Deducciones)**

Estas funciones consultan la tabla `attendance_absence_log`.

| Función Motor Fórmulas | Tabla | Condición | Agregación | Tipo Query |
|------------------------|-------|-----------|------------|------------|
| `AUSENCIAS()` | `attendance_absence_log` | `justified=0` | COUNT | AbsenceLog |
| `AUSENCIAS_JUSTIFICADAS()` | `attendance_absence_log` | `justified=1` | COUNT | AbsenceLog |
| `TOTAL_AUSENCIAS()` | `attendance_absence_log` | (todas) | COUNT | AbsenceLog |

**Ejemplo de uso en concepto:**
```php
// Concepto: Descuento por Ausencias Injustificadas
AUSENCIAS() * (SUELDO / 30)

// Concepto: Alerta por 3+ Ausencias
SI(AUSENCIAS() >= 3, 1, 0)
```

---

### **Funciones de Estadísticas**

| Función Motor Fórmulas | Campo Base de Datos | Agregación | Tipo Query |
|------------------------|---------------------|------------|------------|
| `SCORE_PUNTUALIDAD()` | `punctuality_score` | AVG | Direct |
| `DIAS_ASISTENCIA_PERFECTA()` | `is_perfect_attendance=1` | COUNT | Count |
| `DIAS_TRABAJADOS()` | `is_absent=0` | COUNT | Count |

**Ejemplo de uso en concepto:**
```php
// Concepto: Bono Puntualidad
SI(SCORE_PUNTUALIDAD() >= 95, 100, 0)

// Concepto: Bono Asistencia Perfecta Mensual
SI(DIAS_ASISTENCIA_PERFECTA() >= 20, 50, 0)

// Concepto: Bono Días Trabajados Completos
SI(DIAS_TRABAJADOS() >= 22, SUELDO * 0.05, 0)
```

---

## 🔧 Implementación Técnica

### Archivo: `PlanillaConceptCalculatorSecure.php`

**Método Principal:**
```php
protected function obtenerDatoAsistencia(string $campo): float
```

Consulta directamente `attendance_calculations` haciendo agregaciones SQL según el tipo de dato solicitado.

**Métodos Auxiliares:**

1. **`ejecutarQueryAsistencia()`** - Router que mapea funciones a queries
2. **`queryAggregation()`** - Ejecuta SUM, AVG sobre attendance_calculations
3. **`queryCount()`** - Ejecuta COUNT con condiciones sobre attendance_calculations
4. **`queryAbsences()`** - Consulta attendance_absence_log

### Mapeo Interno

```php
// Mapeo directo (SUM/AVG)
$mapeoDirecto = [
    'total_hours_worked' => 'SUM(total_hours)',
    'regular_hours' => 'SUM(regular_hours)',
    'overtime_hours_25' => 'SUM(overtime_25_hours)',
    'overtime_hours_50' => 'SUM(overtime_50_hours)',
    'night_hours' => 'SUM(night_hours)',
    'holiday_hours' => 'SUM(holiday_hours)',
    'total_tardiness_minutes' => 'SUM(tardiness_minutes)',
    'punctuality_score' => 'AVG(punctuality_score)',
];

// Mapeo de conteos
$mapeoConteos = [
    'tardiness_count' => 'COUNT(*) WHERE is_late = 1',
    'perfect_attendance_days' => 'COUNT(*) WHERE is_perfect_attendance = 1',
    'total_days_worked' => 'COUNT(*) WHERE is_absent = 0',
];

// Mapeo especial (tabla diferente o condiciones complejas)
$mapeoEspecial = [
    'sunday_hours' => 'SUM(total_hours) WHERE is_weekend = 1',
    'unjustified_absences' => 'absence_log_unjustified',
    'total_absences' => 'absence_log_total',
    'justified_absences' => 'absence_log_justified',
];
```

---

## 📊 Queries SQL Generados

### Ejemplo 1: HORAS_EXTRAS_25()
```sql
SELECT SUM(overtime_25_hours) as result
FROM attendance_calculations
WHERE employee_id = ?
AND date >= ?
AND date <= ?
```

### Ejemplo 2: CANTIDAD_TARDANZAS()
```sql
SELECT COUNT(*) as result
FROM attendance_calculations
WHERE employee_id = ?
AND date >= ?
AND date <= ?
AND is_late = 1
```

### Ejemplo 3: AUSENCIAS()
```sql
SELECT COUNT(*) as result
FROM attendance_absence_log
WHERE employee_id = ?
AND absence_date >= ?
AND absence_date <= ?
AND justified = 0
```

### Ejemplo 4: HORAS_DOMINICALES()
```sql
SELECT SUM(total_hours) as result
FROM attendance_calculations
WHERE employee_id = ?
AND date >= ?
AND date <= ?
AND is_weekend = 1
```

---

## ✅ Ventajas del Nuevo Sistema

1. **Consultas Directas:** Ya no depende de `payroll_attendance_summary` pre-calculado
2. **Tiempo Real:** Los datos se calculan dinámicamente del período exacto
3. **Flexibilidad:** Fácil agregar nuevas funciones con diferentes agregaciones
4. **Granularidad:** Acceso a datos diarios si se necesita en el futuro
5. **Mantenibilidad:** Código más claro con mapeos explícitos

## 🔄 Compatibilidad

- ✅ **Retrocompatible** con fórmulas existentes
- ✅ Las 16 funciones de asistencia funcionan igual que antes
- ✅ No requiere cambios en conceptos existentes
- ⚠️ Ahora consulta `attendance_calculations` en lugar de `payroll_attendance_summary`

---

## 📝 Notas Importantes

1. **Período de la Planilla:** Las funciones usan `INIPERIODO` y `FINPERIODO` automáticamente
2. **Empleado Actual:** Las funciones usan el `EMPLOYEE_ID` del contexto actual
3. **Valores Opcionales:** Si no hay datos, las funciones retornan `0` (no causan error)
4. **Performance:** Las queries están optimizadas con índices en `attendance_calculations`

---

## 🚀 Uso en Conceptos

### Ejemplo Completo: Concepto Horas Extras

**Configuración del Concepto:**
- **Código:** HE025
- **Descripción:** Horas Extras 25%
- **Tipo:** Asignación
- **Fórmula:**
  ```php
  HORAS_EXTRAS_25() * (SUELDO / 220) * 1.25
  ```

**Cálculo para un empleado:**
1. Motor evalúa `HORAS_EXTRAS_25()`
2. Consulta `attendance_calculations` del período de la planilla
3. Suma todos los valores de `overtime_25_hours` del empleado
4. Retorna, ejemplo: `12.5` horas
5. Calcula: `12.5 * (1500 / 220) * 1.25 = 106.53`

---

## 📅 Historial de Cambios

| Fecha | Versión | Cambio |
|-------|---------|--------|
| 31-Oct-2025 | v3.5.3 | Funciones iniciales usando `payroll_attendance_summary` |
| 06-Nov-2025 | v3.5.5 | Migración a consultas directas sobre `attendance_calculations` |

---

## 📚 Referencias

- Archivo: `app/Services/PlanillaConceptCalculatorSecure.php` (líneas 750-957)
- Migración BD: `database/migrations/2025_10_10_attendance_calculations.sql`
- Documentación: `CLAUDE.md` (sección Funciones de Asistencias)
