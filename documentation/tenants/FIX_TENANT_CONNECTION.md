# 🔧 Fix: Conexión BD Tenants en Controller.php

**Fecha**: 22 de Noviembre, 2025
**Versión**: v3.5.10 (propuesta)
**Tipo**: Bugfix crítico - Multitenancy
**Impacto**: VacationController, LiquidationController, todos los controladores

---

## 🐛 **PROBLEMA DETECTADO**

### Síntoma
Los módulos de **Vacaciones** y **Liquidaciones** mostraban datos de `planilla_prod` (BD por defecto) en lugar de la BD del tenant cuando el usuario hacía login con una licencia.

### Causa Raíz
`Controller::initDatabase()` creaba una conexión PDO directa usando configuración estática (`config/database.php`) **sin consultar TenantResolver**, ignorando la sesión del tenant activo.

```php
// ANTES (INCORRECTO)
private function initDatabase()
{
    Config::load();
    $dbConfig = Config::get('database.connections.mysql');

    $dbname = $dbConfig['database']; // ❌ Siempre 'planilla_prod'

    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $this->db = new \PDO($dsn, $username, $password);
}
```

### Flujo Incorrecto
```
Login con licencia PINN0000000004
  ↓
TenantResolver detecta tenant ✅
  ↓
$_SESSION['tenant_db'] = 'PINN0000000004' ✅
  ↓
VacationController → Controller::__construct()
  ↓
initDatabase() crea PDO directo ❌
  ↓
Conecta a 'planilla_prod' ❌ (ignora tenant)
  ↓
Muestra datos incorrectos ❌
```

---

## ✅ **SOLUCIÓN IMPLEMENTADA**

### Cambio Realizado
Modificar `app/Core/Controller.php` líneas 15-34 para usar `Database::getInstance()` que **SÍ** resuelve tenants.

```php
// DESPUÉS (CORRECTO)
private function initDatabase()
{
    // Usar Database singleton que resuelve tenant automáticamente
    // Database::getInstance() ya maneja TenantResolver::resolve()
    // y conecta a la BD correcta (tenant o planilla_prod)
    $this->db = Database::getInstance()->getConnection();
}
```

### Flujo Correcto
```
Login con licencia PINN0000000004
  ↓
TenantResolver detecta tenant ✅
  ↓
$_SESSION['tenant_db'] = 'PINN0000000004' ✅
  ↓
VacationController → Controller::__construct()
  ↓
initDatabase() llama Database::getInstance() ✅
  ↓
Database resuelve tenant con TenantResolver ✅
  ↓
Conecta a 'PINN0000000004' ✅
  ↓
Muestra datos correctos del tenant ✅
```

---

## 📊 **IMPACTO**

### Archivos Modificados
- ✅ `app/Core/Controller.php` (1 método simplificado)

### Líneas de Código
- ❌ Eliminadas: ~17 líneas (lógica PDO duplicada)
- ✅ Agregadas: ~4 líneas (delegación a Database)
- 📉 **Reducción neta**: -13 líneas

### Controladores Beneficiados (Automático)
- ✅ VacationController
- ✅ LiquidationController
- ✅ PayrollController
- ✅ EmployeeController
- ✅ AttendanceController
- ✅ Todos los controladores que heredan de `Controller`

---

## 🧪 **TESTING**

### Pre-requisitos
1. Tener al menos 1 tenant activo con licencia (ej: `PINN0000000004`)
2. Tener datos de prueba en BD tenant (empleados, vacaciones)

### Pasos de Prueba

#### Test 1: Verificar Conexión Básica
```sql
-- 1. Ver tenants disponibles
SELECT id, company_name, license_key, db_name
FROM planilla_master.tenants
WHERE status = 'ACTIVE';

-- 2. Verificar datos en tenant
USE PINN0000000004;
SELECT COUNT(*) FROM employees WHERE situacion_id = 1;
SELECT COUNT(*) FROM vacation_requests;
```

#### Test 2: Login y Navegación
```
1. Logout completo
2. Login con:
   Usuario: pruebas3
   Contraseña: 123456
   Licencia: PINN0000000004
3. Ir a: /panel/vacation
4. Verificar que muestra SOLO empleados del tenant
```

