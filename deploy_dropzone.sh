#!/bin/bash

# Script de despliegue de Dropzone a producción
# Uso: ./deploy_dropzone.sh usuario@servidor

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar argumentos
if [ -z "$1" ]; then
    echo -e "${RED}Error: Debes especificar el servidor${NC}"
    echo "Uso: $0 usuario@servidor"
    echo "Ejemplo: $0 root@192.168.1.100"
    exit 1
fi

SERVER=$1
REMOTE_PATH="/var/www/html/planilla/plugins/"
LOCAL_PATH="./plugins/dropzone"

echo -e "${YELLOW}=== Despliegue de Dropzone ===${NC}"
echo "Servidor: $SERVER"
echo "Ruta remota: $REMOTE_PATH"
echo ""

# Verificar que exista el directorio local
if [ ! -d "$LOCAL_PATH" ]; then
    echo -e "${RED}Error: No se encuentra el directorio $LOCAL_PATH${NC}"
    exit 1
fi

# Verificar archivos críticos
echo -e "${YELLOW}Verificando archivos locales...${NC}"
REQUIRED_FILES=(
    "min/dropzone.min.css"
    "min/dropzone.min.js"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$LOCAL_PATH/$file" ]; then
        echo -e "${RED}Error: Falta el archivo $file${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓${NC} $file"
done

echo ""
echo -e "${YELLOW}Subiendo archivos al servidor...${NC}"

# Usar rsync para subir archivos
rsync -avz --progress \
    --exclude="*.map" \
    --exclude=".git" \
    "$LOCAL_PATH/" \
    "$SERVER:$REMOTE_PATH/dropzone/"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓ Archivos subidos exitosamente${NC}"

    # Establecer permisos correctos
    echo ""
    echo -e "${YELLOW}Configurando permisos...${NC}"
    ssh "$SERVER" "chmod -R 755 $REMOTE_PATH/dropzone/ && chown -R www-data:www-data $REMOTE_PATH/dropzone/"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Permisos configurados${NC}"
    else
        echo -e "${YELLOW}⚠ No se pudieron configurar los permisos (puede requerir sudo)${NC}"
    fi

    # Verificar archivos en servidor
    echo ""
    echo -e "${YELLOW}Verificando archivos en servidor...${NC}"
    ssh "$SERVER" "ls -lh $REMOTE_PATH/dropzone/min/"

    echo ""
    echo -e "${GREEN}=== Despliegue completado ===${NC}"
    echo ""
    echo "Prueba la siguiente URL en tu navegador:"
    echo "https://tu-dominio.com/planilla/plugins/dropzone/min/dropzone.min.css"
    echo ""
    echo "También verifica el módulo de empresa:"
    echo "https://tu-dominio.com/planilla/panel/company"

else
    echo ""
    echo -e "${RED}✗ Error al subir archivos${NC}"
    exit 1
fi
