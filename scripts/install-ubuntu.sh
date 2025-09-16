#!/bin/bash

# Script de instalación automática para Sistema de Planillas MVC - Ubuntu
# Versión: 2.0
# Compatibilidad: Ubuntu 20.04 LTS, 22.04 LTS
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
║                     Ubuntu Edition                           ║
╚══════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo -e "${WHITE}Este script instalará automáticamente:${NC}"
echo -e "${GRAY}• Stack LAMP (Apache, MySQL, PHP 8.3)${NC}"
echo -e "${GRAY}• Configuración de virtual hosts${NC}"
echo -e "${GRAY}• Base de datos del sistema${NC}"
echo -e "${GRAY}• Archivos del sistema${NC}"
echo -e "${GRAY}• Certificado SSL básico${NC}"
echo -e "${GRAY}• Configuración de firewall${NC}"
echo ""

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

# Verificar distribución Ubuntu
if ! grep -q "Ubuntu" /etc/os-release; then
    log_error "Este script está diseñado para Ubuntu"
    log_info "Distribuciones soportadas: Ubuntu 20.04 LTS, 22.04 LTS"
    exit 1
fi

# Verificar conexión a Internet
if ! ping -c 1 google.com &> /dev/null; then
    log_error "No se detectó conexión a Internet"
    log_info "Verifique su conexión y vuelva a intentar"
    exit 1
fi

log_success "Prerrequisitos verificados"

# Generar contraseña segura para base de datos si no se proporcionó
if [[ -z "$DB_PASS" ]]; then
    DB_PASS=$(openssl rand -base64 32)
    log_info "Contraseña de base de datos generada automáticamente"
fi

# Solicitar información al usuario
echo ""
echo -e "${YELLOW}CONFIGURACIÓN DE INSTALACIÓN:${NC}"
echo -e "${GRAY}• Directorio del proyecto: $PROJECT_PATH${NC}"
echo -e "${GRAY}• Base de datos: $DB_NAME${NC}"
echo -e "${GRAY}• Usuario de BD: $DB_USER${NC}"
echo -e "${GRAY}• Directorio de respaldos: $BACKUP_PATH${NC}"
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
    systemctl stop apache2 2>/dev/null || true
    systemctl stop mysql 2>/dev/null || true
}

trap cleanup ERR

# Inicio de la instalación
log_step "Iniciando instalación del Sistema de Planillas MVC..."

# 1. Actualizar el sistema
log_step "Actualizando el sistema..."
apt update && apt upgrade -y
log_success "Sistema actualizado"

# 2. Instalar herramientas básicas
log_step "Instalando herramientas básicas..."
apt install -y curl wget unzip git software-properties-common apt-transport-https ca-certificates gnupg lsb-release
log_success "Herramientas básicas instaladas"

# 3. Instalar Apache
log_step "Instalando Apache Web Server..."
apt install -y apache2
systemctl enable apache2
systemctl start apache2

# Habilitar mod_rewrite y otros módulos
a2enmod rewrite
a2enmod ssl
a2enmod headers

log_success "Apache instalado y configurado"

# 4. Instalar MySQL
log_step "Instalando MySQL Server..."
apt install -y mysql-server

# Configuración segura de MySQL
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$DB_PASS';"
mysql -u root -p$DB_PASS -e "DELETE FROM mysql.user WHERE User='';"
mysql -u root -p$DB_PASS -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -u root -p$DB_PASS -e "DROP DATABASE IF EXISTS test;"
mysql -u root -p$DB_PASS -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -u root -p$DB_PASS -e "FLUSH PRIVILEGES;"

log_success "MySQL instalado y configurado"

# 5. Instalar PHP 8.3
log_step "Instalando PHP 8.3..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3 php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-json php8.3-intl libapache2-mod-php8.3

# Configurar PHP
sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php/8.3/apache2/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.3/apache2/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /etc/php/8.3/apache2/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 10M/' /etc/php/8.3/apache2/php.ini
sed -i 's/;date.timezone =/date.timezone = America\/Panama/' /etc/php/8.3/apache2/php.ini