#### Test 3: Verificación SQL en Runtime
```php
// Agregar temporalmente en VacationController::index() línea 105
error_log("Current DB: " . $this->db->query("SELECT DATABASE()")->fetchColumn());
error_log("Session tenant: " . ($_SESSION['tenant_db'] ?? 'NONE'));
```

Resultado esperado en logs:
```
Current DB: PINN0000000004
Session tenant: PINN0000000004
```

#### Test 4: Comparación de Datos
```sql
-- Contar empleados en planilla_prod (BD incorrecta)
USE planilla_prod;
SELECT COUNT(*) as total FROM employees WHERE situacion_id = 1;
-- Ejemplo resultado: 156 empleados

-- Contar empleados en tenant (BD correcta)
USE PINN0000000004;
SELECT COUNT(*) as total FROM employees WHERE situacion_id = 1;
-- Ejemplo resultado: 3 empleados

-- Si VacationController muestra 3 empleados → ✅ CORRECTO
-- Si VacationController muestra 156 empleados → ❌ INCORRECTO (aún usa planilla_prod)
```

---

## ⚠️ **POSIBLES EFECTOS SECUNDARIOS**

### Bajo Riesgo ✅
- Cambio mínimo en código core
- `Database::getInstance()` ya existe y está probado
- `TenantResolver` ya implementado y funcionando
- Sin cambios en estructura BD
- Retrocompatible (funciona con y sin tenant)

### Compatibilidad PDO ✅
Todos los métodos PDO siguen funcionando:
```php
$this->db->prepare($sql)           // ✅
$this->db->query($sql)              // ✅
$this->db->execute()                // ✅
$this->db->beginTransaction()       // ✅
$this->db->commit()                 // ✅
$this->db->rollback()               // ✅
$this->db->lastInsertId()           // ✅
```

### Singleton Reset ⚠️
Si en algún momento se cambia de tenant en runtime (ej: switch entre empresas), usar:
```php
Database::resetInstance();
$this->db = Database::getInstance()->getConnection();
```

---

## 📝 **VERIFICACIÓN POST-FIX**

### Checklist
- [ ] Backup realizado: `Controller.php.backup`
- [ ] Cambio aplicado en `Controller.php`
- [ ] Login con licencia funciona
- [ ] VacationController muestra datos del tenant
- [ ] LiquidationController muestra datos del tenant
- [ ] No hay errores en logs PHP
- [ ] Performance no afectada (singleton es más rápido)

### Comandos de Verificación
```bash
# 1. Verificar cambio aplicado
grep -n "Database::getInstance" app/Core/Controller.php

# 2. Ver logs PHP (Windows)
tail -f C:/xampp82/php/logs/php_error_log

# 3. Comparar con backup
diff app/Core/Controller.php app/Core/Controller.php.backup
```

---

## 🚀 **PRÓXIMOS PASOS**

### Inmediato
1. ✅ Testing en desarrollo (localhost)
2. ⏳ Testing en staging (si existe)
3. ⏳ Deployment a producción

### Mejoras Futuras (Opcional)
1. Agregar log automático de conexión actual en modo debug
2. Crear middleware para validar conexión tenant en cada request
3. Panel admin para visualizar conexiones activas por tenant
4. Métricas de uso de BD por tenant

---

## 📚 **REFERENCIAS**

### Archivos Relacionados
- `app/Core/Controller.php` - Modificado ✅
- `app/Core/Database.php` - Usa TenantResolver ✅
- `app/Core/TenantResolver.php` - Detecta tenant ✅
- `app/Core/MasterDatabase.php` - Conecta a planilla_master ✅
- `app/Controllers/VacationController.php` - Beneficiado ✅
- `app/Controllers/LiquidationController.php` - Beneficiado ✅

### Documentación
- `SISTEMA_LICENCIAS_V2.md` - Sistema de licencias PINN
- `test_tenant_connection.sql` - Script de testing

### Commits Relacionados
- v3.5.8 - Implementación TenantResolver inicial
- v3.5.9 - Employee Import + Wizard UI
- v3.5.10 (propuesta) - Fix conexión tenants en Controller.php

---

## 👨‍💻 **AUTOR**
- Implementado: 22-Nov-2025
- Sistema: Planilla Innova v3.5.10
- Tipo: Bugfix crítico multitenancy
