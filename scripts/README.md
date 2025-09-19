# 📋 Scripts de Mantenimiento del Sistema

Este directorio contiene scripts para el mantenimiento y actualización automática del sistema de planillas.

## 🔧 Scripts Disponibles

### `update_version.php`
Script principal para la gestión de versiones del sistema.

**Uso:**
```bash
php scripts/update_version.php [opciones]
```

**Opciones:**
- `--from-roadmap` - Actualizar versión desde `documentation/ROADMAP.md`
- `--from-claude` - Actualizar versión desde `CLAUDE.md`
- `--current` - Mostrar versión actual
- `--info` - Mostrar información completa de versión
- `--check` - Verificar si hay actualizaciones disponibles
- `--help` - Mostrar ayuda

**Ejemplos:**
```bash
# Mostrar información de versión actual
php scripts/update_version.php --info

# Actualizar desde ROADMAP
php scripts/update_version.php --from-roadmap

# Verificar actualizaciones
php scripts/update_version.php --check
```

### `version.bat`
Script de Windows para facilitar el uso en sistemas Windows.

**Uso:**
```cmd
version.bat [opción]
```

**Opciones:**
- `roadmap` - Actualizar desde ROADMAP.md
- `claude` - Actualizar desde CLAUDE.md
- `info` - Mostrar información de versión
- `check` - Verificar actualizaciones
- `help` - Mostrar ayuda

**Ejemplos:**
```cmd
version.bat info
version.bat roadmap
```

## 🎯 Flujo de Actualización

1. **Manual**: Ejecutar script cuando se actualice la documentación
2. **Automático**: El script extrae la versión de los archivos de documentación
3. **Actualización**: Se actualizan automáticamente:
   - Footer del sistema
   - Menú lateral (sidebar)
   - Archivo de configuración `config/version.php`

## 📁 Archivos de Versión

### `config/version.php`
Archivo de configuración central que contiene:
- Versión actual
- Codename de la versión
- Fecha de build
- Entorno (production/development)
- Historial de versiones (changelog)

### Ubicaciones de Visualización
- **Footer**: `app/Views/layouts/admin.php`
- **Sidebar**: `app/Views/components/sidebar.php`

## 🔄 Automatización

Para automatizar completamente el proceso, se pueden configurar:

1. **Git Hooks**: Ejecutar al hacer commit de cambios en ROADMAP
2. **Cron Jobs**: Verificación periódica de actualizaciones
3. **CI/CD**: Integración en pipeline de deployment

## 🛡️ Seguridad

- Los scripts validan la existencia de archivos antes de procesarlos
- Se mantiene un backup del historial de versiones
- No se ejecutan comandos del sistema sin validación

## 📝 Mantenimiento

Para agregar nuevas funcionalidades al sistema de versiones:

1. Modificar `VersionHelper.php` para nueva lógica
2. Actualizar scripts en este directorio
3. Documentar cambios en este README

