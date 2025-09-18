# 💰 Fórmula del XIII Mes (Décimo Tercer Mes) - Panamá

## 📋 **Resumen de la Fórmula**

El XIII mes se calcula basándose en los días trabajados y el total de salarios devengados en el periodo.

---

## 🧮 **Fórmula Matemática**

### **Paso 1: Cálculo de Días Trabajados**
```
Si fecha_ingreso <= fecha_inicio_periodo:
    dias_trabajados = (fecha_fin_periodo - fecha_inicio_periodo) + 1
Sino:
    dias_trabajados = (fecha_fin_periodo - fecha_ingreso) + 1
```

### **Paso 2: Cálculo de Total de Salarios**
```
total_salarios = Σ(salario_base + horas_extras + comisiones + bonificaciones)
```
*Suma de todos los conceptos de INGRESO en el periodo, excluyendo XIII mes y prima de antigüedad*

### **Paso 3: Aplicación de la Fórmula del XIII Mes**
```
Si dias_trabajados >= 122:
    xiii_mes = total_salarios ÷ 12
Sino:
    proporcion = dias_trabajados ÷ 122
    xiii_mes = (total_salarios ÷ 12) × proporcion
```

---

## 📊 **Ejemplo Práctico**

### **Caso 1: Empleado con Año Completo**
- **Fecha ingreso**: 2023-01-15
- **Periodo**: 2024-01-01 al 2024-12-31
- **Días trabajados**: 365 días
- **Total salarios**: $15,000.00

```
dias_trabajados = 365 >= 122 ✓
xiii_mes = $15,000 ÷ 12 = $1,250.00
```

### **Caso 2: Empleado Nuevo (Proporcional)**
- **Fecha ingreso**: 2024-09-01
- **Periodo**: 2024-01-01 al 2024-12-31
- **Días trabajados**: 122 días (sep-dic)
- **Total salarios**: $6,000.00

```
dias_trabajados = 122 >= 122 ✓
xiii_mes = $6,000 ÷ 12 = $500.00
```

### **Caso 3: Empleado con Pocos Días**
- **Fecha ingreso**: 2024-10-01
- **Periodo**: 2024-01-01 al 2024-12-31
- **Días trabajados**: 92 días (oct-dic)
- **Total salarios**: $4,600.00

```
dias_trabajados = 92 < 122
proporcion = 92 ÷ 122 = 0.7541 (75.41%)
xiii_mes = ($4,600 ÷ 12) × 0.7541 = $289.07
```

---

## 🔧 **Implementación en el Sistema**

### **Configuración del Concepto**
- **ID**: 18
- **Código**: XIII_MES
- **Descripción**: Décimo Tercer Mes (XIII Mes)
- **Tipo**: INGRESO
- **Fórmula**: `XIII_MES_AUTOMATICO()`

### **Método de Cálculo en PHP**
```php
public function calcularXIIIMes(int $employee_id, string $fecha_inicio, string $fecha_fin): float
{
    // 1. Obtener fecha de ingreso
    $fecha_ingreso = $this->obtenerFechaIngreso($employee_id);

    // 2. Calcular días trabajados
    $dias_trabajados = $this->calcularDiasTrabajados($fecha_ingreso, $fecha_inicio, $fecha_fin);

    // 3. Calcular total de salarios
    $total_salarios = $this->calcularTotalSalariosEnPeriodo($employee_id, $fecha_inicio, $fecha_fin);

    // 4. Aplicar fórmula
    if ($dias_trabajados >= 122) {
        return $total_salarios / 12;
    } else {
        $proporcion = $dias_trabajados / 122;
        return ($total_salarios / 12) * $proporcion;
    }
}
```

---

## 📋 **Criterios y Validaciones**

### **Días Mínimos Requeridos**
- **122 días**: Periodo mínimo para XIII mes completo
- **< 122 días**: XIII mes proporcional

### **Conceptos Incluidos en Total de Salarios**
✅ **Incluir:**
- Salario base
- Horas extras
- Comisiones
- Bonificaciones
- Todos los conceptos tipo "INGRESO" o "A"

❌ **Excluir:**
- XIII mes (evitar duplicación)
- Prima de antigüedad
- Conceptos tipo "DEDUCCIÓN" o "D"

### **Manejo de Fechas**
- **Fecha de ingreso antes del periodo**: Contar desde inicio del periodo
- **Fecha de ingreso durante el periodo**: Contar desde fecha de ingreso
- **Incluir día final**: +1 día en el cálculo

---

## 🎯 **Casos Especiales**

### **Empleado sin Planillas Procesadas**
```
Si total_salarios = 0:
    total_salarios = salario_base_empleado
```

### **Empresa Pública vs Privada**
- **Pública**: Usar `sueldo_posicion`
- **Privada**: Usar `sueldo_individual` (fallback a `sueldo_posicion`)

### **Redondeo**
```
xiii_mes = ROUND(resultado, 2)
```

---

## 📝 **Notas de Implementación**

1. **Integración Automática**: El cálculo se ejecuta automáticamente cuando se procesa el concepto XIII_MES
2. **Cache de Datos**: Los salarios se consultan desde `planilla_detalle` para mayor precisión
3. **Validación de Periodo**: Siempre usar año calendario completo (enero-diciembre)
4. **Log de Errores**: Todos los errores se registran para auditoría

---

**✅ Fórmula implementada y probada en el sistema de planillas MVC**