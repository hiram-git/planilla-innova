# Matriz completa de permisos por rol {.unnumbered}

| Módulo                 | Super Admin | Administrador | Operador | Sólo lectura |
|------------------------|:-----------:|:-------------:|:--------:|:------------:|
| Dashboard              | X           | X             | X        | X            |
| Empleados              | X           | X             | X (R)    | R            |
| Campos adicionales     | X           | X             | -        | R            |
| Expedientes            | X           | X             | X (R)    | R            |
| Estructura/Horarios    | X           | X             | -        | R            |
| Asistencias            | X           | X             | X        | R            |
| Aprobación HE          | X           | X             | -        | -            |
| Planillas              | X           | X             | R        | R            |
| Conceptos/Fórmulas     | X           | X             | R        | R            |
| Conceptos manuales     | X           | X             | -        | R            |
| Acumulados             | X           | X             | R        | R            |
| Vacaciones             | X           | X             | X        | R            |
| Liquidaciones          | X           | X             | -        | R            |
| Acreedores             | X           | X             | R        | R            |
| Reportes               | X           | X             | X        | X            |
| Exportación INNOVA     | X           | X             | -        | -            |
| Configuración          | X           | X             | -        | -            |
| Calendario empresarial | X           | X             | R        | R            |
| Panel Backoffice       | X           | -             | -        | -            |
| Gestión de tenants     | X           | -             | -        | -            |

**Leyenda**: X = completo · R = sólo lectura · \- = sin acceso

> **TODO**: validar cada permiso contra la implementación real del sistema
> de roles y actualizar.
