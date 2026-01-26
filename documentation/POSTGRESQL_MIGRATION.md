# 🐘 Migración a PostgreSQL - Guía de Configuración

## 📋 Resumen

Esta guía documenta cómo configurar PostgreSQL en el sistema de planillas, permitiendo una **migración progresiva** desde MySQL.

**Fecha**: 26 de Enero, 2026
**Versión**: 3.5.20 - Soporte PostgreSQL

---

## ✅ Cambios Implementados

### **Archivos Modificados**

1. **`config/database.php`**
   - Agregada conexión `pgsql` (líneas 25-41)
   - Variable `DB_CONNECTION` para cambiar driver dinámicamente
   - Soporte para variables de entorno específicas de PostgreSQL

2. **`config/master_database.php`**
   - Agregado campo `driver` configurable
   - Soporte para puerto 5432 y charset utf8
   - Variables PostgreSQL específicas (schema, sslmode)

3. **`.env.postgresql.example`** (nuevo)
   - Plantilla completa de configuración
   - Ejemplos de diferentes escenarios de migración
   - Documentación de variables

---

## 🔧 Configuración Básica

### **Paso 1: Instalar PostgreSQL**

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install postgresql postgresql-contrib

# Verificar instalación
psql --version

# Iniciar servicio
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

### **Paso 2: Crear Bases de Datos**

```sql
-- Conectar como usuario postgres
sudo -u postgres psql

-- Crear usuario para la aplicación
CREATE USER planilla_user WITH PASSWORD 'tu_password_seguro';

-- Crear base de datos master
CREATE DATABASE planilla_master OWNER planilla_user;

-- Crear base de datos de producción
CREATE DATABASE planilla_prod OWNER planilla_user;

-- Otorgar privilegios
GRANT ALL PRIVILEGES ON DATABASE planilla_master TO planilla_user;
GRANT ALL PRIVILEGES ON DATABASE planilla_prod TO planilla_user;

-- Salir
\q
```

### **Paso 3: Configurar .env**

Agregar estas variables a tu archivo `.env`:

```bash
# ============================================
# OPCIÓN A: MIGRACIÓN COMPLETA A POSTGRESQL
# ============================================

# Conexión principal
DB_CONNECTION=pgsql

# Configuración PostgreSQL - Tenant
PGSQL_HOST=localhost
PGSQL_PORT=5432
PGSQL_DATABASE=planilla_prod
PGSQL_USERNAME=planilla_user
PGSQL_PASSWORD=tu_password_seguro
PGSQL_SCHEMA=public
PGSQL_SSLMODE=prefer

# Configuración PostgreSQL - Master
MASTER_DB_DRIVER=pgsql
MASTER_DB_HOST=localhost
MASTER_DB_PORT=5432
MASTER_DB_NAME=planilla_master
MASTER_DB_USER=planilla_user
MASTER_DB_PASS=tu_password_seguro
MASTER_DB_CHARSET=utf8
MASTER_DB_SCHEMA=public
MASTER_DB_SSLMODE=prefer
```

---

## 🔄 Escenarios de Migración

### **Escenario 1: Mantener MySQL, Solo Master en PostgreSQL**

**Uso**: Probar PostgreSQL sin afectar datos principales

```bash
# .env
DB_CONNECTION=mysql
MASTER_DB_DRIVER=pgsql

# Variables MySQL existentes
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=planilla_prod
DB_USERNAME=root
DB_PASSWORD=

# Variables PostgreSQL solo para master
MASTER_DB_HOST=localhost
MASTER_DB_PORT=5432
MASTER_DB_NAME=planilla_master
MASTER_DB_USER=postgres
MASTER_DB_PASS=password
```

**Resultado**:
- Tenants siguen usando MySQL
- Tabla `tenants` en PostgreSQL
- Migración de riesgo bajo

---

### **Escenario 2: Migración Completa a PostgreSQL**

**Uso**: Migrar toda la aplicación a PostgreSQL

```bash
# .env
DB_CONNECTION=pgsql
MASTER_DB_DRIVER=pgsql

# PostgreSQL para todo
PGSQL_HOST=localhost
PGSQL_PORT=5432
PGSQL_DATABASE=planilla_prod
PGSQL_USERNAME=planilla_user
PGSQL_PASSWORD=password

MASTER_DB_HOST=localhost
MASTER_DB_PORT=5432
MASTER_DB_NAME=planilla_master
MASTER_DB_USER=planilla_user
MASTER_DB_PASS=password
```

**Resultado**:
- Sistema completo en PostgreSQL
- Requiere migración de datos de MySQL → PostgreSQL
- Mayor rendimiento en consultas complejas

---

### **Escenario 3: Mantener MySQL (Sin cambios)**

**Uso**: Configuración actual, sin tocar nada

```bash
# .env
# No agregar variables PostgreSQL
# Sistema usa MySQL por defecto
```

**Resultado**:
- Sistema sigue igual
- MySQL como siempre
- Sin cambios necesarios

---

## 📊 Comparación de Variables de Entorno

