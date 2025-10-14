# 🔍 Debug: Salarios por Tipo de Planilla en Producción

## Problema Identificado

Al actualizar empleados en producción (MySQL 8.0), los salarios por tipo de planilla no se están guardando correctamente, mientras que en desarrollo (MariaDB 10.4.32) funciona sin problemas.

## Cambios Implementados

### 1. ✅ Logging Detallado

Se agregó logging exhaustivo en:

- **`app/Controllers/Employee.php`** (método `update()`, líneas 305-407):
  - Datos recibidos del formulario (`edit_tipo_planilla`, `salaries`, `sueldo_individual`)
  - Tipos de planilla seleccionados
  - Proceso de eliminación de salarios huérfanos
  - Proceso de guardado de salarios
  - Resultado de cada operación

- **`app/Models/EmployeePayrollSalary.php`**:
  - `saveBulkSalaries()` (líneas 118-177): Log de cada salario procesado
  - `saveSalary()` (líneas 90-121): Log de insert/update con detalles de errores PDO

### 2. ✅ Redirección Mejorada

Ahora después de actualizar un empleado:
- ✅ **ANTES**: Redirigía a `/panel/employees` (listado)
- ✅ **AHORA**: Redirige a `/panel/employees/edit/{id}` (mismo formulario con mensaje de éxito)

Esto permite ver inmediatamente si los cambios se guardaron correctamente.

## Cómo Revisar los Logs en Producción

### Opción 1: PHP Error Log (Recomendado)

La ubicación del archivo de log depende de la configuración del servidor. Ubicaciones comunes:

```bash
# XAMPP Windows
C:\xampp\php\logs\php_error_log

# Linux/Apache
/var/log/apache2/error.log
/var/log/httpd/error_log

# cPanel
~/public_html/error_log
~/logs/error_log

# Plesk
/var/log/httpd/domains/tudominio.com/error.log
```

**Ver los logs en tiempo real:**

```bash
# Linux/Mac
tail -f /var/log/apache2/error.log | grep "EMPLOYEE UPDATE"

# Windows (PowerShell)
Get-Content C:\xampp\php\logs\php_error_log -Wait -Tail 50
```

### Opción 2: Verificar configuración PHP

Crear un archivo temporal `phpinfo.php`:

```php
<?php
phpinfo();
?>
```

Buscar la línea `error_log` para ver dónde se están guardando los logs.

## Qué Buscar en los Logs

Cuando actualices un empleado, verás una secuencia completa como esta:

```
======== EMPLOYEE UPDATE DEBUG ========
Employee ID: 17
Tipos planilla recibidos: Array ( [0] => 2 [1] => 5 )
Salarios recibidos: Array ( [2] => Array ( [sueldo_base] => 250.00 [gastos_representacion] => 0 ) [5] => Array ( [sueldo_base] => 350.00 [gastos_representacion] => 0 ) )
Sueldo individual: 500.00

🗑️ Eliminando salarios huérfanos...
Tipos planilla actualmente seleccionados: 2,5
Resultado eliminación: {"success":true,"deleted":1,"message":"Se eliminaron 1 salario(s) huérfano(s)"}

💾 Guardando salarios...
Salarios a procesar: {"2":{"sueldo_base":"250.00","gastos_representacion":"0"},"5":{"sueldo_base":"350.00","gastos_representacion":"0"}}

[EmployeePayrollSalary] saveBulkSalaries() - Employee ID: 17, User ID: 1
[EmployeePayrollSalary] Received salaries count: 2

[EmployeePayrollSalary] Processing tipo_planilla_id: 2
[EmployeePayrollSalary] Salary data: {"sueldo_base":"250.00","gastos_representacion":"0"}
[EmployeePayrollSalary::saveSalary] Employee: 17, Tipo: 2
[EmployeePayrollSalary::saveSalary] Found existing record ID: 22, updating...
[EmployeePayrollSalary::saveSalary] Update result: SUCCESS
[EmployeePayrollSalary] ✅ SUCCESS tipo 2: Record ID = 1

[EmployeePayrollSalary] Processing tipo_planilla_id: 5
[EmployeePayrollSalary] Salary data: {"sueldo_base":"350.00","gastos_representacion":"0"}
[EmployeePayrollSalary::saveSalary] Employee: 17, Tipo: 5
[EmployeePayrollSalary::saveSalary] No existing record, creating new...
[EmployeePayrollSalary::saveSalary] Create result: ID 23
[EmployeePayrollSalary] ✅ SUCCESS tipo 5: Record ID = 23

[EmployeePayrollSalary] saveBulkSalaries() RESULT: {"success":true,"saved":2,"errors":0,"skipped":0,"total":2,"error_details":[]}

✅ Salaries saved successfully: 2 saved, 0 skipped
======== END EMPLOYEE UPDATE DEBUG ========
```

