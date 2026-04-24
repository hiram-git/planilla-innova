# Instalación del Sistema

Este capítulo describe la instalación completa del Sistema de Planillas
INNOVA en un servidor Ubuntu 22.04 LTS con Nginx, PHP 8.3 y MySQL. La
guía está orientada a administradores de sistema / DevOps.

## Requisitos del Sistema

### Especificaciones Mínimas
- **Sistema Operativo**: Ubuntu 22.04 LTS
- **RAM**: 2 GB mínimo (4 GB recomendado)
- **Disco**: 10 GB espacio libre
- **Conexión**: Internet para descargas

### Software Base
- Nginx 1.22+
- PHP 8.3 + extensiones
- MySQL 8.0/MariaDB 10.6
- Composer (gestor dependencias PHP)

---

## Instalación Paso a Paso
### 1. Actualización del Sistema
```bash
sudo apt update && sudo apt upgrade -y
sudo reboot
```

### 2. Instalación de Nginx
```bash
# Instalar Nginx
sudo apt install nginx -y

# Iniciar y habilitar servicio
sudo systemctl start nginx
sudo systemctl enable nginx

# Verificar estado
sudo systemctl status nginx

# Configurar firewall
sudo ufw allow 'Nginx Full'
```

### 3. Instalación de PHP 8.3
```bash
# Agregar repositorio PHP
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Instalar PHP 8.3 y extensiones necesarias
sudo apt install php8.3-fpm php8.3-mysql php8.3-xml php8.3-curl \
php8.3-zip php8.3-intl php8.3-mbstring php8.3-gd php8.3-cli \
php8.3-bcmath php8.3-json php8.3-opcache -y

# Verificar instalación
php --version

# Iniciar PHP-FPM
sudo systemctl start php8.3-fpm
sudo systemctl enable php8.3-fpm
```

### 4. Instalación de MySQL
```bash
# Instalar MySQL Server
sudo apt install mysql-server -y

# Configuración segura
sudo mysql_secure_installation
```

**Responder durante configuración segura:**
- Remove anonymous users? **Y**
- Disallow root login remotely? **Y**
- Remove test database? **Y**
- Reload privilege tables? **Y**

```bash
# Crear usuario y base de datos
sudo mysql -u root -p

# Comandos dentro de MySQL:
CREATE DATABASE planilla_innova CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'planilla_user'@'localhost' IDENTIFIED BY 'Namv-0315$';
GRANT ALL PRIVILEGES ON planilla_innova.* TO 'planilla_user'@'localhost';

CREATE USER 'planilla_user'@'%' IDENTIFIED BY 'Namv-0315$';
GRANT ALL PRIVILEGES ON planilla_innova.* TO 'planilla_user'@'%';

CREATE USER 'root'@'localhost' IDENTIFIED BY 'Namv-0315$';
GRANT ALL PRIVILEGES ON planilla_innova.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Instalación de Composer
```bash
# Descargar e instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verificar instalación
composer --version
```

### 6. Configuración del Proyecto
#### A. Clonar/Subir el Proyecto
```bash
# Crear directorio web
sudo mkdir -p /var/www/planilla-sistema
sudo chown -R $USER:$USER /var/www/planilla-sistema

# Subir archivos del proyecto a /var/www/planilla-sistema/
# (Usar SCP, SFTP, Git, etc.)
```

#### B. Configurar Permisos
```bash
cd /var/www/planilla-sistema

# Permisos de archivos
sudo chown -R www-data:www-data .
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;

# Permisos especiales para escritura
sudo chmod -R 775 storage/ bootstrap/cache/ public/uploads/
```

#### C. Configurar Variables de Entorno
```bash
# Copiar archivo de configuración
cp .env.example .env

# Editar configuración
nano .env
```

**Contenido del archivo .env:**
```ini
# Configuración de la Aplicación
APP_NAME="Sistema de Planillas"
APP_URL=http://tu-dominio.com
APP_ENV=production
APP_DEBUG=false

# Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=planilla_innova
DB_USERNAME=planilla_user
DB_PASSWORD=Namv-0315$

# Configuración de Sesión
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

# Configuración de Archivos
UPLOAD_MAX_SIZE=10485760
ALLOWED_EXTENSIONS=jpg,jpeg,png,pdf,doc,docx
```

### 7. Configuración de Nginx
#### A. Crear Virtual Host
```bash
sudo nano /etc/nginx/sites-available/planilla-sistema
```

**Contenido del archivo:**
```nginx
server {
    listen 80;
    server_name tu-dominio.com www.tu-dominio.com;
    root /var/www/planilla-sistema/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/planilla-access.log;
    error_log /var/log/nginx/planilla-error.log;

    # Configuración principal
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Procesar archivos PHP
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Denegar acceso a archivos sensibles
    location ~ /\.(ht|env) {
        deny all;
    }

    # Configuración de archivos estáticos
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Límite de tamaño de archivos
    client_max_body_size 50M;
}
```

#### B. Habilitar el Sitio
```bash
# Crear enlace simbólico
sudo ln -s /etc/nginx/sites-available/planilla-sistema /etc/nginx/sites-enabled/

# Desactivar sitio por defecto
sudo unlink /etc/nginx/sites-enabled/default

# Verificar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl reload nginx
```

### 8. Importar Base de Datos
#### A. Encontrar archivo SQL
```bash
# Buscar archivo de base de datos
cd /var/www/planilla-sistema
find . -name "*.sql" -o -name "database*"
```

#### B. Importar esquema y datos
```bash
# Importar la base de datos
mysql -u planilla_user -p planilla_innova < database/planilla_innova.sql

