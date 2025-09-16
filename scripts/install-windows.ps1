#Requires -RunAsAdministrator

<#
.SYNOPSIS
    Script de instalación automática para Sistema de Planillas MVC - Windows
.DESCRIPTION
    Este script automatiza la instalación completa del sistema en Windows Server 2019/2022 o Windows 10/11
.AUTHOR
    Sistema de Planillas MVC Team
.VERSION
    2.0
#>

# Configuración de colores para output
$Host.UI.RawUI.BackgroundColor = "Black"
$Host.UI.RawUI.ForegroundColor = "White"

function Write-ColoredOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

function Write-Step {
    param([string]$Message)
    Write-ColoredOutput "🔄 $Message" "Cyan"
}

function Write-Success {
    param([string]$Message)
    Write-ColoredOutput "✅ $Message" "Green"
}

function Write-Error {
    param([string]$Message)
    Write-ColoredOutput "❌ $Message" "Red"
}

function Write-Warning {
    param([string]$Message)
    Write-ColoredOutput "⚠️  $Message" "Yellow"
}

function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Test-InternetConnection {
    try {
        $response = Invoke-WebRequest -Uri "https://www.google.com" -UseBasicParsing -TimeoutSec 10
        return $response.StatusCode -eq 200
    }
    catch {
        return $false
    }
}

# Header
Clear-Host
Write-ColoredOutput @"
╔══════════════════════════════════════════════════════════════╗
║                    🚀 INSTALADOR AUTOMÁTICO                   ║
║              Sistema de Planillas MVC v2.0                  ║
║                     Windows Edition                          ║
╚══════════════════════════════════════════════════════════════╝
"@ "Magenta"

Write-ColoredOutput ""
Write-ColoredOutput "Este script instalará automáticamente:" "White"
Write-ColoredOutput "• XAMPP (Apache, MySQL, PHP 8.3)" "Gray"
Write-ColoredOutput "• Configuración de virtual hosts" "Gray"
Write-ColoredOutput "• Base de datos del sistema" "Gray"
Write-ColoredOutput "• Archivos del sistema" "Gray"
Write-ColoredOutput "• Configuración de permisos" "Gray"
Write-ColoredOutput ""

# Verificaciones previas
Write-Step "Verificando prerrequisitos..."

if (-not (Test-Administrator)) {
    Write-Error "Este script debe ejecutarse como Administrador"
    Write-ColoredOutput "Haga clic derecho en PowerShell y seleccione 'Ejecutar como administrador'" "Yellow"
    pause
    exit 1
}

if (-not (Test-InternetConnection)) {
    Write-Error "No se detectó conexión a Internet"
    Write-ColoredOutput "Verifique su conexión y vuelva a intentar" "Yellow"
    pause
    exit 1
}

Write-Success "Prerrequisitos verificados"

# Configuración
$XAMPP_URL = "https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe"
$XAMPP_INSTALLER = "$env:TEMP\xampp-installer.exe"
$XAMPP_PATH = "C:\xampp"
$PROJECT_PATH = "$XAMPP_PATH\htdocs\planilla-innova"
$BACKUP_PATH = "C:\PlanillaMVC-Backup"

# Confirmación del usuario
Write-ColoredOutput ""
Write-ColoredOutput "CONFIGURACIÓN DE INSTALACIÓN:" "Yellow"
Write-ColoredOutput "• Directorio XAMPP: $XAMPP_PATH" "Gray"
Write-ColoredOutput "• Directorio del proyecto: $PROJECT_PATH" "Gray"
Write-ColoredOutput "• Directorio de respaldos: $BACKUP_PATH" "Gray"
Write-ColoredOutput ""

$confirm = Read-Host "¿Desea continuar con la instalación? (Y/N)"
if ($confirm -notmatch '^[Yy]$') {
    Write-ColoredOutput "Instalación cancelada por el usuario" "Yellow"
    pause
    exit 0
}

