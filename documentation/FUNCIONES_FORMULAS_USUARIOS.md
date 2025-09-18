# 📚 Funciones de Fórmulas para Usuarios - Sistema de Planillas

## 🎯 **Fórmula del XIII Mes Explicada**

### **Fórmula Completa (Simplificada):**
```
dias_trabajados = ANTIGUEDAD(FICHA, "FINPERIODO", "D")
acumulados = ACUMULADOS("SALARIO,HORAS_EXTRAS,COMISIONES,BONIFICACIONES", FICHA, "INIPERIODO", "FINPERIODO")
SI(dias_trabajados >= 122, acumulados/12, (acumulados/12)*(dias_trabajados/122))
```

### **Explicación Línea por Línea:**

**Línea 1:**
```
dias_trabajados = ANTIGUEDAD(FICHA, 'FINPERIODO', 'D')
```
- **¿Qué hace?** Calcula cuántos días ha trabajado el empleado desde su ingreso hasta el final del periodo
- **FICHA** = Código del empleado
- **'FINPERIODO'** = Hasta el 31 de diciembre del año actual
- **'D'** = Resultado en días

**Línea 2:**
```
acumulados = ACUMULADOS("SALARIO,HORAS_EXTRAS,COMISIONES,BONIFICACIONES", FICHA, "INIPERIODO", "FINPERIODO")
```
- **¿Qué hace?** Suma todos los ingresos del empleado en el año en una sola función
- **"SALARIO,HORAS_EXTRAS,COMISIONES,BONIFICACIONES"** = Lista de conceptos separados por comas
- **FICHA** = Código del empleado
- **"INIPERIODO"** = Desde el 1 de enero del año actual
- **"FINPERIODO"** = Hasta el 31 de diciembre del año actual

**Línea 3:**
```
SI(dias_trabajados >= 122, acumulados/12, (acumulados/12)*(dias_trabajados/122))
```
- **¿Qué hace?** Aplica la ley panameña del XIII mes
- **SI trabajó 122 días o más** = XIII mes completo (acumulados ÷ 12)
- **SI trabajó menos de 122 días** = XIII mes proporcional

---

## 🛠️ **Funciones Disponibles**

### **1. ANTIGUEDAD(empleado, fecha_final, tipo)**

**¿Para qué sirve?** Calcular cuánto tiempo ha trabajado un empleado

**Parámetros:**
- **empleado**: `FICHA` (código del empleado)
- **fecha_final**: `'FINPERIODO'` o fecha específica `'2024-12-31'`
- **tipo**: `'D'` (días), `'M'` (meses), `'A'` (años)

**Ejemplos:**
```
ANTIGUEDAD(FICHA, 'FINPERIODO', 'D')     // Días trabajados este año
ANTIGUEDAD(FICHA, 'FINPERIODO', 'M')     // Meses trabajados este año
ANTIGUEDAD(FICHA, '2024-06-30', 'D')     // Días hasta junio 30
```

### **2. ACUMULADOS(conceptos, empleado, fecha_inicio, fecha_fin)**

**¿Para qué sirve?** Sumar cuánto dinero ha recibido un empleado por uno o múltiples conceptos

**Parámetros:**
- **conceptos**: Uno o múltiples tipos de pago separados por comas
- **empleado**: `FICHA` (código del empleado)
- **fecha_inicio**: `"INIPERIODO"` o fecha específica `"2024-01-01"`
- **fecha_fin**: `"FINPERIODO"` o fecha específica `"2024-12-31"`

**Conceptos Disponibles:**
- `"SALARIO"` - Salario base
- `"HORAS_EXTRAS"` - Pago por horas extras
- `"COMISIONES"` - Comisiones ganadas
- `"BONIFICACIONES"` - Bonos y bonificaciones
- `"AGUINALDO"` - XIII mes recibido
- `"VACACIONES"` - Pago de vacaciones

**Ejemplos:**
```
ACUMULADOS("SALARIO", FICHA, "INIPERIODO", "FINPERIODO")                          // Solo salarios del año
ACUMULADOS("SALARIO,HORAS_EXTRAS", FICHA, "INIPERIODO", "FINPERIODO")            // Salarios + horas extras
ACUMULADOS("SALARIO,HORAS_EXTRAS,COMISIONES,BONIFICACIONES", FICHA, "INIPERIODO", "FINPERIODO")  // Todos los ingresos
ACUMULADOS("HORAS_EXTRAS", FICHA, "2024-01-01", "2024-06-30")                   // Horas extras enero-junio
```

