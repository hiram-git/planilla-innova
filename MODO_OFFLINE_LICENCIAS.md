# 🔄 Sistema de Licencias en Modo Offline

**Fecha de Implementación**: 24 de Noviembre, 2025
**Versión**: 1.0.0
**Estado**: ✅ Completado

## 📋 Descripción General

Sistema de contingencia que permite continuar operando cuando el servidor remoto de licencias no está disponible. Las licencias se generan localmente y se sincronizan automáticamente cuando el servidor vuelva a estar en línea.

## 🎯 Problema Resuelto

**Escenario**: El servidor de licencias de Innovasoft Latam no está disponible (mantenimiento, caída, problemas de red).

**Antes**: ❌ El wizard no podía crear empresas nuevas - error total.

**Ahora**: ✅ El wizard crea empresas con licencias locales y las sincroniza después.

## ⚙️ Configuración

### Variables de Entorno (.env)

```env
# Habilitar/deshabilitar modo offline (default: true)
LICENSING_ALLOW_OFFLINE=true

# Otras configuraciones relacionadas
LICENSE_VALIDATION_URL="https://plataforma.innovasoftlatam.com:8080/ajax/license.php"
LICENSE_PREFIX="PINN"
HTTP_TIMEOUT=8
```

**Opciones**:
- `LICENSING_ALLOW_OFFLINE=true` → Permite crear empresas aunque servidor esté caído (RECOMENDADO)
- `LICENSING_ALLOW_OFFLINE=false` → Bloquea creación si servidor no responde (modo estricto)

## 🔧 Componentes Implementados

### 1. **Migración de Base de Datos**

**Archivo**: `database/migrations/master/2025_11_24_add_license_sync_fields_to_tenants.sql`

**Nuevas columnas en tabla `tenants`**:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `license_sync_pending` | TINYINT(1) | 1 = pendiente de sincronización, 0 = sincronizado |
| `license_sync_error` | TEXT | Mensaje de error de última sincronización |
| `license_last_sync_attempt` | DATETIME | Fecha del último intento de sincronización |

**Índice**: `idx_license_sync_pending` para consultas rápidas de licencias pendientes.

### 2. **LicenseGenerator Mejorado**

**Archivo**: `app/Services/LicenseGenerator.php`

#### Nuevos Métodos:

**`generateAndRegister($companyData, $allowOffline = true)`**
- Intenta registrar en servidor remoto
- Si falla y `$allowOffline=true` → genera licencia local
- Si falla y `$allowOffline=false` → retorna error

**Respuesta en modo offline**:
```php
[
    'success' => true,
    'message' => 'Licencia generada en modo offline',
    'license' => 'PINN1234567890',
    'expiration_date' => '2025-12-24',
    'first_activation' => '2025-11-24',
    'offline_mode' => true,
    'pending_sync' => true,
    'sync_error' => 'Error de conexión con servidor'
]
```

**`generateUniqueLicenseLocal()`**
- Genera licencia local sin verificar servidor
- Formato: `PINN + timestamp(5) + random(5)`
- Ejemplo: `PINN6055834567`

**`retryRegistration($licenseData)`**
- Reintenta registrar una licencia pendiente
- Usado por script de sincronización

### 3. **WizardController Actualizado**

**Archivo**: `app/Controllers/WizardController.php`

**Cambios en `createCompany()`**:

1. Lee configuración `LICENSING_ALLOW_OFFLINE`
2. Pasa parámetro a `generateAndRegister()`
3. Guarda estado offline en `$companyData`
4. Logs diferenciados según modo
5. Mensaje de advertencia al usuario

**Log en modo offline**:
```log
=== ⚠️ LICENCIA GENERADA EN MODO OFFLINE ===
Licencia: PINN6055834567
Empresa: Empresa Demo S.A.
RUC: 1234567890
Expiración: 2025-12-24
Estado: PENDIENTE DE REGISTRO EN SERVIDOR REMOTO
Motivo: Error de conexión con el servidor
============================================
```

**Log en modo online**:
```log
=== LICENCIA GENERADA Y REGISTRADA ===
Licencia: PINN8091605110
Empresa: Empresa Demo S.A.
RUC: 1234567890
Expiración: 2025-12-24
Estado: REGISTRADA EN SERVIDOR REMOTO
======================================
```

### 4. **WizardModel Actualizado**

**Archivo**: `app/Models/WizardModel.php`

**Cambios en `createCompanyRecord()`**:

- Guarda `license_sync_pending = 1` si está en modo offline
- Guarda `license_status = 'PENDING_SYNC'` si está pendiente
- Guarda `license_sync_error` con mensaje descriptivo
- Guarda `license_expires_at` calculado

