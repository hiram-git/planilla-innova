# 🚀 Scripts de Instalación Automática

Scripts automatizados para la instalación del Sistema de Planillas MVC en múltiples sistemas operativos.

## 📋 Scripts Disponibles

### 🪟 Windows (`install-windows.ps1`)
- **Compatibilidad**: Windows Server 2019/2022, Windows 10/11 Pro/Enterprise
- **Tecnología**: PowerShell 5.1+
- **Instala**: XAMPP (Apache, MySQL, PHP 8.3), configuración automática, firewall

### 🐧 Ubuntu (`install-ubuntu.sh`)
- **Compatibilidad**: Ubuntu 20.04 LTS, 22.04 LTS
- **Tecnología**: Bash
- **Instala**: Stack LAMP, MySQL 8.0, PHP 8.3, SSL, firewall UFW

### 🎩 CentOS/RHEL (`install-centos.sh`)
- **Compatibilidad**: CentOS 8/9, RHEL 8/9, Rocky Linux 8/9, AlmaLinux 8/9
- **Tecnología**: Bash
- **Instala**: Stack LAMP, MySQL 8.0, PHP 8.3, SELinux, Firewalld, SSL

## 🛠️ Uso de los Scripts

### Windows
```powershell
# Abrir PowerShell como Administrador
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
.\install-windows.ps1
```

### Ubuntu
```bash
# Descargar y ejecutar
curl -O https://raw.githubusercontent.com/tu-repo/planilla-innova/main/scripts/install-ubuntu.sh
chmod +x install-ubuntu.sh
sudo ./install-ubuntu.sh
```

### CentOS/RHEL
```bash
# Descargar y ejecutar
curl -O https://raw.githubusercontent.com/tu-repo/planilla-innova/main/scripts/install-centos.sh
chmod +x install-centos.sh
sudo ./install-centos.sh
```

## ✅ Características de la Instalación Automática

### Todos los Sistemas
- ✅ Instalación completa del stack LAMP/WAMP
- ✅ Configuración automática de base de datos
- ✅ Creación de usuario administrador por defecto
- ✅ Configuración de permisos de archivos
- ✅ Scripts de respaldo automático
- ✅ Monitoreo básico del sistema
- ✅ Configuración de logs y logrotate
- ✅ Instalación en 5-15 minutos

### Específico por Sistema

#### Windows
- ✅ Descarga automática de XAMPP
- ✅ Configuración de servicios de Windows
- ✅ Configuración del Firewall de Windows
- ✅ Creación de accesos directos
- ✅ Configuración de permisos NTFS

#### Ubuntu
- ✅ Repositorios Ondrej (PHP 8.3)
- ✅ Configuración de virtual hosts
- ✅ Configuración UFW (firewall)
- ✅ Certificado SSL autoasignado
- ✅ Configuración de systemd

#### CentOS/RHEL
- ✅ Configuración completa de SELinux
- ✅ Configuración de Firewalld
- ✅ Repositorio Remi (PHP 8.3)
- ✅ Repositorio MySQL Community
- ✅ Certificado SSL con configuración moderna
- ✅ Configuración específica para RHEL

## 📋 Requisitos Previos

### Hardware Mínimo
- **RAM**: 2 GB (recomendado 4 GB+)
- **Almacenamiento**: 5 GB libres (recomendado 20 GB+)
- **Red**: Conexión a Internet para descarga de paquetes

### Software
- **Windows**: PowerShell 5.1+, permisos de administrador
- **Ubuntu**: Bash, sudo access, curl/wget
- **CentOS**: Bash, sudo/root access, dnf/yum

## 🔧 Lo que Instalan los Scripts

### Stack Base
| Componente | Windows | Ubuntu | CentOS |
|------------|---------|--------|--------|
| **Web Server** | Apache 2.4 (XAMPP) | Apache 2.4 | httpd 2.4 |
| **Base de Datos** | MySQL 8.0 (XAMPP) | MySQL 8.0 | MySQL 8.0 Community |
| **PHP** | 8.3 (XAMPP) | 8.3 (Ondrej PPA) | 8.3 (Remi Repo) |
| **SSL** | Manual | Auto-signed | Auto-signed |
| **Firewall** | Windows Firewall | UFW | Firewalld |

### Extensiones PHP
- ✅ pdo_mysql - Conexión MySQL
- ✅ mbstring - Multibyte strings
- ✅ json - Manejo JSON
- ✅ curl - Peticiones HTTP
- ✅ zip - Compresión
- ✅ gd - Imágenes
- ✅ xml - Procesamiento XML
- ✅ intl - Internacionalización

### Configuraciones de Seguridad
- ✅ Firewall habilitado con reglas básicas
- ✅ MySQL con configuración segura
- ✅ PHP con configuración de producción
- ✅ Logs de seguridad habilitados
- ✅ Permisos de archivos restrictivos

## 📊 Información Post-Instalación

### Credenciales por Defecto
- **Usuario**: admin
- **Contraseña**: admin123
- **⚠️ IMPORTANTE**: Cambiar inmediatamente después del primer acceso

### URLs de Acceso
- **Sistema Principal**: `http://localhost/planilla-innova`
- **phpMyAdmin** (Windows): `http://localhost/phpmyadmin`
- **HTTPS**: `https://localhost/planilla-innova` (con certificado autoasignado)

