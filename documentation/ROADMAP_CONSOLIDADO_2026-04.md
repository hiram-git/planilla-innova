# 🚀 ROADMAP CONSOLIDADO — Sistema de Planillas MVC

**Fecha de consolidación**: 24 de Abril, 2026
**Versión actual del sistema**: 3.5.22
**Fuentes cruzadas**: `ROADMAP.md` + `TODO.md` + 32 changelogs (`v3.4.0` a `v3.5.22`)
**Metodología**: auditoría de todo "Próximo Paso" / "Pendiente" / "Mejora Futura" explícita en cada changelog.

---

## 📊 Resumen Ejecutivo

| Bloque                        | Items | Nivel                    |
|-------------------------------|-------|--------------------------|
| 🔴 Bloqueantes de seguridad   |   4   | CRÍTICO                  |
| 🟠 Cierre de módulos al 85-95 % | 16  | Alto                     |
| 🟡 Quick wins                 |  18   | Medio (< 1 semana c/u)   |
| 🟢 Iniciativas grandes (Fase 8 y 9) | **42** | Planificado Q2-Q3 2026 |
| 🔵 Mejoras de UX y calidad    |  32   | Backlog                  |
| **Total**                     | **112** |                        |

> **Actualización 24-Abr-2026 — Integraciones bancarias Panamá**
> Se expandió la Subfase 9.1 de 4 a 26 items al mapear los **7 bancos principales**
> de Panamá con ACH de planilla (Banco General, Banistmo, BAC, BNP, Global Bank,
> Credicorp, Caja de Ahorros) + arquitectura base + reconciliación.
> Además, se documentó que el **Export ERP INNOVA ya está completado** (v3.5.20)
> y se mueve de "pendiente Fase 9" a "✅ hecho".

El sistema está **funcional en producción y estable**. La mayoría de los pendientes son **cierre de flecos** y **piezas opcionales** descubiertas en el cruce de changelogs que nunca llegaron al ROADMAP principal.

---

## 🔴 BLOQUE 1 — Bloqueantes de seguridad *(atender primero)*

Pendientes que representan **hueco de seguridad o credenciales en texto plano**.

### 1.1 CSRF faltante en endpoint de asistencias
- **Origen**: changelog v3.5.8 "Issues conocidos"
- **Ruta**: `POST /panel/attendance/process-day`
- **Riesgo**: CSRF no validado en acción que escribe en BD
- **Esfuerzo**: 1-2 h (agregar token al formulario y validar en controller)
- **Prioridad**: 🔴 CRÍTICA

### 1.2 Contraseña SMTP en texto plano (companies)
- **Origen**: changelog v3.5.15 "Pendientes"
- **Columna**: `companies.mail_password`
- **Riesgo**: fuga de credencial de servidor de correo por backup o consulta de BD
- **Solución**: encriptar con AES usando `APP_KEY` (mismo patrón que `db_pass_enc` en tenants)
- **Esfuerzo**: 4-6 h (migración + helper cifrado + actualizar CRUD)
- **Prioridad**: 🔴 CRÍTICA

### 1.3 Credenciales de BD tenant sin `APP_KEY`
- **Origen**: changelog v3.5.8 "Corto Plazo (v3.6.0)"
- **Campo**: `tenants.db_pass_enc`
- **Nota**: el campo ya existe pero el changelog lo marca como pendiente de **verificar que use `APP_KEY`** como llave en lugar de clave hardcoded
- **Esfuerzo**: 2-3 h (auditar `EncryptionHelper`)
- **Prioridad**: 🔴 CRÍTICA

### 1.4 Permisos de acceso a configuración SMTP
- **Origen**: v3.5.15
- **Descripción**: cualquier usuario con acceso al módulo `Empresa` puede ver/editar credenciales SMTP
- **Solución**: permiso granular `company.smtp.manage` sólo para super admin
- **Esfuerzo**: 2-3 h
- **Prioridad**: 🔴 Alta

---

## 🟠 BLOQUE 2 — Cierre de módulos al 85-95 %

Los módulos del ROADMAP principal que están *casi* completos. Prioridad alta porque **ya se invirtió la mayoría del esfuerzo** y cerrarlos libera dependencias.

