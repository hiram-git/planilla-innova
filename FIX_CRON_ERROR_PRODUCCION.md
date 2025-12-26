# 🔧 Fix: Error "Failed to open config/database.php" en Cron Jobs

## 🚨 Error Detectado

```
PHP Warning:  require(./config/database.php): Failed to open stream: No such file or directory
in /var/www/html/planilla/app/Core/Database.php on line 23
```

## 🔍 Causa del Error

El archivo `Database.php` usaba una **ruta relativa** (`./config/database.php`) que funcionaba cuando se ejecutaba desde el navegador, pero **fallaba en cron jobs** porque el directorio de trabajo actual (`getcwd()`) es diferente cuando se ejecuta desde cron.

## ✅ Solución Implementada

### **1. Modificado `app/Core/Database.php` (líneas 23-39)**

**ANTES**:
```php
// Fallback a configuración por defecto
$app_config = require './config/database.php';  // ❌ Ruta relativa
$config = $app_config['connections']['mysql'] ?? [];
```

**AHORA**:
```php
// Fallback a configuración por defecto
// Usar ruta absoluta para compatibilidad con cron jobs
$configPath = __DIR__ . '/../../config/database.php';  // ✅ Ruta absoluta
if (!file_exists($configPath)) {
    // Fallback adicional: usar variables de entorno directamente
    $config = [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'database' => $_ENV['DB_NAME'] ?? 'planilla_prod',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4'
    ];
} else {
    $app_config = require $configPath;
    $config = $app_config['connections']['mysql'] ?? [];
}
```

**Ventajas**:
- ✅ Funciona tanto desde navegador como desde cron
- ✅ Fallback a variables de entorno si no encuentra `config/database.php`
- ✅ Compatible con cualquier directorio de ejecución

---

## 📋 Pasos para Aplicar el Fix en Producción

### **Paso 1: Subir archivo modificado**

```bash
# Desde tu máquina local
scp app/Core/Database.php root@servidor:/var/www/html/planilla/app/Core/Database.php

# O usando rsync (recomendado)
rsync -avz --progress app/Core/Database.php root@servidor:/var/www/html/planilla/app/Core/Database.php
```

### **Paso 2: Subir script de prueba**

```bash
scp scripts/cron/test_cron_setup.php root@servidor:/var/www/html/planilla/scripts/cron/test_cron_setup.php
```

### **Paso 3: Dar permisos de ejecución**

```bash
ssh root@servidor
cd /var/www/html/planilla
chmod +x scripts/cron/test_cron_setup.php
```

### **Paso 4: Ejecutar script de prueba**

```bash
php scripts/cron/test_cron_setup.php
```

**Resultado esperado**:
```
╔════════════════════════════════════════════════════════════════╗
║                      RESUMEN DE PRUEBAS                        ║
╠════════════════════════════════════════════════════════════════╣
║  ✅ Éxitos:      25                                            ║
║  ⚠️  Advertencias: 0                                           ║
║  ❌ Errores:     0                                             ║
╚════════════════════════════════════════════════════════════════╝

✅ TODAS LAS PRUEBAS PASARON EXITOSAMENTE
✅ El sistema está listo para ejecutar cron jobs
```

### **Paso 5: Probar script de sincronización manualmente**

```bash
# Ejecutar sincronización manualmente
php /var/www/html/planilla/scripts/cron/sync_attendance.php

# Ver resultado
echo $?  # Debe mostrar 0 (éxito)
```

**Resultado esperado**:
```
╔════════════════════════════════════════════════════════════╗
║   CRON JOB: Sincronización de Asistencias desde API       ║
║   Fecha: 2025-12-26 10:30:15                               ║
╚════════════════════════════════════════════════════════════╝

══════════════════════════════════════════════════════
▶️  Tenant: planilla_prod
══════════════════════════════════════════════════════
✓ Configuración encontrada:
  - Proveedor: base44
  - Sincronización habilitada: Sí
  - Intervalo: 15 minutos

🚀 Iniciando sincronización...

📅 Rango sincronizado: 2025-12-26 (día completo)
✅ Sincronización completada para planilla_prod:
  - Registros obtenidos: 45
  - Registros insertados: 12
  - Registros actualizados: 33
  - Registros omitidos: 0
  - Errores: 0

⏱️  Tiempo de ejecución total: 2.35 segundos (1 tenants)

✓ Finalizado con código: 0
```

### **Paso 6: Verificar que cron ejecute correctamente**

```bash
# Esperar 15 minutos y revisar log
tail -f /var/log/planilla/cron_sync.log

# O forzar ejecución inmediata editando crontab temporalmente
crontab -e
# Cambiar temporalmente a: * * * * * (cada minuto)
# Esperar 1 minuto
# Volver a cambiar a: */15 6-20 * * *
```

---

## 🔍 Troubleshooting Adicional

### **Error: "Class 'Dotenv\Dotenv' not found"**

