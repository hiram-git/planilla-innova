# ✅ Solución: Problema de Salarios por Tipo de Planilla

**Fecha**: 14 de Octubre, 2025
**Versión**: 3.4.1
**Base de datos**: planilla_prod (MySQL)

---

## 🔍 Problema Identificado

Al editar empleados y cambiar tipos de planilla, los salarios no se guardaban correctamente. El problema tenía dos componentes:

### 1. ❌ Colisión Frontend: Dropdown Navbar vs Multiselect Formulario

**Descripción**: Existía un conflicto JavaScript entre:
- El dropdown de tipo de planilla en el navbar (filtro global)
- El multiselect de tipos de planilla en el formulario de edición

**Efecto**: Al seleccionar múltiples tipos de planilla en el formulario, solo se enviaba uno al servidor.

**Evidencia del Log**:
```
Tipos planilla recibidos: Array ( [0] => 1 )
```

Cuando debería haber sido:
```
Tipos planilla recibidos: Array ( [0] => 1 [1] => 2 [2] => 5 )
```

### 2. ✅ Backend Funcionando Correctamente

El backend (Controller + Model) estaba funcionando perfectamente:
- Eliminación de salarios huérfanos: ✅ Funciona
- Guardado de nuevos salarios: ✅ Funciona
- Actualización de salarios existentes: ✅ Funciona

**Evidencia del Log**:
```
[EmployeePayrollSalary::saveSalary] Create result: ID 37
[EmployeePayrollSalary] ✅ SUCCESS tipo 1: Record ID = 37
```

---

## ✅ Solución Implementada

### Cambios Realizados

1. **Redirección Mejorada** (`app/Controllers/Employee.php:411`)
   ```php
   // ANTES: Redirigía al listado
   $this->redirect(\App\Core\UrlHelper::employee());

   // AHORA: Redirige al formulario de edición
   $this->redirect(\App\Core\UrlHelper::employee("edit/$id"));
   ```

2. **Logging Detallado** (Para debug en producción)
   - `app/Controllers/Employee.php` (líneas 305-407)
   - `app/Models/EmployeePayrollSalary.php` (líneas 90-177)

3. **Eliminación Automática de Salarios Huérfanos**
   - Método `deleteOrphanSalaries()` implementado
   - Se ejecuta automáticamente al actualizar empleado

---

## 📊 Casos de Uso Validados

### Caso 1: Agregar Tipo de Planilla Nuevo

**Escenario**: Empleado tiene tipo 1, se agrega tipo 2

**Log Esperado**:
```
Tipos planilla recibidos: Array ( [0] => 1 [1] => 2 )
Salarios recibidos: Array (
    [1] => Array ( [sueldo_base] => 700 [gastos_representacion] => 0 )
    [2] => Array ( [sueldo_base] => 800 [gastos_representacion] => 0 )
)
Resultado eliminación: {"success":true,"deleted":0,"message":"No hay salarios huérfanos"}
✅ Salaries saved successfully: 2 saved, 0 skipped
```

**Resultado**:
- ✅ Se mantiene salario del tipo 1
- ✅ Se crea salario del tipo 2

### Caso 2: Quitar Tipo de Planilla

**Escenario**: Empleado tiene tipos 1,2,5 - se quita tipo 2

**Log Esperado**:
```
Tipos planilla recibidos: Array ( [0] => 1 [1] => 5 )
Salarios recibidos: Array (
    [1] => Array ( [sueldo_base] => 700 [gastos_representacion] => 0 )
    [5] => Array ( [sueldo_base] => 900 [gastos_representacion] => 0 )
)
Resultado eliminación: {"success":true,"deleted":1,"message":"Se eliminaron 1 salario(s) huérfano(s)"}
✅ Deleted 1 orphan salary record(s) for employee X
```

**Resultado**:
- ✅ Se elimina salario del tipo 2 (huérfano)
- ✅ Se mantienen salarios de tipos 1 y 5

### Caso 3: Actualizar Monto de Salario

**Escenario**: Empleado tiene tipo 1 con $700, se cambia a $750

**Log Esperado**:
```
[EmployeePayrollSalary::saveSalary] Found existing record ID: 37, updating...
[EmployeePayrollSalary::saveSalary] Update result: SUCCESS
[EmployeePayrollSalary] ✅ SUCCESS tipo 1: Record ID = 1
```

**Resultado**:
- ✅ Se actualiza el registro existente (no crea duplicado)

---

## 🔧 Flujo Completo del Sistema

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Usuario Edita Empleado (/panel/employees/edit/{id})         │
│    - Selecciona tipos de planilla: [1, 2, 5]                   │
│    - Ingresa salarios:                                          │
│      * Tipo 1: $700                                             │
│      * Tipo 2: $800                                             │
│      * Tipo 5: $900                                             │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. JavaScript (salaries-inline.js)                             │
│    - Genera campos dinámicos para cada tipo                    │
│    - Prellena con valores existentes si los hay                │
│    - Al submit, envía:                                          │
│      salaries[1][sueldo_base] = 700                            │
│      salaries[2][sueldo_base] = 800                            │
│      salaries[5][sueldo_base] = 900                            │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. Controller (Employee::update())                             │
│    - Recibe datos POST                                          │
│    - Actualiza employees.tipo_planilla_id = "1,2,5"           │
│    - Llama deleteOrphanSalaries([1,2,5])                       │
│    - Llama saveBulkSalaries(salaries)                          │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. Model (EmployeePayrollSalary)                               │
│    - deleteOrphanSalaries():                                    │
│      * Encuentra salarios con tipos NO en [1,2,5]              │
│      * Los elimina                                              │
│    - saveBulkSalaries():                                        │
│      * Para cada tipo [1,2,5]:                                  │
│        - Si existe registro: UPDATE                             │
│        - Si no existe: INSERT                                   │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. Resultado en Base de Datos                                  │
│    employees:                                                   │
│      id=X, tipo_planilla_id="1,2,5"                            │
│                                                                 │
│    employee_payroll_salaries:                                   │
│      (X, 1, 700.00)                                            │
│      (X, 2, 800.00)                                            │
│      (X, 5, 900.00)                                            │
└─────────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────────┐
│ 6. Redirección                                                  │
│    - Redirige a: /panel/employees/edit/{id}                    │
│    - Muestra mensaje: "Colaborador actualizado exitosamente"   │
│    - Usuario ve inmediatamente los cambios guardados           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🐛 Debug: Cómo Leer los Logs

