# Control de asistencia

Este capítulo documenta el módulo de control de asistencia, desde la
integración con la API de marcaciones Base44 hasta la generación de
conceptos de planilla a partir del tiempo trabajado. El módulo cubre la
normativa laboral panameña (Código de Trabajo artículos 31, 35, 38, 39,
48 y 213).

### Requisitos previos del módulo

Para que el sistema pueda procesar asistencias correctamente necesita:

- Empleados **activos**, con código, horario asignado y situación definida
  (§2.1, §2.6).
- Dispositivos de marcación registrados o la API Base44 configurada.
- **Calendario empresarial** cargado (§9.3) para que feriados y días
  especiales se clasifiquen correctamente.

## Integración API Base44 y sincronización

El sistema obtiene las marcaciones desde la API externa Base44 mediante el
cliente `Base44ApiClient` con reintentos automáticos y *timeout* de 30 s.
La sincronización se ejecuta en tres modalidades:

- **Cron programado**: barre el día anterior cada madrugada.
- **Sincronización manual**: desde *Asistencias → Sincronizar ahora*.
- **Webhook**: la API externa notifica marcaciones en tiempo real.

::: {.badge-info}
**Nota** — La sincronización consulta `attendance_records` y persiste los
resultados procesados en `attendance_calculations`. Ningún cálculo de fórmulas
se ejecuta hasta que este paso termine.
:::

### Configuración inicial

Desde *Asistencia → Configuración API*:

| Campo                   | Descripción                                         |
|-------------------------|-----------------------------------------------------|
| Endpoint URL            | URL base del servicio Base44                        |
| API Key                 | Credencial de autenticación (encriptada en BD)      |
| Timeout (segundos)      | Por defecto 30; subir si la red es lenta            |
| Reintentos              | Por defecto 3, con *back-off* exponencial           |
| Zona horaria            | Zona local del tenant (usada para convertir UTC→local)|

El botón **Probar conexión** lanza una llamada de diagnóstico sin escribir
nada en la BD y muestra el tiempo de respuesta y el código HTTP recibido.

### Dispositivos

Desde *Asistencia → Dispositivos* se pueden registrar fuentes adicionales
de marcaciones (relojes biométricos locales, lectores de huella, apps
móviles). Cada dispositivo tiene:

- Nombre y ubicación.
- Dirección IP / host.
- Estado: activo o inactivo.
- Última sincronización (sólo lectura).

### Modos de sincronización

Desde *Asistencia → Sincronizar*:

| Modo           | Descripción                                               |
|----------------|-----------------------------------------------------------|
| **Completa**   | Importa todo el rango disponible (uso puntual).           |
| **Hoy**        | Sólo el día actual. Ideal para actualizar el dashboard.   |
| **Rango**      | Fechas específicas, ideal para reprocesar semanas pasadas.|

El **Historial de Sync** registra cada ejecución con fecha/hora,
dispositivo, cantidad de registros importados y errores detectados.

### Identificación del empleado (UTC → local)

Desde ene-2026 la sincronización aplica dos mejoras importantes:

- Los *timestamps* recibidos en UTC se convierten a la **zona horaria local
  del tenant** antes de persistir (evita que una marcación de las 11 PM
  aparezca al día siguiente).
- El sistema identifica al empleado primero por **correo electrónico** y,
  si no coincide, por **documento de identidad**. Esto es más robusto que
  la identificación sólo por código interno.

### Reprocesamiento de un día o período

Desde la vista de marcaciones, botón **Reprocesar día**:

1. El sistema elimina los cálculos previos del día (no las marcaciones
   crudas).
2. Recalcula con los horarios y tolerancias **actuales**.
3. Actualiza `attendance_calculations` y `payroll_attendance_summary`.

::: {.badge-warn}
**Importante** — Use el reproceso cuando cambie tolerancias, el horario del
empleado o el tipo de un día (p. ej. al marcar retroactivamente un día como
feriado). El reproceso **no** reenvía la sincronización a Base44 — sólo
recalcula lo que ya está importado.
:::

## Cálculo automático de horas y score

