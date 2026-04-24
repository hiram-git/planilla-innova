# Deducciones, acreedores y préstamos

Este capítulo cubre la gestión de instituciones acreedoras (bancos,
cooperativas, cajas de ahorro), las deducciones recurrentes por empleado
y el sistema de préstamos con seguimiento automático de cuotas.

## Instituciones acreedoras

Las **instituciones acreedoras** son entidades externas a las que se les
transfiere parte del pago del empleado: bancos, cooperativas, pensiones
alimenticias judiciales, seguros médicos privados, etc.

### Crear un acreedor

Desde **Acreedores → Nuevo**:

| Campo           | Descripción                                  |
|-----------------|----------------------------------------------|
| Nombre          | Razón social del acreedor                    |
| Tipo            | Banco, cooperativa, pensión, seguro, otros   |
| Contacto        | Persona, teléfono, correo                    |
| Cuenta bancaria | Datos para transferencia (opcional)          |
| Estado          | Activo / Inactivo                            |

Al guardar, el acreedor queda disponible para vincular deducciones y
préstamos.

## Deducciones por empleado

Desde **Deducciones**:

### Crear una deducción

1. Seleccionar **empleado** (búsqueda Select2).
2. Elegir el **acreedor** destinatario.
3. Definir el **monto**: fijo (ej. $50) o porcentaje (ej. 3.5 % del
   salario).
4. Definir el **rango de fechas** de vigencia (desde/hasta).
5. Agregar una **referencia** (número de préstamo externo, número de
   expediente judicial, etc.).
6. Guardar.

La deducción se activa automáticamente en las planillas del período
configurado, siempre que exista un concepto en el catálogo que use la
función `ACREEDOR()` (ver §4.2).

### Asignación masiva

Para aplicar una misma deducción a **varios empleados al mismo tiempo**:

1. **Deducciones → Asignación masiva**.
2. Seleccionar el acreedor.
3. Configurar monto o porcentaje y rango de fechas.
4. Seleccionar los empleados (por grupo, tipo de planilla o individualmente).
5. Confirmar.

Casos típicos:

- Seguro médico colectivo con misma cuota para todo el personal.
- Aporte sindical uniforme.
- Cuota de cooperativa voluntaria.

## Uso en fórmulas

Para que las deducciones se reflejen en las planillas, hace falta un
**concepto** que las consuma. La función `ACREEDOR()` devuelve el monto
asignado al empleado actual para el acreedor indicado:

```
-- Concepto DED_BANCO_NACIONAL
ACREEDOR(EMPLEADO, 5)      -- 5 es el id del acreedor "Banco Nacional"

-- Concepto DED_SEGURO_PRIVADO
ACREEDOR(EMPLEADO, 12)     -- 12 es el id del seguro
```

El concepto debe estar marcado como **Monto cálculo** y como **Deducción**
(ver §4.1).

## Sistema de préstamos

::: {.badge-new}
**Introducido en v3.5.17, afinado en v3.5 (2026)**
:::

Módulo completo para gestionar préstamos internos y externos a
empleados, con **seguimiento automático de cuotas pagadas** y cambio
automático de estado al completarse.

### Crear un préstamo

Desde **Préstamos → Nuevo Préstamo**:

| Campo                 | Descripción                                                       |
|-----------------------|-------------------------------------------------------------------|
| Empleado              | Seleccione del dropdown                                           |
| Monto total           | Valor del préstamo                                                |
| Número de cuotas      | Cantidad de pagos a realizar                                      |
| Monto cuota           | Se calcula automáticamente (Monto total ÷ Número de cuotas)       |
| Acreedor (opcional)   | Institución externa si el préstamo no es interno                  |
| Fecha inicio          | Primera planilla en la que se descontará                          |
| Descripción           | Motivo o referencia (ej. "Adelanto quincenal", "Emergencia médica") |

Al guardar, el préstamo queda en estado **`activo`**.

### Estados del préstamo

| Estado      | Qué significa                                    | Comportamiento                                                 |
|-------------|--------------------------------------------------|----------------------------------------------------------------|
| `activo`    | Préstamo vigente con cuotas pendientes           | Se descuenta automáticamente en cada planilla.                 |
| `pagado`    | Todas las cuotas completadas                     | **No se descuenta más**. Estado final (no reactivable).        |
| `cancelado` | Cancelado manualmente antes de completarse       | No se descuenta. Permanece en historial.                       |

