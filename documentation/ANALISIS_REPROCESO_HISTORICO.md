# 📋 Análisis: Sistema de Reproceso con Empleados y Salarios Históricos

**Fecha:** 15 de Octubre, 2025
**Versión Propuesta:** 3.4.2
**Tipo:** Feature - Mejora UX Reprocesar Planillas

---

## 🎯 Objetivo

Mejorar el flujo de reprocesar planillas para detectar cuando los empleados actuales no cumplen las validaciones de situación, y ofrecer al usuario la opción de recalcular usando:
- Empleados que **estaban activos en la fecha histórica** de la planilla
- Salarios que **estaban vigentes en esa fecha**

---

## 🔍 Problema Actual

Cuando un usuario intenta reprocesar una planilla antigua con validación de situación activada:

1. **Situación Real:**
   - La planilla es de hace 6 meses (ejemplo: Marzo 2025)
   - En Marzo 2025, había 20 empleados activos (situación_id = 1)
   - Hoy (Octubre 2025), solo 15 empleados están activos
   - 5 empleados fueron dados de baja entre Marzo y Octubre

2. **Comportamiento Actual:**
   - El sistema intenta reprocesar con los empleados actuales
   - Si la validación de situación está activada, solo procesa 15 empleados
   - **Resultado:** La planilla queda incorrecta porque faltan 5 empleados que SÍ deberían estar

3. **Expectativa del Usuario:**
   - Poder recalcular la planilla con los empleados que estaban activos en Marzo 2025
   - Usar los salarios que cada empleado tenía en Marzo 2025
   - Obtener una planilla históricamente correcta

---

## 💡 Solución Propuesta

### **Fase 1: Detección de Empleados Sin Validar**

Cuando el usuario intenta reprocesar con validación activada y no hay empleados que cumplan las condiciones:

1. **Contar Empleados Válidos vs Total**
   ```php
   $totalEmployees = count($employees); // Total sin validaciones
   $validEmployees = 0; // Contador después de validaciones

   // Después del foreach de procesamiento
   if ($validEmployees == 0 && $totalEmployees > 0) {
       // No se procesó ningún empleado a pesar de haber empleados disponibles
       throw new NoValidEmployeesException([
           'total_employees' => $totalEmployees,
           'valid_employees' => 0,
           'payroll_date' => $fecha,
           'payroll_period_start' => $periodo_inicio,
           'payroll_period_end' => $periodo_fin
       ]);
   }
   ```

2. **Excepción Específica**
   - Crear `App\Exceptions\NoValidEmployeesException`
   - Contiene información detallada para el frontend
   - Permite manejar el error de forma especial

---

### **Fase 2: Búsqueda de Empleados Históricos**

Nuevo método en el modelo `Payroll`:

```php
/**
 * Obtener empleados que estaban activos en una fecha específica
 *
 * @param string $fecha - Fecha de referencia (YYYY-MM-DD)
 * @param int $tipoPlanillaId - Tipo de planilla
 * @return array - Empleados activos en esa fecha
 */
public function getHistoricalEmployees($fecha, $tipoPlanillaId)
{
    $sql = "SELECT e.id, e.employee_id, e.firstname, e.lastname,
                   e.position_id, e.cargo_id, e.funcion_id, e.partida_id,
                   e.organigrama_id, e.schedule_id, e.tipo_planilla_id,
                   e.fecha_ingreso,
                   -- Detectar situación en fecha histórica
                   CASE
                       WHEN et.termination_date IS NOT NULL AND et.termination_date <= ?
                       THEN 2 -- Inactivo
                       WHEN e.fecha_ingreso > ?
                       THEN 0 -- Aún no ingresado
                       ELSE 1 -- Activo
                   END as situacion_historica,
                   -- Datos relacionales actuales
                   p.description as position_nombre,
                   c.descripcion as cargo_nombre,
                   f.descripcion as funcion_nombre,
                   pt.partida as partida_codigo,
                   s.nombre as schedule_nombre
            FROM employees e
            LEFT JOIN employee_terminations et ON et.employee_id = e.id
            LEFT JOIN position p ON p.id = e.position_id
            LEFT JOIN cargos c ON c.id = e.cargo_id
            LEFT JOIN funciones f ON f.id = e.funcion_id
            LEFT JOIN partidas pt ON pt.id = e.partida_id
            LEFT JOIN schedules s ON s.id = e.schedule_id
            WHERE FIND_IN_SET(?, e.tipo_planilla_id)
            AND e.fecha_ingreso <= ?  -- Ya había ingresado
            AND (et.termination_date IS NULL OR et.termination_date >= ?)  -- No había sido terminado
            ORDER BY e.lastname, e.firstname";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        $fecha,        // Para detectar terminación
        $fecha,        // Para validar fecha ingreso
        $tipoPlanillaId,
        $fecha,        // Empleado ya ingresado
        $fecha         // Empleado no terminado
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

**Lógica de Detección Histórica:**
- **Activo en fecha X:** `fecha_ingreso <= X AND (termination_date IS NULL OR termination_date >= X)`
- **Situación calculada:** Se determina dinámicamente según las fechas

---

### **Fase 3: Búsqueda de Salarios Históricos**

Nuevo método en el modelo `EmployeePayrollSalary`:

```php
/**
 * Obtener salario vigente de un empleado en una fecha específica
 *
 * @param int $employeeId
 * @param int $tipoPlanillaId
 * @param string $fecha - Fecha de referencia (YYYY-MM-DD)
 * @return array|null - Salario vigente o null
 */
