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
  └── index.js                      # Código fuente JavaScript

public/assets/javascript/modules/example/  👈 CRÍTICO
  └── index.js                      # Archivo servido por el servidor web
```

### ⚠️ CRÍTICO: Ubicación de Archivos JavaScript

**IMPORTANTE**: Los archivos JavaScript deben estar en **AMBAS** ubicaciones:

1. **`/assets/javascript/modules/`** → Código fuente (para desarrollo)
2. **`/public/assets/javascript/modules/`** → Archivos servidos (para producción)

❌ **ERROR COMÚN**: Solo tener el archivo en `/assets/` sin copiarlo a `/public/assets/`

**Síntoma**: El módulo JavaScript no se carga, no hay logs en consola, 404 en Network tab del navegador.

**Solución**: Copiar siempre el archivo a `/public/assets/`:

```bash
# Ejemplo para reference-index.js
cp assets/javascript/modules/reference-index.js public/assets/javascript/modules/reference-index.js

# Ejemplo para schedules.js
cp assets/javascript/modules/schedules.js public/assets/javascript/modules/schedules.js
```

**¿Por qué?** El servidor web (Apache/Nginx) solo sirve archivos desde el directorio `/public/`. Los archivos en `/assets/` no son accesibles directamente por el navegador.

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

### Ejemplo 1: Vista Individual con GSAP Inline
Ver implementación completa en: `app/Views/admin/employees/index.php`

**Características**:
- Funciones GSAP definidas inline en la vista PHP
- Usa `$scripts` variable
- Patrón: `$jsConfig → módulo JS → funciones GSAP inline`

### Ejemplo 2: Vista Individual con Plugins DataTables
Ver implementación completa en: `app/Views/admin/employees/terminated.php`

**Características**:
- Carga plugins adicionales (DataTables Buttons)
- Funciones GSAP definidas inline
- Patrón: `$jsConfig → plugins → módulo JS → funciones GSAP inline`

### Ejemplo 3: Template Compartido con GSAP en Módulo JS
Ver implementación completa en: `app/Views/admin/templates/reference_index.php` + `assets/javascript/modules/reference-index.js`

**Características**:
- **Usado por múltiples vistas**: `/panel/cargos/`, `/panel/funciones/`, `/panel/tipos-planilla`, etc.
- Funciones GSAP integradas en el archivo `.js` modular
- Template PHP solo carga el módulo: `$scripts = $jsConfig . '<script src="...reference-index.js"></script>'`
- **Ventaja**: Un solo archivo JS para múltiples vistas
- **Importante**: Copiar archivo a `/public/assets/javascript/modules/` después de modificar

**Patrón en Template PHP**:
```php
use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();

// Cargar módulo JavaScript (contiene funciones GSAP integradas)
$scripts = $jsConfig . "\n" . '<script src="' . url('/assets/javascript/modules/reference-index.js') . '"></script>';
```

**Patrón en Módulo JS** (`reference-index.js`):
```javascript
(function() {
    'use strict';

    let referenceTable = null;
    let isInitialLoad = true;

    $(document).ready(function() {
        initializeDataTable();
        initializeGSAPAnimations(); // ← Funciones GSAP aquí
    });

    function initializeDataTable() {
        referenceTable = $("#referenceTable").DataTable({
            // ... config ...
            drawCallback: function(settings) {
                setTimeout(function() {
                    animateTableRows(); // ← Llama función GSAP
                }, 50);
            }
        });
    }

    function initializeGSAPAnimations() {
        // Animar botón "Agregar"
        const addButton = $('.card-tools .btn-primary');
        addButton.on('mouseenter', function() {
            gsap.to(this, { scale: 1.05, ... });
        });
    }

    function animateTableRows() {
        // Animaciones de filas de tabla
        const rows = $('#referenceTable tbody tr');
        gsap.to(rows, { opacity: 1, y: 0, ... });
    }

    function setupActionAnimations() {
        // Animaciones de botones y badges
    }
})();
```

**Ventajas del patrón modular**:
- ✅ Código más limpio en templates PHP
- ✅ Reutilizable para múltiples vistas
- ✅ Más fácil de mantener (un solo archivo)
- ✅ Mejor separación de responsabilidades

**Desventajas**:
- ❌ Requiere copiar archivo a `/public/` después de cambios
- ❌ Menos flexible para customizaciones por vista

### Ejemplo 4: Template con Módulo JS Específico
Ver implementación completa en: `app/Views/admin/templates/schedule_index.php` + `assets/javascript/modules/schedules.js`

**Características**:
- Similar al Ejemplo 3 pero para el módulo de horarios
- Tabla con columnas personalizadas (horarios de entrada/salida, almuerzo)
- Mismo patrón: Template carga módulo JS que tiene GSAP integrado

---

## ✅ Checklist de Implementación

Usa este checklist cuando implementes GSAP en un nuevo módulo:

### Fase 1: Preparación
- [ ] Confirmar que GSAP está cargado en `admin.php` línea 388-389
- [ ] Identificar el ID único de la tabla DataTables (ej: `#exampleTable`)
- [ ] Determinar si necesitas plugins adicionales DataTables
- [ ] **Decidir patrón**: ¿GSAP inline en vista PHP o integrado en módulo JS?
  - Inline: Para vistas únicas con customizaciones específicas
  - Modular: Para templates compartidos por múltiples vistas

