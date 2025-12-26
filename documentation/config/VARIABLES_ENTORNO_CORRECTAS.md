# 📝 Variables de Entorno - Nombres Correctos

## ✅ **Variables Detectadas en tu `.env`:**

```env
# Configuración de Base de Datos (Master)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=planilla_prod          # ← Usa DB_DATABASE (no DB_NAME)
DB_USERNAME=root                   # ← Usa DB_USERNAME (no DB_USER)
DB_PASSWORD=

# Configuración de Base de Datos (Tenants/Empresas)
TENANT_DB_HOST=localhost
TENANT_DB_PORT=3306
TENANT_DB_USER=root
TENANT_DB_PASS=
TENANT_DB_CHARSET=utf8mb4
```

---

## 🔄 **Mapeo de Variables:**

| **Tu .env** | **Código esperaba** | **Estado** |
|-------------|---------------------|------------|
| `DB_DATABASE` | `DB_NAME` | ✅ Ahora soporta ambos |
| `DB_USERNAME` | `DB_USER` | ✅ Ahora soporta ambos |
| `DB_PASSWORD` | `DB_PASSWORD` | ✅ Coincide |
| `DB_HOST` | `DB_HOST` | ✅ Coincide |
| `DB_PORT` | `DB_PORT` | ✅ Coincide |

---

## ✅ **Fixes Aplicados:**

### **1. `scripts/cron/sync_attendance.php` (líneas 37-68)**

**Cambios**:
- ✅ Usa `DB_DATABASE` en lugar de `DB_NAME`
- ✅ Usa `DB_USERNAME` en lugar de `DB_USER`
- ✅ Crea alias automáticos para compatibilidad hacia atrás

**Código**:
```php
// Fallback con nombres correctos
$requiredEnvVars = [
    'DB_HOST' => 'localhost',
    'DB_DATABASE' => 'planilla_prod',  // ← Tu .env usa DB_DATABASE
    'DB_USERNAME' => 'root',            // ← Tu .env usa DB_USERNAME
    'DB_PASSWORD' => '',
    'DB_PORT' => '3306'
];

// Crear alias para compatibilidad con código antiguo
if (!isset($_ENV['DB_NAME']) && isset($_ENV['DB_DATABASE'])) {
    $_ENV['DB_NAME'] = $_ENV['DB_DATABASE'];
}
if (!isset($_ENV['DB_USER']) && isset($_ENV['DB_USERNAME'])) {
    $_ENV['DB_USER'] = $_ENV['DB_USERNAME'];
}
```

---

### **2. `scripts/cron/end_of_day_processing.php` (líneas 37-66)**

**Cambios**: Idénticos a `sync_attendance.php`

---

### **3. `app/Core/Database.php` (líneas 25-42)**

**Cambios**:
- ✅ Soporta tanto `DB_DATABASE` como `DB_NAME`
- ✅ Soporta tanto `DB_USERNAME` como `DB_USER`
- ✅ Orden de prioridad: Primero busca el nombre real, luego el alias

**Código**:
```php
// Soporta ambos formatos
$database = $_ENV['DB_DATABASE'] ?? $_ENV['DB_NAME'] ?? 'planilla_prod';
$username = $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'root';

$config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? 3306,
    'database' => $database,
    'username' => $username,
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4'
];
```

---

### **4. `scripts/cron/test_cron_setup.php` (líneas 110-134)**

**Cambios**:
- ✅ Verifica ambos nombres de variables
- ✅ Solo marca error si NINGUNA de las dos existe

**Código**:
```php
$envVars = [
    ['DB_HOST', 'DB_HOST'],
    ['DB_DATABASE', 'DB_NAME'],      // Soporta ambos
    ['DB_USERNAME', 'DB_USER'],      // Soporta ambos
    ['DB_PASSWORD', 'DB_PASSWORD'],
    ['DB_PORT', 'DB_PORT']
];

foreach ($envVars as $varPair) {
    $primaryVar = $varPair[0];
    $alternateVar = $varPair[1];

    $value = $_ENV[$primaryVar] ?? $_ENV[$alternateVar] ??
             getenv($primaryVar) ?? getenv($alternateVar);
}
```

---

## 🎯 **Resultado Esperado:**

Después de estos fixes, el sistema funciona correctamente con tu `.env` actual:

### **Test debe pasar:**
```bash
php scripts/cron/test_cron_setup.php
```

