# Conceptos y motor de fórmulas

Los **conceptos** son los elementos que el sistema suma o resta al procesar
una planilla: salario base, horas extras, ISR, seguro social, bonos,
descuentos, cuotas de préstamo, etc. Cada concepto puede tener un monto
fijo o calcularse con una **fórmula**.

El **motor de fórmulas** evalúa estas expresiones de forma segura, sin
recurrir a `eval()` de PHP, usando la librería `nxp/math-executor`.

## Catálogo de conceptos

Desde **Configuración → Conceptos**.

### Parámetros base

| Campo                  | Descripción                                                       |
|------------------------|-------------------------------------------------------------------|
| Código                 | Identificador único (ej. `SALARIO_BASE`, `DED_AFP`, `HE_25`).     |
| Descripción            | Nombre visible en formularios y reportes.                         |
| Tipo                   | *Asignación* (suma) o *Deducción* (resta).                        |
| Unidad                 | Monetario / Porcentaje / Horas / Unidades (ver siguiente sección).|
| Prioridad              | Orden de cálculo dentro de la planilla.                           |
| Acumulable             | Si el concepto impacta acumulados (XIII, vacaciones, prima).      |
| Monto cálculo          | Si está marcado, el valor viene de la fórmula; si no, es fijo.    |

### Unidad del concepto

La **unidad** define cómo interpretar el valor numérico del concepto:

| Unidad     | Significado                                                        |
|------------|--------------------------------------------------------------------|
| Monetario  | Monto directo en la moneda configurada (ej. $500.00).              |
| Porcentaje | Aplica % sobre una base (otro concepto o el salario base).         |
| Horas      | Usa horas calculadas (extras, nocturnas) multiplicadas por tarifa. |
| Unidades   | Conteo de unidades con valor unitario (incentivos, descuentos).    |

### Condiciones de aplicación

Cada concepto puede restringirse por:

- **Tipo de planilla**: sólo aplica en regulares, sólo en XIII, sólo en
  liquidación, etc.
- **Situación laboral**: sólo para activos, o sólo para suspendidos.
- **Fechas de vigencia**: desde/hasta.
- **Frecuencia**: cada planilla, mensual, quincenal, anual.
- **Grupo o etiqueta**: un subconjunto de empleados.

### Configuración de PDF

- Incluir o no el concepto en el comprobante de pago.
- Orden de impresión en el detalle.
- Agrupación (asignaciones vs. deducciones).
- Etiquetas descriptivas (nombres cortos y claros para que quepan bien
  en el layout).

## Motor de fórmulas V3.5.15

El motor evalúa las fórmulas de los conceptos mediante `nxp/math-executor`.
**Nunca** se usa `eval()` — es una restricción de seguridad del proyecto.

::: {.badge-warn}
**Importante** — La librería `nxp/math-executor` es una dependencia crítica
de seguridad. No la elimine ni la reemplace por `eval()`. Ver
`documentation/DEVELOPMENT_RULES.md` en el repositorio.
:::

### Variables de período

Disponibles automáticamente dentro de cualquier fórmula:

| Variable              | Significado                                        |
|-----------------------|----------------------------------------------------|
| `INIPERIODO`          | Fecha de inicio del período de la planilla actual. |
| `FINPERIODO`          | Fecha de fin del período de la planilla actual.    |
| `FECHA_LIQUIDACION`   | Fecha efectiva de la liquidación (sólo en planillas de liquidación). |

### Variables de XIII Mes

Cuando la planilla es de XIII Mes, el motor expone:

| Variable                  | Significado                                     |
|---------------------------|-------------------------------------------------|
| `INICIO_PERIODO_XIII`     | Inicio del trimestre XIII actual.               |
| `FIN_PERIODO_XIII`        | Fin del trimestre XIII actual.                  |
| `PERIODO_XIII_NUMERO`     | Número de trimestre (1, 2 o 3).                 |
| `PERIODO_XIII_ESTADO`     | Estado del período (abierto, cerrado).          |

### Variables del empleado