public function getHistoricalSalary($employeeId, $tipoPlanillaId, $fecha)
{
    $sql = "SELECT eps.*
            FROM employee_payroll_salaries eps
            WHERE eps.employee_id = ?
            AND eps.tipo_planilla_id = ?
            AND eps.fecha_inicio <= ?
            AND (eps.fecha_fin IS NULL OR eps.fecha_fin >= ?)
            AND eps.is_active = 1
            ORDER BY eps.fecha_inicio DESC
            LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$employeeId, $tipoPlanillaId, $fecha, $fecha]);

    $salary = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$salary) {
        // Fallback: usar salario actual si no hay histórico
        return $this->getSalaryForPayroll($employeeId, $tipoPlanillaId);
    }

    return $salary;
}
```

**Lógica de Vigencia:**
- **Vigente en fecha X:** `fecha_inicio <= X AND (fecha_fin IS NULL OR fecha_fin >= X)`
- **Fallback:** Si no hay salario histórico, usar el actual

---

### **Fase 4: Interfaz de Usuario - Modal de Confirmación**

Cuando se detecta `NoValidEmployeesException`, mostrar modal:

```html
<!-- Modal: Sin Empleados Válidos -->
<div class="modal fade" id="noValidEmployeesModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h4 class="modal-title text-white">
                    <i class="fas fa-exclamation-triangle"></i>
                    Empleados No Disponibles
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Información del Problema -->
                <div class="alert alert-warning">
                    <h5><i class="fas fa-info-circle"></i> Situación Detectada</h5>
                    <p>
                        La planilla que intenta reprocesar es de fecha
                        <strong id="historicalPayrollDate">--/--/----</strong>,
                        pero los empleados actuales no cumplen las validaciones de situación.
                    </p>
                </div>

                <!-- Estadísticas -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-danger">
                                <i class="fas fa-users-slash"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Empleados Actuales Válidos</span>
                                <span class="info-box-number" id="currentValidEmployees">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success">
                                <i class="fas fa-history"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Empleados Activos en Fecha Histórica</span>
                                <span class="info-box-number" id="historicalEmployeesCount">--</span>
                                <small class="text-muted">Cargando...</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opciones -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-question-circle"></i> ¿Cómo desea proceder?
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Opción 1: Recalcular con empleados históricos -->
                        <div class="custom-control custom-radio mb-3">
                            <input type="radio"
                                   id="useHistoricalEmployees"
                                   name="reprocessOption"
                                   class="custom-control-input"
                                   value="historical"
                                   checked>
                            <label class="custom-control-label" for="useHistoricalEmployees">
                                <strong>Recalcular con empleados y salarios históricos</strong>
                                <br>
                                <small class="text-muted">
                                    Se utilizarán los empleados que estaban activos en
                                    <strong id="historicalDateText">--/--/----</strong>
                                    con los salarios vigentes en esa fecha.
                                </small>
                            </label>
                        </div>

                        <!-- Opción 2: Desactivar validación -->
                        <div class="custom-control custom-radio mb-3">
                            <input type="radio"
                                   id="disableValidation"
                                   name="reprocessOption"
                                   class="custom-control-input"
                                   value="no-validation">
                            <label class="custom-control-label" for="disableValidation">
                                <strong>Reprocesar sin validar situación</strong>
                                <br>
                                <small class="text-muted">
                                    Se procesarán todos los empleados actuales sin importar su situación.
                                </small>
                            </label>
                        </div>

                        <!-- Opción 3: Cancelar -->
                        <div class="custom-control custom-radio">
                            <input type="radio"
                                   id="cancelReprocess"
                                   name="reprocessOption"
                                   class="custom-control-input"
                                   value="cancel">
                            <label class="custom-control-label" for="cancelReprocess">
                                <strong>Cancelar operación</strong>
                                <br>
                                <small class="text-muted">
                                    No realizar ninguna acción y cerrar esta ventana.
                                </small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="confirmHistoricalReprocess">
                    <i class="fas fa-history"></i> Continuar
                </button>
            </div>
        </div>
    </div>
