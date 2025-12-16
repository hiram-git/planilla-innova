# 📋 Manual de Usuario Completo

**Sistema de Planillas INNOVA - Guía Detallada de Funcionalidades**

*Versión 3.3.0 - Actualizado Diciembre 2025*

---

## 📑 Índice de Contenidos

1. [Introducción al Sistema](#1--introducción-al-sistema)
2. [Acceso y Autenticación](#2--acceso-y-autenticación)
3. [Dashboard Principal](#3--dashboard-principal)
4. [Gestión de Personal](#4--gestión-de-personal)
   - 4.1 [Empleados](#41-empleados)
   - 4.2 [Horarios y Turnos](#42-horarios-y-turnos)
   - 4.3 [Estructura Organizacional](#43-estructura-organizacional)
5. [Control de Asistencia](#5--control-de-asistencia)
   - 5.1 [Gestión de Dispositivos](#51-gestión-de-dispositivos)
   - 5.2 [Sincronización de Datos](#52-sincronización-de-datos)
   - 5.3 [Gestión de Marcaciones](#53-gestión-de-marcaciones)
6. [Gestión de Vacaciones](#6--gestión-de-vacaciones)
   - 6.1 [Panel de Control](#61-panel-de-control)
   - 6.2 [Solicitudes y Aprobaciones](#62-solicitudes-y-aprobaciones)
   - 6.3 [Calendario de Vacaciones](#63-calendario-de-vacaciones)
7. [Gestión de Conceptos](#7--gestión-de-conceptos)
8. [Gestión de Acreedores](#8--gestión-de-acreedores)
9. [Gestión de Planillas](#9--gestión-de-planillas)
10. [Sistema de Liquidaciones](#10--sistema-de-liquidaciones)
11. [Reportes y Consultas](#11--reportes-y-consultas)
12. [Configuración del Sistema](#12--configuración-del-sistema)

---

## 1. 🎯 Introducción al Sistema

El Sistema de Planillas INNOVA es una solución integral para la gestión de recursos humanos, nómina y asistencia. Esta versión 3.3.0 incorpora módulos avanzados para el control de vacaciones, integración con dispositivos biométricos y un motor de fórmulas optimizado.

---

## 2. 🔐 Acceso y Autenticación

El acceso al sistema está restringido a usuarios autorizados. Cada usuario tiene un rol que define sus permisos.

### 2.1 Pantalla de Login

**Campos:**
- **Usuario/Email:** Correo electrónico o nombre de usuario registrado.
- **Contraseña:** Clave de acceso personal.

**Botones:**
- `Iniciar Sesión`: Valida las credenciales y redirige al Dashboard.
- `¿Olvidó su contraseña?`: Inicia el proceso de recuperación de cuenta.

---

## 3. 📊 Dashboard Principal

Centro de control que ofrece una vista panorámica del estado de la empresa.

### 3.1 Tarjetas Informativas

- **Total Empleados:** Conteo de empleados activos.
- **Planillas del Mes:** Cantidad de procesos de nómina ejecutados en el mes actual.
- **Solicitudes Pendientes:** Número de solicitudes de vacaciones que requieren aprobación.

---

## 4. 👥 Gestión de Personal

### 4.1 Empleados

Módulo central para la administración de la información de los colaboradores.

#### Formulario de Nuevo Empleado

El formulario se divide en secciones lógicas:

##### A. Información Personal

| Campo | Descripción | Obligatorio |
|-------|-------------|-------------|
| Código | Identificador único (ej. EMP-001). Autogenerado o manual. | Sí |
| Nombres/Apellidos | Datos legales del empleado. | Sí |
| Cédula/DNI | Documento de identidad nacional. | Sí |
| Fecha Nacimiento | Para cálculos de edad. | Sí |
| Género | Masculino/Femenino. | Sí |
| Estado Civil | Soltero, Casado, etc. | No |

##### B. Información Laboral

| Campo | Descripción | Obligatorio |
|-------|-------------|-------------|
| Fecha Ingreso | Fecha de inicio de labores. Base para antigüedad. | Sí |
| Posición/Cargo | Puesto que ocupa en la empresa. | Sí |
| Departamento | Área a la que pertenece. | Sí |
| Horario | Horario asignado para control de asistencia. | Sí |
| Tipo Planilla | Semanal, Quincenal, Mensual. | Sí |

##### C. Contrato y Pagos (Nuevas Funcionalidades)

| Campo | Descripción | Validación |
|-------|-------------|------------|
| Tipo Contrato | Indefinido, Definido, Servicios Prof. | Determina si requiere fecha fin. |
| Fecha Vencimiento | Fin del contrato. | Obligatorio si es Contrato Definido. |
| Forma de Pago | Efectivo, Cheque, ACH. | Habilita campos bancarios. |
| Banco | Entidad bancaria. | Requerido para ACH. |
| No. Cuenta | Número de cuenta del empleado. | Requerido para ACH. |

### 4.2 Horarios y Turnos

Define las reglas de tiempo para el control de asistencia.

#### Campos del Horario

- **Código:** Identificador del horario (ej. SCH-001).
- **Nombre:** Descripción corta (ej. "Administrativo 8-5").
- **Hora Entrada (Time In):** Hora oficial de inicio de jornada.
- **Hora Salida (Time Out):** Hora oficial de fin de jornada.
- **Salida/Entrada Almuerzo:** Rango de tiempo para descanso.

#### Configuración de Tolerancias

Permite definir márgenes de tiempo aceptables para las marcaciones:

- **Tolerancia Entrada (Antes/Después):** Minutos permitidos antes o después de la hora de entrada sin considerar llegada tardía o tiempo extra.
- **Tolerancia Salida (Antes/Después):** Minutos permitidos para la salida.

### 4.3 Estructura Organizacional

Define la jerarquía y clasificación de puestos en la empresa.

- **Cargos:** Títulos de trabajo (ej. "Contador", "Gerente").
- **Posiciones:** Plazas específicas dentro de la estructura (Sector Público).
- **Partidas:** Asignaciones presupuestarias.
- **Funciones:** Roles específicos o tareas asignadas.

---

## 5. 🕒 Control de Asistencia

Nuevo módulo para la gestión integral de marcas, dispositivos biométricos y reportes de tiempo.

### 5.1 Gestión de Dispositivos

Permite configurar las fuentes de datos para la asistencia.

#### Tipos de Dispositivos Soportados

1. **Biométricos (ZKTeco/Hikvision):** Conexión directa por IP.
2. **API:** Recepción de marcas vía web service.
3. **Archivos de Texto:** Importación de logs (.txt, .csv).
4. **Manual:** Ingreso directo en sistema.

#### Formulario de Dispositivo

| Campo | Descripción |
|-------|-------------|
| Nombre | Identificador amigable (ej. "Reloj Entrada Principal"). |
| Tipo | Selección del protocolo de comunicación. |
| IP / Puerto | Dirección de red del dispositivo (para biométricos). |
| Formato Archivo | Configuración de columnas para importación de texto. |

### 5.2 Sincronización de Datos

Proceso para descargar marcas desde los dispositivos al sistema.

- `Sincronizar Todo`: Conecta con todos los dispositivos activos y descarga nuevas marcas.
- `Ver Historial`: Muestra logs de sincronizaciones pasadas (éxitos y errores).

### 5.3 Gestión de Marcaciones

Vista detallada de las entradas y salidas de los empleados.

- **Vista Diario:** Muestra marcas de un día específico.
- **Edición Manual:** Los administradores pueden corregir o agregar marcas olvidadas (requiere justificación).
- **Estado:** El sistema califica automáticamente: `A Tiempo`, `Tardanza`, `Ausencia`.

---

## 6. 🏖️ Gestión de Vacaciones

Sistema completo para el control de descansos anuales, compensaciones y balances.

### 6.1 Panel de Control

Muestra el listado de empleados con sus saldos actualizados.

- **Días Ganados:** Total acumulado según antigüedad (30 días/año norma estándar).
- **Días Tomados:** Total de días ya disfrutados o pagados.
- **Saldo Disponible:** Días listos para solicitar.

### 6.2 Solicitudes y Aprobaciones

#### Crear Solicitud

Formulario para registrar nuevas vacaciones.

| Campo | Descripción |
|-------|-------------|
| Empleado | Colaborador que solicita. |
| Fechas (Inicio/Fin) | Rango del descanso. |
| Tipo | **Anuales** (descanso físico) o **Compensadas** (pago en efectivo sin descanso). |
| Días a Pagar | Cantidad de días que se reflejarán en nómina. |
| Días a Disfrutar | Cantidad de días de ausencia física. |

#### Flujo de Aprobación

1. **Pendiente:** Estado inicial. Afecta el saldo "proyectado" pero no el real.
2. **Aprobada:** `Aprobar`. Descuenta los días del balance oficial y genera el registro para pago.
3. **Rechazada:** `Rechazar`. Libera los días solicitados de vuelta al balance. Requiere motivo.

### 6.3 Calendario de Vacaciones

Vista visual de ausencias programadas. Se integra con el **Calendario Empresarial** para mostrar días feriados y no laborables que no cuentan como días de vacaciones.

---

## 7. 💰 Gestión de Conceptos

El módulo de conceptos es el corazón del sistema de nómina. Aquí se definen todas las reglas de ingresos (asignaciones) y egresos (deducciones) que se aplicarán a los empleados.

### 7.1 Detalles del Formulario de Concepto

#### A. Información Básica y Tipo

| Campo | Descripción | Opciones |
|-------|-------------|----------|
| **Concepto** | Código único identificador (ej. SALARIO, CSS). | Texto alfanumérico |
| **Descripción** | Nombre visible en reportes y colillas. | Texto libre |
| **Tipo de Concepto** | Define el comportamiento contable. | <ul><li>**Asignación (A):** Ingresos que SUMAN al neto a pagar (Salarios, Bonos).</li><li>**Deducción (D):** Descuentos que RESTAN al neto (Seguro Social, Préstamos).</li><li>**Patronal (P):** Costos cubiertos por la empresa, no afectan el neto del empleado pero sí el costo de planilla.</li></ul> |
| **Unidad** | Define la naturaleza del valor calculado. | <ul><li>**Monto:** Valor monetario directo.</li><li>**Horas:** Cantidad de tiempo (se multiplica internamente por tarifa horaria).</li><li>**Porcentaje (%):** Valor porcentual (útil para cálculos sobre salario base).</li><li>**Días:** Cantidad de días.</li></ul> |

#### B. Fórmula del Cálculo (Motor V2)

El sistema utiliza un motor de fórmulas avanzado que permite cálculos dinámicos. Puede escribir fórmulas matemáticas estándar utilizando las siguientes variables y funciones:

##### 🧮 Variables Disponibles

- `SALARIO` / `SUELDO`: Salario base mensual del empleado.
- `HORAS`: Horas trabajadas en el período.
- `ANTIGUEDAD`: Años de servicio en la empresa (con decimales).
- `FICHA` / `EMPLEADO`: Código o número de ficha del empleado.
- `INIPERIODO`: Fecha de inicio del período de planilla actual (YYYY-MM-DD).
- `FINPERIODO`: Fecha de fin del período de planilla actual (YYYY-MM-DD).

##### 🔧 Funciones Especiales

- `SI(condición, valor_si_verdadero, valor_si_falso)`: Lógica condicional.
  *Ej: SI(ANTIGUEDAD > 2, 100, 0)*
- `ACREEDOR(EMPLEADO, ID_ACREEDOR)`: Obtiene la cuota a descontar para un acreedor específico.
- `ACUMULADOS(['CODIGO1', 'CODIGO2'], FECHA_INI, FECHA_FIN)`: Suma los montos históricos de los conceptos especificados en el rango de fechas. Vital para cálculos de promedios.

#### C. Opciones de Configuración (Checkboxes)

Estas opciones definen el comportamiento operativo del concepto:

- **¿Se imprimen detalles?**: Si se marca, el concepto aparecerá desglosado en la colilla de pago del empleado. Si no, es un cálculo interno invisible.
- **¿Se prorratea?**: Indica si el cálculo debe ajustarse proporcionalmente a los días trabajados (útil para ingresos tardíos o salidas anticipadas).
- **¿Permite modificar el valor?**: Habilita la edición manual del monto calculado durante la revisión de la planilla. Útil para bonos variables.
- **¿Valor de referencia?**: Marca el concepto como informativo, no afecta el total a pagar pero sirve de base para otros cálculos.
- **¿Usar cálculo de monto?**: **⚠️ CRÍTICO**. Debe estar marcado para que el sistema evalúe la fórmula. Si se desmarca, el sistema esperará un valor fijo manual.
- **¿Permitir monto cero?**: Permite que el concepto se guarde en la planilla aunque el resultado sea 0.00.

#### D. Configuraciones Avanzadas

> **ℹ️ Nota:** Estas configuraciones determinan **CUÁNDO** y **A QUIÉN** se aplica el concepto automáticamente.

- **Tipos de Planilla:** Seleccione en qué procesos corre este concepto (ej. Solo en Planilla Quincenal, o también en Décimo Tercer Mes).
- **Frecuencias:** Controla la periodicidad.
  - *Siempre:* Todas las planillas.
  - *Primera Quincena:* Solo en la primera mitad del mes.
  - *Segunda Quincena:* Solo en el cierre de mes.
- **Situaciones:** Filtra por estado del empleado (ej. Solo empleados "Activos", o incluir "Vacaciones").
- **Acumulado:** **⚠️ IMPORTANTE**. Aquí se vincula el concepto a bolsas de acumulados (ej. XIII Mes, Vacaciones, Prima Antigüedad).
  *Ejemplo: Si marca "XIII Mes" en el concepto "Salario Base", cada pago de salario sumará al acumulado para el cálculo del bono navideño.*

#### E. Configuración de Reportes PDF

Controla cómo se agrupa y visualiza este concepto en los reportes legales y contables.

- **Incluir en Reportes PDF:** Interruptor general de visibilidad.
- **Categoría:** Agrupación semántica para totales.
  - *Seguro Social / Educativo / Renta:* Columnas específicas de ley.
  - *Otras Deducciones:* Agrupación genérica de descuentos.
  - *Otro:* Conceptos generales.
- **Orden:** Número para forzar la posición en la lista (menor número sale primero).

---

## 8. 🏦 Gestión de Acreedores

Control de descuentos por préstamos externos (Bancos, Financieras, Comerciales).

- **Acreedores:** Entidades a las que se debe pagar (ej. Banco General).
- **Deducciones:** Préstamos específicos de empleados. Permite definir monto total, cuota mensual y control de saldo.

---

## 9. 📝 Gestión de Planillas

Proceso central de cálculo de nómina.

### Pasos para Generar Planilla

1. `Nueva Planilla`: Seleccionar tipo (Quincenal/Mensual) y fechas.
2. **Pre-cálculo:** El sistema procesa automáticamente conceptos fijos y fórmulas.
3. **Revisión:** Verificación de montos por empleado. Se pueden agregar novedades manuales.
4. **Cierre:** `Cerrar Planilla`. Finaliza el proceso, genera asientos y actualiza acumulados. **Irreversible.**

---

## 10. 🏁 Sistema de Liquidaciones

Cálculo de prestaciones laborales por terminación de contrato (Panamá).

- **Cálculos Automáticos:** Vacaciones proporcionales, Décimo Tercer Mes proporcional, Prima de Antigüedad, Indemnización.
- **Motivos:** Renuncia, Despido Justificado, Despido Injustificado, Mutuo Acuerdo.

---

## 11. 📈 Reportes y Consultas

Generación de documentos y archivos de exportación.

- **Comprobantes de Pago:** Recibos individuales por empleado.
- **Planilla General:** Resumen de todos los pagos y deducciones.
- **Archivo SIPE:** Exportación para la Caja de Seguro Social.
- **Reporte de Vacaciones:** Saldos y gozes históricos.
- **Asistencia:** Resumen de tardanzas y ausencias.

---

## 12. ⚙️ Configuración del Sistema

### Empresa

Datos generales, logo y representantes legales.

### Calendario Empresarial

Definición de días no laborables, feriados nacionales y días de duelo. Afecta directamente el cálculo de vacaciones y asistencia.

### Usuarios y Roles

Gestión de acceso al sistema. Permite crear roles personalizados con permisos granulares por módulo.

---

© 2025 Innova Planilla. Todos los derechos reservados.
