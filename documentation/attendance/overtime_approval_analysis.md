# 📋 ANÁLISIS COMPLETO: Sistema de Aprobación de Horas Extras

**Fecha**: 02 de Febrero, 2026
**Versión**: 1.0
**Estado**: Análisis Completo - Pendiente Implementación
**Módulo**: Sistema de Asistencias - Subfase 7.6

---

## 📊 RESUMEN EJECUTIVO

Este documento contiene el análisis exhaustivo para implementar un sistema de aprobación de horas extras en el módulo de asistencias del sistema INNOVA Planillas. Se presentan 3 opciones de implementación (Ligera, Intermedia, Empresarial) con la recomendación de implementar la **Opción B (Sistema Intermedio)** por su balance óptimo entre funcionalidad y complejidad.

**Impacto Esperado:**
- ✅ Control total sobre horas extras pagadas
- ✅ Cumplimiento con legislación laboral panameña (Art. 39)
- ✅ Auditoría completa de aprobaciones
- ✅ Reducción de errores en nómina
- ✅ Workflow automatizado con notificaciones

---

## 🎯 CONTEXTO Y NECESIDAD

### Estado Actual del Sistema (v3.5.19)

**Módulo de Asistencias: 92% Completado**

El sistema actual ya cuenta con:
- ✅ **OvertimeCalculator**: Calcula horas extras 25%/50% según legislación panameña
- ✅ **AttendanceCalculation**: Almacena cálculos diarios
- ✅ **AttendanceConceptMapper**: Mapea horas extras → conceptos de planilla
- ✅ **PayrollAttendanceIntegrator**: Integra asistencias en planillas automáticamente
- ✅ **Campo `permite_horas_extras`**: En tabla employees (TINYINT, indexado)
- ✅ **Campo `requiere_aprobacion`**: En tabla attendance_concepts_mapping (NO usado actualmente)

**Lo que NO existe:**
- ❌ Sistema de aprobación/rechazo de horas extras
- ❌ Workflow de autorizaciones
- ❌ Interfaz de gestión de aprobaciones
- ❌ Historial auditable de decisiones
- ❌ Filtros para procesar solo HE aprobadas

### Puntos de Integración Identificados

#### A. OvertimeCalculator.php (líneas 47-60)
```php
public function calculateOvertime($totalHours, $regularHours = 8)
{
    $overtimeHours = max(0, $totalHours - $regularHours);
    $overtime25 = min($overtimeHours, 3); // Primeras 3h al 25%
    $overtime50 = max(0, $overtimeHours - 3); // Resto al 50%
}
```
**Punto de integración**: Cálculo automático → necesita validación de aprobación antes de pagar.

#### B. AttendanceConceptMapper.php (líneas 167-230)
```php
private function mapOvertime25(array $summary, float $tarifaHora): array
private function mapOvertime50(array $summary, float $tarifaHora): array
```
**Punto de integración**: Aquí se mapean HE → conceptos. Debe filtrar solo aprobadas.

#### C. Campo `permite_horas_extras` (employees table)
```sql
permite_horas_extras TINYINT(1) NOT NULL DEFAULT 1
COMMENT 'Indica si el empleado es elegible para horas extras (1=Sí, 0=No/Exento)'
INDEX idx_employees_overtime_eligible (permite_horas_extras ASC)
```
**Punto de integración**: Validación previa - solo empleados con `= 1` pueden generar solicitudes.

---

## 💡 OPCIONES DE IMPLEMENTACIÓN

### OPCIÓN A: Sistema Ligero

**Complejidad**: Baja | **Tiempo**: 2-3 días | **Recomendado**: Equipos pequeños (<30 empleados)

**Características:**
- Aprobación simple (PENDIENTE → APROBADO/RECHAZADO)
- Un solo nivel de aprobación (supervisor directo)
- Interfaz básica con listado + botones
- Sin historial de cambios
- Sin notificaciones automáticas

**Estructura BD:**
```sql
CREATE TABLE overtime_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    calculation_id INT NOT NULL,
    overtime_date DATE NOT NULL,
    overtime_25_hours DECIMAL(5,2),
    overtime_50_hours DECIMAL(5,2),
    status ENUM('PENDIENTE', 'APROBADO', 'RECHAZADO') DEFAULT 'PENDIENTE',
    approved_by INT DEFAULT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ota_employee FOREIGN KEY (employee_id)
        REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT chk_overtime_eligible CHECK (
        (SELECT permite_horas_extras FROM employees WHERE id = employee_id) = 1
    )
);
```

