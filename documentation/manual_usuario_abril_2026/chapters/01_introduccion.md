# Introducción y primeros pasos

## Visión general del sistema

El Sistema de Planillas INNOVA es una plataforma empresarial para la gestión
integral de nómina bajo legislación panameña. Cubre el ciclo completo desde
la incorporación del empleado hasta su liquidación, integrando control de
asistencia vía API, motor de fórmulas dinámicas, acumulados automáticos y
exportación al ERP INNOVA.

El sistema opera en arquitectura **multi-tenant**: una misma instalación
sirve a múltiples empresas aisladas entre sí, cada una con su propia base
de datos. En la página de acceso, el usuario indica el **código de empresa**
para que el sistema enrute las credenciales al tenant correspondiente.

**Versión cubierta por este manual**: 3.5.22 (Abril 2026). Los cambios
principales de esta versión incluyen:

- Refactorización de liquidaciones (`LiquidationReportController` separado
  del CRUD).
- Panel Backoffice multi-tenant para super administradores.
- Exportación al ERP INNOVA con archivos de texto plano.
- Flujo completo de aprobación de horas extras.
- Soporte PostgreSQL junto a MySQL.
- Animaciones GSAP en múltiples vistas.

## Arquitectura multi-tenant

Desde el punto de vista del usuario, el sistema multi-tenant implica que:

1. **Cada empresa tiene su propia base de datos**. No hay riesgo de que los
   datos de una empresa sean visibles para otra.
2. **Un usuario pertenece a una empresa** (o, si es super administrador,
   puede alternar entre varias).
3. **La licencia se valida por tenant** en cada inicio de sesión: si la
   licencia de su empresa expiró, el acceso queda bloqueado (excepto para
   super administradores del sistema, que pueden seguir accediendo para
   renovarla).

La gestión avanzada del sistema multi-tenant (alta de empresas, licencias,
Panel Backoffice) se documenta en el **Capítulo 9**.

## Acceso y autenticación

### Pantalla de login

La pantalla de acceso (`/panel`) pide tres datos:

| Campo              | Obligatorio | Propósito                                    |
|--------------------|-------------|----------------------------------------------|
| Código de empresa  | Sí (tenant) | RUC o clave de la empresa (resuelve tenant)  |
| Usuario            | Sí          | Identificador de la cuenta                   |
| Contraseña         | Sí          | Credencial de la cuenta                      |

::: {.badge-info}
**Nota** — Si usted accede a la base principal (instalación "default" sin
multi-tenant), el campo *Código de empresa* puede quedar vacío.
:::

### Validaciones que realiza el sistema

Al enviar el formulario, el sistema ejecuta en orden:

1. **Protección contra fuerza bruta** — máximo 5 intentos cada 120 segundos
   por IP. Si se excede, el login queda bloqueado temporalmente.
2. **Validación de CSRF** — el formulario incluye un *token* anti-falsificación.
3. **Resolución de tenant** — busca la empresa por el código ingresado;
   si no existe o está inactiva, rechaza el login.
4. **Autenticación de credenciales** — verifica usuario + contraseña contra
   la base de datos del tenant.
5. **Validación de licencia** — consulta la licencia del tenant y calcula
   los días restantes. Si está expirada y el usuario no es super administrador,
   bloquea el acceso.

::: {.badge-warn}
**Importante** — Si la licencia está por vencer (menos de 30 días restantes),
aparece un aviso *info* en el dashboard. Si ya expiró, el acceso se bloquea
para todos los usuarios excepto super administradores.
:::

### Preservación de datos en caso de error

Si el login falla, el sistema recuerda el *usuario* y el *código de empresa*
para que no tenga que reescribirlos. La contraseña, por seguridad, siempre
se limpia.

### Cierre de sesión

El cierre de sesión está disponible en el menú desplegable de usuario
(esquina superior derecha). Al salir, el sistema limpia todas las variables
de sesión y redirige al login.

### Variables de sesión establecidas al autenticarse

Aunque es un detalle técnico, conviene saber qué datos mantiene el sistema
sobre su sesión activa:

- Identidad: `admin`, `admin_name`, `admin_username`, `admin_email`.
- Rol: `admin_role`, `admin_role_id`, `admin_role_description`.
- Bandera de super administrador: `is_super_admin`.
- Hora de inicio de sesión: `admin_login_time`.
- Contexto de tenant: `tenant_id`, `tenant_license`, `tenant_db`.
- Licencia: `license_days_remaining` (puede ser negativo si expiró).

## Roles, permisos y matriz de accesos

### Tipos de usuario

El sistema distingue **dos dimensiones** de usuario:

| Dimensión                  | Campo                    | Descripción                                               |
|----------------------------|--------------------------|-----------------------------------------------------------|
| **Rol funcional**          | `admins.role_id`         | Qué módulos puede usar dentro de la empresa.              |
| **Super admin del sistema**| `admins.is_system_admin` | Acceso al Panel Backoffice y gestión multi-tenant.        |

Un usuario puede ser, por ejemplo, *Administrador* de su empresa **y** super
administrador del sistema (acceso al backoffice). Las dos banderas son
independientes.

### Roles funcionales (referencia rápida)

