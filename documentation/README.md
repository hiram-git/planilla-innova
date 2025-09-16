# 🏢 Planilla Simple - Sistema de Gestión de Nómina y Asistencia

[![Version](https://img.shields.io/badge/version-2.4.0-blue.svg)](https://github.com/tu-usuario/planilla-simple)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Sistema integral de gestión de planillas, control de asistencia y administración de personal desarrollado con arquitectura MVC en PHP 8.

## 📋 Tabla de Contenidos

- [Características](#características)
- [Requisitos del Sistema](#requisitos-del-sistema)
- [Instalación Local (XAMPP)](#instalación-local-xampp)
- [Deployment en Producción](#deployment-en-producción)
- [Configuración](#configuración)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Uso](#uso)
- [Troubleshooting](#troubleshooting)

## ✨ Características

### 🎯 Funcionalidades Core
- ✅ **Control de Asistencia** - Sistema de marcaciones en tiempo real
- ✅ **Gestión de Empleados** - CRUD completo de personal
- ✅ **Estructura Organizacional** - Posiciones, cargos, partidas y funciones
- ✅ **Reportes Avanzados** - Analytics y exportación de datos
- ✅ **Dashboard Ejecutivo** - Métricas y gráficas en tiempo real
- ✅ **Generación de Planillas** - Cálculo automático de nómina *(NUEVO v2.4)*
- ✅ **Sistema de Conceptos** - Editor de fórmulas avanzado *(NUEVO v2.4)*
- 🚧 **Asignaciones por Empleado** - Personalización de conceptos
- 🚧 **Sistema de Usuarios** - Roles y permisos granulares

### 🔧 Tecnológicas
- **Arquitectura MVC** - Código limpio y mantenible
- **PHP 8.0+** - Características modernas del lenguaje
- **MySQL 8.0+** - Base de datos robusta y escalable
- **AdminLTE 3** - Interfaz moderna y responsive
- **Chart.js** - Gráficas interactivas
- **Security First** - CSRF, validaciones y sanitización

---

## 🖥️ Requisitos del Sistema

### Requisitos Mínimos
```
PHP: 8.0+
MySQL: 8.0+
Apache: 2.4+
Memoria: 512MB RAM
Disco: 1GB libre
```

### Extensiones PHP Requeridas
```
✅ mysqli
✅ pdo_mysql
✅ mbstring
✅ openssl
✅ fileinfo
✅ gd (opcional)
```

---

## 🚀 Instalación Local (XAMPP)

### 1. Preparar el Entorno
```bash
# Descargar e instalar XAMPP 8.0+
# https://www.apachefriends.org/

# Iniciar servicios desde Panel de Control XAMPP
- Apache ✅
- MySQL ✅
```

### 2. Obtener el Proyecto
```bash
# Clonar o descargar en:
C:\xampp\htdocs\planilla-claude-v2\
```

### 3. Instalar Dependencias
```bash
# Desde el directorio del proyecto
composer install
```

### 4. Configurar Base de Datos
```sql
-- Acceder a phpMyAdmin: http://localhost/phpmyadmin
CREATE DATABASE planilla_system 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Ejecutar database.sql
```

### 5. Configurar Variables de Entorno
Crear archivo `.env` con:
```bash
# Configuración de la Aplicación
APP_NAME="Planilla Simple"
APP_URL="http://localhost/planilla-claude-v2"
APP_DEBUG=true

# Configuración de Base de Datos  
DB_HOST=localhost
DB_DATABASE=planilla_system
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Verificar Instalación
```
✅ Sistema de Marcaciones: http://localhost/planilla-claude-v2/
✅ Panel Administrativo: http://localhost/planilla-claude-v2/admin
✅ Dashboard: http://localhost/planilla-claude-v2/admin/dashboard
```

**Credenciales por defecto:**
- Usuario: `admin`
- Contraseña: `password`

---

## 🌐 Deployment en Producción

### Servidor Linux (Ubuntu 20.04+)

#### 1. Preparar Servidor
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP 8.2
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml

# Instalar MySQL
sudo apt install mysql-server
sudo mysql_secure_installation

# Instalar Nginx
sudo apt install nginx

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### 2. Configurar Nginx
```nginx
# /etc/nginx/sites-available/planilla-simple
server {
    listen 443 ssl http2;
    server_name tu-dominio.com;
    root /var/www/planilla-simple;
    index index.php;

    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    # Deny sensitive files
    location ~ /\.(env|git) {
        deny all;
        return 404;
    }
}
```

#### 3. Deploy Archivos
```bash
# Subir archivos al servidor
scp -r planilla-claude-v2/ usuario@servidor:/var/www/planilla-simple/

# En el servidor
cd /var/www/planilla-simple
composer install --no-dev --optimize-autoloader

# Configurar permisos
sudo chown -R www-data:www-data /var/www/planilla-simple
sudo chmod -R 755 /var/www/planilla-simple
sudo chmod -R 775 storage/ uploads/
sudo chmod 600 .env
```

#### 4. Configurar Base de Datos
```sql
CREATE DATABASE planilla_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'planilla_user'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT ALL PRIVILEGES ON planilla_prod.* TO 'planilla_user'@'localhost';

USE planilla_prod;
SOURCE database.sql;
```

#### 5. Configurar SSL (Let's Encrypt)
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d tu-dominio.com
```

### Hosting Compartido (cPanel)

1. **Subir archivos** vía File Manager o FTP
2. **Crear base de datos** en cPanel → MySQL Databases
3. **Configurar .env** con datos de producción
4. **Importar database.sql** en phpMyAdmin
5. **Verificar permisos** de carpetas

---

## ⚙️ Configuración

### Variables de Entorno (.env)
```bash
# Aplicación
APP_NAME="Planilla Simple"
APP_URL="https://tu-dominio.com"
APP_DEBUG=false  # false en producción
APP_ENV=production
APP_TIMEZONE="America/Guatemala"

# Base de Datos
DB_HOST=localhost
DB_DATABASE=planilla_system
DB_USERNAME=root
DB_PASSWORD=

# Seguridad
SESSION_SECURE=true  # true con HTTPS
CSRF_TOKEN=true
```

### Configuración PHP (producción)
```ini
# php.ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
date.timezone = "America/Guatemala"

# Seguridad
expose_php = Off
display_errors = Off
log_errors = On
session.cookie_httponly = 1
session.cookie_secure = 1
```

---

## 📁 Estructura del Proyecto

```
planilla-claude-v2/
├── 📁 app/
│   ├── 📁 Controllers/     # Controladores MVC
│   ├── 📁 Models/          # Modelos de datos  
│   ├── 📁 Views/           # Vistas y templates
│   ├── 📁 Core/            # Núcleo del framework
│   └── 📁 Middleware/      # Middlewares
├── 📁 config/              # Configuración
├── 📁 public/              # Assets públicos
├── 📁 storage/             # Logs y archivos temporales
├── 📁 vendor/              # Dependencias
├── 📄 .env                 # Variables de entorno
├── 📄 .htaccess           # Configuración Apache
├── 📄 composer.json       # Dependencias PHP
├── 📄 database.sql        # Estructura BD
└── 📄 index.php           # Punto de entrada
```

---

## 🚀 Uso

### Acceso al Sistema
1. **Sistema de Marcaciones:** Página principal para empleados
2. **Panel Admin:** `/admin` - Gestión administrativa  
3. **Dashboard:** `/admin/dashboard` - Métricas ejecutivas

### Funcionalidades Principales
- **Empleados:** Crear, editar, eliminar personal
- **Asistencia:** Registrar marcaciones y generar reportes
- **Estructura:** Gestionar posiciones, cargos y funciones
- **Horarios:** Definir horarios de trabajo
- **Reportes:** Analytics y exportación de datos

---

## 🛠️ Troubleshooting

### Errores Comunes

#### Error 500 - Internal Server Error
```bash
# Verificar logs
tail -f storage/logs/app.log

# Verificar permisos
chmod -R 755 planilla-claude-v2/
chmod -R 775 storage/ uploads/
```

#### Error de Conexión BD
```bash
# Verificar .env
grep DB_ .env

# Probar conexión
mysql -h localhost -u root -p planilla_system
```

#### Error 404 - Routes
```bash
# Verificar .htaccess existe
ls -la .htaccess

# Verificar mod_rewrite (Apache)
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Performance

#### Optimizar para Producción
```bash
# Composer optimizado
composer install --no-dev --optimize-autoloader

# Habilitar OPCache en php.ini
opcache.enable=1
opcache.memory_consumption=128
```

---

## 📞 Soporte

- **Issues:** [GitHub Issues](https://github.com/tu-usuario/planilla-simple/issues)
- **Documentación:** Archivo README.md completo
- **Email:** soporte@planilla-simple.com

---

## 🎯 Próximas Funcionalidades

### ✅ **v2.4.0 - Completado (Agosto 2025)**
- [x] Sistema de planillas con cálculos automáticos
- [x] Editor de fórmulas con validación en tiempo real  
- [x] Exportación nativa a Excel
- [x] Estados de workflow de planillas

### 🔄 **v2.5.0 - En Desarrollo**
- [ ] Sistema de asignaciones por empleado
- [ ] Personalización de conceptos por trabajador
- [ ] Vigencias y períodos de aplicación

### 📅 **Roadmap Futuro**
- [ ] Sistema de usuarios y roles granulares
- [ ] Control de permisos avanzado
- [ ] API REST completa
- [ ] Notificaciones automáticas
- [ ] Integración con biométricos
- [ ] Aplicación móvil

---

**¡Gracias por usar Planilla Simple! 🚀**

*Sistema desarrollado con ❤️ para simplificar la gestión de nóminas*
