# Asignación Dinámica de UNIDAD en Fórmulas

**Fecha**: 28 de Diciembre, 2025
**Versión**: Sistema de Planillas V3.5+

## 🎯 Objetivo

Permitir que las fórmulas de conceptos asignen dinámicamente el valor del campo `unidad` en `planilla_detalle`, basándose en condiciones como si el empleado marca asistencia o no.

## ✅ Implementación

### **Archivos Modificados**

1. **PlanillaConceptCalculatorSecure.php** (líneas 634-645)
   - Agregado método `obtenerUnidadCalculada()` para capturar el valor de UNIDAD

2. **PayrollController.php** (líneas 1574-1583)
   - Captura automática de UNIDAD después de evaluar la fórmula
   - Prioridad: Valor asignado en fórmula > Cálculo por defecto

## 📝 Sintaxis de Uso

### **Formato de Fórmula con UNIDAD Dinámica**

```php
UNIDAD = expresión
resultado
```

**Importante**: Las fórmulas **deben estar en dos líneas separadas**:
1. **Primera línea**: Asigna el valor a UNIDAD
2. **Segunda línea**: Retorna el monto calculado

### **Ejemplos Reales**

#### **Ejemplo 1: Salario Base con Asistencias**

```php
UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)
SI(MARCA_ASISTENCIA, HORAS_REGULARES() * TARIFA_HORA, SUELDO * 0.5)
```

**Explicación**:
- Si marca asistencia (MARCA_ASISTENCIA = 1):
  - UNIDAD = horas trabajadas (ej: 120 horas)
  - Monto = horas × tarifa hora
- Si NO marca asistencia (MARCA_ASISTENCIA = 0):
  - UNIDAD = 15 días (quincenal)
  - Monto = 50% del sueldo

#### **Ejemplo 2: Horas Extras con Validación**

```php
UNIDAD = SI(PERMITE_HORAS_EXTRAS, HORAS_EXTRAS(), 0)
SI(PERMITE_HORAS_EXTRAS, HORAS_EXTRAS() * TARIFA_HORA * 1.5, 0)
```

**Resultado**:
- Empleados con horas extras permitidas:
  - UNIDAD = cantidad de horas extras (ej: 10)
  - Monto = 10 × tarifa × 1.5
- Empleados exentos:
  - UNIDAD = 0
  - Monto = 0

#### **Ejemplo 3: Comisiones por Ventas**

```php
UNIDAD = SI(VENTAS > 10000, 5, SI(VENTAS > 5000, 3, 1))
VENTAS * (UNIDAD / 100)
```

**Resultado**:
- Ventas > $10,000: UNIDAD = 5% → Comisión 5%
- Ventas > $5,000: UNIDAD = 3% → Comisión 3%
- Ventas ≤ $5,000: UNIDAD = 1% → Comisión 1%

#### **Ejemplo 4: Bonificación por Antigüedad**

```php
UNIDAD = SI(ANTIGUEDAD >= 5, 100, SI(ANTIGUEDAD >= 3, 50, SI(ANTIGUEDAD >= 1, 25, 0)))
UNIDAD
```

**Resultado**:
- 5+ años: UNIDAD = $100 → Bono $100
- 3-4 años: UNIDAD = $50 → Bono $50
- 1-2 años: UNIDAD = $25 → Bono $25
- < 1 año: UNIDAD = $0 → Sin bono

## 🔧 Cómo Funciona

### **Flujo de Ejecución**

1. **Evaluación de Fórmula**
   ```php
   $amount = $calculadora->evaluarFormula($concept['formula']);
   ```

2. **Captura de UNIDAD**
   ```php
   $unidadCalculada = $calculadora->obtenerUnidadCalculada();
   ```

3. **Decisión de Valor**
   - Si la fórmula asignó UNIDAD → usar ese valor
   - Si no → usar cálculo por defecto basado en `conceptos.unidad`

4. **Guardado en BD**
   ```sql
   INSERT INTO planilla_detalle (..., unidad) VALUES (..., $unidadCalculada)
   ```

### **Precedencia**

1. **Valor asignado en fórmula** (mayor prioridad)
2. **Cálculo por defecto** según tipo de unidad:
   - `días` → 15
   - `horas` → 120
   - `porcentaje` → 100
   - `monto` → 1

## 📊 Variables Disponibles

### **Variables de Empleado**
- `SUELDO` / `SALARIO` - Salario base del empleado
- `FICHA` / `EMPLOYEE_ID` - Código/ID del empleado
- `ANTIGUEDAD` - Años de antigüedad
- `MARCA_ASISTENCIA` - 1 si marca, 0 si no
- `TARIFA_HORA` - Tarifa por hora del empleado
- `GASTOS_REP` / `GASTOS_REPRESENTACION` - Gastos de representación