# O si tienes archivos separados:
mysql -u planilla_user -p planilla_simple < database/schema.sql
mysql -u planilla_user -p planilla_simple < database/data.sql
```

### 9. Configuración PHP Adicional
#### A. Optimizar php.ini
```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

**Configuraciones importantes:**
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
date.timezone = America/Costa_Rica

# Optimizaciones de producción
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

#### B. Reiniciar servicios
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

---

## Configuración de Seguridad
### 1. SSL/TLS con Let's Encrypt (HTTPS)
```bash
# Instalar Certbot
sudo apt install snapd -y
sudo snap install core; sudo snap refresh core
sudo snap install --classic certbot
sudo ln -s /snap/bin/certbot /usr/bin/certbot

# Obtener certificado SSL
sudo certbot --nginx -d tu-dominio.com -d www.tu-dominio.com

# Auto-renovación
sudo systemctl status snap.certbot.renew.timer
```

### 2. Configuración de Firewall
```bash
# Configurar UFW
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw status
```

### 3. Configuración de Fail2Ban
```bash
# Instalar Fail2Ban
sudo apt install fail2ban -y

# Configurar para Nginx
sudo nano /etc/fail2ban/jail.local
```

**Contenido básico:**
```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/planilla-error.log
```

```bash
sudo systemctl restart fail2ban
```

---

## Verificación de la Instalación
### 1. Verificar Servicios
```bash
# Verificar que todos los servicios estén activos
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
```

### 2. Verificar Conexión a Base de Datos
```bash
# Probar conexión MySQL
mysql -u planilla_user -p planilla_simple -e "SHOW TABLES;"
```

### 3. Verificar Aplicación Web
```bash
# Verificar logs de Nginx
sudo tail -f /var/log/nginx/planilla-error.log

# Verificar logs de PHP
sudo tail -f /var/log/php8.3-fpm.log
```

### 4. Probar en Navegador
- Acceder a: `http://tu-dominio.com`
- Login por defecto: `admin@planilla.com` / `password`
- Verificar módulos principales funcionando

---

## Mantenimiento del Sistema
### Actualizaciones Regulares
```bash
# Script de actualización (crear archivo update.sh)
#!/bin/bash
sudo apt update && sudo apt upgrade -y
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
echo "Sistema actualizado: $(date)" >> /var/log/planilla-updates.log
```

### Backup Automatizado
```bash
# Crear script de backup (backup.sh)
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/backup/planilla"

# Crear directorio
mkdir -p $BACKUP_DIR

# Backup Base de Datos
mysqldump -u planilla_user -p'Namv-0315$' planilla_simple > $BACKUP_DIR/db_$DATE.sql

# Backup Archivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/planilla-sistema

# Limpiar backups antiguos (más de 30 días)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completado: $DATE" >> /var/log/planilla-backup.log
```

### Crontab para Automatización
```bash
# Editar crontab
crontab -e

# Agregar líneas:
# Backup diario a las 2:00 AM
0 2 * * * /home/scripts/backup.sh

# Actualizaciones semanales domingos 3:00 AM
0 3 * * 0 /home/scripts/update.sh
```

---

## Solución de Problemas
### Error 502 Bad Gateway
```bash
# Verificar PHP-FPM
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm

# Verificar configuración Nginx
sudo nginx -t
```

### Errores de Permisos
```bash
# Resetear permisos
cd /var/www/planilla-sistema
sudo chown -R www-data:www-data .
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;
sudo chmod -R 775 storage/ public/uploads/
```

### Error de Conexión Base de Datos
```bash
# Verificar MySQL
sudo systemctl status mysql
mysql -u planilla_user -p -e "SELECT 1"

# Verificar configuración .env
cat .env | grep DB_
```

### Performance Lenta
```bash
# Verificar recursos
htop
df -h
free -h

# Optimizar MySQL
sudo mysql_secure_installation
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

---

## Soporte Post-Instalación
### Logs Importantes
- **Nginx**: `/var/log/nginx/planilla-error.log`
- **PHP**: `/var/log/php8.3-fpm.log`
- **MySQL**: `/var/log/mysql/error.log`
- **Sistema**: `/var/log/syslog`

### Comandos Útiles
```bash
# Monitorear logs en tiempo real
sudo tail -f /var/log/nginx/planilla-error.log

# Reiniciar todos los servicios
sudo systemctl restart nginx php8.3-fpm mysql

# Verificar espacio en disco
df -h

# Verificar memoria
free -h

# Verificar procesos
ps aux | grep -E '(nginx|php|mysql)'
```

---

## ¡Instalación Completada!
El **Sistema de Planillas MVC** está ahora funcionando en **Ubuntu 22.04 LTS con Nginx**.

### URLs de Acceso
- **Sistema Principal**: `https://tu-dominio.com`
- **Manual de Usuario**: `https://tu-dominio.com/documentation/`
- **Login Administrativo**: `admin@planilla.com` / `password`

### Próximos Pasos
1. Cambiar contraseñas por defecto
2. Configurar datos de la empresa
3. Crear usuarios adicionales
4. Importar empleados
5. Configurar conceptos y fórmulas
6. Capacitar usuarios finales

**Sistema listo para producción empresarial.**

---

*Manual creado para el Sistema de Planillas MVC - Versión Ubuntu/Nginx*
*Fecha: Septiembre 2025*