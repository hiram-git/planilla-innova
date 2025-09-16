# 🧙‍♂️ WIZARD MULTITENANCY - Sistema de Planillas MVC

## 📋 **Descripción**

Sistema wizard de 3 pasos para configuración inicial de empresas en arquitectura **multitenancy**. Permite crear empresas con bases de datos separadas de forma automática, siendo la base para una **plataforma SaaS escalable**.

---

## 🚀 **Características Principales**

### **🏢 Multitenancy Database-per-Tenant**
- **Base de datos separada** por cada empresa
- **Aislamiento completo** de datos entre empresas  
- **Escalabilidad** independiente por tenant
- **Backup/restore** individual por empresa

### **🎯 Wizard Sin Autenticación**
- **Acceso directo** sin login previo
- **3 pasos intuitivos** con validación progresiva
- **Interfaz AdminLTE** moderna y responsive
- **Validaciones en tiempo real** JavaScript + PHP

### **⚙️ Creación Automática**
- **BD tenant** con estructura completa
- **Usuario administrador** empresa configurado
- **Datos iniciales** (monedas, tipos planilla, roles)
- **Configuración empresa** lista para usar

---

## 📁 **Estructura de Archivos**

```
app/
├── Controllers/
│   └── WizardController.php          # Controlador principal wizard
├── Models/
│   └── WizardModel.php              # Modelo multitenancy + BD management
├── Views/
│   └── wizard/
│       └── setup.php                # Vista wizard 3 pasos
├── Config/
│   └── WizardConfig.php             # Configuraciones centralizadas
└── Core/
    └── App.php                      # Router + handleWizardRoutes()

database/
└── multitenancy_schema.sql          # Esquema BD master + tenant

WIZARD_MULTITENANCY.md               # Esta documentación
```

---

## 🛠️ **Instalación y Configuración**

### **1. Importar Esquema Base**
```sql
-- Ejecutar en BD master (planilla_innova)
source database/multitenancy_schema.sql
```

### **2. Configurar Administradores Plataforma**
Editar `app/Config/WizardConfig.php`:
```php
'admin' => [
    'password_hash' => '$2y$10$...', // Cambiar hash
    'email' => 'admin@tudominio.com'
],
```

### **3. Verificar .env**
```env
DB_HOST=localhost
DB_USERNAME=root
DB_PASSWORD=tu_password
DB_DATABASE=planilla_innova
```

### **4. Configurar Permisos**
```bash
# Directorio de logs escribible
chmod 755 /ruta/logs/
```

---

## 📋 **URLs del Sistema**

### **🎯 Wizard Principal**
- **`/setup/wizard`** - Mostrar wizard inicial

### **🔄 AJAX Endpoints** 
- **`POST /setup/wizard/validate-admin`** - Validar credenciales admin
- **`POST /setup/wizard/register-company`** - Registrar empresa 
- **`POST /setup/wizard/create-company`** - Crear BD + setup
- **`GET /setup/wizard/progress`** - Estado actual wizard
- **`POST /setup/wizard/reset`** - Reiniciar wizard

---

## 🎯 **Flujo del Wizard**

### **📝 Paso 1: Validación Admin**
```php
// Input requerido
{
    "admin_username": "admin",
    "admin_password": "password123"
}

// Response exitoso
{
    "success": true,
    "message": "Credenciales válidas",
    "next_step": 2
}
```

### **🏢 Paso 2: Registro Empresa**
```php
// Input requerido
{
    "company_name": "Mi Empresa S.A.",
    "company_ruc": "12345678901",
    "admin_username": "admin_empresa",
    "admin_email": "admin@empresa.com", 
    "admin_password": "password123",
    "admin_firstname": "Juan",
    "admin_lastname": "Pérez"
}

// Response exitoso
{
    "success": true,
    "next_step": 3,
    "company_data": {
        "company_name": "Mi Empresa S.A.",
        "ruc": "12345678901",
        "admin_name": "Juan Pérez",
        "admin_email": "admin@empresa.com"
    }
}
```

### **✅ Paso 3: Creación Automática**
```php
// Response exitoso
{
    "success": true,
    "message": "Empresa creada exitosamente",
    "company_id": 1,
    "database_name": "planilla_empresa_12345678901",
    "admin_user_id": 1,
    "login_url": "/panel/login?tenant=planilla_empresa_12345678901"
}
```

---

## 🗄️ **Estructura Base de Datos**

### **🎯 BD Master (planilla_innova)**
```sql
-- Registro de empresas
multitenancy_companies
├── id, company_name, ruc
├── admin_email, tenant_database
└── status, created_at, updated_at

-- Configuración por tenant  
tenant_configurations
├── company_id, database_name
├── max_employees, max_storage_mb
└── features_enabled, backup_schedule

-- Logs del wizard
wizard_logs
├── company_id, step, action
├── data, status, error_message
└── execution_time_ms, created_at
```

