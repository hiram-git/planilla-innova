# 🧪 Tests y Debugging - Sistema de Planillas

## 📋 **Propósito**
Esta carpeta contiene todos los scripts de testing, debugging y corrección para el sistema de planillas.

## 🔍 **Scripts de Diagnóstico (debug_*.php)**
- `debug_planilla_detalle_structure.php` - Diagnostica estructura de tabla planilla_detalle
- `debug_table_structure.php` - Verifica estructura general de tablas
- `debug_foreign_keys.php` - Diagnostica claves foráneas y dependencias
- `debug_reopen_button.php` - Diagnostica problemas con botones de reapertura

## 🧪 **Scripts de Testing (test_*.php)**
- `test_final_pending_functionality.php` - Test completo de funcionalidad PENDIENTE
- `test_pending_records_deletion.php` - Prueba eliminación de registros
- `test_acumulados.php` - Test del sistema de acumulados

## 🔧 **Scripts de Corrección (fix_*.php)**
- `fix_planilla_detalle_structure.php` - Corrige estructura de planilla_detalle
- `fix_acumulados_table_structure.php` - Corrige tabla de acumulados
- `force_fix_acumulados_table.php` - Solución de emergencia para acumulados

## 📊 **Scripts de Verificación (verify_*.php)**
- `verify_acumulados_structure.php` - Verifica estructura de sistema de acumulados

## 🚀 **Cómo Ejecutar**
1. **Desde navegador**: `http://localhost/planilla-claude-v2/tests/nombre_script.php`
2. **Desde CLI**: `php tests/nombre_script.php` (desde la raíz del proyecto)

## ⚠️ **Importante**
- Los scripts de fix modifican la base de datos
- Los scripts de test/debug solo leen y verifican
- Siempre ejecutar scripts de diagnóstico antes de corrección
- Hacer backup de la BD antes de ejecutar scripts de fix