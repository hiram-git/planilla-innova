# Sincronización de Conceptos Core desde BD de Referencia

## 📋 Descripción

Script para sincronizar conceptos fundamentales desde una base de datos de referencia (`PINN4941184`) a todas las bases de datos tenant activas.

## 🎯 Conceptos Sincronizados

| Código | Descripción                |
|--------|----------------------------|
| 01     | Sueldo                     |
| 02     | Seguro Social              |
| 03     | Seguro Educativo           |
| 04     | Impuesto sobre la renta    |

## ⚙️ Funcionamiento

Para cada concepto en cada tenant:

1. **Si el concepto EXISTE**: Actualiza solo la **fórmula** desde la BD de referencia
2. **Si el concepto NO EXISTE**: Crea el concepto completo con todos sus atributos (tipo, unidad, fórmula, etc.)

## 🚀 Uso

### Ejecutar en todos los tenants activos

```bash
php database/migrations/sync_core_concepts_from_reference.php
```

### Modo prueba (no ejecuta, solo muestra qué haría)

```bash
php database/migrations/sync_core_concepts_from_reference.php --dry-run
```

### Ejecutar solo en un tenant específico

```bash
php database/migrations/sync_core_concepts_from_reference.php --tenant=nombre-tenant
```

### Ver ayuda

```bash
php database/migrations/sync_core_concepts_from_reference.php --help
```

## 📊 Ejemplo de Salida

```
╔═══════════════════════════════════════════════════════════════════════════╗
║       SINCRONIZACIÓN CONCEPTOS CORE DESDE BD DE REFERENCIA               ║
╚═══════════════════════════════════════════════════════════════════════════╝

Modo: ⚡ EJECUCIÓN REAL
BD Referencia: PINN4941184

Conceptos a sincronizar:
   • 01 - Sueldo
   • 02 - Seguro Social
   • 03 - Seguro Educativo
   • 04 - Impuesto sobre la renta

✅ Conectado a base de datos master: planilla_prod
✅ Conectado a BD de referencia: PINN4941184

📋 Tenants encontrados: 3

================================================================================
🏢 TENANT: innova (DB: innova_db)
================================================================================
   ✅ Conectado exitosamente

   🔄 Sincronizando: 01 - Sueldo
         ⏭️  '01' - Fórmula ya actualizada
   🔄 Sincronizando: 02 - Seguro Social
         ✅ '02' - Fórmula actualizada
   🔄 Sincronizando: 03 - Seguro Educativo
         ✅ '03' - Concepto creado
   🔄 Sincronizando: 04 - Impuesto sobre la renta
         ✅ '04' - Fórmula actualizada

   📊 Resumen:
      ✅ Creados: 1
      🔄 Actualizados: 2
      ⏭️  Sin cambios: 1

╔═══════════════════════════════════════════════════════════════════════════╗
║                           RESUMEN FINAL                                   ║
╚═══════════════════════════════════════════════════════════════════════════╝

📊 ESTADÍSTICAS GENERALES:
   Tenants procesados:     3
   ✅ Exitosos:            3
   ❌ Con errores:         0
   ➕ Conceptos creados:   4
   🔄 Conceptos actualizados: 8
   ⏭️  Conceptos sin cambios: 0

📋 DETALLE POR TENANT:
────────────────────────────────────────────────────────────────────────────
TENANT                    STATUS          CREADOS    ACTUALIZADOS SIN CAMBIOS
────────────────────────────────────────────────────────────────────────────
innova                    ✅ Exitoso     1          2            1
empresa2                  ✅ Exitoso     2          1            1
empresa3                  ✅ Exitoso     1          5            -
────────────────────────────────────────────────────────────────────────────

✅ Proceso completado
```

## 🔧 Requisitos Previos

1. **Base de datos de referencia**: Debe existir la BD `PINN4941184` con los 4 conceptos
2. **Configuración .env**: Verificar credenciales de conexión:
   ```
   TENANT_DB_HOST=localhost
   TENANT_DB_PORT=3306
   TENANT_DB_USER=root
   TENANT_DB_PASS=
   ```
3. **Tabla tenants**: Debe existir en la BD master con tenants en estado `ACTIVE`

## ⚠️ Notas Importantes

1. **Conceptos en referencia**: Si algún concepto no existe en `PINN4941184`, se omite con advertencia
2. **Solo fórmula se actualiza**: En conceptos existentes, SOLO se actualiza la fórmula, el resto de atributos no cambia
3. **Conceptos nuevos**: Se crea el concepto COMPLETO con todos los atributos de la BD de referencia
4. **Seguridad**: El script usa `--dry-run` para probar sin ejecutar cambios
5. **Logging**: Muestra detalles completos de cada operación

## 🛡️ Seguridad

- El script valida conexión a BD de referencia antes de procesar
- Maneja errores por tenant (si uno falla, continúa con los demás)
- Usa prepared statements para prevenir SQL injection
- No modifica la BD de referencia (solo lectura)

## 📝 Log de Ejecución

El script genera un log detallado en consola mostrando:
- Conexiones exitosas/fallidas
- Cada concepto procesado (creado/actualizado/omitido)
- Errores específicos si ocurren
- Resumen final con estadísticas

## 🔄 Casos de Uso

### Caso 1: Primera vez en tenant nuevo
- Los 4 conceptos se crean completos desde cero

### Caso 2: Actualizar fórmulas
- Los conceptos existen pero tienen fórmulas desactualizadas
- Solo se actualiza la fórmula, el resto queda igual

### Caso 3: Mix (algunos existen, otros no)
- Crea los que no existen
- Actualiza fórmulas de los que existen
- Omite los que ya están actualizados

## 📞 Soporte

Si encuentras errores:
1. Verifica que la BD `PINN4941184` existe y contiene los conceptos
2. Verifica credenciales en `.env`
3. Ejecuta con `--dry-run` primero para ver qué haría
4. Revisa el log de errores en la consola

---

**Versión**: 1.0.0
**Fecha**: 20-Ene-2026
**Autor**: Sistema de Planillas MVC
