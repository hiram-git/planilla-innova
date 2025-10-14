# 📁 Directorio de Changelogs - Sistema de Planillas MVC

Este directorio contiene los archivos de changelog individuales por versión del sistema.

---

## 📋 **Propósito**

Mantener la documentación de cambios organizada y escalable, evitando que el archivo `CHANGELOG.md` principal se vuelva demasiado extenso.

---

## 🗂️ **Estructura de Archivos**

Cada versión tiene su propio archivo siguiendo el patrón:

```
vX.Y.Z.md
```

**Ejemplo**:
- `v3.4.1.md` - Versión 3.4.1 (Migraciones BD Cálculos Asistencias)
- `v3.4.0.md` - Versión 3.4.0 (Integración API Base44)

---

## 📝 **Formato de Archivos**

Cada archivo de versión sigue esta estructura:

```markdown
# 📋 CHANGELOG - Versión X.Y.Z

**Fecha**: DD de Mes, AAAA
**Tipo**: [Nueva Funcionalidad | Mejora | Bugfix | Infraestructura]
**Fase**: [Descripción de la fase/subfase]

---

## [TÍTULO PRINCIPAL]

### [Subtítulo Descriptivo]

**Objetivo**: Breve descripción del objetivo de esta versión

---

## **Componentes Implementados**

[Lista detallada de componentes]

---

## 📊 **Estadísticas**

[Métricas del release]

---

## 🔗 **Referencias**

[Enlaces a versiones relacionadas]
```

---

## 🔍 **Convenciones de Nomenclatura**

### **Versiones**:
- **Major** (vX.0.0): Cambios arquitectónicos mayores
- **Minor** (v3.X.0): Nuevas funcionalidades completas
- **Patch** (v3.4.X): Bugfixes, mejoras menores, migraciones

### **Nombres de Archivo**:
- ✅ Correcto: `v3.4.1.md`, `v3.4.0.md`
- ❌ Incorrecto: `3.4.1.md`, `version-3.4.1.md`

---

## 📂 **Navegación**

Para navegar a:
- **Índice Principal**: [../CHANGELOG.md](../CHANGELOG.md)
- **Versiones Legacy**: [../CHANGELOG_LEGACY.md](../CHANGELOG_LEGACY.md) *(próximamente)*
- **Documentación Raíz**: [../](../)

---

## ✍️ **Cómo Crear un Nuevo Changelog**

1. **Copia un template existente**:
   ```bash
   cp v3.4.1.md v3.4.2.md
   ```

2. **Edita el contenido**:
   - Actualiza número de versión
   - Cambia fecha
   - Describe cambios de la nueva versión

3. **Actualiza el índice principal**:
   - Edita `../CHANGELOG.md`
   - Agrega entrada en sección "Últimas Versiones"
   - Mantén orden cronológico (más reciente primero)

4. **Valida formato**:
   - Usa mismo formato de headers (# ## ###)
   - Incluye secciones estándar (Componentes, Estadísticas, Referencias)
   - Agrega emojis para mejor legibilidad

---

## 📊 **Estadísticas Actuales**

- **Total de Versiones Documentadas**: 2
  - v3.4.1 (10-Oct-2025): Migraciones BD Cálculos Asistencias
  - v3.4.0 (9-Oct-2025): Integración API Base44
- **Próximas Migraciones**: Versiones 3.3.x legacy

---

## 🎯 **Mejores Prácticas**

1. **Fecha Clara**: Siempre incluye día, mes y año completo
2. **Tipo Descriptivo**: Define claramente el tipo de release
3. **Componentes Detallados**: Lista todos los archivos/módulos afectados
4. **Estadísticas Precisas**: Incluye métricas (líneas, archivos, tablas BD)
5. **Referencias Cruzadas**: Enlaza versiones anteriores y siguientes
6. **Secciones Estándar**: Mantén estructura consistente entre versiones
7. **Emojis Útiles**: Usa emojis para mejorar legibilidad (📊 📝 ✅ 🔧)

---

**Última Actualización**: 10 de Octubre, 2025
**Sistema**: Planillas MVC v3.4.1