Dentro de la fórmula, el motor sustituye automáticamente:

- `SUELDO` / `SALARIO_BASE`: salario individual.
- `EMPLEADO`: ID del empleado actual (usado por `ACREEDOR()`).
- Cualquier concepto previo ya calculado (por código).
- Cualquier campo adicional personalizado del empleado.

### Función ACUMULADOS()

```
ACUMULADOS("TIPO")                       -- suma del tipo en el período actual
ACUMULADOS("TIPO", INICIO, FIN)          -- suma en rango explícito
ACUMULADOS("SALARIO_BASE", INICIO_PERIODO_XIII, FIN_PERIODO_XIII)
```

Devuelve la suma acumulada del tipo indicado para el empleado actual. Se
recomienda usar siempre el rango explícito para evitar ambigüedades.

### Función CONCEPTO()

Trae el resultado calculado de otro concepto del mismo empleado en el mismo
período. Respeta la prioridad definida (el concepto referenciado ya tiene
que haberse calculado).

```
CONCEPTO("LIQ005") * 0.0975        -- 9.75% del valor de LIQ005
CONCEPTO("SALARIO_BASE") - CONCEPTO("DED_AFP")
```

El motor protege contra **recursión circular**: si un concepto A invoca a
otro B que invoca a A, el motor aborta con error claro.

### Función ACREEDOR()

```
ACREEDOR(EMPLEADO, 5)                    -- monto de deducción asignada al acreedor 5
```

Devuelve el monto asignado a ese acreedor específico para el empleado
actual (ver §7.2).

### Operaciones y condicionales

El evaluador soporta `+ - * / ( )`, comparaciones (`=`, `<>`, `>`, `<`,
`>=`, `<=`), operadores lógicos (`Y`, `O`, `NO`), y la función condicional
`SI(cond, valor_si, valor_no)` estilo Excel.

## Funciones de asistencias (19 funciones)

El motor integra 19 funciones que consultan automáticamente la tabla
`attendance_calculations`. Si no hay datos para el período/empleado,
devuelven 0 (no error).

### Funciones de horas

`HORAS_TRABAJADAS()`, `HORAS_REGULARES()`, `HORAS_EXTRAS()`,
`HORAS_EXTRAS_25()`, `HORAS_EXTRAS_50()`, `HORAS_NOCTURNAS()`,
`HORAS_FERIADOS()`, `HORAS_DOMINICALES()`.

### Funciones de horas extras aprobadas

`HORAS_EXTRAS_APROBADAS()`, `HORAS_EXTRAS_APROBADAS_25()`,
`HORAS_EXTRAS_APROBADAS_50()` — consultan **sólo** registros con
`overtime_status = 'APPROVED'` (véase §3.4).

### Funciones de ausencias y tardanzas

`TARDANZAS()` (minutos), `CANTIDAD_TARDANZAS()` (días),
`AUSENCIAS()`, `TOTAL_AUSENCIAS()`, `AUSENCIAS_JUSTIFICADAS()`.

### Funciones estadísticas

`SCORE_PUNTUALIDAD()` (0-100), `DIAS_ASISTENCIA_PERFECTA()`,
`DIAS_TRABAJADOS()`.

Ver **Apéndice B** para la tabla completa con descripción y ejemplos.

## Variable UNIDAD dinámica

::: {.badge-new}
**Nuevo en v3.5.15**
:::

La **UNIDAD** es un campo de cada línea de planilla (`planilla_detalle.unidad`)
que representa cuánto se aplicó del concepto (horas trabajadas, días
acumulados, cantidad de unidades, etc.). Antes era un valor fijo del
catálogo; desde v3.5.15 puede **asignarse condicionalmente dentro de la
propia fórmula**.

### Sintaxis

Use `UNIDAD = expresión` dentro de la fórmula para asignar el valor:

```
UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)
SI(MARCA_ASISTENCIA, HORAS_REGULARES() * TARIFA_HORA, SUELDO * 0.5)
```

### Cómo lo procesa el motor

