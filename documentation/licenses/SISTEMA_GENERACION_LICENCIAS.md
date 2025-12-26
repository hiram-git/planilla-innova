# 🔐 Sistema de Generación y Registro de Licencias

**Fecha de Implementación**: 24 de Noviembre, 2025
**Versión**: 1.0.0
**Estado**: ✅ Completado

## 📋 Descripción General

Sistema completo para generar licencias únicas y registrarlas automáticamente en el servidor de Innovasoft Latam durante la creación de empresas en el wizard multitenancy.

## 🎯 Funcionalidades Implementadas

### 1. **Servicio de Generación de Licencias** (`LicenseGenerator.php`)

**Ubicación**: `app/Services/LicenseGenerator.php`
**Líneas de código**: 319 líneas

#### Métodos Principales:

1. **`generateUniqueLicense($maxAttempts = 10)`**
   - Genera códigos de licencia únicos
   - Formato: `PREFIX + 10 dígitos aleatorios`
   - Ejemplo: `PINN1234567890`
   - Verifica unicidad contra servidor remoto
   - Retry automático si la licencia ya existe

2. **`checkLicenseExists($license)`**
   - Verifica si una licencia existe en el servidor remoto
   - Endpoint: `https://plataforma.innovasoftlatam.com:8080/ajax/user.php`
   - Método: POST con JSON
   - Payload: `{'searchLicense': 'yes', 'License': 'PINNXXXXXXXX'}`

3. **`registerLicense($licenseData)`**
   - Registra la licencia en el servidor remoto
   - Endpoint: `https://plataforma.innovasoftlatam.com:8080/ajax/license.php`
   - Incluye datos completos de empresa y comprador

4. **`generateAndRegister($companyData)`** ⭐ **Método Principal**
   - Combina generación + registro en una sola operación
   - Calcula automáticamente fechas de activación y expiración
   - Período de prueba: 30 días por defecto
   - Retorna licencia generada + fechas

#### Datos Enviados al Servidor:

```json
{
  "registerLicense": "yes",
  "License": "PINN1234567890",
  "RUC": "1234567890123",
  "Buyer": "Juan Pérez",
  "Company": "Empresa Demo S.A.",
  "Email": "admin@empresa.com",
  "Phone": "+507-1234-5678",
  "Expiration": "2025-12-24",
  "MaxActivations": "50",
  "CurActivations": "1",
  "SaintLicense": "PINN1234567890",
  "State": "XSU_TRIAL",
  "CurActCompleted": "1",
  "UniqueTable": "1",
  "FinalUser": "Empresa Demo S.A.",
  "FirstActivation": "2025-11-24",
  "Country": "Panama",
  "Product": "PLANILLA_INNOVA",
  "Reactivation": null,
  "HasCoronaTest": "1",
  "IdAnalitica": null,
  "FinalUserRUC": null
}
```

### 2. **Integración en WizardController**

**Ubicación**: `app/Controllers/WizardController.php`
**Método modificado**: `createCompany()` (líneas 178-251)

#### Flujo de Creación:

```
1. Iniciar transacción BD
2. ✨ GENERAR Y REGISTRAR LICENCIA (nuevo)
   ├─ Generar licencia única (PINN + 10 dígitos)
   ├─ Validar que no existe en servidor remoto
   ├─ Registrar en servidor de Innovasoft Latam
   └─ Obtener fechas de activación y expiración
3. Crear registro empresa en BD master (con licencia)
4. Generar nombre BD tenant basado en licencia
5. Crear base de datos tenant
6. Importar estructura en BD tenant
7. Configurar datos iniciales empresa
8. Crear usuario administrador
9. Actualizar registro empresa con BD asignada
10. Commit transacción
11. Enviar email de bienvenida (incluye licencia + vencimiento)
```

#### Logging Detallado:

```log
=== LICENCIA GENERADA Y REGISTRADA ===
Licencia: PINN1234567890
Empresa: Empresa Demo S.A.
RUC: 1234567890123
Expiración: 2025-12-24
======================================
```

### 3. **Email de Bienvenida Mejorado**

**Ubicación**: `app/Controllers/WizardController.php`
**Método**: `sendWelcomeEmail()` (líneas 408-543)

#### Información Incluida:

- ✅ Código de Licencia
- ✅ Fecha de Vencimiento (formato DD/MM/YYYY)
- ✅ Nombre de Base de Datos
- ✅ Usuario Administrador
- ✅ URL de acceso al sistema

#### Formato HTML:

```html
Credenciales de Acceso
─────────────────────────
Licencia: PINN1234567890
Vence: 24/12/2025
Base de Datos: planilla_tenant_abc123
Usuario Administrador: admin
```

## ⚙️ Configuración (.env)

**Ubicación**: `.env`
**Sección**: Configuración Wizard Multitenancy

```env
# Configuración Wizard Multitenancy
DISTRIBUTOR_VALIDATION_URL="https://plataforma.innovasoftlatam.com:8080"
LICENSE_VALIDATION_URL="https://plataforma.innovasoftlatam.com:8080/ajax/license.php"
LICENSE_PREFIX="PINN"
LICENSING_BASE_UR="https://plataforma.innovasoftlatam.com:8080"
LICENSING_SSL_VERIF=false
HTTP_CONNECT_TIMEOUT=8
HTTP_TIMEOUT=8
```

