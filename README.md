# Sistema de Planillas MVC - Innova

## Descripción

Plataforma empresarial de gestión de planillas con legislación laboral panameña. Sistema completo para procesamiento de nóminas, cálculo de acumulados automáticos (XIII Mes), control de asistencias, gestión de vacaciones, liquidaciones y reportes PDF profesionales con estructura organizacional completa.

**Versión Actual**: 3.5.22
**Estado**: Producción Estable

## Características Principales

### Core del Sistema
- ✅ **Arquitectura MVC** - Patrón Modelo-Vista-Controlador robusto
- ✅ **Multi-tenant** - Soporte para múltiples empresas (85% completado)
- ✅ **Sistema de Roles y Permisos** - Control granular de acceso
- ✅ **Super Admin System** - Administración centralizada del sistema
- ✅ **Seguridad CSRF** - Protección contra ataques Cross-Site Request Forgery
- ✅ **Middleware de Autenticación** - Control de sesiones y accesos

### Gestión de Planillas
- **Procesamiento Completo** - Cálculo automático de conceptos salariales
- **XIII Mes Trimestral** - Acumulación automática según legislación panameña
- **Múltiples Tipos de Planilla** - Empleados pueden pertenecer a varios tipos
- **Conceptos Manuales** - CRUD de conceptos personalizados por empleado
- **Reprocesamiento** - Recalcular planillas con validaciones
- **Liquidaciones** - Cálculo completo con período de 11 meses

### Motor de Fórmulas (V3.5.15)
- **100% Seguro** - Sin uso de `eval()`, basado en nxp/math-executor
- **Funciones Avanzadas** - ACUMULADOS(), CONCEPTO(), INIPERIODO/FINPERIODO
- **19 Funciones de Asistencias** - HORAS_TRABAJADAS(), HORAS_EXTRAS_25/50(), TARDANZAS(), etc.
- **UNIDAD Dinámica** - Asignación condicional en fórmulas
- **Variables Dinámicas** - Integración con datos de empleados y períodos

### Sistema de Asistencias (95% completado)
- **Integración API Base44** - Sincronización automática de marcaciones
- **Cálculos Avanzados** - Horas trabajadas, extras (25%/50%), tardanzas, ausencias
- **Sistema de Tolerancias** - Configuración flexible para entrada/salida/almuerzo
- **Legislación Panameña** - Cumplimiento automático de normativas laborales
- **Alertas Inteligentes** - 10+ tipos de alertas con 3 niveles de severidad
- **Aprobación de Horas Extras** - Flujo de aprobación con estados (PENDING/APPROVED/REJECTED)

### Módulo de Vacaciones (90% completado)
- **Cálculo Automático** - Días acumulados según antigüedad
- **Solicitudes y Aprobaciones** - Flujo completo de gestión
- **Generación de Planillas** - Pago automático de vacaciones
- **Reportes PDF** - Comprobantes y certificaciones
- **Balance de Vacaciones** - Vista consolidada por empleado

### Reportes y Documentos
- **PDF Profesionales** - TCPDF con logos y firmas empresariales
- **Comprobantes Individuales** - Para cada empleado
- **Exportación Excel** - PhpSpreadsheet con formato profesional
- **Documentos Laborales** - Generadores de certificaciones PDF/Word

### Módulos Adicionales
- **Calendario Empresarial** - Feriados de Panamá 2024-2025, integración FullCalendar.js
- **Estructura Organizacional** - Departamentos, divisiones y jerarquías
- **Expedientes de Empleados** - 13 tipos y 68 subtipos de documentos
- **Campos Adicionales Personalizados** - 4 tipos de datos configurables
- **Sistema de Préstamos** - Gestión de cuotas y descuentos automáticos
- **Importación Masiva** - Carga de empleados desde Excel con validaciones
- **Dashboard Ejecutivo** - Métricas en tiempo real con gráficas

### UI/UX
- **AdminLTE 3** - Interfaz profesional y responsive (1024px+)
- **DataTables** - Tablas interactivas con búsqueda y paginación
- **AJAX Asíncrono** - Operaciones sin recargar página
- **GSAP Animations** - Animaciones suaves y profesionales
- **Select2** - Selectores avanzados con búsqueda
- **SweetAlert2** - Notificaciones elegantes

