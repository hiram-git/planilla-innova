# Fix Logs de Debug en Producción

## 🎯 Problema
Los logs de debug están generando headers HTTP enormes y apareciendo en los error logs de Nginx:
```
FastCGI sent in stderr: "PHP message: Connected to tenant database: PINN77337420"
```

## ✅ Archivos Modificados

### 1. app/Core/Database.php (12 logs comentados)
- **Línea 48-50**: Log "Connected to tenant database"
- **Líneas 110-114**: Logs debug de INSERT en employee_payroll_salaries
- **Línea 120**: Log Last Insert ID
- **Líneas 138-144**: Logs debug de UPDATE (SQL, Data, Params, DB Version)
- **Línea 151**: Log Rows affected
- **Línea 165**: Log Record exists

### 2. app/Core/Model.php (13 logs comentados)
- Método `create()`: 10 logs de debug
- Método `update()`: 3 logs de debug (incluido `print_r()`)

### 3. app/Controllers/Employee.php (2 logs comentados)
- Líneas 387-388: Logs de update con `print_r($updateData, true)`

### 4. app/Core/TenantStorage.php (Mejora en getPublicLogoUrl)
- Añadidos comentarios explicativos en lógica de resolución de paths
- Sin cambios funcionales, solo documentación

## 📦 Total de Cambios

| Archivo | Logs Comentados | Funcionalidad |
|---------|----------------|---------------|
| Database.php | 12 | ✅ Sin cambios |
| Model.php | 13 | ✅ Sin cambios |
| Employee.php | 2 | ✅ Sin cambios |
| TenantStorage.php | 0 | ✅ Documentación mejorada |
| **TOTAL** | **27 logs** | **100% funcional** |

## 🚀 Deployment a Producción

### Paso 1: Subir archivos actualizados
```bash
# Desde tu máquina local (Git Bash)
cd C:/laragon60/www/planilla-innova

# Subir archivos
scp app/Core/Database.php root@planilla.innovasoftlatam.com:/var/www/html/planilla/app/Core/
scp app/Core/Model.php root@planilla.innovasoftlatam.com:/var/www/html/planilla/app/Core/
scp app/Controllers/Employee.php root@planilla.innovasoftlatam.com:/var/www/html/planilla/app/Controllers/
scp app/Core/TenantStorage.php root@planilla.innovasoftlatam.com:/var/www/html/planilla/app/Core/
```

### Paso 2: Verificar permisos (opcional)
```bash
ssh root@planilla.innovasoftlatam.com

cd /var/www/html/planilla
chown -R www-data:www-data app/Core/
chown -R www-data:www-data app/Controllers/
chmod -R 755 app/
```

### Paso 3: Verificar logs
```bash
# Monitorear logs de Nginx
sudo tail -f /var/log/nginx/error.log

# Monitorear logs de PHP-FPM
sudo tail -f /var/log/php8.1-fpm.log
```

## ✅ Verificación Post-Deployment

### 1. Verificar que NO aparezcan estos mensajes en logs:
- ❌ "Connected to tenant database: PINN77337420"
- ❌ "[Database::insert] SQL: ..."
- ❌ "[Database::update] Data: ..."
- ❌ "[Employee::update] Datos a actualizar: ..."
- ❌ "Llamando a db->update() en tabla 'employees'..."

### 2. Verificar que SÍ funcionen correctamente:
- ✅ Edición de empleados
- ✅ Creación de empleados
- ✅ Visualización de logos de empresa
- ✅ Subida de logos
- ✅ Todas las funcionalidades del sistema

### 3. Verificar que SÍ aparezcan logs de errores críticos:
- ✅ "Database connection failed: ..." (solo si hay error real)
- ✅ "Model::create() ERROR en tabla employees: ..." (solo si hay error real)
- ✅ "UPDATE ERROR: ..." (solo si hay error real)

## 📊 Impacto Esperado

### Antes del Fix:
- ❌ Logs verbosos en cada request
- ❌ Headers HTTP pueden exceder límites
- ❌ Logs de error llenos de mensajes innecesarios
- ❌ Difícil identificar errores reales

### Después del Fix:
- ✅ Solo logs de errores críticos
- ✅ Headers HTTP limpios y pequeños
- ✅ Logs de error limpio y útil
- ✅ Fácil identificación de errores reales

## 🔍 Problema de Logos (Investigación Adicional)

El error original reportado:
```
open() "/var/www/html/planilla/images/logos/logo_empresa_1761936623.png" failed (2: No such file or directory)
```

**Causa**: El logo se guarda correctamente en subdirectorio tenant:
- Filesystem: `/var/www/html/planilla/images/logos/tenants/19/logo_empresa_1761936623.png`
- BD: `tenants/19/logo_empresa_1761936623.png`
- URL generada: `/images/logos/tenants/19/logo_empresa_1761936623.png` ✅

**Verificación en producción**:
```bash
# Verificar que exista el directorio del tenant
ls -la /var/www/html/planilla/images/logos/tenants/

# Verificar que existan los logos
ls -la /var/www/html/planilla/images/logos/tenants/19/

# Verificar permisos
ls -lh /var/www/html/planilla/images/logos/tenants/19/*.png
```

**Si el archivo no existe en el servidor**:
El logo fue subido en desarrollo pero no sincronizado a producción. Solución:
1. Re-subir el logo desde la interfaz web en `/panel/company`
2. O copiar manualmente los archivos de logos:
   ```bash
   scp -r C:/laragon60/www/planilla-innova/images/logos/tenants/ \
       root@planilla.innovasoftlatam.com:/var/www/html/planilla/images/logos/
   ```

## 🎯 Resultado Final

Después de aplicar este fix:
1. ✅ NO más logs verbosos en Nginx error logs
2. ✅ Headers HTTP limpios (< 4k)
3. ✅ Solo errores críticos aparecen en logs
4. ✅ Performance mejorada (menos I/O de logs)
5. ✅ Logs más útiles para debugging real

---

**Fecha**: 24-Dic-2024
**Archivos modificados**: 4
**Logs comentados**: 27
**Cambios funcionales**: 0
**Deployment time**: 5 minutos