1. Evalúa la fórmula completa del concepto.
2. Si encuentra una asignación `UNIDAD = expresión`, extrae y evalúa esa
   expresión primero.
3. El valor calculado se guarda en `planilla_detalle.unidad`.
4. El resto de la fórmula usa el valor ya asignado.

::: {.badge-warn}
**Importante** — La asignación de UNIDAD debe aparecer **antes** de usarla
en cálculos posteriores dentro de la misma fórmula. El motor procesa
asignaciones en el orden en que aparecen.
:::

### Casos de uso frecuentes

| Escenario                 | Fórmula                                                                                           |
|---------------------------|---------------------------------------------------------------------------------------------------|
| Salario mixto (horas/fijo)| `UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 160)`<br>`SI(MARCA_ASISTENCIA, UNIDAD * 15, 2400)` |
| Bono asistencia perfecta  | `UNIDAD = DIAS_ASISTENCIA_PERFECTA()`<br>`SI(UNIDAD >= 20, 150, 0)`                              |
| Descuento de tardanzas    | `UNIDAD = TARDANZAS()`<br>`(UNIDAD / 60) * (SUELDO / 220)`                                        |
| Bono de puntualidad       | `UNIDAD = SCORE_PUNTUALIDAD()`<br>`SI(UNIDAD >= 95, 100, SI(UNIDAD >= 90, 50, 0))`                |
| Horas extras totales      | `UNIDAD = HORAS_EXTRAS_25() + HORAS_EXTRAS_50()`<br>`UNIDAD * (SUELDO / 220) * 1.35`              |

## Probar fórmulas antes de guardar

En el formulario de creación/edición de conceptos aparece el botón
**Probar fórmula**. Al pulsarlo:

1. El sistema solicita un empleado de prueba y un período de prueba.
2. Evalúa la fórmula con ese contexto (sin crear registros).
3. Muestra el resultado numérico y, si hubo errores, los detalla.

Es la forma recomendada de validar fórmulas complejas antes de ponerlas en
producción. Verifica también que el concepto tenga activado **Monto
cálculo** si la fórmula debe usarse.

## Conceptos manuales por empleado

::: {.badge-new}
**Nuevo en v3.5.11**
:::

Mientras que los conceptos del catálogo global aplican a todos los
empleados según sus fórmulas, los **conceptos manuales** son
personalizados: se asignan a un empleado específico con monto y
referencia únicos. Útiles para bonos ocasionales, ajustes retroactivos,
compensaciones temporales o deducciones particulares que no justifican
crear un concepto nuevo en el catálogo.

### Diferencias con conceptos del catálogo

| Aspecto           | Catálogo global       | Conceptos manuales               |
|-------------------|-----------------------|----------------------------------|
| Alcance           | Todos los empleados   | Un empleado específico           |
| Cálculo           | Fórmula automática    | Monto fijo personalizado         |
| Configuración     | Una vez en el catálogo| Uno por empleado que lo necesite |
| Trazabilidad      | Versionado del concepto| Registro individual por empleado|

### Estructura

| Campo              | Descripción                                        | Ejemplo                |
|--------------------|----------------------------------------------------|------------------------|
| Empleado           | Empleado al que aplica                             | Juan Pérez (ID: 1523)  |
| Concepto base      | Concepto del catálogo global que sirve de base     | `BNS001` (Bono especial)|
| Monto              | Valor numérico personalizado                       | 250.00                 |
| Unidad             | Cantidad opcional                                  | 1, 0.5, 2              |
| Referencia         | Nota descriptiva (máx. 255 caracteres)             | "Bono proyecto X"      |
| Estado             | Activo (1) o Inactivo (0)                          | Activo                 |

### Crear un concepto manual

Desde **Empleados → Conceptos Manuales → + Nuevo**:

1. Seleccionar el **empleado** (búsqueda Select2).
2. Seleccionar el **concepto base** del catálogo.
3. Ingresar el **monto** (decimales con punto).
4. Ingresar **unidad** y **referencia** (opcionales).
5. Marcar como **Activo** si debe aplicarse de inmediato.
6. Guardar.