**Solución**:
```bash
cd /var/www/html/planilla
composer install --no-dev --optimize-autoloader
```

---

### **Error: "No such file or directory: /var/log/planilla"**

**Solución**:
```bash
sudo mkdir -p /var/log/planilla
sudo chown www-data:www-data /var/log/planilla
sudo chmod 755 /var/log/planilla
```

---

### **Error: "Permission denied" al ejecutar scripts**

**Solución**:
```bash
cd /var/www/html/planilla/scripts/cron
chmod +x *.php
```

---

### **Cron no se ejecuta automáticamente**

**Verificar**:
```bash
# Ver crontab actual
crontab -l

# Ver logs del sistema cron
sudo grep CRON /var/log/syslog | tail -n 20

# Verificar servicio cron
sudo systemctl status cron
```

**Solución**:
```bash
# Reiniciar servicio cron
sudo systemctl restart cron

# Ver errores específicos
sudo journalctl -u cron -n 50
```

---

## 📊 Verificación Post-Implementación

### **1. Verificar que el archivo Database.php se actualizó**

```bash
ssh root@servidor
cd /var/www/html/planilla
grep -n "Usar ruta absoluta" app/Core/Database.php
```

**Resultado esperado**:
```
23:            // Usar ruta absoluta para compatibilidad con cron jobs
```

### **2. Verificar logs de sincronización**

```bash
# Ver últimas 20 líneas
tail -n 20 /var/log/planilla/cron_sync.log

# Contar errores
grep -c "ERROR\|Fatal" /var/log/planilla/cron_sync.log
```

**Resultado esperado**: 0 errores

### **3. Verificar registros en BD**

```sql
-- Conectar a BD
mysql -u root -p planilla_prod

-- Ver últimos registros sincronizados
SELECT COUNT(*) as total, MAX(timestamp) as ultima_marcacion
FROM attendance_records
WHERE DATE(timestamp) = CURDATE();

-- Ver log de sincronización
SELECT id, sync_type, records_fetched, records_inserted, status, start_time
FROM attendance_sync_log
ORDER BY id DESC
LIMIT 5;
```

---

## ✅ Checklist de Validación

Ejecuta este checklist después de aplicar el fix:

- [ ] **1. Archivo Database.php actualizado en producción**
  ```bash
  grep "Usar ruta absoluta" /var/www/html/planilla/app/Core/Database.php
  ```

- [ ] **2. Script de prueba ejecuta sin errores**
  ```bash
  php /var/www/html/planilla/scripts/cron/test_cron_setup.php
  ```

- [ ] **3. Sincronización manual funciona**
  ```bash
  php /var/www/html/planilla/scripts/cron/sync_attendance.php
  ```

- [ ] **4. Directorio de logs existe y es escribible**
  ```bash
  ls -ld /var/log/planilla
  ```

- [ ] **5. Crontab configurado correctamente**
  ```bash
  crontab -l | grep sync_attendance
  ```

- [ ] **6. Logs de cron muestran ejecuciones exitosas**
  ```bash
  tail -n 50 /var/log/planilla/cron_sync.log
  ```

- [ ] **7. Registros de asistencia se sincronizan en BD**
  ```sql
  SELECT COUNT(*) FROM attendance_records WHERE DATE(created_at) = CURDATE();
  ```

- [ ] **8. No hay errores en logs del sistema**
  ```bash
  sudo grep "planilla\|attendance" /var/log/syslog | grep -i error
  ```

---

## 🚀 Resumen del Fix

| **Aspecto** | **Antes** | **Después** |
|-------------|-----------|-------------|
| **Ruta config** | `./config/database.php` (relativa) | `__DIR__ . '/../../config/database.php'` (absoluta) |
| **Funciona en navegador** | ✅ Sí | ✅ Sí |
| **Funciona en cron** | ❌ No | ✅ Sí |
| **Fallback** | ❌ No | ✅ Sí (usa `$_ENV`) |
| **Compatible multitenancy** | ✅ Sí | ✅ Sí |

---

## 📞 Comandos Útiles para Monitoreo

```bash
# Ver ejecuciones del cron en tiempo real
sudo tail -f /var/log/syslog | grep CRON

# Ver log de sincronización en tiempo real
tail -f /var/log/planilla/cron_sync.log

# Ejecutar sincronización manualmente y ver output
php /var/www/html/planilla/scripts/cron/sync_attendance.php 2>&1 | tee /tmp/sync_test.log

# Ver última ejecución del cron
sudo grep "sync_attendance" /var/log/syslog | tail -n 5

# Contar sincronizaciones exitosas del día
grep "Finalizado con código: 0" /var/log/planilla/cron_sync.log | grep "$(date +%Y-%m-%d)" | wc -l
```

---

**Fecha**: 26 de Diciembre, 2025
**Versión**: 3.5.14
**Autor**: Sistema Planillas Innova
