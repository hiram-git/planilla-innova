# 💰 Cálculo de Salario Basado en Asistencia

**Fecha:** 06 de Noviembre, 2025
**Versión:** v3.5.6
**Componente:** Motor de Fórmulas - Variables de Salario

## 🎯 Objetivo

Documentar cómo calcular el salario de empleados basándose en el campo `marca_asistencia` de la tabla `employees`. Esto permite manejar dos tipos de empleados:

1. **Salario Fijo** (`marca_asistencia = 0`): Empleados con salario mensual fijo
2. **Salario Variable** (`marca_asistencia = 1`): Empleados que cobran por horas trabajadas

## 📋 Nuevas Variables Disponibles

### `MARCA_ASISTENCIA`
- **Tipo:** Entero (0 o 1)
- **Descripción:** Indica si el empleado cobra por horas trabajadas
- **Valores:**
  - `0` = Salario fijo mensual (default)
  - `1` = Salario calculado por horas trabajadas

### `TARIFA_HORA`
- **Tipo:** Decimal
- **Descripción:** Tarifa por hora del empleado
- **Cálculo:** `SUELDO / 220` (220 = horas mensuales estándar)
- **Ejemplo:** Si SUELDO = 1,500, entonces TARIFA_HORA = 6.82

### `SUELDO` (Actualizada)
- **Tipo:** Decimal
- **Descripción:** Salario base mensual del empleado
- **Uso:**
  - Para `marca_asistencia = 0`: Representa el pago mensual completo
  - Para `marca_asistencia = 1`: Representa la base para calcular la tarifa por hora

---

## 💡 Ejemplos de Uso en Conceptos

### 1. Concepto: Salario Base

**Objetivo:** Pagar salario fijo O calcular según horas trabajadas

**Tipo:** Asignación

**Fórmula:**
```php
SI(MARCA_ASISTENCIA = 1,
   HORAS_TRABAJADAS() * TARIFA_HORA,
   SUELDO
)
```

**Explicación:**
- Si `MARCA_ASISTENCIA = 1`: Calcula `horas_trabajadas × tarifa_hora`
- Si `MARCA_ASISTENCIA = 0`: Usa el `SUELDO` fijo

**Ejemplo Empleado A (Salario Fijo):**
- SUELDO = $1,500
- MARCA_ASISTENCIA = 0
- **Resultado:** $1,500 (fijo)

**Ejemplo Empleado B (Por Horas):**
- SUELDO = $1,500 (base de cálculo)
- TARIFA_HORA = $6.82
- HORAS_TRABAJADAS() = 176 horas
- MARCA_ASISTENCIA = 1
- **Resultado:** 176 × $6.82 = $1,200.32

---

### 2. Concepto: Horas Extras 25%

**Objetivo:** Calcular pago de horas extras con recargo del 25%

**Tipo:** Asignación

**Fórmula:**
```php
HORAS_EXTRAS_25() * TARIFA_HORA * 1.25
```

**Explicación:**
- Funciona igual para ambos tipos de empleados
- Usa `TARIFA_HORA` que se calcula automáticamente
- No necesita condicional porque `HORAS_EXTRAS_25()` solo tiene valor si hay horas extras

**Ejemplo:**
- TARIFA_HORA = $6.82
- HORAS_EXTRAS_25() = 5 horas
- **Resultado:** 5 × $6.82 × 1.25 = $42.63

---

### 3. Concepto: Salario con Prorrateado

**Objetivo:** Calcular salario proporcional a días trabajados

**Tipo:** Asignación

**Opción A - Fórmula Simple:**
```php
SI(MARCA_ASISTENCIA = 1,
   HORAS_TRABAJADAS() * TARIFA_HORA,
   SUELDO * DIAS_TRABAJADOS() / 30
)
```

**Opción B - Con Variable Intermedia:**
```php
salario_base = SI(MARCA_ASISTENCIA = 1, HORAS_TRABAJADAS() * TARIFA_HORA, SUELDO)
salario_base * DIAS_TRABAJADOS() / 30
```

**Explicación:**
- Empleados por hora: Automáticamente proporcional (pagan solo horas trabajadas)
- Empleados fijos: Prorratea según días trabajados del mes

---

### 4. Concepto: Salario Quincenal

**Objetivo:** Pagar quincena (50% del salario mensual)

