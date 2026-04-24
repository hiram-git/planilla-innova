# Gestión de personal

Este capítulo documenta el mantenimiento del catálogo de empleados y la
estructura organizacional sobre la que se procesan las planillas.

## Empleados: alta, edición y baja

### Requisitos previos

Antes de dar de alta un empleado, deben existir:

- Catálogos organizativos: posición (empresas públicas) o
  cargo + partida + función (empresas privadas).
- Al menos un **horario** creado (§2.6).
- Los tipos de **situación** laboral (activo, inactivo, suspendido).
- Al menos un **tipo de planilla** al que asignar al empleado.

### Crear empleado

Desde **Empleados → Nuevo Empleado**:

#### Datos básicos

| Campo           | Obligatorio | Notas                                                  |
|-----------------|-------------|--------------------------------------------------------|
| Código empleado | Sí          | Único; se usa en deducciones y asistencia.             |
| Nombres         | Sí          | Texto libre.                                           |
| Apellidos       | Sí          | Texto libre.                                           |
| Documento       | Sí          | Cédula o pasaporte; validar formato local.             |
| Fecha nacimiento| Sí          |                                                        |
| Fecha ingreso   | Sí          | Base para antigüedad y acumulados.                     |
| Correo          | No          | Usado también por la API de marcaciones Base44 (§3.1). |
| Foto            | No          |                                                        |

#### Información organizacional

Según el tipo de empresa:

- **Pública**: seleccionar **Posición** (con enlace al organigrama).
- **Privada**: seleccionar **Cargo**, **Partida** y **Función**.

#### Horario y situación

- **Horario**: obligatorio si el empleado marca asistencia.
- **Situación**: Activo / Inactivo / Suspendido (afecta filtros y cálculo
  de planillas).

#### Contrato

| Campo         | Notas                                                         |
|---------------|---------------------------------------------------------------|
| Tipo contrato | Indefinido / Definido / Proyecto / Temporal.                  |
| Número        |                                                               |
| Fecha inicio  | Obligatorio.                                                  |
| Fecha fin     | **Obligatorio si el tipo no es "Indefinido"**.                |

#### Forma de pago

- **Efectivo**: no pide datos adicionales.
- **Cheque** / **ACH**: solicita banco, número de cuenta y tipo de cuenta.
  El formulario muestra u oculta estos campos automáticamente según la
  selección.

#### Salario

- **Salario individual**: base para todos los cálculos y acumulados.
- **Gastos de representación**: opcional, monto adicional que puede usarse
  en fórmulas dedicadas.

#### Banderas de comportamiento (nómina y asistencia)

Tres casillas de verificación ajustan cómo procesa el sistema al empleado:

+----------------------------+--------------------------+------------------------------------------------+
| Bandera                    | Campo BD                 | Efecto                                         |
+============================+==========================+================================================+
| Marca asistencia           | `marca_asistencia`       | Si está activa, el empleado entra en la        |
|                            |                          | sincronización de marcaciones (§3.1).          |
+----------------------------+--------------------------+------------------------------------------------+
| Permite horas extras       | `permite_horas_extras`   | Habilita al empleado para el flujo de          |
|                            |                          | aprobación de horas extras (§3.4).             |
+----------------------------+--------------------------+------------------------------------------------+
| Tiene bono de asistencia   | `tiene_bono_asistencia`  | Desde v3.5 (ene-2026): marca al empleado       |
|                            |                          | como candidato a bonos atados al *score* de    |
|                            |                          | puntualidad. Las fórmulas pueden leer esta     |
|                            |                          | bandera para aplicar el bono.                  |
+----------------------------+--------------------------+------------------------------------------------+

#### Validaciones que aplica el sistema al guardar

- Código de empleado único.
- Campos obligatorios completos.
- Coherencia entre forma de pago y datos bancarios.
- Fecha fin presente si el contrato no es indefinido.

### Edición

Desde el listado principal de empleados:

- Búsqueda por nombre, código, documento, cargo o posición.
- Acciones por fila: **Ver detalle**, **Editar**, **Desactivar**.
- Al editar, los mismos campos de creación aparecen pre-poblados. Los
  prefijos `edit_` en los nombres internos son un detalle del formulario
  (ej. `edit_marca_asistencia`), irrelevante para el usuario final.

### Baja / desactivación

No se elimina físicamente al empleado — se cambia su **situación** a
*Inactivo* o *Suspendido*. El empleado queda oculto de los filtros por
defecto pero su historial permanece disponible.

