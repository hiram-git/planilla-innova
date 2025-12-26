# 🕐 Configuración Crontab para Asistencias - Producción

## 📋 Tabla de Contenidos
- [Configuración Recomendada](#configuración-recomendada)
- [Explicación de Cada Script](#explicación-de-cada-script)
- [Horarios y Frecuencias](#horarios-y-frecuencias)
- [Logs y Monitoreo](#logs-y-monitoreo)
- [Troubleshooting](#troubleshooting)

---

## ✅ Configuración Recomendada (OPCIÓN A)

### **Para empresas con horario laboral estándar (L-V, 8 AM - 6 PM)**

```bash
# ============================================================================
# ASISTENCIAS - CRON JOBS PRODUCCIÓN
# ============================================================================

# 1. SINCRONIZACIÓN API: Cada 15 minutos durante horario laboral (6 AM - 8 PM)
#    Sincroniza TODO EL DÍA ACTUAL desde la API Base44
*/15 6-20 * * * /usr/bin/php /var/www/html/planilla/scripts/cron/sync_attendance.php >> /var/log/planilla/cron_sync.log 2>&1

# 2. PROCESAMIENTO DE FIN DE DÍA: Una vez al día a las 9 PM (L-V)
#    Consolida registros, detecta ausencias, marca omisiones, calcula métricas
0 21 * * 1-5 /usr/bin/php /var/www/html/planilla/scripts/cron/end_of_day_processing.php >> /var/log/planilla/cron_eod.log 2>&1

# 3. PIPELINE COMPLETO (OPCIONAL): Sábados a las 2 AM para reprocesar semana
#    Útil para corregir datos históricos o recalcular métricas
0 2 * * 6 /usr/bin/php /var/www/html/planilla/scripts/cron/process_attendance_pipeline.php >> /var/log/planilla/cron_pipeline.log 2>&1
```

---

## 🔄 Configuración Alternativa (OPCIÓN B)

### **Para empresas con operación 24/7 o turnos rotativos**

```bash
# 1. SINCRONIZACIÓN API: Cada 15 minutos TODO EL DÍA
*/15 * * * * /usr/bin/php /var/www/html/planilla/scripts/cron/sync_attendance.php >> /var/log/planilla/cron_sync.log 2>&1

# 2. PROCESAMIENTO DE FIN DE DÍA: Dos veces al día (turno mañana y turno noche)
0 15,23 * * * /usr/bin/php /var/www/html/planilla/scripts/cron/end_of_day_processing.php >> /var/log/planilla/cron_eod.log 2>&1

# 3. PIPELINE COMPLETO: Una vez a la semana (Domingo 3 AM)
0 3 * * 0 /usr/bin/php /var/www/html/planilla/scripts/cron/process_attendance_pipeline.php >> /var/log/planilla/cron_pipeline.log 2>&1
```

---

## 📊 Explicación de Cada Script

### **1️⃣ sync_attendance.php**

**Función**: Sincroniza marcaciones desde API Base44 al sistema local

**Cambio implementado**: Ahora sincroniza **TODO EL DÍA ACTUAL** en lugar de solo registros nuevos

```php
// ANTES (línea 138):
$stats = $syncService->syncSince(); // Solo registros nuevos

// AHORA (líneas 137-139):
$today = date('Y-m-d');
$stats = $syncService->syncByDateRange($today, $today); // TODO el día actual
```

**¿Qué hace?**:
- Lee configuración activa de API para cada tenant
- Llama a la API Base44 con rango de fechas = día actual completo
- Inserta/actualiza registros en `attendance_records` con `is_processed = 0`
- Registra estadísticas en `attendance_sync_log`

**¿Cuándo ejecutar?**:
- ✅ **Cada 15 minutos** durante horario laboral (6 AM - 8 PM)
- ✅ **Cada 15 minutos todo el día** si hay turnos 24/7

**Ventajas**:
- ✅ Asegura que marcaciones tardías o corregidas del día se sincronicen
- ✅ No depende del `last_sync_at` para obtener datos del día actual
- ✅ Permite reprocesar marcaciones editadas en el dispositivo

---

### **2️⃣ end_of_day_processing.php** ⭐ NUEVO

**Función**: Procesamiento completo de fin de día para detección de ausencias y cálculo final

**¿Qué hace?**:
1. **PASO 1**: Consolida `attendance_records` → `attendance_detail` (solo del día)
2. **PASO 2**: Detecta empleados SIN marcación y crea registros de AUSENCIA
3. **PASO 3**: Marca marcaciones incompletas (solo entrada o solo salida) como OMISIÓN
4. **PASO 4**: Calcula métricas finales (horas, tardanzas, horas extras, score puntualidad)
5. **PASO 5**: Actualiza estadísticas del `attendance_header`

**¿Cuándo ejecutar?**:
- ✅ **Una vez al día a las 9 PM** (después del horario laboral)
- ✅ **Solo días laborables** (L-V) con `1-5` en el crontab
- ⚠️ **NO ejecutar múltiples veces** - podría duplicar ausencias

**Ventajas**:
- ✅ Detección precisa de ausencias (todos ya deberían haber marcado)
- ✅ Cálculos finales del día completos
- ✅ No interfiere con marcaciones en tiempo real
- ✅ Puede recibir fecha como argumento: `php end_of_day_processing.php 2025-12-25`

**Logs**:
- Ubicación: `/var/log/planilla/cron_eod.log`
- Incluye: Resumen por tenant, ausencias detectadas, omisiones, cálculos

---

### **3️⃣ process_attendance_pipeline.php**

**Función**: Pipeline completo de procesamiento (registros → detalles → cálculos)

**¿Qué hace?**:
- PASO 1: Consolida registros pendientes (últimos N días)
- PASO 2: Calcula métricas para los últimos 7 días laborables

**¿Cuándo ejecutar?**:
- ✅ **Una vez a la semana** (Sábado 2 AM o Domingo 3 AM)
- ✅ **Manualmente** cuando necesites reprocesar datos históricos
- ⚠️ **NO ejecutar cada 15 minutos** - es demasiado pesado y redundante

**Ventajas**:
- ✅ Útil para correcciones masivas
- ✅ Recalcula métricas de días anteriores si hubo cambios
- ✅ Backup/validación semanal de datos

---

## ⏰ Horarios y Frecuencias

### **Comparación de Opciones**

| Script | Opción A (Horario Laboral) | Opción B (24/7) | Cuándo Usar |
|--------|---------------------------|----------------|-------------|
| **sync_attendance.php** | `*/15 6-20 * * *` (cada 15min, 6 AM-8 PM) | `*/15 * * * *` (cada 15min, todo el día) | A: Oficinas / B: Hospitales, Fábricas |
| **end_of_day_processing.php** | `0 21 * * 1-5` (9 PM, L-V) | `0 15,23 * * *` (3 PM y 11 PM, diario) | A: Un turno / B: Dos turnos |
| **process_attendance_pipeline.php** | `0 2 * * 6` (2 AM, Sábados) | `0 3 * * 0` (3 AM, Domingos) | Semanal para ambos |

### **Explicación de Sintaxis Crontab**

```
┌───────────── minuto (0 - 59)
│ ┌───────────── hora (0 - 23)
│ │ ┌───────────── día del mes (1 - 31)
│ │ │ ┌───────────── mes (1 - 12)
│ │ │ │ ┌───────────── día de la semana (0 - 7) (0 y 7 = Domingo)
│ │ │ │ │
* * * * * comando a ejecutar
```

**Ejemplos**:
- `*/15 * * * *` = Cada 15 minutos, todo el día
- `*/15 6-20 * * *` = Cada 15 minutos, solo de 6 AM a 8 PM
- `0 21 * * 1-5` = A las 9 PM (21:00), solo Lunes a Viernes
- `0 2 * * 6` = A las 2 AM, solo Sábados

---

## 📂 Logs y Monitoreo

### **Ubicación de Logs**

```bash
/var/log/planilla/
├── cron_sync.log          # Sincronización API (cada 15min)
├── cron_eod.log           # Procesamiento fin de día (9 PM)
└── cron_pipeline.log      # Pipeline semanal (Sábado/Domingo)
```

### **Crear Directorio de Logs**

```bash
sudo mkdir -p /var/log/planilla
sudo chown www-data:www-data /var/log/planilla
sudo chmod 755 /var/log/planilla
```

### **Verificar Logs en Tiempo Real**

```bash
# Ver últimas 50 líneas del log de sincronización
tail -n 50 /var/log/planilla/cron_sync.log

# Seguir log en tiempo real
tail -f /var/log/planilla/cron_eod.log

# Buscar errores
grep "ERROR\|❌" /var/log/planilla/*.log

# Ver resumen de hoy
grep "$(date +%Y-%m-%d)" /var/log/planilla/cron_eod.log
```

### **Rotación de Logs (Logrotate)**

Crear archivo `/etc/logrotate.d/planilla-cron`:

```bash
/var/log/planilla/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

---

## 🔍 Troubleshooting

### **Problema 1: Cron no se ejecuta**

**Verificar**:
```bash
# Ver crontab actual
crontab -l

# Ver logs del sistema cron
sudo grep CRON /var/log/syslog

# Verificar servicio cron
sudo systemctl status cron
```

**Solución**:
```bash
# Editar crontab
crontab -e

# Reiniciar servicio cron
sudo systemctl restart cron
```

---

### **Problema 2: Script da error de permisos**

**Verificar**:
```bash
ls -la /var/www/html/planilla/scripts/cron/
```

**Solución**:
```bash
sudo chmod +x /var/www/html/planilla/scripts/cron/*.php
sudo chown www-data:www-data /var/www/html/planilla/scripts/cron/*.php
```

---

### **Problema 3: No detecta ausencias**

**Verificar en log**:
```bash
grep "ausencias detectadas" /var/log/planilla/cron_eod.log
```

**Posibles causas**:
1. El script `end_of_day_processing.php` no se ejecutó
2. Todos los empleados tienen `marca_asistencia = 0`
3. El día es fin de semana o feriado no pagado

**Solución**:
```bash
# Ejecutar manualmente para ayer
php /var/www/html/planilla/scripts/cron/end_of_day_processing.php 2025-12-25
```

---

### **Problema 4: Marcaciones duplicadas**

**Verificar**:
```sql
SELECT employee_id, COUNT(*) as count
FROM attendance_detail
WHERE header_id = (SELECT id FROM attendance_header WHERE attendance_date = '2025-12-26')
GROUP BY employee_id
HAVING count > 1;
```

**Posible causa**: El script `end_of_day_processing.php` se ejecutó múltiples veces

**Solución**:
```bash
# Verificar si hay múltiples entradas en crontab
crontab -l | grep end_of_day

# Eliminar duplicados manualmente en la BD
DELETE d1 FROM attendance_detail d1
INNER JOIN attendance_detail d2
WHERE d1.id > d2.id
  AND d1.employee_id = d2.employee_id
  AND d1.header_id = d2.header_id;
```

---

## 📋 Checklist de Implementación

- [ ] **1. Crear directorio de logs**
  ```bash
  sudo mkdir -p /var/log/planilla
  sudo chown www-data:www-data /var/log/planilla
  ```

- [ ] **2. Copiar nuevo script a producción**
  ```bash
  scp scripts/cron/end_of_day_processing.php root@servidor:/var/www/html/planilla/scripts/cron/
  ```

- [ ] **3. Dar permisos de ejecución**
  ```bash
  sudo chmod +x /var/www/html/planilla/scripts/cron/*.php
  ```

- [ ] **4. Editar crontab**
  ```bash
  crontab -e
  ```

- [ ] **5. Pegar configuración recomendada (Opción A o B)**

- [ ] **6. Verificar ejecución inicial**
  ```bash
  # Esperar 15 minutos y verificar log de sincronización
  tail -f /var/log/planilla/cron_sync.log
  ```

- [ ] **7. Probar end_of_day manualmente**
  ```bash
  php /var/www/html/planilla/scripts/cron/end_of_day_processing.php 2025-12-25
  ```

- [ ] **8. Configurar logrotate**
  ```bash
  sudo nano /etc/logrotate.d/planilla-cron
  ```

- [ ] **9. Monitorear durante 3 días**

- [ ] **10. Documentar en wiki/manual de operaciones**

---

## 🎯 Resumen de Cambios Implementados

### **✅ Modificaciones en Archivos Existentes**

**Archivo**: `scripts/cron/sync_attendance.php`
**Líneas**: 137-141
**Cambio**:
```php
// ANTES:
$stats = $syncService->syncSince();

// AHORA:
$today = date('Y-m-d');
$stats = $syncService->syncByDateRange($today, $today); // Sincroniza TODO el día
echo "📅 Rango sincronizado: {$today} (día completo)\n";
```

### **✅ Archivos Nuevos Creados**

1. **`scripts/cron/end_of_day_processing.php`** (323 líneas)
   - Procesamiento completo de fin de día
   - Detección de ausencias
   - Marcado de omisiones
   - Cálculo de métricas finales

2. **`CONFIGURACION_CRONTAB_PRODUCCION.md`** (este documento)
   - Guía completa de configuración
   - Troubleshooting
   - Checklist de implementación

---

## 📞 Soporte

Para dudas sobre la configuración de crontab:

1. **Revisar logs**: `/var/log/planilla/*.log`
2. **Ejecutar manualmente**: Probar scripts con fecha específica
3. **Verificar BD**: Consultar `attendance_header`, `attendance_detail`, `attendance_calculations`

**Queries útiles**:

```sql
-- Ver procesamiento del día
SELECT * FROM attendance_header
WHERE attendance_date = CURDATE()
ORDER BY id DESC LIMIT 1;

-- Ver ausencias detectadas hoy
SELECT e.firstname, e.lastname, ad.status, ad.notes
FROM attendance_detail ad
INNER JOIN employees e ON e.id = ad.employee_id
WHERE ad.status = 'ABSENT'
  AND ad.header_id = (SELECT id FROM attendance_header WHERE attendance_date = CURDATE());

-- Ver última sincronización
SELECT * FROM attendance_sync_log
ORDER BY id DESC LIMIT 5;
```

---

**Versión**: 1.0
**Fecha**: 26 de Diciembre, 2025
**Autor**: Sistema Planillas Innova
