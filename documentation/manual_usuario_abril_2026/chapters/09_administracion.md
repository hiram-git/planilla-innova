# Administración del sistema

Este capítulo agrupa la configuración que hace el administrador de la
empresa (§9.1-9.3) y las funciones exclusivas del super administrador
del sistema en el Panel Backoffice (§9.4-9.5).

## Configuración de empresa

Desde **Configuración → Empresa**.

### Datos generales

| Campo           | Uso                                                          |
|-----------------|--------------------------------------------------------------|
| Nombre legal    | Razón social; aparece en reportes PDF.                       |
| Nombre comercial| Nombre de marca (puede diferir del legal).                   |
| RUC / NIT       | Identificador fiscal.                                        |
| Dirección       | Domicilio fiscal.                                            |
| Teléfono, correo| Contacto institucional.                                      |
| Moneda          | Símbolo y código ISO (PAB, USD, etc.).                       |
| Logo principal  | Usado en encabezados de reportes.                            |
| Logo secundario | Opcional, para comprobantes o firmas.                        |
| Firmante 1 / 2  | Nombre, cargo y firma digital para reportes.                 |
| Zona horaria    | Zona del tenant (afecta la conversión UTC → local en asistencias). |

::: {.badge-warn}
**Importante** — Verifique el símbolo de moneda antes de generar reportes.
Cambiarlo después de cerrar planillas no modifica los reportes ya emitidos.
:::

## Usuarios, roles y permisos granulares

Desde **Configuración → Usuarios** y **Configuración → Roles**.

### Crear usuario

| Campo              | Descripción                                           |
|--------------------|-------------------------------------------------------|
| Nombre completo    | Nombre visible del usuario.                           |
| Correo             | Usado para notificaciones y recuperación de contraseña.|
| Usuario (username) | Identificador único para el login.                    |
| Contraseña         | Temporal; el usuario debe cambiarla al primer ingreso.|
| Rol                | Uno de los roles definidos en el catálogo de roles.   |
| Estado             | Activo / Inactivo.                                    |

### Roles

Los roles definen **qué módulos** puede usar cada usuario. El sistema
trae roles predeterminados pero pueden crearse personalizados:

| Rol (sugerido)  | Uso típico                                                |
|-----------------|-----------------------------------------------------------|
| Super Admin     | Acceso completo dentro de la empresa.                     |
| Administrador   | Planillas, empleados, conceptos, reportes.                |
| Operador        | Consulta y operaciones acotadas.                          |
| Sólo lectura    | Consulta de reportes y listados.                          |

::: {.badge-info}
**Super admin de empresa vs. Super admin de sistema** — El "Super Admin"
del rol de empresa (arriba) tiene acceso total dentro de **su tenant**, pero
no al Panel Backoffice. El Super Admin del sistema (`is_system_admin = true`)
es otra cosa y se documenta en §9.4.
:::

### Permisos granulares (v3.5.13)

Cada rol se compone de **permisos granulares por módulo**:

| Permiso     | Significado                                      |
|-------------|--------------------------------------------------|
| Lectura     | Ver el módulo y sus listados.                    |
| Creación    | Crear nuevos registros.                          |
| Actualización| Editar registros existentes.                    |
| Eliminación | Borrar registros.                                |
| Procesar    | Acciones especiales (calcular planilla, aprobar, etc.) |

El sistema aplica los permisos de tres formas complementarias:

1. **Menú lateral**: sólo muestra los 23 módulos donde el usuario tiene
   permiso de **lectura**.
2. **Secciones agrupadas**: si el usuario no tiene permiso sobre ningún
   módulo de una sección, la sección entera se oculta.
3. **Helper `canAccessRoute()`**: cada ruta verifica el permiso antes de
   responder; si no, redirige al dashboard con mensaje de error.

Para restricciones estrictas, defina un rol "Auditor" con sólo permiso
de **lectura** en los módulos de reportes y listados.

## Calendario empresarial

Desde **Configuración → Calendario Empresarial**. Tabla
`business_calendar` con ~731 registros precargados para 2024-2025 y 28
feriados nacionales panameños.

### Tipos de día

| Tipo             | Ejemplos                                              |
|------------------|-------------------------------------------------------|
| `LABORAL`        | Lunes-viernes comunes.                                |
| `NO_LABORAL`     | Sábados y domingos por defecto.                       |
| `FERIADO`        | 25-Dic, 1-Ene, días patrios (recargo 50 %).           |
| `DUELO`          | Duelos nacionales decretados.                         |
| `ESPECIAL`       | Días con reglas particulares (medio día, recuperable).|

