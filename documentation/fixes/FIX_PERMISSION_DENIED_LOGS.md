# 🔧 Fix: Permission Denied en Logs

## ❌ **Errores Detectados:**

```
Warning: file_put_contents(/var/www/html/planilla/storage/logs/api_2025-12-26.log):
Failed to open stream: Permission denied in ApiClient.php on line 311

Warning: file_put_contents(/var/www/html/planilla/storage/logs/attendance_sync_2025-12-26.log):
Failed to open stream: Permission denied in AttendanceSyncService.php on line 634
```

## 🔍 **Causa:**

El directorio `storage/logs/` no tiene permisos de escritura para el usuario que ejecuta PHP (generalmente `www-data` en Ubuntu/Debian o `apache` en CentOS/RHEL).

**¿Por qué sucede?**
- Los archivos fueron subidos por un usuario (root, tu usuario SSH, etc.)
- PHP/Apache/Nginx ejecuta con usuario `www-data` o `apache`
- El usuario `www-data` no tiene permisos para escribir en `storage/logs/`

---

## ✅ **Solución Rápida (3 comandos):**

```bash
# Conectar al servidor
ssh root@servidor

# Ir al proyecto
cd /var/www/html/planilla

# Aplicar permisos
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
sudo chown -R www-data:www-data /var/log/planilla/
sudo chmod -R 755 /var/log/planilla/
```

**⚠️ Nota**: Si usas CentOS/RHEL, cambia `www-data` por `apache`.

---

## 🚀 **Solución Automatizada (Script):**

### **Opción A: Usar script incluido**

```bash
# 1. Subir script de fix de permisos
scp scripts/fix_permissions.sh root@servidor:/var/www/html/planilla/scripts/

# 2. Ejecutar en servidor
ssh root@servidor
cd /var/www/html/planilla
chmod +x scripts/fix_permissions.sh
sudo ./scripts/fix_permissions.sh
```

### **Opción B: Comando one-liner**

```bash
ssh root@servidor "cd /var/www/html/planilla && \
  mkdir -p storage/logs /var/log/planilla && \
  WEB_USER=\$(id -nu www-data 2>/dev/null || echo apache) && \
  chown -R \$WEB_USER:\$WEB_USER storage/ /var/log/planilla/ && \
  chmod -R 775 storage/ && chmod -R 755 /var/log/planilla/ && \
  echo 'Permisos aplicados exitosamente'"
```

---

## 🔍 **Verificación Post-Fix:**

### **1. Verificar propietario de directorios:**

```bash
ls -la storage/
```

**Resultado esperado**:
```
drwxrwxr-x 3 www-data www-data 4096 Dec 26 10:30 logs
drwxrwxr-x 2 www-data www-data 4096 Dec 26 10:30 cache
drwxrwxr-x 2 www-data www-data 4096 Dec 26 10:30 uploads
```

**Explicación de permisos** (drwxrwxr-x):
- `d` = directorio
- `rwx` (owner) = lectura, escritura, ejecución para propietario
- `rwx` (group) = lectura, escritura, ejecución para grupo
- `r-x` (others) = solo lectura y ejecución para otros

---

### **2. Verificar permisos numéricos:**

```bash
stat -c "%a %n" storage/logs/
```

**Resultado esperado**:
```
775 storage/logs/
```

**Explicación**:
- `7` (owner) = 4(read) + 2(write) + 1(execute) = 7
- `7` (group) = 4(read) + 2(write) + 1(execute) = 7
- `5` (others) = 4(read) + 0(no write) + 1(execute) = 5

---

### **3. Probar escritura como usuario web:**

```bash
# Probar escritura en storage/logs
sudo -u www-data touch storage/logs/test.log && echo "✅ OK" || echo "❌ FAIL"

# Limpiar archivo de prueba
rm storage/logs/test.log
```

**Resultado esperado**: `✅ OK`

---

### **4. Ejecutar script de sincronización:**

```bash
php scripts/cron/sync_attendance.php 2>&1 | grep -i "permission denied"
```

**Resultado esperado**: Sin output (ningún error de permisos)

---

## 📋 **Estructura de Directorios de Logs:**

El sistema escribe logs en **2 ubicaciones**:

### **1. Logs del Proyecto** (`storage/logs/`):
```
storage/
└── logs/
    ├── api_2025-12-26.log              # Logs de llamadas a API externa
    ├── attendance_sync_2025-12-26.log  # Logs de sincronización
    ├── attendance_sync_2025-12-25.log
    ├── base44_api_2025-12-26.log       # Logs específicos de Base44 API
    └── .gitkeep                         # Preserva directorio en Git
```

**Propietario requerido**: `www-data:www-data`
**Permisos requeridos**: `775` (rwxrwxr-x)

---

### **2. Logs de Cron** (`/var/log/planilla/`):
```
/var/log/planilla/
├── cron_sync.log      # Output del cron de sincronización
├── cron_eod.log       # Output del cron de fin de día
└── cron_pipeline.log  # Output del cron de pipeline
```

**Propietario requerido**: `www-data:www-data` o `root:root`
**Permisos requeridos**: `755` (rwxr-xr-x)

---

## 🛡️ **Mejores Prácticas de Permisos:**

