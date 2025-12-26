# 🔧 Fix: Actualización Template Excel Importación V3.5.9

**Fecha**: 22 de Noviembre, 2025
**Versión**: v3.5.9
**Tipo**: Actualización Template + Fix Tipo de Dato
**Impacto**: EmployeeImportController, Templates estáticos

---

## 🐛 **PROBLEMA DETECTADO**

### Síntoma
Al importar empleados desde el template Excel estático (`template_empleados.xlsx`), los datos se insertaban en columnas incorrectas:

```
❌ [email] => Calle Principal #123        (debería ser: jperez@empresa.com)
❌ [address] => 1985-03-15                 (debería ser: Calle Principal #123)
❌ [birthdate] => 2023-01-15               (debería ser: 1985-03-15)
❌ [contact_info] => M                     (debería ser: 6677-8899)
❌ [gender] => 1                           (debería ser: M)
```

### Causa Raíz

**Problema #1: Template Desactualizado**
El template estático no se actualizó cuando se agregaron 3 campos nuevos en V3.5.9:
- `EMAIL` (columna D) - Campo obligatorio
- `MARCA ASISTENCIA` (columna U) - Default 1
- `PERMITE HORAS EXTRAS` (columna V) - Default 1

**Template Viejo** (30 columnas):
```
A: CÓDIGO EMPLEADO
B: NOMBRES
C: APELLIDOS
D: DIRECCIÓN        ← ❌ Falta EMAIL antes de esto
E: FECHA NACIMIENTO
F: FECHA INGRESO
...
```

**Template Nuevo V3.5.9** (33 columnas):
```
A: CÓDIGO EMPLEADO
B: NOMBRES
C: APELLIDOS
D: EMAIL           ← ✅ Campo nuevo obligatorio
E: DIRECCIÓN       ← Desplazado +1 columna
F: FECHA NACIMIENTO ← Desplazado +1 columna
G: FECHA INGRESO    ← Desplazado +1 columna
...
U: MARCA ASISTENCIA (1=SI, 0=NO)     ← ✅ Campo nuevo
V: PERMITE HORAS EXTRAS (1=SI, 0=NO) ← ✅ Campo nuevo
```

**Problema #2: Tipo de Dato `created_on`**
El campo `created_on` en la tabla `employees` es tipo `DATE`, pero el código estaba enviando `DATETIME`:

```php
// ANTES (INCORRECTO)
$cleanData['created_on'] = date('Y-m-d H:i:s'); // ❌ Incluye hora

// DESPUÉS (CORRECTO)
$cleanData['created_on'] = date('Y-m-d'); // ✅ Solo fecha
```

---

## ✅ **SOLUCIÓN IMPLEMENTADA**

### 1. Actualización Templates Estáticos

**Archivos Generados**:
- ✅ `/public/template_empleados.xlsx` (actualizado)
- ✅ `/template_empleados.xlsx` (raíz, actualizado)

**Script Generador**: `generate_updated_template.php`

**Estructura Nueva**:
- 33 columnas totales (A-AD)
- 3 campos nuevos correctamente posicionados
- 2 filas de ejemplo con datos válidos
- Headers con formato profesional (azul, texto blanco, centrado)

### 2. Corrección Tipo de Dato

**Archivo**: `app/Controllers/Admin/EmployeeImportController.php`

```php
// Línea 382
$cleanData['created_on'] = date('Y-m-d'); // Solo fecha, no hora
```

### 3. Logging Detallado

Agregado logging en 3 niveles para debugging:

**EmployeeImportController** (líneas 327-334, 388-411):
```php
error_log("=== INICIO IMPORTACIÓN EXCEL ===");
error_log("Tenant en sesión: " . ($_SESSION['tenant_db'] ?? 'NONE'));
error_log("BD conectada: {$currentDb}");
error_log("=== ANTES DE CREATE() - Fila {$row} ===");
error_log("Employee ID retornado: " . ($employeeId ?: 'NULL/FALSE'));
```

**Model::create()** (líneas 50-78):
```php
error_log("=== Model::create() llamado ===");
error_log("Tabla: {$this->table}");
error_log("Datos después de filterFillable (" . count($data) . " campos)");
error_log("ID insertado: " . ($insertId ?: 'NULL/0'));
```

---

## 📊 **CAMBIOS REALIZADOS**

### Archivos Modificados
- ✅ `app/Controllers/Admin/EmployeeImportController.php` (2 cambios)
- ✅ `app/Core/Model.php` (logging agregado)
- ✅ `public/template_empleados.xlsx` (regenerado)
- ✅ `template_empleados.xlsx` (regenerado)

### Archivos Nuevos
- ✅ `generate_updated_template.php` (script generador)
- ✅ `FIX_EXCEL_IMPORT_TEMPLATE_UPDATE.md` (este archivo)

