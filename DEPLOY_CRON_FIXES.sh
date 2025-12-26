#!/bin/bash
#
# Script de Deployment: Aplicar Fixes para Cron Jobs
# Fecha: 26-Dic-2025
# Versión: 3.5.14
#
# Este script sube todos los archivos modificados/creados para solucionar
# los 4 errores críticos detectados en el test de producción
#

set -e  # Salir si hay error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables
SERVER="root@tu_servidor"
REMOTE_PATH="/var/www/html/planilla"

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   DEPLOYMENT: Fixes para Cron Jobs - Versión 3.5.14           ║${NC}"
echo -e "${BLUE}║   Fecha: $(date '+%Y-%m-%d %H:%M:%S')                                  ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Verificar que el usuario configure el servidor
if [ "$SERVER" == "root@tu_servidor" ]; then
    echo -e "${RED}❌ ERROR: Debes configurar la variable SERVER en este script${NC}"
    echo -e "${YELLOW}   Editar línea 18 con tu servidor real: SERVER=\"root@IP_O_DOMINIO\"${NC}"
    exit 1
fi

# Función para subir archivo
upload_file() {
    local file=$1
    local remote_file="${REMOTE_PATH}/${file}"

    if [ -f "$file" ]; then
        echo -e "${YELLOW}📤 Subiendo: ${file}${NC}"
        scp "$file" "${SERVER}:${remote_file}"
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}   ✓ Subido exitosamente${NC}"
        else
            echo -e "${RED}   ❌ Error subiendo archivo${NC}"
            return 1
        fi
    else
        echo -e "${RED}❌ Archivo no encontrado: ${file}${NC}"
        return 1
    fi
}

# Crear respaldo en servidor
echo -e "\n${BLUE}═══ PASO 1: Crear respaldo de archivos originales ═══${NC}"
ssh $SERVER << 'ENDSSH'
    cd /var/www/html/planilla
    BACKUP_DIR="backups/cron_fixes_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"

    # Backup de archivos que serán modificados
    if [ -f "app/Core/Database.php" ]; then
        cp app/Core/Database.php "$BACKUP_DIR/"
        echo "✓ Backup: Database.php"
    fi

    if [ -f "scripts/cron/sync_attendance.php" ]; then
        cp scripts/cron/sync_attendance.php "$BACKUP_DIR/"
        echo "✓ Backup: sync_attendance.php"
    fi

    echo "✅ Respaldo completado en: $BACKUP_DIR"
ENDSSH

# Subir archivos modificados
echo -e "\n${BLUE}═══ PASO 2: Subir archivos modificados ═══${NC}"

# Fix 1 y 2: Variables de entorno
upload_file "scripts/cron/sync_attendance.php"
upload_file "scripts/cron/end_of_day_processing.php"

# Fix 1: Rutas absolutas en Database.php
upload_file "app/Core/Database.php"

# Fix 3 y 4: Alias para clases en subdirectorio
upload_file "app/Services/Attendance/AttendanceCalculator.php"
upload_file "app/Services/Attendance/AbsenceDetector.php"

# Scripts de prueba
upload_file "scripts/cron/test_cron_setup.php"

echo -e "\n${BLUE}═══ PASO 3: Dar permisos de ejecución ═══${NC}"
ssh $SERVER << 'ENDSSH'
    cd /var/www/html/planilla
    chmod +x scripts/cron/*.php
    echo "✓ Permisos de ejecución aplicados"
ENDSSH

echo -e "\n${BLUE}═══ PASO 3.5: Corregir permisos de directorios de logs ═══${NC}"
ssh $SERVER << 'ENDSSH'
    cd /var/www/html/planilla

    # Detectar usuario web server
    if id "www-data" &>/dev/null; then
        WEB_USER="www-data"
    elif id "apache" &>/dev/null; then
        WEB_USER="apache"
    else
        WEB_USER=$(whoami)
    fi

    # Crear directorios si no existen
    mkdir -p storage/logs
    mkdir -p /var/log/planilla

    # Aplicar permisos
    chown -R $WEB_USER:$WEB_USER storage/
    chmod -R 775 storage/
    chown -R $WEB_USER:$WEB_USER /var/log/planilla/
    chmod -R 755 /var/log/planilla/

    echo "✓ Permisos de logs aplicados para usuario: $WEB_USER"
    echo "  - storage/logs/: 775 (rwxrwxr-x)"
    echo "  - /var/log/planilla/: 755 (rwxr-xr-x)"
ENDSSH

echo -e "\n${BLUE}═══ PASO 4: Ejecutar test de validación ═══${NC}"
ssh $SERVER << 'ENDSSH'
    cd /var/www/html/planilla
    php scripts/cron/test_cron_setup.php
ENDSSH

echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                  DEPLOYMENT COMPLETADO                         ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📋 Próximos pasos:${NC}"
echo -e "  1. Revisar output del test (arriba)"
echo -e "  2. Si el test pasa (0 errores), probar sincronización:"
echo -e "     ${BLUE}ssh $SERVER${NC}"
echo -e "     ${BLUE}php /var/www/html/planilla/scripts/cron/sync_attendance.php${NC}"
echo -e "  3. Configurar crontab:"
echo -e "     ${BLUE}crontab -e${NC}"
echo ""
