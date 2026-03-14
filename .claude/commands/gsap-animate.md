---
name: gsap-animate
description: Generate GSAP animations following GSAP_ANIMATION_PATTERN.md
argument-hint: module_name animation_type [inline|modular] (e.g., "employees basic inline" or "reports advanced modular")
allowed-tools: [Read, Write, Edit]
---

Generate GSAP animations for: **$ARGUMENTS**

## 📋 Instructions

Parse the arguments as follows:
- **First argument**: Module name (e.g., "employees", "reports", "payrolls")
- **Second argument**: Animation type: `basic` (DataTable only) or `advanced` (includes info-boxes, badges, shake)
- **Third argument** (optional): Implementation mode: `inline` (default) or `modular`

## 🎯 Before Starting

### Critical Understanding
1. **GSAP is loaded GLOBALLY** in `app/Views/layouts/admin.php` line 388-389
   - ❌ NEVER load GSAP again in individual views
   - ✅ GSAP is already available as `window.gsap`

2. **Script Load Order** (CRITICAL):
   ```
   1. JavaScriptHelper config
   2. Additional plugins (if needed)
   3. Module JavaScript file
   4. GSAP animation functions  ← AFTER module
   ```

3. **File Locations** (CRITICAL for modular mode):
   - `/assets/javascript/modules/{module}.js` OR `/assets/javascript/modules/{module}/index.js` → Source code (follow existing module convention)
   - `/public/assets/javascript/modules/{module}.js` OR `/public/assets/javascript/modules/{module}/index.js` → Server-accessible (MUST COPY HERE)
   - ⚠️ If file only exists in `/assets/`, browser will get 404!

## 📚 Read Pattern First

Before generating code, read `documentation/GSAP_ANIMATION_PATTERN.md` to understand:
- Load architecture
- Common errors and solutions
- Complete examples
- Troubleshooting guide

## 🎨 Animation Types

### Basic Animations
Includes:
- DataTable row fade-in + slide-up
- Action button hover effects (scale 1.15)
- Icon rotation (360°)
- Pagination fade-in

### Advanced Animations
Includes all basic animations PLUS:
- Info-boxes (statistics cards) animation
- Badge animations with `fromTo` (corrected pattern)
- Modal button animations
- Export button animations (DataTables Buttons)
- Shake effect for callouts/alerts
- Filter button animations with dynamic shadow colors

## 🔧 Generation Steps

### Mode 1: Inline Implementation (for unique views)

Generate code that goes directly in the PHP view file using `$scripts` variable.

#### Step 1: Create CSS Styles
```php
$styles = '
<link rel="stylesheet" href="' . url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<style>
/* GSAP - Hide pagination elements before animating */
.dataTables_info,
.dataTables_paginate {
    opacity: 0;
}
</style>';
```

#### Step 2: Create Script Variable Structure
```php
<?php
use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();

// ==================================================
// 1. LOAD ADDITIONAL PLUGINS (if needed)
// ==================================================
$scripts = $jsConfig . '
<script src="' . url('/plugins/datatables-buttons/js/dataTables.buttons.min.js') . '"></script>
<script src="' . url('/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') . '"></script>
';

// ==================================================
// 2. LOAD MODULE JAVASCRIPT
// ==================================================
$scripts .= "\n" . '<script src="' . url('/assets/javascript/modules/{module_name}/index.js') . '"></script>';

// ==================================================
// 3. DEFINE GSAP FUNCTIONS (AFTER module)
// ==================================================
$scripts .= '
<script>
// [GSAP FUNCTIONS GO HERE]
</script>
';
```