| Variable | MySQL | PostgreSQL | Notas |
|----------|-------|------------|-------|
| **Driver** | `mysql` | `pgsql` | Define el motor de BD |
| **Puerto** | `3306` | `5432` | Puerto por defecto |
| **Charset** | `utf8mb4` | `utf8` | PostgreSQL no soporta utf8mb4 |
| **Usuario** | `root` | `postgres` | Usuario por defecto del sistema |
| **Schema** | N/A | `public` | PostgreSQL usa schemas |
| **SSL Mode** | N/A | `prefer` | prefer/require/disable |

---

## 🔍 Verificar Conexión

### **Test Manual desde PHP**

Crear archivo `test_pgsql.php` en la raíz:

```php
<?php
require_once 'vendor/autoload.php';

// Cargar .env
if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createImmutable(__DIR__)->load();
}

try {
    $host = $_ENV['PGSQL_HOST'] ?? 'localhost';
    $port = $_ENV['PGSQL_PORT'] ?? '5432';
    $dbname = $_ENV['PGSQL_DATABASE'] ?? 'planilla_prod';
    $user = $_ENV['PGSQL_USERNAME'] ?? 'postgres';
    $pass = $_ENV['PGSQL_PASSWORD'] ?? '';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✅ Conexión PostgreSQL exitosa!\n";
    echo "Servidor: {$host}:{$port}\n";
    echo "Base de datos: {$dbname}\n";

    // Test query
    $version = $pdo->query('SELECT version()')->fetchColumn();
    echo "Versión: {$version}\n";

} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}
```

Ejecutar:
```bash
php test_pgsql.php
```

---

## 🚨 Diferencias Importantes MySQL vs PostgreSQL

### **1. Sintaxis SQL**

| Característica | MySQL | PostgreSQL |
|----------------|-------|------------|
| **Auto-increment** | `AUTO_INCREMENT` | `SERIAL` o `IDENTITY` |
| **Límite de filas** | `LIMIT 10` | `LIMIT 10` (igual) |
| **Concatenación** | `CONCAT(a, b)` | `a || b` |
| **IF condicional** | `IF(condition, true, false)` | `CASE WHEN condition THEN true ELSE false END` |
| **IFNULL** | `IFNULL(col, 0)` | `COALESCE(col, 0)` |
| **Fecha actual** | `NOW()` o `CURRENT_TIMESTAMP` | `NOW()` o `CURRENT_TIMESTAMP` (igual) |
| **Backticks** | \`column_name\` | "column_name" o sin comillas |

### **2. Tipos de Datos**

| MySQL | PostgreSQL | Notas |
|-------|------------|-------|
| `TINYINT(1)` | `BOOLEAN` | Para valores true/false |
| `DATETIME` | `TIMESTAMP` | Fecha + hora |
| `TEXT` | `TEXT` | Texto ilimitado |
| `VARCHAR(255)` | `VARCHAR(255)` | Igual |
| `INT` | `INTEGER` | Enteros |
| `DECIMAL(10,2)` | `NUMERIC(10,2)` | Decimales |

### **3. Migraciones SQL**

**Ejemplo MySQL → PostgreSQL:**

```sql
-- MySQL
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- PostgreSQL
CREATE TABLE employees (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 📁 Próximos Pasos

1. **✅ Configuración inicial** - Completado
2. **⏳ Adaptador de Query Builder** - Ajustar sintaxis SQL
3. **⏳ Migración de esquemas** - Convertir DDL MySQL → PostgreSQL
4. **⏳ Migración de datos** - ETL MySQL → PostgreSQL
5. **⏳ Testing** - Verificar funcionalidad completa
6. **⏳ Optimización** - Índices y performance tuning

---

## 🔧 Herramientas Útiles

### **pgAdmin**
- GUI oficial de PostgreSQL
- https://www.pgadmin.org/

### **DBeaver**
- Cliente universal (MySQL + PostgreSQL)
- https://dbeaver.io/

### **pg_dump / pg_restore**
- Backup y restauración nativa
```bash
# Backup
pg_dump -U planilla_user -d planilla_prod -F c -f backup.dump

# Restore
pg_restore -U planilla_user -d planilla_prod backup.dump
```

### **pgloader**
- Migración automática MySQL → PostgreSQL
```bash
# Instalar
sudo apt install pgloader

# Migrar
pgloader mysql://root@localhost/planilla_prod postgresql://planilla_user@localhost/planilla_prod
```

---

## 🆘 Troubleshooting

### **Error: "could not connect to server"**

```bash
# Verificar que PostgreSQL esté corriendo
sudo systemctl status postgresql

# Iniciar si está detenido
sudo systemctl start postgresql
```

### **Error: "password authentication failed"**

```bash
# Editar pg_hba.conf
sudo nano /etc/postgresql/[version]/main/pg_hba.conf

# Cambiar de 'peer' a 'md5'
local   all   all   md5

# Reiniciar
sudo systemctl restart postgresql
```

### **Error: "database does not exist"**

```sql
-- Crear la base de datos
sudo -u postgres createdb planilla_prod

-- O desde psql
sudo -u postgres psql
CREATE DATABASE planilla_prod;
```

---

## 📞 Soporte

Si encuentras problemas:
1. Verificar logs de PostgreSQL: `/var/log/postgresql/`
2. Revisar configuración en `.env`
3. Ejecutar `test_pgsql.php` para validar conexión
4. Consultar documentación oficial: https://www.postgresql.org/docs/

---

**Última Actualización**: 26 de Enero, 2026
**Versión del Sistema**: 3.5.20