**Pros:**
- ✅ Implementación rápida
- ✅ Fácil de usar
- ✅ Bajo impacto en sistema actual

**Contras:**
- ❌ No escalable
- ❌ Sin workflow multinivel
- ❌ Sin auditoría completa

---

### OPCIÓN B: Sistema Intermedio ⭐ RECOMENDADA

**Complejidad**: Media | **Tiempo**: 4-6 días | **Recomendado**: Empresas medianas (30-200 empleados)

**Características:**
- ✅ Aprobación multinivel (supervisor → gerente)
- ✅ Workflow completo con estados
- ✅ Historial auditable de cambios
- ✅ Notificaciones email automáticas
- ✅ Dashboard gerencial con filtros
- ✅ Aprobación batch (múltiples días)
- ✅ Integración completa con `permite_horas_extras`

**Estructura BD:**
```sql
CREATE TABLE overtime_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_overtime_25 DECIMAL(10,2),
    total_overtime_50 DECIMAL(10,2),
    total_amount DECIMAL(10,2) COMMENT 'Monto calculado en base a salario',

    -- Workflow
    approval_level INT DEFAULT 1 COMMENT '1=supervisor, 2=gerente',
    current_approver_id INT,
    status ENUM('PENDIENTE', 'EN_REVISION', 'APROBADO', 'RECHAZADO', 'CANCELADO') DEFAULT 'PENDIENTE',

    -- Aprobación Nivel 1 (Supervisor)
    approved_by_level_1 INT DEFAULT NULL,
    approved_at_level_1 TIMESTAMP NULL,
    comments_level_1 TEXT,

    -- Aprobación Nivel 2 (Gerente) - Opcional según configuración
    approved_by_level_2 INT DEFAULT NULL,
    approved_at_level_2 TIMESTAMP NULL,
    comments_level_2 TEXT,

    -- Rechazo
    rejected_by INT DEFAULT NULL,
    rejected_at TIMESTAMP NULL,
    rejection_reason TEXT,

    -- Metadata
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Constraints
    UNIQUE KEY idx_unique_period (employee_id, period_start, period_end),
    KEY idx_status (status),
    KEY idx_approver (current_approver_id),

    CONSTRAINT fk_ota_employee FOREIGN KEY (employee_id)
        REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_ota_approver_l1 FOREIGN KEY (approved_by_level_1)
        REFERENCES admin(id) ON DELETE SET NULL,
    CONSTRAINT fk_ota_approver_l2 FOREIGN KEY (approved_by_level_2)
        REFERENCES admin(id) ON DELETE SET NULL
);

CREATE TABLE overtime_approval_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    approval_id INT NOT NULL,
    action ENUM('CREADO', 'APROBADO_L1', 'APROBADO_L2', 'RECHAZADO', 'MODIFICADO', 'CANCELADO') NOT NULL,
    performed_by INT NOT NULL,
    previous_status VARCHAR(50),
    new_status VARCHAR(50),
    comments TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY idx_approval (approval_id),
    KEY idx_action (action),

    CONSTRAINT fk_history_approval FOREIGN KEY (approval_id)
        REFERENCES overtime_approvals(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_user FOREIGN KEY (performed_by)
        REFERENCES admin(id) ON DELETE CASCADE
);

-- Índice adicional en attendance_calculations para performance
CREATE INDEX idx_calc_overtime_eligible
ON attendance_calculations(employee_id, date, overtime_25_hours, overtime_50_hours);
```

**Workflow Completo:**
```
1. CÁLCULO AUTOMÁTICO
   ↓ AttendanceCalculator (ya existe)

2. GENERACIÓN SOLICITUDES
   ↓ Cron diario: OvertimeApprovalService->generatePendingApprovals()
   ↓ Filtro: permite_horas_extras = 1
   ↓ Crea registro status=PENDIENTE
   ↓ Asigna current_approver_id = supervisor
   ↓ Notifica email al supervisor

3. APROBACIÓN NIVEL 1 (Supervisor)
   ↓ Dashboard: /panel/overtime/approvals
   ↓ Ve solicitudes de su equipo
   ↓ [Aprobar] → status = 'APROBADO' (si 1 nivel)
   ↓ [Aprobar] → status = 'EN_REVISION' (si 2 niveles) + notifica gerente
   ↓ [Rechazar] → status = 'RECHAZADO' + motivo
   ↓ Se registra en overtime_approval_history

4. APROBACIÓN NIVEL 2 (Gerente) - Opcional
   ↓ Solo si configurado requiere 2 niveles
   ↓ Dashboard gerencial con filtros
   ↓ [Aprobar Final] → status = 'APROBADO'
   ↓ [Rechazar] → status = 'RECHAZADO'

5. INTEGRACIÓN CON PLANILLAS
   ↓ PayrollAttendanceIntegrator->processPayrollAttendance()
   ↓ AttendanceConceptMapper filtra:
   ↓   - permite_horas_extras = 1
   ↓   - status = 'APROBADO'
   ↓ Solo incluye HE aprobadas en conceptos
   ↓ Genera planilla final
```