### Variables Configurables:

| Variable | Descripción | Default |
|----------|-------------|---------|
| `LICENSE_VALIDATION_URL` | Endpoint de validación/registro | `https://plataforma.innovasoftlatam.com:8080/ajax/license.php` |
| `LICENSE_PREFIX` | Prefijo de licencias | `PINN` (Panama Innova) |
| `HTTP_TIMEOUT` | Timeout de requests (segundos) | `8` |
| `LICENSING_SSL_VERIF` | Verificar certificados SSL | `false` |

## 🔒 Seguridad

### Características de Seguridad:

1. **Validación de Unicidad**
   - Cada licencia se verifica contra servidor remoto antes de usar
   - Retry automático si hay colisión (hasta 10 intentos)

2. **Manejo de Errores**
   - Try-catch completo en todas las operaciones
   - Rollback automático de transacciones en caso de fallo
   - Logging detallado de errores

3. **Timeout y Reintentos**
   - Timeout de 8 segundos por defecto
   - Doble timeout para registro (16 segundos)
   - Manejo graceful de errores de conexión

4. **Validación de Datos**
   - Campos requeridos validados antes de enviar
   - Formato de email validado
   - RUC validado según formato panameño

## 📊 Respuesta del Sistema

### Respuesta Exitosa:

```json
{
  "success": true,
  "message": "Licencia generada y registrada exitosamente",
  "license": "PINN1234567890",
  "expiration_date": "2025-12-24",
  "first_activation": "2025-11-24"
}
```

### Respuesta de Error:

```json
{
  "success": false,
  "message": "Error generando licencia: No se pudo conectar al servidor",
  "license": null
}
```

## 🧪 Testing

### Escenarios de Prueba:

1. **Generación Exitosa**
   - Crear empresa desde wizard
   - Verificar licencia en logs
   - Verificar email recibido con licencia
   - Confirmar registro en servidor remoto

2. **Manejo de Colisiones**
   - Simular licencia duplicada
   - Verificar retry automático
   - Confirmar nueva licencia generada

3. **Manejo de Errores de Conexión**
   - Simular servidor remoto caído
   - Verificar rollback de transacción
   - Verificar mensaje de error al usuario

4. **Validación de Datos**
   - Intentar crear empresa sin RUC
   - Intentar crear empresa sin email
   - Verificar mensajes de validación

## 📝 Logs

### Ubicaciones de Logs:

- **PHP Error Log**: Logs detallados de generación/registro
- **Email Logs**: Confirmación de envío de emails
- **BD Logs**: Transacciones de creación de empresas

### Ejemplo de Logs Exitosos:

```log
=== LICENCIA GENERADA Y REGISTRADA ===
Licencia: PINN8091605110
Empresa: Empresa Demo S.A.
RUC: 1234567890123
Expiración: 2025-12-24
======================================

Licencia única generada: PINN8091605110 (intento 1)
Licencia registrada exitosamente: PINN8091605110

=== EMAIL ENVIADO EXITOSAMENTE ===
Destinatario: admin@empresa.com
Empresa: Empresa Demo S.A.
Licencia: PINN8091605110
Servidor SMTP: sandbox.smtp.mailtrap.io:2525
==================================
```

## 🔗 Integración con Sistema Existente

### Componentes que Interactúan:

1. **LicenseValidator.php** (Validación en login)
   - Valida licencias al iniciar sesión
   - Verifica expiración
   - Almacena en sesión

2. **WizardModel.php** (Gestión de empresas)
   - Crea registros de empresa con licencia
   - Genera nombres de BD basados en licencia
   - Almacena fechas de expiración

3. **Admin.php** (Controller de login)
   - Usa LicenseValidator para verificar acceso
   - Bloquea acceso si licencia expiró

## 🚀 Próximos Pasos (Opcional)

1. **Dashboard de Licencias**
   - Vista para administradores de todas las licencias
   - Alertas de vencimiento próximo
   - Estadísticas de uso

2. **Renovación Automática**
   - Proceso de renovación desde el sistema
   - Notificaciones antes de vencimiento
   - Integración con pagos

3. **Gestión de Planes**
   - Diferentes niveles de licencia (Trial, Basic, Premium)
   - Límites por plan (empleados, usuarios, etc.)
   - Upgrade/downgrade de planes

## 📚 Referencias

- **Legacy Code**: `legacy/registrar_empresa.php` (líneas 217-250: generación)
- **Legacy Code**: `legacy/registrar_empresa.php` (líneas 151-215: registro)
- **API Documentation**: Innovasoft Latam License API
- **Related Services**: `LicenseValidator.php` (validación en login)

---

**Notas Importantes**:
- ✅ Sistema completamente funcional y probado
- ✅ Compatible con arquitectura multitenancy existente
- ✅ Logging completo para debugging
- ✅ Manejo robusto de errores
- ✅ Emails profesionales con toda la información
- ✅ Configuración flexible vía .env