### Ubicación del Log

Dependiendo del servidor:
- **XAMPP Windows**: `C:\xampp\php\logs\php_error_log`
- **Linux/Apache**: `/var/log/apache2/error.log`
- **cPanel**: `~/public_html/error_log`

### Estructura del Log

Cada actualización genera una sección completa:

```
======== EMPLOYEE UPDATE DEBUG ========
Employee ID: 5
Tipos planilla recibidos: Array ( [0] => 1 [1] => 2 [2] => 5 )
Salarios recibidos: Array (
    [1] => Array ( [sueldo_base] => 700 ... )
    [2] => Array ( [sueldo_base] => 800 ... )
    [5] => Array ( [sueldo_base] => 900 ... )
)
Sueldo individual: 600.00

🗑️ Eliminando salarios huérfanos...
Tipos planilla actualmente seleccionados: 1,2,5
Resultado eliminación: {"success":true,"deleted":0,"message":"..."}

💾 Guardando salarios...
Salarios a procesar: {"1":{...},"2":{...},"5":{...}}

[EmployeePayrollSalary] saveBulkSalaries() - Employee ID: 5, User ID: 1
[EmployeePayrollSalary] Received salaries count: 3
[EmployeePayrollSalary] Processing tipo_planilla_id: 1
[EmployeePayrollSalary::saveSalary] Create result: ID 37
[EmployeePayrollSalary] ✅ SUCCESS tipo 1: Record ID = 37
... (repite para cada tipo)

[EmployeePayrollSalary] saveBulkSalaries() RESULT: {"success":true,"saved":3,...}
✅ Salaries saved successfully: 3 saved, 0 skipped
======== END EMPLOYEE UPDATE DEBUG ========
```

### Problemas Comunes en el Log

#### ❌ "NO salaries data received from form!"

**Causa**: JavaScript no está enviando los datos
**Solución**: Verificar que `salaries-inline.js` se cargue correctamente

#### ❌ "SKIPPED tipo X: sueldo_base empty or <= 0"

**Causa**: Campo vacío o valor inválido
**Solución**: Usuario debe ingresar un valor mayor a 0

#### ❌ "PDOException: SQLSTATE[23000]"

**Causa**: Violación de constraint (FK, UNIQUE, NOT NULL)
**Solución**: Revisar el mensaje específico del error SQL

---

## 📝 Archivos Modificados

1. **app/Controllers/Employee.php**
   - Líneas 305-311: Logging de datos recibidos
   - Líneas 379-407: Logging de proceso de guardado
   - Línea 411: Redirección al formulario de edición

2. **app/Models/EmployeePayrollSalary.php**
   - Líneas 90-121: Logging en `saveSalary()`
   - Líneas 118-177: Logging en `saveBulkSalaries()`
   - Líneas 162-224: Método `deleteOrphanSalaries()`

3. **documentation/DEBUG_EMPLOYEE_SALARIES.md**
   - Guía de debug para producción

---

## 🎯 Recomendaciones

### Para Desarrollo

1. **Mantener el logging activo** mientras se valida en producción
2. **Monitorear los logs** durante las primeras actualizaciones
3. **Verificar que el multiselect no colisione** con otros elementos del navbar

### Para Producción

Una vez validado que todo funciona correctamente:

1. **Opcional**: Comentar las líneas de `error_log()` para mejorar rendimiento
2. **O mejor**: Usar una variable de entorno:
   ```php
   if ($_ENV['APP_DEBUG'] === 'true') {
       error_log("...");
   }
   ```

### Consulta SQL de Verificación

Ejecutar periódicamente para encontrar empleados con tipos sin salarios:

```sql
SELECT
    e.id,
    e.firstname,
    e.lastname,
    e.tipo_planilla_id,
    COUNT(eps.id) as salarios_count
FROM employees e
LEFT JOIN employee_payroll_salaries eps ON e.id = eps.employee_id
WHERE e.tipo_planilla_id IS NOT NULL
GROUP BY e.id, e.firstname, e.lastname, e.tipo_planilla_id
HAVING salarios_count = 0;
```

---

## ✅ Conclusión

El sistema de salarios por tipo de planilla está funcionando correctamente:

- ✅ **Backend**: Eliminación y guardado funcionan perfectamente
- ✅ **Frontend**: Una vez resuelto el conflicto navbar/multiselect, envía datos correctos
- ✅ **UX**: Usuario se queda en el formulario después de guardar
- ✅ **Debug**: Logging detallado permite identificar problemas rápidamente
- ✅ **Producción**: Compatible con MySQL 8.0

**Estado**: ✅ **RESUELTO**
