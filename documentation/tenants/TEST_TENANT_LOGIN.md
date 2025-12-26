# Prueba de Login con Código de Empresa

## ✅ Sistema Implementado

El sistema de login ahora soporta **2 formas de acceso**:

### 1. Login Sin Código (Base de Datos Principal)
- Dejar el campo "Código de Empresa" **vacío**
- Se conectará automáticamente a `planilla_prod` (base de datos principal)
- Usuario: Los usuarios existentes en `planilla_prod`

### 2. Login Con Código (Tenant Específico)
- Ingresar un código de empresa en el campo "Código de Empresa"
- El sistema buscará el tenant por: **RUC, License Key, ID, o Slug**
- Se conectará a la base de datos específica del tenant

---

## 🧪 Datos de Prueba Disponibles

### Empresa de Prueba #1: "Empresa Test"
- **RUC**: `12345678`
- **Base de Datos**: `planilla_tenant_ef797c8118`
- **Usuario Admin**: `admin`
- **Contraseña**: `admin123` (la que se usó en el wizard)
- **ID Tenant**: 6

### Empresa de Prueba #2: "empresa prueba"
- **RUC**: `1234567890`
- **Base de Datos**: `planilla_tenant_c775e7b757`
- **ID Tenant**: 7

### Empresa de Prueba #3: "prueba2"
- **RUC**: `098767890`
- **Base de Datos**: `planilla_tenant_500ba32bad`
- **ID Tenant**: 8

---

## 📝 Pasos para Probar

### Prueba 1: Login Base Principal (sin código)
1. Ir a: `http://localhost/planilla-innova/panel/login`
2. **No ingresar** código de empresa
3. Usar credenciales de `planilla_prod`
4. ✅ Debería entrar a la base de datos principal

### Prueba 2: Login Tenant por RUC
1. Ir a: `http://localhost/planilla-innova/panel/login`
2. Usuario: `admin`
3. Contraseña: `admin123`
4. **Código de Empresa**: `12345678`
5. Clic en "Entrar"
6. ✅ Debería resolver el tenant y autenticar en `planilla_tenant_ef797c8118`

### Prueba 3: Login Tenant por ID
1. Ir a: `http://localhost/planilla-innova/panel/login`
2. Usuario: `admin`
3. Contraseña: `admin123`
4. **Código de Empresa**: `6`
5. Clic en "Entrar"
6. ✅ Debería resolver el tenant por ID

### Prueba 4: Código Inválido
1. Ir a: `http://localhost/planilla-innova/panel/login`
2. Usuario: `admin`
3. Contraseña: `admin123`
4. **Código de Empresa**: `99999999` (código que no existe)
5. Clic en "Entrar"
6. ✅ Debería mostrar error: "Código de empresa no válido o inactivo"

---

## 🔍 Verificar en Logs

Los logs de error de PHP mostrarán:

```
# Login exitoso con código:
Tenant resolved by code '12345678': Empresa Test (DB: planilla_tenant_ef797c8118)
Tenant resolved for login: Empresa Test (RUC/Code: 12345678)

# Login sin código:
Login without company code - using default database

# Código inválido:
Tenant not found with code: 99999999
```

**Ubicación de logs**:
- Windows: `C:/xampp/php/logs/php_error_log`
- Laravel: `storage/logs/`

---

## 🔧 Implementación Técnica

### Archivos Modificados:
1. **`app/Core/TenantResolver.php`**
   - Método nuevo: `resolveByCompanyCode(string $code)`
   - Busca tenant por RUC, license_key, ID, o slug
   - Guarda en sesión para persistencia

2. **`app/Controllers/Admin.php`**
   - Líneas 81-108: Procesamiento del campo `company_code`
   - Resuelve tenant antes de autenticar
   - Resetea conexión de BD si se encuentra tenant

3. **`app/Views/admin/login.php`**
   - Líneas 54-64: Campo opcional "Código de Empresa"
   - Icono: `fa-building`
   - Placeholder: "Código de Empresa (Opcional)"

### Flujo de Login:
```
1. Usuario ingresa credenciales + código empresa (opcional)
2. POST a /panel/login
3. Validar CSRF y rate limiting
4. Si hay código empresa:
   a. TenantResolver::resolveByCompanyCode($code)
   b. Si encuentra: resetear Database instance
   c. Si no encuentra: error y redirect
5. Autenticar usuario en BD correspondiente
6. Establecer sesión
7. Redirect a dashboard
```

---

## ⚠️ Notas Importantes

1. **Seguridad**: El código de empresa se sanitiza con `Security::sanitizeInput()`
2. **Sesión**: El tenant se guarda en `$_SESSION['tenant_db']` para persistencia
3. **Logging**: Todos los intentos se registran en logs de error
4. **Fallback**: Si no hay código, usa `planilla_prod` por defecto
5. **Validación**: Solo tenants con status='ACTIVE' son accesibles

---

## 🎯 Próximos Pasos

1. ✅ Sistema base implementado
2. 🔄 Pruebas manuales
3. 📊 Agregar métricas de uso por tenant
4. 🔐 Implementar 2FA por tenant (opcional)
5. 🎨 Personalizar logos por tenant en login
6. 📧 Notificaciones de login por tenant

---

## 🐛 Troubleshooting

### Error: "Master database connection is not configured"
- Verificar archivo `config/master_database.php`
- Verificar que existe base de datos `planilla_master`

### Error: "Tenant not found with code: XXXX"
- Verificar que el tenant existe: `SELECT * FROM tenants WHERE ruc='XXXX'`
- Verificar que status='ACTIVE'

### Error: "Credenciales incorrectas" (después de resolver tenant)
- El usuario debe existir en la base de datos del **tenant específico**
- Verificar: `SELECT * FROM planilla_tenant_XXXX.admin WHERE username='admin'`

### Sesión no persiste entre requests
- Verificar que `session_start()` se llama en bootstrap
- Verificar permisos de carpeta de sesiones PHP
