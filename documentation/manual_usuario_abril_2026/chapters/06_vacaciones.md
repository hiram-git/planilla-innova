# Vacaciones y tiempo libre

Este capítulo documenta el módulo de vacaciones: balances por empleado,
solicitudes con flujo de aprobación, generación de planillas de
vacaciones, calendario visual y reportes PDF.

## Balances y acumulación (legislación Panamá)

Desde **Vacaciones → Balances** se consulta el saldo de vacaciones
disponibles para cada empleado, calculado por el servicio
`VacationBalanceService`.

La vista muestra por empleado:

| Columna          | Descripción                                                 |
|------------------|-------------------------------------------------------------|
| Código / Nombre  | Datos del empleado                                          |
| Cargo / Posición | Ubicación organizacional                                    |
| Fecha ingreso    | Base para el cálculo de antigüedad                          |
| Meses trabajados | Tiempo transcurrido desde el ingreso                        |
| Antigüedad       | Años y meses completos (singular/plural dinámico)           |
| Días disponibles | Saldo vigente                                               |
| Días disfrutados | Total ya tomado                                             |
| Situación        | Activo / Inactivo / Suspendido                              |
| Tipo de planilla | Grupo al que pertenece el empleado                          |

El saldo puede filtrarse por **tipo de planilla** para enfocarse en un
grupo específico.

### Cálculo del saldo

El sistema sigue la legislación panameña:

- Cada empleado devenga **1 mes de vacaciones por 11 meses trabajados**.
- Las vacaciones se expresan en **días de disfrute** (30 días por
  período anual típicamente).
- La antigüedad se calcula desde la fecha de ingreso hasta hoy; los
  períodos interrumpidos (suspensiones, licencias sin goce) pueden
  restarse según política de la empresa.

### Integración con asistencias

Desde v3.5.22 el cálculo de horas trabajadas esperadas dentro del período
de vacaciones **incluye el tiempo de almuerzo** (ajuste aplicado para que
las horas programadas reflejen la jornada completa, no sólo el tiempo
efectivo de trabajo).

## Solicitudes de vacaciones

### Requisitos previos

- Empleado **activo** con fecha de ingreso registrada.
- Calendario empresarial cargado (§9.3) para validar feriados y días
  especiales dentro del período solicitado.
- Tipo de planilla configurado si se desea generar automáticamente la
  planilla de vacaciones al aprobar.

### Crear una solicitud

Desde **Vacaciones → Nueva Solicitud**:

| Campo                    | Descripción                                                     |
|--------------------------|-----------------------------------------------------------------|
| Empleado                 | Búsqueda Select2 por nombre o código.                           |
| Fecha inicio             | Primer día del período de disfrute.                             |
| Fecha fin                | Último día del período de disfrute.                             |
| Tipo de vacaciones       | `ANNUAL` (anuales acumuladas) o `COMPENSATION` (compensación).  |
| Días solicitados         | Cantidad a disfrutar.                                           |
| Días de pago             | Por defecto igual a los días solicitados (editable desde mar-2026). |

El sistema valida que haya **saldo suficiente** antes de guardar. Si el
empleado no tiene los días disponibles, el formulario rechaza la
solicitud con mensaje claro.

::: {.badge-new}
**UX mejorada en v3.5.22** — En la vista de creación se **ocultaron los
campos "Valor Día" y "Monto Compensación"** para evitar confusión. El
sistema los calcula internamente en función del salario del empleado y los
días solicitados, sin que el usuario tenga que intervenir.
:::

Al guardar, la solicitud queda en estado **PENDIENTE**.

### Estados de la solicitud

| Estado       | Significado                                          |
|--------------|------------------------------------------------------|
| `PENDIENTE`  | Creada, sin revisar.                                 |
| `APROBADA`   | Validada por el responsable; puede generar planilla.  |
| `RECHAZADA`  | Denegada con motivo.                                 |
| `CANCELADA`  | Cancelada a solicitud del empleado antes del disfrute.|

## Flujo de aprobación

Desde **Vacaciones → Solicitudes pendientes**:

1. El responsable revisa la solicitud.
2. Valida saldo disponible, fechas coherentes y ausencia de conflictos
   con otras vacaciones del mismo período o con feriados.
3. Elige **Aprobar** o **Rechazar** (con motivo obligatorio si rechaza).
4. Al aprobar, el sistema puede **generar automáticamente** una planilla
   de vacaciones con el cálculo del pago correspondiente.

### Acciones disponibles sobre solicitudes aprobadas (v3.5.22)

::: {.badge-new}
**Nuevo en v3.5.22**
:::

Desde la lista de solicitudes aprobadas:

- **Editar días de disfrute**: ajustar la cantidad de días sin crear una
  nueva solicitud. Útil cuando el empleado regresa antes de lo previsto
  o extiende sus vacaciones.
- **Revertir planilla de vacaciones**: nueva acción que permite **deshacer
  la planilla generada** si hubo un error de cálculo, sin eliminar la
  solicitud original. El empleado queda con la solicitud aprobada y el
  saldo de días intacto, listo para regenerar una planilla correcta.

## Planilla de vacaciones

Al aprobar una solicitud con generación automática activa, el sistema
crea una planilla especial con:

- Frecuencia tipo `VACATION` (o la configurada para vacaciones).
- Los días solicitados convertidos a monto de pago.
- Los conceptos aplicables al tipo de planilla de vacaciones.
- Los descuentos proporcionales (seguro social, ISR sobre vacaciones,
  acreedores activos del empleado).

La planilla se procesa y cierra igual que una regular (§5.2), generando
acumulados y el comprobante de pago correspondiente.

### Casos típicos

| Situación                                    | Acción recomendada                                |
|----------------------------------------------|---------------------------------------------------|
| Empleado se va menos días de lo aprobado     | Editar días de disfrute y regenerar planilla      |
| Error en el cálculo de la planilla           | Revertir planilla → revisar datos → regenerar     |
| Empleado cancela las vacaciones              | Cancelar solicitud (saldo vuelve a quedar disponible)|
| Vacaciones fraccionadas (varias salidas)     | Crear una solicitud por cada fracción             |

## Calendario de vacaciones

Desde **Vacaciones → Calendario**. Muestra en vista mensual/anual
(FullCalendar) todas las vacaciones **aprobadas** del período
seleccionado.

- Código de color por tipo (anual vs. compensación).
- Click en un evento abre el detalle de la solicitud.
- Integración con el calendario empresarial: los feriados nacionales se
  muestran como marcadores de fondo (§9.3).

Útil para:

- Planificar períodos de alta demanda operativa.
- Detectar solapamientos entre empleados del mismo equipo.
- Validar que no se apruebe vacaciones durante cierres contables
  críticos.

## Reportes PDF

Desde **Vacaciones → Reportes**. Tres tipos disponibles:

### 1. Certificado individual

Certificado PDF por empleado con:

- Datos del empleado, cargo y fecha de ingreso.
- Período de disfrute (inicio, fin, días).
- Monto pagado y desglose.
- Firma y sello de la empresa.

### 2. Listado de vacaciones

Reporte tabular con filtros por empleado, estado, tipo y rango de fechas.
Lista todas las solicitudes que coincidan con los filtros.

### 3. Reporte de saldos

Listado completo de balances disponibles por empleado, ordenable y
filtrable por tipo de planilla. Útil para:

- Planificación anual de vacaciones.
- Detectar empleados con saldos excesivos (riesgo laboral).
- Control de ausentismo.

## Buenas prácticas

- **Validar el saldo disponible** antes de aprobar; documentar comentario
  si se rechaza.
- Usar el **calendario empresarial** para evitar aprobar solicitudes que
  se solapen con feriados nacionales (el empleado no estaría "tomando"
  vacaciones los días feriados).
- Si se genera una planilla de vacaciones con error, usar **Revertir
  Planilla** antes de intentar una corrección — es más rápido y seguro
  que editar manualmente el detalle.
- **Exportar el reporte de saldos** periódicamente (trimestral o
  semestral) para control de ausentismo y planificación de cobertura.
- Acumular saldos excesivos es un riesgo laboral: recomendar a los
  empleados con más de 45 días acumulados que programen disfrute.
