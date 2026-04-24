# Procesamiento de planillas

Este capítulo documenta el ciclo completo de una planilla: desde la
creación hasta el cierre, el cálculo de acumulados, las liquidaciones
finales y la exportación al ERP INNOVA.

## Tipos de planilla y ciclo de vida

### Tipos soportados

El sistema trabaja con varios tipos de planilla, cada uno con su propio
conjunto de conceptos aplicables:

| Tipo                   | Uso típico                                              |
|------------------------|---------------------------------------------------------|
| Regular                | Nómina ordinaria (quincenal o mensual).                 |
| XIII Mes               | Pago trimestral del décimo tercer mes (Panamá).         |
| Vacaciones             | Pago de vacaciones cuando el empleado sale a gozarlas.  |
| Liquidación            | Pago final al terminar la relación laboral.             |
| Especial               | Bonos, aguinaldos, ajustes retroactivos masivos.        |

Un empleado puede pertenecer a **varios tipos** simultáneamente (su salario
se paga en regulares, su XIII se paga en las planillas de XIII, etc.).

### Estados de una planilla

| Estado      | Qué significa                                                |
|-------------|--------------------------------------------------------------|
| `PENDIENTE` | Creada, sin calcular. Se puede modificar el encabezado.      |
| `CALCULADA` | Conceptos aplicados; permite revisar y recalcular.           |
| `PROCESADA` | Cálculos fijados; los acumulados aún no se han generado.     |
| `CERRADA`   | Bloqueada para edición; acumulados generados; lista para PDF.|

El flujo normal es: Crear → Calcular → Revisar → Procesar → Cerrar.
Desde `PROCESADA` o `CERRADA` se puede devolver a `PENDIENTE` con la
acción de **Reproceso** (§5.2).

### Requisitos previos antes de crear una planilla

- Empleados **activos** con horario y salario asignados.
- Conceptos configurados y fórmulas probadas (§4).
- Tipos de planilla y frecuencias activas.
- Asistencias sincronizadas del período si los conceptos dependen de ellas.
- Horas extras revisadas si usa el flujo de aprobación (§3.4).

## Creación, cálculo, cierre y reproceso

### Crear una planilla

Desde **Planillas → Nueva Planilla**:

| Campo              | Descripción                                             |
|--------------------|---------------------------------------------------------|
| Descripción        | Nombre visible (ej. "Regular Marzo 2026 - 2ª quincena").|
| Tipo de planilla   | Regular, XIII, Vacaciones, Liquidación, Especial.       |
| Frecuencia         | Semanal, quincenal, mensual, etc.                       |
| Fecha de pago      | Fecha efectiva en que se pagará al empleado.            |
| Período inicio/fin | Rango de días que cubre la planilla.                    |

Al guardar, se genera el encabezado en estado `PENDIENTE`. Todavía no hay
empleados ni conceptos asignados.

### Cargar empleados

Desde el detalle de la planilla:

- **Cargar todos** los empleados elegibles según tipo y situación.
- **Cargar seleccionados**: elegir individualmente desde un listado
  filtrable (útil para planillas especiales).

### Calcular

Botón **Calcular** en el detalle. El sistema aplica los conceptos del
catálogo global según sus condiciones:

1. Verifica elegibilidad por tipo de planilla, situación y fechas de vigencia.
2. Evalúa las fórmulas (motor `nxp/math-executor`, ver §4.2).
3. Precarga conceptos de asistencias si el empleado tiene
   `marca_asistencia = 1` (horas trabajadas, extras, nocturnas,
   descuentos por tardanza/ausencia).
4. Aplica **conceptos manuales activos** del empleado (§4.6).
5. **Manejo especial de cuotas de préstamo**: desde ene-2026 el sistema
   detecta préstamos activos del empleado y aplica automáticamente la
   cuota correspondiente al período (ver §7.3).
6. Calcula totales: ingresos - deducciones = neto.

### Revisar

