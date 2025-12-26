# ⚡ Quick Fix: Error "upstream sent too big header" + Logs Debug

## 🎯 Problemas
1. `[error] upstream sent too big header while reading response header from upstream`
2. `FastCGI sent in stderr: "PHP message: Connected to tenant database: PINN77337420"`

## ✅ Solución Rápida (5 minutos)

### Paso 1: Subir archivos actualizados
```bash
# Desde tu máquina local (Git Bash)
cd C:/laragon60/www/planilla-innova

scp app/Core/Database.php root@planilla.innovasoftlatam.com:/var/www/html/planilla/app/Core/
scp app/Core/Model.php root@planilla.innovasoftlatam.com:/var/www/html/planilla/app/Core/
scp app/Core/TenantStorage.php root@planilla.innovasoftlatam.com:/var/www/html/planilla/app/Core/
scp app/Controllers/Employee.php root@planilla.innovasoftlatam.com:/var/www/html/planilla/app/Controllers/
```

### Paso 2: Conectar al servidor
```bash
ssh root@planilla.innovasoftlatam.com
```

### Paso 3: Editar configuración Nginx
```bash
sudo nano /etc/nginx/sites-available/planilla.innovasoftlatam.com
```

**Agregar estas 3 líneas** dentro del bloque `server {}`:
```nginx
# Fix: upstream sent too big header (24-Dic-2024)
fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;
fastcgi_busy_buffers_size 32k;
```

### Paso 4: Verificar y recargar
```bash
# Verificar sintaxis
sudo nginx -t

# Recargar Nginx
sudo systemctl reload nginx
```

## ✅ Verificación
1. Editar un empleado: https://planilla.innovasoftlatam.com/panel/employees/edit/25
2. Guardar cambios
3. Verificar que se guarde sin errores
4. El error "upstream sent too big header" NO debe aparecer en logs

## 📋 Archivos Modificados
- ✅ `app/Core/Database.php` - Logs de debug comentados (12 líneas)
- ✅ `app/Core/Model.php` - Logs de debug comentados (13 líneas)
- ✅ `app/Controllers/Employee.php` - Logs de debug comentados (2 líneas)
- ✅ `app/Core/TenantStorage.php` - Comentarios mejorados (0 cambios funcionales)
- ✅ Nginx config - Buffers aumentados de 4k a 32k

**Total: 27 logs comentados | 0 cambios funcionales | 100% compatible**

## 🔄 Rollback (si algo sale mal)
```bash
# Restaurar Nginx
sudo cp /etc/nginx/sites-available/planilla.innovasoftlatam.com.backup.* \
       /etc/nginx/sites-available/planilla.innovasoftlatam.com
sudo systemctl reload nginx
```

---

**Documentación completa**: Ver `NGINX_FASTCGI_BUFFER_FIX.md` y `RESUMEN_FIX_NGINX_HEADERS.md`
