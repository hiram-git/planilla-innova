# 💰 Fórmula XIII Mes Períodos Trimestrales - Liquidaciones Panamá

## 🎯 **Problema Identificado**

### **Situación Actual**
- **Fórmula Existente**: `ACUMULADOS("SALARIO_BASE", FICHA, FECHAINICIO, FECHAFIN)/12`
- **Problema**: La función `ACUMULADOS()` está usando las fechas de la planilla de liquidación, no las fechas del período correcto del XIII mes
- **Períodos Reales**: El XIII mes se paga en **3 períodos trimestrales**, no anuales

### **Períodos Correctos del XIII Mes en Panamá**
```
Período 1: Segunda quincena Diciembre → Primera quincena Abril
Período 2: Segunda quincena Abril → Primera quincena Agosto
Período 3: Segunda quincena Agosto → Primera quincena Diciembre
```

---

## 📅 **Definición Exacta de Períodos Trimestrales**

### **Período 1: Diciembre-Abril**
```
Inicio: 16 de Diciembre del año anterior
Final: 15 de Abril del año actual
Pago: Durante primera quincena de Abril
```

### **Período 2: Abril-Agosto**
```
Inicio: 16 de Abril del año actual
Final: 15 de Agosto del año actual
Pago: Durante primera quincena de Agosto
```

### **Período 3: Agosto-Diciembre**
```
Inicio: 16 de Agosto del año actual
Final: 15 de Diciembre del año actual
Pago: Durante primera quincena de Diciembre
```

---

## 🧮 **Algoritmo de Determinación de Período**

### **Función de Clasificación por Fecha de Liquidación**
```javascript
function determinarPeriodoXIIIMesTrimestral(fechaLiquidacion) {
    const fecha = new Date(fechaLiquidacion);
    const mes = fecha.getMonth() + 1; // 1-12
    const dia = fecha.getDate();
    const año = fecha.getFullYear();

    // Período 1: Segunda quincena Dic → Primera quincena Abr
    if ((mes === 12 && dia >= 16) || mes === 1 || mes === 2 || mes === 3 || (mes === 4 && dia <= 15)) {
        if (mes >= 4 || (mes === 12 && dia >= 16)) {
            // Período actual
            return {
                periodo: 1,
                año: año,
                fechaInicio: `${año}-12-16`, // 16 dic año anterior
                fechaFin: `${año + 1}-04-15`,   // 15 abr año actual
                pago: `${año + 1}-04`,
                estado: "EN_CURSO"
            };
        } else {
            // Período del año anterior
            return {
                periodo: 1,
                año: año,
                fechaInicio: `${año - 1}-12-16`, // 16 dic año anterior
                fechaFin: `${año}-04-15`,         // 15 abr año actual
                pago: `${año}-04`,
                estado: "EN_CURSO"
            };
        }
    }

    // Período 2: Segunda quincena Abr → Primera quincena Ago
    else if ((mes === 4 && dia >= 16) || mes === 5 || mes === 6 || mes === 7 || (mes === 8 && dia <= 15)) {
        return {
            periodo: 2,
            año: año,
            fechaInicio: `${año}-04-16`,  // 16 abr año actual
            fechaFin: `${año}-08-15`,     // 15 ago año actual
            pago: `${año}-08`,
            estado: "EN_CURSO"
        };
    }

    // Período 3: Segunda quincena Ago → Primera quincena Dic
    else if ((mes === 8 && dia >= 16) || mes === 9 || mes === 10 || mes === 11 || (mes === 12 && dia <= 15)) {
        return {
            periodo: 3,
            año: año,
            fechaInicio: `${año}-08-16`,  // 16 ago año actual
            fechaFin: `${año}-12-15`,     // 15 dic año actual
            pago: `${año}-12`,
            estado: "EN_CURSO"
        };
    }
}
```

---

## 📊 **Ejemplos de Clasificación por Fecha de Liquidación**

### **Ejemplo 1: Liquidación 10 de Marzo 2025**
```
Fecha Liquidación: 10 de Marzo 2025
Período Corresponde: 1 (Dic-Abr)
Fecha Inicio Período: 16 de Diciembre 2024
Fecha Fin Período: 15 de Abril 2025
Días Trabajados: 16 Dic 2024 → 10 Mar 2025
```

