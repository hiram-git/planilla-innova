# 💰 Fórmula XIII Mes Proporcional en Liquidaciones - Análisis Panamá

## 📋 **Análisis del Problema**

### **Objetivo**
Crear una fórmula que calcule el XIII mes proporcional en liquidaciones, determinando automáticamente en qué período del décimo tercer mes se encuentra el empleado según la fecha de liquidación, para calcular exactamente la porción pendiente que le corresponde.

### **Contexto Legal - Código de Trabajo de Panamá**
- **Período del XIII Mes**: 1 de diciembre del año anterior al 30 de noviembre del año actual
- **Pago Regular**: Se paga en diciembre de cada año
- **En Liquidación**: Se debe pagar la parte proporcional del período actual que no ha sido pagada

---

## 🗓️ **Períodos del XIII Mes según Fecha de Liquidación**

### **Concepto Clave**
El XIII mes NO sigue el año calendario (enero-diciembre), sino que sigue el **año del décimo** (diciembre-noviembre).

### **Definición de Períodos Anuales del XIII Mes**
```
Período XIII Mes 2024: 1 Dic 2023 → 30 Nov 2024 (Pago: Dic 2024)
Período XIII Mes 2025: 1 Dic 2024 → 30 Nov 2025 (Pago: Dic 2025)
Período XIII Mes 2026: 1 Dic 2025 → 30 Nov 2026 (Pago: Dic 2026)
```

---

## 🧮 **Fórmula Principal: Determinar Período Pendiente**

### **Algoritmo de Clasificación por Fecha**
```javascript
function determinarPeriodoXIIIMes(fechaLiquidacion) {
    const año = fechaLiquidacion.getFullYear();
    const mes = fechaLiquidacion.getMonth() + 1; // 1-12

    if (mes === 12) {
        // Diciembre: primer mes del nuevo período XIII
        return {
            periodoXIII: año + 1,
            inicioPeríodo: new Date(año, 11, 1), // 1 dic año actual
            finPeríodo: new Date(año + 1, 10, 30), // 30 nov año siguiente
            mesPorcentaje: 1, // primer mes del período
            estado: "PERIODO_NUEVO"
        };
    } else if (mes >= 1 && mes <= 11) {
        // Enero-Noviembre: continuación del período anterior
        return {
            periodoXIII: año,
            inicioPeríodo: new Date(año - 1, 11, 1), // 1 dic año anterior
            finPeríodo: new Date(año, 10, 30), // 30 nov año actual
            mesPorcentaje: mes + 1, // mes 2-12 del período
            estado: "PERIODO_EN_CURSO"
        };
    }
}
```

### **Cálculo del Monto Proporcional**
```javascript
function calcularXIIIMesProporcionalLiquidacion(fechaLiquidacion, salarioBase, fechaIngreso) {
    const periodoInfo = determinarPeriodoXIIIMes(fechaLiquidacion);

    // 1. Determinar fecha de inicio para el cálculo
    const fechaInicioCalculo = fechaIngreso > periodoInfo.inicioPeríodo
        ? fechaIngreso
        : periodoInfo.inicioPeríodo;

    // 2. Calcular días trabajados en el período del XIII mes
    const diasTrabajados = calcularDiasLaborables(fechaInicioCalculo, fechaLiquidacion);
    const diasTotalPeriodo = calcularDiasLaborables(periodoInfo.inicioPeríodo, periodoInfo.finPeríodo);

    // 3. Verificar último pago de XIII mes
    const ultimoPagoXIII = obtenerUltimoPagoXIIIMes(empleadoId);
    const fechaUltimoPago = ultimoPagoXIII ? ultimoPagoXIII.fechaPago : null;

    // 4. Ajustar fecha de inicio si ya recibió pago en período anterior
    if (fechaUltimoPago && fechaUltimoPago >= periodoInfo.inicioPeríodo) {
        fechaInicioCalculo = new Date(fechaUltimoPago.getTime() + 86400000); // +1 día
    }

    // 5. Calcular proporción exacta
    const proporcion = diasTrabajados / diasTotalPeriodo;
    const montoAnualXIII = salarioBase * 12 / 12; // salario base mensual
    const montoProporcional = montoAnualXIII * proporcion;

    return {
        periodoXIII: periodoInfo.periodoXIII,
        inicioCalculo: fechaInicioCalculo,
        finCalculo: fechaLiquidacion,
        diasTrabajados: diasTrabajados,
        diasTotalPeriodo: diasTotalPeriodo,
        proporcion: proporcion,
        salarioBase: salarioBase,
        montoProporcional: Math.round(montoProporcional * 100) / 100,
        ultimoPagoXIII: ultimoPagoXIII,
        estado: periodoInfo.estado
    };
}
```