### 5. **Script de Sincronización**

**Archivo**: `scripts/sync_pending_licenses.php`

**Uso**:
```bash
# Sincronizar todas las licencias pendientes
php scripts/sync_pending_licenses.php

# Modo verbose (con más detalles)
php scripts/sync_pending_licenses.php --verbose

# Sincronizar una licencia específica
php scripts/sync_pending_licenses.php --license=PINN1234567890
```

**Características**:
- ✅ Output con colores para mejor legibilidad
- ✅ Estadísticas de sincronización (total/exitosas/fallidas)
- ✅ Actualiza estado en BD automáticamente
- ✅ Logging detallado de cada operación
- ✅ Manejo robusto de errores

**Output ejemplo**:
```
======================================================================
  Sincronización de Licencias Pendientes
======================================================================

✓ Conectado a base de datos master
ℹ Buscando todas las licencias pendientes...
ℹ Encontradas 3 licencia(s) pendiente(s)

----------------------------------------------------------------------
Procesando: Empresa Demo S.A.
Licencia: PINN6055834567
RUC: 1234567890
Email: admin@empresa.com
Último intento: 2025-11-24 10:30:00
Error previo: Error de conexión con el servidor
✓ Sincronización exitosa

----------------------------------------------------------------------
Procesando: Otra Empresa S.A.
Licencia: PINN7166945678
✗ Sincronización fallida: Timeout connecting to server

======================================================================
  Resumen de Sincronización
======================================================================

Total procesadas: 3
Exitosas:         2
Fallidas:         1

✓ Se sincronizaron 2 licencia(s) exitosamente
⚠ Hay 1 licencia(s) que no se pudieron sincronizar
ℹ Ejecute el script nuevamente cuando el servidor esté disponible
```

## 🔄 Flujo de Operación

### Escenario 1: Servidor Disponible (Normal)

```
Usuario → Wizard → LicenseGenerator
                   ↓
                   Generar licencia
                   ↓
                   Verificar en servidor ✓
                   ↓
                   Registrar en servidor ✓
                   ↓
                   BD: license_sync_pending = 0
                   BD: license_status = 'ACTIVE'
                   ↓
Empresa creada → Email enviado → Usuario logueado
```

### Escenario 2: Servidor No Disponible (Modo Offline)

```
Usuario → Wizard → LicenseGenerator
                   ↓
                   Generar licencia LOCAL
                   ↓
                   Intentar registrar en servidor ✗
                   ↓
                   MODO OFFLINE ACTIVADO
                   ↓
                   BD: license_sync_pending = 1
                   BD: license_status = 'PENDING_SYNC'
                   BD: license_sync_error = "mensaje error"
                   ↓
Empresa creada → Email enviado → Usuario logueado
                   ↓
                   [Más tarde]
                   ↓
Script Sincronización → Reintenta registro
                        ↓
                        Registro exitoso ✓
                        ↓
                        BD: license_sync_pending = 0
                        BD: license_status = 'ACTIVE'
                        BD: license_sync_error = NULL
```

## 📊 Estados de Licencia

| Estado | Descripción | Acción Requerida |
|--------|-------------|------------------|
| `ACTIVE` | Licencia registrada en servidor remoto | Ninguna |
| `PENDING_SYNC` | Licencia generada localmente, pendiente de registro | Ejecutar script de sincronización |
| `PENDING` | Estado legacy, reemplazado por PENDING_SYNC | Actualizar a PENDING_SYNC |

## 🧪 Testing

### Simular Servidor Caído

**Opción 1**: Modificar temporalmente `.env`
```env
LICENSE_VALIDATION_URL="https://servidor-inexistente.com/ajax/license.php"
```

**Opción 2**: Modificar `LicenseGenerator.php` para forzar timeout corto
```php
$this->timeout = 1; // 1 segundo de timeout
```

### Verificar Modo Offline

1. **Crear empresa con servidor caído**
   ```bash
   # Acceder a http://localhost/planilla-innova/crear-empresa
   # Completar wizard
   ```

2. **Verificar logs**
   ```bash
   # Ver últimas líneas del error log
   tail -n 50 /path/to/php-error.log
   ```

   Buscar:
   ```
   ⚠️ MODO OFFLINE ACTIVADO - Servidor de licencias no disponible
   ```

3. **Verificar BD**
   ```sql
   SELECT company_name, license_key, license_sync_pending,
          license_status, license_sync_error
   FROM planilla_master.tenants
   WHERE license_sync_pending = 1;
   ```