**🆕 NUEVA FUNCIONALIDAD:**
- Ahora puedes sumar múltiples conceptos en una sola función usando comas
- **Antes:** `ACUMULADOS('SALARIO', ...) + ACUMULADOS('HORAS_EXTRAS', ...)`
- **Ahora:** `ACUMULADOS("SALARIO,HORAS_EXTRAS", ...)`
- **Usa comillas dobles** para evitar problemas de sintaxis

### **3. SI(condición, valor_si_verdadero, valor_si_falso)**

**¿Para qué sirve?** Tomar decisiones en las fórmulas

**Ejemplos:**
```
SI(ANTIGUEDAD(FICHA, 'FINPERIODO', 'A') >= 1, 500, 0)          // Bono si tiene 1+ años
SI(ACUMULADOS('SALARIO', FICHA, 'INIPERIODO', 'FINPERIODO') > 10000, 1000, 500)  // Bono según salario
```

### **4. ACREEDOR(empleado, id_acreedor)**

**¿Para qué sirve?** Obtener cuánto debe pagar el empleado a un acreedor

**Ejemplos:**
```
ACREEDOR(FICHA, 1)    // Descuento del acreedor ID 1
ACREEDOR(FICHA, 5)    // Descuento del acreedor ID 5
```

---

## 📋 **Variables del Sistema**

### **Variables del Empleado:**
- **FICHA** = Código del empleado (campo `employee_id` de tabla `employees`)
- **SALARIO** = Salario base actual
- **HORAS** = Horas semanales de trabajo
- **ANTIGUEDAD** = Años trabajados en la empresa

### **Variables de Fechas (Mapeo a BD):**
- **"INIPERIODO"** = Campo `fecha_desde` de `planilla_cabecera`
- **"FINPERIODO"** = Campo `fecha_hasta` de `planilla_cabecera`
- **"FECHA"** = Campo `fecha` de `planilla_cabecera`

### **Conceptos de Nómina:**
Los parámetros como `"SALARIO,HORAS_EXTRAS,COMISIONES,BONIFICACIONES"` corresponden al campo `concepto` de la tabla `concepto`, agrupados y separados por comas.

---

## 💡 **Ejemplos Prácticos**

### **Bono por Antigüedad:**
```
SI(ANTIGUEDAD(FICHA, 'FINPERIODO', 'A') >= 5, SALARIO * 0.1, 0)
```
*Si tiene 5+ años, recibe 10% del salario como bono*

### **Prima de Productividad:**
```
total_ingresos = ACUMULADOS('SALARIO', FICHA, 'INIPERIODO', 'FINPERIODO') + ACUMULADOS('COMISIONES', FICHA, 'INIPERIODO', 'FINPERIODO')
SI(total_ingresos > 15000, total_ingresos * 0.05, 0)
```
*Si ganó más de $15,000 en el año, recibe 5% extra*

### **Bono de Navidad:**
```
dias_diciembre = ANTIGUEDAD(FICHA, '2024-12-31', 'D') - ANTIGUEDAD(FICHA, '2024-11-30', 'D')
SI(dias_diciembre >= 30, 500, dias_diciembre * 16.67)
```
*Bono proporcional por días trabajados en diciembre*

### **Descuento por Préstamo:**
```
prestamo = ACREEDOR(FICHA, 1)
SI(prestamo > 0, prestamo, 0)
```
*Descontar préstamo del acreedor 1 si existe*

---

## 🔧 **Operadores Matemáticos**

- **+** = Suma
- **-** = Resta
- ***** = Multiplicación
- **/** = División
- **()** = Paréntesis para agrupar operaciones

### **Ejemplos:**
```
total = SALARIO + (HORAS * 5.5)                    // Salario + horas extras
promedio = (valor1 + valor2 + valor3) / 3          // Promedio de tres valores
porcentaje = total * 0.15                          // 15% del total
```

---

## ⚠️ **Reglas Importantes**

1. **Los nombres de conceptos van entre comillas dobles:** `"SALARIO"`, `"HORAS_EXTRAS"`
2. **Las fechas van entre comillas dobles:** `"2024-12-31"`, `"FINPERIODO"`
3. **Los números NO van entre comillas:** `122`, `12`, `0.15`
4. **Usar = para asignar variables:** `total = SALARIO * 12`
5. **La última línea es el resultado final**
6. **🆕 Para múltiples conceptos:** Sepáralos por comas dentro de las comillas: `"SALARIO,HORAS_EXTRAS"`

---

## 📞 **Soporte**

Si necesitas ayuda con fórmulas:
1. Revisa estos ejemplos
2. Prueba con fórmulas simples primero
3. Agrega complejidad gradualmente
4. Verifica que los nombres de conceptos existan en tu sistema

**✅ Sistema de fórmulas listo para usar por usuarios no técnicos**