### Fase 2: Vista PHP (o Template PHP)
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
- [ ] **SI usas patrón modular**: Integrar funciones GSAP en el archivo `.js`
- [ ] **CRÍTICO**: Copiar archivo `.js` a `/public/assets/javascript/modules/`
  ```bash
  cp assets/javascript/modules/tu-modulo.js public/assets/javascript/modules/tu-modulo.js
  ```

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

### Problema: El módulo JavaScript no se carga (404 en Network tab)
**Causa**: El archivo `.js` solo existe en `/assets/` pero NO en `/public/assets/`.
**Solución**: Copiar el archivo a `/public/`:
```bash
cp assets/javascript/modules/tu-modulo.js public/assets/javascript/modules/tu-modulo.js
```
**Verificar**: Abrir DevTools → Network tab → buscar el archivo `.js` → debe retornar 200 OK (no 404).

### Problema: No hay logs en consola, las animaciones no funcionan
**Causa 1**: El archivo JavaScript no se está cargando (ver problema anterior).
**Causa 2**: Error de sintaxis en el archivo `.js` que impide su ejecución.
**Solución**:
1. Verificar en Network tab que el archivo carga correctamente (200 OK)
2. Abrir el archivo en el navegador directamente: `http://tu-dominio/assets/javascript/modules/tu-modulo.js`
3. Revisar Console tab para errores de sintaxis
4. Agregar `console.log('[Module] Loaded')` al inicio del archivo para confirmar carga

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

---

## 🎨 Patrones Avanzados de Animación

### Patrón 5: Vistas con Info-Boxes y Elementos Complejos

Ver implementación completa en: `app/Views/admin/overtime_approvals/index.php`

**Características**:
- Animación de Info-Boxes (cards estadísticas)
- Animación de iconos con hover (rotation + scale)
- Select2 AJAX dinámico
- Botones con colores diferentes (primary, secondary, success, danger)
- Modales con animaciones
- Integración avanzada con DataTables

#### 1. Animación de Info-Boxes

```javascript
function animateInfoBoxes() {
    const infoBoxes = $('.info-box');

    if (infoBoxes.length > 0) {
        // Configurar estado inicial
        gsap.set(infoBoxes, { opacity: 0, y: 30 });

        // Animar con stagger
        gsap.to(infoBoxes, {
            opacity: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power2.out',
            clearProps: 'all'
        });
    }
}
```

**Cuándo usar**: Cuando tienes cards de estadísticas en la parte superior de la vista.

#### 2. Animación de Iconos en Info-Boxes

```javascript
function setupInfoBoxIconAnimations() {
    const icons = $('.info-box-icon i');

    icons.on('mouseenter', function() {
        gsap.to(this, {
            rotation: 360,
            scale: 1.2,
            duration: 0.5,
            ease: 'power2.inOut'
        });
    });

    icons.on('mouseleave', function() {
        gsap.to(this, {
            rotation: 0,
            scale: 1,
            duration: 0.3,
            ease: 'power2.out'
        });
    });
}
```

**Cuándo usar**: Para agregar interactividad visual a los iconos de las cards.

#### 3. Corrección de Badges con fromTo

**⚠️ PROBLEMA COMÚN**: Los badges se ven muy pequeños después de animar

```javascript
❌ INCORRECTO - Puede dejar badges pequeños:
badges.each(function(index) {
    gsap.from(this, {
        scale: 0,
        duration: 0.4,
        ease: "back.out(2)"
        // ❌ No especifica estado final, puede quedar con scale < 1
    });
});
```

```javascript
✅ CORRECTO - Garantiza tamaño normal:
badges.each(function(index) {
    gsap.fromTo(this,
        {
            scale: 0,      // Estado inicial
            opacity: 0
        },
        {
            scale: 1,      // Estado final EXPLÍCITO
            opacity: 1,
            duration: 0.4,
            delay: index * 0.02,
            ease: 'back.out(2)',
            clearProps: 'all'  // Limpia propiedades inline
        }
    );
});
```