El motor de cálculo (`AttendanceCalculator` + `AbsenceDetector`) determina
por cada día laborado del empleado:

- **Horas regulares** dentro de la jornada ordinaria (Art. 31 CT).
- **Horas extras** al 25 % (primeras 3 h fuera de jornada) y al 50 %
  (adicionales, domingos, feriados) según Art. 39 y 48 CT.
- **Horas nocturnas** (6 PM – 6 AM) con recargo 50 % (Art. 38 CT).
- **Tiempo de almuerzo** descontado (mínimo 30 min, Art. 35 CT).
- *Score* de **puntualidad** del día (0-100).
- **Ausencias y tardanzas**, aplicando tolerancias configuradas.

### Overrides personales (nuevo abr-2026)

Desde la v3.5.22 es posible aplicar **reglas personales por empleado**
que sobrescriben el comportamiento general en fines de semana y feriados.
Por ejemplo: un empleado de seguridad cuyo sábado es día laboral normal
(no se paga 50 %), o un empleado de turno rotativo cuyo domingo cuenta
como feriado.

Los *overrides* se configuran en el perfil del empleado → *Asistencia*.

### Estados de las ausencias

El `AbsenceDetector` marca cada ausencia con uno de tres estados:

| Estado         | Significado                                              |
|----------------|----------------------------------------------------------|
| `PENDIENTE`    | Detectada pero sin revisar por RRHH                      |
| `JUSTIFICADA`  | Aprobada con justificación (certificado médico, permiso) |
| `NO_JUSTIFICADA` | Rechazada o sin justificación válida                   |

Sólo las ausencias `NO_JUSTIFICADA` generan descuento automático y
disparan la alerta de Art. 213 CT (3+ ausencias injustificadas/mes).

### Gestión de justificaciones (nuevo mar-2026)

Desde v3.5.22 el módulo de justificaciones permite:

- **Cargar archivos** de respaldo (certificados médicos, permisos firmados,
  constancias).
- **Editar** justificaciones existentes.
- Clasificar por tipo (médica, personal, duelo, maternidad, etc.).
- Consultar el historial de justificaciones por empleado.

Desde la vista de marcaciones, al tocar una ausencia pendiente se abre el
modal de justificación con carga de archivo (PDF, imagen).

## Sistema de tolerancias

Desde la v3.5 el sistema permite configurar ocho campos de tolerancia por
horario (tabla `schedules`):

| Campo                          | Tipo    | Unidad  | Propósito                                 |
|--------------------------------|---------|---------|-------------------------------------------|
| `tolerance_entry_before`       | integer | minutos | Margen permitido al entrar antes de hora  |
| `tolerance_entry_after`        | integer | minutos | Margen permitido al entrar después de hora|
| `tolerance_exit_before`        | integer | minutos | Margen al salir antes de la hora oficial  |
| `tolerance_exit_after`         | integer | minutos | Margen al salir después de la hora oficial|
| `tolerance_lunch_start_before` | integer | minutos | Margen al iniciar el almuerzo antes       |
| `tolerance_lunch_start_after`  | integer | minutos | Margen al iniciar el almuerzo después     |
| `tolerance_lunch_end_before`   | integer | minutos | Margen al terminar el almuerzo antes      |
| `tolerance_lunch_end_after`    | integer | minutos | Margen al terminar el almuerzo después    |

El método `calculateTardinessWithTolerance` aplica estas tolerancias antes
de contar un minuto como tardanza. El método `calculateLunchWithTolerance`
descuenta el almuerzo sólo cuando la duración real excede el umbral
configurado, evitando penalizar atrasos menores.

::: {.badge-warn}
**Importante** — Las tolerancias no se aplican retroactivamente. Si modifica
un horario existente, los días ya calculados conservan su resultado; use
*Reprocesar período* para actualizarlos.
:::

Ver detalles técnicos en el documento
`documentation/attendance/TOLERANCES_SYSTEM.md` del repositorio.

## Aprobación de horas extras {#sec-aprobacion-he}

::: {.badge-new}
**Nuevo en v3.5.22**
:::