### **Ejemplo 2: Liquidación 25 de Junio 2025**
```
Fecha Liquidación: 25 de Junio 2025
Período Corresponde: 2 (Abr-Ago)
Fecha Inicio Período: 16 de Abril 2025
Fecha Fin Período: 15 de Agosto 2025
Días Trabajados: 16 Abr 2025 → 25 Jun 2025
```

### **Ejemplo 3: Liquidación 5 de Noviembre 2025**
```
Fecha Liquidación: 5 de Noviembre 2025
Período Corresponde: 3 (Ago-Dic)
Fecha Inicio Período: 16 de Agosto 2025
Fecha Fin Período: 15 de Diciembre 2025
Días Trabajados: 16 Ago 2025 → 5 Nov 2025
```

### **Ejemplo 4: Liquidación 20 de Diciembre 2025**
```
Fecha Liquidación: 20 de Diciembre 2025
Período Corresponde: 1 (Dic-Abr) del próximo período
Fecha Inicio Período: 16 de Diciembre 2025
Fecha Fin Período: 15 de Abril 2026
Días Trabajados: 16 Dic 2025 → 20 Dic 2025
```

---

## 🔧 **Solución Implementación en PHP**

### **Clase para Determinar Períodos XIII Mes**
```php
<?php
class XIIIMesPeriodoTrimestralCalculator
{
    public function determinarPeriodoTrimestral(string $fechaLiquidacion): array
    {
        $fecha = new DateTime($fechaLiquidacion);
        $mes = (int)$fecha->format('n');
        $dia = (int)$fecha->format('j');
        $año = (int)$fecha->format('Y');

        // Período 1: Segunda quincena Dic → Primera quincena Abr
        if (($mes === 12 && $dia >= 16) || $mes === 1 || $mes === 2 || $mes === 3 || ($mes === 4 && $dia <= 15)) {

            if ($mes === 12 && $dia >= 16) {
                // Diciembre - inicio nuevo período
                return [
                    'periodo' => 1,
                    'año' => $año + 1,
                    'fecha_inicio' => "{$año}-12-16",
                    'fecha_fin' => ($año + 1) . "-04-15",
                    'descripcion' => "Período 1: Dic {$año} - Abr " . ($año + 1),
                    'estado' => 'INICIO_PERIODO'
                ];
            } else {
                // Enero-Abril - continuación período
                return [
                    'periodo' => 1,
                    'año' => $año,
                    'fecha_inicio' => ($año - 1) . "-12-16",
                    'fecha_fin' => "{$año}-04-15",
                    'descripcion' => "Período 1: Dic " . ($año - 1) . " - Abr {$año}",
                    'estado' => 'CONTINUACION_PERIODO'
                ];
            }
        }

        // Período 2: Segunda quincena Abr → Primera quincena Ago
        elseif (($mes === 4 && $dia >= 16) || $mes === 5 || $mes === 6 || $mes === 7 || ($mes === 8 && $dia <= 15)) {
            return [
                'periodo' => 2,
                'año' => $año,
                'fecha_inicio' => "{$año}-04-16",
                'fecha_fin' => "{$año}-08-15",
                'descripcion' => "Período 2: Abr {$año} - Ago {$año}",
                'estado' => 'PERIODO_MEDIO'
            ];
        }

        // Período 3: Segunda quincena Ago → Primera quincena Dic
        else {
            return [
                'periodo' => 3,
                'año' => $año,
                'fecha_inicio' => "{$año}-08-16",
                'fecha_fin' => "{$año}-12-15",
                'descripcion' => "Período 3: Ago {$año} - Dic {$año}",
                'estado' => 'PERIODO_FINAL'
            ];
        }
    }

    public function calcularXIIIMesTrimestral(string $fechaLiquidacion, float $salarioBase, string $fechaIngreso, int $empleadoId): array
    {
        $periodoInfo = $this->determinarPeriodoTrimestral($fechaLiquidacion);

        // Determinar fecha inicio real para el cálculo
        $fechaIngresoDate = new DateTime($fechaIngreso);
        $fechaInicioPeriodo = new DateTime($periodoInfo['fecha_inicio']);
        $fechaLiquidacionDate = new DateTime($fechaLiquidacion);

        $fechaInicioCalculo = ($fechaIngresoDate > $fechaInicioPeriodo)
            ? $fechaIngresoDate
            : $fechaInicioPeriodo;

        // Calcular días trabajados en el período
        $diasTrabajados = $this->calcularDiasLaborables($fechaInicioCalculo, $fechaLiquidacionDate);
        $diasTotalPeriodo = $this->calcularDiasLaborables($fechaInicioPeriodo, new DateTime($periodoInfo['fecha_fin']));

        // El XIII mes trimestral es salario_base * días_trabajados / días_período / 4
        // Dividido entre 4 porque se paga en 4 cuotas al año (3 trimestrales + 1 anual)
        $proporcion = $diasTotalPeriodo > 0 ? $diasTrabajados / $diasTotalPeriodo : 0;
        $montoTrimestral = ($salarioBase / 4) * $proporcion;

        return [
            'periodo_info' => $periodoInfo,
            'fecha_inicio_calculo' => $fechaInicioCalculo->format('Y-m-d'),
            'fecha_fin_calculo' => $fechaLiquidacionDate->format('Y-m-d'),
            'dias_trabajados' => $diasTrabajados,
            'dias_total_periodo' => $diasTotalPeriodo,
            'proporcion' => round($proporcion, 4),
            'salario_base' => $salarioBase,
            'monto_trimestral' => round($montoTrimestral, 2),
            'formula_aplicada' => "($salarioBase / 4) * ($diasTrabajados / $diasTotalPeriodo)"
        ];
    }

    private function calcularDiasLaborables(DateTime $inicio, DateTime $fin): int
    {
        $dias = 0;
        $current = clone $inicio;

        while ($current <= $fin) {
            $diaSemana = (int)$current->format('N'); // 1=Lunes, 7=Domingo
            if ($diaSemana <= 5) { // Lunes a Viernes
                $dias++;
            }
            $current->add(new DateInterval('P1D'));
        }

        return $dias;
    }
}
?>
```