**Pros:**
- ✅ Balance perfecto funcionalidad/complejidad
- ✅ Escalable a Opción C
- ✅ Auditoría completa
- ✅ Notificaciones automáticas
- ✅ Dashboard gerencial
- ✅ Usa campo `permite_horas_extras` existente

**Contras:**
- ⚠️ Requiere configurar jerarquías (supervisor/gerente)
- ⚠️ Más complejo que Opción A

---

### OPCIÓN C: Sistema Empresarial Completo

**Complejidad**: Alta | **Tiempo**: 8-12 días | **Recomendado**: Grandes empresas (>200 empleados)

**Características Adicionales (sobre Opción B):**
- Políticas configurables por departamento/cargo
- Límites automáticos (diario/semanal/mensual)
- Justificación obligatoria para HE
- Aprobación previa vs. aprobación posterior
- Override de `permite_horas_extras` por política
- Integración con Sistema de Alertas
- Reportes ejecutivos avanzados
- Dashboard analítico con KPIs

**Estructura BD Adicional:**
```sql
CREATE TABLE overtime_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department_id INT,
    position_id INT,

    -- Límites
    max_daily_overtime DECIMAL(5,2) COMMENT 'Horas extras máximas por día',
    max_weekly_overtime DECIMAL(5,2),
    max_monthly_overtime DECIMAL(10,2),

    -- Override campo permite_horas_extras
    override_employee_eligibility TINYINT(1) DEFAULT 0
        COMMENT 'Si=1, ignora campo permite_horas_extras del empleado',

    -- Requisitos
    requires_justification TINYINT(1) DEFAULT 1,
    requires_prior_approval TINYINT(1) DEFAULT 0 COMMENT 'Aprobación antes de trabajar',

    -- Workflow
    approval_levels INT DEFAULT 1,
    level_1_role VARCHAR(50) COMMENT 'SUPERVISOR, JEFE_DEPARTAMENTO',
    level_2_role VARCHAR(50) COMMENT 'GERENTE, DIRECTOR',

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE overtime_pre_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    requested_date DATE NOT NULL,
    requested_hours DECIMAL(5,2),
    justification TEXT NOT NULL,
    status ENUM('PENDIENTE', 'APROBADO', 'RECHAZADO'),
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Pros:**
- ✅ Control total y granular
- ✅ Prevención de excesos
- ✅ Cumplimiento normativo robusto
- ✅ Reportería completa

**Contras:**
- ❌ Implementación larga (8-12 días)
- ❌ Requiere configuración extensa
- ❌ Puede ser "overkill" para empresas pequeñas

---

## 🎯 RECOMENDACIÓN: OPCIÓN B

### Justificación

1. **Balance Óptimo**: Funcionalidad robusta sin complejidad excesiva
2. **Escalabilidad**: Puede evolucionar a Opción C si crece la empresa
3. **ROI Alto**: Automatiza workflow crítico con inversión razonable
4. **Tiempo Razonable**: 4-6 días vs. 8-12 días de Opción C
5. **Usa Infraestructura Existente**: Campo `permite_horas_extras` + índice ya creados

### Métricas de Éxito Esperadas

Post-implementación:
1. **Eficiencia**: Tiempo promedio aprobación < 24h
2. **Control**: % horas extras rechazadas vs. solicitadas
3. **Cumplimiento**: 100% HE con registro de aprobación antes de pago
4. **Auditoría**: Historial completo de decisiones
5. **Satisfacción**: Feedback positivo de supervisores y empleados

---

## ⚖️ CONSIDERACIONES LEGALES (Panamá)

### Código de Trabajo - Artículo 39

**Requisitos Legales:**
- ✅ Acuerdo previo entre empleador y trabajador
- ✅ Pago con recargos (25% primeras 3h, 50% adicionales)
- ✅ Documentación de horas trabajadas

**Cómo el Sistema Cumple:**
- ✅ Sistema de aprobación = Acuerdo documentado
- ✅ Historial auditable = Evidencia legal
- ✅ Respeta campo `permite_horas_extras` = Personal exento identificado
- ✅ Integración con OvertimeCalculator = Recargos correctos garantizados

### Personal Exento

Empleados con `permite_horas_extras = 0`:
- Gerentes y ejecutivos
- Personal mensual sin derecho a HE
- Contratos especiales

**Tratamiento en el Sistema:**
1. NO generan solicitudes de aprobación
2. NO se pagan horas extras (aunque trabajen más de 8h)
3. Se registra en log para auditoría

---

## 🔄 INTEGRACIÓN CON `permite_horas_extras`

### Campo Existente

```sql
-- Tabla: employees
permite_horas_extras TINYINT(1) NOT NULL DEFAULT 1
INDEX idx_employees_overtime_eligible (permite_horas_extras ASC)
```

**Valores:**
- `1` = Empleado ELEGIBLE para horas extras
- `0` = Empleado EXENTO (no genera solicitudes, no se pagan HE)

### Flujo de Validación

```
TODOS LOS EMPLEADOS
    ↓
    ├─→ permite_horas_extras = 1 (ELEGIBLES)
    │   ↓
    │   └─→ Genera solicitud → Requiere aprobación → Puede pagarse
    │
    └─→ permite_horas_extras = 0 (EXENTOS)
        ↓
        └─→ NO genera solicitud → Log auditoría → NO se paga
