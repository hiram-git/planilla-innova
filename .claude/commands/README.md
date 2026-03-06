# 📚 Claude Code Slash Commands - Sistema Planillas

Este directorio contiene los **slash commands** personalizados para el proyecto de planillas.

## 🎯 ¿Qué son los Slash Commands?

Los slash commands son atajos que ejecutan instrucciones complejas con Claude Code. Son archivos `.md` que se convierten en comandos ejecutables precedidos por `/`.

## 📋 Comandos Disponibles

### `/crud-generator`

**Descripción**: Genera módulo CRUD completo siguiendo el patrón MVC del proyecto.

**Documentación base**: `documentation/PATRON_DESARROLLO_MVC.md`

**Uso**:
```
/crud-generator ModuleName table_name field1:type field2:type...
```

**Ejemplos**:

1. **Módulo simple de Departamentos**:
```
/crud-generator Department departments name:string code:string description:text is_active:boolean
```

2. **Módulo de Bonos**:
```
/crud-generator Bonus bonuses name:string amount:decimal description:text is_taxable:boolean effective_date:date
```

3. **Módulo de Deducciones**:
```
/crud-generator Deduction deductions name:string percentage:decimal fixed_amount:decimal type:string is_mandatory:boolean
```

4. **Módulo de Categorías**:
```
/crud-generator Category categories name:string parent_id:int order:int is_active:boolean
```

**Genera**:
- ✅ Model (`app/Models/{ModuleName}.php`)
- ✅ Controller (`app/Controllers/{ModuleName}Controller.php`)
- ✅ Vista index con DataTable (`app/Views/admin/{module}/index.php`)
- ✅ Migraciones SQL (UP y DOWN)
- ✅ Instrucciones para rutas y sidebar

**Características**:
- Sigue patrón `PATRON_DESARROLLO_MVC.md`
- CSRF protection automático
- PDO prepared statements
- patrón `ob_start()`/`ob_get_clean()` para scripts
- DataTables en español
- Toastr para notificaciones
- UrlHelper para URLs base
- Manejo de errores con try-catch

---

### `/gsap-animate`

**Descripción**: Genera animaciones GSAP siguiendo los patrones del proyecto.

**Documentación base**: `documentation/GSAP_ANIMATION_PATTERN.md`

**Uso**:
```
/gsap-animate module_name animation_type [mode]
```

**Parámetros**:
- `module_name` - Nombre del módulo (e.g., "employees", "reports")
- `animation_type` - Tipo de animaciones:
  - `basic` - Solo DataTable (filas, botones, iconos)
  - `advanced` - Incluye info-boxes, badges, modales, shake effect
- `mode` (opcional) - Modo de implementación:
  - `inline` (default) - Código en la vista PHP
  - `modular` - Archivo JavaScript separado (reutilizable)

**Ejemplos**:

1. **Animaciones básicas para módulo de empleados** (inline):
```
/gsap-animate employees basic inline
```

2. **Animaciones avanzadas para reportes** (inline):
```
/gsap-animate reports advanced inline
```

3. **Animaciones básicas modulares** (para templates compartidos):
```
/gsap-animate reference basic modular
```

4. **Animaciones avanzadas modulares**:
```
/gsap-animate approvals advanced modular
```

**Genera**:

**Modo Inline**:
- ✅ Variable `$styles` con CSS para ocultar elementos
- ✅ Variable `$scripts` con orden correcto de carga
- ✅ Funciones GSAP globales (window.animateXXXTableRows)
- ✅ Animaciones básicas (tabla, botones, iconos)
- ✅ Animaciones avanzadas (si se solicita)

**Modo Modular**:
- ✅ Archivo JavaScript completo con IIFE pattern
- ✅ Funciones GSAP integradas en el módulo
- ✅ Vista PHP simplificada
- ✅ Instrucción para copiar a `/public/assets/`

**Tipos de Animaciones**:

| Tipo | Animaciones Incluidas |
|------|----------------------|
| **Basic** | • Filas de tabla (fade-in + slide-up)<br>• Botones de acción (hover scale 1.15)<br>• Iconos (rotation 360°)<br>• Paginación (fade-in) |
| **Advanced** | Todo lo de Basic PLUS:<br>• Info-boxes (estadísticas)<br>• Badges con `fromTo` (corrección de tamaño)<br>• Botones modales<br>• Botones de exportación<br>• Shake effect (callouts/alertas)<br>• Botones de filtro con sombras dinámicas |

**Características**:
- GSAP ya está cargado globalmente (NO se carga nuevamente)
- Orden de carga correcto: Config → Módulo → GSAP
- Validación `typeof gsap === "undefined"`
- Uso de `clearProps: "all"` en todas las animaciones
- `gsap.fromTo()` para badges (evita badges pequeños)
- `.off()` antes de `.on()` (evita duplicados)
- Keyframes explícitos para shake effect
- Shadow colors dinámicos por tipo de botón
- Event delegation para elementos modales

**⚠️ Importante para Modo Modular**:
Después de generar el archivo, DEBES copiarlo a `/public/`:
```bash
cp assets/javascript/modules/{module}.js public/assets/javascript/modules/{module}.js
```
Si no haces esto, el navegador obtendrá un error 404.

---

### `/git-safe-commit`

**Descripción**: Genera mensaje de commit seguro en inglés usando Conventional Commits (solo feat/fix/chore).

**Documentación base**: Conventional Commits + reglas de seguridad del proyecto