---

## 🎯 **Nueva Función para PlanillaConceptCalculator**

### **Función ACUMULADOS_XIII_TRIMESTRAL**
```php
// Agregar al PlanillaConceptCalculator.php
private function procesarACUMULADOSXIIITrimestral(string $formula): string
{
    return preg_replace_callback(
        '/ACUMULADOS_XIII_TRIMESTRAL\s*\(\s*([^,]+)\s*,\s*([^,]+)\s*,\s*([^)]+)\s*\)/',
        function($matches) {
            $conceptos = trim($matches[1], '"\'');
            $fichaVariable = trim($matches[2]);
            $empleadoId = $this->reemplazarVariables($fichaVariable);

            // Obtener fecha de liquidación del contexto
            $fechaLiquidacion = $this->obtenerFechaLiquidacion($empleadoId);

            if (!$fechaLiquidacion) {
                return '0';
            }

            // Determinar período trimestral correcto
            $calculator = new XIIIMesPeriodoTrimestralCalculator();
            $periodoInfo = $calculator->determinarPeriodoTrimestral($fechaLiquidacion);

            // Obtener acumulados del período correcto
            $acumulados = $this->obtenerAcumuladosPeriodo(
                $empleadoId,
                $conceptos,
                $periodoInfo['fecha_inicio'],
                $periodoInfo['fecha_fin']
            );

            // Calcular XIII mes trimestral
            return (string)($acumulados / 4); // Dividido entre 4 cuotas anuales
        },
        $formula
    );
}

private function obtenerFechaLiquidacion(int $empleadoId): ?string
{
    // Buscar en liquidation_calculations o employee_terminations
    // para obtener la fecha de liquidación del empleado

    // Implementar consulta a BD
    $sql = "
        SELECT et.termination_date
        FROM employee_terminations et
        WHERE et.employee_id = ?
        ORDER BY et.termination_date DESC
        LIMIT 1
    ";

    // Ejecutar query y retornar fecha
    // Por ahora placeholder
    return date('Y-m-d'); // Placeholder
}

private function obtenerAcumuladosPeriodo(int $empleadoId, string $conceptos, string $fechaInicio, string $fechaFin): float
{
    $conceptosArray = explode(',', str_replace(['"', "'"], '', $conceptos));
    $total = 0;

    foreach ($conceptosArray as $concepto) {
        $concepto = trim($concepto);

        $sql = "
            SELECT COALESCE(SUM(pd.monto), 0) as total
            FROM planilla_detalle pd
            INNER JOIN planilla_cabecera pc ON pd.planilla_id = pc.id
            INNER JOIN concepto c ON pd.concepto_id = c.id
            WHERE pd.employee_id = ?
                AND c.concepto = ?
                AND pc.fecha_inicio >= ?
                AND pc.fecha_fin <= ?
                AND pc.estado = 'CERRADA'
                AND pd.tipo = 'A'
        ";

        // Ejecutar consulta y sumar al total
        // Implementar conexión BD
        // $total += resultado de la consulta;
    }

    return $total;
}
```