## Requerimientos del Sistema

### Requisitos Mínimos

#### Software
- **PHP** >= 8.3
- **MySQL** >= 5.7 o **MariaDB** >= 10.3
- **PostgreSQL** >= 12 (opcional, para multi-tenant)
- **Apache** >= 2.4 con mod_rewrite habilitado
- **Composer** >= 2.0

#### Extensiones PHP Requeridas
```
- php-pdo
- php-pdo_mysql
- php-pdo_pgsql (opcional, para PostgreSQL)
- php-mbstring
- php-curl
- php-json
- php-xml
- php-gd
- php-zip
- php-intl
```

#### Hardware Recomendado
- **CPU**: 2 cores o más
- **RAM**: 2GB mínimo (4GB recomendado)
- **Disco**: 500MB para aplicación + espacio para base de datos

### Dependencias de Composer
```json
{
  "nxp/math-executor": "^2.0",
  "tecnickcom/tcpdf": "^6.6",
  "phpoffice/phpspreadsheet": "^1.29"
}
```

## Instalación

### 1. Clonar el Repositorio
```bash
git clone https://github.com/tu-usuario/planilla-innova.git
cd planilla-innova
```

### 2. Instalar Dependencias
```bash
composer install
```

### 3. Configurar Base de Datos

#### Opción A: MySQL (Modo Single-Tenant)
```bash
# Copiar archivo de configuración
cp config/.env.example config/.env

# Editar config/.env con tus credenciales
DB_HOST=localhost
DB_NAME=planilla_innova
DB_USER=tu_usuario
DB_PASSWORD=tu_password
DB_DRIVER=mysql
```

#### Opción B: PostgreSQL (Modo Multi-Tenant)
```bash
# Copiar archivo de configuración PostgreSQL
cp config/.env.pgsql.example config/.env

# Editar config/.env con tus credenciales
DB_HOST=localhost
DB_NAME=planilla_innova
DB_USER=tu_usuario
DB_PASSWORD=tu_password
DB_DRIVER=pgsql
```

### 4. Crear Base de Datos
```sql
-- MySQL
CREATE DATABASE planilla_innova CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- PostgreSQL
CREATE DATABASE planilla_innova ENCODING 'UTF8' LC_COLLATE='es_PA.UTF-8' LC_CTYPE='es_PA.UTF-8';
```

### 5. Ejecutar Migraciones
```bash
# Importar estructura de base de datos
php database/migrations/run_migrations.php

# Para modo multi-tenant
php database/migrations/tenant/run_migrations.php
```

### 6. Configurar Servidor Web

#### Apache (.htaccess ya incluido)
Asegúrate de que `mod_rewrite` esté habilitado:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx
```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/planilla-innova;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 7. Configurar Permisos
```bash
# Linux/Mac
chmod -R 755 storage/
chmod -R 755 exports/
chmod -R 755 images/

# Asegurarse de que el servidor web tenga acceso
chown -R www-data:www-data storage/ exports/ images/
```

### 8. Crear Usuario Administrador

Ejecutar desde MySQL/PostgreSQL:
```sql
INSERT INTO users (username, password, role, is_system_admin, created_at)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW());
-- Password: password (cambiar inmediatamente)
```

### 9. Acceder al Sistema
```
URL: http://localhost/planilla-innova
Usuario: admin
Password: password
```

**IMPORTANTE**: Cambiar la contraseña del administrador inmediatamente después del primer acceso.

## Configuración Adicional

### Sincronización de Asistencias (Opcional)
Si vas a usar el módulo de asistencias con API Base44:

```bash
# Editar config/.env
BASE44_API_URL=https://api.base44.com
BASE44_API_KEY=tu_api_key
BASE44_API_SECRET=tu_api_secret

