#!/bin/bash

# Script de instalación automática para Sistema de Planillas MVC - CentOS/RHEL
# Versión: 2.0
# Compatibilidad: CentOS 8/9, RHEL 8/9, Rocky Linux 8/9, AlmaLinux 8/9
# Autor: Sistema de Planillas MVC Team

set -e  # Salir si cualquier comando falla

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
GRAY='\033[0;90m'
NC='\033[0m' # No Color

# Funciones de utilidad
log_step() {
    echo -e "${CYAN}🔄 $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Header
clear
echo -e "${MAGENTA}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════╗
║                    🚀 INSTALADOR AUTOMÁTICO                   ║
║              Sistema de Planillas MVC v2.0                  ║
║                 CentOS/RHEL/Rocky Edition                    ║
╚══════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo -e "${WHITE}Este script instalará automáticamente:${NC}"
echo -e "${GRAY}• Stack LAMP (Apache, MySQL, PHP 8.3)${NC}"
echo -e "${GRAY}• Configuración de SELinux${NC}"
echo -e "${GRAY}• Configuración de Firewalld${NC}"
echo -e "${GRAY}• Base de datos del sistema${NC}"
echo -e "${GRAY}• Archivos del sistema${NC}"
echo -e "${GRAY}• Certificado SSL autoasignado${NC}"
echo ""

# Detectar la distribución específica
if command -v dnf >/dev/null 2>&1; then
    PKG_MANAGER="dnf"
    SYSTEM_VERSION=$(rpm -E %{rhel})
else
    PKG_MANAGER="yum"
    SYSTEM_VERSION=$(rpm -E %{rhel})
fi

# Variables de configuración
PROJECT_NAME="planilla-innova"
PROJECT_PATH="/var/www/html/$PROJECT_NAME"
BACKUP_PATH="/var/backups/$PROJECT_NAME"
DB_NAME="planilla_mvc"
DB_USER="planilla_user"
DB_PASS=""
ADMIN_EMAIL=""
DOMAIN="localhost"

# Verificaciones previas
log_step "Verificando prerrequisitos..."

# Verificar si se ejecuta como root
if [[ $EUID -ne 0 ]]; then
   log_error "Este script debe ejecutarse como root (sudo)"
   echo -e "${YELLOW}Uso: sudo $0${NC}"
   exit 1
fi

# Verificar distribución
if ! grep -qE "(CentOS|Red Hat|Rocky|AlmaLinux)" /etc/os-release; then
    log_error "Este script está diseñado para CentOS/RHEL/Rocky/AlmaLinux"
    log_info "Distribuciones soportadas: CentOS 8/9, RHEL 8/9, Rocky Linux 8/9, AlmaLinux 8/9"
    exit 1
fi

# Detectar versión específica
DISTRO=$(grep '^NAME=' /etc/os-release | cut -d'"' -f2)
VERSION=$(grep '^VERSION_ID=' /etc/os-release | cut -d'"' -f2)
log_info "Detectado: $DISTRO $VERSION"

# Verificar conexión a Internet
if ! ping -c 1 google.com &> /dev/null; then
    log_error "No se detectó conexión a Internet"
    log_info "Verifique su conexión y vuelva a intentar"
    exit 1
fi

log_success "Prerrequisitos verificados"

# Generar contraseña segura para base de datos
if [[ -z "$DB_PASS" ]]; then
    DB_PASS=$(openssl rand -base64 32)
    log_info "Contraseña de base de datos generada automáticamente"
fi

# Solicitar información al usuario
echo ""
echo -e "${YELLOW}CONFIGURACIÓN DE INSTALACIÓN:${NC}"
echo -e "${GRAY}• Sistema: $DISTRO $VERSION${NC}"
echo -e "${GRAY}• Gestor de paquetes: $PKG_MANAGER${NC}"
echo -e "${GRAY}• Directorio del proyecto: $PROJECT_PATH${NC}"
echo -e "${GRAY}• Base de datos: $DB_NAME${NC}"
echo -e "${GRAY}• Usuario de BD: $DB_USER${NC}"
echo ""

read -p "¿Desea continuar con la instalación? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Instalación cancelada por el usuario${NC}"
    exit 0
fi

# Solicitar email del administrador
read -p "Ingrese email del administrador (opcional): " ADMIN_EMAIL

# Función de limpieza en caso de error
cleanup() {
    log_error "Error durante la instalación. Limpiando..."
    systemctl stop httpd 2>/dev/null || true
    systemctl stop mysqld 2>/dev/null || true
}

trap cleanup ERR

# Inicio de la instalación
log_step "Iniciando instalación del Sistema de Planillas MVC..."

# 1. Actualizar el sistema
log_step "Actualizando el sistema..."
$PKG_MANAGER update -y
log_success "Sistema actualizado"

# 2. Instalar EPEL y herramientas básicas
log_step "Instalando repositorios y herramientas básicas..."
$PKG_MANAGER install -y epel-release
$PKG_MANAGER install -y curl wget unzip git openssl which

# Configurar PowerTools/CRB repository (necesario para algunas dependencias)
if [[ "$SYSTEM_VERSION" == "8" ]]; then
    $PKG_MANAGER config-manager --set-enabled powertools 2>/dev/null || $PKG_MANAGER config-manager --set-enabled PowerTools 2>/dev/null || true
elif [[ "$SYSTEM_VERSION" == "9" ]]; then
    $PKG_MANAGER config-manager --set-enabled crb 2>/dev/null || true
fi

log_success "Herramientas básicas instaladas"

# 3. Instalar Apache (httpd)
log_step "Instalando Apache Web Server..."
$PKG_MANAGER install -y httpd httpd-tools mod_ssl

# Configurar y habilitar Apache
systemctl enable httpd
systemctl start httpd

log_success "Apache instalado y configurado"

# 4. Instalar MySQL 8.0
log_step "Instalando MySQL Server..."

# Instalar MySQL repository
if [[ ! -f /etc/yum.repos.d/mysql-community.repo ]]; then
    if [[ "$SYSTEM_VERSION" == "8" ]]; then
        $PKG_MANAGER install -y https://dev.mysql.com/get/mysql80-community-release-el8-1.noarch.rpm
    elif [[ "$SYSTEM_VERSION" == "9" ]]; then
        $PKG_MANAGER install -y https://dev.mysql.com/get/mysql80-community-release-el9-1.noarch.rpm
    fi
fi

# Deshabilitar MySQL module de AppStream para evitar conflictos
$PKG_MANAGER module disable mysql -y 2>/dev/null || true

# Instalar MySQL
$PKG_MANAGER install -y mysql-community-server

# Configurar y habilitar MySQL
systemctl enable mysqld
systemctl start mysqld

# Obtener contraseña temporal de root
TEMP_PASS=$(grep 'temporary password' /var/log/mysqld.log | tail -1 | awk '{print $NF}')

# Configurar MySQL con contraseña segura
mysql --connect-expired-password -u root -p"$TEMP_PASS" << EOF
ALTER USER 'root'@'localhost' IDENTIFIED BY 'TempP@ssw0rd123!';
FLUSH PRIVILEGES;
EOF

# Configuración de seguridad más permisiva para el entorno
mysql -u root -p'TempP@ssw0rd123!' << EOF
SET GLOBAL validate_password.policy = LOW;
SET GLOBAL validate_password.length = 6;
ALTER USER 'root'@'localhost' IDENTIFIED BY '$DB_PASS';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF

log_success "MySQL instalado y configurado"

# 5. Instalar PHP 8.3 desde Remi
log_step "Instalando PHP 8.3..."

# Instalar repositorio Remi
if [[ "$SYSTEM_VERSION" == "8" ]]; then
    $PKG_MANAGER install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
elif [[ "$SYSTEM_VERSION" == "9" ]]; then
    $PKG_MANAGER install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
fi

# Habilitar PHP 8.3 desde Remi
$PKG_MANAGER module reset php -y
$PKG_MANAGER module enable php:remi-8.3 -y

# Instalar PHP y extensiones
$PKG_MANAGER install -y php php-mysql php-mbstring php-xml php-curl php-zip php-gd php-json php-intl php-opcache

# Configurar PHP
sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /etc/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 10M/' /etc/php.ini
sed -i 's/;date.timezone =/date.timezone = America\/Panama/' /etc/php.ini

# Configurar OpCache
cat >> /etc/php.d/10-opcache.ini << EOF
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
EOF

log_success "PHP 8.3 instalado y configurado"

# 6. Configurar SELinux
log_step "Configurando SELinux..."

# Instalar herramientas de SELinux
$PKG_MANAGER install -y policycoreutils-python-utils

# Configurar contextos SELinux para Apache
setsebool -P httpd_can_network_connect 1
setsebool -P httpd_can_network_connect_db 1
setsebool -P httpd_execmem 1
setsebool -P httpd_unified 1

# Configurar contexto para directorio web
semanage fcontext -a -t httpd_exec_t "/var/www/html(/.*)?" 2>/dev/null || true
restorecon -Rv /var/www/html/

log_success "SELinux configurado"

# 7. Configurar Firewalld
log_step "Configurando Firewall..."

systemctl enable firewalld
systemctl start firewalld

# Configurar reglas de firewall
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-service=ssh

# Puerto MySQL solo si es necesario para acceso remoto
# firewall-cmd --permanent --add-port=3306/tcp

firewall-cmd --reload

log_success "Firewall configurado"

# 8. Crear base de datos
log_step "Configurando base de datos..."

mysql -u root -p"$DB_PASS" << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

# Crear estructura básica
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" << EOF
-- Tabla de usuarios por defecto
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role VARCHAR(20) DEFAULT 'user',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de configuración de empresa
CREATE TABLE IF NOT EXISTS companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    nit VARCHAR(50),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    currency_symbol VARCHAR(10) DEFAULT 'Q',
    currency_code VARCHAR(3) DEFAULT 'GTQ',
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar usuario administrador
INSERT IGNORE INTO users (username, password, email, role) 
VALUES ('admin', SHA2('admin123', 256), COALESCE(NULLIF('$ADMIN_EMAIL', ''), 'admin@planilla-innova.com'), 'admin');

-- Insertar configuración básica de empresa
INSERT IGNORE INTO companies (name, currency_symbol, currency_code) 
VALUES ('Mi Empresa', 'Q', 'GTQ');
EOF

log_success "Base de datos configurada"

# 9. Crear directorios del proyecto
log_step "Creando estructura de directorios..."

# Crear respaldo si el directorio existe
if [[ -d "$PROJECT_PATH" ]]; then
    log_warning "El directorio del proyecto ya existe. Creando respaldo..."
    backup_name="${PROJECT_NAME}-backup-$(date +%Y%m%d-%H%M%S)"
    mv "$PROJECT_PATH" "/var/www/html/$backup_name"
fi

# Crear directorios
mkdir -p $PROJECT_PATH
mkdir -p $BACKUP_PATH
mkdir -p $PROJECT_PATH/{storage,logs,database,public,app,config}
mkdir -p $PROJECT_PATH/storage/{reports,exports,uploads}
mkdir -p $PROJECT_PATH/logs/{apache,php,system}

log_success "Estructura de directorios creada"

# 10. Instalar archivos del sistema
log_step "Instalando archivos del sistema..."

# Crear página de bienvenida
cat > $PROJECT_PATH/index.php << 'EOF'
<?php
// Sistema de Planillas MVC - CentOS/RHEL Edition
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php/error.log');

if (!file_exists('.env')) {
    die('Sistema no configurado. Configure el archivo .env');
}

require_once 'config/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Planillas MVC - CentOS Edition</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-size: 24px; margin-bottom: 20px; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; color: #856404; }
        .centos { background: #9f2b68; color: white; padding: 10px; border-radius: 5px; text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="centos">
            <h2>🎩 CentOS/RHEL Edition</h2>
        </div>
        
        <h1 class="success">✅ Sistema de Planillas MVC Instalado</h1>
        <p>La instalación se ha completado exitosamente en <?php echo php_uname('s') . ' ' . php_uname('r'); ?>.</p>
        
        <div class="info">
            <h3>📋 Información de Acceso:</h3>
            <ul>
                <li><strong>Usuario por defecto:</strong> admin</li>
                <li><strong>Contraseña por defecto:</strong> admin123</li>
                <li><strong>Base de datos:</strong> <?php echo $DB_NAME ?? 'planilla_mvc'; ?></li>
                <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                <li><strong>Servidor:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></li>
            </ul>
        </div>
        
        <div class="warning">
            <h3>⚠️ Importante - Seguridad CentOS/RHEL:</h3>
            <ul>
                <li>SELinux está configurado y habilitado</li>
                <li>Firewalld está configurado con reglas básicas</li>
                <li>Cambie la contraseña por defecto inmediatamente</li>
                <li>Configure certificado SSL/TLS válido</li>
                <li>Revise logs de seguridad regularmente</li>
            </ul>
        </div>
        
        <h3>🛠️ Características de la Instalación:</h3>
        <ul>
            <li>✅ Apache HTTP Server con mod_ssl</li>
            <li>✅ MySQL 8.0 Community Server</li>
            <li>✅ PHP 8.3 con extensiones requeridas</li>
            <li>✅ SELinux configurado para aplicaciones web</li>
            <li>✅ Firewalld con reglas de seguridad</li>
            <li>✅ OpCache habilitado para mejor rendimiento</li>
            <li>✅ Logrotate configurado</li>
            <li>✅ Respaldos automáticos programados</li>
        </ul>
        
        <h3>🔧 Comandos Útiles CentOS/RHEL:</h3>
        <ul>
            <li><code>systemctl status httpd</code> - Estado de Apache</li>
            <li><code>systemctl status mysqld</code> - Estado de MySQL</li>
            <li><code>firewall-cmd --list-all</code> - Configuración firewall</li>
            <li><code>getenforce</code> - Estado de SELinux</li>
            <li><code>tail -f /var/log/httpd/error_log</code> - Logs de Apache</li>
        </ul>
    </div>
</body>
</html>
EOF

# Crear archivo .env
cat > $PROJECT_PATH/.env << EOF
# Configuración de la aplicación
APP_NAME="Sistema de Planillas MVC"
APP_URL="http://$DOMAIN/$PROJECT_NAME"
APP_ENV="production"
APP_DEBUG=false

# Base de datos
DB_HOST="localhost"
DB_DATABASE="$DB_NAME"
DB_USERNAME="$DB_USER"
DB_PASSWORD="$DB_PASS"

# Configuración regional
TIMEZONE="America/Panama"
LOCALE="es_ES"

# Configuración de correo
MAIL_HOST="smtp.gmail.com"
MAIL_PORT=587
MAIL_USERNAME=""
MAIL_PASSWORD=""

# Configuración de seguridad
SESSION_LIFETIME=7200
MAX_LOGIN_ATTEMPTS=5

# Configuración específica CentOS/RHEL
SELINUX_ENABLED=true
FIREWALLD_ENABLED=true
EOF

# Crear bootstrap básico
mkdir -p $PROJECT_PATH/config
cat > $PROJECT_PATH/config/bootstrap.php << 'EOF'
<?php
// Bootstrap del sistema para CentOS/RHEL
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Configuración específica para CentOS/RHEL
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
EOF

log_success "Archivos del sistema instalados"

# 11. Configurar Virtual Host
log_step "Configurando virtual host de Apache..."

cat > /etc/httpd/conf.d/$PROJECT_NAME.conf << EOF
# Virtual Host para Sistema de Planillas MVC
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $PROJECT_PATH
    
    <Directory $PROJECT_PATH>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Seguridad adicional
        <FilesMatch "\.env$">
            Require all denied
        </FilesMatch>
        
        <FilesMatch "\.(log|sql|md|conf)$">
            Require all denied
        </FilesMatch>
        
        # Prevenir acceso a directorios sensibles
        <DirectoryMatch "/\.(git|svn|ht)">
            Require all denied
        </DirectoryMatch>
    </Directory>
    
    # Configuración de logs
    ErrorLog /var/log/httpd/$PROJECT_NAME-error.log
    CustomLog /var/log/httpd/$PROJECT_NAME-access.log combined
    
    # Configuración PHP específica
    php_admin_value error_log "$PROJECT_PATH/logs/php/error.log"
    php_admin_flag log_errors on
    php_admin_flag display_errors off
    
    # Headers de seguridad
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>

# Configuración SSL (con certificado autoasignado)
<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $PROJECT_PATH
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/$PROJECT_NAME.crt
    SSLCertificateKeyFile /etc/ssl/private/$PROJECT_NAME.key
    
    # Configuración SSL moderna
    SSLProtocol -all +TLSv1.2 +TLSv1.3
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder off
    SSLSessionTickets off
    
    <Directory $PROJECT_PATH>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog /var/log/httpd/$PROJECT_NAME-ssl-error.log
    CustomLog /var/log/httpd/$PROJECT_NAME-ssl-access.log combined
    
    # Headers de seguridad HTTPS
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
</IfModule>
EOF

log_success "Virtual host configurado"

# 12. Generar certificado SSL autoasignado
log_step "Generando certificado SSL autoasignado..."

mkdir -p /etc/ssl/private
mkdir -p /etc/ssl/certs

openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/$PROJECT_NAME.key \
    -out /etc/ssl/certs/$PROJECT_NAME.crt \
    -subj "/C=PA/ST=Panama/L=Panama/O=Planilla MVC/CN=$DOMAIN"

chmod 600 /etc/ssl/private/$PROJECT_NAME.key
chmod 644 /etc/ssl/certs/$PROJECT_NAME.crt

log_success "Certificado SSL generado"

# 13. Configurar permisos y contextos SELinux
log_step "Configurando permisos y SELinux..."

# Permisos básicos
chown -R apache:apache $PROJECT_PATH
chmod -R 755 $PROJECT_PATH
chmod -R 777 $PROJECT_PATH/storage
chmod -R 777 $PROJECT_PATH/logs
chmod 600 $PROJECT_PATH/.env

# Contextos SELinux específicos
semanage fcontext -a -t httpd_exec_t "$PROJECT_PATH(/.*)?" 2>/dev/null || true
semanage fcontext -a -t httpd_log_t "$PROJECT_PATH/logs(/.*)?" 2>/dev/null || true
semanage fcontext -a -t httpd_var_lib_t "$PROJECT_PATH/storage(/.*)?" 2>/dev/null || true
restorecon -Rv $PROJECT_PATH

log_success "Permisos y SELinux configurados"

# 14. Configurar logrotate
log_step "Configurando rotación de logs..."

cat > /etc/logrotate.d/$PROJECT_NAME << EOF
$PROJECT_PATH/logs/*.log $PROJECT_PATH/logs/*/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 apache apache
    sharedscripts
    postrotate
        systemctl reload httpd > /dev/null 2>&1 || true
    endscript
}

/var/log/httpd/$PROJECT_NAME*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 apache apache
    sharedscripts
    postrotate
        systemctl reload httpd > /dev/null 2>&1 || true
    endscript
}
EOF