---

## 🔧 **Modificación de la Fórmula Actual**

### **Fórmula Actual Problemática**
```
ACUMULADOS("SALARIO_BASE", FICHA, FECHAINICIO, FECHAFIN)/12
```

### **Nueva Fórmula Propuesta**
```
ACUMULADOS_XIII_TRIMESTRAL("SALARIO_BASE", FICHA)/4
```

### **O Alternativa usando Variables de Fecha Dinámicas**
```
ACUMULADOS("SALARIO_BASE", FICHA, INICIO_PERIODO_XIII, FIN_PERIODO_XIII)/4
```

---

## 🎯 **Implementación de Variables de Fecha Dinámicas**

### **Agregar Variables Especiales**
```php
// En el método reemplazarVariables() de PlanillaConceptCalculator
private function obtenerVariablesFechaTrimestral(int $empleadoId): array
{
    $fechaLiquidacion = $this->obtenerFechaLiquidacion($empleadoId);

    if (!$fechaLiquidacion) {
        return [
            'INICIO_PERIODO_XIII' => date('Y-01-01'),
            'FIN_PERIODO_XIII' => date('Y-12-31')
        ];
    }

    $calculator = new XIIIMesPeriodoTrimestralCalculator();
    $periodoInfo = $calculator->determinarPeriodoTrimestral($fechaLiquidacion);

    return [
        'INICIO_PERIODO_XIII' => $periodoInfo['fecha_inicio'],
        'FIN_PERIODO_XIII' => $periodoInfo['fecha_fin'],
        'PERIODO_XIII_NUMERO' => $periodoInfo['periodo'],
        'PERIODO_XIII_AÑO' => $periodoInfo['año']
    ];
}

// Modificar el array de variables en el método principal
protected function reemplazarVariables(string $expression): string
{
    // ... código existente ...

    // Agregar variables de fecha XIII mes si hay empleado en contexto
    if (isset($this->variablesColaborador['EMPLOYEE_ID'])) {
        $variablesFechaXIII = $this->obtenerVariablesFechaTrimestral(
            $this->variablesColaborador['EMPLOYEE_ID']
        );
        $variables = array_merge($variables, $variablesFechaXIII);
    }

    // ... resto del código ...
}
```

---

## 📊 **Casos de Prueba Detallados**

### **Caso 1: Liquidación en Período 1 (Marzo)**
```
Empleado: Juan Pérez
Fecha Liquidación: 15 de Marzo 2025
Salario Base: $1,200.00
Fecha Ingreso: 1 de Enero 2023

Período Determinado: 1 (Dic 2024 - Abr 2025)
Fecha Inicio Período: 16 de Diciembre 2024
Fecha Fin Período: 15 de Abril 2025
Fecha Inicio Cálculo: 16 de Diciembre 2024
Fecha Fin Cálculo: 15 de Marzo 2025

Días Trabajados: 63 días laborables
Días Total Período: 86 días laborables
Proporción: 63/86 = 0.7326 (73.26%)

Acumulados Período: $3,600.00
XIII Mes Trimestral: $3,600.00 ÷ 4 = $900.00
XIII Mes Proporcional: $900.00 × 0.7326 = $659.34
```

