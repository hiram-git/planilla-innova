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

## ⏰ **Funciones de Asistencias**

### **Descripción General**

Las funciones de asistencias permiten calcular bonos y descuentos basados en la puntualidad, asistencia perfecta, horas trabajadas, tardanzas y ausencias de los empleados. Estas funciones consultan automáticamente los datos del sistema de marcaciones del período de la planilla.

**🔍 Importante:**
- Todas las funciones retornan **0** si no hay datos de asistencia para el empleado
- Los datos se consultan automáticamente del período de la planilla
- No requieren parámetros de fecha (usan las fechas de la planilla)

---

### **5. Funciones de Horas**

#### **5.1. HORAS_TRABAJADAS()**

**¿Para qué sirve?** Obtener el total de horas trabajadas por el empleado en el período

**Retorna:** Número decimal de horas (ej: 176.5)

**Ejemplos:**
```
HORAS_TRABAJADAS()                                    // Total de horas trabajadas
HORAS_TRABAJADAS() * 5                                // Bono de $5 por hora trabajada
SI(HORAS_TRABAJADAS() >= 176, 200, 0)                 // Bono si cumplió jornada completa
```

#### **5.2. HORAS_REGULARES()**

**¿Para qué sirve?** Obtener solo las horas regulares (sin horas extras) del período

**Retorna:** Número decimal de horas regulares

**Ejemplos:**
```
HORAS_REGULARES()                                     // Horas regulares trabajadas
HORAS_REGULARES() * (SALARIO / 220)                   // Calcular pago por horas regulares
```

#### **5.3. HORAS_EXTRAS()**

**¿Para qué sirve?** Obtener el total de horas extras (suma de 25% + 50%)

**Retorna:** Número decimal de horas extras totales

**Ejemplos:**
```
HORAS_EXTRAS()                                        // Total de horas extras
HORAS_EXTRAS() * (SALARIO / 220) * 1.5                // Pago horas extras promedio
SI(HORAS_EXTRAS() > 0, 50, 0)                         // Bono por trabajar horas extras
```

#### **5.4. HORAS_EXTRAS_25()**

**¿Para qué sirve?** Obtener horas extras al 25% (primeras 3 horas según ley panameña)

**Retorna:** Número decimal de horas extras al 25%

**Ejemplos:**
```
HORAS_EXTRAS_25()                                     // Horas extras al 25%
HORAS_EXTRAS_25() * (SALARIO / 220) * 1.25            // Pago horas extras 25%
```

#### **5.5. HORAS_EXTRAS_50()**

**¿Para qué sirve?** Obtener horas extras al 50% (después de las primeras 3 horas)

**Retorna:** Número decimal de horas extras al 50%

**Ejemplos:**
```
HORAS_EXTRAS_50()                                     // Horas extras al 50%
HORAS_EXTRAS_50() * (SALARIO / 220) * 1.5             // Pago horas extras 50%
```

#### **5.6. HORAS_NOCTURNAS()**

**¿Para qué sirve?** Obtener horas trabajadas en jornada nocturna (6PM-6AM)

**Retorna:** Número decimal de horas nocturnas

**Ejemplos:**
```
HORAS_NOCTURNAS()                                     // Horas en jornada nocturna
HORAS_NOCTURNAS() * (SALARIO / 220) * 1.5             // Recargo nocturno 50%
SI(HORAS_NOCTURNAS() > 0, 100, 0)                     // Bono por trabajo nocturno
```

#### **5.7. HORAS_FERIADOS()**

**¿Para qué sirve?** Obtener horas trabajadas en días feriados

**Retorna:** Número decimal de horas en feriados

**Ejemplos:**
```
HORAS_FERIADOS()                                      // Horas trabajadas en feriados
HORAS_FERIADOS() * (SALARIO / 220) * 1.5              // Recargo feriado 50%
SI(HORAS_FERIADOS() > 0, 150, 0)                      // Bono fijo por trabajar feriado
```

