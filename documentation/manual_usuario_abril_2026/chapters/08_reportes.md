# Reportes y exportaciones

El sistema genera reportes en tres formatos principales: **PDF** (vía
TCPDF), **Excel** (vía PhpSpreadsheet) y **CSV**. Este capítulo lista
los reportes disponibles, describe su propósito y documenta los
generadores de documentos del empleado (cartas, constancias, plantillas).

## Reportes PDF

Los reportes PDF usan **TCPDF** con layout empresarial (logo + firmas
digitales + numeración de páginas). Los estilos se configuran en la
sección *Empresa* (ver §9.1).

### Reportes de planilla

Desde el detalle de una planilla (estados `PROCESADA` o `CERRADA`):

| Reporte                       | Contenido                                                      |
|-------------------------------|----------------------------------------------------------------|
| **Planilla PDF**              | Documento detallado con todos los empleados y sus conceptos.   |
| **Comprobantes de pago**      | Un recibo individual por empleado (imprimible por lote).       |
| **Reporte de acreedores**     | Deducciones agrupadas por acreedor para transferencias.        |
| **Informe 03**                | Reporte gubernamental (formato oficial panameño).              |

### Reportes de liquidación (§5.4)

- **Certificado de liquidación** PDF con cálculos detallados.
- **Comprobante de liquidación** para firma del empleado.
- **Reporte contable** para registros financieros.

### Reportes de vacaciones (§6)

- **Certificado individual** de vacaciones.
- **Listado** de solicitudes filtrable.
- **Reporte de saldos** por empleado.

### Reportes de asistencias (§3)

- **Reporte de marcaciones** (*Punches Report*): estadísticas del
  período con top 10 de tardanzas y detalle por departamento.
- **Justificaciones** (desde mar-2026): listado de ausencias con
  archivos adjuntos.

### Integración XIII Mes en comprobantes

Desde v3.5 (PRs #90-94) los comprobantes de pago incluyen el **desglose
de la acumulación XIII Mes** del período: se muestra no sólo el total
acumulado sino qué conceptos contribuyeron a él. Esto facilita la
auditoría y mejora la transparencia ante el empleado.

## Exportaciones Excel

Usan **PhpSpreadsheet** con formato profesional (celdas coloreadas,
bordes, tipos de datos, fórmulas nativas de Excel cuando aplica).

### Planilla Panamá (4 hojas)

Formato extendido con las hojas:

1. **Resumen**: totales por tipo de concepto.
2. **Detalle**: una fila por empleado con todas sus líneas de conceptos.
3. **Acreedores**: agrupación de deducciones por acreedor destinatario.
4. **Totales**: sumatorias finales (bruto, deducciones, neto).

### Acumulados (v3.5.12)

Exportación de los tres modos de agrupación (§5.3):

- **Por empleado**
- **Por concepto**
- **Por tipo**

Incluye filtros aplicados en el encabezado para trazabilidad.

### Asistencias

Exportación del reporte de marcaciones (§3.6): 8 métricas principales,
top 10 de tardanzas y detalle por departamento.

## Exportaciones CSV

Archivos de texto plano en UTF-8, compatibles con Excel y Google Sheets.

| Recurso     | Contenido exportado                                       |
|-------------|-----------------------------------------------------------|
| Empleados   | Catálogo completo con todos los campos incluidos en BD.   |
| Acreedores  | Instituciones activas y su información de contacto.       |
| Conceptos   | Catálogo global de conceptos con fórmulas.                |

Los archivos CSV incluyen **fecha y hora de generación** en el nombre
(ej. `empleados_2026-04-24_14-32.csv`) para facilitar la trazabilidad en
carpetas de respaldo.

## Exportación ERP INNOVA

Documentada en §5.5. Formato de texto plano específico para importación
contable en el ERP INNOVA.

## Generadores de documentos del empleado

::: {.badge-new}
**Nuevo en v3.5 (ene-2026)**
:::

Módulo que genera **documentos laborales** (cartas de trabajo,
constancias, certificaciones) en formato PDF o Word, a partir de
plantillas predefinidas y datos del empleado.

### Acceder al módulo

Desde el perfil del empleado → pestaña **Documentos** (o desde el menú
**Empleados → Documentos**).

### Tipos de documento disponibles

El sistema incluye plantillas para los documentos más frecuentes:

| Documento                      | Uso típico                                              |
|--------------------------------|---------------------------------------------------------|
| Carta de trabajo               | Constancia para trámites (visas, bancos, embajadas).    |
| Certificación de salario       | Con monto bruto/neto y antigüedad, para créditos.       |
| Constancia laboral             | Confirmación de vigencia del contrato.                  |
| Certificación de ingresos      | Últimos N meses para seguros o solicitudes bancarias.   |
| Recomendación                  | Referencias para empleado que sale.                     |

### Flujo de generación

1. Seleccionar el empleado.
2. Elegir el tipo de documento.
3. Elegir el formato: **PDF** o **Word** (`.docx`).
4. Ajustar parámetros específicos si aplica (rango de meses para
   certificación de ingresos, destinatario, etc.).
5. Generar — el archivo se descarga automáticamente.

Las plantillas toman automáticamente:

- Datos del empleado (nombre, cédula, cargo, fecha de ingreso).
- Datos de la empresa (razón social, RUC, logo, firmante autorizado).
- Fecha actual.
- Salarios y antigüedad calculados del empleado.

### Personalizar plantillas

Las plantillas base viven en `resources/templates/employee_documents/`
(ver README del repo para ubicación exacta por tenant). Para modificar:

1. Editar la plantilla Word / HTML existente manteniendo los
   *placeholders* (ej. `{{employee_name}}`, `{{company_name}}`).
2. Subir al tenant correspondiente.
3. El generador detecta la plantilla personalizada y la usa en lugar de
   la base.

## Buenas prácticas

- **Generar PDF después de cerrar** una planilla para asegurar que los
  datos son finales y no se recalcularán.
- Para **auditorías externas**, conservar los PDF y Excel del período
  con la fecha de generación en el nombre del archivo.
- Usar los **CSV** para conciliaciones con sistemas externos (BI,
  contabilidad paralela) — son los más ligeros y universales.
- **No regenerar** reportes de períodos antiguos sin una razón clara: si
  los datos cambiaron (p. ej. por reproceso), el PDF nuevo no coincidirá
  con el original y puede generar confusión en auditoría.
- Para documentos del empleado (cartas, constancias), guardar una copia
  firmada en el **expediente del empleado** (§2.4) — los expedientes son
  el archivo oficial del sistema.