El módulo permite a los responsables de nómina revisar las horas extras
generadas automáticamente por el sistema de asistencias y decidir cuáles
serán pagadas en planilla. Sólo las horas extras con estado **APROBADO**
son consideradas por las fórmulas `HORAS_EXTRAS_APROBADAS()`,
`HORAS_EXTRAS_APROBADAS_25()` y `HORAS_EXTRAS_APROBADAS_50()`.

### Requisitos previos

- Asistencias sincronizadas y procesadas.
- Empleados con el campo **Permite horas extras** habilitado en su perfil.
- Horarios con tolerancias configuradas (entrada/salida/almuerzo) para que
  el cálculo sea preciso.
- Permiso de aprobación asignado al usuario en el sistema de roles.

### Pantalla principal

Al ingresar a **Asistencias → Aprobación de Horas Extras** se muestran
cuatro tarjetas de estadísticas en la parte superior:

| Tarjeta             | Descripción                                                |
|---------------------|------------------------------------------------------------|
| HE pendientes       | Total de horas extras aún no revisadas y cantidad de registros. |
| HE aprobadas        | Total de horas extras ya aprobadas para pago.              |
| HE 25 % pendientes  | Subtotal de horas extras al 25 % sin revisar.              |
| HE 25 % aprobadas   | Subtotal de horas extras al 25 % ya aprobadas.             |

### Filtros disponibles

- **Estado**: *Todos* / *Pendientes* / *Aprobados* / *Rechazados* / *No aplica*.
- **Empleado**: búsqueda Select2 por nombre o código.
- **Fecha desde / hasta**: rango de fechas de los registros diarios.

Use **Filtrar** para aplicar y **Limpiar** para restablecer.

### Columnas de la tabla

| Columna          | Descripción                                                    |
|------------------|----------------------------------------------------------------|
| Código           | Identificador del empleado                                     |
| Empleado         | Nombre completo                                                |
| Horario          | Horario asignado del empleado                                  |
| Fecha            | Día del registro de asistencia                                 |
| Entrada / Salida | Hora real de marcación                                         |
| H. regulares     | Horas dentro de la jornada ordinaria                           |
| H. extras 25 %   | Horas extras con recargo 25 % (primeras 3 h)                   |
| H. extras 50 %   | Horas extras con recargo 50 % (adicionales o domingos/feriados)|
| Total HE         | Suma 25 % + 50 %                                               |
| Estado           | `PENDING` / `APPROVED` / `REJECTED` / `NOT_APPLICABLE`         |
| Acciones         | Ver detalle, Aprobar, Rechazar                                 |

### Flujo de aprobación

1. Localice el registro en la tabla (use filtros si es necesario).
2. Haga clic en **Aprobar** (icono de palomita verde).
3. Se abre un modal con el resumen del registro: empleado, fecha, horas
   25 % y 50 %.
4. Escriba notas opcionales (justificación, autorización verbal, etc.).
5. Confirme con **Aprobar**. El estado cambia a `APPROVED`.

Las horas aprobadas quedan disponibles para las fórmulas
`HORAS_EXTRAS_APROBADAS_25()` y `HORAS_EXTRAS_APROBADAS_50()` al procesar
planillas (véase §4.3).

### Flujo de rechazo

1. Haga clic en **Rechazar** (icono de X roja).
2. Ingrese el motivo del rechazo en el modal (campo obligatorio).
3. Confirme. El estado cambia a `REJECTED` y el registro no se incluye en
   planilla.

### Ver detalle del registro

El botón de información abre un modal con el detalle completo:

- Hora exacta de entrada y salida.
- Tiempo de almuerzo registrado.
- Desglose de horas: regulares, nocturnas, extras 25 %, extras 50 %,
  feriados, dominicales.
- *Score* de puntualidad del día.
- Historial de cambios de estado (quién aprobó/rechazó y cuándo).

### Estados posibles

| Estado           | Significado                       | Efecto en planilla                                |
|------------------|-----------------------------------|---------------------------------------------------|
| `PENDING`        | Calculado, sin revisión           | No se paga (fórmulas aprobadas lo ignoran)        |
| `APPROVED`       | Revisado y autorizado             | Se incluye en `HORAS_EXTRAS_APROBADAS()`          |
| `REJECTED`       | Revisado y denegado               | No se paga                                        |
| `NOT_APPLICABLE` | Empleado no elegible o sin extras | No aplica                                         |