log_success "Rotación de logs configurada"

# 15. Crear scripts de mantenimiento
log_step "Configurando scripts de mantenimiento..."

# Script de respaldo
cat > /usr/local/bin/backup-planilla-innova.sh << EOF
#!/bin/bash
# Script de respaldo para CentOS/RHEL

DATE=\$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$BACKUP_PATH/\$DATE"
PROJECT_DIR="$PROJECT_PATH"
DB_NAME="$DB_NAME"
DB_USER="$DB_USER"
DB_PASS="$DB_PASS"

# Crear directorio de respaldo
mkdir -p \$BACKUP_DIR

# Respaldar base de datos
mysqldump -u \$DB_USER -p\$DB_PASS \$DB_NAME > \$BACKUP_DIR/database.sql

# Respaldar archivos
tar -czf \$BACKUP_DIR/files.tar.gz -C \$(dirname \$PROJECT_DIR) \$(basename \$PROJECT_DIR) --exclude='logs/*.log' --exclude='storage/temp/*'

# Respaldar configuración del sistema
cp /etc/httpd/conf.d/$PROJECT_NAME.conf \$BACKUP_DIR/
cp -r /etc/ssl/certs/$PROJECT_NAME.crt /etc/ssl/private/$PROJECT_NAME.key \$BACKUP_DIR/ 2>/dev/null || true