En el detalle por empleado se ve la tabla completa de conceptos con
asignaciones, deducciones y neto. Aquí es cuando conviene:

- Validar totales y observar mensajes/alertas que disparen reglas de
  conceptos.
- Corregir datos del empleado o del concepto si hay algún valor anómalo y
  **regenerar ese empleado** puntualmente (sin recalcular toda la
  planilla).

::: {.badge-warn}
**Importante** — Al regenerar un empleado se **reaplican los conceptos
manuales** y las cuotas de préstamo desde ene-2026. Antes de esta versión
el reproceso ignoraba los conceptos manuales, por eso se añadió el fix
específico.
:::

### Procesar

Botón **Procesar**. Fija los cálculos y cambia el estado a `PROCESADA`.
En este punto:

- Ya no se pueden editar los montos línea por línea sin reprocesar.
- La planilla aparece disponible para **Exportación INNOVA** (§5.5).
- Aún no se han generado los acumulados.

### Cerrar

Botón **Cerrar**. Bloquea la planilla y genera acumulados:

- XIII Mes trimestral (salario del período ÷ 3).
- Vacaciones proporcionales.
- Prima de antigüedad.
- Cualquier acumulado configurado via `ACUMULADOS("CODIGO")`.

Después de cerrar, **los reportes PDF y Excel** de la planilla ya están
disponibles para impresión y archivo.

### Reprocesar

Acción **Reprocesar** disponible en planillas `PROCESADA` o `CERRADA`.
Devuelve la planilla a `PENDIENTE`, limpia los registros previos y
permite recalcular.

Opciones del modal de reproceso (desde v3.4.2):

| Opción                              | Efecto                                                          |
|-------------------------------------|-----------------------------------------------------------------|
| **Validar situación del empleado**  | Excluye del recálculo a empleados inactivos al momento actual.  |
| **Usar salario histórico por tipo** | Mantiene el salario que tenía el empleado en la fecha original. |

Use reproceso cuando:

- Se actualizaron datos de empleados (salario, horario) y debe reflejarse.
- Se ajustaron conceptos o fórmulas.
- Se re-sincronizaron asistencias del período.
- Se aprobaron/rechazaron horas extras que antes estaban pendientes.

## Acumulados

Los **acumulados** son montos que el sistema guarda para calcular
prestaciones futuras: XIII mes, vacaciones, prima de antigüedad, etc.

### Tipos principales

| Tipo               | Cómo se calcula                                                           |
|--------------------|---------------------------------------------------------------------------|
| XIII mes           | Mensual y trimestral (períodos Dic 16 – Abr 15, Abr 16 – Ago 15, Ago 16 – Dic 15). Salario anual ÷ 3 por período. |
| Vacaciones         | Saldo de días devengados y montos proporcionales.                         |
| Prima de antigüedad| Según antigüedad acumulada del empleado.                                  |
| Otros              | Configurables via conceptos con la función `ACUMULADOS("CODIGO")`.        |

Los acumulados se generan **automáticamente al cerrar una planilla** o al
calcular una planilla de liquidación. Para consultarlos:

### Vistas disponibles

Desde **Planillas → Acumulados**:

| Vista               | Descripción                                                             |
|---------------------|-------------------------------------------------------------------------|
| **Por empleado**    | Histórico completo de acumulaciones y saldos del empleado seleccionado. |
| **Por concepto**    | Agrupación por concepto, filtrable por año y tipo de acumulado.         |
| **Por tipo**        | Consolidado de todos los empleados para un tipo de acumulado.           |

Cada vista soporta filtros por año, tipo, concepto y planilla, y permite
exportar a Excel con formato profesional (PhpSpreadsheet).

### Importación de acumulados

Para empresas que migran desde otro sistema, **Acumulados → Importar**
permite cargar acumulados históricos desde Excel. Los acumulados
importados se integran en las consultas de período y se consideran en la
liquidación (fix aplicado ene-2026 para incluirlos en los *preview* de
liquidación y en las consultas por período).

### Buenas prácticas