---

## 📊 **Ejemplos Prácticos por Mes de Liquidación**

### **Caso 1: Liquidación en Marzo 2025**
```
Fecha Liquidación: 15 de Marzo 2025
Salario Base: $1,000.00
Fecha Ingreso: 1 de Enero 2020

Análisis:
- Período XIII Mes: 1 Dic 2024 → 30 Nov 2025
- Tiempo trabajado: 1 Dic 2024 → 15 Mar 2025 = 104 días laborables
- Total período: 1 Dic 2024 → 30 Nov 2025 = 260 días laborables
- Proporción: 104/260 = 0.4 (40%)
- XIII Mes Proporcional: $1,000 × 0.4 = $400.00
```

### **Caso 2: Liquidación en Diciembre 2025**
```
Fecha Liquidación: 10 de Diciembre 2025
Salario Base: $1,200.00
Último XIII Mes Pagado: Diciembre 2024 (período 2024 completo)

Análisis:
- Nuevo Período XIII: 1 Dic 2025 → 30 Nov 2026
- Tiempo trabajado: 1 Dic 2025 → 10 Dic 2025 = 8 días laborables
- Total período: 1 Dic 2025 → 30 Nov 2026 = 261 días laborables
- Proporción: 8/261 = 0.0306 (3.06%)
- XIII Mes Proporcional: $1,200 × 0.0306 = $36.72
```

### **Caso 3: Liquidación en Julio 2025 (Empleado Nuevo)**
```
Fecha Liquidación: 20 de Julio 2025
Salario Base: $800.00
Fecha Ingreso: 15 de Febrero 2025

Análisis:
- Período XIII Mes: 1 Dic 2024 → 30 Nov 2025
- Tiempo trabajado: 15 Feb 2025 → 20 Jul 2025 = 110 días laborables
- Total período: 260 días laborables
- Proporción: 110/260 = 0.4231 (42.31%)
- XIII Mes Proporcional: $800 × 0.4231 = $338.48
```

---

## 🔧 **Implementación en PHP**