### 2.1 Asistencias — Subfase 7.5 (8 % restante)

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 2.1.1 | **Dashboard gerencial** de asistencias por departamento | ROADMAP + v3.4.8 | 1 semana |
| 2.1.2 | **Vista empleados self-service** para consultar sus asistencias propias | ROADMAP + v3.4.8 | 1 semana |
| 2.1.3 | Reportes ejecutivos de ausentismo y horas extras | ROADMAP + v3.5.4 | 4-5 días |
| 2.1.4 | **Alertas automáticas** para supervisores/RRHH (email + notificaciones in-app) | ROADMAP | 3-4 días |
| 2.1.5 | **Exportación PDF** de reportes con logos | ROADMAP + v3.5.4 | 2 días |
| 2.1.6 | Pruebas de regresión: feriados pagados + tolerancias | TODO.md | 2 días |
| 2.1.7 | Edge cases: múltiples turnos, cambios de horario mid-período | TODO.md | 3 días |
| 2.1.8 | Tests de `calculateTardinessWithTolerance` y `calculateLunchWithTolerance` | changelogs v3.4.x | 2 días |

**Total estimado**: 3-4 semanas. Cierra Fase 7 al 100 %.

### 2.2 Vacaciones Panamá (10 % restante)

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 2.2.1 | **Notificaciones email** automáticas en aprobación/rechazo | ROADMAP | 3-4 días (depende de SMTP desde BD) |
| 2.2.2 | Flujo de aprobación **multinivel** (Supervisor → RRHH) | ROADMAP | 1 semana |
| 2.2.3 | Tests unitarios `calculateVacationDailySalary` (11m completos, parciales, sin acumulados) | ROADMAP | 2 días |
| 2.2.4 | Fix `tipo_planilla_id` no se preserva en todos los enlaces del módulo | v3.5.8 | 1 día |

**Total estimado**: 2-3 semanas.

### 2.3 Multitenancy (15 % restante)

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 2.3.1 | **Panel Backoffice CRUD completo** de tenants | ROADMAP + v3.5.8 | 2 semanas |
| 2.3.2 | `importTenantSchema()` real (ejecutar migraciones auto) | v3.5.8 | 1 semana |
| 2.3.3 | **Seed inicial automático** (empresa, admin, conceptos base) al crear tenant | v3.5.8 | 3-4 días |
| 2.3.4 | Panel de estadísticas de uso por tenant | ROADMAP + v3.5.8 | 1 semana |
| 2.3.5 | Testing exhaustivo de aislamiento entre tenants (suite 20+ tests) | ROADMAP | 1-2 semanas |
| 2.3.6 | Performance testing múltiples conexiones simultáneas | ROADMAP | 3-4 días |
| 2.3.7 | Sistema de planes (Basic, Pro, Enterprise) con límites por plan | v3.5.8 | 2 semanas |

**Total estimado**: 6-8 semanas. Cierra Fase 6 al 100 %.

### 2.4 Motor de Fórmulas (5 % restante)

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 2.4.1 | Tests combinaciones complejas `ACUMULADOS + CONCEPTO + SI()` | ROADMAP | 3-4 días |
| 2.4.2 | **Función `ADICIONALES("CODIGO")`** para leer campos adicionales en fórmulas | v3.5.19 | 2-3 días |
| 2.4.3 | Documentación con ejemplos avanzados en CLAUDE.md / Apéndice B | ROADMAP | 1 día |

**Total estimado**: 1 semana.

---

## 🟡 BLOQUE 3 — Quick wins *(< 1 semana cada uno, alto impacto / bajo costo)*

### 3.1 SMTP — completar funcionalidad

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 3.1.1 | Botón **"Probar Conexión SMTP"** antes de guardar (AJAX) | v3.5.15 | 2-3 h |
| 3.1.2 | Validación backend de formato email del remitente | v3.5.15 | 1 h |
| 3.1.3 | **Fallback a `.env`** si BD no tiene config SMTP | v3.5.15 | 2 h |
| 3.1.4 | Migrar `ReportController::sendMassEmail()` para usar config desde BD | v3.5.15 | 3-4 h |
| 3.1.5 | **Tabla `email_log`** con correos enviados/fallidos para auditoría | v3.5.15 | 1 día |