### **🏢 BD Tenant (planilla_empresa_XXXXX)**
```sql
-- Estructura completa copiada:
companies, users, roles, employees, 
positions, concepts, creditors, deductions,
payrolls, payroll_details, tipos_planilla,
frecuencias, situaciones, etc.

-- Datos iniciales insertados:
- Roles: Super Admin, Admin, Operador
- Monedas: GTQ, USD  
- Tipos planilla: Quincenal, Mensual, Semanal
- Usuario administrador empresa
```

---

## ⚙️ **Configuraciones Técnicas**

### **🔑 Credenciales Admin Plataforma**
Por defecto en `WizardConfig.php`:
- **Usuario**: `admin` / **Password**: `password`
- **Usuario**: `superadmin` / **Password**: `password` 
- **Usuario**: `wizard` / **Password**: `secret`

### **📊 Límites por Defecto**
- **Empleados máximo**: 100 por empresa
- **Almacenamiento**: 1GB por empresa
- **Respaldos**: Semanal automático
- **Retención logs**: 90 días

### **🛡️ Validaciones**
- **RUC**: 8-12 dígitos únicos
- **Empresa**: 3-255 caracteres
- **Usuario**: Alfanumérico + underscore
- **Email**: Formato válido
- **Password**: Mínimo 6 caracteres

---

## 🎨 **Interfaz de Usuario**

### **📱 Responsive Design**
- **AdminLTE 3.x** + Bootstrap 4
- **Progress stepper** visual
- **Loading overlays** con mensajes
- **Toast notifications** para feedback
- **Validación en tiempo real** 

### **🌈 Características UI**
- **Gradientes modernos** en header
- **Cards con sombras** para pasos
- **Iconografía FontAwesome** consistente
- **Animaciones suaves** entre pasos
- **Confirmación visual** datos empresa

---

## 🔧 **Desarrollo y Extensiones**

### **🛠️ Agregar Validaciones**
```php
// En WizardConfig.php
'nuevo_campo' => [
    'required' => true,
    'min_length' => 5,
    'pattern' => '/^[A-Z0-9]+$/'
]
```

### **📊 Logging Personalizado**
```php
// En WizardModel.php
$this->logWizardActivity($companyId, 'custom_step', [
    'action' => 'custom_action',
    'data' => $customData,
    'status' => 'success'
]);
```

### **🎯 Hooks Post-Creación**
```php
// En WizardController.php después de crear empresa
$this->executePostCreationHooks($companyData, $databaseName);

private function executePostCreationHooks($companyData, $databaseName) {
    // Email bienvenida
    $this->sendWelcomeEmail($companyData, $databaseName);
    
    // Notificaciones Slack/Discord
    $this->sendSlackNotification($companyData);
    
    // Métricas/Analytics
    $this->trackCompanyCreation($companyData);
}
```

---

## 🚀 **Roadmap Futuro**

### **v1.1 - Mejoras UX**
- [ ] Preview de empresa antes de crear
- [ ] Wizard modo oscuro
- [ ] Selección de plantilla/industria
- [ ] Import CSV datos iniciales

### **v1.2 - Automatización**
- [ ] Email templates personalizables
- [ ] Setup automático subdominios
- [ ] Integración DNS automática
- [ ] Certificados SSL automáticos

### **v2.0 - Plataforma SaaS**
- [ ] Panel admin multi-empresa
- [ ] Billing automático
- [ ] Métricas cross-tenant
- [ ] API gestión empresas

---

## 🐛 **Troubleshooting**

### **❌ Error: "Database creation failed"**
```sql
-- Verificar permisos usuario BD
GRANT ALL PRIVILEGES ON *.* TO 'usuario'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

### **❌ Error: "Table already exists"**
```php
// En WizardModel.php cambiar CREATE TABLE por:
CREATE TABLE IF NOT EXISTS `tabla_name` (...)
```

### **❌ Error: "Wizard session invalid"**
```php
// Reiniciar wizard
POST /setup/wizard/reset
```

### **📋 Logs de Debugging**
```php
// Ver logs PHP
tail -f /ruta/logs/php_error.log

// Ver logs wizard en BD
SELECT * FROM wizard_logs ORDER BY created_at DESC LIMIT 10;
```

---

## ✅ **Testing**

### **🧪 Test Manual Completo**
1. Visitar: `http://localhost/planilla-claude-v2/setup/wizard`
2. **Paso 1**: admin / password
3. **Paso 2**: Empresa Test / RUC 123456789 / Admin datos
4. **Paso 3**: Confirmar creación
5. Verificar: BD `planilla_empresa_123456789` creada
6. Login: `/panel/login?tenant=planilla_empresa_123456789`

### **🔍 Validación Automática**
```php
// Ejecutar validation script
php validation/wizard_test.php
```

---

**🎯 WIZARD MULTITENANCY - BASE PARA PLATAFORMA SAAS ESCALABLE**

*Sistema implementado: ✅ Funcional | Documentación: ✅ Completa | Testing: ✅ Manual validado*