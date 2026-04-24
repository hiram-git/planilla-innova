# Resolución de problemas (FAQ) {.unnumbered}

> **TODO migración**: consolidar contenido desde `modulo_buenas_practicas.html`
> y ampliar con casos reales de soporte.

## Asistencias

### "Las horas extras no aparecen en la planilla"

Verificar:

1. El empleado tiene **Permite horas extras** habilitado en su perfil.
2. Los registros tienen estado `APPROVED` (véase §3.4).
3. El concepto usa la función correcta:
   - `HORAS_EXTRAS_APROBADAS_25()` para flujo con aprobación.
   - `HORAS_EXTRAS_25()` para pagar todas sin revisar.

### "La sincronización con Base44 falla"

Verificar credenciales API en *Configuración → API externa*, revisar logs en
`storage/logs/attendance_sync_*.log`.

## Planillas

### "Un empleado aparece duplicado"

Revisar los tipos de planilla asignados al empleado. La consulta
`FIND_IN_SET()` puede incluirlo en más de un tipo si está asignado a ambos
(comportamiento esperado desde v3.3.x).

### "El cálculo de XIII Mes no coincide"

Confirmar el trimestre activo. El sistema calcula salario anual ÷ 3 por
período trimestral; verificar acumulados previos en *Acumulados → Por
empleado*.

## Motor de fórmulas

### "La fórmula devuelve 0 siempre"

Verificar:

1. Existen registros en `attendance_calculations` para el período.
2. Los nombres de función coinciden exactamente (sensibles a mayúsculas).
3. No hay recursión circular en funciones `CONCEPTO()`.

### "Mensaje: `Syntax error in formula`"

El motor usa `nxp/math-executor` — revisar balance de paréntesis,
comillas dobles en `CONCEPTO("...")` y `ACUMULADOS("...")`, y operadores
válidos.

## Multi-tenant

### "No puedo cambiar de tenant"

Sólo los usuarios con `is_system_admin = 1` pueden acceder al Panel
Backoffice (véase §9.4).