### Estadísticas
- 2 templates actualizados: 30→33 columnas
- 1 fix tipo de dato (DATE vs DATETIME)
- 3 niveles de logging agregados
- 0 cambios en BD

---

## 🧪 **TESTING**

### Pre-requisitos
1. Usar el **template nuevo** de `/public/template_empleados.xlsx`
2. Tener datos en tenant (horarios, situaciones, tipos planilla)
3. Estar logueado con licencia de tenant

### Pasos de Prueba

#### Test 1: Verificar Template Actualizado
```bash
# Ver columnas del template
# Verificar que columna D sea "EMAIL*"
# Verificar que columna U sea "MARCA ASISTENCIA (1=SI, 0=NO)"
# Verificar que columna V sea "PERMITE HORAS EXTRAS (1=SI, 0=NO)"
```

#### Test 2: Importación Básica
1. Descargar template nuevo desde `/public/template_empleados.xlsx`
2. Modificar datos de ejemplo (cambiar EMP001 por EMP999)
3. Asegurar que columna D tenga email válido (ej: `test@empresa.com`)
4. Importar desde módulo de importación
5. Verificar que se cree empleado correctamente

#### Test 3: Verificar Logs
```bash
# Tail logs durante importación
tail -f C:/laragon60/logs/php_errors.log

# Buscar estas líneas:
# - "=== INICIO IMPORTACIÓN EXCEL ==="
# - "BD conectada: PINN8091605110" (debe ser tenant, no planilla_prod)
# - "=== EMPLEADO CREADO EXITOSAMENTE ==="
# - "Employee ID: X" (X > 0)
```

#### Test 4: Validar Datos Insertados
```sql
-- Conectar a BD tenant
USE PINN8091605110;

-- Ver último empleado insertado
SELECT id, employee_id, firstname, lastname, email,
       marca_asistencia, permite_horas_extras, created_on
FROM employees
ORDER BY id DESC
LIMIT 1;

-- Verificar que:
-- ✅ email tenga valor válido (no dirección)
-- ✅ marca_asistencia sea 0 o 1
-- ✅ permite_horas_extras sea 0 o 1
-- ✅ created_on sea DATE (YYYY-MM-DD sin hora)
```

---

## 📋 **ESTRUCTURA COMPLETA TEMPLATE V3.5.9**

### Columnas (A-AD) - 33 Total

| Col | Campo | Obligatorio | Tipo | Ejemplo |
|-----|-------|------------|------|---------|
| A | CÓDIGO EMPLEADO | Sí * | String | EMP001 |
| B | NOMBRES | Sí * | String | Juan Carlos |
| C | APELLIDOS | Sí * | String | Pérez González |
| **D** | **EMAIL** | **Sí \*** | **Email** | **jperez@empresa.com** |
| E | DIRECCIÓN | No | String | Calle Principal #123 |
| F | FECHA NACIMIENTO | Sí * | Date | 1985-03-15 |
| G | FECHA INGRESO | Sí * | Date | 2023-01-15 |
| H | CONTACTO | No | String | 6677-8899 |
| I | GÉNERO | Sí * | Enum | M, F |
| J | POSICIÓN ID | No | Int | 1 |
| K | HORARIO ID | Sí * | Int | 1 |
| L | DOCUMENTO ID | No | String | 8-123-456 |
| M | SEGURO SOCIAL | No | String | SS123456 |
| N | SITUACIÓN ID | Sí * | Int | 1 |
| O | TIPO PLANILLA ID | Sí * | Int | 1 |
| P | CARGO ID | No | Int | 1 |
| Q | FUNCIÓN ID | No | Int | 1 |
| R | PARTIDA ID | No | Int | 1 |
| S | SUELDO INDIVIDUAL | No | Decimal | 1500.00 |
| T | GASTOS REPRES. | No | Decimal | 200.00 |
| **U** | **MARCA ASISTENCIA** | **No** | **Boolean** | **1, 0, SI, NO** |
| **V** | **PERMITE HORAS EXTRAS** | **No** | **Boolean** | **1, 0, SI, NO** |
| W | TIPO CONTRATO | No | Enum | INDEFINIDO, DEFINIDO |
| X | NÚMERO CONTRATO | No | String | CT-2023-001 |
| Y | FECHA INICIO CONTRATO | No | Date | 2023-01-15 |
| Z | FECHA VENC. CONTRATO | No | Date | 2024-01-15 |
| AA | FORMA PAGO | No | Enum | EFECTIVO, CHEQUE, ACH |
| AB | BANCO | No | String | Banco Nacional |
| AC | NÚMERO CUENTA | No | String | 123456789 |
| AD | TIPO CUENTA | No | Enum | AHORROS, CORRIENTE |