Cada día además puede marcarse como **pagado** (`is_paid_holiday`) para
indicar que el empleado recibe pago aun sin trabajar.

### Vista del calendario

Interfaz gráfica con **FullCalendar.js**:

- Vista mensual, semanal y anual.
- Código de color por tipo de día.
- Click en un día abre el detalle y permite editarlo.

### Inicializar un año nuevo

Para preparar el calendario de un año siguiente, hay dos opciones:

1. **Script CLI** (`BusinessCalendar::initializeYear(year)`) desde el
   servidor.
2. **Sincronización con la API** (`CalendarSyncService`, ~500 líneas)
   que obtiene los feriados desde Base44 y los carga automáticamente,
   incluyendo la bandera `is_paid_holiday` para feriados pagados.

### Impacto en otros módulos

El calendario se consulta automáticamente en:

- **Asistencias** (§3): clasifica cada día como LABORAL/FERIADO para
  calcular horas con el recargo correcto.
- **Vacaciones** (§6): valida que las solicitudes no solapen con
  feriados nacionales.
- **Planillas especiales**: conceptos condicionales por tipo de día.
- **Alertas legales**: detecta trabajo en feriado sin recargo.

## Parámetros del catálogo

Desde **Configuración → Parámetros**:

| Catálogo              | Descripción                                         |
|-----------------------|-----------------------------------------------------|
| Tipos de planilla     | Regular, XIII, Vacaciones, Liquidación, Especial.   |
| Frecuencias           | Semanal, quincenal, mensual, anual, etc.            |
| Situaciones laborales | Activo, Inactivo, Suspendido, Licencia, etc.        |
| Tipos de acumulado    | XIII, vacaciones, prima de antigüedad, etc.         |

Cada catálogo permite CRUD completo. **Eliminar** un parámetro en uso
por empleados o planillas se bloquea para evitar datos huérfanos.

## Licencia del tenant

El dropdown en la barra superior muestra:

- Empresa (nombre comercial).
- RUC / identificador fiscal.
- Clave de licencia (opcional).
- Fecha de expiración.
- Días restantes.

Código de colores:

| Color    | Condición                                       |
|----------|-------------------------------------------------|
| Verde    | Mayor o igual a 30 días restantes.              |
| Amarillo | Entre 7 y 29 días restantes.                    |
| Rojo     | Menos de 7 días restantes.                      |
| "Expirada" | Licencia vencida (bloquea acceso).            |

Si la licencia expira, todos los usuarios quedan bloqueados **excepto
super administradores del sistema** (§9.4), que pueden entrar para
renovarla.

## Panel Backoffice multi-tenant

::: {.badge-new}
**Nuevo en v3.5.22**
:::

El Panel Backoffice es el acceso de administración del **sistema
multi-tenant**. Desde aquí se gestionan todas las empresas (tenants)
registradas en la plataforma, se configuran sus parámetros, se monitorea
el estado del sistema y se realizan operaciones administrativas que no
están disponibles dentro del panel de cada empresa individual.

::: {.badge-warn}
**Acceso restringido** — Este panel es exclusivo para usuarios con
`is_system_admin = true`. Los administradores de empresa normales **no
tienen acceso** a estas funciones.
:::

### Acceso al Backoffice

1. Ingresar a la URL del backoffice: `/backoffice/login`.
2. Autenticarse con las credenciales de Super Administrador del sistema
   (son **independientes** de los usuarios de cada empresa).
3. Al ingresar correctamente se redirige al *Dashboard del Backoffice*.

El login del backoffice es **diferente** al login del panel de empresa
(`/panel`). No comparten sesión.

Desde mar-2026 existe un middleware específico (`BackofficeAuthMiddleware`)
que protege todas las rutas del backoffice — sólo sesiones autenticadas
como super admin del sistema pueden acceder.

### Dashboard del Backoffice

La pantalla principal muestra el resumen global:

- Total de empresas (tenants) registradas.
- Empresas activas vs. inactivas.
- Últimos accesos por empresa.
- Alertas del sistema o errores detectados.
- Licencias próximas a vencer en todos los tenants.

### Gestión de empresas (tenants)

| Acción              | Descripción                                                        |
|---------------------|--------------------------------------------------------------------|
| Ver listado         | Tabla con todas las empresas: nombre, subdominio, estado, BD.      |
| Crear empresa       | Wizard paso-a-paso (ver siguiente sección).                        |
| Editar empresa      | Modificar parámetros de conexión, logo, nombre, estado.            |
| Activar / Desactivar| Suspender o reactivar acceso sin eliminar datos.                   |
| Migraciones         | Ejecutar migraciones pendientes en la BD del tenant.               |
| Impersonar          | Entrar como esa empresa para diagnóstico (acción auditada).        |