**Por qué usar `fromTo`**:
- ✅ Control total sobre valores inicial y final
- ✅ Garantiza que el elemento termine en tamaño normal (`scale: 1`)
- ✅ Evita problemas de elementos que quedan pequeños
- ✅ Más predecible que `from()` o `to()` solo

#### 4. Botones con Colores Dinámicos

```javascript
function setupFilterButtonAnimations() {
    const filterButtons = $('#filterForm .btn, #clearFilters');

    filterButtons.on('mouseenter', function() {
        const isPrimary = $(this).hasClass('btn-primary');
        const shadowColor = isPrimary ? 'rgba(0,123,255,0.4)' : 'rgba(108,117,125,0.4)';

        gsap.to(this, {
            scale: 1.05,
            boxShadow: `0 5px 15px ${shadowColor}`,
            duration: 0.3,
            ease: 'power2.out'
        });
    });

    filterButtons.on('mouseleave', function() {
        gsap.to(this, {
            scale: 1,
            boxShadow: 'none',
            duration: 0.3,
            ease: 'power2.out'
        });
    });
}
```

**Colores de Sombra por Tipo de Botón**:

| Clase Bootstrap | Color RGBA | Uso |
|----------------|-----------|-----|
| `.btn-primary` | `rgba(0,123,255,0.4)` | Botones principales (azul) |
| `.btn-secondary` | `rgba(108,117,125,0.4)` | Botones secundarios (gris) |
| `.btn-success` | `rgba(40,167,69,0.4)` | Botones de éxito (verde) |
| `.btn-danger` | `rgba(220,53,69,0.4)` | Botones de peligro (rojo) |
| `.btn-warning` | `rgba(255,193,7,0.4)` | Botones de advertencia (amarillo) |
| `.btn-info` | `rgba(23,162,184,0.4)` | Botones informativos (cyan) |

#### 5. Animación de Botones en Modales

```javascript
function setupModalButtonAnimations() {
    const modalButtons = '.modal .btn';

    $(document).on('mouseenter', modalButtons, function() {
        let shadowColor = 'rgba(108,117,125,0.4)'; // Default

        if ($(this).hasClass('btn-success')) {
            shadowColor = 'rgba(40,167,69,0.4)';
        } else if ($(this).hasClass('btn-danger')) {
            shadowColor = 'rgba(220,53,69,0.4)';
        } else if ($(this).hasClass('btn-secondary')) {
            shadowColor = 'rgba(108,117,125,0.4)';
        }

        gsap.to(this, {
            scale: 1.05,
            boxShadow: `0 5px 15px ${shadowColor}`,
            duration: 0.3,
            ease: 'power2.out'
        });
    });

    $(document).on('mouseleave', modalButtons, function() {
        gsap.to(this, {
            scale: 1,
            boxShadow: 'none',
            duration: 0.3,
            ease: 'power2.out'
        });
    });
}
```

**⚠️ Importante**: Usar `$(document).on()` en lugar de `.on()` directo para elementos que se crean dinámicamente (modales).

#### 6. Animación de Botones de Exportación (DataTables)

```javascript
function setupExportButtonAnimations() {
    // Esperar a que DataTables cree los botones
    setTimeout(function() {
        const exportButtons = '.dt-buttons .btn';

        $(document).on('mouseenter', exportButtons, function() {
            let shadowColor = 'rgba(108,117,125,0.4)';

            if ($(this).hasClass('btn-success')) {
                shadowColor = 'rgba(40,167,69,0.4)';
            } else if ($(this).hasClass('btn-danger')) {
                shadowColor = 'rgba(220,53,69,0.4)';
            }

            gsap.to(this, {
                scale: 1.05,
                boxShadow: `0 5px 15px ${shadowColor}`,
                duration: 0.3,
                ease: 'power2.out'
            });
        });

        $(document).on('mouseleave', exportButtons, function() {
            gsap.to(this, {
                scale: 1,
                boxShadow: 'none',
                duration: 0.3,
                ease: 'power2.out'
            });
        });
    }, 500);  // Delay necesario para esperar creación de botones
}
```

**⚠️ Critical**: DataTables Buttons se crean DESPUÉS de inicializar la tabla, por eso necesitamos el `setTimeout`.

#### 7. Integración Avanzada con DataTables drawCallback

