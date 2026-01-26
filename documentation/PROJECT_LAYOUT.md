# 📁 PROJECT LAYOUT - Sistema de Planillas MVC

**Versión**: 3.5.19
**Fecha**: 22 de Enero, 2026
**Descripción**: Estructura completa del proyecto Sistema de Planillas con arquitectura MVC

---

## 📌 Índice
1. [Estructura de Alto Nivel](#estructura-de-alto-nivel)
2. [Directorio `/app` - Aplicación Core](#directorio-app---aplicación-core)
3. [Directorio `/database` - Base de Datos](#directorio-database---base-de-datos)
4. [Directorio `/documentation` - Documentación](#directorio-documentation---documentación)
5. [Directorio `/public` - Punto de Entrada](#directorio-public---punto-de-entrada)
6. [Directorio `/assets` - Recursos Estáticos](#directorio-assets---recursos-estáticos)
7. [Directorio `/plugins` - Librerías Frontend](#directorio-plugins---librerías-frontend)
8. [Directorio `/vendor` - Dependencias PHP](#directorio-vendor---dependencias-php)
9. [Archivos de Configuración](#archivos-de-configuración)

---

## 🏗️ Estructura de Alto Nivel

```
planilla-innova/
├── .claude/                      # Configuración Claude Code
├── .git/                         # Control de versiones Git
├── .vscode/                      # Configuración VS Code
├── admin/                        # Sistema de administración legacy
├── app/                          # ⭐ APLICACIÓN PRINCIPAL MVC
├── assets/                       # Recursos estáticos del proyecto
├── bin/                          # Ejecutables y scripts CLI
├── config/                       # Archivos de configuración
├── database/                     # ⭐ MIGRACIONES Y SCRIPTS BD
├── databases/                    # Archivos de bases de datos SQLite
├── dist/                         # AdminLTE distribución
├── docs/                         # AdminLTE documentación (NO MODIFICAR)
├── documentation/                # ⭐ DOCUMENTACIÓN DEL PROYECTO
├── images/                       # Imágenes y logos
├── js/                           # Scripts JavaScript del proyecto
├── legacy/                       # Código y archivos legacy
├── plugins/                      # Librerías AdminLTE y frontend
├── public/                       # ⭐ PUNTO DE ENTRADA WEB
├── scripts/                      # Scripts de automatización
├── storage/                      # Almacenamiento logs y archivos
├── tcpdf/                        # Librería generación PDF
├── tests/                        # Tests y plantillas de prueba
├── vendor/                       # Dependencias Composer
├── .env                          # Variables de entorno
├── .gitignore                    # Git ignore rules
├── CLAUDE.md                     # ⭐ MEMORIA PRINCIPAL DEL PROYECTO
├── composer.json                 # Dependencias PHP
└── composer.lock                 # Lock de versiones Composer
```

---

## 📦 Directorio `/app` - Aplicación Core

**Arquitectura**: MVC (Model-View-Controller) con capas adicionales (Services, Libraries, Middleware)

```
app/
├── Config/                       # Configuración de la aplicación
│   └── WizardConfig.php         # Configuración wizard multi-tenant
│
├── Controllers/                  # ⭐ CONTROLADORES (Lógica de negocio)
│   ├── Admin/                   # Controladores de administración
│   │   ├── AccumulatedImportController.php
│   │   ├── EmployeeImportController.php
│   │   └── PersonalScheduleController.php
│   │
│   ├── ActivityLogController.php
│   ├── AcumuladoController.php
│   ├── Admin.php
│   ├── AnnualPayrollEstimateController.php
│   ├── ApiController.php
│   ├── AsientosContablesPDFGenerator.php
│   ├── Attendance.php
│   ├── AttendanceApiConfigController.php
│   ├── AttendanceController.php
│   ├── AttendanceDeviceController.php
│   ├── BaseController.php       # Controlador base con lógica común
│   ├── BusinessCalendarController.php
│   ├── Cargo.php
│   ├── CompanyController.php
│   ├── ConceptController.php
│   ├── CreditorController.php
│   ├── CuentaContable.php
│   ├── DeductionController.php
│   ├── Employee.php
│   ├── EmployeeAdditionalFieldController.php  # V3.5.19 Campos adicionales
│   ├── EmployeeDocumentController.php
│   ├── EmployeeFileController.php
│   ├── EmployeeManualConceptController.php
│   ├── EstimateReportController.php
│   ├── ExcelReportController.php
│   ├── FrecuenciaController.php
│   ├── Funcion.php
│   ├── Home.php
│   ├── LiquidationController.php
│   ├── LoanController.php
│   ├── OrganizationalController.php
│   ├── Partida.php
│   ├── PartidaPresupuestaria.php
│   ├── PayrollController.php    # ⭐ Procesamiento de planillas
│   ├── PDFReportController.php
│   ├── PlanillaContableExcelGenerator.php
│   ├── Position.php
│   ├── ReportController.php
│   ├── RoleController.php
│   ├── ScheduleController.php
│   ├── SituacionController.php
│   ├── TipoAcumuladoController.php
│   ├── TipoPlanillaController.php
│   ├── Timeclock.php
│   ├── UserController.php
│   └── VacationController.php
│
├── Core/                         # ⭐ NÚCLEO DEL FRAMEWORK
│   ├── Adapters/                # Adaptadores de base de datos
│   │   ├── DatabaseAdapter.php
│   │   ├── MySQLAdapter.php
│   │   └── PostgreSQLAdapter.php
│   │
│   ├── Bootstrap.php            # Inicialización de la aplicación
│   ├── Config.php               # Configuración del sistema
│   ├── Database.php             # Clase de conexión a BD
│   ├── helpers.php              # Funciones helper globales
│   ├── Logger.php               # Sistema de logging
│   ├── PayrollValidationRules.php
│   ├── QueryBuilder.php         # ⭐ Query Builder personalizado (V3.2.2)
│   ├── ReferenceModel.php       # Modelo base para tablas de referencia
│   ├── Router.php               # Sistema de enrutamiento
│   └── UrlHelper.php            # Helper de URLs
│
├── Helpers/                      # Funciones auxiliares
│   ├── JavaScriptHelper.php
│   └── PermissionHelper.php     # ⭐ Sistema de permisos granulares
│
├── Libraries/                    # Librerías reutilizables
│   └── PlanillaConceptCalculator.php  # ⭐ Motor de fórmulas V3.5.15
│
├── Middleware/                   # Middleware de la aplicación
│   ├── AuthMiddleware.php       # Autenticación
│   ├── CSRFMiddleware.php       # Protección CSRF
│   ├── PermissionMiddleware.php # Permisos granulares
│   └── RoleMiddleware.php       # Control de roles
│
├── Models/                       # ⭐ MODELOS (Lógica de datos)
│   ├── Acumulado.php
│   ├── Admin.php
│   ├── Attendance.php
│   ├── AttendanceAbsenceLog.php
│   ├── AttendanceApiConfig.php
│   ├── AttendanceCalculation.php
│   ├── AttendanceDetail.php
│   ├── AttendanceDevice.php
│   ├── AttendanceHeader.php
│   ├── AttendanceRecord.php
│   ├── AttendanceSyncLog.php
│   ├── BusinessCalendar.php     # ⭐ Calendario empresarial Panamá
│   ├── Cargo.php
│   ├── Company.php
│   ├── Concept.php
│   ├── Creditor.php
│   ├── CuentaContable.php
│   ├── Deduction.php
│   ├── Employee.php             # ⭐ Modelo principal de empleados
│   ├── EmployeeAdditionalField.php      # V3.5.19 Campos adicionales
│   ├── EmployeeAdditionalFieldValue.php # V3.5.19 Valores campos adicionales
│   ├── EmployeeDailySchedule.php
│   ├── EmployeeFile.php
│   ├── EmployeeFileAttachment.php
│   ├── EmployeeManualConcept.php
│   ├── EmployeePayrollSalary.php
│   ├── Frecuencia.php
│   ├── Funcion.php
│   ├── Loan.php
│   ├── LoanInstallment.php
│   ├── Organigrama.php
│   ├── Organizational.php
│   ├── Partida.php
│   ├── PartidaPresupuestaria.php
│   ├── Payroll.php              # ⭐ Modelo principal de planillas
│   ├── PayrollAccumulationsProcessor.php
│   ├── PayrollConcept.php
│   ├── PayrollDetail.php
│   ├── Posicion.php
│   ├── Position.php
│   ├── Report.php
│   ├── Role.php
│   ├── Schedule.php
│   ├── Situacion.php
│   ├── TipoAcumulado.php
│   ├── TipoPlanilla.php
│   ├── User.php
│   └── WizardModel.php
│
├── Services/                     # ⭐ SERVICIOS (Lógica de negocio compleja)
│   ├── Attendance/              # ⭐ MÓDULO ASISTENCIAS (V3.4.0 - V3.5.5)
│   │   ├── Calculators/        # Calculadores especializados
│   │   │   ├── AbsenceDetector.php             # Detección de ausencias
│   │   │   ├── AttendanceCalculator.php        # Cálculo de asistencias
│   │   │   ├── LegalComplianceChecker.php      # Validación legal Panamá
│   │   │   ├── OvertimeCalculator.php
│   │   │   ├── OvertimeRateCalculator.php      # Cálculo tarifas extras (+25%/+50%)
│   │   │   ├── WorkingDayClassifier.php        # Clasificación días laborales
│   │   │   └── WorkScheduleResolver.php
│   │   │
│   │   ├── AbsenceDetector.php
│   │   ├── AlertsSystem.php                    # Sistema de alertas legales
│   │   ├── ApiClient.php                       # Cliente API Base44
│   │   ├── AttendanceCalculator.php
│   │   ├── AttendanceConceptMapper.php         # Mapeo asistencias→conceptos
│   │   ├── AttendanceProcessor.php
│   │   ├── AttendanceSyncService.php           # Sincronización API
│   │   ├── ExcelExporter.php
│   │   ├── PayrollAttendanceIntegrator.php     # Integración con planillas
│   │   ├── PeriodAttendanceSummary.php         # Resúmenes por período
│   │   ├── RecordsProcessor.php
│   │   └── ReportsGenerator.php
│   │
│   ├── CalendarSyncService.php  # Sincronización calendario empresarial
│   ├── LicenseGenerator.php
│   ├── LicenseValidator.php
│   ├── PlanillaConceptCalculator.php
│   ├── PlanillaConceptCalculatorSecure.php
│   ├── VacationBalanceService.php
│   └── XIIIMesPeriodoTrimestralCalculator.php  # ⭐ Cálculo XIII Mes (V3.3.9)
│
└── Views/                        # ⭐ VISTAS (Interfaz de usuario)
    ├── admin/                   # Vistas de administración
    │   ├── acumulados/         # Módulo de acumulados
    │   │   ├── all_employees.php
    │   │   ├── by_payroll.php
    │   │   └── employee.php
    │   │
    │   ├── attendance/         # Módulo de asistencias
    │   │   ├── index.php
    │   │   └── reports.php
    │   │
    │   ├── cargos/             # Gestión de cargos
    │   ├── concepts/           # Conceptos de planilla
    │   ├── creditors/          # Acreedores
    │   ├── deductions/         # Deducciones
    │   ├── employee_additional_fields/  # V3.5.19 Campos adicionales
    │   ├── frecuencias/        # Frecuencias de pago
    │   ├── funciones/          # Funciones organizacionales
    │   ├── liquidation/        # Liquidaciones
    │   ├── organizational/     # Estructura organizacional
    │   ├── partidas/           # Partidas presupuestarias
    │   ├── positions/          # Posiciones
    │   ├── reports/            # Reportes
    │   ├── roles/              # Roles y permisos
    │   ├── schedules/          # Horarios
    │   ├── situaciones/        # Situaciones de empleados
    │   ├── templates/          # Plantillas reutilizables
    │   ├── tipos-acumulados/   # Tipos de acumulados
    │   ├── tipos-planilla/     # Tipos de planilla
    │   └── users/              # Usuarios del sistema
    │
    ├── home/                   # Vista del dashboard
    ├── layouts/                # Layouts de la aplicación
    │   └── main.php           # Layout principal AdminLTE
    │
    ├── reports/                # Reportes
    ├── timeclock/              # Reloj de marcaciones
    └── wizard/                 # Wizard de configuración multi-tenant
```

---

## 💾 Directorio `/database` - Base de Datos

**Descripción**: Migraciones, scripts de instalación, backups y queries del sistema

```
database/
├── backups/                      # Backups de bases de datos
├── install/                      # Scripts de instalación inicial
├── migrations/                   # ⭐ MIGRACIONES DE BD (Sistema principal)
│   ├── 001_create_users_table.sql
│   ├── 002_create_roles_table.sql
│   ├── 003_create_employees_table.sql
│   ├── 004_create_payroll_tables.sql
│   ├── 005_create_concepts_table.sql
│   ├── 006_create_deductions_table.sql
│   ├── 007_create_attendance_tables.sql
│   ├── 008_create_business_calendar.sql
│   ├── 009_create_loans_tables.sql
│   ├── 010_create_organizational_tables.sql
│   ├── 011_add_employee_file_types.sql           # V3.5.16 Expedientes
│   ├── 012_add_employee_additional_fields.sql    # V3.5.19 Campos adicionales
│   └── ...
│
├── migrations_consolidated/      # Migraciones consolidadas por versión
├── queries/                      # Queries SQL de uso común
└── scripts/                      # Scripts de mantenimiento y utilidades
```

---

## 📚 Directorio `/documentation` - Documentación

**Descripción**: Toda la documentación del proyecto organizada por categorías

```
documentation/
├── attendance/                   # Documentación módulo de asistencias
├── changelog/                    # ⭐ CHANGELOGS POR VERSIÓN (V3.4.1+)
│   ├── README.md                # Guía de estructura y convenciones
│   ├── v3.5.19.md              # Módulo Campos Adicionales Personalizados
│   ├── v3.5.18.md              # Fix TypeError insertConceptDetail
│   ├── v3.5.17.md              # Bug Fixes + UX Improvements
│   ├── v3.5.16.md              # Expedientes Empleados + Migraciones Multi-Tenant
│   ├── v3.5.15.md              # UNIDAD Dinámica en Fórmulas
│   ├── v3.5.14.md              # Campo UNIDAD en Planilla Detalle
│   ├── v3.5.13.md              # Sistema Permisos Granulares
│   ├── v3.5.12.md              # Acumulados Excel Export
│   ├── v3.5.0-v3.5.11.md       # Versiones anteriores
│   ├── v3.4.0.md               # API Externa Asistencias
│   ├── v3.4.1-v3.4.8.md        # Calculadores y procesamiento
│   └── v3.3.x.md               # Versiones históricas
│
├── claude/                       # Documentación específica de Claude Code
├── config/                       # Configuración del sistema
├── credentials/                  # Credenciales y configuración de APIs
├── cron/                         # Documentación de tareas programadas
├── deployment/                   # Guías de despliegue
├── fixes/                        # Documentación de fixes y soluciones
├── imports/                      # Documentación de importaciones
├── licenses/                     # Licencias del sistema
├── loans/                        # Documentación de préstamos
├── manual_usuario/               # Manuales de usuario
├── migrations/                   # Documentación de migraciones
├── nginx/                        # Configuración Nginx
├── reports/                      # Documentación de reportes
├── summary/                      # Resúmenes de funcionalidades
├── tenants/                      # Documentación multi-tenant
├── testing/                      # Documentación de pruebas
│
├── CHANGELOG.md                  # ⭐ ÍNDICE PRINCIPAL DE VERSIONES
├── PROJECT_LAYOUT.md             # ⭐ ESTE ARCHIVO - Estructura del proyecto
├── ROADMAP.md                    # Hoja de ruta del proyecto
└── TODO.md                       # Lista de tareas pendientes
```

---

## 🌐 Directorio `/public` - Punto de Entrada

**Descripción**: Directorio web público accesible desde el navegador

```
public/
├── assets/                       # Assets específicos de public
├── js/                           # JavaScript del proyecto
├── templates/                    # Plantillas exportables
├── index.php                     # ⭐ PUNTO DE ENTRADA PRINCIPAL
└── template_empleados.xlsx       # Plantilla importación empleados
```

---

## 🎨 Directorio `/assets` - Recursos Estáticos

**Descripción**: CSS, JavaScript y recursos estáticos personalizados

```
assets/
├── css/                          # Estilos personalizados
├── javascript/                   # Scripts JavaScript personalizados
├── js/                           # Alias de javascript
└── template/                     # Plantillas de recursos
```

---

## 🔌 Directorio `/plugins` - Librerías Frontend

**Descripción**: Plugins de AdminLTE y librerías JavaScript

```
plugins/
├── bootstrap/                    # Bootstrap 4
├── bootstrap4-duallistbox/
├── chart.js/                     # Gráficas
├── datatables/                   # ⭐ DataTables (tablas dinámicas)
├── datatables-bs4/
├── datatables-buttons/
├── datatables-responsive/
├── daterangepicker/
├── dropzone/
├── fontawesome-free/             # ⭐ Font Awesome iconos
├── fullcalendar/                 # ⭐ FullCalendar (calendario empresarial)
├── icheck-bootstrap/
├── jquery/                       # ⭐ jQuery
├── jquery-validation/
├── moment/                       # Manejo de fechas
├── select2/                      # ⭐ Select2 (selects mejorados)
├── select2-bootstrap4-theme/
├── sweetalert2/                  # ⭐ SweetAlert2 (alertas bonitas)
├── sweetalert2-theme-bootstrap-4/
├── tempusdominus-bootstrap-4/    # Selector de fechas
└── ...
```

---

## 📦 Directorio `/vendor` - Dependencias PHP

**Descripción**: Dependencias de Composer (NO MODIFICAR)

```
vendor/
├── composer/                     # Composer autoload
├── ezyang/                       # HTML Purifier (seguridad)
├── maennchen/                    # ZipStream (exportación archivos)
├── markbaker/                    # Complex numbers (PhpSpreadsheet)
├── myclabs/                      # PHP Enum
├── nxp/                          # ⭐ Math Executor (motor de fórmulas)
├── phpmailer/                    # PHPMailer (envío de correos)
├── phpoffice/                    # ⭐ PhpSpreadsheet (Excel)
├── psr/                          # PSR standards
└── tecnickcom/                   # TCPDF (generación PDF)
```

**Dependencias Críticas**:
- **nxp/math-executor**: Motor de evaluación de fórmulas matemáticas (NUNCA eliminar)
- **phpoffice/phpspreadsheet**: Generación de archivos Excel
- **tecnickcom/tcpdf**: Generación de PDFs

---

## ⚙️ Archivos de Configuración

**Descripción**: Archivos de configuración del proyecto

```
/
├── .env                          # ⭐ VARIABLES DE ENTORNO (NO SUBIR A GIT)
├── .env.example                  # Ejemplo de .env
├── .gitignore                    # Archivos ignorados por Git
├── composer.json                 # ⭐ DEPENDENCIAS PHP
├── composer.lock                 # Lock de versiones exactas
├── CLAUDE.md                     # ⭐ MEMORIA PRINCIPAL DEL PROYECTO
├── README.md                     # README del proyecto
└── package.json                  # Dependencias NPM (si aplica)
```

---

## 📊 Estadísticas del Proyecto

**Última Actualización**: 22 de Enero, 2026

### Conteo de Archivos por Tipo

| Tipo | Cantidad Aproximada | Descripción |
|------|---------------------|-------------|
| Controladores | 50+ | Lógica de negocio y endpoints |
| Modelos | 45+ | Modelos de datos y BD |
| Vistas | 100+ | Interfaces de usuario AdminLTE |
| Servicios | 25+ | Lógica de negocio compleja |
| Migraciones | 50+ | Scripts de base de datos |
| Documentación | 30+ | Archivos .md de documentación |

### Módulos Principales

1. **Sistema Core MVC** (100%)
2. **Gestión de Planillas** (100%)
3. **Sistema de Asistencias** (92%)
4. **Liquidaciones** (100%)
5. **Calendario Empresarial** (100%)
6. **Sistema de Permisos Granulares** (100%)
7. **Expedientes de Empleados** (100%)
8. **Campos Adicionales Personalizados** (100%)
9. **Multi-tenancy** (45%)
10. **Módulo de Vacaciones** (45%)

---

## 🔗 Referencias Importantes

### Archivos Críticos del Sistema

| Archivo | Ubicación | Descripción |
|---------|-----------|-------------|
| **CLAUDE.md** | `/CLAUDE.md` | Memoria principal del proyecto |
| **index.php** | `/public/index.php` | Punto de entrada web |
| **Router.php** | `/app/Core/Router.php` | Sistema de enrutamiento |
| **Database.php** | `/app/Core/Database.php` | Conexión a BD |
| **QueryBuilder.php** | `/app/Core/QueryBuilder.php` | Query Builder personalizado |
| **PlanillaConceptCalculator.php** | `/app/Libraries/PlanillaConceptCalculator.php` | Motor de fórmulas V3.5.15 |
| **PayrollController.php** | `/app/Controllers/PayrollController.php` | Procesamiento de planillas |
| **PermissionHelper.php** | `/app/Helpers/PermissionHelper.php` | Sistema de permisos |
| **.env** | `/.env` | Variables de entorno (NO SUBIR A GIT) |
| **composer.json** | `/composer.json` | Dependencias PHP |

### Módulos de Asistencias (V3.4.0+)

| Archivo | Ubicación | Descripción |
|---------|-----------|-------------|
| **ApiClient.php** | `/app/Services/Attendance/ApiClient.php` | Cliente API Base44 |
| **AttendanceSyncService.php** | `/app/Services/Attendance/AttendanceSyncService.php` | Sincronización automática |
| **AttendanceCalculator.php** | `/app/Services/Attendance/Calculators/AttendanceCalculator.php` | Cálculos de asistencia |
| **LegalComplianceChecker.php** | `/app/Services/Attendance/Calculators/LegalComplianceChecker.php` | Validación legislación Panamá |
| **OvertimeRateCalculator.php** | `/app/Services/Attendance/Calculators/OvertimeRateCalculator.php` | Cálculo +25%/+50% horas extras |
| **PayrollAttendanceIntegrator.php** | `/app/Services/Attendance/PayrollAttendanceIntegrator.php` | Integración con planillas |

---

## 📝 Convenciones y Buenas Prácticas

### Estructura de Archivos

1. **Controladores**: Nombres en PascalCase (ej. `PayrollController.php`)
2. **Modelos**: Nombres en singular PascalCase (ej. `Employee.php`)
3. **Vistas**: Nombres en snake_case (ej. `employee_list.php`)
4. **Servicios**: Nombres descriptivos terminados en `Service` o `Calculator`
5. **Migraciones**: Prefijo numérico + descripción (ej. `012_add_employee_additional_fields.sql`)

### Organización de Código

1. **Un controlador por tabla/entidad principal**
2. **Separar lógica compleja en Services**
3. **Usar QueryBuilder para consultas SQL**
4. **Vistas en subdirectorios por módulo**
5. **Middleware para validación de permisos**

### Documentación

1. **Changelogs individuales** por versión en `/documentation/changelog/`
2. **CLAUDE.md** solo info crítica + enlaces a changelogs
3. **README.md** en subdirectorios para explicar estructura
4. **Comentarios PHPDoc** en funciones públicas

---

## 🚀 Flujo de Trabajo del Proyecto

### Solicitud HTTP → Respuesta

```
1. Usuario accede a URL
   ↓
2. public/index.php (Punto de entrada)
   ↓
3. app/Core/Bootstrap.php (Inicialización)
   ↓
4. app/Core/Router.php (Enrutamiento)
   ↓
5. Middleware (Auth, CSRF, Permissions)
   ↓
6. Controller (Lógica de negocio)
   ↓
7. Model / Service (Lógica de datos)
   ↓
8. View (Renderizado HTML)
   ↓
9. Respuesta al navegador
```

### Procesamiento de Planilla

```
1. PayrollController::create()
   ↓
2. Validación de datos y permisos
   ↓
3. Payroll::create() → Inserta cabecera
   ↓
4. PlanillaConceptCalculator → Evalúa fórmulas
   ↓
5. PayrollDetail::insert() → Inserta detalles
   ↓
6. PayrollAccumulationsProcessor → Actualiza acumulados
   ↓
7. PayrollAttendanceIntegrator → Integra asistencias (si aplica)
   ↓
8. Generación de PDF/Excel
   ↓
9. Vista de confirmación
```

---

## 🎯 Próximos Pasos

Según el **ROADMAP.md** y **CLAUDE.md**, las siguientes áreas están en desarrollo:

1. **Módulo de Asistencias - Subfase 7.5** (35%)
   - Dashboard gerencial de asistencias
   - Reportes ejecutivos PDF
   - Vistas detalladas por empleado

2. **Sistema de Vacaciones Panamá** (45%)
   - VacationCalculator según legislación
   - CRUD de solicitudes
   - Aprobaciones workflow

3. **Multi-tenancy** (45%)
   - Wizard de creación de empresas
   - Gestión automática de BD por tenant
   - TenantResolver y middleware

---

**Generado por**: Claude Code
**Fecha**: 22 de Enero, 2026
**Versión del Sistema**: 3.5.19
**Mantenido por**: Equipo de Desarrollo

Para más información, consultar:
- [CLAUDE.md](../CLAUDE.md) - Memoria principal del proyecto
- [CHANGELOG.md](CHANGELOG.md) - Historial de versiones
- [ROADMAP.md](ROADMAP.md) - Hoja de ruta