- Revisar acumulados **después de cerrar** planillas clave (cierres
  trimestrales XIII y cierres mensuales).
- Al reprocesar planillas antiguas, confirmar los **rangos explícitos**
  usados en `ACUMULADOS()` para evitar duplicidades.
- Validar que los tipos de acumulado estén activos y correctamente
  referenciados en los conceptos que los alimentan.

### XIII Mes en comprobantes PDF

Desde v3.5 (PRs #90-94) los comprobantes de pago incluyen el **detalle de
la acumulación XIII mes** del período: se muestra el desglose por
concepto que contribuyó al acumulado, no sólo el total.

## Liquidaciones

### Refactor v3.5.22

::: {.badge-new}
**Refactorizado en v3.5.22**
:::

- **Rutas separadas**: CRUD en `/liquidation/*`; reportes en
  `/liquidation-reports/*` (principio de responsabilidad única).
- **Cálculo LIQ007 mejorado**: ajuste de rangos de fechas + totales
  mensuales precisos + logging detallado para auditoría.
- **Acumulados por tipo**: método `getAccumulatedTypesForLiquidation()`
  que consolida XIII mes y otros acumulados automáticamente.
- **Totales mensuales**: método `getMonthlyTotalsForLiquidation()` para
  calcular la base mensual exacta por período.
- **Visualización**: singular/plural dinámico ("1 año" / "2 años",
  "1 día" / "2 días") en la vista de detalle del empleado.

### Requisitos

- Empleado **activo** con datos completos (fecha de ingreso, salario,
  situación).
- Motivo de terminación definido.
- Fecha de terminación.
- Frecuencia `LIQUIDACION` creada y activa.
- Acumulados del empleado actualizados (XIII mes, vacaciones) para que
  los cálculos proporcionales sean precisos.

### Crear una liquidación

Desde **Liquidaciones → Crear**:

1. Seleccionar el **empleado**.
2. Indicar la **fecha de terminación**.
3. Elegir el **motivo**:
   - Renuncia voluntaria.
   - Despido justificado.
   - Despido injustificado.
   - Jubilación.
   - Mutuo acuerdo.
4. Indicar **días de preaviso** (editable).
5. El sistema calcula automáticamente:
   - **Prima de antigüedad** (según fórmula legal y antigüedad acumulada).
   - **Indemnización** (varía según causa de terminación).
   - **Preaviso** (según los días indicados).
   - **XIII mes proporcional** (concepto `LIQ007` con totales mensuales
     precisos).
   - **Vacaciones pendientes y proporcionales**.
6. Revisar los valores calculados y guardar.

### Planilla de liquidación

Las liquidaciones aprobadas pueden generar una **planilla dedicada** con
frecuencia `LIQUIDACION`:

- Se procesa y cierra igual que una planilla regular.
- Genera sus propios reportes (PDF, Excel, comprobante).
- Al generar la planilla se **asocian automáticamente los acumulados**
  del empleado.

### Reportes de liquidación

En **Liquidaciones → Reportes** (ruta `/liquidation-reports/*`):

- **Certificado de liquidación PDF** con cálculos detallados y base
  mensual.
- **Comprobante de pago** para el empleado (con firma y sello digital).
- **Reporte contable** para registros financieros.
- **Exportación Excel** para análisis adicional.

### Checklist de liquidación

- [ ] Fecha de terminación correcta y motivo seleccionado.
- [ ] Salario y antigüedad revisados antes de aprobar.
- [ ] **Acumulados XIII mes actualizados** para que `LIQ007` dé el valor correcto.
- [ ] Acumulados de vacaciones al día.
- [ ] Exportar/reportar tras aprobar para firma y archivo físico.
- [ ] Si corresponde, crear la **planilla de liquidación** y procesarla.

## Exportación ERP INNOVA

::: {.badge-new}
**Nuevo en v3.5.21**
:::

Este módulo exporta planillas procesadas o cerradas al formato de **texto
plano** requerido por el ERP INNOVA. El archivo incluye movimientos
individuales por empleado, netos y totales por área, listos para
importación directa en el sistema contable.

### Requisitos previos

- Planilla en estado **PROCESADA** o **CERRADA** (no se exportan
  planillas abiertas o en borrador).
- Empleados con cuentas contables o partidas presupuestarias asignadas si
  el ERP las requiere.
- Permiso de exportación asignado al usuario.

### Acceder al módulo

Menú lateral → **Planillas → Exportación INNOVA**. Se muestra una tabla
con todas las planillas disponibles para exportar.

### Columnas de la tabla

| Columna       | Descripción                                      |
|---------------|--------------------------------------------------|
| ID            | Identificador interno de la planilla             |
| Descripción   | Nombre o descripción                             |
| Tipo planilla | Categoría de empleados incluidos                 |
| Frecuencia    | Semanal, quincenal, mensual, etc.                |
| Fecha pago    | Fecha de pago programada                         |
| Período       | Rango de fechas cubierto                         |
| Estado        | `PROCESADA` / `CERRADA`                          |
| Empleados     | Cantidad incluida                                |
| Total neto    | Suma total de pagos netos                        |
| Acciones      | Botón de exportación                             |

### Generar el archivo

1. Localizar la planilla deseada en la tabla.
2. Hacer clic en el botón de exportación en la columna *Acciones*.
3. El sistema genera el archivo de texto plano con el formato INNOVA.
4. El archivo se descarga automáticamente al equipo del usuario.

### Estructura del archivo

El archivo contiene tres secciones en formato de texto plano:

1. **Movimientos individuales**: una línea por concepto por empleado con
   código, descripción, monto devengado y deducido.
2. **Netos por empleado**: resumen del neto a pagar por cada empleado.
3. **Totales por área**: agrupación de totales por departamento o área
   organizacional.

El separador de campos, la longitud de columnas y el encoding siguen el
estándar requerido por el ERP INNOVA.

### Casos de uso

- **Cierre contable mensual**: exportar la planilla mensual y cargarla
  en el ERP para registrar el gasto de nómina.
- **Conciliación bancaria**: usar los netos por empleado para verificar
  los débitos de la cuenta de nómina.
- **Auditoría**: el archivo sirve como respaldo del proceso de pago en
  cada período.
- **Planillas especiales**: liquidaciones y planillas de vacaciones se
  exportan con la misma herramienta.

### Checklist de exportación

- [ ] Planilla en estado PROCESADA o CERRADA.
- [ ] Empleados con cuentas contables correctamente asignadas.
- [ ] Guardar el archivo generado en la carpeta de respaldo del período.
- [ ] Verificar en el ERP INNOVA que la importación se completó sin errores.
- [ ] Si hay diferencias, comparar el total neto del archivo con el
      reporte PDF de la planilla.

## Planillas estimadas (preview)

Antes de procesar una planilla definitiva, el sistema permite generar
una **estimación** que muestra lo que pagaría la planilla real sin
comprometer acumulados ni historial. Útil para:

- Presupuesto anual de nómina.
- Validar el impacto de un cambio masivo de salario.
- Estimar el costo de liquidaciones futuras.

Desde **Planillas → Planillas Estimadas** y **Liquidaciones → Liquidaciones
Estimadas**. Los reportes estimados usan la misma lógica de cálculo que los
reales pero escriben en tablas separadas (`_estimate`) y pueden eliminarse
en cualquier momento sin afectar datos de producción.

## Checklist general previo al cierre

- [ ] Conceptos sin errores y fórmulas probadas (§4.7).
- [ ] Asistencias sincronizadas y procesadas del período.
- [ ] Horas extras revisadas y aprobadas/rechazadas si usa el flujo (§3.4).
- [ ] Empleados activos con salario y horario correctos.
- [ ] Reportes previos revisados (vista estimada o PDF preliminar).
- [ ] Acumulados consultados para detectar duplicidades o saldos raros.