::: {.badge-warn}
**Importante** — El concepto manual se aplica en planillas **futuras** o al
**regenerar** planillas existentes. No modifica planillas ya cerradas.
:::

### Integración con planillas

Al regenerar un empleado, el sistema:

1. Limpia los conceptos previos del empleado en esa planilla.
2. Aplica los conceptos del catálogo global (con sus fórmulas).
3. **Aplica los conceptos manuales activos** del empleado.
4. Recalcula totales (ingresos - deducciones).
5. Actualiza `planilla_empleado`.

### Restricciones

- No se puede **cambiar el empleado ni el concepto base** después de creado
  (para eso hay que eliminar y crear uno nuevo).
- Sólo los conceptos manuales con `estado = 1` (activo) se aplican.
- El concepto base debe existir en el catálogo al momento de aplicar.

### Casos de uso comunes

| Situación                          | Concepto base        | Monto    | Referencia                |
|------------------------------------|----------------------|----------|---------------------------|
| Bono especial por proyecto         | `BONO_PROYECTO`      | 500.00   | "Proyecto Cliente ABC"    |
| Compensación temporal de alquiler  | `COMP_ALQUILER`      | 300.00   | "Compensación 3 meses"    |
| Deducción préstamo informal        | `DED_PRESTAMO_OTRO`  | -100.00  | "Cuota préstamo personal" |
| Ajuste salarial retroactivo        | `AJUSTE_SALARIAL`    | 150.00   | "Ajuste retroactivo enero"|
| Incentivo por cumplimiento de meta | `INCENTIVO_META`     | 200.00   | "Meta ventas Q4"          |
| Bono transporte temporal           | `BONO_TRANSPORTE`    | 75.00    | "Transporte proyecto"     |

## Buenas prácticas

- **Códigos cortos y consistentes** (`SALARIO_BASE`, `DED_AFP`, `HE_25`,
  `HE_50`, `HE_NOC`).
- Separar claramente conceptos de asistencias: `HORAS_TRABAJADAS`,
  `HORAS_EXTRAS_25`, `HORAS_EXTRAS_50`, `HORAS_NOCTURNAS`,
  `DESCUENTO_TARDANZA`, `DESCUENTO_AUSENCIA`.
- **Prioridades coherentes**: bases primero → asignaciones → deducciones.
- Documentar en la descripción del concepto cuándo aplica y qué variables
  usa.
- Al usar `ACUMULADOS`, enviar siempre el **rango explícito** para evitar
  ambigüedades entre períodos.
- Si usa el flujo de aprobación de horas extras, asegurarse de usar las
  funciones `HORAS_EXTRAS_APROBADAS_*` (§3.4) y no las genéricas.

## Ejemplos rápidos

### Conceptos básicos

```
-- XIII proporcional (se calcula sobre los acumulados del trimestre XIII)
ACUMULADOS("SALARIO_BASE", INICIO_PERIODO_XIII, FIN_PERIODO_XIII) / 11

-- Horas extras 25 % (sin flujo de aprobación)
HORAS_EXTRAS_25() * (SALARIO_BASE / 240) * 1.25

-- Horas extras 25 % (con flujo de aprobación, v3.5.22)
HORAS_EXTRAS_APROBADAS_25() * (SALARIO_BASE / 240) * 1.25

-- Deducción por acreedor
ACREEDOR(EMPLEADO, 5)

-- Prima de antigüedad mensual (ejemplo, ajustar a política)
(SALARIO_BASE / 4.33) * 0.0192
```

### Con UNIDAD dinámica

```
-- Bono por score de puntualidad (registra el score como unidad)
UNIDAD = SCORE_PUNTUALIDAD()
SI(UNIDAD >= 95, 100, SI(UNIDAD >= 90, 50, 0))

-- Descuento por tardanzas (registra los minutos como unidad)
UNIDAD = TARDANZAS()
(UNIDAD / 60) * (SUELDO / 220)
```