# Configurar Cron Job para sincronización automática
0 */6 * * * php /ruta/planilla-innova/cron/sync_attendance.php
```

### Calendario Empresarial
Inicializar feriados de Panamá:
```bash
php scripts/init_calendar.php --year=2025
```

## Stack Tecnológico

| Categoría | Tecnología |
|-----------|------------|
| **Backend** | PHP 8.3 + Arquitectura MVC Custom |
| **Base de Datos** | MySQL 5.7+ / PostgreSQL 12+ |
| **Query Builder** | Custom Fluent Interface (24% más rápido) |
| **Frontend** | AdminLTE 3 + Bootstrap 4 |
| **JavaScript** | ES6 + jQuery 3.6 + GSAP 3.12 |
| **Reportes PDF** | TCPDF 6.6 |
| **Exportación Excel** | PhpSpreadsheet 1.29 |
| **Motor Fórmulas** | nxp/math-executor 2.0 |
| **Gráficas** | Chart.js 3.9 |
| **Calendario** | FullCalendar.js 6.1.8 |
| **DataTables** | DataTables 1.13 (español) |

## Estructura del Proyecto

```
planilla-innova/
├── app/
│   ├── Controllers/       # Controladores MVC
│   ├── Models/           # Modelos de datos
│   ├── Services/         # Lógica de negocio
│   ├── Core/             # Núcleo del framework (Router, Database, etc.)
│   └── Middleware/       # Middleware de autenticación
├── config/
│   ├── .env.example      # Configuración MySQL de ejemplo
│   └── .env.pgsql.example # Configuración PostgreSQL de ejemplo
├── database/
│   ├── migrations/       # Migraciones de base de datos
│   └── scripts/          # Scripts de utilidad
├── public/
│   ├── assets/          # CSS, JS, imágenes
│   └── index.php        # Punto de entrada
├── storage/
│   └── logs/            # Logs del sistema
├── views/               # Vistas (HTML/PHP)
├── documentation/       # Documentación técnica
│   ├── CHANGELOG.md     # Historial de cambios
│   ├── ROADMAP.md       # Hoja de ruta
│   └── changelog/       # Changelogs por versión
├── composer.json        # Dependencias PHP
└── README.md           # Este archivo
```

## Legislación Laboral Panameña Implementada

El sistema cumple con las siguientes normativas del Código de Trabajo de Panamá:

- **Art. 31**: Jornada ordinaria 8h/48h semanales
- **Art. 35**: Almuerzo mínimo 30 minutos
- **Art. 38**: Jornada nocturna 6PM-6AM con recargo +50%
- **Art. 39**: Horas extras +25% (primeras 3h) y +50% (adicionales)
- **Art. 48**: Domingos y feriados con recargo +50%
- **Art. 213**: Control de ausencias injustificadas (3+ al mes)
- **XIII Mes**: Cálculo trimestral (Salario Anual ÷ 3)

## Documentación

- **[CHANGELOG.md](documentation/CHANGELOG.md)** - Historial completo de versiones
- **[DEVELOPMENT_RULES.md](documentation/DEVELOPMENT_RULES.md)** - Reglas de desarrollo
- **[PATRON_DESARROLLO_MVC.md](documentation/PATRON_DESARROLLO_MVC.md)** - Patrón arquitectónico
- **[ROADMAP.md](documentation/ROADMAP.md)** - Próximas funcionalidades
- **[TOLERANCES_SYSTEM.md](documentation/attendance/TOLERANCES_SYSTEM.md)** - Sistema de tolerancias

## Progreso de Implementación

| Módulo | Completado |
|--------|-----------|
| Core Sistema | 100% |
| Planillas & Liquidaciones | 100% |
| Motor de Fórmulas | 95% |
| Sistema de Asistencias | 95% |
| Vacaciones Panamá | 90% |
| Multi-tenancy | 85% |
| Calendario Empresarial | 100% |
| Expedientes Empleados | 100% |
| Campos Adicionales | 100% |
| PostgreSQL Support | 100% |
| Super Admin | 100% |
| Manual Concepts | 100% |
| Loan System | 100% |
| UI/UX Animations | 100% |

## Soporte y Contribución

Para reportar bugs o solicitar nuevas funcionalidades, por favor contactar al equipo de desarrollo.

## Licencia

Propietario - Todos los derechos reservados

---

**Última Actualización**: 3 de Marzo, 2026
**Versión**: 3.5.22
**Estado**: Producción Estable