La vista **Empleados dados de baja** permite consultar y, de ser necesario,
reactivar cuentas dadas de baja previamente.

## Importación masiva desde Excel

Desde **Empleados → Importar desde Excel**:

### Flujo

1. Descargue la **plantilla** (`Importar desde Excel → Descargar plantilla`).
2. Complete las columnas obligatorias en Excel.
3. Cargue el archivo. El sistema valida y muestra un reporte antes de
   insertar (filas con errores se listan, las correctas se procesan).

### Columnas obligatorias

- Código, nombres, apellidos, documento.
- Fecha de ingreso, salario.
- Horario, situación, tipo de planilla.

### Columnas opcionales

- Correo electrónico.
- `marca_asistencia`, `permite_horas_extras`, `tiene_bono_asistencia`
  (valores 0 o 1).
- Datos bancarios (banco, cuenta, tipo de cuenta).
- Contrato (tipo, número, fechas).

### Validaciones

- Rechazo de códigos duplicados dentro del archivo o contra BD.
- Formato de fecha `YYYY-MM-DD`.
- Salario numérico.
- Horario y tipo de planilla deben existir previamente en los catálogos.

## Campos adicionales personalizados

::: {.badge-new}
**Nuevo en v3.5.19**
:::

Cada empresa puede definir **campos personalizados** que aparecerán
automáticamente en los formularios de alta y edición de empleados. Los
campos se almacenan en tablas propias, no modifican la estructura de la
tabla `employees`.

### Tipos de dato soportados

| Tipo      | Descripción                             | Validación              |
|-----------|-----------------------------------------|-------------------------|
| `TEXTO`   | Cadena de texto libre                   | -                       |
| `NUMERO`  | Valores numéricos (enteros o decimales) | Sólo numérico           |
| `FECHA`   | Fechas                                  | Formato `YYYY-MM-DD`    |
| `BOOLEAN` | Sí / No                                 | 0/1 o true/false        |

### Crear un campo adicional

Desde **Empleados → Campos Adicionales → + Nuevo Campo**:

| Campo             | Descripción                                                           |
|-------------------|-----------------------------------------------------------------------|
| Nombre del campo  | Identificador interno, sin espacios (ej. `licencia_conducir`).        |
| Etiqueta          | Nombre visible en formularios (ej. "Licencia de Conducir").           |
| Tipo de dato      | TEXTO, NUMERO, FECHA, BOOLEAN.                                        |
| Valor por defecto | Se asigna automáticamente a empleados nuevos (opcional).              |
| Obligatorio       | Si está marcado, no permite guardar sin valor.                        |

Una vez creado, el campo aparece en la sección *Campos Personalizados* al
final del formulario de empleado.

### Restricciones

- El **nombre interno** no se puede repetir.
- El **tipo de dato no se puede modificar** después de creado (evita
  inconsistencias con valores ya capturados).
- **Al eliminar un campo adicional, se eliminan todos los valores
  asociados** de todos los empleados (requiere confirmación).

### Casos de uso frecuentes

| Caso                 | Tipo    | Nombre sugerido     |
|----------------------|---------|---------------------|
| Contacto de emergencia | TEXTO  | `contacto_emergencia` |
| Grupo sanguíneo      | TEXTO   | `grupo_sanguineo`   |
| Número de hijos      | NUMERO  | `numero_hijos`      |
| Talla de uniforme    | TEXTO   | `talla_uniforme`    |
| Vencimiento licencia | FECHA   | `venc_licencia`     |
| ¿Posee vehículo?     | BOOLEAN | `tiene_vehiculo`    |
| Nivel de inglés      | TEXTO   | `nivel_ingles`      |

## Expedientes del empleado

::: {.badge-new}
**Nuevo en v3.5.16**
:::

El módulo de expedientes organiza documentos del empleado en **13 categorías**
predefinidas (un catálogo común a todas las empresas) con **68 subcategorías**
en total — 81 tipos de documento clasificados.

### Categorías disponibles

| # | Categoría                     | Subcategorías |
|---|-------------------------------|---------------|
| 1 | Estudios Académicos           | 13            |
| 2 | Capacitación y Desarrollo     | 5             |
| 3 | Permisos                      | 10            |
| 4 | Licencias                     | 13            |
| 5 | Certificaciones Profesionales | 6             |
| 6 | Documentos Legales            | 8             |
| 7 | Evaluaciones de Desempeño     | 4             |
| 8 | Documentos Médicos            | 6             |
| 9 | Sanciones y Amonestaciones    | 4             |
|10 | Reconocimientos y Premios     | 3             |
|11 | Vacaciones                    | 3             |
|12 | Seguridad y Salud Ocupacional | 5             |
|13 | Otros Documentos              | 1             |

