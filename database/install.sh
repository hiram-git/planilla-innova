#!/bin/bash

# Script de Instalación de Base de Datos Limpia
# Sistema de Planillas MVC v2.1
# Compatible con: Linux, macOS, Windows (WSL/Git Bash)

# Configuración de colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Funciones para mensajes
show_step() {
    echo -e "${CYAN}🔄 $1${NC}"
}

show_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

show_error() {
    echo -e "${RED}❌ $1${NC}"
}

show_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

show_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Header
clear
echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║              🗃️  INSTALADOR BASE DE DATOS LIMPIA              ║"
echo "║                 Sistema de Planillas MVC v2.1                ║"
echo "║                      Bash Edition                            ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Variables
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
ENV_FILE="$SCRIPT_DIR/../.env"
SCHEMA_FILE="$SCRIPT_DIR/schema.sql"
PHP_SCRIPT="$SCRIPT_DIR/install.php"

# 1. Verificar prerrequisitos
show_step "Verificando prerrequisitos del sistema..."

# Verificar PHP
if ! command -v php &> /dev/null; then
    show_error "PHP no está instalado o no está en PATH"
    show_info "Instale PHP 8.0+ antes de continuar"
    exit 1
fi

# Verificar MySQL cliente
if ! command -v mysql &> /dev/null; then
    show_error "Cliente MySQL no está instalado o no está en PATH"
    show_info "Instale mysql-client antes de continuar"
    exit 1
fi

show_success "Prerrequisitos verificados"

# 2. Verificar archivos necesarios
show_step "Verificando archivos del sistema..."

if [ ! -f "$ENV_FILE" ]; then
    show_error "Archivo .env no encontrado en: $ENV_FILE"
    show_info "Ejecute la instalación del sistema primero"
    exit 1
fi

if [ ! -f "$SCHEMA_FILE" ]; then
    show_error "Archivo schema.sql no encontrado en: $SCHEMA_FILE"
    show_info "Verifique que todos los archivos del sistema estén presentes"
    exit 1
fi

show_success "Archivos del sistema verificados"

# 3. Cargar configuración .env
show_step "Cargando configuración del sistema..."

# Función para leer .env
load_env() {
    if [ -f "$ENV_FILE" ]; then
        # Leer variables ignorando comentarios y líneas vacías
        while IFS='=' read -r key value
        do
            # Ignorar comentarios y líneas vacías
            [[ $key =~ ^#.*$ ]] && continue
            [[ -z $key ]] && continue
            
            # Remover comillas
            value=$(echo "$value" | sed 's/^"//' | sed 's/"$//')
            
            # Exportar variable
            export "$key"="$value"
        done < "$ENV_FILE"
    fi
}

load_env
show_success "Configuración cargada exitosamente"

# 4. Configurar variables de base de datos
DB_HOST="${DB_HOST:-localhost}"
DB_DATABASE="${DB_DATABASE:-planilla}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

show_info "Configuración de base de datos:"
show_info "• Host: $DB_HOST"
show_info "• Base de datos: $DB_DATABASE"
show_info "• Usuario: $DB_USERNAME"
echo ""

# 5. Confirmar instalación
read -p "¿Desea continuar con la instalación de la base de datos? (y/N): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    show_warning "Instalación cancelada por el usuario"
    exit 0
fi

# 6. Método de instalación
echo ""
show_info "Seleccione el método de instalación:"
echo "1) Usar PHP (Recomendado - más robusto)"
echo "2) Usar MySQL directamente (Rápido)"
echo ""
read -p "Seleccione una opción (1-2): " METHOD

case $METHOD in
    1)
        show_step "Ejecutando instalación vía PHP..."
        
        if [ -f "$PHP_SCRIPT" ]; then
            php "$PHP_SCRIPT"
            INSTALL_RESULT=$?
        else
            show_error "Script PHP de instalación no encontrado"
            exit 1
        fi
        ;;
    2)
        show_step "Ejecutando instalación vía MySQL..."
        
        # Construir comando MySQL
        MYSQL_CMD="mysql -h $DB_HOST -u $DB_USERNAME"
        if [ ! -z "$DB_PASSWORD" ]; then
            MYSQL_CMD="$MYSQL_CMD -p$DB_PASSWORD"
        fi
        
        # Crear base de datos
        show_step "Creando base de datos '$DB_DATABASE'..."
        echo "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | $MYSQL_CMD
        
        if [ $? -ne 0 ]; then
            show_error "Error al crear la base de datos"
            exit 1
        fi
        
        # Ejecutar schema
        show_step "Instalando estructura de la base de datos..."
        $MYSQL_CMD "$DB_DATABASE" < "$SCHEMA_FILE"
        
        if [ $? -ne 0 ]; then
            show_error "Error al instalar el esquema de la base de datos"
            exit 1
        fi
        
        # Insertar datos iniciales
        show_step "Insertando datos iniciales..."
        
        # Crear archivo temporal con datos iniciales
        TEMP_DATA=$(mktemp)
        cat > "$TEMP_DATA" << 'EOF'
