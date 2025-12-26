# 🔑 Sistema de Licencias - Multitenancy

## ✅ Sistema Implementado (v3.5.8)

### Cambios Principales

1. **Generación de Licencias Únicas**
   - Formato: `INNO-XXXX-XXXX-XXXX` (ejemplo: `INNO-A3F2-B8D1-C4E9`)
   - Generadas automáticamente durante creación de empresa
   - Verificación de unicidad en base de datos master

2. **Nombre de Base de Datos = Licencia**
   - Formato BD: `planilla_innoxxxxyyyyzzzz` (sin guiones)
   - Ejemplo: Licencia `INNO-A3F2-B8D1-C4E9` → BD `planilla_innoa3f2b8d1c4e9`

3. **Login por Licencia**
   - Campo en formulario: "Licencia (Opcional)"
   - Icono: 🔑 (fa-key)
   - Prioridad de búsqueda: License Key → RUC → ID → Slug

---

## 🔄 Flujo de Creación de Empresa

### Antes (v3.5.7)
```
1. Crear registro master (sin licencia)
2. Generar BD basada en RUC hash
3. Importar schema
4. Crear usuario admin
5. GENERAR licencia (al final)
6. Actualizar registro
```

### Ahora (v3.5.8)
```
1. ✨ GENERAR LICENCIA PRIMERO
2. Crear registro master CON licencia
3. Generar BD basada en LICENCIA
4. Importar schema
5. Crear usuario admin
6. Actualizar registro
```

---

## 📝 Flujo Completo de Uso

### Paso 1: Crear Empresa (Wizard)
1. Ir a: `http://localhost/planilla-innova/crear-empresa`
2. Llenar formulario de 3 pasos
3. Al finalizar, recibirás:
   ```json
   {
     "success": true,
     "license_key": "INNO-A3F2-B8D1-C4E9",
     "database_name": "planilla_innoa3f2b8d1c4e9",
     "company_id": 10
   }
   ```

### Paso 2: Guardar Licencia
⚠️ **IMPORTANTE**: Guarda la licencia mostrada. Es el código de acceso de la empresa.

### Paso 3: Login con Licencia
1. Ir a: `http://localhost/planilla-innova/panel/login`
2. Llenar formulario:
   - **Usuario**: El que creaste en el wizard
   - **Contraseña**: La que pusiste en el wizard
   - **Licencia**: `INNO-A3F2-B8D1-C4E9`
3. Clic en "Entrar"

---

## 🔍 Estructura de Datos

### Tabla `tenants` (master database)
```sql
CREATE TABLE tenants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255),
    ruc VARCHAR(50),
    license_key VARCHAR(50) UNIQUE,     -- ⭐ CLAVE PRINCIPAL
    db_name VARCHAR(100),                -- Basado en license_key
    db_host VARCHAR(100),
    db_port INT,
    db_user VARCHAR(100),
    db_pass_enc TEXT,
    status ENUM('ACTIVE', 'SUSPENDED', 'INACTIVE'),
    license_status ENUM('ACTIVE', 'PENDING', 'EXPIRED')
);
```

### Ejemplo de Registro
```sql
INSERT INTO tenants VALUES (
    10,
    'Mi Empresa S.A.',
    '12345678-1-123456',
    'INNO-A3F2-B8D1-C4E9',           -- Licencia única
    'planilla_innoa3f2b8d1c4e9',      -- BD basada en licencia
    'localhost',
    3306,
    'root',
    'encrypted_pass',
    'ACTIVE',
    'ACTIVE'
);
```

---

## 🔐 Sistema de Resolución de Tenant

### Prioridades de Búsqueda

Cuando ingresas un código en el login, el sistema busca en este orden:

1. **License Key** (prioridad 1) ⭐
   - `WHERE license_key = 'INNO-A3F2-B8D1-C4E9'`

2. **RUC** (prioridad 2)
   - `WHERE ruc = '12345678-1-123456'`

3. **ID** (prioridad 3)
   - `WHERE id = 10`

4. **Slug** (prioridad 4)
   - `WHERE slug = 'mi-empresa-sa'`

### Ejemplo de Código SQL
```sql
SELECT * FROM tenants
WHERE (license_key = ? OR ruc = ? OR id = ? OR slug = ?)
  AND status = 'ACTIVE'
ORDER BY
    CASE
        WHEN license_key = ? THEN 1
        WHEN ruc = ? THEN 2
        WHEN id = ? THEN 3
        ELSE 4
    END
LIMIT 1;
```

---

## 📊 Ventajas del Nuevo Sistema

### ✅ Seguridad
- Licencias únicas imposibles de adivinar
- Fáciles de revocar (cambiar status)
- No dependen de datos públicos (RUC)

### ✅ Usabilidad
- Formato legible: `INNO-XXXX-XXXX-XXXX`
- Fácil de comunicar por teléfono
- Fácil de copiar/pegar