**Tipo:** Asignación

**Fórmula:**
```php
SI(MARCA_ASISTENCIA = 1,
   HORAS_TRABAJADAS() * TARIFA_HORA * 0.5,
   SUELDO * 0.5
)
```

**Nota:** Para empleados por hora, asume que `HORAS_TRABAJADAS()` tiene el total del mes, no solo la quincena. Ajustar según implementación del período.

---

### 5. Concepto: Bono Asistencia Perfecta

**Objetivo:** Bonificar a empleados con asistencia perfecta

**Tipo:** Asignación

**Fórmula:**
```php
SI(DIAS_ASISTENCIA_PERFECTA() >= 20,
   SI(MARCA_ASISTENCIA = 1, 100, 150),
   0
)
```

**Explicación:**
- Si hay 20+ días de asistencia perfecta: dar bono
- Empleados por hora reciben $100
- Empleados fijos reciben $150 (mayor incentivo)

---

## 🔧 Escenarios de Implementación

### Escenario 1: Empresa con Ambos Tipos

**Empleados Fijos:**
- Administrativos
- Gerentes
- Personal permanente

**Empleados por Hora:**
- Temporales
- Contratistas
- Medio tiempo

**Configuración:**
1. Marcar `marca_asistencia = 1` en empleados por hora
2. Dejar `marca_asistencia = 0` (o NULL) en empleados fijos
3. Usar fórmulas condicionales en conceptos de salario base

---

### Escenario 2: Solo Empleados Fijos

**Configuración:**
- Todos los empleados con `marca_asistencia = 0`
- Las fórmulas con `SI(MARCA_ASISTENCIA = 1, ...)` siempre usarán la rama del SUELDO fijo
- No hay impacto en el sistema actual

---

### Escenario 3: Solo Empleados por Hora

**Configuración:**
- Todos los empleados con `marca_asistencia = 1`
- Salarios calculados según horas reales trabajadas
- Importante tener sistema de marcaciones funcionando correctamente

---

## 📊 Tabla Comparativa

| Concepto | Empleado Fijo | Empleado por Hora |
|----------|---------------|-------------------|
| **Salario Base** | SUELDO fijo | HORAS_TRABAJADAS() × TARIFA_HORA |
| **Horas Extras** | HORAS_EXTRAS() × TARIFA_HORA × 1.25 | HORAS_EXTRAS() × TARIFA_HORA × 1.25 |
| **Tardanzas** | Descuento proporcional | Automático (menos horas = menos pago) |
| **Ausencias** | Descuento por día | Automático (0 horas = 0 pago) |
| **Salario Diario** | SUELDO / 30 | HORAS_DIA × TARIFA_HORA |

---

## ⚙️ Configuración en Base de Datos

### Tabla: `employees`

```sql
-- Empleado con salario fijo
UPDATE employees
SET marca_asistencia = 0,
    sueldo_individual = 1500.00
WHERE id = 1;

-- Empleado que cobra por horas
UPDATE employees
SET marca_asistencia = 1,
    sueldo_individual = 1500.00  -- Base para calcular tarifa hora
WHERE id = 2;
```

---

## 🎨 Conceptos Sugeridos

### Concepto 1: SUELDO_BASE
- **Código:** SAL001
- **Descripción:** Salario Base Mensual
- **Tipo:** Asignación
- **Fórmula:**
```php
SI(MARCA_ASISTENCIA = 1,
   HORAS_TRABAJADAS() * TARIFA_HORA,
   SUELDO
)
```

### Concepto 2: HORAS_EXTRAS_25
- **Código:** HE025
- **Descripción:** Horas Extras 25%
- **Tipo:** Asignación
- **Fórmula:**
```php
HORAS_EXTRAS_25() * TARIFA_HORA * 1.25
```

### Concepto 3: HORAS_EXTRAS_50
- **Código:** HE050
- **Descripción:** Horas Extras 50%
- **Tipo:** Asignación
- **Fórmula:**
```php
HORAS_EXTRAS_50() * TARIFA_HORA * 1.50
```

### Concepto 4: DESCUENTO_TARDANZAS
- **Código:** DED_TARD
- **Descripción:** Descuento por Tardanzas
- **Tipo:** Deducción
- **Fórmula:**
```php
SI(MARCA_ASISTENCIA = 1,
   0,
   TARDANZAS() / 60 * TARIFA_HORA
)
```
**Nota:** Empleados por hora no necesitan descuento explícito (pagan solo horas trabajadas).

