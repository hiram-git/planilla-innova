# 🧮 Fórmula del XIII Mes para el Sistema de Planillas

## 📋 **Fórmula en el Sistema**

Para configurar el concepto XIII_MES en el sistema, usar la siguiente fórmula en el campo **formula** del concepto:

```
XIII_MES()
```

---

## 🔧 **Configuración del Concepto en la Base de Datos**

### **Tabla: `concepto`**
```sql
UPDATE concepto
SET formula = 'XIII_MES()'
WHERE concepto = 'XIII_MES';
```

### **Datos del Concepto:**
- **ID**: 18
- **Concepto**: `XIII_MES`
- **Descripción**: `Décimo Tercer Mes (XIII Mes)`
- **Fórmula**: `XIII_MES()`
- **Tipo**: `INGRESO`

---

## 🎯 **Cómo Funciona la Función XIII_MES()**

La función `XIII_MES()` es procesada automáticamente por el calculador de conceptos y ejecuta la siguiente lógica:

### **Algoritmo Interno:**
1. **Obtiene el employee_id** del empleado actual en procesamiento
2. **Calcula el periodo** del año en curso (enero-diciembre)
3. **Ejecuta el cálculo completo** usando `calcularXIIIMes()`
4. **Retorna el monto** calculado según la legislación panameña

### **Implementación en PHP:**
```php
// En PlanillaConceptCalculator.php
$formula = preg_replace_callback('/XIII_MES\(\)/', function($matches) {
    if (isset($this->variablesColaborador['EMPLOYEE_ID'])) {
        return (string)$this->calcularXIIIMesConFechasPlanilla();
    }
    return '0';
}, $formula);
```

---

## 📊 **Variables Disponibles en Fórmulas**

El sistema tiene las siguientes variables disponibles para cualquier fórmula:

### **Variables del Empleado:**
- `SALARIO` - Salario base del empleado
- `SUELDO` - Alias de SALARIO
- `FICHA` - Código del empleado (employee_id)
- `EMPLEADO` - Alias de FICHA
- `EMPLOYEE_ID` - ID numérico interno (para cálculos)
- `HORAS` - Horas semanales (calculadas)
- `ANTIGUEDAD` - Años de antigüedad

### **Funciones Disponibles:**
- `SI(condicion, valor_verdadero, valor_falso)` - Condicional
- `ACREEDOR(FICHA, id_deduction)` - Obtener monto de acreedor
- `XIII_MES()` - Calcular décimo tercer mes ✨ **NUEVA**

### **Operadores Matemáticos:**
- `+` - Suma
- `-` - Resta
- `*` - Multiplicación
- `/` - División
- `()` - Paréntesis para agrupación

---

## 💡 **Ejemplos de Fórmulas**

### **XIII Mes Simple:**
```
XIII_MES()
```

### **XIII Mes con Condición:**
```
SI(ANTIGUEDAD >= 1, XIII_MES(), 0)
```

### **XIII Mes Reducido:**
```
XIII_MES() * 0.5
```

### **XIII Mes con Mínimo:**
```
SI(XIII_MES() < 100, 100, XIII_MES())
```

---

## 🔍 **Verificación del Cálculo**

### **Para Probar la Fórmula:**
1. Ir al concepto XIII_MES en la base de datos
2. Verificar que la fórmula sea: `XIII_MES()`
3. Procesar una planilla con el concepto
4. El sistema calculará automáticamente el monto

### **Log de Debugging:**
El sistema registra logs detallados en caso de errores:
```php
error_log("Error calculando XIII mes: " . $e->getMessage());
```

---

## 📋 **Integración con Planillas**

### **Procesamiento Automático:**
1. **Planilla se procesa** → Se evalúan todos los conceptos
2. **Concepto XIII_MES** → Se ejecuta fórmula `XIII_MES()`
3. **Función XIII_MES()** → Calcula según legislación panameña
4. **Resultado** → Se guarda en planilla_detalle

### **Campos en planilla_detalle:**
- `concepto_id`: 18 (ID del concepto XIII_MES)
- `monto`: Resultado del cálculo
- `tipo`: 'A' (Asignación/Ingreso)

---

## ⚠️ **Notas Importantes**

1. **La función es automática** - No requiere parámetros
2. **Usa el año actual** - Calcula enero-diciembre del año en curso
3. **Considera fecha de ingreso** - Aplica proporción si < 122 días
4. **Excluye conceptos duplicados** - No suma XIII mes previos
5. **Funciona con cualquier tipo de empresa** - Pública o privada

---

**✅ Fórmula lista para usar en el sistema de planillas**