**Output esperado**:
```
TEST 4: Cargando variables de entorno...
  ✓ Dotenv cargado
  ✓ DB_HOST = localhost
  ✓ DB_DATABASE = planilla_prod     # ← Detecta tu variable
  ✓ DB_USERNAME = root               # ← Detecta tu variable
  ✓ DB_PASSWORD =
  ✓ DB_PORT = 3306
```

---

### **Sincronización debe funcionar:**
```bash
php scripts/cron/sync_attendance.php
```

**Output esperado** (sin errores de variables):
```
╔════════════════════════════════════════════════════════════╗
║   CRON JOB: Sincronización de Asistencias desde API       ║
╚════════════════════════════════════════════════════════════╝

▶️  Tenant: planilla_prod
✓ Configuración encontrada
🚀 Iniciando sincronización...
```

---

## 📋 **Compatibilidad Garantizada:**

El sistema ahora soporta **AMBOS** formatos de variables:

### **Formato Laravel (tu actual)**:
```env
DB_DATABASE=planilla_prod
DB_USERNAME=root
```

### **Formato Tradicional (código antiguo)**:
```env
DB_NAME=planilla_prod
DB_USER=root
```

**¿Qué significa esto?**
- ✅ No necesitas cambiar tu `.env`
- ✅ El código funciona con ambos formatos
- ✅ Si existe `DB_DATABASE`, se usa; si no, busca `DB_NAME`
- ✅ Si existe `DB_USERNAME`, se usa; si no, busca `DB_USER`

---

## 🔍 **Verificar en Producción:**

### **1. Verificar que tu `.env` tiene las variables correctas:**

```bash
ssh root@servidor
cd /var/www/html/planilla
cat .env | grep "^DB_"
```

**Resultado esperado**:
```
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=planilla_prod
DB_USERNAME=root
DB_PASSWORD=
```

---

### **2. Probar carga de variables:**

```bash
php -r '
require "vendor/autoload.php";
\App\Core\Config::load();
echo "DB_DATABASE: " . ($_ENV["DB_DATABASE"] ?? "NO DEFINIDA") . "\n";
echo "DB_USERNAME: " . ($_ENV["DB_USERNAME"] ?? "NO DEFINIDA") . "\n";
echo "Alias DB_NAME: " . ($_ENV["DB_NAME"] ?? "NO DEFINIDA") . "\n";
echo "Alias DB_USER: " . ($_ENV["DB_USER"] ?? "NO DEFINIDA") . "\n";
'
```

**Resultado esperado**:
```
DB_DATABASE: planilla_prod
DB_USERNAME: root
Alias DB_NAME: planilla_prod    # ← Creado automáticamente
Alias DB_USER: root              # ← Creado automáticamente
```

---

### **3. Ejecutar test completo:**

```bash
php scripts/cron/test_cron_setup.php | grep "DB_"
```

**Resultado esperado**:
```
  ✓ DB_HOST = localhost
  ✓ DB_DATABASE = planilla_prod
  ✓ DB_USERNAME = root
  ✓ DB_PASSWORD =
  ✓ DB_PORT = 3306
```

---

## ⚠️ **Importante:**

### **NO necesitas cambiar tu `.env`**

Tu archivo `.env` actual es **CORRECTO**. Los fixes aplicados hacen que el código se adapte a tu formato de variables, no al revés.

### **¿Por qué el código esperaba DB_NAME y DB_USER?**

Probablemente porque:
1. Código fue copiado de otro proyecto que usaba formato tradicional
2. Documentación de Laravel usa `DB_DATABASE` y `DB_USERNAME`
3. Había inconsistencia entre diferentes partes del código

**Solución**: Ahora el código es **agnóstico** al formato y acepta ambos.

---

## 📊 **Resumen de Cambios:**

| **Archivo** | **Líneas** | **Cambio** |
|-------------|------------|------------|
| `sync_attendance.php` | 37-68 | Usa `DB_DATABASE` + crea alias `DB_NAME` |
| `end_of_day_processing.php` | 37-66 | Usa `DB_DATABASE` + crea alias `DB_NAME` |
| `Database.php` | 25-42 | Soporta ambos formatos con fallback |
| `test_cron_setup.php` | 110-134 | Verifica ambos nombres de variables |

**Total de líneas modificadas**: ~60 líneas
**Archivos modificados**: 4 archivos
**Tiempo de deployment**: 5 minutos

---

**Versión**: 3.5.14
**Fecha**: 26 de Diciembre, 2025
**Autor**: Sistema Planillas Innova
