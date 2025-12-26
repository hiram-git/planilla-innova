# Fix Error "upstream sent too big header" en Nginx

## Problema
Error en logs de Nginx:
```
[error] upstream sent too big header while reading response header from upstream
```

## Causa
Los headers HTTP generados por PHP-FPM exceden el límite de buffer configurado en Nginx. Esto ocurre cuando:
- Se imprimen logs de debug muy largos
- Se envían cookies grandes
- Se generan headers HTTP extensos

En este caso, los mensajes de debug del modelo `Employee` están imprimiendo arrays completos de datos (32 campos), generando salidas enormes que exceden el buffer.

## Solución

### Opción 1: Aumentar Buffers de Nginx (Recomendado para Producción)

Editar el archivo de configuración de Nginx del sitio:

```bash
# Conectar al servidor de producción
ssh usuario@servidor

# Editar configuración del sitio
sudo nano /etc/nginx/sites-available/planilla.innovasoftlatam.com
```

Agregar o modificar las siguientes directivas dentro del bloque `server {}`:

```nginx
server {
    # ... otras configuraciones ...

    # Aumentar buffers para FastCGI
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_busy_buffers_size 32k;

    # Opcional: aumentar límites adicionales
    proxy_buffer_size 128k;
    proxy_buffers 4 256k;
    proxy_busy_buffers_size 256k;

    # ... resto de configuración ...
}
```

**Valores Recomendados**:
- `fastcgi_buffers`: 16 buffers de 16k cada uno (total 256k)
- `fastcgi_buffer_size`: 32k (tamaño inicial del buffer para headers)
- `fastcgi_busy_buffers_size`: 32k (buffers ocupados enviando respuesta al cliente)

### Opción 2: Configuración Global (Afecta todos los sitios)

Si prefieres aplicar esta configuración a todos los sitios, edita el archivo principal de Nginx:

```bash
sudo nano /etc/nginx/nginx.conf
```

Agregar dentro del bloque `http {}`:

```nginx
http {
    # ... otras configuraciones ...

    # Buffers FastCGI globales
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_busy_buffers_size 32k;

    # ... resto de configuración ...
}
```

### Paso 2: Verificar Sintaxis de Nginx

```bash
sudo nginx -t
```

Deberías ver:
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### Paso 3: Recargar Nginx

```bash
sudo systemctl reload nginx
```

O si prefieres reiniciar completamente:

```bash
sudo systemctl restart nginx
```

### Paso 4: Verificar Estado

```bash
sudo systemctl status nginx
```

## Opción Alternativa: Reducir Logs de Debug (Desarrollo)

Si estás en desarrollo local y no quieres modificar Nginx, puedes comentar los logs de debug excesivos.

**Archivos a revisar**:

### 1. `app/Models/Employee.php`

Buscar y comentar líneas como:
```php
// error_log("[Employee::update] Datos a actualizar: " . print_r($data, true));
```

### 2. `app/Core/Database.php`

Buscar y comentar logs de debug:
```php
// error_log("Llamando a db->update() en tabla '$table' para ID $id");
// error_log("Datos completos: " . print_r($data, true));
```

**IMPORTANTE**: En producción, estos logs NO deberían estar activos. Solo se usan para debugging durante desarrollo.

## Verificación Post-Fix

1. **Verificar que no haya errores en logs**:
```bash
sudo tail -f /var/log/nginx/error.log
```

2. **Probar la edición de empleado** desde la interfaz web:
   - Ir a `/panel/employees/edit/25`
   - Modificar algún campo
   - Guardar cambios
   - Verificar que se guarde correctamente sin errores

3. **Verificar que no aparezcan más errores "upstream sent too big header"**

## Valores de Referencia

### Para Aplicaciones Pequeñas (Default):
```nginx
fastcgi_buffers 8 4k;
fastcgi_buffer_size 4k;
```

### Para Aplicaciones Medianas (Recomendado):
```nginx
fastcgi_buffers 16 16k;
fastcgi_buffer_size 32k;
```

### Para Aplicaciones Grandes o con Debug Extensivo:
```nginx
fastcgi_buffers 32 32k;
fastcgi_buffer_size 64k;
```

## Notas Importantes

1. **Producción**: Los logs de debug deben estar deshabilitados en producción
2. **Seguridad**: No aumentes los buffers más de lo necesario, consume memoria RAM
3. **Monitoreo**: Verifica el uso de memoria después de aplicar cambios
4. **Logs**: Mantén un ojo en los logs de error de Nginx después del cambio

## Troubleshooting

### Si el error persiste:

1. **Verificar logs de PHP-FPM**:
```bash
sudo tail -f /var/log/php8.1-fpm.log
```

2. **Aumentar límites de PHP-FPM** (si es necesario):
```bash
sudo nano /etc/php/8.1/fpm/php.ini
```

Buscar y ajustar:
```ini
output_buffering = 4096
memory_limit = 256M
```

3. **Reiniciar PHP-FPM**:
```bash
sudo systemctl restart php8.1-fpm
```

## Script de Verificación Rápida

```bash
#!/bin/bash
# Verificación rápida de configuración Nginx

echo "=== Verificando configuración actual ==="
grep -r "fastcgi_buffer" /etc/nginx/

echo ""
echo "=== Verificando sintaxis Nginx ==="
sudo nginx -t

echo ""
echo "=== Monitoreando logs en tiempo real ==="
echo "Presiona Ctrl+C para salir"
sudo tail -f /var/log/nginx/error.log
```

## Archivo de Configuración Completo de Ejemplo

```nginx
server {
    listen 443 ssl http2;
    server_name planilla.innovasoftlatam.com;

    root /var/www/html/planilla;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # Buffers FastCGI (FIX para "upstream sent too big header")
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_busy_buffers_size 32k;

    # Límites de carga
    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Logs
    access_log /var/log/nginx/planilla_access.log;
    error_log /var/log/nginx/planilla_error.log;
}
```

## Referencia

- Documentación oficial Nginx: http://nginx.org/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_buffers
- Guía de optimización: https://www.nginx.com/blog/tuning-nginx/