### Concepto 5: DESCUENTO_AUSENCIAS
- **Código:** DED_AUS
- **Descripción:** Descuento por Ausencias
- **Tipo:** Deducción
- **Fórmula:**
```php
SI(MARCA_ASISTENCIA = 1,
   0,
   AUSENCIAS() * (SUELDO / 30)
)
```

---

## 📐 Cálculos Internos

### Tarifa por Hora
```php
TARIFA_HORA = SUELDO / 220
```

**Donde:**
- 220 = Horas mensuales estándar
- Cálculo: 8 horas/día × 5 días/semana × 4.4 semanas/mes ≈ 176 horas
- Se usa 220 como estándar de la industria (incluye horas no productivas)

**Alternativas según legislación:**
- 173.33 = (8 horas × 52 semanas) / 12 meses
- 176 = 8 horas × 22 días laborables
- 220 = Estándar industria (más conservador)

---

## ✅ Ventajas del Sistema

1. **Flexibilidad:** Un solo sistema para ambos tipos de empleados
2. **Transparencia:** Fórmulas claras y auditables
3. **Automático:** Cálculos basados en asistencia real
4. **Escalable:** Fácil agregar más tipos de cálculos
5. **Justo:** Empleados por hora pagan exacto lo trabajado

---

## 🔍 Debugging y Testing

### Verificar Variables de un Empleado

**Query SQL:**
```sql
SELECT
    e.id,
    CONCAT(e.firstname, ' ', e.lastname) as nombre,
    e.sueldo_individual as sueldo,
    e.marca_asistencia,
    ROUND(e.sueldo_individual / 220, 2) as tarifa_hora_calculada
FROM employees e
WHERE e.id = 1;
```

### Probar Fórmula en Concepto

1. Ir a Conceptos → Editar
2. Usar el botón "Probar Fórmula"
3. Seleccionar empleado de prueba
4. Ver resultado calculado

**Ejemplo de Salida:**
```
Empleado: Juan Pérez
SUELDO: 1500.00
MARCA_ASISTENCIA: 1
TARIFA_HORA: 6.82
HORAS_TRABAJADAS: 176
Resultado: 1200.32
```

---

## 📝 Notas Importantes

1. **Retrocompatibilidad:** Empleados sin `marca_asistencia` (NULL) se tratan como `0` (salario fijo)
2. **220 Horas:** Es un estándar configurable, puede ajustarse según necesidad
3. **Períodos:** `HORAS_TRABAJADAS()` usa el período de la planilla (INIPERIODO/FINPERIODO)
4. **Gastos de Representación:** Se incluyen en SUELDO base automáticamente
5. **Horas Extras:** Siempre calculan usando TARIFA_HORA, independiente de marca_asistencia

---

## 🚀 Implementación en Producción

### Paso 1: Configurar Empleados
```sql
-- Actualizar empleados que cobran por hora
UPDATE employees
SET marca_asistencia = 1
WHERE employee_id IN ('E001', 'E002', 'E003');
```

### Paso 2: Crear/Actualizar Concepto Salario Base
- Código: SAL001
- Fórmula: `SI(MARCA_ASISTENCIA = 1, HORAS_TRABAJADAS() * TARIFA_HORA, SUELDO)`

### Paso 3: Probar en Planilla de Prueba
1. Crear planilla de prueba con ambos tipos de empleados
2. Procesar y verificar cálculos
3. Comparar con cálculos manuales

### Paso 4: Documentar Empleados por Hora
- Mantener lista actualizada de empleados `marca_asistencia = 1`
- Incluir en manual de procedimientos de RRHH

---

## 📅 Historial de Cambios

| Fecha | Versión | Cambio |
|-------|---------|--------|
| 06-Nov-2025 | v3.5.6 | Implementación inicial de MARCA_ASISTENCIA y TARIFA_HORA |

---

## 📚 Referencias

- Archivo: `app/Services/PlanillaConceptCalculator.php` (líneas 49-160)
- Documentación: `MAPEO_FUNCIONES_ASISTENCIA.md`
- Documentación: `CLAUDE.md` (Motor de Fórmulas)