# Limpiar respaldos antiguos
find $BACKUP_PATH -type d -name "20*" -mtime +7 -exec rm -rf {} + 2>/dev/null || true

echo "Respaldo completado: \$BACKUP_DIR"
logger "Planilla MVC: Respaldo completado en \$BACKUP_DIR"
EOF

chmod +x /usr/local/bin/backup-planilla-innova.sh

# Script de monitoreo específico para CentOS/RHEL
cat > /usr/local/bin/monitor-planilla-innova.sh << 'EOF'
#!/bin/bash
# Monitor para CentOS/RHEL

LOG_FILE="/var/log/planilla-innova-monitor.log"
PROJECT_PATH="/var/www/html/planilla-innova"

log_with_timestamp() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
    logger "Planilla MVC Monitor: $1"
}

# Verificar servicios críticos
for service in httpd mysqld firewalld; do
    if ! systemctl is-active --quiet $service; then
        log_with_timestamp "ERROR: Servicio $service no está ejecutándose"
        systemctl start $service 2>/dev/null
    fi
done

# Verificar SELinux
if command -v getenforce >/dev/null 2>&1; then
    SELINUX_STATUS=$(getenforce)
    if [[ "$SELINUX_STATUS" != "Enforcing" && "$SELINUX_STATUS" != "Permissive" ]]; then
        log_with_timestamp "WARNING: SELinux está deshabilitado"
    fi