### ✅ Mantenibilidad
- Nombre de BD predecible
- Fácil debugging: ver BD = ver licencia
- Migración simple entre servidores

### ✅ Escalabilidad
- Sin colisiones de nombres de BD
- 16^12 combinaciones posibles (~281 trillones)
- Soporta subdominios futuros: `innoxxxx.planilla.com`

---

## 🧪 Testing

### Crear Empresa de Prueba
```bash
# 1. Ir al wizard
http://localhost/planilla-innova/crear-empresa

# 2. Llenar datos:
Empresa: "Empresa Test 4"
RUC: "111222333"
Usuario: "admin"
Email: "admin@test.com"
Contraseña: "admin123"

# 3. Resultado esperado:
License: INNO-XXXX-XXXX-XXXX
BD: planilla_innoxxxxxxxxxxxxxxxx
```

### Probar Login
```bash
# URL
http://localhost/planilla-innova/panel/login

# Credenciales
Usuario: admin
Contraseña: admin123
Licencia: INNO-XXXX-XXXX-XXXX

# Resultado esperado
✅ Login exitoso → Dashboard
✅ Conectado a BD tenant
✅ Sesión persistente
```

### Verificar en Base de Datos
```sql
-- Ver último tenant creado
SELECT id, company_name, license_key, db_name, status
FROM planilla_master.tenants
ORDER BY id DESC
LIMIT 1;

-- Verificar BD fue creada
SHOW DATABASES LIKE 'planilla_inno%';

-- Verificar usuarios en tenant
SELECT username, firstname, lastname
FROM planilla_innoxxxxxxxxxx.admin;
```

---

## 🔧 Archivos Modificados (v3.5.8)

### 1. **WizardModel.php**
- Método nuevo: `generateUniqueLicense()` (líneas 237-257)
- Modificado: `generateTenantDatabaseName()` (líneas 136-148)
- Modificado: `generateAndValidateLicense()` (líneas 259-264)

### 2. **WizardController.php**
- Modificado: `createCompany()` flujo (líneas 179-202)
- Modificado: `getCompanyLoginUrl()` (líneas 373-376)
- Respuesta incluye `license_key` (línea 217)

### 3. **TenantResolver.php**
- Modificado: `resolveByCompanyCode()` con priorización (líneas 54-104)
- Logging mejorado con licencia (líneas 102-103)

### 4. **login.php**
- Modificado: Label "Licencia" en lugar de "Código Empresa" (línea 55)
- Icono cambiado a `fa-key` (línea 58)
- Helper text actualizado (línea 63)

---

## 📈 Roadmap Futuro

### v3.6.0 - Gestión de Licencias
- [ ] Panel administración de licencias
- [ ] Renovación automática
- [ ] Notificaciones de expiración
- [ ] Métricas de uso por licencia

### v3.7.0 - Subdominios
- [ ] Sistema de subdominios: `{license}.planilla.com`
- [ ] Detección automática por subdomain
- [ ] Sin necesidad de campo licencia en login

### v3.8.0 - API de Licencias
- [ ] Endpoint validación licencias
- [ ] Webhook activación/desactivación
- [ ] Integración con plataforma de pagos

---

## ⚠️ Notas Importantes

1. **Migración de Tenants Existentes**
   - Los tenants creados antes de v3.5.8 NO tienen licencia
   - Pueden seguir accediendo por RUC o ID
   - Se recomienda generar licencias retroactivas

2. **Compatibilidad con RUC**
   - El sistema sigue soportando login por RUC
   - License key tiene prioridad si ambos coinciden
   - No hay breaking changes

3. **Base de Datos Master**
   - DEBE tener tabla `tenants` actualizada
   - Campo `license_key` debe ser UNIQUE
   - Status debe ser 'ACTIVE' para acceso

4. **Logging**
   - Todos los intentos se registran en PHP error log
   - Incluye licencia usada para debugging
   - Útil para auditoría de accesos

---

## 🐛 Troubleshooting

### "Código de empresa no válido"
**Causa**: Licencia no existe o tenant inactivo
**Solución**: Verificar en master DB
```sql
SELECT * FROM tenants WHERE license_key = 'INNO-XXXX-XXXX-XXXX';
```

### "Credenciales incorrectas" (con licencia válida)
**Causa**: Usuario no existe en BD tenant
**Solución**: Verificar usuarios
```sql
USE planilla_innoxxxxxxxxxx;
SELECT * FROM admin;
```

### BD no encontrada después de crear empresa
**Causa**: Error en importación de schema
**Solución**: Revisar logs de error PHP
```bash
tail -f C:/xampp/php/logs/php_error_log
```

### Licencia duplicada al crear empresa
**Causa**: Hash collision (extremadamente raro)
**Solución**: El sistema regenera automáticamente

---

## 📞 Soporte

Para reportar problemas con el sistema de licencias:
1. Incluir licencia afectada
2. Incluir logs de error PHP
3. Incluir resultado de query master DB