```javascript
// Integrar animación de tabla con DataTables
const originalDrawCallback = table.settings()[0].aoDrawCallback;
table.settings()[0].aoDrawCallback.push({
    fn: function() {
        setTimeout(animateTableRows, 50);
    },
    sName: 'gsapAnimation'
});

// Ejecutar animación de tabla en la carga inicial
setTimeout(animateTableRows, 300);
```

**Ventajas de este método**:
- ✅ No interfiere con otros callbacks existentes
- ✅ Se ejecuta automáticamente en cada redraw
- ✅ Funciona con paginación, filtros y ordenamiento

#### 8. Patrón Completo para Vista con Elementos Complejos

```javascript
if (typeof gsap !== 'undefined') {
    // Flag para controlar animación inicial de la tabla
    let isInitialTableLoad = true;

    // 1. Ejecutar animaciones iniciales al cargar la página
    animateInfoBoxes();
    setupInfoBoxIconAnimations();
    setupFilterButtonAnimations();
    setupModalButtonAnimations();
    setupExportButtonAnimations();

    // 2. Integrar animación de tabla con DataTables
    const originalDrawCallback = table.settings()[0].aoDrawCallback;
    table.settings()[0].aoDrawCallback.push({
        fn: function() {
            setTimeout(animateTableRows, 50);
        },
        sName: 'gsapAnimation'
    });

    // 3. Ejecutar animación de tabla en la carga inicial
    setTimeout(animateTableRows, 300);
}
```

**Orden de Ejecución**:
1. Info-boxes (inmediato)
2. Setup de iconos (inmediato)
3. Setup de botones (inmediato)
4. Setup de modales (inmediato)
5. Setup de exportación (después de 500ms)
6. Animación de tabla (después de 300ms o en cada redraw)

---

## 🔧 Select2 AJAX Dinámico (Bonus Pattern)

### Problema: Endpoint que no retorna array de empleados

```javascript
❌ INCORRECTO - Puede causar error "forEach is not a function":
$.get(baseUrl + '/panel/employees/get-active', function(employees) {
    employees.forEach(function(emp) {  // ← Error si employees no es array
        // ...
    });
});
```

### Solución: Select2 con AJAX Dinámico

```javascript
✅ CORRECTO - Carga bajo demanda:
$('.select2').select2({
    theme: 'bootstrap4',
    width: '100%',
    ajax: {
        url: baseUrl + '/panel/payrolls/get-employees',
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return {
                term: params.term  // Búsqueda
            };
        },
        processResults: function (data) {
            // Validar que data sea array
            const employees = Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
            return {
                results: employees.map(function(emp) {
                    return {
                        id: emp.id,
                        text: emp.employee_id + ' - ' + emp.firstname + ' ' + emp.lastname
                    };
                })
            };
        },
        cache: true
    },
    placeholder: 'Todos',
    allowClear: true
});
```

**Ventajas**:
- ✅ No carga todos los empleados al inicio
- ✅ Búsqueda dinámica mientras el usuario escribe
- ✅ Mejor rendimiento con muchos empleados
- ✅ Valida que la respuesta sea un array
- ✅ No depende de endpoints que pueden no existir

---

## 🎭 Efectos Especiales de Animación

### Shake Effect (Sacudida)

El **shake effect** es útil para llamar la atención del usuario hacia elementos importantes como mensajes de error, alertas o callouts.

#### ❌ INCORRECTO - Usando Array Simple

```javascript
// ❌ NO FUNCIONA CORRECTAMENTE
gsap.to(element, {
    x: [-12, 12, -10, 10, -8, 8, -5, 5, 0],
    duration: 0.5,
    ease: "power2.inOut"
});
```

**Problema**:
- El elemento puede quedar desalineado (fuera de posición)
- No garantiza que termine en `x: 0`
- El timing de cada oscilación no es predecible

#### ✅ CORRECTO - Usando Keyframes Explícitos

```javascript
// ✅ FUNCIONA PERFECTAMENTE
gsap.to(element, {
    keyframes: [
        { x: -12, duration: 0.05 },
        { x: 12, duration: 0.05 },
        { x: -10, duration: 0.05 },
        { x: 10, duration: 0.05 },
        { x: -8, duration: 0.05 },
        { x: 8, duration: 0.05 },
        { x: -5, duration: 0.05 },
        { x: 5, duration: 0.05 },
        { x: 0, duration: 0.1 }  // ← Asegura volver a posición original
    ],
    ease: "power2.inOut"
});
```

**Ventajas**:
- ✅ Control preciso sobre cada oscilación
- ✅ Garantiza que termine en `x: 0` (posición original)
- ✅ Timing individual para cada movimiento
- ✅ Efecto más suave y predecible

#### Ejemplo Completo: Login con Callout de Error