</div>
```

---

### **Fase 5: Flujo JavaScript**

```javascript
// Manejar respuesta de error "no_valid_employees"
$.ajax({
    url: reprocessUrl,
    method: "POST",
    data: ajaxData,
    success: function(response) {
        if (response.success) {
            // Continuar normalmente
        } else if (response.error_code === 'no_valid_employees') {
            // Mostrar modal de empleados históricos
            showNoValidEmployeesModal(response.data);
        }
    }
});

function showNoValidEmployeesModal(data) {
    // Llenar información
    $('#historicalPayrollDate').text(formatDate(data.payroll_date));
    $('#currentValidEmployees').text(data.valid_employees);
    $('#historicalDateText').text(formatDate(data.payroll_date));

    // Consultar empleados históricos disponibles
    $.ajax({
        url: `${baseUrl}/panel/payrolls/${data.payroll_id}/historical-employees`,
        method: 'GET',
        data: {
            date: data.payroll_date,
            tipo_planilla_id: data.tipo_planilla_id
        },
        success: function(response) {
            $('#historicalEmployeesCount').text(response.count);
        }
    });

    // Mostrar modal
    $('#noValidEmployeesModal').modal('show');
}

// Confirmar opción seleccionada
$('#confirmHistoricalReprocess').click(function() {
    const selectedOption = $('input[name="reprocessOption"]:checked').val();

    if (selectedOption === 'cancel') {
        $('#noValidEmployeesModal').modal('hide');
        return;
    }

    // Reprocesar con la opción seleccionada
    reprocessWithOption(selectedOption);
});

function reprocessWithOption(option) {
    const ajaxData = {
        csrf_token: csrfToken,
        validate_situacion: option === 'no-validation' ? 0 : 1,
        use_historical: option === 'historical' ? 1 : 0
    };

    // Reintentar reproceso
    $.ajax({
        url: reprocessUrl,
        method: "POST",
        data: ajaxData,
        success: function(response) {
            // Continuar con progreso
        }
    });
}
```

---

## 🔧 Cambios Técnicos Requeridos

### **1. Modelo Payroll.php**

**Nuevos Métodos:**
- `getHistoricalEmployees($fecha, $tipoPlanillaId)` - Buscar empleados activos en fecha
- `processPayrollWithHistorical($payrollId, $userId, $tipoPlanillaId, $useHistorical)` - Variante que usa datos históricos

**Modificaciones:**
- `processPayroll()` - Agregar contador de empleados válidos
- Lanzar excepción cuando `validEmployees == 0 && totalEmployees > 0`

### **2. Modelo EmployeePayrollSalary.php**

**Nuevos Métodos:**
- `getHistoricalSalary($employeeId, $tipoPlanillaId, $fecha)` - Buscar salario vigente en fecha

### **3. Controlador PayrollController.php**

**Nuevos Endpoints:**
- `GET /panel/payrolls/{id}/historical-employees` - Contar empleados históricos
- `POST /panel/payrolls/{id}/reprocess` - Modificar para aceptar `use_historical`

**Manejo de Excepciones:**
```php
try {
    $result = $this->payrollModel->reprocessPayroll(...);
} catch (NoValidEmployeesException $e) {
    echo json_encode([
        'success' => false,
        'error_code' => 'no_valid_employees',
        'data' => $e->getData()
    ]);
    exit;
}
```

### **4. Vista index.php**

**Agregar:**
- Modal `#noValidEmployeesModal`

### **5. JavaScript index.js**

**Nuevas Funciones:**
- `showNoValidEmployeesModal(data)`
- `reprocessWithOption(option)`
- Manejo de error `no_valid_employees`

### **6. Nueva Excepción**

**Archivo:** `app/Exceptions/NoValidEmployeesException.php`

```php
<?php

namespace App\Exceptions;

class NoValidEmployeesException extends \Exception
{
    private $data;

    public function __construct($data, $message = "No valid employees found", $code = 0)
    {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
```

---

