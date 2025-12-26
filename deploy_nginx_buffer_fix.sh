#!/bin/bash

# Script para aplicar fix de buffers FastCGI en Nginx
# Uso: ./deploy_nginx_buffer_fix.sh usuario@servidor

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Verificar argumentos
if [ -z "$1" ]; then
    echo -e "${RED}Error: Debes especificar el servidor${NC}"
    echo "Uso: $0 usuario@servidor"
    echo "Ejemplo: $0 root@planilla.innovasoftlatam.com"
    exit 1
fi

SERVER=$1
NGINX_SITE_CONFIG="/etc/nginx/sites-available/planilla.innovasoftlatam.com"
REMOTE_APP_PATH="/var/www/html/planilla"

echo -e "${BLUE}=== Fix Nginx 'upstream sent too big header' ===${NC}"
echo "Servidor: $SERVER"
echo ""

# Paso 1: Subir archivo Model.php actualizado
echo -e "${YELLOW}Paso 1/4: Subiendo archivo Model.php actualizado...${NC}"
scp app/Core/Model.php "$SERVER:$REMOTE_APP_PATH/app/Core/"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Archivo Model.php subido${NC}"
else
    echo -e "${RED}✗ Error al subir Model.php${NC}"
    exit 1
fi

echo ""

# Paso 2: Backup configuración Nginx
echo -e "${YELLOW}Paso 2/4: Creando backup de configuración Nginx...${NC}"
ssh "$SERVER" "sudo cp $NGINX_SITE_CONFIG ${NGINX_SITE_CONFIG}.backup.\$(date +%Y%m%d_%H%M%S)"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Backup creado${NC}"
else
    echo -e "${RED}✗ Error al crear backup${NC}"
    exit 1
fi

echo ""

# Paso 3: Aplicar configuración Nginx
echo -e "${YELLOW}Paso 3/4: Aplicando configuración de buffers FastCGI...${NC}"
ssh "$SERVER" "sudo bash -c 'cat >> $NGINX_SITE_CONFIG <<EOF

    # Fix: upstream sent too big header (24-Dic-2024)
    # Aumentar buffers para FastCGI
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_busy_buffers_size 32k;

EOF
'"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Configuración agregada${NC}"
else
    echo -e "${RED}✗ Error al agregar configuración${NC}"
    exit 1
fi

echo ""

# Paso 4: Verificar y recargar Nginx
echo -e "${YELLOW}Paso 4/4: Verificando sintaxis y recargando Nginx...${NC}"

# Verificar sintaxis
ssh "$SERVER" "sudo nginx -t"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Sintaxis correcta${NC}"

    # Recargar Nginx
    echo -e "${YELLOW}Recargando Nginx...${NC}"
    ssh "$SERVER" "sudo systemctl reload nginx"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Nginx recargado exitosamente${NC}"
    else
        echo -e "${RED}✗ Error al recargar Nginx${NC}"
        echo -e "${YELLOW}Restaurando backup...${NC}"
        ssh "$SERVER" "sudo cp ${NGINX_SITE_CONFIG}.backup.* $NGINX_SITE_CONFIG && sudo systemctl reload nginx"
        exit 1
    fi
else
    echo -e "${RED}✗ Error de sintaxis en configuración Nginx${NC}"
    echo -e "${YELLOW}Restaurando backup...${NC}"
    ssh "$SERVER" "sudo cp ${NGINX_SITE_CONFIG}.backup.* $NGINX_SITE_CONFIG"
    exit 1
fi

echo ""
echo -e "${GREEN}=== Deployment completado exitosamente ===${NC}"
echo ""
echo -e "${BLUE}Próximos pasos:${NC}"
echo "1. Verificar logs de error: sudo tail -f /var/log/nginx/error.log"
echo "2. Probar edición de empleados en /panel/employees/edit/25"
echo "3. Verificar que no aparezcan errores 'upstream sent too big header'"
echo ""
echo -e "${YELLOW}Si necesitas revertir cambios:${NC}"
echo "ssh $SERVER"
echo "sudo cp ${NGINX_SITE_CONFIG}.backup.* $NGINX_SITE_CONFIG"
echo "sudo systemctl reload nginx"
echo ""
