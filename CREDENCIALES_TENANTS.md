# 🔑 Credenciales de Acceso - Tenants Existentes

## URL de Login
```
http://localhost/planilla-innova/panel/login
```

---

## 🏢 Tenant #1: "Empresa Test" (ID: 6)

### Licencia
```
INNO-TEST-0001-EF79
```

### Base de Datos
```
planilla_tenant_ef797c8118
```

### Usuarios Disponibles
| Usuario | Contraseña | Notas |
|---------|------------|-------|
| admin | (contraseña original) | Usuario por defecto |
| innova | (contraseña original) | Usuario adicional |
| admin (ID 10) | admin123 | Creado en wizard |

### Forma de Acceso
1. Usuario: `admin`
2. Contraseña: `admin123`
3. **Licencia**: `INNO-TEST-0001-EF79`

---

## 🏢 Tenant #2: "empresa prueba" (ID: 7)

### Licencia
```
INNO-TEST-0002-C775
```

### Base de Datos
```
planilla_tenant_c775e7b757
```

### RUC Alternativo
```
1234567890
```

---

## 🏢 Tenant #3: "prueba2" (ID: 8)

### Licencia
```
INNO-TEST-0003-500B
```

### Base de Datos
```
planilla_tenant_500ba32bad
```

### RUC Alternativo
```
098767890
```

---

## 🏢 Tenant #4: "prueba3" (ID: 9) ⭐ PROBADO

### Licencia
```
INNO-TEST-0004-E0D3
```

### Base de Datos
```
planilla_tenant_e0d35ff378
```

### Usuarios Disponibles
| Usuario | Contraseña | Verificado |
|---------|------------|------------|
| admin | (desconocida) | ❌ |
| innova | (desconocida) | ❌ |
| **pruebas3** | **123456** | ✅ **FUNCIONA** |

### Forma de Acceso VERIFICADA ✅
1. Usuario: `pruebas3`
2. Contraseña: `123456`
3. **Licencia**: `INNO-TEST-0004-E0D3`

### RUC Alternativo (también funciona)
```
098765678890
```

---

## 🔍 Formas de Acceso

### Opción 1: Por Licencia (RECOMENDADO) ⭐
```
Usuario: [tu_usuario]
Contraseña: [tu_contraseña]
Licencia: INNO-TEST-XXXX-XXXX
```

### Opción 2: Por RUC (Legacy)
```
Usuario: [tu_usuario]
Contraseña: [tu_contraseña]
Código de Empresa: [RUC de la empresa]
```

### Opción 3: Por ID de Tenant
```
Usuario: [tu_usuario]
Contraseña: [tu_contraseña]
Código de Empresa: [6, 7, 8, o 9]
```

### Opción 4: Sin Código (Base Principal)
```
Usuario: [usuario_de_planilla_prod]
Contraseña: [contraseña]
Licencia: [dejar vacío]
```

---

## 🧪 Testing Completo

### Test 1: Login con Licencia ✅ LISTO PARA PROBAR
```bash
URL: http://localhost/planilla-innova/panel/login
Usuario: pruebas3
Contraseña: 123456
Licencia: INNO-TEST-0004-E0D3

✅ Resultado esperado: Login exitoso → Dashboard tenant "prueba3"
```

### Test 2: Login con RUC (compatibilidad) ✅
```bash
URL: http://localhost/planilla-innova/panel/login
Usuario: pruebas3
Contraseña: 123456
Código de Empresa: 098765678890

✅ Resultado esperado: Login exitoso → Dashboard tenant "prueba3"
```

### Test 3: Crear Nueva Empresa ✅ LISTO PARA PROBAR
```bash
URL: http://localhost/planilla-innova/crear-empresa

Paso 1: Datos Distribuidor
Paso 2: Datos Empresa + Usuario Admin
Paso 3: Confirmación

✅ Resultado esperado:
{
  "success": true,
  "license_key": "INNO-XXXX-XXXX-XXXX",  ← NUEVA LICENCIA GENERADA
  "database_name": "planilla_innoxxxxxxxxxxxx",
  "login_url": "/panel/login"
}
```

---

## 📊 Resumen de Licencias Asignadas

| ID | Empresa | Licencia | BD |
|----|---------|----------|-----|
| 6 | Empresa Test | `INNO-TEST-0001-EF79` | planilla_tenant_ef797c8118 |
| 7 | empresa prueba | `INNO-TEST-0002-C775` | planilla_tenant_c775e7b757 |
| 8 | prueba2 | `INNO-TEST-0003-500B` | planilla_tenant_500ba32bad |
| 9 | prueba3 | `INNO-TEST-0004-E0D3` | planilla_tenant_e0d35ff378 |

**Nota**: Los tenants 6-9 tienen licencias de prueba. Las nuevas empresas creadas tendrán licencias con formato aleatorio real.

---

## ⚡ Próximos Pasos

### 1. Probar Login con Licencia
Usa las credenciales del Tenant #4 (prueba3) que ya están verificadas.

### 2. Crear Nueva Empresa
Usa el wizard para crear una empresa nueva y verificar que:
- Se genera licencia única
- BD se crea con nombre basado en licencia
- Login funciona con la licencia generada

### 3. Verificar Logs
Después de hacer login, verifica en los logs:
```bash
# Windows
tail -f C:/xampp82/php/logs/php_error_log

# Buscar líneas como:
"Tenant resolved by code 'INNO-TEST-0004-E0D3': prueba3 (DB: planilla_tenant_e0d35ff378 | License: INNO-TEST-0004-E0D3)"
```

---

## 🐛 Si algo no funciona

### Licencia no reconocida
```sql
-- Verificar en master DB
USE planilla_master;
SELECT * FROM tenants WHERE license_key = 'INNO-TEST-XXXX-XXXX';
```

### Credenciales incorrectas
```sql
-- Verificar usuarios en tenant DB
USE planilla_tenant_XXXXXXXXXX;
SELECT username, firstname, lastname FROM admin;
```

### Para resetear contraseña de usuario
```sql
-- Generar nuevo hash bcrypt para "admin123"
UPDATE admin
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'pruebas3';
```