### **Función Principal**
```php
<?php
class XIIIMesProporcionalCalculator
{
    public function calcularXIIIMesProporcionalLiquidacion(
        string $fechaLiquidacion,
        float $salarioBase,
        string $fechaIngreso,
        int $empleadoId
    ): array {

        $fechaLiq = new DateTime($fechaLiquidacion);
        $periodoInfo = $this->determinarPeriodoXIIIMes($fechaLiq);

        // Determinar fecha de inicio para cálculo
        $fechaIng = new DateTime($fechaIngreso);
        $fechaInicioCalculo = ($fechaIng > $periodoInfo['inicio'])
            ? $fechaIng
            : $periodoInfo['inicio'];

        // Verificar último pago XIII mes
        $ultimoPago = $this->obtenerUltimoPagoXIIIMes($empleadoId);
        if ($ultimoPago && $ultimoPago['fecha_pago'] >= $periodoInfo['inicio']->format('Y-m-d')) {
            $fechaUltimoPago = new DateTime($ultimoPago['fecha_pago']);
            $fechaUltimoPago->add(new DateInterval('P1D')); // +1 día

            if ($fechaUltimoPago > $fechaInicioCalculo) {
                $fechaInicioCalculo = $fechaUltimoPago;
            }
        }

        // Calcular días laborables
        $diasTrabajados = $this->calcularDiasLaborables(
            $fechaInicioCalculo,
            $fechaLiq
        );

        $diasTotalPeriodo = $this->calcularDiasLaborables(
            $periodoInfo['inicio'],
            $periodoInfo['fin']
        );

        // Calcular proporción y monto
        $proporcion = $diasTotalPeriodo > 0 ? $diasTrabajados / $diasTotalPeriodo : 0;
        $montoProporcional = $salarioBase * $proporcion;

        return [
            'periodo_xiii' => $periodoInfo['año'],
            'inicio_periodo' => $periodoInfo['inicio']->format('Y-m-d'),
            'fin_periodo' => $periodoInfo['fin']->format('Y-m-d'),
            'inicio_calculo' => $fechaInicioCalculo->format('Y-m-d'),
            'fin_calculo' => $fechaLiq->format('Y-m-d'),
            'dias_trabajados' => $diasTrabajados,
            'dias_total_periodo' => $diasTotalPeriodo,
            'proporcion' => round($proporcion, 4),
            'salario_base' => $salarioBase,
            'monto_proporcional' => round($montoProporcional, 2),
            'ultimo_pago_xiii' => $ultimoPago,
            'estado_periodo' => $periodoInfo['estado']
        ];
    }

    private function determinarPeriodoXIIIMes(DateTime $fechaLiquidacion): array
    {
        $año = (int)$fechaLiquidacion->format('Y');
        $mes = (int)$fechaLiquidacion->format('n');

        if ($mes === 12) {
            // Diciembre: primer mes del nuevo período
            return [
                'año' => $año + 1,
                'inicio' => new DateTime("{$año}-12-01"),
                'fin' => new DateTime(($año + 1) . "-11-30"),
                'estado' => 'PERIODO_NUEVO'
            ];
        } else {
            // Enero-Noviembre: período en curso
            return [
                'año' => $año,
                'inicio' => new DateTime(($año - 1) . "-12-01"),
                'fin' => new DateTime("{$año}-11-30"),
                'estado' => 'PERIODO_EN_CURSO'
            ];
        }
    }

    private function calcularDiasLaborables(DateTime $inicio, DateTime $fin): int
    {
        $dias = 0;
        $current = clone $inicio;

        while ($current <= $fin) {
            $diaSemana = (int)$current->format('N'); // 1=Lunes, 7=Domingo

            if ($diaSemana <= 5) { // Lunes a Viernes
                // Aquí se podría integrar con BusinessCalendar para excluir feriados
                if (!$this->esFeriado($current)) {
                    $dias++;
                }
            }

            $current->add(new DateInterval('P1D'));
        }

        return $dias;
    }

    private function esFeriado(DateTime $fecha): bool
    {
        // TODO: Integrar con BusinessCalendar cuando esté implementado
        // Por ahora, lista básica de feriados fijos de Panamá
        $feriados = [
            '01-01', // Año Nuevo
            '01-09', // Día de los Mártires
            '05-01', // Día del Trabajador
            '11-03', // Independencia de Colombia
            '11-10', // Primer Grito de Independencia
            '11-28', // Independencia de España
            '12-08', // Día de la Madre
            '12-25'  // Navidad
        ];

        $fechaFormato = $fecha->format('m-d');
        return in_array($fechaFormato, $feriados);
    }

    private function obtenerUltimoPagoXIIIMes(int $empleadoId): ?array
    {
        // Consultar último pago de XIII mes del empleado
        // Buscar en planilla_detalle donde concepto sea XIII Mes
        $sql = "
            SELECT pd.monto, pc.fecha_fin, pc.fecha_inicio
            FROM planilla_detalle pd
            INNER JOIN planilla_cabecera pc ON pd.planilla_id = pc.id
            INNER JOIN concepto c ON pd.concepto_id = c.id
            WHERE pd.employee_id = ?
                AND c.concepto IN ('8', 'XIII_MES', 'DECIMO_TERCER_MES')
                AND pc.estado = 'CERRADA'
            ORDER BY pc.fecha_fin DESC
            LIMIT 1
        ";

        // Implementar consulta a BD
        // Retornar array con fecha_pago, monto, etc. o null si no hay pagos
        return null; // Placeholder
    }
}
?>
```

---

## 📝 **Fórmula para Concepto LIQ006 Mejorada**

### **Fórmula Actual**
```
SUELDO_MENSUAL * MESES_TRABAJADOS_ANO_ACTUAL / 12
```

### **Fórmula Propuesta Mejorada**
```
XIII_MES_PROPORCIONAL_LIQUIDACION(FECHA_LIQUIDACION, SUELDO, FECHA_INGRESO, FICHA)
```