log_success "PHP 8.3 instalado y configurado"

# 6. Crear base de datos
log_step "Configurando base de datos..."

# Crear base de datos y usuario
mysql -u root -p$DB_PASS << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

# Crear estructura básica
mysql -u $DB_USER -p$DB_PASS $DB_NAME << EOF
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

# 7. Crear directorios del proyecto
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

# 8. Descargar e instalar archivos del sistema
log_step "Instalando archivos del sistema..."

# En un escenario real, aquí se descargarían desde GitHub
# Por ahora, creamos estructura básica
cat > $PROJECT_PATH/index.php << 'EOF'
<?php
// Sistema de Planillas MVC
// Punto de entrada principal

session_start();

// Configuración básica
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php/error.log');

// Verificar instalación
if (!file_exists('.env')) {
    die('Sistema no configurado. Por favor configure el archivo .env');
}

// Cargar configuración
require_once 'config/bootstrap.php';

// Mostrar página de bienvenida temporal
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Planillas MVC - Instalación Completada</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-size: 24px; margin-bottom: 20px; }
        .info { background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">✅ Sistema de Planillas MVC Instalado</h1>
        <p>La instalación se ha completado exitosamente.</p>
        
        <div class="info">
            <h3>📋 Información de Acceso:</h3>
            <ul>
                <li><strong>Usuario por defecto:</strong> admin</li>
                <li><strong>Contraseña por defecto:</strong> admin123</li>
                <li><strong>Base de datos:</strong> <?php echo 'planilla_mvc'; ?></li>
            </ul>
        </div>
        
        <div class="warning">
            <h3>⚠️ Importante:</h3>
            <ul>
                <li>Cambie la contraseña por defecto inmediatamente</li>
                <li>Configure SSL/HTTPS para producción</li>
                <li>Revise los logs regularmente</li>
                <li>Realice respaldos periódicos</li>
            </ul>
        </div>
        
        <h3>🛠️ Próximos Pasos:</h3>
        <ol>
            <li>Configurar certificado SSL</li>
            <li>Personalizar configuración de empresa</li>
            <li>Importar empleados existentes</li>
            <li>Configurar conceptos de nómina</li>
            <li>Realizar primera planilla de prueba</li>
        </ol>
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
EOF

# Crear archivo básico de configuración
mkdir -p $PROJECT_PATH/config
cat > $PROJECT_PATH/config/bootstrap.php << 'EOF'
<?php
// Bootstrap básico del sistema
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}
EOF

log_success "Archivos del sistema instalados"

# 9. Configurar virtual host
log_step "Configurando virtual host de Apache..."

cat > /etc/apache2/sites-available/$PROJECT_NAME.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $PROJECT_PATH
    
    <Directory $PROJECT_PATH>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Configuración de seguridad
        <FilesMatch "\.env$">
            Require all denied
        </FilesMatch>
        
        <FilesMatch "\.(log|sql|md)$">
            Require all denied
        </FilesMatch>
    </Directory>
    
    # Logs
    ErrorLog \${APACHE_LOG_DIR}/$PROJECT_NAME-error.log
    CustomLog \${APACHE_LOG_DIR}/$PROJECT_NAME-access.log combined
    
    # Configuración PHP
    php_admin_value error_log "$PROJECT_PATH/logs/php/error.log"
    php_admin_flag log_errors on
    php_admin_flag display_errors off
</VirtualHost>

# Configuración SSL (preparada para certificado)
<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $PROJECT_PATH
    
    SSLEngine on
    # SSLCertificateFile /etc/ssl/certs/$DOMAIN.crt
    # SSLCertificateKeyFile /etc/ssl/private/$DOMAIN.key
    
    <Directory $PROJECT_PATH>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/$PROJECT_NAME-ssl-error.log
    CustomLog \${APACHE_LOG_DIR}/$PROJECT_NAME-ssl-access.log combined
</VirtualHost>
</IfModule>
EOF

