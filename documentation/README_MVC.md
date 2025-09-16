# Planilla Simple - Arquitectura MVC

## Descripción

Sistema de gestión de planillas y recursos humanos completamente refactorizado con arquitectura MVC, implementando las mejores prácticas de desarrollo en PHP 8.

## Estructura del Proyecto

```
planilla-claude-v2/
├── app/
│   ├── Controllers/        # Controladores de la aplicación
│   ├── Models/            # Modelos de datos
│   ├── Views/             # Vistas y plantillas
│   ├── Core/              # Clases fundamentales del framework
│   └── Middleware/        # Middleware de autenticación y seguridad
├── config/                # Archivos de configuración
├── public/                # Punto de entrada público
├── storage/               # Logs y cache
└── vendor/                # Dependencias de Composer
```

## Características Implementadas

### ✅ Arquitectura MVC
- **Modelos**: Abstracción de base de datos con métodos CRUD
- **Vistas**: Sistema de plantillas con layouts reutilizables
- **Controladores**: Lógica de negocio separada de la presentación

### ✅ Seguridad
- **Protección CSRF**: Tokens de seguridad en formularios
- **Validación de entrada**: Sanitización automática de datos
- **Rate Limiting**: Protección contra ataques de fuerza bruta
- **Logging de seguridad**: Registro de eventos de seguridad
- **Autenticación robusta**: Sistema de sesiones seguro

### ✅ Base de Datos
- **PDO con prepared statements**: Protección contra SQL injection
- **Abstracción de consultas**: Métodos helper para operaciones comunes
- **Validación de modelos**: Reglas de validación automáticas

### ✅ Funcionalidades
- **Sistema de marcaciones**: Registro de entrada y salida
- **Gestión de empleados**: CRUD completo de empleados
- **Panel administrativo**: Dashboard con estadísticas
- **Gestión de posiciones**: Cargos y roles
- **Horarios**: Gestión de horarios de trabajo

## Instalación y Configuración

### Prerrequisitos
- PHP 8.0 o superior
- MySQL 8.0 o superior
- Composer
- Servidor web (Apache/Nginx)

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone [repository-url]
   cd planilla-claude-v2
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   ```

3. **Configurar base de datos**
   - Crear base de datos `planilla-simple`
   - Importar `db/db.sql`

4. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   # Editar .env con sus configuraciones
   ```

5. **Configurar servidor web**
   - DocumentRoot debe apuntar a la carpeta `public/`
   - Habilitar mod_rewrite para URLs amigables

## Uso del Sistema

### Acceso al Sistema de Marcaciones
- URL: `http://localhost/planilla-claude-v2/public/`
- Los empleados pueden registrar entrada/salida con su ID

### Panel Administrativo
- URL: `http://localhost/planilla-claude-v2/public/admin`
- Usuario por defecto: `ary_bandana`
- Contraseña: (ver base de datos)

## API y Rutas

### Rutas Públicas
- `GET /` - Sistema de marcaciones
- `POST /home/attendance` - Registrar marcación

### Rutas Administrativas
- `GET /admin` - Login administrativo
- `POST /admin/login` - Autenticación
- `GET /admin/dashboard` - Panel principal
- `GET /employee` - Gestión de empleados
- `GET /employee/create` - Formulario nuevo empleado
- `POST /employee/store` - Crear empleado
- `GET /employee/edit/{id}` - Editar empleado
- `POST /employee/update/{id}` - Actualizar empleado
- `GET /employee/delete/{id}` - Eliminar empleado

## Modelos Disponibles

### Employee
- Gestión completa de empleados
- Validación de datos
- Relaciones con posiciones y horarios

### Attendance
- Registro de marcaciones
- Cálculo automático de horas trabajadas
- Reportes de asistencia

### Admin
- Autenticación segura
- Gestión de usuarios administrativos

### Position
- Gestión de cargos/posiciones
- Tarifas por posición

### Schedule
- Gestión de horarios de trabajo
- Validación de rangos horarios

## Seguridad Implementada

### Autenticación
- Hash seguro de contraseñas (PASSWORD_DEFAULT)
- Sesiones con configuración segura
- Regeneración de IDs de sesión

### Protección contra Ataques
- **CSRF**: Tokens únicos por sesión
- **SQL Injection**: PDO con prepared statements
- **XSS**: Sanitización automática de salida
- **Rate Limiting**: Límite de intentos de login
- **File Upload**: Validación de tipos y tamaños

### Logging
- Eventos de seguridad registrados
- Logs rotativos por fecha
- Información de IP y User-Agent

## Configuración Avanzada

### Variables de Entorno (.env)
```env
APP_NAME="Planilla Simple"
APP_URL=http://localhost/planilla-claude-v2/public
APP_DEBUG=true
APP_TIMEZONE=America/Mexico_City
APP_LOCALE=es

DB_HOST=localhost
DB_NAME=planilla-simple
DB_USER=root
DB_PASS=
```

### Configuración de Base de Datos (config/database.php)
```php
return [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_NAME'] ?? 'planilla-simple',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci'
];
```

## Troubleshooting

### Problemas Comunes

1. **Error 500 - Internal Server Error**
   - Verificar permisos de carpetas (755)
   - Verificar configuración de .htaccess
   - Revisar logs de PHP

2. **Error de Conexión a Base de Datos**
   - Verificar credenciales en .env
   - Confirmar que la base de datos existe
   - Verificar permisos de usuario de BD

3. **URLs no funcionan (404)**
   - Verificar mod_rewrite habilitado
   - Confirmar .htaccess en public/
   - Revisar DocumentRoot del servidor

4. **Problemas de Permisos**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 storage/logs/
   chmod -R 755 storage/cache/
   ```

## Desarrollo Futuro

### Funcionalidades Pendientes
- [ ] Sistema de reportes avanzados
- [ ] API REST completa
- [ ] Notificaciones por email
- [ ] Exportación a Excel/PDF
- [ ] Dashboard con gráficos
- [ ] Sistema de roles más granular
- [ ] Integración con sistemas biométricos

### Mejoras Técnicas
- [ ] Cache de consultas frecuentes
- [ ] Optimización de imágenes
- [ ] Compresión de assets
- [ ] Tests unitarios
- [ ] CI/CD pipeline
- [ ] Docker containerization

## Contribución

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Licencia

Este proyecto está bajo licencia MIT. Ver archivo LICENSE para más detalles.

## Soporte

Para soporte técnico o reportar bugs, crear un issue en el repositorio del proyecto.

---

**Desarrollado con ❤️ y las mejores prácticas de PHP**