4. **Sincronizar**
   ```bash
   php scripts/sync_pending_licenses.php --verbose
   ```

## 🔒 Seguridad

### Unicidad de Licencias Locales

**Formato**: `PINN + timestamp(5) + random(5)`

**Probabilidad de colisión**: Extremadamente baja
- Timestamp: Cambia cada segundo (5 dígitos)
- Random: 5 dígitos (100,000 combinaciones)
- Total combinaciones por segundo: 100,000
- Si se generan 10 licencias por segundo, probabilidad de colisión: 0.01%

### Validación en Login

El sistema de validación en login (`LicenseValidator`) funciona normalmente:
- Licencias `PENDING_SYNC` → Permitidas (usuario puede trabajar)
- Licencias expiradas → Bloqueadas
- Licencias inválidas → Bloqueadas

## 📈 Monitoreo

### Consulta de Licencias Pendientes

```sql
-- Ver todas las licencias pendientes
SELECT
    company_name,
    license_key,
    license_status,
    license_sync_error,
    license_last_sync_attempt,
    TIMESTAMPDIFF(HOUR, license_last_sync_attempt, NOW()) as hours_since_attempt
FROM planilla_master.tenants
WHERE license_sync_pending = 1
ORDER BY license_last_sync_attempt DESC;
```

### Estadísticas Generales

```sql
-- Estadísticas de licencias
SELECT
    license_status,
    COUNT(*) as total,
    SUM(license_sync_pending) as pending_sync
FROM planilla_master.tenants
GROUP BY license_status;
```

## 🚀 Automatización (Recomendado)

### Cron Job Linux/Mac

```bash
# Sincronizar cada hora
0 * * * * php /path/to/planilla-innova/scripts/sync_pending_licenses.php >> /var/log/license-sync.log 2>&1
```

### Task Scheduler Windows

```powershell
# Crear tarea programada que ejecuta cada hora
schtasks /create /tn "SyncLicenses" /tr "php C:\laragon60\www\planilla-innova\scripts\sync_pending_licenses.php" /sc hourly
```

### Webhook (Avanzado)

Crear endpoint que se ejecute cuando el servidor remoto vuelva online:
```php
// POST /webhooks/license-server-online
public function onLicenseServerOnline() {
    exec('php ' . __DIR__ . '/../../scripts/sync_pending_licenses.php > /dev/null 2>&1 &');
    return ['success' => true, 'message' => 'Sync started'];
}
```

## 📝 Notas Importantes

1. **Configuración por Defecto**: `LICENSING_ALLOW_OFFLINE=true` (modo permisivo)
2. **Licencias Locales**: Válidas por 30 días igual que las remotas
3. **Sincronización**: Puede ejecutarse múltiples veces sin problema
4. **Idempotencia**: El script verifica estado antes de sincronizar
5. **Logging**: Todos los eventos se registran en error_log de PHP

## 🔗 Archivos Relacionados

- `app/Services/LicenseGenerator.php` - Generación y registro de licencias
- `app/Services/LicenseValidator.php` - Validación en login
- `app/Controllers/WizardController.php` - Flujo de creación de empresas
- `app/Models/WizardModel.php` - Persistencia de datos de tenant
- `scripts/sync_pending_licenses.php` - Script de sincronización
- `database/migrations/master/2025_11_24_add_license_sync_fields_to_tenants.sql` - Migración BD

## 💡 Casos de Uso

### Caso 1: Mantenimiento Programado del Servidor

```
1. Servidor en mantenimiento (2 horas)
2. Durante ese tiempo: 5 empresas creadas en modo offline
3. Mantenimiento termina
4. Ejecutar: php scripts/sync_pending_licenses.php
5. Resultado: 5 licencias sincronizadas exitosamente
```

### Caso 2: Problemas de Red Intermitentes

```
1. Red inestable
2. 3 empresas creadas, 2 en modo offline, 1 online
3. Script sincronización ejecutado cada hora (cron)
4. Después de 4 horas: todas sincronizadas
```

### Caso 3: Demo/Desarrollo sin Servidor

```
1. Desarrollador sin VPN a servidor producción
2. LICENSING_ALLOW_OFFLINE=true en .env
3. Puede crear empresas de prueba sin problemas
4. Sincronización manual cuando tenga acceso
```

---

**Ventajas del Sistema**:
- ✅ Alta disponibilidad - Sistema no se detiene por servidor caído
- ✅ Experiencia de usuario sin interrupciones
- ✅ Sincronización automática en background
- ✅ Logging completo para auditoría
- ✅ Configuración flexible (modo permisivo/estricto)
- ✅ Monitoreo y estadísticas incluidas