### Wizard de creación de empresa

Al crear una empresa nueva el sistema guía paso a paso:

1. **Datos de la empresa**: nombre legal, nombre comercial, RUC, país.
2. **Configuración de acceso**: subdominio o identificador único, logo.
3. **Base de datos**: seleccionar MySQL o PostgreSQL, ingresar host,
   puerto, credenciales y nombre de la BD.
4. **Usuario administrador**: crear el primer usuario administrador del
   tenant (email y contraseña inicial).
5. **Confirmación**: el sistema **prueba la conexión** y ejecuta las
   migraciones iniciales automáticamente.

Si la prueba de conexión falla, el wizard permite corregir las
credenciales antes de continuar.

### Soporte multi-base de datos

INNOVA Planillas soporta dos motores por tenant:

| Motor        | Cuándo usarlo                                                       |
|--------------|---------------------------------------------------------------------|
| **MySQL**    | Instalaciones estándar en Laragon, XAMPP, servidores Linux.         |
| **PostgreSQL** | Empresas con política interna de usar PostgreSQL o integración. |

La configuración se guarda por tenant y el sistema resuelve
automáticamente qué adaptador usar en cada petición. El archivo
`.env.pgsql.example` del repositorio muestra la configuración para
PostgreSQL.

## Gestión de super administradores del sistema

Los Super Administradores del sistema se identifican por el flag
`is_system_admin = true` en la tabla de administradores del sistema
central.

### Características

- **Acceso a todas las empresas** registradas.
- Pueden **impersonar** (entrar como) cualquier empresa para diagnóstico
  o soporte técnico.
- Sus acciones **quedan registradas** en el log del sistema para
  auditoría.
- **No bloqueo por licencia vencida**: pueden entrar aun si la licencia
  del tenant expiró (para renovarla).

### Alta de super administrador

Un super admin sólo puede crearse desde la base de datos directamente o
siendo promovido por otro super admin ya existente. No hay UI pública de
registro para este rol (medida de seguridad).

## Migraciones multi-tenant

Cuando se lanza una actualización del sistema con cambios en la base de
datos:

1. El Super Admin accede a **Backoffice → Migraciones**.
2. Se muestra la lista de tenants con el estado de migraciones
   pendientes.
3. Puede ejecutar la migración en **un tenant individual** o en **todos
   a la vez**.
4. El resultado de cada migración (éxito/error) se muestra en pantalla
   y se guarda en el log.

El sistema `TenantMigrationSystem` se asegura de que cada tenant tenga
su esquema al día independientemente de los demás.

## Resolución de problemas frecuentes (backoffice)

| Problema                              | Solución                                                                       |
|---------------------------------------|--------------------------------------------------------------------------------|
| Empresa no puede iniciar sesión       | Verificar que el tenant esté activo y las credenciales de BD sean correctas.   |
| Error de conexión a BD del tenant     | Editar el tenant y verificar host, puerto, BD, usuario. Usar "Probar conexión".|
| Migraciones pendientes                | Ejecutar desde Backoffice → Migraciones para ese tenant.                       |
| Super Admin no puede acceder          | Verificar que tenga `is_system_admin = true` en la tabla central.              |
| Licencia vencida pero no expirable    | Actualizar `license_expires_at` del tenant desde Backoffice → Editar.          |

## Checklist de operación (backoffice)

- [ ] Al crear una empresa nueva, **verificar que la conexión a su BD
      sea exitosa** antes de finalizar el wizard.
- [ ] **Ejecutar migraciones pendientes** antes de notificar al cliente
      que puede acceder tras una actualización.
- [ ] **Nunca compartir** las credenciales de Super Admin con
      administradores de empresa.
- [ ] **Revisar periódicamente** el log de accesos del backoffice para
      detectar accesos no autorizados o patrones inusuales.
- [ ] Mantener **respaldos regulares** de la BD central (licencias,
      tenants) además de las BD de cada tenant.

## Checklist de operación (administración del tenant)

- [ ] Configurar moneda y logos antes de generar reportes.
- [ ] Roles revisados: cada usuario tiene sólo los permisos requeridos.
- [ ] Calendario empresarial actualizado cada año nuevo.
- [ ] Tipos de planilla y frecuencias activos y usados consistentemente.
- [ ] Licencia revisada: si queda menos de 30 días, gestionar renovación.
