# 🔑 Credenciales de Acceso - Formato PINN

## 🌐 URL de Login
```
http://localhost/planilla-innova/panel/login
```

---

## 🏢 Tenant #1: "Empresa Test" (ID: 6)

### Licencia Nueva
```
PINN0000000001
```

### Base de Datos Actual
```
planilla_tenant_ef797c8118
```
**Nota**: BD mantiene nombre antiguo por compatibilidad

### Acceso
| Campo | Valor |
|-------|-------|
| Usuario | `admin` |
| Contraseña | `admin123` |
| Licencia | `PINN0000000001` |

---

## 🏢 Tenant #2: "empresa prueba" (ID: 7)

### Licencia Nueva
```
PINN0000000002
```

### Base de Datos Actual
```
planilla_tenant_c775e7b757
```

### RUC (Alternativo)
```
1234567890
```

---

## 🏢 Tenant #3: "prueba2" (ID: 8)

### Licencia Nueva
```
PINN0000000003
```

### Base de Datos Actual
```
planilla_tenant_500ba32bad
```

### RUC (Alternativo)
```
098767890
```

---

## 🏢 Tenant #4: "prueba3" (ID: 9) ⭐ VERIFICADO

### Licencia Nueva
```
PINN0000000004
```

### Base de Datos Actual
```
planilla_tenant_e0d35ff378
```

### Acceso Verificado ✅
| Campo | Valor |
|-------|-------|
| Usuario | `pruebas3` |
| Contraseña | `123456` |
| Licencia | `PINN0000000004` |

### RUC (Alternativo)
```
098765678890
```

---

## 🧪 Prueba Recomendada

### Con Tenant Existente
```
URL: http://localhost/planilla-innova/panel/login

Formulario:
┌──────────────────────────────┐
│ Usuario:    pruebas3         │
│ Contraseña: 123456           │
│ Licencia:   PINN0000000004   │
│                              │
│         [Entrar]             │
└──────────────────────────────┘

✅ Resultado esperado:
- Login exitoso
- Dashboard del tenant "prueba3"
- BD conectada: planilla_tenant_e0d35ff378
```

### Crear Nueva Empresa (RECOMENDADO)
```
URL: http://localhost/planilla-innova/crear-empresa

Datos sugeridos:
- Empresa: "Test Licencias PINN"
- RUC: "999888777666"
- Usuario: "admin"
- Email: "admin@test.com"
- Contraseña: "admin123"

Resultado esperado:
{
  "license_key": "PINN1234567890",     ← Generada aleatoriamente
  "database_name": "PINN1234567890",   ← Mismo nombre
  "company_id": 10
}

Luego hacer login con:
- Usuario: admin
- Contraseña: admin123
- Licencia: PINN1234567890 (la que se generó)

✅ Debería crear la BD "PINN1234567890" y permitir acceso
```

---

## 🔍 Verificación en Base de Datos

### Ver Todos los Tenants
```sql
USE planilla_master;

SELECT
    id,
    company_name,
    license_key AS 'Licencia Nueva',
    db_name AS 'Base de Datos',
    status
FROM tenants
ORDER BY id;
```

**Resultado Actual**:
```
+----+----------------+------------------+----------------------------+--------+
| id | company_name   | Licencia Nueva   | Base de Datos              | status |
+----+----------------+------------------+----------------------------+--------+
|  6 | Empresa Test   | PINN0000000001   | planilla_tenant_ef797c8118 | ACTIVE |
|  7 | empresa prueba | PINN0000000002   | planilla_tenant_c775e7b757 | ACTIVE |
|  8 | prueba2        | PINN0000000003   | planilla_tenant_500ba32bad | ACTIVE |
|  9 | prueba3        | PINN0000000004   | planilla_tenant_e0d35ff378 | ACTIVE |
+----+----------------+------------------+----------------------------+--------+
```

### Verificar BD de Nuevo Tenant
```sql
-- Después de crear empresa nueva
SHOW DATABASES LIKE 'PINN%';

-- Ejemplo esperado:
+-------------------+
| Database          |
+-------------------+
| PINN8392746105    |  ← Nueva empresa
+-------------------+
```

### Verificar Usuarios en Nuevo Tenant
```sql
USE PINN8392746105;

SELECT
    id,
    username,
    firstname,
    lastname,
    role_id,
    status
FROM admin;
```

---

## 📊 Comparación Formato Antiguo vs Nuevo

### Formato Antiguo (Ya no se usa)
```
Licencia:  INNO-TEST-0001-EF79
BD:        planilla_tenant_ef797c8118
Login:     INNO-TEST-0001-EF79
```

### Formato Nuevo (Actual)
```
Licencia:  PINN0000000001
BD:        PINN0000000001  ← Mismo nombre
Login:     PINN0000000001
```

### Tenants Existentes (Transición)
```
Licencia:  PINN0000000001       ← Actualizada
BD:        planilla_tenant_... ← Sin cambiar (compatibilidad)
Login:     PINN0000000001       ← Funciona con licencia nueva
```

### Nuevos Tenants (A partir de ahora)
```
Licencia:  PINN8392746105       ← Generada automáticamente
BD:        PINN8392746105       ← Mismo nombre
Login:     PINN8392746105
```

---

## ⚡ Ventajas del Nuevo Sistema

### Para Usuarios
- ✅ Código más corto y fácil de recordar
- ✅ Sin guiones (más simple de escribir)
- ✅ Formato consistente siempre

### Para Administradores
- ✅ Ver nombre de BD = Ver licencia
- ✅ Búsqueda rápida en MySQL
- ✅ Debugging simplificado

### Para Desarrolladores
- ✅ Sin conversiones de nombre
- ✅ Código más limpio
- ✅ Menos validaciones necesarias

---

## 🔄 Formas de Acceso

### 1. Por Licencia Nueva (Recomendado) ⭐
```
Campo Licencia: PINN0000000004
✅ Busca en: tenants.license_key
```

### 2. Por RUC (Retrocompatibilidad)
```
Campo Licencia: 098765678890
✅ Busca en: tenants.ruc
```

### 3. Por ID de Tenant
```
Campo Licencia: 9
✅ Busca en: tenants.id
```

### 4. Sin Código (Base Principal)
```
Campo Licencia: [vacío]
✅ Conecta a: planilla_prod
```

---

## 🐛 Troubleshooting

### "Código de empresa no válido"
**Solución**: Verificar licencia en master
```sql
SELECT * FROM planilla_master.tenants
WHERE license_key = 'PINN0000000004';
```

### "Credenciales incorrectas" (con licencia válida)
**Solución**: Verificar usuario en BD tenant
```sql
USE planilla_tenant_e0d35ff378;
SELECT * FROM admin WHERE username = 'pruebas3';
```

### Licencia no encuentra tenant
**Causa**: Formato incorrecto
**Formato correcto**: `PINN` + 10 dígitos
```
✅ PINN0000000004
✅ PINN8392746105
❌ PINN-0000-0001 (con guiones)
❌ pinn0000000001 (minúsculas)
❌ PIN0000000001  (falta una N)
```

---

## 🎯 Siguiente Paso: Crear Nueva Empresa

**RECOMENDACIÓN**: Crear una empresa nueva usando el wizard para ver el sistema completo en acción:

1. ✅ Licencia se genera con formato `PINN1234567890`
2. ✅ BD se crea con nombre `PINN1234567890`
3. ✅ Login funciona con la licencia generada
4. ✅ Todo el flujo multitenancy operativo

**URL**: http://localhost/planilla-innova/crear-empresa