### 3.2 Licencias — visualización y consulta

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 3.2.1 | Gráfico visual (barra de progreso) de días restantes en dropdown | v3.5.10 | 2-3 h |
| 3.2.2 | Notificación Toastr bloqueante cuando quedan < 7 días | v3.5.10 | 2 h |
| 3.2.3 | **API endpoint** `GET /api/license/status` para consultar desde backoffice | v3.5.10 | 3-4 h |
| 3.2.4 | Panel backoffice: tabla de licencias de todos los tenants con filtros | v3.5.10 | 1 día |
| 3.2.5 | Histórico de renovaciones de licencia por tenant | v3.5.10 | 1 día |

### 3.3 Wizard creación empresas

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 3.3.1 | Métricas de tiempo de ejecución por paso (ya hay logs, falta UI) | v3.5.10 | 2 h |
| 3.3.2 | Exportar logs del wizard a archivo separado (`wizard-debug.log`) | v3.5.10 | 1 h |
| 3.3.3 | Alerta automática si falla creación de empresa (email a super admin) | v3.5.10 | 2-3 h |
| 3.3.4 | Configuración inicial de horarios y calendario empresarial en el wizard | v3.5.9 | 1-2 días |

### 3.4 Export INNOVA — mejoras incrementales

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 3.4.1 | Filtros avanzados (departamento, rango fechas) | v3.5.20 | 1 día |
| 3.4.2 | **Vista previa** del archivo antes de descargar | v3.5.20 | 1 día |
| 3.4.3 | Historial de exportaciones por planilla | v3.5.20 | 1 día |
| 3.4.4 | Validaciones adicionales de integridad de datos | v3.5.20 | 3-4 h |

---

## 🟢 BLOQUE 4 — Iniciativas grandes *(planificadas Q2-Q3 2026)*

Mantiene el mismo plan del ROADMAP original, sin cambios.

### 4.1 Fase 8 — Reportería avanzada + API *(Q2 2026, 6-7 semanas)*

**Subfase 8.1 — Reportes Legales Panamá** (3-4 semanas)
- ❌ Planilla oficial formato Ministerio de Trabajo
- ❌ Declaración Jurada CSS automática
- ❌ Reporte anual XIII Mes legislativo
- ❌ Formularios DGI actualizados

**Subfase 8.2 — Business Intelligence** (2-3 semanas)
- ❌ Dashboard ejecutivo con KPIs
- ❌ Análisis de tendencias salariales
- ❌ Proyecciones de costos laborales
- ❌ Comparativas entre períodos anteriores

**Subfase 8.3 — API REST Completa** (2-3 semanas)
- ❌ Endpoints CRUD todas las entidades
- ❌ Autenticación JWT + refresh tokens
- ❌ Documentación OpenAPI 3.0
- ❌ Rate limiting + webhooks

### 4.2 Fase 9 — Integraciones externas *(Q2-Q3 2026, 10-14 semanas)*

#### Subfase 9.1 — Sistemas Bancarios Panamá *(6-8 semanas)*

> **Actualización Abr-2026**: el ROADMAP original mencionaba sólo BAC y Banistmo.
> Tras auditar el mercado panameño, se expande a **7 bancos principales** que
> operan ACH de planilla en Panamá. Cada banco define su propio formato de
> archivo plano (fixed-width, CSV o XML), por lo que cada uno es un entregable
> independiente pero reusable con una **capa base común** (`BankExportService`).

**9.1.0 — Arquitectura base (pre-requisito)** *(1 semana)*

- ❌ Crear `BankExportService` (interfaz/base abstracta en `app/Services/BankExports/`)
- ❌ Definir `BankExporterInterface` con método `generateAchFile($payrollId, $bankConfig)`
- ❌ Tabla `bank_templates` con: banco, formato (CSV/FIXED/XML), especificación de columnas
- ❌ Tabla `bank_export_history` para trazabilidad (análoga a `innova_export_history`)
- ❌ Controller `BankExportController` con tabla de planillas exportables por banco
- ❌ Permisos granulares: `bank_export.generate`, `bank_export.view_history`
- ❌ Validación: sólo planillas en estado `PROCESADA` o `CERRADA`
- ❌ Pre-check: empleados con forma de pago `ACH` deben tener banco, cuenta y tipo de cuenta

**9.1.1 — Banco General** *(1 semana)* 🇵🇦 mayor volumen
- ❌ Formato fixed-width, encoding ASCII
- ❌ Registro tipo 1: encabezado (empresa, cuenta origen, fecha proceso, total)
- ❌ Registro tipo 2: detalle por empleado (cuenta destino, tipo cuenta, monto, nombre)
- ❌ Registro tipo 3: totales de control
- ❌ Extensión `.txt`