Algunos ejemplos de subcategorías frecuentes:

- **Estudios académicos**: Primaria, Secundaria, Bachillerato, Técnico,
  Universitario, Maestría, Doctorado, Diplomado, Idiomas…
- **Licencias**: Licencia médica, Maternidad, Paternidad, Sin goce de
  sueldo, Por duelo, Por matrimonio…
- **Documentos legales**: Contrato de trabajo, Adendas, Terminación,
  Confidencialidad, Poderes, Autorizaciones…
- **Documentos médicos**: Certificado médico, Examen pre-empleo, Examen
  periódico, Incapacidad, Recetas…

### Cargar un documento al expediente

Desde el perfil del empleado, pestaña **Expedientes**:

1. Seleccionar **+ Nuevo Documento**.
2. Elegir **categoría** (13 opciones) y luego **subcategoría**.
3. Adjuntar el archivo (PDF, imagen o Word).
4. Escribir descripción y fechas relevantes (emisión, vencimiento).
5. Guardar.

Los archivos se almacenan en la carpeta del tenant (aislamiento multi-tenant).

## Estructura organizacional

### Catálogos organizativos

- **Posiciones** (empresas públicas): jerarquía por puesto, enlazada al
  organigrama.
- **Cargos, Partidas, Funciones** (empresas privadas): catálogos
  económico-funcionales asociados al empleado.
- **Organigrama**: estructura visual; se enlaza desde la ficha del
  empleado (opcional).

### Flujo recomendado

1. Crear primero los catálogos base (cargos o posiciones).
2. Crear partidas y funciones (si aplica).
3. Definir el organigrama.
4. Asignar al empleado.

## Horarios y tolerancias

Un **horario** define la jornada del empleado: hora de entrada, hora de
salida, duración del almuerzo y los márgenes de tolerancia que el sistema
aplica al calcular tardanzas.

### Crear un horario

Desde **Horarios → Nuevo**:

| Campo                  | Obligatorio | Uso                                             |
|------------------------|-------------|-------------------------------------------------|
| Código                 | Sí          | Único (ej. `H08-17`, `ADM-DIURNO`).             |
| Nombre                 | Sí          | Descripción corta.                              |
| Hora entrada / salida  | Sí          | Base para tardanza y horas trabajadas.          |
| Almuerzo (salida/entrada) | No       | Si se usa, impacta cálculo de horas netas.      |
| Tolerancias (8 campos) | Recomendado | Ver sección siguiente.                          |

### Sistema de tolerancias

Desde la v3.5 cada horario soporta **8 tolerancias** independientes, en minutos:

| Campo                          | Significado                                       |
|--------------------------------|---------------------------------------------------|
| `tolerance_entry_before`       | Margen permitido al entrar antes de la hora       |
| `tolerance_entry_after`        | Margen permitido al entrar después de la hora     |
| `tolerance_exit_before`        | Margen al salir antes de la hora oficial          |
| `tolerance_exit_after`         | Margen al salir después de la hora oficial        |
| `tolerance_lunch_start_before` | Margen al iniciar el almuerzo antes               |
| `tolerance_lunch_start_after`  | Margen al iniciar el almuerzo después             |
| `tolerance_lunch_end_before`   | Margen al terminar el almuerzo antes              |
| `tolerance_lunch_end_after`    | Margen al terminar el almuerzo después            |

Un empleado que llega dentro de `tolerance_entry_after` no genera registro
de tardanza. El detalle del cálculo con tolerancias se documenta en §3.3.

### Buenas prácticas

- Usar códigos de horario **cortos y consistentes** (ej. `H08-17` = entra 8:00,
  sale 17:00).
- Tolerancias **realistas**: si se dejan en cero, cada minuto de variación
  cuenta como tardanza.
- Verificar que **todo empleado activo que marca asistencia tenga horario**
  antes de procesar la sincronización de marcaciones del período.

### Horarios personales (excepciones por empleado)

Además del horario general, el sistema admite **horarios personales** que
sobrescriben el general en días específicos (vacaciones activas, permisos
especiales, capacitación externa, etc.). Se gestionan desde
**Empleados → Horarios Personales**.