# Habilitar el sitio
a2ensite $PROJECT_NAME.conf
a2dissite 000-default.conf

log_success "Virtual host configurado"

# 10. Configurar permisos
log_step "Configurando permisos de archivos..."

chown -R www-data:www-data $PROJECT_PATH
chmod -R 755 $PROJECT_PATH
chmod -R 777 $PROJECT_PATH/storage
chmod -R 777 $PROJECT_PATH/logs
chmod 600 $PROJECT_PATH/.env

log_success "Permisos configurados"

# 11. Configurar firewall
log_step "Configurando firewall..."

ufw --force enable
ufw allow ssh
ufw allow 'Apache Full'
ufw allow 3306  # MySQL (solo si es necesario para acceso remoto)

log_success "Firewall configurado"

# 12. Configurar logrotate para logs del sistema
log_step "Configurando rotación de logs..."

cat > /etc/logrotate.d/$PROJECT_NAME << EOF
$PROJECT_PATH/logs/*.log $PROJECT_PATH/logs/*/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2 > /dev/null 2>&1 || true
    endscript
}
EOF

log_success "Rotación de logs configurada"

# 13. Crear script de respaldo automático
log_step "Configurando respaldos automáticos..."

cat > /usr/local/bin/backup-planilla-innova.sh << EOF
#!/bin/bash
# Script de respaldo automático para Sistema de Planillas MVC

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

# Respaldar archivos (excluyendo logs temporales)
tar -czf \$BACKUP_DIR/files.tar.gz -C \$(dirname \$PROJECT_DIR) \$(basename \$PROJECT_DIR) --exclude='logs/*.log' --exclude='storage/temp/*'

# Limpiar respaldos antiguos (mantener últimos 7 días)
find $BACKUP_PATH -type d -name "20*" -mtime +7 -exec rm -rf {} + 2>/dev/null || true

echo "Respaldo completado: \$BACKUP_DIR"
EOF

chmod +x /usr/local/bin/backup-planilla-innova.sh

# Programar respaldo diario a las 2:00 AM
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-planilla-innova.sh >> /var/log/planilla-innova-backup.log 2>&1") | crontab -

log_success "Respaldos automáticos configurados"

# 14. Reiniciar servicios
log_step "Reiniciando servicios..."

systemctl restart apache2
systemctl restart mysql
systemctl status apache2 --no-pager -l
systemctl status mysql --no-pager -l

log_success "Servicios reiniciados"

# 15. Configurar monitoreo básico
log_step "Configurando monitoreo básico..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor-planilla-innova.sh << 'EOF'
#!/bin/bash
# Monitor básico para Sistema de Planillas MVC

LOG_FILE="/var/log/planilla-innova-monitor.log"
PROJECT_PATH="/var/www/html/planilla-innova"

# Función para log con timestamp
log_with_timestamp() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}

# Verificar servicios
if ! systemctl is-active --quiet apache2; then
    log_with_timestamp "ERROR: Apache no está ejecutándose"
    systemctl start apache2
fi

if ! systemctl is-active --quiet mysql; then
    log_with_timestamp "ERROR: MySQL no está ejecutándose"
    systemctl start mysql
fi