**Uso**:
```
/git-safe-commit
```

**Sin argumentos** - Analiza automáticamente los archivos staged.

**Características**:
- ✅ Solo usa tipos: `feat:`, `fix:`, `chore:`
- ✅ Analiza **exclusivamente** archivos STAGED (`git diff --staged`)
- ✅ Tiempo imperativo presente (add, fix, update)
- ✅ Mensajes en inglés
- ✅ **Seguridad**: NUNCA menciona breaking, security, integrity, corruption
- ✅ Sin `!`, sin `BREAKING CHANGE`
- ✅ Prefiere una línea (sin body innecesario)

**Output**:
Solo el mensaje de commit, listo para usar:
```
feat: add employee vacation balance calculation
```

**Flujo de trabajo típico**:
```bash
# 1. Hacer cambios en archivos
# 2. Agregar a staging
git add file1.php file2.js

# 3. Generar mensaje con Claude Code
/git-safe-commit

# 4. Usar el mensaje generado
git commit -m "feat: add GSAP animations to employee list"
```

**Cuándo Usar**:
- Cuando tienes cambios staged y necesitas un mensaje profesional
- Quieres seguir Conventional Commits sin pensar en el formato
- Necesitas un mensaje neutral que no revele detalles sensibles

**Ejemplo de salida**:
```
chore: update slash commands documentation
```

---

## 🔧 Tipos de Datos Soportados

| Argumento | SQL Type | HTML Input |
|-----------|----------|------------|
| `string` | VARCHAR(255) | text |
| `text` | TEXT | textarea |
| `int` / `integer` | INT | number |
| `decimal` / `float` | DECIMAL(10,2) | number step="0.01" |
| `boolean` / `bool` | TINYINT(1) | checkbox |
| `date` | DATE | date |
| `datetime` | DATETIME | datetime-local |
| `timestamp` | TIMESTAMP | datetime-local |

## 🎓 Cómo Usar un Slash Command

1. **En Claude Code**, simplemente escribe el comando con `/`:
   ```
   /crud-generator Department departments name:string code:string
   ```

2. **Claude Code ejecutará** todas las instrucciones del comando automáticamente

3. **Revisa los archivos generados** y sigue el checklist final

4. **Completa las tareas manuales**:
   - Agregar rutas al router
   - Agregar entrada en el sidebar
   - Ejecutar migración
   - Probar CRUD completo

## 📝 Crear Nuevos Comandos

Para crear un nuevo slash command:

1. Crea un archivo `.md` en este directorio
2. Usa el frontmatter YAML:
```markdown
---
name: mi-comando
description: Descripción breve del comando
argument-hint: formato de argumentos
allowed-tools: [Write, Read, Edit, Bash]
---

Instrucciones detalladas para Claude...
```

3. El nombre del archivo no importa, pero usa descriptivos (ej: `migration-maker.md`)
4. El `name` en el frontmatter define el comando (ej: `/mi-comando`)

## 🎯 Ideas para Futuros Comandos

Comandos planeados para próximas versiones:

- `/migration-maker` - Crear migraciones SQL (UP y DOWN)
- `/test-generator` - Generar tests unitarios PHPUnit
- `/api-endpoint` - Generar endpoint REST API completo
- `/security-audit` - Auditoría de seguridad (SQL injection, XSS, CSRF)
- `/changelog-update` - Actualizar CHANGELOG.md automáticamente
- `/pdf-report` - Generar nuevo tipo de reporte PDF con TCPDF
- `/formula-helper` - Ayudar con fórmulas del motor (validar sintaxis)
- `/service-generator` - Generar Service class para lógica de negocio
- `/permission-check` - Verificar permisos en rutas y vistas
- `/db-seeder` - Generar seeders para datos de prueba

## 💡 Cómo Contribuir

Si creas un nuevo slash command útil:

1. Documéntalo en este README
2. Sigue el formato YAML frontmatter
3. Incluye ejemplos de uso
4. Referencia la documentación base si existe
5. Agrega checklist de tareas post-generación

## 📚 Referencias

- **Documentación oficial**: [Claude Code Slash Commands](https://code.claude.com/docs/en/slash-commands)
- **Comunidad**: [awesome-claude-code](https://github.com/hesreallyhim/awesome-claude-code)
- **Documentación del proyecto**:
  - Patrón MVC: `documentation/PATRON_DESARROLLO_MVC.md`
  - Patrón GSAP: `documentation/GSAP_ANIMATION_PATTERN.md`
  - Reglas de desarrollo: `documentation/DEVELOPMENT_RULES.md`
  - Changelog: `documentation/CHANGELOG.md`

## 🐛 Troubleshooting

### Comando no aparece en la lista
- Verifica que el archivo `.md` esté en `.claude/commands/`
- Revisa que el frontmatter YAML tenga el campo `name`
- Reinicia Claude Code si es necesario

### Comando genera código incorrecto
- Lee la documentación base referenciada
- Verifica que los argumentos sean correctos
- Revisa el formato de los argumentos (orden y tipo)

### Archivos JavaScript no cargan (404)
- Para modo modular, DEBES copiar a `/public/assets/`
- Verifica permisos de lectura en el archivo
- Revisa la ruta en el `src` del script tag

---

**Última Actualización**: 6 de Marzo, 2026
**Comandos Disponibles**: 3 (`/crud-generator`, `/gsap-animate`, `/git-safe-commit`)
**Estado**: Activo y en expansión