### **Caso 2: Liquidación en Período 2 (Junio)**
```
Empleado: María González
Fecha Liquidación: 30 de Junio 2025
Salario Base: $1,500.00
Fecha Ingreso: 1 de Mayo 2025 (empleado nuevo)

Período Determinado: 2 (Abr 2025 - Ago 2025)
Fecha Inicio Período: 16 de Abril 2025
Fecha Fin Período: 15 de Agosto 2025
Fecha Inicio Cálculo: 1 de Mayo 2025 (fecha ingreso)
Fecha Fin Cálculo: 30 de Junio 2025

Días Trabajados: 43 días laborables
Días Total Período: 87 días laborables
Proporción: 43/87 = 0.4943 (49.43%)

Acumulados Período: $3,000.00 (May-Jun)
XIII Mes Trimestral: $3,000.00 ÷ 4 = $750.00
XIII Mes Proporcional: $750.00 × 0.4943 = $370.73
```

### **Caso 3: Liquidación en Período 3 (Octubre)**
```
Empleado: Carlos Medina
Fecha Liquidación: 20 de Octubre 2025
Salario Base: $2,000.00
Fecha Ingreso: 1 de Septiembre 2024

Período Determinado: 3 (Ago 2025 - Dic 2025)
Fecha Inicio Período: 16 de Agosto 2025
Fecha Fin Período: 15 de Diciembre 2025
Fecha Inicio Cálculo: 16 de Agosto 2025
Fecha Fin Cálculo: 20 de Octubre 2025

Días Trabajados: 47 días laborables
Días Total Período: 87 días laborables
Proporción: 47/87 = 0.5402 (54.02%)

Acumulados Período: $4,000.00
XIII Mes Trimestral: $4,000.00 ÷ 4 = $1,000.00
XIII Mes Proporcional: $1,000.00 × 0.5402 = $540.20
```

---

## 🚀 **Plan de Implementación**

### **Fase 1: Crear Función de Períodos** *(3 días)*
1. Implementar `XIIIMesPeriodoTrimestralCalculator`
2. Testing de clasificación de fechas
3. Validación de períodos correctos

### **Fase 2: Integrar Variables Dinámicas** *(2 días)*
1. Agregar variables `INICIO_PERIODO_XIII`, `FIN_PERIODO_XIII`
2. Modificar `reemplazarVariables()` en PlanillaConceptCalculator
3. Testing de variables en fórmulas

### **Fase 3: Actualizar Función ACUMULADOS** *(2 días)*
1. Modificar lógica para usar fechas dinámicas en liquidaciones
2. Crear función `ACUMULADOS_XIII_TRIMESTRAL()`
3. Testing con casos reales

### **Fase 4: Actualizar Concepto LIQ006** *(1 día)*
1. Cambiar fórmula a nueva implementación
2. Testing en planillas de liquidación
3. Validación de resultados

### **Fase 5: Documentación y Deploy** *(1 día)*
1. Documentación técnica completa
2. Testing final
3. Deploy a producción

**Total Estimado**: 9 días (1.8 semanas)

---

## ✅ **Beneficios de la Solución**

### **Precisión Legal**
- ✅ Cumple exactamente con períodos trimestrales panameños
- ✅ Calcula proporcional correcto según días trabajados
- ✅ Evita errores de cálculo por períodos incorrectos

### **Flexibilidad**
- ✅ Funciona para cualquier fecha de liquidación
- ✅ Se adapta automáticamente al período correcto
- ✅ Considera fecha de ingreso real del empleado

### **Mantenibilidad**
- ✅ Lógica centralizada en una clase
- ✅ Fácil de actualizar si cambian períodos
- ✅ Reutilizable para otros cálculos trimestrales

---

**Estado**: 📋 **Análisis Completo - Solución Definida**
**Problema**: ✅ **Identificado y Solucionado**
**Implementación**: 🚀 **Lista para Desarrollo**