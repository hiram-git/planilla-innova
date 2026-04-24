# Referencia rápida de fórmulas {.unnumbered}

## Funciones de acumulados

| Función                            | Retorna                                           |
|------------------------------------|---------------------------------------------------|
| `ACUMULADOS("TIPO")`               | Suma del tipo en el período actual                |
| `ACUMULADOS("TIPO", INIPERIODO, FINPERIODO)` | Suma en rango explícito                 |
| `CONCEPTO("CODIGO")`               | Valor calculado de otro concepto del mismo empleado |

## Funciones de asistencias (19)

### Horas

| Función                | Descripción                                  |
|------------------------|----------------------------------------------|
| `HORAS_TRABAJADAS()`   | Total de horas efectivas del período         |
| `HORAS_REGULARES()`    | Horas dentro de jornada ordinaria            |
| `HORAS_EXTRAS()`       | Total de horas extras (25 % + 50 %)          |
| `HORAS_EXTRAS_25()`    | Horas extras al 25 %                         |
| `HORAS_EXTRAS_50()`    | Horas extras al 50 %                         |
| `HORAS_NOCTURNAS()`    | Horas nocturnas (6 PM - 6 AM) con recargo    |
| `HORAS_FERIADOS()`     | Horas en feriados nacionales                 |
| `HORAS_DOMINICALES()`  | Horas dominicales                            |

### Horas extras aprobadas (sólo `overtime_status = APPROVED`)

| Función                          | Descripción                               |
|----------------------------------|-------------------------------------------|
| `HORAS_EXTRAS_APROBADAS()`       | Total aprobadas (25 % + 50 %)             |
| `HORAS_EXTRAS_APROBADAS_25()`    | Aprobadas al 25 %                         |
| `HORAS_EXTRAS_APROBADAS_50()`    | Aprobadas al 50 %                         |

### Ausencias y tardanzas

| Función                    | Descripción                           |
|----------------------------|---------------------------------------|
| `TARDANZAS()`              | Minutos totales de tardanza           |
| `CANTIDAD_TARDANZAS()`     | Número de días con tardanza           |
| `AUSENCIAS()`              | Días de ausencia                      |
| `TOTAL_AUSENCIAS()`        | Total (justificadas + injustificadas) |
| `AUSENCIAS_JUSTIFICADAS()` | Sólo justificadas                     |

### Estadísticas

| Función                        | Descripción                            |
|--------------------------------|----------------------------------------|
| `SCORE_PUNTUALIDAD()`          | 0-100, promedio del período            |
| `DIAS_ASISTENCIA_PERFECTA()`   | Días con score = 100                   |
| `DIAS_TRABAJADOS()`            | Total de días con marcación            |

## Patrones comunes

```
-- Pago de horas extras aprobadas 25 %
HORAS_EXTRAS_APROBADAS_25() * (SUELDO / 220) * 1.25

-- Descuento por tardanzas
TARDANZAS() / 60 * (SUELDO / 220)

-- Bono de puntualidad
SI(SCORE_PUNTUALIDAD() >= 95, 100, 0)

-- UNIDAD dinámica (v3.5.15)
UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)
SI(MARCA_ASISTENCIA, HORAS_REGULARES() * TARIFA_HORA, SUELDO * 0.5)
```
