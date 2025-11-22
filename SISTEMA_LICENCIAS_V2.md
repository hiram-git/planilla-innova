# 🔑 Sistema de Licencias v2 - Formato PINN

## ✅ Formato Actualizado

### Licencia
```
PINN1234567890
```
- **Prefijo**: `PINN` (Planilla Innova)
- **Dígitos**: 10 números aleatorios
- **Total**: 14 caracteres

### Base de Datos
```
PINN1234567890
```
- **Nombre de BD**: EXACTAMENTE igual a la licencia
- **Sin prefijos ni sufijos adicionales**

---

## 🔄 Flujo de Creación de Empresa

```
1. Generar licencia única
   └─> PINN3847562901

2. Crear registro en master.tenants
   └─> license_key = "PINN3847562901"

3. Crear base de datos
   └─> CREATE DATABASE `PINN3847562901`;

4. Importar schema completo
   └─> Tablas, triggers, procedures

5. Crear usuario admin
   └─> INSERT INTO PINN3847562901.admin

6. Actualizar registro master
   └─> db_name = "PINN3847562901"
```

---

## 📊 Ejemplos

### Ejemplo 1: Nueva Empresa
```json
{
  "company_name": "Mi Empresa S.A.",
  "ruc": "12345678-1-123456",
  "license_key": "PINN5739284061",
  "database_name": "PINN5739284061"
}
```

### Ejemplo 2: Estructura en MySQL
```sql
-- Base de datos master
USE planilla_master;
SELECT * FROM tenants WHERE license_key = 'PINN5739284061';
+----+----------------+-------------------+----------------+----------------+
| id | company_name   | ruc               | license_key    | db_name        |
+----+----------------+-------------------+----------------+----------------+
| 10 | Mi Empresa S.A.| 12345678-1-123456 | PINN5739284061 | PINN5739284061 |
+----+----------------+-------------------+----------------+----------------+

-- Base de datos tenant
SHOW DATABASES LIKE 'PINN%';
+-------------------------+
| Database                |
+-------------------------+
| PINN5739284061          |
+-------------------------+

-- Usuarios del tenant
USE PINN5739284061;
SELECT * FROM admin;
```

---

## 🧪 Tenants Existentes Actualizados

| ID | Empresa | Licencia Nueva | BD Actual | Usuario | Contraseña |
|----|---------|----------------|-----------|---------|------------|
| 6 | Empresa Test | `PINN0000000001` | planilla_tenant_ef797c8118 | admin | admin123 |
| 7 | empresa prueba | `PINN0000000002` | planilla_tenant_c775e7b757 | - | - |
| 8 | prueba2 | `PINN0000000003` | planilla_tenant_500ba32bad | - | - |
| 9 | prueba3 | `PINN0000000004` | planilla_tenant_e0d35ff378 | pruebas3 | 123456 |

**⚠️ Nota**: Los tenants existentes tienen licencias actualizadas pero la BD mantiene el nombre antiguo por compatibilidad.

---

## 🔐 Login con Licencia

### Formulario de Login
```
URL: http://localhost/planilla-innova/panel/login

┌─────────────────────────────────────┐
│ Usuario:    [admin              ]   │
│ Contraseña: [********            ]   │
│ Licencia:   [PINN0000000004     ]   │ ← Formato nuevo
│                                      │
│ ℹ️ Ingresa tu código de licencia    │
│   (formato: PINN1234567890)         │
│                                      │
│             [Entrar]                 │
└─────────────────────────────────────┘
```

### Prueba con Tenant Existente
```
Usuario: pruebas3
Contraseña: 123456
Licencia: PINN0000000004

✅ Resultado: Login exitoso → Dashboard tenant "prueba3"
```

---

## 📝 Crear Nueva Empresa

### Paso 1: Wizard
```
URL: http://localhost/planilla-innova/crear-empresa

Datos:
- Empresa: "Nueva Empresa Test"
- RUC: "555444333"
- Usuario Admin: "admin"
- Contraseña: "admin123"
```

### Paso 2: Respuesta del Sistema
```json
{
  "success": true,
  "license_key": "PINN8392746105",        ← Generada automáticamente
  "database_name": "PINN8392746105",      ← Mismo nombre
  "company_id": 10,
  "login_url": "/panel/login"
}
```

### Paso 3: Verificar en Base de Datos
```sql
-- Verificar tenant en master
USE planilla_master;
SELECT id, company_name, license_key, db_name
FROM tenants
WHERE license_key = 'PINN8392746105';

-- Verificar BD creada
SHOW DATABASES LIKE 'PINN8392746105';

-- Verificar usuario admin
USE PINN8392746105;
SELECT username, firstname, lastname FROM admin;
```

### Paso 4: Login
```
URL: http://localhost/planilla-innova/panel/login

Usuario: admin
Contraseña: admin123
Licencia: PINN8392746105

✅ Acceso al dashboard de la nueva empresa
```

---

## 🔍 Búsqueda de Tenant en Login

### Prioridad de Búsqueda
Cuando ingresas un código en el campo "Licencia", el sistema busca en este orden:

1. **Por License Key** (prioridad 1) ⭐
   ```sql
   WHERE license_key = 'PINN8392746105'
   ```

2. **Por RUC** (prioridad 2 - retrocompatibilidad)
   ```sql
   WHERE ruc = '12345678-1-123456'
   ```