### **Regla General:**

| Directorio/Archivo | Owner | Permisos | Razón |
|-------------------|-------|----------|-------|
| `storage/` | www-data | 775 | PHP necesita escribir |
| `storage/logs/` | www-data | 775 | PHP escribe logs diarios |
| `storage/cache/` | www-data | 775 | PHP escribe cache |
| `storage/uploads/` | www-data | 775 | PHP sube archivos |
| `/var/log/planilla/` | www-data | 755 | Cron escribe logs |
| `app/` | root | 755 | Solo lectura para PHP |
| `config/` | root | 755 | Solo lectura para PHP |
| `.env` | www-data | 600 | Solo owner puede leer (seguridad) |

---

### **Comandos Completos para Aplicar Mejores Prácticas:**

```bash
cd /var/www/html/planilla

# Aplicar owner correcto a todo el proyecto
sudo chown -R www-data:www-data .

# Directorios de código (solo lectura para PHP)
sudo chmod -R 755 app/ config/ public/ scripts/

# Directorios de escritura (lectura/escritura para PHP)
sudo chmod -R 775 storage/

# Archivo .env (solo owner puede leer)
sudo chmod 600 .env

# Logs externos
sudo mkdir -p /var/log/planilla
sudo chown -R www-data:www-data /var/log/planilla
sudo chmod -R 755 /var/log/planilla
```

---

## 🔄 **Rotación de Logs (Opcional pero Recomendado):**

Para evitar que los logs crezcan indefinidamente, configurar `logrotate`:

```bash
# Crear configuración de logrotate
sudo nano /etc/logrotate.d/planilla
```

**Contenido**:
```
/var/www/html/planilla/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0664 www-data www-data
}

/var/log/planilla/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

**Explicación**:
- `daily`: Rotar diariamente
- `rotate 30`: Mantener 30 días de logs
- `compress`: Comprimir logs antiguos con gzip
- `delaycompress`: No comprimir el log de ayer (útil para debugging)
- `missingok`: No dar error si el archivo no existe
- `notifempty`: No rotar si el archivo está vacío
- `create 0664 www-data www-data`: Crear nuevo archivo con estos permisos

**Probar configuración**:
```bash
sudo logrotate -d /etc/logrotate.d/planilla
```

---

## 🚨 **Troubleshooting:**

### **Error: "Operation not permitted"**

**Solución**: Ejecutar con `sudo`:
```bash
sudo chown -R www-data:www-data storage/
```

---

### **Error: "www-data: unknown user"**

**Causa**: Estás en CentOS/RHEL, el usuario web es `apache`.

**Solución**:
```bash
sudo chown -R apache:apache storage/
```

**O detectar automáticamente**:
```bash
WEB_USER=$(ps aux | grep -E 'apache|httpd|nginx' | grep -v grep | head -1 | awk '{print $1}')
sudo chown -R $WEB_USER:$WEB_USER storage/
```

---

### **Los logs siguen sin crearse**

**Verificar**:
1. **SELinux está bloqueando** (en CentOS/RHEL):
   ```bash
   sudo setenforce 0  # Desactivar temporalmente
   # Probar de nuevo
   # Si funciona, configurar SELinux correctamente:
   sudo chcon -R -t httpd_sys_rw_content_t storage/
   sudo setenforce 1  # Reactivar
   ```

2. **AppArmor está bloqueando** (en Ubuntu):
   ```bash
   sudo aa-status
   # Si está activo, configurar reglas para PHP
   ```

3. **Espacio en disco lleno**:
   ```bash
   df -h
   ```

---

## 📞 **Checklist de Validación:**

Después de aplicar el fix, verificar:

- [ ] **1. Propietario correcto de storage/**
  ```bash
  ls -la storage/ | grep www-data
  ```

- [ ] **2. Permisos correctos (775)**
  ```bash
  stat -c "%a" storage/logs/
  ```

- [ ] **3. Escritura funciona**
  ```bash
  sudo -u www-data touch storage/logs/test.log && rm storage/logs/test.log && echo "OK"
  ```

- [ ] **4. Script de sincronización no da error**
  ```bash
  php scripts/cron/sync_attendance.php 2>&1 | grep -i "permission"
  ```

- [ ] **5. Logs se crean automáticamente**
  ```bash
  ls -lt storage/logs/ | head -5
  ```

- [ ] **6. Directorio /var/log/planilla/ existe**
  ```bash
  ls -ld /var/log/planilla/
  ```

---

## 🎯 **Resumen Ejecutivo:**

### **Problema:**
- ❌ PHP no puede escribir en `storage/logs/`
- ❌ Warnings en ApiClient.php y AttendanceSyncService.php

### **Causa:**
- Directorios con propietario incorrecto o permisos restrictivos

### **Solución:**
```bash
sudo chown -R www-data:www-data storage/ /var/log/planilla/
sudo chmod -R 775 storage/
sudo chmod -R 755 /var/log/planilla/
```

### **Resultado:**
- ✅ Logs se crean automáticamente
- ✅ Sin warnings de permission denied
- ✅ Cron jobs funcionan correctamente

---

**Versión**: 3.5.14
**Fecha**: 26 de Diciembre, 2025
**Autor**: Sistema Planillas Innova
