# 🎨 Patrón de Implementación GSAP en el Proyecto

**Versión**: 1.0
**Fecha**: 24 de Febrero, 2026
**Librería**: GSAP v3.12.5

## 📋 Tabla de Contenidos
- [Resumen Ejecutivo](#resumen-ejecutivo)
- [Arquitectura de Carga](#arquitectura-de-carga)
- [Patrón Correcto](#patrón-correcto)
- [Errores Comunes y Soluciones](#errores-comunes-y-soluciones)
- [Ejemplos Completos](#ejemplos-completos)
- [Checklist de Implementación](#checklist-de-implementación)

---

## 📝 Resumen Ejecutivo

Este documento establece el **patrón estándar** para implementar animaciones GSAP en módulos con DataTables del proyecto. Se basa en las lecciones aprendidas durante la implementación en los módulos de empleados (`/panel/employees/` y `/panel/employees/terminated`).

**Principio fundamental**: GSAP se carga una sola vez en el layout global y las funciones de animación se definen DESPUÉS de cargar el módulo DataTables.

---

## 🏗️ Arquitectura de Carga

### Ubicación de GSAP
✅ **CORRECTO**: GSAP está cargado en el layout global `app/Views/layouts/admin.php` línea 388-389:

```php
<!-- GSAP Animation Library -->
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
```

❌ **INCORRECTO**: Cargar GSAP en cada vista individual

### Orden de Carga de Scripts en el Layout

```
1. jQuery (plugins/jquery/jquery.min.js)
2. Bootstrap (plugins/bootstrap/js/bootstrap.bundle.min.js)
3. AdminLTE (dist/js/adminlte.min.js)
4. DataTables (plugins/datatables/...)
5. Select2, Toastr, SweetAlert2
6. Moment.js, DateRangePicker
7. GSAP 👈 (cargado globalmente)
8. Tenant Storage Manager
9. Global Scripts (toastr config, sidebar persistence)
10. Page Specific Scripts ($scripts variable)
```

---

## ✅ Patrón Correcto

### Estructura de Archivos

Para un módulo llamado `example`:

```
app/Views/admin/example/
  └── index.php                    # Vista principal

assets/javascript/modules/example/
  └── index.js                      # Módulo DataTables
```

### Paso 1: Vista PHP (`app/Views/admin/example/index.php`)

**ORDEN CRÍTICO**: Plugins → Módulo JS → Funciones GSAP

```php
<?php
use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();

// ==================================================
// 1. CARGAR PLUGINS ADICIONALES (si los necesitas)
// ==================================================
$scripts = $jsConfig . '
<script src="' . url('/plugins/datatables-buttons/js/dataTables.buttons.min.js') . '"></script>
<script src="' . url('/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') . '"></script>
';

// ==================================================
// 2. CARGAR MÓDULO DATATABLES
// ==================================================
$scripts .= "\n" . '<script src="' . url('/assets/javascript/modules/example/index.js') . '"></script>';

// ==================================================
// 3. DEFINIR FUNCIONES GSAP (DESPUÉS del módulo)
// ==================================================
$scripts .= '
<script>
// Flag global para controlar animación inicial
window.exampleTableIsInitialLoad = true;

// Función global para animar filas del DataTable
window.animateExampleTableRows = function() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const rows = $("#exampleTable tbody tr");

    // Si no hay filas, no hacer nada
    if (rows.length === 0) {
        return;
    }

    // Primero, asegurar que las filas estén ocultas
    gsap.set(rows, { opacity: 0, y: 0 });

    if (window.exampleTableIsInitialLoad) {
        // Primera carga: animación más elaborada
        gsap.set(rows, { y: 20 });
        gsap.to(rows, {
            opacity: 1,
            y: 0,
            duration: 0.4,
            stagger: 0.05,
            ease: "power2.out",
            clearProps: "all"
        });

        // Animar controles de paginación
        gsap.to(".dataTables_info, .dataTables_paginate", {
            opacity: 1,
            duration: 0.5,
            delay: 0.3
        });

        window.exampleTableIsInitialLoad = false;
    } else {
        // Recargas/filtros: fade rápido
        gsap.to(rows, {
            opacity: 1,
            duration: 0.3,
            stagger: 0.02,
            ease: "power1.out",
            clearProps: "all"
        });
    }

    // Animar iconos de acciones
    setupExampleActionButtonAnimations();
};

// Función para animar botones de acción
function setupExampleActionButtonAnimations() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const badges = $("#exampleTable .badge");
    const buttons = $("#exampleTable .btn-sm");
    const icons = $("#exampleTable .btn-sm i");

    // Animar badges de estado
    badges.each(function(index) {
        gsap.from(this, {
            scale: 0,
            duration: 0.4,
            delay: index * 0.02,
            ease: "back.out(2)"
        });
    });

    // Hover effects en botones de acción
    buttons.off("mouseenter.gsap mouseleave.gsap").on({
        "mouseenter.gsap": function() {
            if (!$(this).prop("disabled")) {
                gsap.to(this, {
                    scale: 1.15,
                    duration: 0.2,
                    ease: "power2.out"
                });
            }
        },
        "mouseleave.gsap": function() {
            gsap.to(this, {
                scale: 1,
                duration: 0.2,
                ease: "power2.out"
            });
        }
    });

    // Animación para iconos dentro de botones
    icons.off("mouseenter.gsap").on("mouseenter.gsap", function() {
        if (!$(this).closest(".btn").prop("disabled")) {
            gsap.to(this, {
                rotation: 360,
                duration: 0.5,
                ease: "power2.inOut"
            });
        }
    });
}
</script>
';

// ==================================================
// 4. ESTILOS (Ocultar paginación antes de animar)
// ==================================================
$styles = '
<link rel="stylesheet" href="' . url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<style>
/* GSAP - Ocultar solo elementos de paginación antes de animar */
.dataTables_info,
.dataTables_paginate {
    opacity: 0;
}
</style>';

include __DIR__ . '/../../layouts/admin.php';
?>
```

### Paso 2: Módulo JavaScript (`assets/javascript/modules/example/index.js`)

```javascript
/**
 * Módulo: Example
 */
$(document).ready(function() {
    const urls = window.APP_CONFIG?.urls || {};

    // Inicializar DataTable
    const exampleTable = $("#exampleTable").DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": urls.panel_url + "/example/datatables-ajax",
            "type": "GET"
        },
        "columns": [
            { "data": 0, "orderable": false }, // Foto
            { "data": 1 }, // ID
            { "data": 2 }, // Nombre
            { "data": 3, "orderable": false } // Acciones
        ],
        "order": [[1, "asc"]],
        "pageLength": 25,
        "responsive": true,
        "language": {
            "url": urls.datatables_spanish || "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        // ==================================================
        // CRÍTICO: drawCallback llama a la función GSAP
        // ==================================================
        "drawCallback": function(settings) {
            // GSAP: Animar filas después de cada draw
            if (typeof window.animateExampleTableRows === 'function') {
                // Pequeño delay para asegurar que el DOM esté listo
                setTimeout(function() {
                    window.animateExampleTableRows();
                }, 50);
            }
        }
    });
});
```

---

## ❌ Errores Comunes y Soluciones

### Error 1: Cargar GSAP en cada vista
```php
❌ INCORRECTO:
$scripts = '
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script>
window.animateExampleTableRows = function() { ... }
</script>
';
```

**Problema**: Carga duplicada, desperdicio de recursos, posibles conflictos.

**Solución**: GSAP ya está en el layout global, no lo cargues nuevamente.

---

### Error 2: Funciones GSAP ANTES del módulo DataTables
```php
❌ INCORRECTO:
$scripts = '
<script>
window.animateExampleTableRows = function() { ... }
</script>
<script src="' . url('/assets/javascript/modules/example/index.js') . '"></script>
';
```

**Problema**: Cuando DataTables inicializa y llama `drawCallback`, el DOM aún no está listo.

**Solución**: Cargar módulo PRIMERO, funciones GSAP DESPUÉS.

---

### Error 3: Usar array $scriptFiles con funciones inline
```php
❌ INCORRECTO:
$scripts = '
<script>
window.animateExampleTableRows = function() { ... }
</script>
';

$scriptFiles = [
    '/assets/javascript/modules/example/index.js'
];
```

**Problema**: El layout admin.php tiene dos modos:
- Si existe `$scriptFiles` (array) → ignora `$scripts` completamente
- Si NO existe `$scriptFiles` → usa `$scripts`

**Solución**: Usa SOLO `$scripts` concatenando todo en orden.

---

### Error 4: No ocultar elementos de paginación
```css
❌ SIN ESTILOS
(Los elementos de paginación aparecen antes de la animación)
```

**Solución**: Agregar CSS para ocultarlos inicialmente:
```css
.dataTables_info,
.dataTables_paginate {
    opacity: 0;
}
```

---

### Error 5: Olvidar clearProps en animaciones
```javascript
❌ INCORRECTO:
gsap.to(rows, {
    opacity: 1,
    y: 0,
    duration: 0.4
});
```

**Problema**: Los estilos inline permanecen después de la animación.

**Solución**: Agregar `clearProps: "all"`:
```javascript
gsap.to(rows, {
    opacity: 1,
    y: 0,
    duration: 0.4,
    clearProps: "all"
});
```

---

## 📚 Ejemplos Completos

### Ejemplo 1: Lista Simple (sin plugins adicionales)
Ver implementación completa en: `app/Views/admin/employees/index.php`

### Ejemplo 2: Lista con Botones Export (con plugins DataTables)
Ver implementación completa en: `app/Views/admin/employees/terminated.php`

---

## ✅ Checklist de Implementación

Usa este checklist cuando implementes GSAP en un nuevo módulo:

### Fase 1: Preparación
- [ ] Confirmar que GSAP está cargado en `admin.php` línea 388-389
- [ ] Identificar el ID único de la tabla DataTables (ej: `#exampleTable`)
- [ ] Determinar si necesitas plugins adicionales DataTables

### Fase 2: Vista PHP
- [ ] Crear variable `$scripts` con `JavaScriptHelper::renderConfigScript()`
- [ ] **SI necesitas plugins**: Cargar plugins PRIMERO con `$scripts .= '<script src="..."></script>'`
- [ ] Cargar módulo JS con `$scripts .= '<script src="' . url('/assets/javascript/modules/example/index.js') . '"></script>'`
- [ ] Definir funciones GSAP DESPUÉS con `$scripts .= '<script>...</script>'`
- [ ] Crear estilos con CSS para ocultar `.dataTables_info, .dataTables_paginate`
- [ ] **NO usar** array `$scriptFiles` si tienes funciones inline GSAP

### Fase 3: Módulo JavaScript
- [ ] Agregar `drawCallback` en configuración DataTable
- [ ] Llamar función GSAP con `setTimeout(..., 50)` dentro de `drawCallback`
- [ ] Verificar que la función usa el ID correcto de la tabla

### Fase 4: Funciones GSAP
- [ ] Crear flag global: `window.exampleTableIsInitialLoad = true`
- [ ] Crear función global: `window.animateExampleTableRows = function() { ... }`
- [ ] Validar disponibilidad de GSAP: `if (typeof gsap === "undefined") return;`
- [ ] Seleccionar filas: `const rows = $("#exampleTable tbody tr");`
- [ ] Usar `gsap.set()` para ocultar filas antes de animar
- [ ] Diferenciar animación inicial (elaborada) vs. filtros (rápida)
- [ ] Agregar `clearProps: "all"` en todas las animaciones
- [ ] Crear función de botones: `setupExampleActionButtonAnimations()`
- [ ] Implementar hover effects con `.off()` antes de `.on()` para evitar duplicados

### Fase 5: Testing
- [ ] Probar carga inicial: ¿Las filas se animan con fade-in + slide-up?
- [ ] Probar paginación: ¿Animación rápida al cambiar de página?
- [ ] Probar búsqueda/filtros: ¿Animación rápida al filtrar?
- [ ] Probar hover botones: ¿Escalan a 1.15?
- [ ] Probar hover iconos: ¿Rotan 360°?
- [ ] Verificar consola: ¿Sin errores de GSAP undefined?
- [ ] Verificar controles: ¿Paginación aparece después de la animación?

### Fase 6: Limpieza (Producción)
- [ ] Remover todos los `console.log()` de depuración
- [ ] Remover todos los `console.error()` de depuración
- [ ] Verificar que las animaciones siguen funcionando sin logs

---

## 🎯 Nomenclatura Estándar

Para mantener consistencia en el proyecto, usa estos nombres:

| Elemento | Patrón | Ejemplo |
|----------|--------|---------|
| ID Tabla | `#{moduleName}Table` | `#employeesTable` |
| Flag Global | `window.{moduleName}TableIsInitialLoad` | `window.employeesTableIsInitialLoad` |
| Función Animación | `window.animate{ModuleName}TableRows` | `window.animateEmployeesTableRows` |
| Función Botones | `setup{ModuleName}ActionButtonAnimations` | `setupEmployeeActionButtonAnimations` |

---

## 🔧 Troubleshooting

### Problema: "GSAP is not defined"
**Causa**: GSAP no se cargó correctamente desde el layout global.
**Solución**: Verificar línea 388-389 de `admin.php`, limpiar caché del navegador.

### Problema: "animateXXXTableRows is not a function"
**Causa**: Las funciones GSAP se están cargando ANTES del módulo DataTables.
**Solución**: Reordenar scripts: módulo primero, funciones GSAP después.

### Problema: Las animaciones no se ejecutan
**Causa**: `drawCallback` no se está llamando.
**Solución**: Verificar que DataTables se inicializa correctamente, revisar errores AJAX.

### Problema: Los elementos de paginación aparecen antes de animarse
**Causa**: Falta CSS para ocultarlos inicialmente.
**Solución**: Agregar `opacity: 0` a `.dataTables_info, .dataTables_paginate`.

### Problema: Múltiples animaciones en hover (duplicadas)
**Causa**: Event listeners no se están removiendo antes de agregar nuevos.
**Solución**: Usar `.off("mouseenter.gsap")` antes de `.on("mouseenter.gsap")`.

---

## 📖 Referencias

- **GSAP Docs**: https://greensock.com/docs/
- **DataTables Callbacks**: https://datatables.net/reference/option/drawCallback
- **Proyecto CLAUDE.md**: Ver sección "Motor Fórmulas & Query Builder" para arquitectura general

---

## 📝 Historial de Cambios

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0 | 24-Feb-2026 | Documento inicial basado en implementación empleados |

---

**Autor**: Sistema Innova Planilla
**Revisión**: Documentación técnica oficial
**Última Actualización**: 24 de Febrero, 2026
