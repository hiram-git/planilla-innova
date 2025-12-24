#!/bin/bash
# Script para crear archivos de migración con timestamp correcto
# Uso: ./scripts/create-migration.sh "descripcion_de_la_migracion"

# Colores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar que se proporcionó una descripción
if [ -z "$1" ]; then
    echo -e "${YELLOW}Uso: $0 \"descripcion_de_la_migracion\"${NC}"
    echo ""
    echo "Ejemplo:"
    echo "  $0 \"add_creditor_to_loans\""
    echo "  $0 \"create_payments_table\""
    exit 1
fi

# Limpiar descripción (remover espacios, convertir a lowercase, solo alfanuméricos y guiones bajos)
DESCRIPTION=$(echo "$1" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9_]/_/g' | sed 's/__*/_/g')

# Generar timestamp en formato YYYY_MM_DD_HHMMSS
TIMESTAMP=$(date +%Y_%m_%d_%H%M%S)

# Nombre del archivo
FILENAME="${TIMESTAMP}_${DESCRIPTION}.sql"
FILEPATH="database/migrations/${FILENAME}"

# Crear directorio si no existe
mkdir -p database/migrations

# Template de la migración
cat > "$FILEPATH" << EOF
-- Migración: ${DESCRIPTION}
-- Fecha: $(date +"%Y-%m-%d %H:%M:%S")
-- Autor: $(git config user.name 2>/dev/null || echo "Sistema")
-- Descripción: [Agregar descripción detallada aquí]

-- ==== UP Migration ====

-- Tu código SQL aquí
-- Ejemplo:
-- CREATE TABLE IF NOT EXISTS nueva_tabla (
--   id INT AUTO_INCREMENT PRIMARY KEY,
--   nombre VARCHAR(100) NOT NULL,
--   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ALTER TABLE tabla_existente
-- ADD COLUMN IF NOT EXISTS nueva_columna VARCHAR(50) NULL
-- COMMENT 'Descripción de la columna';


-- ==== DOWN Migration (Rollback) ====
-- Para revertir manualmente si es necesario:

-- DROP TABLE IF EXISTS nueva_tabla;
-- ALTER TABLE tabla_existente DROP COLUMN IF EXISTS nueva_columna;
EOF

# Confirmación
echo -e "${GREEN}✓ Migración creada exitosamente:${NC}"
echo -e "${BLUE}  $FILEPATH${NC}"
echo ""
echo "Siguiente paso:"
echo "  1. Editar el archivo y agregar tu código SQL"
echo "  2. Probar la migración en una BD de prueba"
echo "  3. Commit y push cuando esté listo"
echo ""
echo "Abrir en editor:"
echo "  code $FILEPATH  # VS Code"
echo "  nano $FILEPATH  # Terminal"