### Integración con planillas

Para que las horas extras aprobadas se reflejen en el pago, los conceptos
de planilla deben usar las funciones específicas de horas aprobadas:

```
HORAS_EXTRAS_APROBADAS_25() * (SUELDO / 220) * 1.25   -- pago HE 25 % aprobadas
HORAS_EXTRAS_APROBADAS_50() * (SUELDO / 220) * 1.50   -- pago HE 50 % aprobadas
HORAS_EXTRAS_APROBADAS()                              -- total aprobadas (25 + 50)
```

Si su empresa usa `HORAS_EXTRAS_25()` sin la palabra *APROBADAS*, se pagan
**todas** las horas sin importar el estado de aprobación.

### Checklist de cierre

- [ ] Sincronizar asistencias antes de revisar horas extras del período.
- [ ] Revisar todos los registros en estado `PENDING` antes de procesar la planilla.
- [ ] Documentar motivo al rechazar para respaldo y auditoría.
- [ ] Verificar que los conceptos de planilla usen `HORAS_EXTRAS_APROBADAS_*`
      si el flujo de aprobación está activo.

## Alertas y cumplimiento legal (Panamá)

El componente `AlertsSystem` detecta y notifica incumplimientos legales y
situaciones que requieren atención del área de RRHH. Las alertas se
muestran en el Dashboard (top 5) y en el módulo dedicado de alertas.

### Tipos de alerta

| Tipo                               | Disparador                                  |
|------------------------------------|---------------------------------------------|
| Jornada > 48 h semanales           | Sumatoria semanal excede el límite legal.   |
| Horas extras > 3 h (25 %)          | Un día con más de 3 h extras al 25 %.       |
| Ausencias injustificadas ≥ 3/mes   | Art. 213 CT (causal de despido).            |
| Falta de marcación de salida       | Empleado entró pero no marcó salida.        |
| Almuerzo < 30 min                  | Violación al Art. 35 CT.                    |
| Trabajo en feriado sin recargo     | Feriado no detectado por el calendario.     |
| Horas nocturnas no pagadas         | Turno nocturno sin recargo del 50 %.        |
| Licencia próxima a vencer          | Certificado/permiso que expira < 30 días.   |
| Score de puntualidad < 60          | Empleado con baja puntualidad recurrente.   |
| Descuadre entre marcaciones y cálculo | Inconsistencia detectada al procesar.    |

### Niveles de severidad

- **Informativa**: para conocimiento, no bloquea procesos.
- **Advertencia**: requiere revisión antes del cierre de planilla.
- **Crítica**: bloquea el cierre hasta que se atienda o justifique.

### Ciclo de vida de una alerta

1. **Detectada** automáticamente tras cada sincronización.
2. **Revisada** por el responsable (RRHH o supervisor).
3. **Resuelta**: se justifica, se corrige o se escala.
4. **Archivada**: queda en el historial.

## Reportes de marcaciones

Disponible en *Asistencia → Reportes → Marcaciones* (ruta
`/panel/attendance/reports`).

### Formatos disponibles

| Formato   | Descripción                                            |
|-----------|--------------------------------------------------------|
| Vista web | Reporte interactivo en pantalla con filtros dinámicos. |
| Excel     | Archivo descargable con formato profesional.          |
| JSON      | Endpoint API para integración con otros sistemas.     |

### Estadísticas incluidas (8 métricas principales)

| Métrica                 | Descripción                                                |
|-------------------------|------------------------------------------------------------|
| Total marcaciones       | Cantidad total de registros de entrada/salida del período. |
| Marcaciones a tiempo    | Empleados que cumplieron horario sin tardanzas.            |
| Tardanzas               | Total de llegadas tarde registradas.                       |
| Ausencias               | Días sin marcaciones (justificadas y no justificadas).     |
| Horas trabajadas        | Total de horas regulares laboradas.                        |
| Horas extras 25 %       | Primeras 3 h adicionales con recargo del 25 %.             |
| Horas extras 50 %       | Horas adicionales (4ª en adelante) o dominicales/feriados. |
| Score puntualidad       | Promedio general de puntualidad del período (0-100).       |