### **Variables de Asistencias**
- `HORAS_REGULARES()` - Horas trabajadas regulares
- `HORAS_EXTRAS()` - Total horas extras
- `HORAS_EXTRAS_25()` - Horas extras al 25%
- `HORAS_EXTRAS_50()` - Horas extras al 50%
- `HORAS_NOCTURNAS()` - Horas nocturnas
- `HORAS_FERIADOS()` - Horas en días feriados
- `TARDANZAS()` - Minutos de tardanza
- `AUSENCIAS()` - Días de ausencia

### **Funciones Condicionales**
- `SI(condicion, valorVerdadero, valorFalso)` - Condicional Excel-style
- `MAX(val1, val2, ...)` - Valor máximo
- `MIN(val1, val2, ...)` - Valor mínimo
- `SUMA(val1, val2, ...)` - Suma de valores
- `PROMEDIO(val1, val2, ...)` - Promedio de valores

## ⚠️ Consideraciones Importantes

### **Formato de Fórmula**

❌ **INCORRECTO** (una sola línea):
```php
UNIDAD=SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15) SI(MARCA_ASISTENCIA, HORAS_REGULARES()* TARIFA_HORA, SUELDO*0.5)
```

✅ **CORRECTO** (dos líneas separadas):
```php
UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)
SI(MARCA_ASISTENCIA, HORAS_REGULARES() * TARIFA_HORA, SUELDO * 0.5)
```

### **Tipos de Datos**

- **UNIDAD** puede ser **numérico** o **string**
- Para valores numéricos: `UNIDAD = 15` o `UNIDAD = 120.5`
- Para texto: `UNIDAD = "días"` (menos común pero soportado)

### **Compatibilidad**

- ✅ Funciona con conceptos de tipo **INGRESO**, **DEDUCCION**, **PATRONAL**
- ✅ Compatible con fórmulas **multilínea** complejas
- ✅ Se guarda en `planilla_detalle.unidad` (VARCHAR 50)
- ✅ Se muestra en vistas de detalles de planilla

## 🧪 Testing

### **Caso de Prueba 1: Empleado con Marcación**

**Datos**:
- MARCA_ASISTENCIA = 1
- HORAS_REGULARES() = 120
- TARIFA_HORA = 5.50
- SUELDO = 800

**Fórmula**:
```php
UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)
SI(MARCA_ASISTENCIA, HORAS_REGULARES() * TARIFA_HORA, SUELDO * 0.5)
```

**Resultado Esperado**:
- UNIDAD = 120
- Monto = 120 × 5.50 = $660.00

### **Caso de Prueba 2: Empleado sin Marcación**

**Datos**:
- MARCA_ASISTENCIA = 0
- SUELDO = 800

**Fórmula**: (misma de arriba)

**Resultado Esperado**:
- UNIDAD = 15
- Monto = 800 × 0.5 = $400.00

## 📍 Ubicación en el Sistema

### **Visualización**

La unidad calculada se muestra en:

1. **Vista de Detalle de Empleado** (`/panel/payrolls/{id}/employee/{employeeId}`)
   - Columna "Unidad" en tablas de Ingresos y Deducciones
   - Badge con color según tipo (azul para ingresos, amarillo para deducciones)

2. **Reportes PDF/Excel**
   - Campo `unidad` incluido en reportes de planilla

3. **Base de Datos**
   - Tabla: `planilla_detalle`
   - Campo: `unidad` VARCHAR(50)

### **Edición**

Para configurar una fórmula con UNIDAD dinámica:

1. Ir a **Panel → Conceptos**
2. Editar/Crear concepto
3. En **Fórmula**, escribir en dos líneas:
   ```
   UNIDAD = expresión
   resultado
   ```
4. Guardar

## 🔒 Seguridad

- ✅ **Sin eval()**: Todo se evalúa con `nxp/math-executor`
- ✅ **Validación de variables**: Solo variables autorizadas
- ✅ **Protección contra inyección**: Nombres de variables validados con regex
- ✅ **Manejo de errores**: Valores por defecto en caso de error

## 📚 Referencias

- Documentación principal: `CLAUDE.md`
- Implementación campo UNIDAD: `IMPLEMENTACION_CAMPO_UNIDAD.md`
- Motor de fórmulas: `app/Services/PlanillaConceptCalculatorSecure.php`
- Procesamiento planillas: `app/Controllers/PayrollController.php`

---

**Nota**: Esta funcionalidad está disponible desde la versión 3.5.14 del sistema.
