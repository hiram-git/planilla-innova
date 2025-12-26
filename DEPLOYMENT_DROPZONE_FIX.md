# Fix Error Dropzone en Producción

## Problema
Error en logs de Nginx:
```
2025/12/24 10:16:49 [error] open() "/var/www/html/planilla/panel/plugins/dropzone/min/dropzone.min.css" failed
```

## Causa
Los archivos de la librería Dropzone no están presentes en el servidor de producción.

## Solución

### 1. Archivos Modificados
- `app/Views/admin/company/index.php` (línea 16): Corregida ruta CSS con función `url()`

### 2. Archivos a Subir a Producción

Subir el directorio completo `plugins/dropzone/` al servidor:

```bash
# Desde el servidor de producción
cd /var/www/html/planilla/
```

**Estructura de archivos requeridos:**
```
plugins/dropzone/
├── min/
│   ├── dropzone.min.css
│   └── dropzone.min.js
├── dropzone.css
└── dropzone.js
```

### 3. Comandos de Despliegue

#### Opción A: Usando SCP desde local
```bash
# Desde tu máquina local (Windows con Git Bash o WSL)
scp -r C:/laragon60/www/planilla-innova/plugins/dropzone usuario@servidor:/var/www/html/planilla/plugins/
```

#### Opción B: Usando rsync (más eficiente)
```bash
rsync -avz --progress C:/laragon60/www/planilla-innova/plugins/dropzone/ usuario@servidor:/var/www/html/planilla/plugins/dropzone/
```

#### Opción C: Desde el servidor (si tienes acceso SSH)
```bash
# 1. Conectar al servidor
ssh usuario@servidor

# 2. Ir al directorio de plugins
cd /var/www/html/planilla/plugins/

# 3. Descargar dropzone desde CDN o repositorio
wget https://github.com/dropzone/dropzone/releases/download/v5.9.3/dist.zip
unzip dist.zip -d dropzone/
rm dist.zip

# 4. Verificar permisos
chmod -R 755 dropzone/
chown -R www-data:www-data dropzone/
```

### 4. Verificación

#### Verificar archivos en servidor:
```bash
ls -la /var/www/html/planilla/plugins/dropzone/min/
```

Deberías ver:
```
-rw-r--r-- 1 www-data www-data   9830 dropzone.min.css
-rw-r--r-- 1 www-data www-data 114702 dropzone.min.js
```

#### Verificar URL en navegador:
```
https://tu-dominio.com/planilla/plugins/dropzone/min/dropzone.min.css
```

Debería cargar el archivo CSS sin error 404.

### 5. Reiniciar Servicios (si es necesario)
```bash
# Limpiar cache de Nginx
sudo nginx -t && sudo systemctl reload nginx

# O reiniciar completamente
sudo systemctl restart nginx
```

### 6. Archivos del Proyecto Modificados
```
app/Views/admin/company/index.php
```

## Notas Importantes
- Dropzone se usa en el módulo de **Configuración de Empresa** (`/panel/company`) para subir logos
- Los archivos minificados son suficientes para producción
- Asegúrate de que los permisos sean correctos (`755` para directorios, `644` para archivos)
- El propietario debe ser el usuario del servidor web (generalmente `www-data`)

## Prueba Final
1. Ir a `/panel/company` en producción
2. Verificar que no haya errores en la consola del navegador (F12)
3. Verificar que los logs de Nginx no muestren más errores 404 de dropzone
4. Probar subir un logo para confirmar que Dropzone funciona correctamente

## Versión Dropzone
- Versión actual en el proyecto: 5.9.3
- Archivos necesarios: CSS y JS minificados