### Ubicaciones de Archivos
| Sistema | Proyecto | Logs | Respaldos |
|---------|----------|------|-----------|
| **Windows** | `C:\xampp\htdocs\planilla-innova` | `C:\xampp\htdocs\planilla-innova\logs` | `C:\PlanillaMVC-Backup` |
| **Ubuntu** | `/var/www/html/planilla-innova` | `/var/www/html/planilla-innova/logs` | `/var/backups/planilla-innova` |
| **CentOS** | `/var/www/html/planilla-innova` | `/var/www/html/planilla-innova/logs` | `/var/backups/planilla-innova` |

## 🛠️ Scripts de Mantenimiento Incluidos

### Respaldos Automáticos
- **Frecuencia**: Diario a las 2:00 AM
- **Ubicación**: Directorio de respaldos del sistema
- **Contenido**: Base de datos + archivos del sistema
- **Retención**: 7 días

### Monitoreo del Sistema
- **Frecuencia**: Cada 15 minutos
- **Verifica**: Estado de servicios, espacio en disco, errores de logs
- **Logs**: `/var/log/planilla-innova-monitor.log`

## 🔄 Comandos Útiles Post-Instalación

### Windows
```powershell
# Ver servicios XAMPP
Get-Service | Where-Object {$_.Name -like "*Apache*" -or $_.Name -like "*MySQL*"}

# Ver logs
Get-Content "C:\xampp\htdocs\planilla-innova\logs\php\error.log" -Tail 50

# Crear respaldo manual
# Ver archivo de información en C:\xampp\htdocs\planilla-innova\
```

### Ubuntu
```bash
# Estado de servicios
sudo systemctl status apache2 mysql

# Ver logs en tiempo real
sudo tail -f /var/www/html/planilla-innova/logs/php/error.log

# Crear respaldo manual
sudo /usr/local/bin/backup-planilla-innova.sh

# Monitor del sistema
sudo /usr/local/bin/monitor-planilla-innova.sh
```

### CentOS/RHEL
```bash
# Estado de servicios
sudo systemctl status httpd mysqld firewalld

# Ver configuración SELinux
getenforce
getsebool -a | grep httpd

# Ver configuración firewall
sudo firewall-cmd --list-all

# Ver logs de Apache
sudo tail -f /var/log/httpd/planilla-innova-error.log

# Crear respaldo manual
sudo /usr/local/bin/backup-planilla-innova.sh
```

## 🚨 Solución de Problemas Comunes

### Error: "Script no puede ejecutarse"
**Windows**: Ejecutar `Set-ExecutionPolicy RemoteSigned` como administrador
**Linux**: Verificar permisos con `chmod +x script.sh`

### Error: "No se puede conectar a Internet"
- Verificar conexión de red
- Desactivar temporalmente proxy/VPN
- Verificar DNS (`nslookup google.com`)

### Error: "Permisos insuficientes"
- **Windows**: Ejecutar PowerShell como Administrador
- **Linux**: Usar `sudo` antes del comando

### Error: "Puerto 80 ya en uso"
- Detener servicios web existentes (IIS, Apache previo)
- Cambiar puerto en configuración
- Verificar con `netstat -tulpn | grep :80`

### Error: "MySQL no inicia"
- Verificar logs: `/var/log/mysql/error.log`
- Verificar permisos del directorio de datos
- Verificar espacio en disco

## 📞 Soporte

### Documentación
- **Manual de Usuario**: `/documentation/manual-usuario.html`
- **Manual de Instalación**: `/documentation/manual-instalacion.html`

### Logs Importantes
- **Sistema**: `{proyecto}/logs/system/`
- **PHP**: `{proyecto}/logs/php/error.log`
- **Web Server**: `/var/log/apache2/` o `/var/log/httpd/`
- **MySQL**: `/var/log/mysql/` o `/var/log/mysqld.log`

### Información de Instalación
- **Windows**: Información guardada en el escritorio
- **Ubuntu**: `/root/planilla-innova-install-info.txt`
- **CentOS**: `/root/planilla-innova-install-info.txt`

## 🔄 Actualización del Sistema

Para actualizar a una nueva versión:

1. **Crear respaldo completo**
2. **Descargar nueva versión de los scripts**
3. **Ejecutar script de actualización** (próximamente)
4. **Verificar funcionalidad**

## 📝 Changelog de Scripts

### v2.0 (Agosto 2025)
- ✅ Scripts completamente reescritos
- ✅ Soporte multi-plataforma mejorado
- ✅ Configuración automática de seguridad
- ✅ Scripts de mantenimiento incluidos
- ✅ Monitoreo automático
- ✅ Mejores mensajes de error y logging

### v1.0 (Versión inicial)
- ✅ Scripts básicos de instalación
- ✅ Configuración manual requerida

---

## ⚠️ Importante

- **🔒 Seguridad**: Cambie todas las contraseñas por defecto
- **🌐 Producción**: Configure SSL válido y dominio real
- **💾 Respaldos**: Verifique que los respaldos automáticos funcionen
- **📊 Monitoreo**: Revise logs regularmente
- **🔄 Actualizaciones**: Mantenga el sistema actualizado

---

**Sistema de Planillas MVC** - Scripts de Instalación Automática v2.0  
Desarrollado con ❤️ para facilitar el despliegue multi-plataforma