```

### Casos de Uso

**Caso 1: Empleado Elegible**
```
María López (Analista) - permite_horas_extras = 1
Trabajó 11h el 01/02/2026 (3h extras)

Flujo:
1. AttendanceCalculator calcula: 3.0h extras
2. OvertimeApprovalService crea solicitud PENDIENTE
3. Supervisor recibe notificación
4. Supervisor APRUEBA
5. AttendanceConceptMapper incluye en planilla
6. Resultado: HE PAGADAS con recargo 25%
```

**Caso 2: Empleado Exento**
```
Juan Pérez (Gerente) - permite_horas_extras = 0
Trabajó 10h el 01/02/2026 (2h extras)

Flujo:
1. AttendanceCalculator calcula: 2.0h extras
2. OvertimeApprovalService NO crea solicitud (filtrado)
3. Se registra en log: "Empleado E-001 EXENTO"
4. AttendanceConceptMapper NO incluye en planilla
5. Resultado: HE NO PAGADAS
```

---

## 📊 INTERFAZ DE USUARIO (UI/UX)

### Dashboard Principal - Aprobaciones Pendientes

```
┌─────────────────────────────────────────────────────────────┐
│  📋 Aprobación de Horas Extras - Mi Equipo                  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Filtros: [Todos ▼] [Este Mes ▼] [Pendientes ▼] [Buscar]  │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Empleado        │ Período      │ HE 25% │ HE 50% │ $   │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ Juan Pérez      │ 01-15 Feb/26 │  3.5h  │  2.0h  │ 🟡  │ │
│  │ Cod: E-001      │ 15 días      │        │        │     │ │
│  │ Analista        │              │ $52.50 │ $48.00 │     │ │
│  │                                                          │ │
│  │ [✓ Aprobar] [✗ Rechazar] [👁 Ver Detalle] [📧]      │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ María López     │ 01-15 Feb/26 │  4.5h  │  1.5h  │ 🟡  │ │
│  │ Cod: E-045      │ 12 días      │        │        │     │ │
│  │ Supervisor      │              │ $67.50 │ $36.00 │     │ │
│  │                                                          │ │
│  │ [✓ Aprobar] [✗ Rechazar] [👁 Ver Detalle] [📧]      │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  Mostrando 2 de 2 solicitudes pendientes                    │
│  Total a aprobar: $204.00                                   │
│                                                              │
│  [Aprobar Seleccionadas] [Rechazar Seleccionadas]          │
└─────────────────────────────────────────────────────────────┘

