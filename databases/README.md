# 🗄️ Database Scripts - Sistema de Planillas

## 📋 **Propósito**
Esta carpeta contiene todas las migraciones y scripts de base de datos para el sistema de planillas.

## 📜 **Migraciones SQL (migration_*.sql)**
- `migration_clave_seguro_social.sql` - Agrega campo clave_seguro_social a empleados
- `migration_new_acumulados_structure.sql` - **NUEVA ARQUITECTURA DE ACUMULADOS v2.5.0**

### Ejecutar Migraciones SQL:
```sql
-- Ejecutar en phpMyAdmin o cliente MySQL
SOURCE databases/migration_clave_seguro_social.sql;
SOURCE databases/migration_new_acumulados_structure.sql;
```

### ⚠️ **IMPORTANTE - Nueva Arquitectura v2.5.0**
La migración `migration_new_acumulados_structure.sql` implementa un **sistema dual de acumulados**:
- Respalda tabla actual como `acumulados_por_planilla_backup`
- Crea nueva tabla `acumulados_por_empleado` (detallado por transacción)
- Recrea tabla `acumulados_por_planilla` (consolidado optimizado)
- Crea vista `vista_acumulados_anuales` para reportes anuales

## 🏗️ **Scripts de Creación (create_*.php)**
- `create_tipos_acumulados_table.php` - Crea tabla tipos_acumulados con datos básicos
- `create_simple_acumulados_table.php` - Crea tabla acumulados_por_planilla

### Ejecutar Scripts PHP:
1. **Desde navegador**: `http://localhost/planilla-claude-v2/databases/create_tipos_acumulados_table.php`
2. **Desde CLI**: `php databases/create_tipos_acumulados_table.php`

## 📊 **Estructura de Tablas Creadas**

### tipos_acumulados
Tipos básicos de acumulados para legislación panameña:
- XIII_MES - Décimo tercer mes
- PRIMA_ANTIGUEDAD - Prima de antigüedad  
- VACACIONES - Vacaciones anuales
- INDEMNIZACION - Indemnización por despido
- GASTOS_REP - Gastos de representación

### acumulados_por_planilla
Registro detallado de acumulados por planilla específica:
- Tracking preciso por empleado y planilla
- Campos: tipo_acumulado_id, employee_id, planilla_id, monto, concepto_id
- Permite rollback exacto al reabrir planillas

## ⚠️ **Importante**
- Hacer backup antes de ejecutar migraciones
- Los scripts PHP verifican existencia antes de crear
- Scripts son idempotentes (pueden ejecutarse múltiples veces)
- Las migraciones SQL deben ejecutarse una sola vez