**9.1.2 — Banistmo** *(1 semana)*
- ❌ Formato CSV específico (delimitador `|`)
- ❌ Cabecera con convenio empresa y fecha aplicación
- ❌ Validaciones locales: longitud cuenta, dígito verificador

**9.1.3 — BAC Credomatic** *(3-4 días)*
- ❌ Formato fixed-width propio
- ❌ Archivo separado para planilla en USD vs. PAB

**9.1.4 — Banco Nacional de Panamá** *(3-4 días)* 🇵🇦 entidades públicas
- ❌ Formato específico para entidades públicas y privadas con cuenta BNP
- ❌ Cabecera con RUC empresa y código de trámite

**9.1.5 — Global Bank** *(3 días)*
- ❌ Formato CSV con encabezado definido
- ❌ Soporte para transferencias masivas ACH

**9.1.6 — Credicorp Bank** *(3 días)*
- ❌ Formato propietario (revisar documentación banca empresarial)

**9.1.7 — Caja de Ahorros** *(3 días)* 🇵🇦 entidad estatal
- ❌ Formato fixed-width para planilla funcionarios públicos

**9.1.8 — API Banco General (online)** *(2-3 semanas)* — ALTERNATIVA AVANZADA
- ❌ Autenticación OAuth2 con Banco General API
- ❌ Envío directo de lote ACH sin archivo intermedio
- ❌ Consulta de estado del lote (en proceso / aplicado / rechazado)
- ❌ Reintentos y manejo de errores de red

**9.1.9 — Reconciliación y validación** *(1 semana)*
- ❌ Conciliación automática pagos (archivo de retorno del banco)
- ❌ Detección de pagos rechazados (cuenta cerrada, saldo insuficiente)
- ❌ Notificación email al admin de planilla si hay rechazos
- ❌ Reporte de conciliación por período

#### Subfase 9.2 — Sistemas Contables *(3-4 semanas)*

- [x] ✅ **Export ERP INNOVA** — YA IMPLEMENTADO en v3.5.20
      *(`InnovaExportService` 433 líneas, 3 tipos de registro, fixed-width 347 chars,
      integrado en menú y con historial)*. Ver §5.5 del manual.
- ❌ Conector SAP Business One
- ❌ QuickBooks Online API
- ❌ Export asientos contables automáticos (genérico)
- ❌ Integración ERP empresariales

#### Subfase 9.3 — Conectores Gubernamentales *(1-2 semanas)*
- ❌ API Ministerio de Trabajo
- ❌ Integración CSS Panamá
- ❌ Reportes automáticos DGI

### 4.3 Otros grandes descubiertos en changelogs

**SMTP avanzado** *(continuación de 3.1)*
- ❌ Sistema de **plantillas HTML** para correos (editor visual)
- ❌ **Queue system** para envíos masivos (background jobs)

**Campos Adicionales — evolución** *(v3.5.19)*
- ❌ Campos condicionales (mostrar X si Y = valor)
- ❌ Agrupación en secciones personalizables
- ❌ Historial de cambios de valores por empleado
- ❌ Reportes personalizados basados en campos adicionales
- ❌ **Campos calculados** (fórmulas entre campos adicionales)
- ❌ Constructor visual drag & drop (v4.x)

**Importación de empleados — evolución** *(v3.5.9)*
- ❌ Vista previa de datos antes de importar
- ❌ Detección de duplicados en preview
- ❌ Actualización masiva (hoy sólo crea)
- ❌ Import asíncrono en background para 500+ empleados (con barra de progreso)
- ❌ Template dinámico adaptado a campos configurables

**Mobile Self-Service** *(Q2 2026, ROADMAP)*
- ❌ App móvil empleados + notificaciones push

**Seguridad avanzada** *(Q3 2026, ROADMAP)*
- ❌ 2FA
- ❌ Encryption en reposo
- ❌ Audit log avanzado

---

## 🔵 BLOQUE 5 — UX, calidad y deuda técnica *(backlog priorizable)*