try {
    # 1. Descargar XAMPP
    Write-Step "Descargando XAMPP..."
    if (Test-Path $XAMPP_INSTALLER) {
        Remove-Item $XAMPP_INSTALLER -Force
    }
    
    $progressPreference = 'silentlyContinue'
    Invoke-WebRequest -Uri $XAMPP_URL -OutFile $XAMPP_INSTALLER
    $progressPreference = 'Continue'
    
    if (-not (Test-Path $XAMPP_INSTALLER)) {
        throw "Error al descargar XAMPP"
    }
    
    Write-Success "XAMPP descargado exitosamente"

    # 2. Instalar XAMPP
    Write-Step "Instalando XAMPP (esto puede tomar varios minutos)..."
    $process = Start-Process -FilePath $XAMPP_INSTALLER -ArgumentList "--mode", "unattended", "--launchapps", "0" -Wait -PassThru
    
    if ($process.ExitCode -ne 0) {
        Write-Warning "XAMPP puede haberse instalado con advertencias. Continuando..."
    }
    
    # Verificar instalación
    if (-not (Test-Path "$XAMPP_PATH\apache\bin\httpd.exe")) {
        throw "XAMPP no se instaló correctamente"
    }
    
    Write-Success "XAMPP instalado exitosamente"

    # 3. Configurar servicios XAMPP
    Write-Step "Configurando servicios de XAMPP..."
    
    # Instalar servicios
    Start-Process -FilePath "$XAMPP_PATH\apache\bin\httpd.exe" -ArgumentList "-k", "install" -Wait
    Start-Process -FilePath "$XAMPP_PATH\mysql\bin\mysqld.exe" -ArgumentList "--install" -Wait
    
    # Iniciar servicios
    Start-Service -Name "Apache2.4" -ErrorAction SilentlyContinue
    Start-Service -Name "MySQL" -ErrorAction SilentlyContinue
    
    Write-Success "Servicios XAMPP configurados"

    # 4. Configurar PHP
    Write-Step "Configurando PHP..."
    $phpIniPath = "$XAMPP_PATH\php\php.ini"
    
    if (Test-Path $phpIniPath) {
        $phpIni = Get-Content $phpIniPath
        $phpIni = $phpIni -replace ';extension=pdo_mysql', 'extension=pdo_mysql'
        $phpIni = $phpIni -replace ';extension=mbstring', 'extension=mbstring'
        $phpIni = $phpIni -replace ';extension=curl', 'extension=curl'
        $phpIni = $phpIni -replace ';extension=zip', 'extension=zip'
        $phpIni = $phpIni -replace ';extension=gd', 'extension=gd'
        $phpIni = $phpIni -replace 'memory_limit = 128M', 'memory_limit = 256M'
        $phpIni = $phpIni -replace 'max_execution_time = 30', 'max_execution_time = 300'
        
        $phpIni | Set-Content $phpIniPath
        Write-Success "PHP configurado exitosamente"
    }

    # 5. Crear directorios del proyecto
    Write-Step "Creando estructura de directorios..."
    
    if (Test-Path $PROJECT_PATH) {
        Write-Warning "El directorio del proyecto ya existe. Creando respaldo..."
        $backupName = "planilla-innova-backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
        Move-Item $PROJECT_PATH "$XAMPP_PATH\htdocs\$backupName"
    }
    
    New-Item -ItemType Directory -Path $PROJECT_PATH -Force | Out-Null
    New-Item -ItemType Directory -Path $BACKUP_PATH -Force | Out-Null
    New-Item -ItemType Directory -Path "$PROJECT_PATH\storage" -Force | Out-Null
    New-Item -ItemType Directory -Path "$PROJECT_PATH\logs" -Force | Out-Null
    New-Item -ItemType Directory -Path "$PROJECT_PATH\database" -Force | Out-Null
    
    Write-Success "Estructura de directorios creada"

    # 6. Descargar archivos del sistema (simulado - en producción sería desde repositorio)
    Write-Step "Instalando archivos del sistema..."
    
    # En un escenario real, aquí se descargarían los archivos desde GitHub
    # Por ahora, copiamos desde la ubicación actual si existe
    $currentPath = Split-Path -Parent $MyInvocation.MyCommand.Path
    $sourcePath = Split-Path -Parent $currentPath
    
    if (Test-Path $sourcePath) {
        Copy-Item -Path "$sourcePath\*" -Destination $PROJECT_PATH -Recurse -Force -Exclude @("scripts", "documentation", ".git")
        Write-Success "Archivos del sistema instalados"
    } else {
        Write-Warning "Archivos fuente no encontrados. Descargue manualmente el sistema."
    }

    # 7. Configurar archivo .env
    Write-Step "Configurando archivo de ambiente..."
    
    $envContent = @"
# Configuración de la aplicación
APP_NAME="Sistema de Planillas MVC"
APP_URL="http://localhost/planilla-innova"
APP_ENV="production"
APP_DEBUG=false

# Base de datos
DB_HOST="localhost"
DB_DATABASE="planilla_mvc"
DB_USERNAME="root"
DB_PASSWORD=""

# Configuración regional
TIMEZONE="America/Panama"
LOCALE="es_ES"

# Configuración de correo
MAIL_HOST="smtp.gmail.com"
MAIL_PORT=587
MAIL_USERNAME=""
MAIL_PASSWORD=""
"@
    
    $envContent | Set-Content "$PROJECT_PATH\.env"
    Write-Success "Archivo .env configurado"

    # 8. Configurar base de datos
    Write-Step "Configurando base de datos MySQL..."
    
    $mysqlPath = "$XAMPP_PATH\mysql\bin\mysql.exe"
    if (Test-Path $mysqlPath) {
        # Crear base de datos
        $sqlCommands = @"
CREATE DATABASE IF NOT EXISTS planilla_mvc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE planilla_mvc;

-- Crear tabla de usuarios por defecto
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role VARCHAR(20) DEFAULT 'user',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar usuario administrador por defecto
INSERT IGNORE INTO users (username, password, email, role) 
VALUES ('admin', SHA2('admin123', 256), 'admin@planilla-innova.com', 'admin');
"@
        
        $sqlCommands | & $mysqlPath -u root --default-character-set=utf8mb4
        Write-Success "Base de datos configurada"
    }

    # 9. Configurar permisos
    Write-Step "Configurando permisos de archivos..."
    
    # Dar permisos completos a directorios específicos
    icacls "$PROJECT_PATH\storage" /grant Everyone:F /t
    icacls "$PROJECT_PATH\logs" /grant Everyone:F /t
    
    Write-Success "Permisos configurados"

    # 10. Configurar Windows Firewall
    Write-Step "Configurando Firewall de Windows..."
    
    try {
        New-NetFirewallRule -DisplayName "Apache HTTP" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow -ErrorAction SilentlyContinue
        New-NetFirewallRule -DisplayName "Apache HTTPS" -Direction Inbound -Protocol TCP -LocalPort 443 -Action Allow -ErrorAction SilentlyContinue
        New-NetFirewallRule -DisplayName "MySQL" -Direction Inbound -Protocol TCP -LocalPort 3306 -Action Allow -ErrorAction SilentlyContinue
        Write-Success "Firewall configurado"
    } catch {
        Write-Warning "No se pudo configurar el firewall automáticamente"
    }

    # 11. Reiniciar servicios
    Write-Step "Reiniciando servicios..."
    Restart-Service -Name "Apache2.4" -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
    Write-Success "Servicios reiniciados"

    # 12. Crear accesos directos
    Write-Step "Creando accesos directos..."
    
    $desktopPath = [Environment]::GetFolderPath("Desktop")
    $shell = New-Object -ComObject WScript.Shell
    
    # Acceso directo al panel de control XAMPP
    $shortcut = $shell.CreateShortcut("$desktopPath\XAMPP Control Panel.lnk")
    $shortcut.TargetPath = "$XAMPP_PATH\xampp-control.exe"
    $shortcut.Save()
    
    # Acceso directo al sistema web
    $shortcut = $shell.CreateShortcut("$desktopPath\Sistema Planillas.lnk")
    $shortcut.TargetPath = "http://localhost/planilla-innova"
    $shortcut.Save()
    
    Write-Success "Accesos directos creados en el escritorio"

    # Instalación completada
    Write-ColoredOutput ""
    Write-ColoredOutput "╔══════════════════════════════════════════════════════════════╗" "Green"
    Write-ColoredOutput "║                ✅ INSTALACIÓN COMPLETADA                      ║" "Green"
    Write-ColoredOutput "╚══════════════════════════════════════════════════════════════╝" "Green"
    Write-ColoredOutput ""
    
    Write-ColoredOutput "🎉 ¡El Sistema de Planillas MVC se ha instalado exitosamente!" "Green"
    Write-ColoredOutput ""
    Write-ColoredOutput "📋 INFORMACIÓN DE ACCESO:" "Yellow"
    Write-ColoredOutput "• URL del Sistema: http://localhost/planilla-innova" "White"
    Write-ColoredOutput "• Usuario por defecto: admin" "White"
    Write-ColoredOutput "• Contraseña por defecto: admin123" "White"
    Write-ColoredOutput "• phpMyAdmin: http://localhost/phpmyadmin" "White"
    Write-ColoredOutput ""
    Write-ColoredOutput "⚠️  IMPORTANTE:" "Red"
    Write-ColoredOutput "• Cambie la contraseña por defecto inmediatamente" "Yellow"
    Write-ColoredOutput "• Configure SSL/HTTPS para producción" "Yellow"
    Write-ColoredOutput "• Revise la configuración de seguridad" "Yellow"
    Write-ColoredOutput ""
    Write-ColoredOutput "📁 UBICACIONES:" "Cyan"
    Write-ColoredOutput "• XAMPP: $XAMPP_PATH" "Gray"
    Write-ColoredOutput "• Proyecto: $PROJECT_PATH" "Gray"
    Write-ColoredOutput "• Respaldos: $BACKUP_PATH" "Gray"
    Write-ColoredOutput ""
    Write-ColoredOutput "🛠️  HERRAMIENTAS:" "Cyan"
    Write-ColoredOutput "• Panel XAMPP: Acceso directo en escritorio" "Gray"
    Write-ColoredOutput "• Sistema Web: Acceso directo en escritorio" "Gray"
    Write-ColoredOutput ""
    
    # Abrir navegador automáticamente
    $openBrowser = Read-Host "¿Desea abrir el sistema en el navegador ahora? (Y/N)"
    if ($openBrowser -match '^[Yy]$') {
        Start-Process "http://localhost/planilla-innova"
    }

} catch {
    Write-Error "Error durante la instalación: $($_.Exception.Message)"
    Write-ColoredOutput ""
    Write-ColoredOutput "🔧 PASOS PARA RESOLUCIÓN:" "Yellow"
    Write-ColoredOutput "1. Verifique que tiene permisos de administrador" "Gray"
    Write-ColoredOutput "2. Desactive temporalmente el antivirus" "Gray"
    Write-ColoredOutput "3. Verifique la conexión a Internet" "Gray"
    Write-ColoredOutput "4. Ejecute el script nuevamente" "Gray"
    Write-ColoredOutput ""
    Write-ColoredOutput "Para soporte técnico, consulte el manual de instalación" "Gray"
}

Write-ColoredOutput ""
Write-ColoredOutput "Presione cualquier tecla para continuar..." "Gray"
pause