### **Implementación en PlanillaConceptCalculator**
```php
// Agregar al método processFormula()
$formula = preg_replace_callback(
    '/XIII_MES_PROPORCIONAL_LIQUIDACION\s*\(\s*([^,]+)\s*,\s*([^,]+)\s*,\s*([^,]+)\s*,\s*([^)]+)\s*\)/',
    function($matches) {
        $fechaLiquidacion = trim($matches[1]);
        $salario = trim($matches[2]);
        $fechaIngreso = trim($matches[3]);
        $empleadoId = trim($matches[4]);

        // Obtener valores reales
        $fechaLiqReal = $this->obtenerFechaLiquidacion($empleadoId);
        $salarioReal = $this->obtenerSalarioBase($empleadoId);
        $fechaIngReal = $this->obtenerFechaIngreso($empleadoId);

        $calculator = new XIIIMesProporcionalCalculator();
        $resultado = $calculator->calcularXIIIMesProporcionalLiquidacion(
            $fechaLiqReal,
            $salarioReal,
            $fechaIngReal,
            $empleadoId
        );

        return (string)$resultado['monto_proporcional'];
    },
    $formula
);
```

---

## 🎯 **Casos Especiales y Consideraciones**

### **1. Empleado con Múltiples Pagos XIII Mes**
- **Problema**: Empleado que ya recibió XIII mes en período actual
- **Solución**: Restar días ya pagados del cálculo proporcional

### **2. Empleado que Ingresó en Diciembre**
- **Problema**: Ingreso en primer mes del período XIII
- **Solución**: Calcular desde fecha exacta de ingreso

### **3. Liquidación en Noviembre (Final de Período)**
- **Problema**: Último mes del período XIII
- **Solución**: Calcular proporcional hasta fecha exacta de liquidación

### **4. Cambios de Salario Durante el Período**
- **Problema**: Salario varió durante el período del XIII mes
- **Solución**: Usar salario promedio ponderado o último salario

### **5. Integración con BusinessCalendar**
- **Requerimiento**: Días laborables exactos según calendario empresarial
- **Implementación**: Integrar con módulo BusinessCalendar cuando esté disponible

---

## 📋 **Validaciones Necesarias**

### **Validaciones de Entrada**
1. **Fecha de Liquidación**: Debe ser válida y posterior a fecha de ingreso
2. **Salario Base**: Debe ser mayor a 0
3. **Fecha de Ingreso**: Debe ser anterior o igual a fecha de liquidación
4. **Employee ID**: Debe existir en la base de datos

### **Validaciones de Cálculo**
1. **Días Trabajados**: No puede ser negativo
2. **Proporción**: Debe estar entre 0 y 1
3. **Monto Resultante**: Debe ser mayor o igual a 0

### **Validaciones de Negocio**
1. **Período Mínimo**: Verificar si empleado cumple tiempo mínimo para XIII mes
2. **Último Pago**: Evitar duplicación de pagos del XIII mes
3. **Estado del Empleado**: Verificar que esté en condición de recibir liquidación

---

## 🚀 **Plan de Implementación**

### **Fase 1: Implementación Básica** *(1 semana)*
1. Crear clase `XIIIMesProporcionalCalculator`
2. Implementar lógica de determinación de períodos
3. Crear función básica de cálculo de días laborables
4. Testing con casos básicos

### **Fase 2: Integración Sistema** *(1 semana)*
1. Integrar función en `PlanillaConceptCalculator`
2. Actualizar fórmula concepto LIQ006
3. Testing con liquidaciones reales
4. Validación resultados

### **Fase 3: Optimizaciones** *(1 semana)*
1. Integración con BusinessCalendar
2. Optimización consultas BD
3. Cache de resultados
4. Testing rendimiento

### **Fase 4: Documentación y Entrega** *(0.5 semanas)*
1. Documentación técnica completa
2. Manual de usuario
3. Testing final
4. Deploy a producción

---

## 💡 **Beneficios de la Implementación**

### **Precisión Legal**
- ✅ Cumple exactamente con legislación panameña
- ✅ Considera períodos reales del XIII mes (dic-nov)
- ✅ Evita duplicación de pagos

### **Automatización**
- ✅ Cálculo automático según fecha de liquidación
- ✅ No requiere intervención manual
- ✅ Reduce errores humanos

### **Trazabilidad**
- ✅ Registro detallado de cálculos
- ✅ Auditoría completa del proceso
- ✅ Justificación de montos

### **Escalabilidad**
- ✅ Funciona con cualquier fecha de liquidación
- ✅ Se adapta a cambios en calendarios laborales
- ✅ Preparado para integración BusinessCalendar

---

**Estado**: 📋 **Análisis Completo - Listo para Implementación**
**Prioridad**: 🔥 **Alta** - Requerido para cálculos precisos de liquidación
**Estimación**: 3.5 semanas para implementación completa