### 5.1 UI de Permisos y Roles

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 5.1.1 | **UI para gestión de permisos** (hoy sólo por BD) | v3.5.11 | 1 semana |
| 5.1.2 | Roles predefinidos listos (Admin, Manager, Viewer, Auditor) | v3.5.11 | 2 días |
| 5.1.3 | **Audit log** de denegaciones de acceso | v3.5.11 | 3-4 días |
| 5.1.4 | Audit log de cambios en `role_permissions` | v3.5.13 | 2 días |
| 5.1.5 | Permisos granulares por campo (no sólo por módulo) | v3.5.11 | 1-2 semanas |
| 5.1.6 | Request access workflow desde página "denegado" | v3.5.11 | 3-4 días |
| 5.1.7 | Unificar validación en `PermissionMiddleware` (retirar `$_SESSION['permissions']` legacy) | TODO.md | 2 días |
| 5.1.8 | Búsqueda de módulos, filtros por categoría, estadísticas de uso en página "denegado" | v3.5.11 | 1 semana |
| 5.1.9 | Tests unitarios de `PermissionHelper` | v3.5.13 | 2 días |

### 5.2 Motor de Fórmulas — UX editor

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 5.2.1 | Syntax highlighting en editor de fórmulas (CodeMirror/Monaco) | v3.5.11 | 3-4 días |
| 5.2.2 | Autocomplete de funciones (19 asistencias + ACUMULADOS + CONCEPTO + etc.) | v3.5.11 | 3-4 días |
| 5.2.3 | Validación en tiempo real mientras se escribe | v3.5.11 | 2-3 días |
| 5.2.4 | **Sandbox de testing** para probar fórmulas sin guardar | v3.5.11 | 1 semana |

### 5.3 Animaciones GSAP

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 5.3.1 | GSAP en Planillas, Conceptos y Acumulados | v3.5.20 | 2-3 días c/u |
| 5.3.2 | Animaciones en modales (open/close) | v3.5.20 | 2 días |
| 5.3.3 | Transiciones entre páginas | v3.5.20 | 3-4 días |
| 5.3.4 | Respeto a `prefers-reduced-motion` (accesibilidad) | v3.5.20 | 2 h |
| 5.3.5 | **Fix DataTable "Procesando"** en `employee-manual-concepts` | v3.5.21 | 1 día (investigación) |
| 5.3.6 | Documentar edge cases de GSAP + DataTables | v3.5.21 | 1 día |

### 5.4 Acumulados y reportes

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 5.4.1 | Export a PDF de acumulados (hoy sólo Excel) | v3.5.12 | 2-3 días |
| 5.4.2 | Gráficas visuales (pie, bar) en vista de acumulados | v3.5.12 | 3-4 días |
| 5.4.3 | Subtotales por grupo en Excel | v3.5.12 | 1 día |
| 5.4.4 | Colores diferenciados por tipo ASIGNACION/DEDUCCION | v3.5.12 | 4 h |
| 5.4.5 | Performance con 1 000+ registros | v3.5.12 | 2-3 días |

### 5.5 Calendario empresarial

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 5.5.1 | Integrar referencia `insert_holidays_panama_2025.sql` en CHANGELOG | v3.5.6 WIP | 30 min |
| 5.5.2 | Guía breve de ejecución del script anual en documentación | v3.5.6 WIP | 1 h |

### 5.6 Deuda técnica varia

| # | Item | Origen | Esfuerzo |
|---|------|--------|----------|
| 5.6.1 | **Sistema Reproceso Histórico de Planillas** (5 fases diseñadas, pendiente aprobación) | ROADMAP | 3-4 semanas |
| 5.6.2 | Aplicar patrón de búsqueda dinámica a otros hardcodes (situaciones, tipos planilla) | v3.5.13 | 2-3 días |
| 5.6.3 | Panel admin para gestionar códigos de frecuencias | v3.5.13 | 3-4 días |
| 5.6.4 | Parámetros globales de sistema (config table) | v3.5.13 | 1 semana |
| 5.6.5 | Extraer JS inline a módulos y reutilizar helpers | TODO.md | 1-2 semanas |
| 5.6.6 | Validar fallback jQuery sin CDN | TODO.md | 1 día |
| 5.6.7 | Revisar endpoints AJAX para CSRF y permisos finos | TODO.md | 3-4 días |

---

## 📋 Recomendación de ejecución — plan de 3 olas

### 🌊 Ola 1 — Seguridad y cierre de flecos *(4-6 semanas)*
1. **Bloque 1 completo** (seguridad) — 2 semanas
2. **Bloque 2.4** (motor fórmulas 95 → 100 %) — 1 semana
3. **Bloque 2.2** (vacaciones 90 → 100 %) — 2-3 semanas

