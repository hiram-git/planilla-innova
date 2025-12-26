#!/bin/bash
#
# Script: Fix de Permisos para Directorios de Logs
# Fecha: 26-Dic-2025
# Descripción: Corrige permisos de directorios storage/, cache/, y logs externos
#

set -e  # Salir si hay error

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   FIX: Permisos de Logs y Directorios de Escritura            ║${NC}"
echo -e "${BLUE}║   Fecha: $(date '+%Y-%m-%d %H:%M:%S')                                  ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Detectar usuario web server
if id "www-data" &>/dev/null; then
    WEB_USER="www-data"
    WEB_GROUP="www-data"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
    WEB_GROUP="apache"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
    WEB_GROUP="nginx"
else
    echo -e "${YELLOW}⚠️  No se pudo detectar el usuario del web server${NC}"
    echo -e "${YELLOW}   Usando usuario actual: $(whoami)${NC}"
    WEB_USER=$(whoami)
    WEB_GROUP=$(id -gn)
fi

echo -e "${GREEN}✓ Usuario web server detectado: ${WEB_USER}:${WEB_GROUP}${NC}"
echo ""

# Función para crear directorio si no existe
create_dir_if_not_exists() {
    local dir=$1
    if [ ! -d "$dir" ]; then
        echo -e "${YELLOW}📁 Creando directorio: ${dir}${NC}"
        mkdir -p "$dir"
    else
        echo -e "${GREEN}✓ Directorio existe: ${dir}${NC}"
    fi
}

# Función para aplicar permisos
apply_permissions() {
    local dir=$1
    local owner=$2
    local perms=$3

    echo -e "${BLUE}🔧 Aplicando permisos a: ${dir}${NC}"

    # Cambiar propietario
    if [ -d "$dir" ]; then
        chown -R $owner "$dir"
        chmod -R $perms "$dir"
        echo -e "${GREEN}   ✓ Owner: ${owner} | Permisos: ${perms}${NC}"
    else
        echo -e "${RED}   ❌ Directorio no existe: ${dir}${NC}"
    fi
}

echo -e "${BLUE}═══ PASO 1: Crear directorios faltantes ═══${NC}"

# Directorios del proyecto
create_dir_if_not_exists "storage"
create_dir_if_not_exists "storage/logs"
create_dir_if_not_exists "storage/cache"
create_dir_if_not_exists "storage/uploads"
create_dir_if_not_exists "storage/temp"

# Directorios externos de logs
create_dir_if_not_exists "/var/log/planilla"

echo ""
echo -e "${BLUE}═══ PASO 2: Aplicar permisos a directorios del proyecto ═══${NC}"

# Storage y subdirectorios (775 - lectura/escritura para grupo)
apply_permissions "storage" "${WEB_USER}:${WEB_GROUP}" "775"
apply_permissions "storage/logs" "${WEB_USER}:${WEB_GROUP}" "775"
apply_permissions "storage/cache" "${WEB_USER}:${WEB_GROUP}" "775"
apply_permissions "storage/uploads" "${WEB_USER}:${WEB_GROUP}" "775"
apply_permissions "storage/temp" "${WEB_USER}:${WEB_GROUP}" "775"

echo ""
echo -e "${BLUE}═══ PASO 3: Aplicar permisos a logs externos ═══${NC}"

# Logs externos (755 - lectura para otros)
if [ -d "/var/log/planilla" ]; then
    apply_permissions "/var/log/planilla" "${WEB_USER}:${WEB_GROUP}" "755"
fi

echo ""
echo -e "${BLUE}═══ PASO 4: Crear archivos .gitkeep para preservar estructura ═══${NC}"

# Crear .gitkeep en directorios vacíos
touch storage/logs/.gitkeep
touch storage/cache/.gitkeep
touch storage/uploads/.gitkeep
touch storage/temp/.gitkeep

echo -e "${GREEN}✓ Archivos .gitkeep creados${NC}"

echo ""
echo -e "${BLUE}═══ PASO 5: Verificar permisos aplicados ═══${NC}"

echo -e "\n${YELLOW}📊 Permisos de storage/:${NC}"
ls -la storage/

echo -e "\n${YELLOW}📊 Permisos de storage/logs/:${NC}"
ls -la storage/logs/ | head -10

if [ -d "/var/log/planilla" ]; then
    echo -e "\n${YELLOW}📊 Permisos de /var/log/planilla/:${NC}"
    ls -la /var/log/planilla/ | head -10
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              PERMISOS APLICADOS EXITOSAMENTE                   ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}📋 Resumen:${NC}"
echo -e "  - Usuario web: ${WEB_USER}:${WEB_GROUP}"
echo -e "  - Permisos storage/: 775 (rwxrwxr-x)"
echo -e "  - Permisos /var/log/planilla/: 755 (rwxr-xr-x)"
echo ""
echo -e "${YELLOW}🧪 Probar escritura:${NC}"
echo -e "  ${BLUE}sudo -u ${WEB_USER} touch storage/logs/test.log && echo 'OK' || echo 'FAIL'${NC}"
echo ""