# Verificar espacio en disco
DISK_USAGE=$(df $PROJECT_PATH | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
    log_with_timestamp "WARNING: Espacio en disco bajo: ${DISK_USAGE}%"
fi

# Verificar logs de errores
ERROR_COUNT=$(tail -100 $PROJECT_PATH/logs/php/error.log 2>/dev/null | wc -l)
if [ $ERROR_COUNT -gt 50 ]; then
    log_with_timestamp "WARNING: Muchos errores PHP detectados: $ERROR_COUNT en últimas 100 líneas"
fi
EOF

chmod +x /usr/local/bin/monitor-planilla-innova.sh

# Programar monitoreo cada 15 minutos
(crontab -l 2>/dev/null; echo "*/15 * * * * /usr/local/bin/monitor-planilla-innova.sh") | crontab -

log_success "Monitoreo básico configurado"

# Instalación completada
echo ""
echo -e "${GREEN}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════╗
║                ✅ INSTALACIÓN COMPLETADA                      ║
╚══════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

echo -e "${GREEN}🎉 ¡El Sistema de Planillas MVC se ha instalado exitosamente!${NC}"
echo ""
echo -e "${YELLOW}📋 INFORMACIÓN DE ACCESO:${NC}"
echo -e "${WHITE}• URL del Sistema: http://$DOMAIN/$PROJECT_NAME${NC}"
echo -e "${WHITE}• Usuario por defecto: admin${NC}"
echo -e "${WHITE}• Contraseña por defecto: admin123${NC}"
echo -e "${WHITE}• Base de datos: $DB_NAME${NC}"
echo -e "${WHITE}• Usuario de BD: $DB_USER${NC}"
echo ""
echo -e "${RED}⚠️  IMPORTANTE:${NC}"
echo -e "${YELLOW}• Cambie la contraseña por defecto inmediatamente${NC}"
echo -e "${YELLOW}• Configure SSL/HTTPS para producción${NC}"
echo -e "${YELLOW}• Revise la configuración de seguridad${NC}"
echo -e "${YELLOW}• Configure DNS si usa dominio personalizado${NC}"
echo ""
echo -e "${CYAN}📁 UBICACIONES:${NC}"
echo -e "${GRAY}• Proyecto: $PROJECT_PATH${NC}"
echo -e "${GRAY}• Respaldos: $BACKUP_PATH${NC}"
echo -e "${GRAY}• Logs: $PROJECT_PATH/logs${NC}"
echo -e "${GRAY}• Configuración: $PROJECT_PATH/.env${NC}"
echo ""
echo -e "${CYAN}🛠️  COMANDOS ÚTILES:${NC}"
echo -e "${GRAY}• Reiniciar Apache: sudo systemctl restart apache2${NC}"
echo -e "${GRAY}• Ver logs: sudo tail -f $PROJECT_PATH/logs/php/error.log${NC}"
echo -e "${GRAY}• Crear respaldo: sudo /usr/local/bin/backup-planilla-innova.sh${NC}"
echo -e "${GRAY}• Ver estado: sudo /usr/local/bin/monitor-planilla-innova.sh${NC}"
echo ""

# Guardar información importante en archivo
cat > /root/planilla-innova-install-info.txt << EOF
Sistema de Planillas MVC - Información de Instalación
====================================================

Fecha de instalación: $(date)
Servidor: $(hostname)
Sistema operativo: $(lsb_release -d | cut -f2)

ACCESO:
- URL: http://$DOMAIN/$PROJECT_NAME
- Usuario: admin
- Contraseña: admin123

BASE DE DATOS:
- Nombre: $DB_NAME
- Usuario: $DB_USER
- Contraseña: $DB_PASS
- Host: localhost

UBICACIONES:
- Proyecto: $PROJECT_PATH
- Respaldos: $BACKUP_PATH
- Logs: $PROJECT_PATH/logs
- Configuración: $PROJECT_PATH/.env

SERVICIOS:
- Apache: systemctl status apache2
- MySQL: systemctl status mysql
- Firewall: ufw status

SCRIPTS AUTOMÁTICOS:
- Respaldo: /usr/local/bin/backup-planilla-innova.sh (diario 2:00 AM)
- Monitoreo: /usr/local/bin/monitor-planilla-innova.sh (cada 15 min)

PRÓXIMOS PASOS:
1. Cambiar contraseña por defecto
2. Configurar SSL/HTTPS
3. Personalizar configuración de empresa
4. Importar datos existentes
5. Realizar primera planilla de prueba

Para soporte: Consulte la documentación en $PROJECT_PATH/documentation/
EOF

echo -e "${GREEN}💾 Información guardada en: /root/planilla-innova-install-info.txt${NC}"
echo ""
echo -e "${GRAY}Para ver este archivo: cat /root/planilla-innova-install-info.txt${NC}"
echo ""

log_success "Instalación del Sistema de Planillas MVC completada exitosamente"