**Resultado**: sistema blindado + 3 módulos llevados al 100 %.

### 🌊 Ola 2 — Cierre de módulos grandes y quick wins *(8-10 semanas)*
1. **Bloque 2.1** (asistencias subfase 7.5) — 3-4 semanas
2. **Bloque 2.3** (multitenancy 85 → 100 %) — 6-8 semanas *(puede solaparse con 2.1)*
3. **Bloque 3 completo** (quick wins SMTP, licencias, wizard, export INNOVA) — 2-3 semanas en paralelo

**Resultado**: todos los módulos del ROADMAP al 100 %. Fases 1-7 cerradas.

### 🌊 Ola 3 — Nuevas capacidades *(Q2-Q3 2026)*
1. **Bloque 4.1** (Fase 8) — 6-7 semanas
2. **Bloque 4.2** (Fase 9) — 8-10 semanas *(puede solaparse parcialmente)*
3. **Bloque 5** (UX + deuda) — backlog continuo en paralelo

**Resultado**: reportes legislativos, API REST, integraciones bancarias.

---

## 📊 Vista de alto nivel — semaforización de módulos

```
Estado actual                    Post-Ola 1    Post-Ola 2    Post-Ola 3
─────────────────────────────────────────────────────────────────────────
Core System             100% ✅   100% ✅      100% ✅       100% ✅
Liquidaciones           100% ✅   100% ✅      100% ✅       100% ✅
Calendario Empresarial  100% ✅   100% ✅      100% ✅       100% ✅
Employee Import         100% ✅   100% ✅      100% ✅       100% ✅
Employee Fields/Docs    100% ✅   100% ✅      100% ✅       100% ✅
Manual Concepts         100% ✅   100% ✅      100% ✅       100% ✅
Loan System             100% ✅   100% ✅      100% ✅       100% ✅
XIII Mes                100% ✅   100% ✅      100% ✅       100% ✅
Super Admin             100% ✅   100% ✅      100% ✅       100% ✅
PostgreSQL              100% ✅   100% ✅      100% ✅       100% ✅
Export ERP INNOVA       100% ✅   100% ✅      100% ✅       100% ✅
Motor Fórmulas           95% 🟢   100% ✅      100% ✅       100% ✅
Vacaciones Panamá        90% 🟢   100% ✅      100% ✅       100% ✅
API Asistencias (7.5)    92% 🟢    92% 🟢      100% ✅       100% ✅
Multitenancy             85% 🟢    85% 🟢      100% ✅       100% ✅
SMTP + Notificaciones   ~60% 🟡    80% 🟢      100% ✅       100% ✅
Seguridad                85% 🟡    98% ✅       98% ✅        98% ✅
UI de Permisos           30% 🔴    30% 🔴       30% 🔴       100% ✅
Fase 8: Reportería+API    0% ❌     0% ❌        0% ❌        100% ✅
Fase 9.1 Bancos ACH PA    0% ❌     0% ❌        0% ❌        100% ✅
Fase 9.2 Contables        14% 🟢   14% 🟢       14% 🟢       100% ✅
Fase 9.3 Gobierno PA       0% ❌    0% ❌        0% ❌        100% ✅
```

> Nota sobre "Fase 9.2 Contables — 14 %": es el aporte del **Export ERP INNOVA**
> ya implementado (1 de 7 ítems de la subfase). El resto (SAP B1, QuickBooks,
> asientos automáticos, integraciones genéricas) queda pendiente.

---

## 🔗 Trazabilidad

Este ROADMAP consolidado se construyó cruzando:

- [`documentation/ROADMAP.md`](ROADMAP.md) — fases 1-9 y porcentajes oficiales
- [`documentation/TODO.md`](TODO.md) — pendientes operativos cortos
- [`documentation/changelog/v3.4.0.md` ... `v3.5.22.md`](changelog/) — 32 changelogs revisados en busca de "Próximos Pasos" / "Pendientes" / "Mejoras Futuras"

Cada item del ROADMAP consolidado tiene una columna **Origen** que apunta al changelog o documento donde fue identificado por primera vez.

---

**Responsable del cruce**: Claude (sesión 24-Abr-2026)
**Próxima revisión sugerida**: al completar la **Ola 1**, regenerar este documento para reflejar lo cerrado.
