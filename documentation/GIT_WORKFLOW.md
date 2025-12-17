# 🔄 FLUJO DE TRABAJO GIT - BRANCHING STRATEGY SIMPLIFICADO
**METODOLOGÍA PARA CONTROL DE VERSIONES - ACTUALIZADO 17/12/2025**

## **Estructura de Branches (Simplificada)**
```
develop (rama de desarrollo/testing)
  ↑
  │ commits directos (cambios pequeños/medianos)
  │
  └── feature/* (solo para features grandes que tomen varios días)

        ↓ cuando esté probado y listo

main (rama de producción - código estable en servidor productivo)
```

## **Filosofía del Flujo**

- **develop**: Tu rama principal de trabajo diario. Aquí haces TODO el desarrollo y testing.
- **main**: Espejo exacto del código en producción. Solo recibe merges desde `develop`.
- **feature/***: Opcional, solo para features grandes (>2 días de desarrollo).

---

## **Aliases Git Configurados**

Para facilitar el trabajo diario, están configurados los siguientes aliases:

```bash
git st                    # git status -sb (estado compacto)
git lg                    # Log gráfico bonito (últimos 20 commits)
git sync                  # Actualizar develop (checkout + pull)
git deploy                # Deploy a producción (merge develop → main)
git feat <nombre>         # Crear nuevo feature branch
git done                  # Terminar feature actual (merge a develop)
```

---

## **Flujo de Trabajo**

### **Caso 1: Cambio Pequeño/Mediano (80% de los casos)**

```bash
# 1. Asegurarte estar en develop actualizado
git sync                  # Atajo: git checkout develop && git pull

# 2. Hacer cambios
# ... editar archivos ...

# 3. Commit y push
git add .
git commit -m "feat: agregar campo tarifa_hora a formulario edit"
git push origin develop

# 4. Probar en desarrollo/staging
# ... testing manual o automático ...

# 5. Cuando esté listo para producción
git deploy                # Atajo: merge develop → main y push

# Listo! Solo 3 comandos principales
```

---

### **Caso 2: Feature Grande (20% de los casos)**

Para features que tomen varios días (>2 días):

```bash
# 1. Crear feature branch
git feat sistema-vacaciones
# Esto hace: checkout develop, pull, y crea feature/sistema-vacaciones

# 2. Desarrollo durante varios días
# ... commits múltiples ...
git add .
git commit -m "feat: agregar modelo Vacation"
git push

git add .
git commit -m "feat: agregar controlador y vistas"
git push

# 3. Cuando esté completa
git done
# Esto hace: checkout develop, pull, merge feature, push, delete branch

# 4. Testing en develop
# ... probar todo ...

# 5. Deploy a main
git deploy
```

---

### **Caso 3: Hotfix Urgente en Producción**

Para correcciones urgentes que no pueden esperar:

```bash
# 1. Crear hotfix desde main
git checkout main
git pull origin main
git checkout -b hotfix/critical-bug

# 2. Fix rápido
# ... corregir bug ...
git add .
git commit -m "fix: corregir error crítico en cálculo planillas"

# 3. Mergear a main (producción)
git checkout main
git merge hotfix/critical-bug --no-ff -m "Hotfix: error crítico planillas"
git push origin main

# 4. Deploy urgente a producción
# ... deployment manual o automático ...

# 5. Backport a develop (IMPORTANTE!)
git checkout develop
git merge hotfix/critical-bug --no-ff -m "Backport hotfix: error crítico"
git push origin develop

# 6. Limpiar
git branch -d hotfix/critical-bug
```

---

## **Reglas de Oro**

### ✅ **HACER**
1. **Desarrollar siempre en `develop`** (o feature branches para features grandes)
2. **Probar en `develop` antes de mergear a `main`**
3. **`main` siempre debe poder deployarse** a producción sin problemas
4. **Commits descriptivos** con prefijos convencionales:
   - `feat:` - Nueva funcionalidad
   - `fix:` - Corrección de bug
   - `docs:` - Solo documentación
   - `refactor:` - Refactorización de código
   - `test:` - Agregar o modificar tests
   - `chore:` - Tareas de mantenimiento
5. **Usar `--no-ff`** en merges importantes (mantiene historia clara)
6. **Pull antes de push** para evitar conflictos

### ❌ **NO HACER**
1. **NUNCA commit directo a `main`** (solo merges desde `develop`)
2. **NUNCA force push a `main`** (solo en emergencias extremas con backup)
3. **NO dejar features incompletas en `develop`** por mucho tiempo
4. **NO hacer deploy a producción sin probar en `develop` primero**
5. **NO olvidar backport de hotfixes a `develop`**
6. **NO mezclar múltiples features sin relación** en un solo commit

---

## **Comandos Útiles Diarios**

```bash
# Ver estado actual
git st                              # Status compacto
git lg                              # Historia gráfica

# Actualizar develop
git sync                            # Checkout develop + pull

# Deploy a producción
git deploy                          # Merge develop → main

# Crear feature
git feat nombre-feature             # Crea feature/nombre-feature

# Terminar feature
git done                            # Merge a develop y limpia

# Ver diferencias entre branches
git diff develop main               # Ver qué hay en develop que no está en main

# Ver branches locales
git branch                          # Solo locales
git branch -a                       # Locales + remotos

# Limpiar branches mergeados
git branch --merged                 # Ver cuáles están mergeados
git branch -d nombre-branch         # Eliminar branch mergeado
```

---

## **Ejemplo Completo Real**

### **Feature Tarifa Hora + Widget Contratos (17/12/2025)**

```bash
# Desarrollo en develop
git sync
# ... hacer cambios en edit.php, Employee.php, Admin.php, dashboard.php ...
git add app/Views/admin/employees/edit.php
git commit -m "feat: add tarifa_hora field to employee edit form"

git add app/Models/Employee.php app/Controllers/Admin.php app/Views/admin/dashboard.php
git commit -m "feat: add expiring contracts widget to dashboard"

git push origin develop

# Testing en desarrollo
# ... probar ambas funcionalidades ...

# Deploy a producción
git deploy

# Listo! 4 comandos vs los 15+ del flujo anterior
```

---

## **Estructura de Commits**

### **Formato Recomendado**

```
<tipo>: <descripción corta en presente>

[Opcional] Descripción detallada del cambio.
[Opcional] Por qué se hizo este cambio.

[Opcional] 🤖 Generated with Claude Code
[Opcional] Co-Authored-By: Claude <noreply@anthropic.com>
```

### **Ejemplos Buenos**

```bash
feat: add expiring contracts widget to dashboard

Widget shows contracts expiring in next 60 days with color-coded urgency badges.
Employees can be filtered by tipo_planilla_id.

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
```

```bash
fix: correct hourly rate calculation from 220 to 192 hours

Panama labor law uses 8h/day × 24 working days = 192 hours/month.
Previous calculation used 220 which was incorrect.
```

```bash
refactor: simplify git workflow to 2-branch strategy

Removed production branch. Using only develop + main.
Main now directly reflects production server code.
```

---

## **Resolución de Problemas Comunes**

### **Conflictos de Merge**
```bash
# Si hay conflictos al mergear
git checkout develop
git pull origin develop
# Resolver conflictos en archivos marcados
git add .
git commit -m "fix: resolve merge conflicts"
git push origin develop
```

### **Descartar Cambios No Guardados**
```bash
# Archivo específico
git checkout -- nombre-archivo.php

# Todos los cambios
git checkout -- .

# Regresar al último commit (PELIGROSO)
git reset --hard HEAD
```

### **Ver Qué Cambió**
```bash
# Ver cambios no commiteados
git diff

# Ver cambios en archivo específico
git diff nombre-archivo.php

# Ver qué se agregó al staging
git diff --staged
```

### **Deshacer Último Commit (Mantener Cambios)**
```bash
git reset --soft HEAD~1
# Los cambios quedan en staging, listos para re-commit
```

---

## **Deployment a Producción**

### **Proceso Manual**

```bash
# En tu máquina de desarrollo
git deploy

# En servidor de producción (si tienes acceso)
cd /ruta/al/proyecto
git checkout main
git pull origin main

# Ejecutar migraciones si hay
php scripts/run_migrations.php

# Limpiar caché si es necesario
rm -rf storage/cache/*

# Restart servicios si es necesario
# (ejemplo: restart PHP-FPM, Apache, etc.)
```

---

## **Branches Actuales del Proyecto**

Después de la limpieza del 17/12/2025:

```
main                              # ← Producción (código estable)
develop                           # ← Desarrollo activo
feature/organigrama-payroll-core  # ← Feature antiguo (evaluar si mantener)
feature/process_attendance_jobs   # ← Feature antiguo (evaluar si mantener)
```

**Recomendación**: Evaluar si los 2 feature branches antiguos deben mergearse a `develop` o eliminarse.

---

## **Flujo Visual Simplificado**

```
┌─────────────────────────────────────────────┐
│  DESARROLLO DIARIO (80% del tiempo)         │
│                                             │
│  develop (tu rama principal)                │
│    ↓                                        │
│  [edit files]                               │
│    ↓                                        │
│  git add . && git commit                    │
│    ↓                                        │
│  git push origin develop                    │
│    ↓                                        │
│  [testing en desarrollo]                    │
│    ↓                                        │
│  git deploy → main                          │
│                                             │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│  FEATURES GRANDES (20% del tiempo)          │
│                                             │
│  git feat nueva-funcionalidad               │
│    ↓                                        │
│  feature/nueva-funcionalidad                │
│    ↓                                        │
│  [desarrollo varios días]                   │
│    ↓                                        │
│  git done → develop                         │
│    ↓                                        │
│  [testing]                                  │
│    ↓                                        │
│  git deploy → main                          │
│                                             │
└─────────────────────────────────────────────┘
```

---

## **Ventajas del Flujo Simplificado**

✅ **Menos comandos diarios** (3-4 vs 10-15 del flujo anterior)
✅ **Menos posibilidad de errores** (menos branches = menos confusión)
✅ **Deploy más rápido** (develop → main directo)
✅ **Más adecuado para equipos pequeños** (1-3 desarrolladores)
✅ **Mantiene control de calidad** (develop = testing, main = producción)
✅ **Aliases hacen el trabajo pesado** (automatización de comandos comunes)

---

## **Migración del Flujo Anterior**

**Cambios realizados el 17/12/2025:**

1. ✅ Eliminada rama `production` (redundante con `main`)
2. ✅ `main` ahora es la rama de producción estable
3. ✅ `develop` es la única rama de desarrollo
4. ✅ Feature branches solo para features grandes (>2 días)
5. ✅ Aliases git configurados globalmente
6. ✅ Limpieza de feature branches mergeados

**Branches eliminados:**
- `production` (mergeado a `main`)
- `feature/tarifa-hora-edit-form` (mergeado)
- `feature/contracts-expiring-widget` (mergeado)
- `feature/attendance-sync-improvements` (mergeado)
- `feature/business-calendar-sync` (mergeado)
- `feature/organigrama-accounting-integration` (mergeado)
- `feature/excel-accounting-reports` (mergeado)

---

## **NOTA IMPORTANTE**

Este flujo simplificado está diseñado para **proyectos en producción con 1-3 desarrolladores**. Garantiza:

- 🔒 Código estable en `main` siempre listo para deploy
- 🧪 Ambiente de testing en `develop` sin afectar producción
- 📦 Control de versiones sin complejidad innecesaria
- ⚡ Velocidad de deployment sin sacrificar calidad

**Última Actualización**: 17 de Diciembre, 2025
**Autor**: Sistema de Planillas Innova
**Versión**: 2.0 (Flujo Simplificado)