#### **5.8. HORAS_DOMINICALES()**

**¿Para qué sirve?** Obtener horas trabajadas en domingos

**Retorna:** Número decimal de horas dominicales

**Ejemplos:**
```
HORAS_DOMINICALES()                                   // Horas trabajadas en domingo
HORAS_DOMINICALES() * (SALARIO / 220) * 1.5           // Recargo dominical 50%
SI(HORAS_DOMINICALES() >= 8, 200, 0)                  // Bono por domingo completo
```

---

### **6. Funciones de Ausencias y Tardanzas**

#### **6.1. TARDANZAS()**

**¿Para qué sirve?** Obtener el total de minutos de tardanzas acumulados

**Retorna:** Número entero de minutos totales de tardanzas

**Ejemplos:**
```
TARDANZAS()                                           // Minutos totales de tardanzas
TARDANZAS() / 60 * (SALARIO / 220)                    // Descuento por tardanzas
SI(TARDANZAS() > 60, TARDANZAS() * 0.5, 0)            // Descuento si tardanzas > 1 hora
```

#### **6.2. CANTIDAD_TARDANZAS()**

**¿Para qué sirve?** Obtener el número de veces que llegó tarde

**Retorna:** Número entero de días con tardanza

**Ejemplos:**
```
CANTIDAD_TARDANZAS()                                  // Número de veces tarde
CANTIDAD_TARDANZAS() * 10                             // Descuento $10 por cada tardanza
SI(CANTIDAD_TARDANZAS() >= 3, 50, 0)                  // Descuento si 3+ tardanzas
```

#### **6.3. AUSENCIAS()**

**¿Para qué sirve?** Obtener el total de horas de ausencias (justificadas + no justificadas)

**Retorna:** Número decimal de horas de ausencias

**Ejemplos:**
```
AUSENCIAS()                                           // Total horas ausentes
AUSENCIAS() * (SALARIO / 220)                         // Descuento por ausencias
SI(AUSENCIAS() > 8, AUSENCIAS() * 2, 0)               // Descuento doble si > 1 día
```

#### **6.4. TOTAL_AUSENCIAS()**

**¿Para qué sirve?** Obtener el número de días completos de ausencias

**Retorna:** Número entero de días ausentes

**Ejemplos:**
```
TOTAL_AUSENCIAS()                                     // Días totales ausentes
TOTAL_AUSENCIAS() * (SALARIO / 30)                    // Descuento días ausentes
SI(TOTAL_AUSENCIAS() >= 3, 100, 0)                    // Penalización 3+ ausencias
```

#### **6.5. AUSENCIAS_JUSTIFICADAS()**

**¿Para qué sirve?** Obtener el número de días de ausencias justificadas

**Retorna:** Número entero de días justificados

**Ejemplos:**
```
AUSENCIAS_JUSTIFICADAS()                              // Días con justificación
TOTAL_AUSENCIAS() - AUSENCIAS_JUSTIFICADAS()          // Días sin justificar
SI(AUSENCIAS_JUSTIFICADAS() > 2, 0, 50)               // Bono si < 3 ausencias justif.
```

---

### **7. Funciones de Estadísticas**

#### **7.1. DIAS_ASISTENCIA_PERFECTA()**

**¿Para qué sirve?** Obtener el número de días con asistencia perfecta (puntual, sin tardanzas)

**Retorna:** Número entero de días con asistencia perfecta

**Ejemplos:**
```
DIAS_ASISTENCIA_PERFECTA()                            // Días con asistencia perfecta
DIAS_ASISTENCIA_PERFECTA() * 5                        // Bono $5 por día perfecto
SI(DIAS_ASISTENCIA_PERFECTA() >= 20, 150, 0)          // Bono si 20+ días perfectos
```

#### **7.2. SCORE_PUNTUALIDAD()**

**¿Para qué sirve?** Obtener el score de puntualidad (0-100) del empleado

**Retorna:** Número decimal entre 0 y 100