Ver implementación en: `app/Views/admin/login.php` (líneas 206-229)

```javascript
// Verificar si existe callout de error/mensaje
const errorCallout = document.querySelector(".callout-danger, .callout-warning, .callout-info, .callout-success");

if (errorCallout) {
    const tl = gsap.timeline();

    // 1. Fade-in del callout
    tl.to(errorCallout, {
        opacity: 1,
        duration: 0.4,
        ease: "power2.out"
    }, 0)

    // 2. Shake effect para llamar atención (después de aparecer)
    .to(errorCallout, {
        keyframes: [
            { x: -12, duration: 0.05 },
            { x: 12, duration: 0.05 },
            { x: -10, duration: 0.05 },
            { x: 10, duration: 0.05 },
            { x: -8, duration: 0.05 },
            { x: 8, duration: 0.05 },
            { x: -5, duration: 0.05 },
            { x: 5, duration: 0.05 },
            { x: 0, duration: 0.1 }
        ],
        ease: "power2.inOut"
    }, 0.4); // Inicia después del fade-in
}
```

**Timeline del Efecto**:
```
t=0.0    Fade-in inicia (opacity: 0 → 1)
t=0.4    Fade-in completa / Shake inicia
t=0.45   Primera oscilación (-12px → 12px)
t=0.50   Segunda oscilación (-10px → 10px)
t=0.55   Tercera oscilación (-8px → 8px)
t=0.60   Cuarta oscilación (-5px → 5px)
t=0.70   Regreso a posición original (x: 0)
```

#### Variaciones de Intensidad

**Shake Suave** (para notificaciones informativas):
```javascript
keyframes: [
    { x: -5, duration: 0.05 },
    { x: 5, duration: 0.05 },
    { x: -3, duration: 0.05 },
    { x: 3, duration: 0.05 },
    { x: 0, duration: 0.1 }
]
```

**Shake Moderado** (para advertencias):
```javascript
keyframes: [
    { x: -8, duration: 0.05 },
    { x: 8, duration: 0.05 },
    { x: -6, duration: 0.05 },
    { x: 6, duration: 0.05 },
    { x: -4, duration: 0.05 },
    { x: 4, duration: 0.05 },
    { x: 0, duration: 0.1 }
]
```

**Shake Exagerado** (para errores críticos):
```javascript
keyframes: [
    { x: -12, duration: 0.05 },
    { x: 12, duration: 0.05 },
    { x: -10, duration: 0.05 },
    { x: 10, duration: 0.05 },
    { x: -8, duration: 0.05 },
    { x: 8, duration: 0.05 },
    { x: -5, duration: 0.05 },
    { x: 5, duration: 0.05 },
    { x: 0, duration: 0.1 }
]
```

#### Cuándo Usar Shake Effect

| Contexto | Intensidad | Ejemplo |
|----------|-----------|---------|
| Error de validación de formulario | Exagerado | Credenciales incorrectas en login |
| Advertencia importante | Moderado | Licencia próxima a expirar |
| Notificación informativa | Suave | Mensaje de confirmación |
| Campo obligatorio vacío | Suave | Highlight de campo requerido |

#### CSS Inicial Requerido

No necesitas aplicar `transform` inicial en CSS. El efecto funciona directamente:

```css
/* ✅ SUFICIENTE - Solo ocultar para fade-in */
.callout {
    opacity: 0;
}

/* ❌ NO NECESARIO - No agregar transform inicial */
.callout {
    opacity: 0;
    transform: translateX(-20px);  /* ← NO necesario para shake */
}
```

---

## 📝 Historial de Cambios

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0 | 24-Feb-2026 | Documento inicial basado en implementación empleados |
| 1.1 | 25-Feb-2026 | Agregada sección crítica sobre ubicación de archivos JS (`/assets/` vs `/public/assets/`), nuevos patrones modulares, ejemplos de templates compartidos (reference-index.js, schedules.js), troubleshooting mejorado |
| 1.2 | 25-Feb-2026 | **Patrones avanzados agregados**: Info-Boxes, corrección de badges con `fromTo`, botones con colores dinámicos, animaciones de modales, botones de exportación DataTables, integración avanzada drawCallback, Select2 AJAX. Basado en implementación `overtime-approvals` |
| 1.3 | 26-Feb-2026 | **Shake Effect agregado**: Documentación completa de shake effect usando keyframes, variaciones de intensidad, ejemplos de uso, comparación método correcto vs incorrecto. Basado en implementación login con callouts de error |

---

**Autor**: Sistema Innova Planilla
**Revisión**: Documentación técnica oficial
**Última Actualización**: 26 de Febrero, 2026