-- Insertar roles por defecto
INSERT IGNORE INTO roles (id, name, description, created_at) VALUES
(1, 'Super Admin', 'Administrador con acceso completo al sistema', NOW()),
(2, 'Usuario', 'Usuario estándar con permisos limitados', NOW()),
(3, 'Solo Lectura', 'Usuario con permisos únicamente de consulta', NOW());

-- Insertar usuario administrador por defecto (password: admin123)
INSERT IGNORE INTO users (id, username, email, password, role_id, active, created_at) VALUES
(1, 'admin', 'admin@planilla-innova.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, NOW());

-- Insertar configuración básica de la empresa
INSERT IGNORE INTO companies (id, name, address, phone, email, tax_id, created_at) VALUES
(1, 'Sistema de Planillas MVC', 'Dirección de la empresa', '000-0000', 'contacto@empresa.com', '0000000000', NOW());

-- Insertar tipos de conceptos básicos
INSERT IGNORE INTO concept_types (id, name, description, created_at) VALUES
(1, 'Salario', 'Salario base del empleado', NOW()),
(2, 'Horas Extra', 'Pago por horas adicionales trabajadas', NOW()),
(3, 'Bono', 'Bonificaciones y incentivos', NOW()),
(4, 'Deducción', 'Descuentos aplicados al salario', NOW());

-- Insertar conceptos básicos
INSERT IGNORE INTO concepts (id, code, name, concept_type_id, is_active, created_at) VALUES
(1, 'SAL_BASE', 'Salario Base', 1, 1, NOW()),
(2, 'HRS_EXTRA', 'Horas Extras', 2, 1, NOW()),
(3, 'BONO_PROD', 'Bono de Productividad', 3, 1, NOW()),
(4, 'DED_SS', 'Deducción Seguro Social', 4, 1, NOW()),
(5, 'DED_IR', 'Deducción Impuesto sobre la Renta', 4, 1, NOW());

-- Insertar situaciones laborales
INSERT IGNORE INTO employment_situations (id, name, description, is_active, created_at) VALUES
(1, 'Activo', 'Empleado activo en la empresa', 1, NOW()),
(2, 'Suspendido', 'Empleado temporalmente suspendido', 1, NOW()),
(3, 'Vacaciones', 'Empleado en período de vacaciones', 1, NOW()),
(4, 'Incapacidad', 'Empleado con incapacidad médica', 1, NOW()),
(5, 'Retirado', 'Ex-empleado retirado de la empresa', 1, NOW());

-- Insertar frecuencias de pago
INSERT IGNORE INTO payment_frequencies (id, name, description, days, created_at) VALUES
(1, 'Quincenal', 'Pago cada 15 días', 15, NOW()),
(2, 'Mensual', 'Pago mensual', 30, NOW()),
(3, 'Semanal', 'Pago semanal', 7, NOW());
EOF
        
        $MYSQL_CMD "$DB_DATABASE" < "$TEMP_DATA"
        rm "$TEMP_DATA"
        
        INSTALL_RESULT=$?
        ;;
    *)
        show_error "Opción inválida"
        exit 1
        ;;
esac

# 7. Verificar resultado
if [ $INSTALL_RESULT -eq 0 ]; then
    echo ""
    show_success "╔══════════════════════════════════════════════════════════════╗"
    show_success "║              ✅ INSTALACIÓN COMPLETADA                        ║"
    show_success "╚══════════════════════════════════════════════════════════════╝"
    echo ""
    
    show_success "🎉 ¡La base de datos se instaló exitosamente!"
    echo ""
    show_warning "📋 INFORMACIÓN DE ACCESO:"
    show_info "• Usuario por defecto: admin"
    show_info "• Contraseña por defecto: admin123"
    show_info "• Base de datos: $DB_DATABASE"
    echo ""
    show_success "🆕 NOVEDADES VERSIÓN 2.1:"
    show_info "• Filtrado por tipo de planilla mejorado"
    show_info "• Validación de empleados antes de procesamiento"
    show_info "• Gestión de acreedores con validaciones de seguridad"
    show_info "• Corrección de errores de JavaScript en formularios"
    echo ""
    show_error "⚠️  IMPORTANTE:"
    show_warning "• Cambie la contraseña por defecto inmediatamente"
    show_warning "• Configure los datos de la empresa en el panel"
    show_warning "• Revise los permisos de usuarios según sus necesidades"
    show_warning "• Verifique la configuración de moneda en configuración empresa"
    echo ""
    show_success "🚀 Sistema listo para usar!"
    
else
    show_error "La instalación falló con código de error: $INSTALL_RESULT"
    echo ""
    show_warning "🔧 PASOS PARA RESOLUCIÓN:"
    show_info "1. Verifique que el archivo .env tiene la configuración correcta"
    show_info "2. Asegúrese de que MySQL/MariaDB está ejecutándose"
    show_info "3. Verifique las credenciales de la base de datos"
    show_info "4. Confirme que el usuario de BD tiene permisos para crear databases"
    show_info "5. Ejecute el script nuevamente"
    exit 1
fi