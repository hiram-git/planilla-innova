# 🚀 Pruebas de Stress - Sistema de Planillas

Este conjunto de scripts permite realizar pruebas de stress completas del sistema de planillas con 5000 empleados.

## 📋 Archivos Incluidos

- **`seeder_employees.php`** - Crea 5000 empleados ficticios
- **`stress_test.php`** - Ejecuta prueba de stress del procesamiento de planillas
- **`cleanup_seeder.php`** - Limpia los datos de prueba

## 🛠️ Preparación del Entorno

### Requisitos Previos
- PHP 8.2+ con CLI habilitado
- MySQL/MariaDB corriendo
- Memoria PHP mínima: 512MB (recomendado: 1GB+)
- Tiempo de ejecución PHP: sin límite o 600s+

### Configuración PHP (php.ini)
```ini
memory_limit = 1G
max_execution_time = 600
mysql.connect_timeout = 60
```

## 📊 Ejecución de Pruebas

### Paso 1: Crear Empleados de Prueba
```bash
cd /path/to/planilla-claude-v2
php seeder_employees.php
```

**Características del seeder:**
- Crea 5000 empleados con datos realistas
- Distribuye empleados entre las 7 posiciones existentes
- Asigna horarios aleatorios
- Procesa en lotes de 100 para optimizar memoria
- Genera employee_id únicos (EMP00001-EMP05000)

**Tiempo estimado:** 30-60 segundos
**Memoria utilizada:** ~50-100MB

### Paso 2: Ejecutar Prueba de Stress
```bash
php stress_test.php
```

**Lo que hace:**
- Crea una planilla de prueba
- Procesa todos los empleados con conceptos activos
- Mide tiempo, memoria y rendimiento
- Verifica integridad de datos
- Ejecuta pruebas de consultas adicionales

**Tiempo estimado:** 5-15 minutos (dependiente de conceptos)
**Memoria utilizada:** 200-500MB

### Paso 3: Limpieza (Opcional)
```bash
php cleanup_seeder.php
```

**Lo que limpia:**
- Empleados creados por el seeder (EMP*)
- Planillas procesadas en las pruebas
- Detalles de planilla asociados
- Resetea contadores auto_increment

## 📈 Métricas Esperadas

### Rendimiento Óptimo
- **Velocidad:** 50-100 empleados/segundo
- **Memoria:** Menos de 512MB pico
- **Tiempo total:** Menos de 5 minutos
- **Errores:** 0 errores críticos

### Rendimiento Aceptable  
- **Velocidad:** 20-50 empleados/segundo
- **Memoria:** 512MB-1GB pico
- **Tiempo total:** 5-15 minutos
- **Errores:** Menos de 1% de empleados

### Indicadores de Problemas
- **Velocidad:** Menos de 20 empleados/segundo
- **Memoria:** Más de 1GB pico
- **Tiempo total:** Más de 15 minutos
- **Errores:** Más de 5% de empleados

## 🔧 Troubleshooting

### Error: "MySQL server has gone away"
```bash
# Aumentar timeout en MySQL
mysql -u root -e "SET GLOBAL wait_timeout = 3600;"
mysql -u root -e "SET GLOBAL interactive_timeout = 3600;"
```

### Error: Memory limit exceeded
```bash
# Editar php.ini
memory_limit = 2G

# O ejecutar con parámetro
php -d memory_limit=2G stress_test.php
```

### Error: Maximum execution time exceeded
```bash
# Ejecutar sin límite de tiempo
php -d max_execution_time=0 stress_test.php
```

### Base de datos lenta
```sql
-- Añadir índices para optimizar
CREATE INDEX idx_planilla_detalle_cabecera ON planilla_detalle(planilla_cabecera_id);
CREATE INDEX idx_planilla_detalle_employee ON planilla_detalle(employee_id);
CREATE INDEX idx_employees_position ON employees(position_id);
```

## 📊 Interpretación de Resultados

### Sección "TIEMPO DE PROCESAMIENTO"
- **Tiempo total:** Duración completa de la prueba
- **Empleados procesados:** Cantidad procesada exitosamente
- **Conceptos calculados:** Total de cálculos realizados
- **Velocidad:** Empleados por segundo (KPI principal)

### Sección "USO DE MEMORIA"
- **Memoria inicial:** Memoria base del script
- **Memoria pico:** Máximo uso durante la prueba
- **Memoria utilizada:** Diferencia pico - inicial

### Sección "INTEGRIDAD DE DATOS"
- **Registros creados:** Filas en planilla_detalle
- **Empleados procesados:** Empleados únicos procesados
- **Total neto generado:** Suma total de la planilla

## 🎯 Optimizaciones Sugeridas

### Si el rendimiento es bajo:

1. **Base de datos:**
   - Verificar índices en tablas principales
   - Optimizar queries de conceptos
   - Aumentar buffer pool de MySQL

2. **PHP:**
   - Usar OPcache
   - Optimizar algoritmos de cálculo
   - Implementar cache de conceptos

3. **Sistema:**
   - Usar SSD para base de datos
   - Aumentar RAM disponible
   - Verificar CPU usage

### Si hay muchos errores:

1. **Verificar conceptos:**
   - Fórmulas válidas en todos los conceptos
   - Dependencias circulares
   - Variables undefined

2. **Verificar empleados:**
   - Posiciones válidas asignadas
   - Horarios configurados correctamente
   - Datos completos en employees

## 🚨 IMPORTANTE

- **Backup:** Haga backup de la BD antes de las pruebas
- **Entorno:** Ejecute solo en desarrollo, nunca en producción
- **Recursos:** Asegúrese de tener recursos suficientes
- **Limpieza:** Use cleanup_seeder.php después de las pruebas

## 📞 Soporte

Si encuentra problemas:

1. Verificar logs de errores PHP
2. Revisar logs de MySQL
3. Monitorear recursos del sistema
4. Ejecutar pruebas por fases para aislar problemas

---

**Creado para:** Sistema de Planillas MVC  
**Fecha:** Agosto 2025  
**Versión:** 1.0