3. **Por ID** (prioridad 3)
   ```sql
   WHERE id = 10
   ```

4. **Por Slug** (prioridad 4)
   ```sql
   WHERE slug = 'nueva-empresa-test'
   ```

### Ejemplos de Códigos Válidos
```
PINN8392746105    → Busca por licencia ✅
12345678-1-123456 → Busca por RUC ✅
10                → Busca por ID ✅
nueva-empresa     → Busca por slug ✅
```

---

## 💻 Implementación Técnica

### Generación de Licencia
```php
// app/Models/WizardModel.php líneas 251-265
public function generateUniqueLicense(): string
{
    do {
        // Generar 10 dígitos aleatorios
        $randomDigits = '';
        for ($i = 0; $i < 10; $i++) {
            $randomDigits .= random_int(0, 9);
        }

        $license = "PINN" . $randomDigits;

        // Verificar unicidad
    } while ($this->licenseExists($license));

    return $license;
}
```

### Nombre de Base de Datos
```php
// app/Models/WizardModel.php líneas 143-147
public function generateTenantDatabaseName(string $license): string
{
    // El nombre es EXACTAMENTE igual a la licencia
    return $license;
}
```

### Flujo en WizardController
```php
// app/Controllers/WizardController.php líneas 179-187
// 1. Generar licencia
$license = $this->wizardModel->generateUniqueLicense();
// Resultado: "PINN8392746105"

// 2. Nombre de BD = Licencia
$databaseName = $this->wizardModel->generateTenantDatabaseName($license);
// Resultado: "PINN8392746105"

// 3. Crear BD
$this->wizardModel->createTenantDatabase($databaseName);
// Ejecuta: CREATE DATABASE `PINN8392746105`;
```

---

## 📊 Ventajas del Formato PINN

### ✅ Simplicidad
- Formato corto y simple
- Fácil de comunicar por teléfono
- Solo caracteres alfanuméricos (sin guiones)

### ✅ Identificación Clara
- Prefijo `PINN` identifica el sistema
- 10 dígitos = ~10 mil millones de combinaciones
- Imposible de adivinar

### ✅ Compatibilidad MySQL
- Nombres de BD válidos sin modificaciones
- Sin caracteres especiales
- Sin conversiones ni limpieza

### ✅ Administración Simple
- Ver nombre de BD = Ver licencia
- Fácil debugging
- Migración simple entre servidores

---

## 🔄 Migración de Tenants Antiguos

### Opción 1: Mantener BD Antigua
```sql
-- Actualizar solo la licencia
UPDATE tenants
SET license_key = 'PINN0000000001'
WHERE id = 6;

-- BD sigue siendo: planilla_tenant_ef797c8118
-- Login funciona con: PINN0000000001
```

### Opción 2: Renombrar BD (Avanzado)
```sql
-- 1. Crear nueva BD con nombre de licencia
CREATE DATABASE PINN0000000001;

-- 2. Copiar todas las tablas
-- (Usar mysqldump o script de migración)

-- 3. Actualizar registro master
UPDATE tenants
SET license_key = 'PINN0000000001',
    db_name = 'PINN0000000001'
WHERE id = 6;

-- 4. Eliminar BD antigua
DROP DATABASE planilla_tenant_ef797c8118;
```

---

## 🧪 Testing Completo

### Test 1: Generar Licencia
```php
php -r "
for (\$i = 0; \$i < 10; \$i++) {
    \$digits = '';
    for (\$j = 0; \$j < 10; \$j++) {
        \$digits .= random_int(0, 9);
    }
    echo 'PINN' . \$digits . PHP_EOL;
}
"

Resultado esperado:
PINN3847562901
PINN9182736450
PINN5029384761
...
```

### Test 2: Crear Empresa Completa
```bash
1. Ir a: http://localhost/planilla-innova/crear-empresa
2. Completar wizard
3. Verificar respuesta incluye:
   - license_key: "PINN..."
   - database_name: (igual a license_key)
4. Verificar BD creada:
   SHOW DATABASES LIKE 'PINN%';
```

### Test 3: Login con Nueva Licencia
```bash
1. Usar licencia recibida en paso 2
2. Login exitoso
3. Dashboard carga correctamente
4. Datos del tenant correctos
```

---

## 📋 Checklist de Implementación

- [x] Formato de licencia: PINN + 10 dígitos
- [x] Nombre de BD = Licencia
- [x] Generación única verificada
- [x] Login por licencia funcional
- [x] Actualización tenants existentes
- [x] Formulario login actualizado
- [x] Documentación completa
- [ ] Prueba creación empresa nueva
- [ ] Verificación login con licencia nueva
- [ ] Migración tenants antiguos (opcional)

---

## 🚀 Próximos Pasos

1. **Crear empresa de prueba** usando el wizard
2. **Verificar licencia generada** formato PINN
3. **Confirmar BD creada** con nombre = licencia
4. **Probar login** con nueva licencia
5. **Documentar** credenciales para referencia

---

## 📞 Soporte

### Verificar Licencia
```sql
SELECT * FROM planilla_master.tenants
WHERE license_key = 'PINN1234567890';
```

### Verificar BD
```sql
SHOW DATABASES LIKE 'PINN1234567890';
```

### Verificar Usuarios
```sql
USE PINN1234567890;
SELECT * FROM admin;
```