## Posibles Problemas y Soluciones

### ❌ Problema 1: "NO salaries data received from form!"

**Causa**: El JavaScript no está enviando los datos del formulario correctamente.

**Solución**:
1. Verificar que el JavaScript `salaries-inline.js` se esté cargando
2. Revisar la consola del navegador (F12) para errores JavaScript
3. Verificar que los campos tengan los nombres correctos: `salaries[{tipo_id}][sueldo_base]`

### ❌ Problema 2: "SKIPPED tipo X: sueldo_base empty or <= 0"

**Causa**: El campo de sueldo está vacío o tiene valor 0.

**Solución**:
1. Verificar que el usuario ingrese un valor válido mayor a 0
2. Revisar que el JavaScript esté prellenando los valores existentes correctamente
3. Verificar `window.EMPLOYEE_SALARIES` en la consola del navegador

### ❌ Problema 3: "PDOException: SQLSTATE[XXXXX]"

**Causa**: Error de base de datos (constraint, tipo de dato incorrecto, etc.)

**Solución**: El log mostrará el mensaje exacto del error SQL. Ejemplos comunes:

- `SQLSTATE[23000]`: Violación de constraint (FK, UNIQUE, NOT NULL)
- `SQLSTATE[42S22]`: Columna no encontrada
- `SQLSTATE[42000]`: Error de sintaxis SQL

## Verificación Manual en Base de Datos

Después de actualizar un empleado, verificar directamente en la BD:

```sql
-- Ver tipos de planilla asignados al empleado
SELECT id, firstname, lastname, tipo_planilla_id
FROM employees
WHERE id = 17;

-- Ver salarios por tipo de planilla
SELECT eps.id, eps.tipo_planilla_id, eps.sueldo_base, eps.gastos_representacion,
       tp.descripcion as tipo_planilla_nombre
FROM employee_payroll_salaries eps
LEFT JOIN tipos_planilla tp ON eps.tipo_planilla_id = tp.id
WHERE eps.employee_id = 17
ORDER BY eps.tipo_planilla_id;
```

## Desactivar Logs en Producción (Opcional)

Una vez resuelto el problema, puedes comentar las líneas de `error_log()` para mejorar el rendimiento:

```php
// error_log("======== EMPLOYEE UPDATE DEBUG ========");
```

O mejor aún, usar una variable de entorno:

```php
if ($_ENV['APP_DEBUG'] === 'true') {
    error_log("======== EMPLOYEE UPDATE DEBUG ========");
}
```

## Contacto de Soporte

Si después de revisar los logs el problema persiste, enviar:

1. ✅ Fragmento completo de los logs (desde `======== EMPLOYEE UPDATE DEBUG` hasta `======== END`)
2. ✅ Captura de pantalla del formulario con los valores ingresados
3. ✅ Resultado de las consultas SQL de verificación
4. ✅ Versión de PHP y MySQL (`SELECT VERSION();`)

---

**Fecha de creación**: 14 de Octubre, 2025
**Versión del sistema**: 3.4.1
**Archivos modificados**:
- `app/Controllers/Employee.php`
- `app/Models/EmployeePayrollSalary.php`
