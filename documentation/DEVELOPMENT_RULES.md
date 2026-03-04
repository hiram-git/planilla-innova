# ⚙️ Reglas de Desarrollo y Modificación de Código

**CRÍTICO - SEGUIR ESTRICTAMENTE PARA EVITAR ROMPER FUNCIONALIDADES**

## 🎯 Principio Fundamental: Mínima Intervención

**"Hacer SOLO lo solicitado, sin tocar sistemas que ya funcionan"**

---

## 📋 Reglas Obligatorias

### 1. NO MODIFICAR archivos core/helpers sin necesidad absoluta

- ❌ **PROHIBIDO**: Cambiar `JavaScriptHelper`, `UrlHelper`, `Database`, `Router`, etc. sin pedirlo explícitamente
- ❌ **PROHIBIDO**: Modificar sistemas de rutas, configuraciones globales, o arquitectura base
- ✅ **PERMITIDO**: Solo si el usuario explícitamente solicita "modifica el helper X"

### 2. RESPETAR convenciones y estándares del proyecto

- ✅ Usar `window.APP_CONFIG` (ya establecido en el proyecto) - NUNCA cambiar a `appConfig`
- ✅ Seguir patrones de nombres de variables y métodos existentes
- ✅ Mantener estructura de carpetas y archivos actual
- ⚠️ Si necesitas cambiar una convención, **PREGUNTAR PRIMERO**

### 3. ALCANCE LIMITADO de modificaciones

- ✅ Si piden "agrega datepicker al formulario X" → Solo agregar datepicker
- ❌ NO agregar validaciones, rutas AJAX, o refactorizar lógica sin pedirlo
- ❌ NO crear archivos JavaScript complejos si el formulario ya funciona con lógica existente
- ✅ Reutilizar lógica existente en lugar de crear nueva

### 4. VERIFICAR antes de modificar

- ✅ Usar `Grep` para buscar cómo se usa actualmente un patrón (ej: `window.APP_CONFIG`)
- ✅ Leer archivos relacionados antes de hacer cambios
- ❌ NO asumir que tu implementación es mejor que la existente
- ⚠️ Si tienes duda, **PREGUNTAR AL USUARIO**

### 5. PREFERIR ediciones mínimas sobre refactorizaciones

- ✅ Agregar `autocomplete="off"` en campos específicos
- ❌ NO refactorizar todo el formulario "para mejorarlo"
- ❌ NO cambiar arquitectura de routing/helpers "para hacerlo más limpio"

---

## 🔴 Casos de Error Comunes (APRENDER DE ESTOS)

### ❌ ERROR - Modificación Excesiva

```
Usuario: "Agrega datepicker y quita autocomplete"
Claude: [Modifica JavaScriptHelper + crea sistema de rutas + refactoriza form.php]
Resultado: Sistema de rutas roto, error 404
```

### ✅ CORRECTO - Modificación Precisa

```
Usuario: "Agrega datepicker y quita autocomplete"
Claude: [Agrega autocomplete="off" + crea JS simple con datepicker]
Resultado: Funciona perfectamente, nada roto
```

---

## ❓ Preguntas Obligatorias Antes de Modificar

Antes de realizar cualquier modificación, pregúntate:

1. **¿El usuario pidió modificar este archivo específicamente?**
   - Si NO, no lo modifiques

2. **¿Este cambio puede romper funcionalidad existente?**
   - Si SÍ, pregunta primero

3. **¿Estoy cambiando una convención del proyecto?**
   - Si SÍ, pregunta primero

4. **¿Puedo lograr el objetivo sin tocar archivos core?**
   - Si SÍ, hazlo así

---

## 💡 En caso de duda: PREGUNTAR > ASUMIR

Si no estás 100% seguro de que una modificación es necesaria o segura, **pregunta al usuario** antes de proceder.

---

## 📝 Política de Documentación

**CRÍTICO - SEGUIR ESTRICTAMENTE**

### Reglas

1. **NUNCA crear archivos de documentación (.md) automáticamente** cuando se implementa un cambio
2. **SOLO crear documentación cuando el usuario lo solicite explícitamente** con frases como:
   - "crea un documento de..."
   - "documenta esto en..."
   - "genera documentación..."
   - "escribe un .md con..."
3. **IMPLEMENTAR CAMBIOS DIRECTAMENTE** sin documentación adicional a menos que se pida
4. **ACTUALIZAR CLAUDE.md** solo cuando sea un cambio de versión mayor o característica significativa

### Ejemplos

- ❌ Usuario: "cambia la función X" → NO crear documento de cambios automáticamente
- ✅ Usuario: "cambia la función X" → Solo implementar el cambio
- ✅ Usuario: "cambia la función X y documenta el cambio" → Implementar + crear documento

### Razón

Evitar saturación de archivos .md innecesarios en el proyecto.

---

## 🚨 Flujo Obligatorio para Análisis

**MANDATORY ANALYSIS WORKFLOW - NO EXCEPTIONS**

Cuando el usuario solicite cualquier tipo de análisis (usando palabras como "analiza", "analyze", "evalúa", "estudia", etc.):

1. **ANÁLISIS**: Realizar investigación y análisis completo
2. **PRESENTACIÓN**: Presentar opciones, pros/contras, recomendaciones
3. **ESPERAR APROBACIÓN**: NO proceder hasta recibir confirmación explícita del usuario
4. **IMPLEMENTACIÓN**: Solo si se solicita específicamente

### Restricciones

- **PROHIBIDO**: Implementar automáticamente después de análisis sin aprobación explícita
- **OBLIGATORIO**: Siempre preguntar "¿Proceder con la implementación de [opción recomendada]?" antes de cualquier implementación

---

## 📌 Important Instruction Reminders

```
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
```

---

**Última Actualización**: 04 de Marzo, 2026
**Estado**: Documento de referencia oficial del proyecto