#### Step 3: Generate Basic GSAP Functions
```javascript
// Flag global para controlar animación inicial
window.{moduleName}TableIsInitialLoad = true;

// Función global para animar filas del DataTable
window.animate{ModuleName}TableRows = function() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const rows = $("#{moduleName}Table tbody tr");

    // Si no hay filas, no hacer nada
    if (rows.length === 0) {
        return;
    }

    // Primero, asegurar que las filas estén ocultas
    gsap.set(rows, { opacity: 0, y: 0 });

    if (window.{moduleName}TableIsInitialLoad) {
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

        window.{moduleName}TableIsInitialLoad = false;
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
    setup{ModuleName}ActionButtonAnimations();
};

// Función para animar botones de acción
function setup{ModuleName}ActionButtonAnimations() {
    // Verificar que GSAP esté disponible
    if (typeof gsap === "undefined") {
        return;
    }

    const badges = $("#{moduleName}Table .badge");
    const buttons = $("#{moduleName}Table .btn-sm");
    const icons = $("#{moduleName}Table .btn-sm i");

    // Animar badges de estado - USAR fromTo para evitar que queden pequeños
    badges.each(function(index) {
        gsap.fromTo(this,
            {
                scale: 0,
                opacity: 0
            },
            {
                scale: 1,
                opacity: 1,
                duration: 0.4,
                delay: index * 0.02,
                ease: "back.out(2)",
                clearProps: "all"
            }
        );
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
```

#### Step 4: Generate Advanced GSAP Functions (if requested)

Add these additional functions after the basic ones:

**Info-Boxes Animation**:
```javascript
function animate{ModuleName}InfoBoxes() {
    if (typeof gsap === "undefined") return;

    const infoBoxes = $('.info-box');
    if (infoBoxes.length > 0) {
        gsap.set(infoBoxes, { opacity: 0, y: 30 });
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

**Info-Box Icon Animations**:
```javascript
function setup{ModuleName}InfoBoxIconAnimations() {
    if (typeof gsap === "undefined") return;

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

**Filter Button Animations** (with dynamic colors):
```javascript
function setup{ModuleName}FilterButtonAnimations() {
    if (typeof gsap === "undefined") return;

    const filterButtons = $('#filterForm .btn, #clearFilters');

    filterButtons.on('mouseenter', function() {
        let shadowColor = 'rgba(108,117,125,0.4)'; // Default gray

        if ($(this).hasClass('btn-primary')) {
            shadowColor = 'rgba(0,123,255,0.4)';
        } else if ($(this).hasClass('btn-success')) {
            shadowColor = 'rgba(40,167,69,0.4)';
        } else if ($(this).hasClass('btn-danger')) {
            shadowColor = 'rgba(220,53,69,0.4)';
        } else if ($(this).hasClass('btn-warning')) {
            shadowColor = 'rgba(255,193,7,0.4)';
        } else if ($(this).hasClass('btn-info')) {
            shadowColor = 'rgba(23,162,184,0.4)';
        }

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

**Modal Button Animations** (event delegation):
```javascript
function setup{ModuleName}ModalButtonAnimations() {
    if (typeof gsap === "undefined") return;

    const modalButtons = '.modal .btn';

    $(document).on('mouseenter', modalButtons, function() {
        let shadowColor = 'rgba(108,117,125,0.4)';

        if ($(this).hasClass('btn-success')) {
            shadowColor = 'rgba(40,167,69,0.4)';
        } else if ($(this).hasClass('btn-danger')) {
            shadowColor = 'rgba(220,53,69,0.4)';
        } else if ($(this).hasClass('btn-primary')) {
            shadowColor = 'rgba(0,123,255,0.4)';
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

**Shake Effect** (for callouts/alerts):
```javascript
function apply{ModuleName}ShakeEffect(element, intensity = 'moderate') {
    if (typeof gsap === "undefined") return;

    const keyframes = {
        'soft': [
            { x: -5, duration: 0.05 },
            { x: 5, duration: 0.05 },
            { x: -3, duration: 0.05 },
            { x: 3, duration: 0.05 },
            { x: 0, duration: 0.1 }
        ],
        'moderate': [
            { x: -8, duration: 0.05 },
            { x: 8, duration: 0.05 },
            { x: -6, duration: 0.05 },
            { x: 6, duration: 0.05 },
            { x: -4, duration: 0.05 },
            { x: 4, duration: 0.05 },
            { x: 0, duration: 0.1 }
        ],
        'strong': [
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
    };

    gsap.to(element, {
        keyframes: keyframes[intensity] || keyframes['moderate'],
        ease: "power2.inOut"
    });
}
```

**Initial Call for Advanced Animations**:
```javascript
// Al cargar la página, ejecutar animaciones iniciales
if (typeof gsap !== 'undefined') {
    animate{ModuleName}InfoBoxes();
    setup{ModuleName}InfoBoxIconAnimations();
    setup{ModuleName}FilterButtonAnimations();
    setup{ModuleName}ModalButtonAnimations();
}
```

### Mode 2: Modular Implementation (for shared templates)

Generate code in a separate JavaScript file that can be reused by multiple views.

#### File Structure:
```
assets/javascript/modules/{module_name}.js
public/assets/javascript/modules/{module_name}.js  ← MUST COPY HERE

# OR (if project already uses folder-based modules):
assets/javascript/modules/{module_name}/index.js
public/assets/javascript/modules/{module_name}/index.js  ← MUST COPY HERE
```

#### Module Pattern (IIFE):
```javascript
/**
 * Module: {ModuleName}
 * GSAP Animations Integrated
 */
(function() {
    'use strict';

    let {moduleName}Table = null;
    let isInitialLoad = true;

    $(document).ready(function() {
        initializeDataTable();
        initializeGSAPAnimations();
    });

    function initializeDataTable() {
        const urls = window.APP_CONFIG?.urls || {};

        {moduleName}Table = $("#{moduleName}Table").DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": urls.panel_url + "/{module_route}/datatables-ajax",
                "type": "GET"
            },
            "columns": [
                { "data": 0 },
                { "data": 1 },
                { "data": 2 },
                { "data": 3, "orderable": false }
            ],
            "pageLength": 25,
            "language": {
                "url": urls.datatables_spanish || "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            },
            "drawCallback": function(settings) {
                // GSAP: Animate rows after each draw
                setTimeout(function() {
                    animateTableRows();
                }, 50);
            }
        });
    }

    function initializeGSAPAnimations() {
        if (typeof gsap === "undefined") return;

        // Setup animations for static elements
        [ADVANCED ANIMATIONS SETUP IF REQUESTED]
    }

    function animateTableRows() {
        if (typeof gsap === "undefined") return;

        const rows = $('#{moduleName}Table tbody tr');
        if (rows.length === 0) return;

        gsap.set(rows, { opacity: 0, y: 0 });

        if (isInitialLoad) {
            gsap.set(rows, { y: 20 });
            gsap.to(rows, {
                opacity: 1,
                y: 0,
                duration: 0.4,
                stagger: 0.05,
                ease: "power2.out",
                clearProps: "all"
            });

            gsap.to(".dataTables_info, .dataTables_paginate", {
                opacity: 1,
                duration: 0.5,
                delay: 0.3
            });

            isInitialLoad = false;
        } else {
            gsap.to(rows, {
                opacity: 1,
                duration: 0.3,
                stagger: 0.02,
                ease: "power1.out",
                clearProps: "all"
            });
        }

        setupActionAnimations();
    }

    function setupActionAnimations() {
        if (typeof gsap === "undefined") return;

        const badges = $('#{moduleName}Table .badge');
        const buttons = $('#{moduleName}Table .btn-sm');
        const icons = $('#{moduleName}Table .btn-sm i');

        // Badges - usar fromTo
        badges.each(function(index) {
            gsap.fromTo(this,
                { scale: 0, opacity: 0 },
                {
                    scale: 1,
                    opacity: 1,
                    duration: 0.4,
                    delay: index * 0.02,
                    ease: "back.out(2)",
                    clearProps: "all"
                }
            );
        });

        // Buttons hover
        buttons.off("mouseenter.gsap mouseleave.gsap").on({
            "mouseenter.gsap": function() {
                if (!$(this).prop("disabled")) {
                    gsap.to(this, { scale: 1.15, duration: 0.2, ease: "power2.out" });
                }
            },
            "mouseleave.gsap": function() {
                gsap.to(this, { scale: 1, duration: 0.2, ease: "power2.out" });
            }
        });

        // Icons rotation
        icons.off("mouseenter.gsap").on("mouseenter.gsap", function() {
            if (!$(this).closest(".btn").prop("disabled")) {
                gsap.to(this, { rotation: 360, duration: 0.5, ease: "power2.inOut" });
            }
        });
    }

    [ADD ADVANCED ANIMATION FUNCTIONS IF REQUESTED]

})();
```

#### PHP View (simplified):
```php
<?php
use App\Helpers\JavaScriptHelper;
$jsConfig = JavaScriptHelper::renderConfigScript();

// Load module with integrated GSAP
$scripts = $jsConfig . "\n" . '<script src="' . url('/assets/javascript/modules/{module_name}.js') . '"></script>';

$styles = '
<link rel="stylesheet" href="' . url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') . '">
<style>
.dataTables_info,
.dataTables_paginate {
    opacity: 0;
}
</style>';
?>
```

## 📝 After Generation

### For Inline Mode:
Show complete code ready to paste into the PHP view file with:
- `$styles` variable
- `$scripts` variable with correct order
- All GSAP functions properly namespaced

### For Modular Mode:
Provide:
1. **JavaScript module file** content (for `assets/javascript/modules/{module}.js`)
2. **Simplified PHP view** code
3. **CRITICAL instruction**:
   ```bash
   # IMPORTANT: Copy to public directory
   # Linux/macOS:
   cp assets/javascript/modules/{module}.js public/assets/javascript/modules/{module}.js

   # Windows PowerShell:
   Copy-Item -Force assets/javascript/modules/{module}.js public/assets/javascript/modules/{module}.js
   ```

## ✅ Checklist to Display

After generating code, show this checklist:

### Inline Mode:
- [x] CSS styles generated (hide pagination)
- [x] Script load order correct (config → module → GSAP functions)
- [x] Basic animations generated (table rows, buttons, icons)
- [ ] or [x] Advanced animations generated (if requested)
- [ ] **TODO: Paste code into view file** `app/Views/admin/{module}/index.php`
- [ ] **TODO: Update module JS** to call GSAP functions in `drawCallback`
- [ ] **TODO: Test initial load** (fade-in + slide-up)
- [ ] **TODO: Test pagination** (quick fade)
- [ ] **TODO: Test hover effects** (scale + rotation)

### Modular Mode:
- [x] JavaScript module generated with GSAP integrated
- [x] PHP view simplified (only loads module)
- [x] CSS styles generated
- [x] Basic animations integrated
- [ ] or [x] Advanced animations integrated (if requested)
- [ ] **TODO: Save JS file** to `assets/javascript/modules/{module}.js`
- [ ] **CRITICAL TODO: Copy to public** → `cp assets/... public/assets/...`
- [ ] **TODO: Update PHP view** with simplified code
- [ ] **TODO: Test in browser** (check Network tab for 200 OK, not 404)
- [ ] **TODO: Test all animations**

## ⚠️ Critical Reminders

1. **GSAP is Global**: Never load GSAP again, it's already in admin.php
2. **Load Order**: Module FIRST, GSAP functions AFTER
3. **fromTo for Badges**: Always use `fromTo` to avoid small badges
4. **clearProps**: Always add `clearProps: "all"` in animations
5. **Event Cleanup**: Use `.off()` before `.on()` to avoid duplicates
6. **Keyframes for Shake**: Use explicit keyframes, not simple arrays
7. **Modular Files**: MUST copy to `/public/assets/` or browser gets 404

## 🎨 Animation Timing Reference

| Animation | Duration | Stagger | Ease |
|-----------|----------|---------|------|
| Initial row fade | 0.4s | 0.05s | power2.out |
| Pagination appear | 0.5s | - | (default) |
| Filter fade | 0.3s | 0.02s | power1.out |
| Button scale | 0.2s | - | power2.out |
| Icon rotation | 0.5s | - | power2.inOut |
| Badge fromTo | 0.4s | 0.02s | back.out(2) |
| Info-box slide | 0.6s | 0.1s | power2.out |
| Shake effect | 0.05s/step | - | power2.inOut |

## 🎯 Shadow Colors by Button Type

| Button Class | RGBA Color |
|--------------|-----------|
| `.btn-primary` | `rgba(0,123,255,0.4)` |
| `.btn-secondary` | `rgba(108,117,125,0.4)` |
| `.btn-success` | `rgba(40,167,69,0.4)` |
| `.btn-danger` | `rgba(220,53,69,0.4)` |
| `.btn-warning` | `rgba(255,193,7,0.4)` |
| `.btn-info` | `rgba(23,162,184,0.4)` |

---

**Remember**: Generate production-ready code that follows ALL patterns from GSAP_ANIMATION_PATTERN.md. Include proper error checking (`typeof gsap === "undefined"`), use correct naming conventions, and provide clear instructions for implementation.