fi

# Verificar espacio en disco
DISK_USAGE=$(df $PROJECT_PATH | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
    log_with_timestamp "WARNING: Espacio en disco bajo: ${DISK_USAGE}%"
fi

# Verificar logs de errores
if [[ -f "$PROJECT_PATH/logs/php/error.log" ]]; then
    ERROR_COUNT=$(tail -100 "$PROJECT_PATH/logs/php/error.log" 2>/dev/null | wc -l)
    if [ $ERROR_COUNT -gt 50 ]; then
        log_with_timestamp "WARNING: Muchos errores PHP: $ERROR_COUNT en últimas 100 líneas"
    fi
fi

# Verificar intentos de acceso no autorizados en Apache
FAILED_LOGIN_COUNT=$(tail -1000 /var/log/httpd/$PROJECT_NAME-access.log 2>/dev/null | grep -c " 401 \| 403 " || echo "0")
if [ $FAILED_LOGIN_COUNT -gt 20 ]; then
    log_with_timestamp "WARNING: Muchos intentos de acceso no autorizados: $FAILED_LOGIN_COUNT"
fi
EOF

chmod +x /usr/local/bin/monitor-planilla-innova.sh

# Programar tareas de mantenimiento
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-planilla-innova.sh >> /var/log/planilla-innova-backup.log 2>&1") | crontab -
(crontab -l 2>/dev/null; echo "*/15 * * * * /usr/local/bin/monitor-planilla-innova.sh") | crontab -

log_success "Scripts de mantenimiento configurados"

# 16. Reiniciar servicios
log_step "Reiniciando servicios..."

systemctl restart httpd
systemctl restart mysqld
systemctl status httpd --no-pager -l
systemctl status mysqld --no-pager -l

log_success "Servicios reiniciados"

# Instalación completada
echo ""
echo -e "${GREEN}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════╗
║                ✅ INSTALACIÓN COMPLETADA                      ║
╚══════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo -e "${GREEN}🎉 ¡Sistema de Planillas MVC instalado exitosamente en $DISTRO $VERSION!${NC}"
echo ""
echo -e "${YELLOW}📋 INFORMACIÓN DE ACCESO:${NC}"
echo -e "${WHITE}• URL HTTP: http://$DOMAIN/$PROJECT_NAME${NC}"
echo -e "${WHITE}• URL HTTPS: https://$DOMAIN/$PROJECT_NAME${NC}"
echo -e "${WHITE}• Usuario por defecto: admin${NC}"
echo -e "${WHITE}• Contraseña por defecto: admin123${NC}"
echo -e "${WHITE}• Base de datos: $DB_NAME${NC}"
echo -e "${WHITE}• Usuario de BD: $DB_USER${NC}"
echo ""
echo -e "${RED}⚠️  IMPORTANTE - SEGURIDAD CENTOS/RHEL:${NC}"
echo -e "${YELLOW}• SELinux está habilitado y configurado${NC}"
echo -e "${YELLOW}• Firewalld está activo con reglas de seguridad${NC}"
echo -e "${YELLOW}• Certificado SSL autoasignado generado${NC}"
echo -e "${YELLOW}• Cambie la contraseña por defecto inmediatamente${NC}"
echo -e "${YELLOW}• Configure certificado SSL válido para producción${NC}"
echo ""
echo -e "${CYAN}📁 UBICACIONES:${NC}"
echo -e "${GRAY}• Proyecto: $PROJECT_PATH${NC}"
echo -e "${GRAY}• Respaldos: $BACKUP_PATH${NC}"
echo -e "${GRAY}• Logs del sistema: $PROJECT_PATH/logs${NC}"
echo -e "${GRAY}• Logs de Apache: /var/log/httpd/${NC}"
echo -e "${GRAY}• Configuración: $PROJECT_PATH/.env${NC}"
echo -e "${GRAY}• Virtual Host: /etc/httpd/conf.d/$PROJECT_NAME.conf${NC}"
echo ""
echo -e "${CYAN}🛠️  COMANDOS ÚTILES CENTOS/RHEL:${NC}"
echo -e "${GRAY}• Estado Apache: systemctl status httpd${NC}"
echo -e "${GRAY}• Estado MySQL: systemctl status mysqld${NC}"
echo -e "${GRAY}• Estado SELinux: getenforce${NC}"
echo -e "${GRAY}• Firewall: firewall-cmd --list-all${NC}"
echo -e "${GRAY}• Logs Apache: tail -f /var/log/httpd/$PROJECT_NAME-error.log${NC}"
echo -e "${GRAY}• Crear respaldo: /usr/local/bin/backup-planilla-innova.sh${NC}"
echo -e "${GRAY}• Monitor sistema: /usr/local/bin/monitor-planilla-innova.sh${NC}"
echo ""

# Guardar información de instalación
cat > /root/planilla-innova-install-info.txt << EOF
Sistema de Planillas MVC - Información de Instalación CentOS/RHEL
===============================================================

Fecha de instalación: $(date)
Servidor: $(hostname)
Sistema operativo: $DISTRO $VERSION
Kernel: $(uname -r)
SELinux: $(getenforce 2>/dev/null || echo "No disponible")

ACCESO:
- URL HTTP: http://$DOMAIN/$PROJECT_NAME
- URL HTTPS: https://$DOMAIN/$PROJECT_NAME
- Usuario: admin
- Contraseña: admin123

BASE DE DATOS:
- Nombre: $DB_NAME
- Usuario: $DB_USER
- Contraseña: $DB_PASS
- Host: localhost
- Puerto: 3306

UBICACIONES:
- Proyecto: $PROJECT_PATH
- Respaldos: $BACKUP_PATH
- Logs del sistema: $PROJECT_PATH/logs
- Logs Apache: /var/log/httpd/
- Configuración: $PROJECT_PATH/.env
- Virtual Host: /etc/httpd/conf.d/$PROJECT_NAME.conf
- Certificado SSL: /etc/ssl/certs/$PROJECT_NAME.crt

SERVICIOS:
- Apache: systemctl {start|stop|restart|status} httpd
- MySQL: systemctl {start|stop|restart|status} mysqld
- Firewall: systemctl {start|stop|restart|status} firewalld

SEGURIDAD:
- SELinux: $(getenforce 2>/dev/null || echo "No configurado")
- Firewall: Habilitado con reglas HTTP/HTTPS
- SSL: Certificado autoasignado (reemplazar en producción)

SCRIPTS AUTOMÁTICOS:
- Respaldo diario: /usr/local/bin/backup-planilla-innova.sh (2:00 AM)
- Monitoreo: /usr/local/bin/monitor-planilla-innova.sh (cada 15 min)

CONFIGURACIÓN ESPECÍFICA CENTOS/RHEL:
- Repositorios: EPEL, Remi (PHP), MySQL Community
- SELinux contexts: Configurados para aplicación web
- Firewalld rules: HTTP (80), HTTPS (443), SSH (22)
- OpCache: Habilitado para mejor rendimiento
- Logrotate: Configurado para logs del sistema y Apache

PRÓXIMOS PASOS:
1. Cambiar contraseña por defecto
2. Configurar certificado SSL válido
3. Revisar configuración de SELinux si es necesario
4. Personalizar reglas de firewall según necesidades
5. Configurar monitoreo adicional con Nagios/Zabbix
6. Configurar respaldos remotos
7. Realizar pruebas de carga
8. Configurar logrotate adicional si es necesario

COMANDOS DE DIAGNÓSTICO:
- SELinux: ausearch -m AVC -ts recent
- Firewall: firewall-cmd --list-all
- Apache: systemctl status httpd -l
- MySQL: systemctl status mysqld -l
- Logs: journalctl -xe

Para soporte: Consulte documentación en $PROJECT_PATH/documentation/
EOF

echo -e "${GREEN}💾 Información detallada guardada en: /root/planilla-innova-install-info.txt${NC}"
echo -e "${GRAY}Ver información: cat /root/planilla-innova-install-info.txt${NC}"
echo ""

log_success "Instalación del Sistema de Planillas MVC completada exitosamente en $DISTRO $VERSION"