### Tracking automático de cuotas

El sistema mantiene actualizado el progreso del préstamo sin
intervención manual:

- **Cuotas pagadas**: se incrementa cada vez que se procesa una planilla
  que descontó la cuota.
- **Monto pagado**: `Cuotas pagadas × Monto cuota`.
- **Saldo pendiente**: `Monto total - Monto pagado`.
- **Cambio automático a `pagado`**: cuando `Cuotas pagadas = Número de cuotas`,
  el préstamo se marca como pagado y **deja de aparecer en planillas
  nuevas**.

### Integración con planillas (v3.5 ene-2026)

Durante la regeneración de empleados, el sistema:

1. Busca todos los préstamos en estado `activo` del empleado.
2. Aplica automáticamente la cuota de cada uno.
3. Si alguno alcanzó el total de cuotas, lo marca como `pagado` y no lo
   incluye en planillas futuras.

Este comportamiento es automático desde el fix específico aplicado en
ene-2026 para *manejo especial de cuotas de préstamo en el
procesamiento de planillas*.

### Vista principal de préstamos

Desde **Préstamos → Listado**:

| Columna      | Información                                                |
|--------------|------------------------------------------------------------|
| Empleado     | Nombre completo                                            |
| Monto total  | Valor original del préstamo                                |
| Cuotas       | Pagadas / Total (ej. 5/12)                                 |
| Monto cuota  | Valor del descuento periódico                              |
| Monto pagado | Total abonado hasta el momento                             |
| Saldo        | Monto pendiente                                            |
| Acreedor     | Institución si aplica (vacío si es interno)                |
| Estado       | Badge: Activo (azul), Pagado (verde), Cancelado (rojo)     |
| Acciones     | Ver, Editar, Eliminar                                      |

### Filtros y búsqueda

- **Por estado**: activos / pagados / cancelados.
- **Por empleado**: nombre o código.
- **Por acreedor**: filtrar por institución financiera.
- **DataTables**: búsqueda global, ordenamiento, paginación.

### Asociación con acreedores

Los préstamos pueden asociarse **opcionalmente** a un acreedor:

- Útil para préstamos bancarios o de instituciones financieras.
- Permite generar reportes agrupados por acreedor.
- Facilita la conciliación de pagos externos (lo que el empleado paga vs.
  lo que transfiere la empresa al banco).

::: {.badge-warn}
**Importante** — Una vez que un préstamo alcanza estado `pagado`, **no se
puede reactivar**. Si hubo un error y debe retomar los descuentos, debe
eliminar el préstamo y crear uno nuevo con el saldo correcto.
:::

### Casos de uso comunes

| Escenario              | Configuración                                                   |
|------------------------|-----------------------------------------------------------------|
| Préstamo empresa 12 cuotas | Monto $600, Cuotas 12, Cuota $50, sin acreedor              |
| Préstamo bancario      | Monto $5 000, Cuotas 24, Acreedor "Banco Nacional"              |
| Adelanto salarial      | Monto $200, Cuotas 2, Descripción "Adelanto quincenal"          |
| Préstamo de emergencia | Monto $300, Cuotas 3, Descripción "Emergencia médica"           |

## Reportes

Desde **Acreedores → Reportes**:

- **Por acreedor**: total a transferir a cada institución en un período.
- **Por empleado**: historial de deducciones y préstamos activos.
- **Conciliación**: lo descontado en planilla vs. lo que se debe
  transferir al acreedor.

Los reportes están disponibles en PDF y Excel.

## Checklist de operación

- [ ] Acreedores con datos completos y activos.
- [ ] Deducciones asociadas al empleado con fechas vigentes que cubran
      el período de la planilla.
- [ ] Conceptos que usan `ACREEDOR()` correctamente configurados y
      probados (§4).
- [ ] Para asignaciones masivas: **revisar la lista final** antes de
      confirmar — es fácil incluir empleados por error.
- [ ] **Préstamos**: verificar que el monto total sea divisible entre
      número de cuotas para evitar residuos decimales.
- [ ] Revisar periódicamente préstamos **cercanos a completarse** y
      validar que el cambio automático a `pagado` se aplicó.
- [ ] Confirmar que préstamos marcados como `pagado` **no aparezcan**
      en planillas nuevas.
- [ ] Documentar externamente (acta, firma) las aprobaciones de
      préstamos para auditoría.