### Top 10 de tardanzas

Ranking de los 10 empleados con mayores tardanzas del período. Por cada
uno muestra:

- Datos del empleado: ID, cédula, nombre completo, departamento, cargo.
- Detalle de la tardanza: fecha, hora de entrada programada y real.
- Métricas: minutos de tardanza, horas trabajadas, estado
  (justificada/no justificada).

### Detalle por departamento

Desglose completo de marcaciones agrupado por estructura departamental:

- ID empleado, cédula, nombre, cargo.
- Fecha de cada marcación.
- Entrada/salida (horas reales vs. programadas).
- Horas trabajadas y minutos de tardanza.
- Estado del día (A tiempo / Tardanza / Ausencia).

### Filtros del reporte

| Filtro         | Opciones                                              |
|----------------|-------------------------------------------------------|
| Período        | Fecha inicio y fin (rango personalizado).             |
| Departamento   | Filtro por área organizacional específica.            |
| Empleado       | Búsqueda individual por código o nombre.              |
| Tipo marcación | Todas / A tiempo / Tardanzas / Ausencias.             |

## Kiosko de marcaciones

Acceso rápido desde la barra superior → botón **Marcaciones**.

- Permite registrar entrada/salida manual vinculada al código de empleado.
- Requiere horario asignado y situación activa.
- Útil en oficinas sin reloj biométrico o como respaldo cuando la API
  Base44 está caída.

Las marcaciones del kiosko entran a la misma tabla `attendance_records` y
pasan por el mismo `AttendanceCalculator`, así que son indistinguibles
para el resto del sistema.

## Integración con planillas

Los datos de asistencias se integran automáticamente con el módulo de
planillas por tres vías:

1. **Motor de fórmulas**: 19 funciones de asistencias disponibles para
   definir conceptos. Ver catálogo completo en §4.3 y **Apéndice B**.
2. **Resumen por período**: la tabla `payroll_attendance_summary`
   consolida estadísticas por empleado y período, lista para consumo
   rápido.
3. **Regeneración de planilla**: al regenerar un empleado, el sistema
   recalcula automáticamente todos los conceptos que dependen de
   asistencias.

### Funciones más utilizadas

| Función              | Uso típico                                |
|----------------------|-------------------------------------------|
| `HORAS_TRABAJADAS()` | `HORAS_TRABAJADAS() * TARIFA_HORA`        |
| `HORAS_EXTRAS_25()`  | `HORAS_EXTRAS_25() * (SUELDO/220) * 1.25` |
| `HORAS_EXTRAS_50()`  | `HORAS_EXTRAS_50() * (SUELDO/220) * 1.50` |
| `TARDANZAS()`        | `TARDANZAS() / 60 * (SUELDO/220)`         |
| `SCORE_PUNTUALIDAD()`| `SI(SCORE_PUNTUALIDAD() >= 95, 100, 0)`   |
| `AUSENCIAS()`        | `AUSENCIAS() * (SUELDO/30)`               |

## Checklist de operación

- [ ] Todos los empleados activos tienen horario y situación correcta.
- [ ] Dispositivos y API con credenciales válidas y prueba de conexión
      exitosa.
- [ ] Calendario empresarial cargado con feriados del año en curso.
- [ ] Sincronización programada (cron) activa e historial sin errores.
- [ ] Antes de enviar a planilla: reprocesar el día si se ajustaron
      tolerancias u horarios.
- [ ] Revisar el reporte de marcaciones y justificar ausencias antes del
      cierre.
- [ ] Validar top 10 de tardanzas y gestionar excepciones antes de
      aplicar descuentos.
- [ ] Si usa el flujo de aprobación, revisar y aprobar/rechazar todos los
      registros `PENDING` del período.
- [ ] Verificar que los conceptos de planilla basados en asistencias
      calculen correctamente en un empleado piloto antes del cierre
      masivo.
