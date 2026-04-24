# Glosario {.unnumbered}

**Acumulado**
: Total calculado de un concepto para un empleado en un período (mes, trimestre,
  año). Ejemplos: XIII Mes, vacaciones, prima de antigüedad.

**Base44**
: API externa de control de marcaciones integrada al sistema mediante
  `Base44ApiClient`.

**CSRF**
: *Cross-Site Request Forgery*. Protección de formularios con *token* único por
  sesión.

**Eisvogel**
: Plantilla LaTeX para Pandoc usada en la generación del PDF de este manual.

**Horas extras aprobadas**
: Registros de horas extras con `overtime_status = 'APPROVED'`, únicos
  considerados por las fórmulas `HORAS_EXTRAS_APROBADAS_*()`.

**INIPERIODO / FINPERIODO**
: Variables dinámicas del motor de fórmulas que representan las fechas de
  inicio y fin del período de planilla en curso.

**Liquidación**
: Cálculo de las prestaciones finales de un empleado al cesar su relación
  laboral (por motivo: renuncia, despido justificado/injustificado, mutuo
  acuerdo, etc.).

**Motor de fórmulas**
: Componente que evalúa las fórmulas de los conceptos usando
  `nxp/math-executor`, sin recurrir a `eval()`.

**Multi-tenant (o multitenancy)**
: Arquitectura que permite servir a múltiples empresas (*tenants*) desde una
  única instalación, aislando sus datos.

**Planilla**
: Proceso de cálculo de nómina para un período y un tipo (regular, XIII mes,
  vacaciones, liquidación).

**Score de puntualidad**
: Métrica 0-100 calculada por `AttendanceCalculator` que refleja la
  puntualidad de un empleado en un día o período.

**Super Admin**
: Usuario con `is_system_admin = 1` y acceso al Panel Backoffice.

**Tenant**
: Empresa individual en el sistema multi-tenant.

**Tolerancia (asistencias)**
: Margen en minutos configurado por horario para entrada, salida o almuerzo,
  dentro del cual no se aplican sanciones.

**TOC**
: *Table of Contents* (tabla de contenido). Se genera automáticamente en
  Pandoc con `--toc`.

**XIII Mes**
: Prestación legal panameña equivalente al salario mensual prorrateado. El
  sistema lo calcula trimestralmente (salario anual ÷ 3).