**Cálculo:**
- 100 = Asistencia perfecta todo el mes
- Se descuenta por cada tardanza y ausencia
- Promedio ponderado del período

**Ejemplos:**
```
SCORE_PUNTUALIDAD()                                   // Score 0-100
SI(SCORE_PUNTUALIDAD() >= 95, 200, 0)                 // Bono si score >= 95
SI(SCORE_PUNTUALIDAD() >= 90, 150, SI(SCORE_PUNTUALIDAD() >= 85, 100, 0))  // Bono escalonado
SCORE_PUNTUALIDAD() * 2                               // Bono proporcional al score
```

#### **7.3. DIAS_TRABAJADOS()**

**¿Para qué sirve?** Obtener el número total de días trabajados en el período

**Retorna:** Número entero de días trabajados

**Ejemplos:**
```
DIAS_TRABAJADOS()                                     // Días que asistió a trabajar
DIAS_TRABAJADOS() * 3                                 // Bono $3 por día trabajado
SI(DIAS_TRABAJADOS() >= 22, 100, 0)                   // Bono si trabajó mes completo
```

---

## 💰 **Ejemplos de Bonos de Asistencia**

### **Ejemplo 1: Bono por Asistencia Perfecta**
```
// Bono de $10 por cada día con asistencia perfecta
DIAS_ASISTENCIA_PERFECTA() * 10
```

### **Ejemplo 2: Bono Escalonado por Puntualidad**
```
// Bono según score de puntualidad
SI(SCORE_PUNTUALIDAD() >= 95, 200,
   SI(SCORE_PUNTUALIDAD() >= 90, 150,
      SI(SCORE_PUNTUALIDAD() >= 85, 100,
         SI(SCORE_PUNTUALIDAD() >= 80, 50, 0))))
```

### **Ejemplo 3: Bono por Cero Tardanzas**
```
// Bono de $100 si no tuvo ninguna tardanza
SI(CANTIDAD_TARDANZAS() = 0, 100, 0)
```

### **Ejemplo 4: Bono Combinado (Sin Ausencias + Alta Puntualidad)**
```
// Bono de $250 si no faltó y tiene score >= 95
SI(TOTAL_AUSENCIAS() = 0 && SCORE_PUNTUALIDAD() >= 95, 250, 0)
```

### **Ejemplo 5: Bono Proporcional por Días Perfectos**
```
// 5% del salario por cada 5 días perfectos
dias_perfectos = DIAS_ASISTENCIA_PERFECTA()
SI(dias_perfectos >= 5, (dias_perfectos / 5) * (SALARIO * 0.05), 0)
```

### **Ejemplo 6: Descuento por Tardanzas**
```
// Descuento proporcional: $1 por cada 2 minutos de tardanza
TARDANZAS() * 0.5
```

### **Ejemplo 7: Descuento Progresivo por Ausencias**
```
// Descuento por ausencias no justificadas
ausencias_sin_justificar = TOTAL_AUSENCIAS() - AUSENCIAS_JUSTIFICADAS()
ausencias_sin_justificar * (SALARIO / 30) * 1.5
```

### **Ejemplo 8: Bono Completo de Asistencia (Recomendado)**
```
// Bono que combina múltiples criterios
score = SCORE_PUNTUALIDAD()
dias_perfectos = DIAS_ASISTENCIA_PERFECTA()
ausencias = TOTAL_AUSENCIAS()

// Base: Score de puntualidad (máximo $150)
bono_score = SI(score >= 95, 150, SI(score >= 90, 100, SI(score >= 85, 50, 0)))

// Adicional: $5 por día perfecto
bono_dias = dias_perfectos * 5

// Penalización: -$30 por cada ausencia no justificada
penalizacion = (ausencias - AUSENCIAS_JUSTIFICADAS()) * 30

// Total (mínimo 0)
total = bono_score + bono_dias - penalizacion
SI(total > 0, total, 0)
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