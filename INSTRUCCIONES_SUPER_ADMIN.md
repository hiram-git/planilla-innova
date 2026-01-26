# 🛡️ Instrucciones: Sistema Super Administrador

## 📋 Resumen

Se ha implementado un sistema de **Super Administrador** que permite identificar al administrador principal del sistema con privilegios especiales, incluyendo acceso con licencia expirada y protección de contraseña.

---

## 🔧 Pasos de Implementación

### **Paso 1: Ejecutar Migración de Base de Datos**

```bash
cd database/migrations
mysql -u root -p < 2026_01_26_add_is_system_admin_to_admin.sql
```

**¿Qué hace esta migración?**
- Agrega la columna `is_system_admin TINYINT(1)` a la tabla `admin`
- Crea un índice para mejorar el rendimiento
- Por defecto, todos los usuarios tienen `is_system_admin = 0`

---

### **Paso 2: Marcar al Super Administrador**

1. **Identificar el username del super administrador:**
   ```sql
   SELECT id, username, firstname, lastname FROM admin WHERE status = 1;
   ```

2. **Marcar como super admin (ejemplo: username 'admin'):**
   ```sql
   UPDATE admin SET is_system_admin = 1 WHERE username = 'admin';
   ```

3. **Verificar que solo hay UN super admin:**
   ```sql
   SELECT username, is_system_admin FROM admin WHERE is_system_admin = 1;
   ```

**Resultado esperado:** Solo 1 fila con `is_system_admin = 1`

---

## 🎯 Características del Super Administrador

### ✅ **Privilegios Especiales**

| Característica | Usuario Normal | Super Admin |
|----------------|----------------|-------------|
| Acceso con licencia expirada | ❌ Bloqueado | ✅ Permitido |
| Modificar contraseña desde UI | ✅ Permitido | ❌ Protegida |
| Puede ser eliminado | ✅ Sí | ❌ No |
| Puede ser desactivado | ✅ Sí | ❌ Estado protegido |
| Badge visible | - | 🔴 "SUPER ADMIN" |

---

### 🖥️ **Indicadores Visuales**

#### **1. Listado de Usuarios** (`/panel/users`)
- Badge rojo con icono de escudo: **🛡️ SUPER ADMIN**
- Estado muestra "Protegido" en lugar del toggle activo/inactivo
- Botón de eliminar deshabilitado

#### **2. Edición de Usuario** (`/panel/users/{id}/edit`)
- Banner rojo superior: "SUPER ADMINISTRADOR DEL SISTEMA"
- Badge en el título de la tarjeta
- Campos de contraseña reemplazados por mensaje de protección
- Botón "Reset Password" oculto
- Botón "Eliminar" deshabilitado
- Panel lateral muestra tipo: "SUPER ADMIN"

---

## 🔒 **Seguridad y Validación de Licencia**

### **Flujo de Login**

```php
// En Admin.php:338-365

if ($daysRemaining < 0) {
    $isSuperAdmin = isset($admin['is_system_admin']) && $admin['is_system_admin'] == 1;

    if (!$isSuperAdmin) {
        // ❌ Usuario normal: Acceso bloqueado
        $_SESSION['error'] = 'Licencia expirada. Contacte al administrador del sistema.';
        // ... destruye sesión y redirige
    } else {
        // ✅ Super Admin: Acceso permitido con advertencia
        $_SESSION['warning'] = 'ADVERTENCIA: Licencia expirada. Renueve la licencia lo antes posible.';
        // ... permite continuar
    }
}
```

---

## 📁 **Archivos Modificados**

### **Base de Datos**
- `database/migrations/2026_01_26_add_is_system_admin_to_admin.sql` - Migración principal
- `database/migrations/EJECUTAR_DESPUES_DE_MIGRAR_marcar_superadmin.sql` - Script helper

### **Backend**
- `app/Controllers/Admin.php:338-365` - Validación de licencia en login
- `app/Models/Admin.php:116-119` - Método `isSuperAdmin()` actualizado

### **Frontend**
- `app/Views/admin/users/index.php` - Badge y protección en listado
- `app/Views/admin/users/edit.php` - Banner, protección de contraseña y eliminación

---

## ⚠️ **Consideraciones Importantes**

### **1. Solo UN Super Admin**
```sql
-- Verificar que solo hay uno:
SELECT COUNT(*) as total_super_admins
FROM admin
WHERE is_system_admin = 1 AND status = 1;
-- Resultado esperado: 1
```

### **2. Backup Antes de Migrar**
```bash
mysqldump -u root -p planilla_prod > backup_antes_super_admin_$(date +%Y%m%d).sql
```

### **3. No Eliminar al Super Admin**
- Si necesitas cambiar el super admin, primero marca otro usuario y luego desmarca el anterior
- NUNCA dejes la base de datos sin un super admin

### **4. Cambio de Contraseña del Super Admin**
- Solo se puede cambiar directamente en la base de datos:
  ```sql
  -- Generar hash de contraseña en PHP:
  -- password_hash('nueva_contraseña', PASSWORD_DEFAULT)

  UPDATE admin
  SET password = '$2y$10$HASH_GENERADO_AQUI'
  WHERE is_system_admin = 1;
  ```

---

## 🧪 **Pruebas Recomendadas**

### **Test 1: Licencia Expirada - Usuario Normal**
1. Ingresar con usuario normal (is_system_admin = 0)
2. Con licencia expirada
3. **Resultado esperado:** Acceso bloqueado con mensaje "Licencia expirada"

### **Test 2: Licencia Expirada - Super Admin**
1. Ingresar con super admin (is_system_admin = 1)
2. Con licencia expirada
3. **Resultado esperado:** Acceso permitido + advertencia amarilla visible

### **Test 3: Protección de Contraseña**
1. Ir a editar super admin
2. Intentar ver campos de contraseña
3. **Resultado esperado:** Campos ocultos, mensaje de protección visible

### **Test 4: Protección de Eliminación**
1. Ir al listado de usuarios
2. Ver fila del super admin
3. **Resultado esperado:** Botón eliminar deshabilitado, badge "SUPER ADMIN" visible

---

## 🔄 **Rollback (Si es necesario)**

Si necesitas revertir los cambios:

```sql
-- Eliminar columna is_system_admin
ALTER TABLE admin DROP COLUMN is_system_admin;

-- Eliminar índice
DROP INDEX idx_is_system_admin ON admin;
```

Luego revertir los cambios en código usando Git:
```bash
git checkout HEAD -- app/Controllers/Admin.php
git checkout HEAD -- app/Models/Admin.php
git checkout HEAD -- app/Views/admin/users/
```

---

## 📞 **Soporte**

Si encuentras problemas:
1. Verifica que la migración se ejecutó correctamente
2. Confirma que solo hay UN usuario con `is_system_admin = 1`
3. Revisa los logs en `storage/logs/` para errores de autenticación
4. Verifica que la sesión `$_SESSION['is_super_admin']` se está estableciendo correctamente

---

**Fecha de Implementación:** 26 de Enero, 2026
**Versión:** 3.5.20 (Sistema Super Administrador)
