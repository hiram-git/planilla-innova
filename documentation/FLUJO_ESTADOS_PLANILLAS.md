# 📋 Flujo de Estados de Planillas - Sistema Planilla Simple

> **Versión**: 2.0  
> **Fecha**: Septiembre 2025  
> **Funcionalidad**: Control de estados y rollback de acumulados

## 🔄 **Diagrama de Estados**

```
CREAR ──────────────────→ PENDIENTE
                             │
                             ▼ (PROCESAR)
                          PROCESADA ◄─────────┐
                          │ │ │              │
                          │ │ └─(REPROCESAR)─┘
                          │ │ 
                          │ └─(MARCAR PENDIENTE)─→ PENDIENTE
                          │
                          ▼ (CERRAR)
                        CERRADA
                          │
                          └─(ABRIR)───────────→ PROCESADA
```

## 📝 **Detalle de Transiciones**

### **1. CREAR → PENDIENTE**
- Nueva planilla creada
- Estado inicial: `PENDIENTE`
- **Acciones disponibles**: Procesar, Editar

### **2. PENDIENTE → PROCESADA**
- **Acción**: Procesar planilla
- Se calculan conceptos y acumulados
- **Acciones disponibles**: Reprocesar, Marcar como Pendiente, Cerrar

### **3. PROCESADA → PROCESADA**
- **Acción**: Reprocesar planilla
- Se recalculan conceptos y acumulados
- Mantiene estado `PROCESADA`

### **4. PROCESADA → PENDIENTE**
- **Acción**: Marcar como Pendiente
- Permite editar nuevamente la planilla
- **Acciones disponibles**: Procesar, Editar

### **5. PROCESADA → CERRADA**
- **Acción**: Cerrar planilla
- Se consolidan acumulados finales
- **Acciones disponibles**: Abrir

### **6. CERRADA → PROCESADA** ⭐ **(NUEVA FUNCIONALIDAD)**
- **Acción**: Abrir planilla
- **Rollback automático** de acumulados
- Requiere motivo obligatorio
- Se registra en auditoría
- **Acciones disponibles**: Reprocesar, Marcar como Pendiente, Cerrar

## 🔐 **Seguridad y Auditoría**

- ✅ **CSRF tokens** en todas las transiciones
- ✅ **Rollback automático** de acumulados al abrir
- ✅ **Motivo obligatorio** para abrir planillas cerradas
- ✅ **Log de auditoría** en tabla `planilla_auditoria`
- ✅ **Transacciones atómicas** (todo o nada)

## 🎯 **Botones por Estado**

| Estado | Botones Disponibles |
|--------|-------------------|
| **PENDIENTE** | 🟢 Procesar, ✏️ Editar, 📋 Editar Detalles |
| **PROCESADA** | 🔄 Reprocesar, ⏰ Marcar Pendiente, 🔒 Cerrar |
| **CERRADA** | 🔓 **Abrir** (con rollback automático) |

## ⚠️ **Consideraciones Importantes**

1. **Rollback de Acumulados**: Al abrir una planilla cerrada se revierten automáticamente todos los acumulados procesados
2. **Motivo Obligatorio**: Se requiere justificación para abrir planillas cerradas
3. **Auditoría Completa**: Todos los cambios se registran con usuario, fecha y motivo
4. **Atomicidad**: Si falla el rollback, no se cambia el estado de la planilla

## 🔧 **Implementación Técnica**

### **Base de Datos**
- **Tabla principal**: `planilla_cabecera` 
- **Campo estado**: ENUM('PENDIENTE', 'PROCESADA', 'CERRADA')
- **Auditoría**: `planilla_auditoria` (nuevo registro por cada cambio de estado)
- **Acumulados**: 
  - `empleados_acumulados_historicos` (rollback automático)
  - `planillas_acumulados_consolidados` (eliminación de registros)

### **Controlador y Rutas**
- **Archivo**: `app/Controllers/PayrollController.php`
- **Método principal**: `reopen($id)` 
- **Ruta**: `POST /panel/payrolls/{id}/reopen`
- **Middleware**: CSRF token requerido
- **Rollback**: Método `rollbackAccumulatedData($payrollId)`

### **Vista y JavaScript**
- **Archivo**: `app/Views/admin/payroll/show.php`
- **Botón**: Aparece solo cuando `$payroll['estado'] === 'CERRADA'`
- **Modal**: `#reopenModal` con validación de motivo obligatorio
- **Handler**: `#reopenBtn` y `#confirmReopen`

### **Campos de Auditoría**
```sql
ALTER TABLE planilla_cabecera ADD COLUMN (
    fecha_reapertura TIMESTAMP NULL,
    usuario_reapertura VARCHAR(100) NULL,
    motivo_reapertura TEXT NULL
);
```

## 🎯 **Casos de Uso**

### **Caso 1: Error en Cálculo Detectado**
1. Planilla procesada y cerrada
2. Se detecta error en concepto o acumulado
3. **Abrir planilla** → Rollback automático de acumulados
4. Corregir concepto/empleado
5. Reprocesar y cerrar nuevamente

### **Caso 2: Empleado Agregado Tardío**
1. Planilla cerrada sin empleado X
2. Necesidad de incluir empleado X
3. **Abrir planilla** → Estado PROCESADA
4. Agregar empleado X y conceptos
5. Reprocesar → Recalcula acumulados con empleado X
6. Cerrar planilla actualizada

### **Caso 3: Ajuste de Acumulados**
1. Planilla cerrada con acumulados incorrectos
2. **Abrir planilla** → Rollback de acumulados consolidados
3. Ajustar configuración de conceptos acumulables
4. Reprocesar → Acumulados se recalculan correctamente
5. Cerrar con datos corregidos

## 📊 **Métricas y Monitoreo**

- **Tabla auditoría**: Tracking completo de aperturas
- **Logs**: Error log en `PayrollController@reopen`
- **Alertas**: Notificaciones en interfaz con conteo de registros afectados
- **Validaciones**: Verificación de integridad pre y post rollback

---

*Documentación actualizada por Claude Code - Sistema Planilla Simple v2.0*