Estados:
🟡 = PENDIENTE
🟢 = APROBADO
🔴 = RECHAZADO
🔵 = EN_REVISION (esperando nivel 2)
```

### Modal - Detalle de Solicitud

```
┌────────────────────────────────────────────────────────────┐
│  📊 Detalle Horas Extras - Juan Pérez (E-001)              │
├────────────────────────────────────────────────────────────┤
│                                                             │
│  Período: 01/02/2026 - 15/02/2026 (15 días)               │
│  Cargo: Analista | Depto: Tecnología                       │
│  Salario Base: $1,500.00 | Tarifa Hora: $6.82             │
│                                                             │
│  Resumen:                                                   │
│  • Total HE 25%: 3.5 horas → $29.86                        │
│  • Total HE 50%: 2.0 horas → $20.46                        │
│  • Total General: 5.5 horas → $50.32                       │
│                                                             │
│  Detalle Diario:                                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Fecha      │ HE 25% │ HE 50% │ Total   │ Estado    │  │
│  ├──────────────────────────────────────────────────────┤  │
│  │ 05/02/2026 │ 1.5h   │ 0.0h   │ $12.83  │ ✅ Normal │  │
│  │ 07/02/2026 │ 2.0h   │ 0.5h   │ $20.46  │ ✅ Normal │  │
│  │ 12/02/2026 │ 0.0h   │ 1.5h   │ $15.35  │ ⚠️ Feriado│  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  Comentarios:                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ [Textarea para comentarios del aprobador]             │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  [✓ Aprobar Todo] [✗ Rechazar Todo] [Cerrar]              │
└────────────────────────────────────────────────────────────┘
```

### Historial de Aprobaciones

```
┌────────────────────────────────────────────────────────────┐
│  📜 Historial de Aprobaciones - Enero 2026                 │
├────────────────────────────────────────────────────────────┤
│                                                             │
│  Filtros: [Todos▼] [Estado: Todos▼] [Excel] [PDF]        │
│                                                             │
│  Empleado       │ Período  │ HE    │ Monto  │ Estado │ ▾  │
│  ───────────────┼──────────┼───────┼────────┼────────┼──  │
│  Juan Pérez     │ 01-15 Ene│ 5.5h  │ $50.32 │ 🟢 APR │ 👁 │
│  María López    │ 01-15 Ene│ 6.0h  │ $72.00 │ 🟢 APR │ 👁 │
│  Carlos Ruiz    │ 01-15 Ene│ 4.0h  │ $48.00 │ 🔴 REC │ 👁 │
│  Ana Torres     │ 16-31 Ene│ 7.5h  │ $90.00 │ 🟢 APR │ 👁 │
│                                                             │
│  Total Aprobado: $212.32 | Total Rechazado: $48.00        │
└────────────────────────────────────────────────────────────┘
```

---

## 🚀 ROADMAP DE ESCALAMIENTO A OPCIÓN C

### Cuando Escalar

**Indicadores:**
1. Empresa supera 200 empleados
2. Múltiples departamentos con políticas diferentes
3. Necesidad de límites automáticos por cargo
4. Excesos frecuentes en horas extras
5. Requerimiento de aprobación previa (antes de trabajar)

### Cambios Necesarios (Opción B → C)

**Base de Datos:**
- Agregar tabla `overtime_policies`
- Agregar tabla `overtime_pre_approvals`
- Modificar `overtime_approvals` para referenciar policy_id

**Servicios:**
- Crear `OvertimePolicyEvaluator.php`
- Crear `OvertimePreApprovalService.php`
- Modificar `OvertimeApprovalService` para validar políticas

**UI:**
- Módulo CRUD de políticas (/panel/overtime/policies)
- Vista pre-aprobaciones (/panel/overtime/pre-approvals)
- Dashboard analítico con KPIs

**Tiempo Estimado Escalamiento:** 4-6 días adicionales

---

## 📚 REFERENCIAS

- **Código de Trabajo de Panamá**: Artículos 31, 38, 39, 48
- **Documentación Actual**: `documentation/changelog/v3.4.7.md` (Integración Planillas-Asistencias)
- **CLAUDE.md**: Estado actual del sistema (v3.5.19)
- **Arquitectura Existente**:
  - `app/Services/Attendance/OvertimeCalculator.php`
  - `app/Services/Attendance/AttendanceConceptMapper.php`
  - `app/Services/Attendance/PayrollAttendanceIntegrator.php`
  - `app/Models/AttendanceCalculation.php`

---

## 📋 CONCLUSIÓN

La **Opción B (Sistema Intermedio)** representa la mejor decisión para implementar en este momento:

1. ✅ Resuelve 100% el problema de control de horas extras
2. ✅ Tiempo de implementación razonable (4-6 días)
3. ✅ Escalable a Opción C cuando sea necesario
4. ✅ Usa infraestructura existente (`permite_horas_extras`)
5. ✅ Cumple con legislación panameña
6. ✅ Proporciona auditoría completa

**Siguiente Paso**: Ver `overtime_approval_implementation_plan.md` para plan detallado de implementación.

---

**Documento creado por**: Claude Code
**Fecha**: 02 de Febrero, 2026
**Versión del Sistema**: 3.5.19
**Estado**: Análisis Completo ✅
