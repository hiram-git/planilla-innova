# Imágenes del manual

Las capturas de pantalla se organizan por capítulo:

- `cap01/` — Introducción y acceso
- `cap02/` — Gestión de personal
- `cap03/` — Control de asistencia (aprobación HE, tolerancias)
- `cap04/` — Conceptos y fórmulas
- `cap05/` — Planillas (cierre, liquidaciones, export INNOVA)
- `cap06/` — Vacaciones
- `cap07/` — Acreedores
- `cap08/` — Reportes
- `cap09/` — Administración (backoffice, calendario)

Logo corporativo: `logo_innova.png` en esta misma carpeta.

## Convenciones

- Formato: PNG para capturas, SVG para diagramas.
- Resolución mínima: 1600 px de ancho (escala bien a A4).
- Nombre: `<contexto>_<accion>.png`, ejemplo: `aprobacion_he_modal.png`.
- Referenciar desde Markdown con ruta relativa:

```markdown
![Pantalla de aprobación de horas extras](../assets/img/cap03/aprobacion_he_modal.png){width=90%}
```