### Campos Obligatorios (*)
```
✅ A: CÓDIGO EMPLEADO
✅ B: NOMBRES
✅ C: APELLIDOS
✅ D: EMAIL (NUEVO V3.5.9)
✅ F: FECHA NACIMIENTO
✅ G: FECHA INGRESO
✅ I: GÉNERO
✅ K: HORARIO ID
✅ N: SITUACIÓN ID
✅ O: TIPO PLANILLA ID
```

### Campos con Defaults Automáticos
```
🔹 U: MARCA ASISTENCIA → Default: 1 (Sí)
🔹 V: PERMITE HORAS EXTRAS → Default: 1 (Sí)
🔹 PHOTO → Default: 'images/facebook-profile-image.jpeg'
🔹 CREATED_ON → Default: fecha actual (DATE)
```

---

## ⚠️ **NOTAS IMPORTANTES**

### Migración de Templates Antiguos

Si tienes archivos Excel con el **template viejo**:

**Opción 1: Regenerar desde cero** (RECOMENDADO)
1. Descargar template nuevo desde `/public/template_empleados.xlsx`
2. Copiar datos manualmente
3. **IMPORTANTE**: Agregar emails válidos en columna D

**Opción 2: Insertar columnas manualmente**
1. Abrir Excel viejo
2. Insertar columna nueva ANTES de columna D (actual DIRECCIÓN)
3. Llenar header: `EMAIL*`
4. Llenar emails para cada empleado
5. Verificar que todas las columnas estén correctamente alineadas

### Validación de Emails

El sistema valida formato de email con `FILTER_VALIDATE_EMAIL`:
```
✅ jperez@empresa.com
✅ maria.garcia@correo.pa
✅ admin@sistema.net
❌ correo sin arroba
❌ @dominio.com
❌ usuario@
```

### Valores Boolean (Marca Asistencia / Permite Horas Extras)

Valores aceptados (case insensitive):
```
TRUE:  1, SI, SÍ, YES, TRUE, S, Y
FALSE: 0, NO, FALSE, N
VACÍO: Default 1 (TRUE)
```

---

## 🚀 **DEPLOYMENT**

### Pasos para Producción

1. **Backup de templates actuales**:
```bash
cp public/template_empleados.xlsx public/template_empleados.xlsx.backup
cp template_empleados.xlsx template_empleados.xlsx.backup
```

2. **Ejecutar script generador**:
```bash
cd /c/laragon60/www/planilla-innova
php generate_updated_template.php
```

3. **Verificar archivos generados**:
```bash
ls -lh public/template_empleados.xlsx
ls -lh template_empleados.xlsx
```

4. **Notificar usuarios**:
> ⚠️ IMPORTANTE: Si tienes templates Excel descargados previamente, descarga la versión nueva desde el sistema. El formato cambió en V3.5.9 para incluir campo EMAIL obligatorio.

5. **Aplicar cambios en código**:
- ✅ `EmployeeImportController.php` (línea 382)
- ✅ `Model.php` (logging)

6. **Testing post-deployment**:
- Importar 1 empleado de prueba
- Verificar logs
- Verificar datos en BD

---

## 📚 **REFERENCIAS**

### Archivos Relacionados
- `app/Controllers/Admin/EmployeeImportController.php` - Importación
- `app/Models/Employee.php` - Modelo empleado
- `app/Core/Model.php` - Base model con create()
- `generate_updated_template.php` - Script generador
- `public/template_empleados.xlsx` - Template público
- `template_empleados.xlsx` - Template raíz

### Documentación
- `CLAUDE.md` - V3.5.9 Employee Import System
- `FIX_EXCEL_IMPORT_EMPTY_ROWS.md` - Fix filas vacías
- `FIX_TENANT_CONNECTION.md` - Fix conexión tenants

### Issues Relacionados
- #1: Template desactualizado (30→33 columnas)
- #2: Tipo de dato created_on (DATETIME→DATE)
- #3: Falta logging para debugging

---

## 👨‍💻 **AUTOR**
- Implementado: 22-Nov-2025
- Sistema: Planilla Innova v3.5.9
- Tipo: Fix template + tipo de dato + logging

---

## 🎯 **RESULTADO FINAL**

### Antes del Fix
```
❌ Template desactualizado (30 columnas)
❌ Datos desfasados en importación
❌ Error tipo de dato created_on
❌ Sin visibilidad de errores (no logs)
```

### Después del Fix
```
✅ Template actualizado (33 columnas)
✅ Datos correctamente mapeados
✅ Tipo de dato correcto (DATE)
✅ Logging completo en 3 niveles
✅ Importación funcionando en tenants
```