| Rol                   | Permisos típicos                                              | Uso sugerido               |
|-----------------------|---------------------------------------------------------------|----------------------------|
| Super Admin (empresa) | Todo el sistema dentro del tenant: planillas, empleados, usuarios, configuración. | Líder de RRHH / Payroll Manager. |
| Administrador         | Planillas, empleados, asistencias, conceptos, reportes.       | Personal de RRHH.          |
| Operador              | Consulta y operaciones acotadas (empleados, asistencias, reportes). | Analistas operativos.|
| Sólo lectura          | Consulta de reportes y listados.                              | Auditoría, visores.        |
| Super Admin (sistema) | Además de lo anterior, acceso al Panel Backoffice y gestión de tenants. | TI / soporte técnico.|

La matriz completa módulo-por-módulo está en el **Apéndice C**.

### Permisos granulares por ruta

Desde la v3.5.13, cada ruta del sistema está protegida por el helper
`canAccessRoute()`. Esto significa que:

- El menú lateral **sólo muestra los módulos que el usuario puede abrir**
  (23 módulos filtrados dinámicamente).
- Si alguien intenta acceder a una URL sin permiso, el sistema redirige al
  dashboard con mensaje de error.
- Las secciones agrupadas (ej. "Reportes") se ocultan por completo si el
  usuario no tiene permiso sobre ninguno de sus hijos.

## Dashboard ejecutivo

El dashboard es la pantalla de inicio tras el login. Presenta información
clave en cuatro bloques.

### 1. Métricas principales

Tarjetas superiores con totales:

- Empleados activos.
- Posiciones.
- Cargos definidos.
- Horarios configurados.
- Total de empleados (filtrable por tipo de planilla).

### 2. Asistencia del día

- Empleados que marcaron entrada hoy.
- Score de puntualidad promedio del día.
- Tardanzas y ausencias acumuladas.

### 3. Estadísticas mensuales

- Puntualidad promedio del mes.
- Distribución de asistencias por día (gráfica).
- Tendencia de horas trabajadas vs. horas extras.

### 4. Acumulados del tipo de planilla activo

- XIII Mes acumulado del trimestre actual.
- Prima de antigüedad.
- Vacaciones devengadas.

### 5. Alertas activas (top 5)

Avisos del sistema `AlertsSystem`: ausencias excesivas, licencias por vencer,
conceptos sin calcular, etc.

### Filtro por tipo de planilla

En la parte superior del dashboard aparece un **selector de tipo de planilla**
que re-calcula todas las métricas para el tipo elegido (Regular, XIII Mes,
Vacaciones, etc.). La selección se **persiste en `sessionStorage`**, así que
al volver al dashboard desde otra pantalla la vista conserva el filtro.

El evento `payrollTypeChanged` se dispara en toda la aplicación para que
otras vistas que escuchen ese tipo de planilla (reportes, métricas de
empleados) actualicen sus datos en tiempo real.

## Convenciones de interfaz

El sistema usa **AdminLTE 3** como base visual. Los patrones que encontrará
consistentemente en todas las pantallas:

### Navegación

- **Barra superior**:
  - Selector de tipo de planilla (izquierda).
  - Reloj en vivo con fecha y hora del servidor.
  - Accesos rápidos (icono `+`): nuevo empleado, nuevo horario, nueva posición.
  - Acceso directo a **Marcaciones** (asistencias).
  - Dropdown de licencia: muestra empresa, RUC, clave y días restantes.
  - Menú de usuario (derecha): perfil, cambio de contraseña, cerrar sesión.

- **Sidebar (barra lateral)**:
  Módulos agrupados en secciones:

  - Dashboard
  - Gestión de personal (empleados, expedientes, campos adicionales)
  - Control de asistencia (marcaciones, aprobación horas extras)
  - Nómina / Planillas (procesar, reproceso, acumulados)
  - Liquidaciones
  - Vacaciones
  - Acreedores y deducciones
  - Reportes
  - Configuración
  - *Panel Backoffice* (sólo super administradores)

- **Breadcrumbs**: cada vista incluye migas de pan que indican la ubicación
  jerárquica dentro del sistema.

### Notificaciones

- **Toastr**: mensajes flotantes (arriba-derecha) para éxitos, errores e
  información puntual. Se auto-ocultan tras unos segundos.
- **SweetAlert2**: diálogos modales para confirmaciones (eliminar, aprobar,
  rechazar) y alertas bloqueantes.

### Tablas y formularios

- **DataTables** con búsqueda, paginación y ordenamiento en todas las
  tablas grandes. Idioma español, responsive a 1024 px.
- **Select2** para campos de búsqueda por nombre o código (empleados,
  conceptos, acreedores).
- **AJAX inline editing**: algunos campos (días de preaviso, valores por
  empleado) se editan directamente sin recargar la página.
- **Cache-busting**: los recursos JS/CSS incluyen *query string* de versión
  para evitar que el navegador use copias desactualizadas tras un despliegue.
- **Modal refresh inteligente**: al cerrar un modal de edición, la tabla
  padre se refresca automáticamente para mostrar los cambios.

### Animaciones (desde v3.5.20)

Las vistas de Liquidaciones, Planillas, Innova Export y Empleados usan
animaciones **GSAP**: *fade-in*, *slide-up*, rotaciones en iconos al pasar
el cursor, y transiciones coordinadas en botones de acción. Son puramente
visuales — no afectan los datos.

Ver detalles de cada módulo en los capítulos siguientes.