## 📊 Flujo Completo

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Usuario Click "Reprocesar" con validación activada      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. Sistema intenta procesar con empleados actuales         │
│    - Obtiene empleados actuales                             │
│    - Aplica validaciones de situación                       │
│    - Cuenta: validEmployees = 0, totalEmployees = 20       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Sistema detecta: 0 válidos pero 20 disponibles          │
│    throw NoValidEmployeesException([                        │
│        'payroll_date' => '2025-03-15',                      │
│        'valid_employees' => 0,                              │
│        'total_employees' => 20                              │
│    ])                                                       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. Controlador captura excepción                           │
│    return JSON: {                                           │
│        success: false,                                      │
│        error_code: 'no_valid_employees',                    │
│        data: { ... }                                        │
│    }                                                        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. JavaScript detecta error_code                           │
│    showNoValidEmployeesModal(response.data)                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Modal consulta empleados históricos (AJAX)              │
│    GET /payrolls/{id}/historical-employees?date=2025-03-15 │
│    Response: { count: 25 }                                  │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. Usuario ve opciones:                                    │
│    ○ Recalcular con 25 empleados históricos (recomendado)  │
│    ○ Reprocesar sin validación (20 actuales)               │
│    ○ Cancelar                                               │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 8. Usuario selecciona "Históricos" y confirma              │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 9. Sistema reprocesa con datos históricos                  │
│    - getHistoricalEmployees('2025-03-15', tipoPlanilla=1)  │
│    - Para cada empleado:                                    │
│      · getHistoricalSalary(empId, 1, '2025-03-15')         │
│      · Usar situacion_historica calculada                   │
│    - Procesar normalmente                                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 10. Resultado: Planilla con 25 empleados procesados        │
│     (históricamente correcta)                               │
└─────────────────────────────────────────────────────────────┘
```

---

## ⚠️ Consideraciones Importantes

### **1. Datos Históricos Incompletos**

**Problema:** ¿Qué pasa si un empleado no tiene salario histórico registrado?

**Solución:**
```php
$historicalSalary = $this->getHistoricalSalary($empId, $tipoId, $fecha);

if (!$historicalSalary) {
    // Fallback: usar salario actual
    $historicalSalary = $this->getSalaryForPayroll($empId, $tipoId);

    // Log warning
    error_log("WARN: Empleado {$empId} sin salario histórico para {$fecha}, usando salario actual");
}
```

### **2. Validación de Conceptos**

**Problema:** Los conceptos también tienen validaciones de situación.

**Solución:** Usar la `situacion_historica` calculada en lugar de `situacion_id` actual:

```php
foreach ($employees as $employee) {
    $employeeSituacion = $employee['situacion_historica'] ?? $employee['situacion_id'];

    foreach ($conceptos as $concepto) {
        if (!$this->validateConceptConditions($concepto, $payroll, $employeeSituacion, $validateSituacion)) {
            continue;
        }
        // ... procesar concepto
    }
}
```

### **3. Performance**

**Problema:** Consultar salarios históricos en un loop puede ser lento.

**Solución:** Precarga de salarios históricos:

```php
// Antes del loop de empleados
$historicalSalaries = $this->employeePayrollSalary->getBulkHistoricalSalaries(
    array_column($employees, 'id'),
    $tipoPlanillaId,
    $fecha
);

// En el loop
foreach ($employees as $employee) {
    $salary = $historicalSalaries[$employee['id']] ?? $this->getFallbackSalary($employee['id']);
    // ...
}
```

### **4. Auditoría**

**Recomendación:** Registrar en logs cuando se usa modo histórico:

```php
if ($useHistorical) {
    error_log("Reprocesando planilla {$payrollId} con datos históricos de fecha {$fecha}. Usuario: {$userId}");
}
```

---

## 📈 Beneficios

1. **✅ Corrección Histórica:** Planillas antiguas se recalculan correctamente
2. **✅ UX Mejorada:** Usuario informado y con opciones claras
3. **✅ Flexibilidad:** Tres opciones según caso de uso
4. **✅ Auditoría:** Logs detallados de operaciones
5. **✅ Fallback:** Sistema robusto ante datos incompletos

---

## 🚀 Próximos Pasos

1. Revisar y aprobar análisis
2. Implementar nueva excepción
3. Agregar métodos históricos a modelos
4. Modificar controlador para capturar excepción
5. Crear modal en vista
6. Implementar lógica JavaScript
7. Testing completo (casos edge)
8. Actualizar documentación (CHANGELOG, ROADMAP, CLAUDE.md)

---

## 📝 Casos de Prueba

### **Caso 1: Empleados actuales NO válidos, históricos SÍ**
- **Setup:** Planilla Marzo 2025, hoy Octubre 2025, 5 empleados dados de baja
- **Esperado:** Modal mostrado, opción históricos con 25 empleados
- **Resultado:** Planilla con 25 empleados procesados

### **Caso 2: No hay empleados históricos**
- **Setup:** Planilla muy antigua, todos los empleados terminados
- **Esperado:** Modal mostrado, count históricos = 0
- **Comportamiento:** Opción históricos deshabilitada, solo "sin validación" o "cancelar"

### **Caso 3: Usuario cancela**
- **Setup:** Modal mostrado, usuario elige "Cancelar"
- **Esperado:** Modal cerrado, planilla sin cambios, estado restaurado a PROCESADA

### **Caso 4: Salarios históricos faltantes**
- **Setup:** 3 de 25 empleados sin salarios históricos
- **Esperado:** Sistema usa salario actual como fallback + log warning
- **Resultado:** Planilla procesada exitosamente con advertencia en logs

---

**Estado:** ⏳ **PENDIENTE APROBACIÓN E IMPLEMENTACIÓN**
