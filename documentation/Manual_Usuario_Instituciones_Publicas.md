# Manual de Usuario - Sistema de Planillas
## Instituciones Públicas

---

**Versión:** 2.0  
**Fecha:** Septiembre 2025  
**Dirigido a:** Administradores de Recursos Humanos - Instituciones Públicas

---

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Configuración Inicial](#configuración-inicial)
3. [Gestión de Estructura Organizacional](#gestión-de-estructura-organizacional)
4. [Administración de Empleados](#administración-de-empleados)
5. [Sistema de Conceptos y Fórmulas](#sistema-de-conceptos-y-fórmulas)
6. [Generación de Planillas](#generación-de-planillas)
7. [Reportes y Consultas](#reportes-y-consultas)
8. [Solución de Problemas](#solución-de-problemas)

---

## Introducción

Este manual está específicamente diseñado para **instituciones públicas** que utilizan el Sistema de Planillas MVC. Las instituciones públicas tienen características particulares:

- ✅ Estructura organizacional basada en presupuesto aprobado
- ✅ Salarios determinados por la posición presupuestaria
- ✅ Gestión de partidas y funciones presupuestarias
- ✅ Módulos especializados para el sector público

### Características Exclusivas para Instituciones Públicas

- **Módulo de Estructura Organizacional** completo
- **Salarios basados en posiciones presupuestarias**
- **Gestión de partidas presupuestarias**
- **Control de funciones institucionales**
- **Validaciones específicas del sector público**

---

## Configuración Inicial

### 1. Configurar Tipo de Institución

**Paso 1:** Navegar a **Configuración → Empresa**

**Paso 2:** En el campo **"Tipo de Institución"**, seleccionar **"Institución Pública"**

```
Tipo de Institución: [Institución Pública ▼]

📋 INFORMACIÓN:
- Institución Pública: Los salarios se toman de las posiciones presupuestarias
- Se habilita el módulo de Estructura Organizacional
- Los empleados deben tener una posición asignada
```

**Paso 3:** Completar información institucional:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre** | Razón social completa | Ministerio de Educación |
| **Dirección** | Dirección física | 6ta. Avenida 1-36, Zona 10 |
| **Teléfono** | Número principal | 2411-9595 |
| **Email** | Correo institucional | rrhh@mineduc.gob.gt |
| **Moneda** | Moneda nacional | Quetzales (Q) |

**Paso 4:** Hacer clic en **"Guardar Configuración"**

### 2. Configuración de Moneda y Formatos

- **Símbolo de moneda:** Q (se aplica automáticamente)
- **Formato numérico:** 1,234.56
- **Zona horaria:** America/Guatemala

---

## Gestión de Estructura Organizacional

> ⚠️ **IMPORTANTE:** Este módulo solo está disponible para Instituciones Públicas

### 3. Configurar Posiciones Presupuestarias

**Acceso:** Menú lateral → **Estructura Organizacional → Posiciones**

#### Crear Nueva Posición

**Paso 1:** Clic en **"Nueva Posición"**

**Paso 2:** Completar formulario:

| Campo | Descripción | Obligatorio |
|-------|-------------|-------------|
| **Nombre** | Denominación del puesto | ✅ |
| **Código** | Código presupuestario | ✅ |
| **Sueldo Base** | Salario mensual autorizado | ✅ |
| **Descripción** | Funciones principales | ❌ |
| **Estado** | Activo/Inactivo | ✅ |

**Ejemplo de posición:**
```
Nombre: Director de Recursos Humanos
Código: 011-001-001
Sueldo Base: Q 8,500.00
Descripción: Responsable de la gestión del talento humano institucional
Estado: Activo
```

**Paso 3:** Guardar posición

### 4. Gestionar Cargos Institucionales

**Acceso:** **Estructura Organizacional → Cargos**

Los cargos representan las responsabilidades específicas dentro de la institución:

- **Jefe de Departamento**
- **Coordinador de Área**
- **Especialista**
- **Analista**
- **Asistente**

### 5. Configurar Funciones Presupuestarias

**Acceso:** **Estructura Organizacional → Funciones**

Funciones según clasificación presupuestaria:

| Código | Función | Descripción |
|--------|---------|-------------|
| **01** | Administración General | Dirección y coordinación |
| **02** | Educación | Servicios educativos |
| **03** | Salud | Servicios de salud |
| **04** | Seguridad | Orden público |

### 6. Administrar Partidas Presupuestarias

**Acceso:** **Estructura Organizacional → Partidas**

Partidas de gasto de personal:

| Partida | Descripción | Uso |
|---------|-------------|-----|
| **011** | Personal Permanente | Empleados de carrera |
| **021** | Personal Temporal | Contratos temporales |
| **022** | Personal por Contrato | Servicios técnicos |
| **029** | Otras Remuneraciones | Bonificaciones especiales |

---

## Administración de Empleados

### 7. Registrar Nuevo Empleado

**Acceso:** **Empleados → Nuevo Empleado**

#### Datos Personales

| Campo | Descripción | Validación |
|-------|-------------|------------|
| **Nombres** | Nombres completos | Requerido |
| **Apellidos** | Apellidos completos | Requerido |
| **DPI** | Documento de identidad | 13 dígitos |
| **NIT** | Número de identificación tributaria | Formato válido |
| **Fecha Nacimiento** | DD/MM/AAAA | Mayor de edad |
| **Teléfono** | Número de contacto | 8 dígitos |
| **Email** | Correo electrónico | Formato válido |
| **Dirección** | Domicilio actual | Requerido |

#### Datos Laborales (Institución Pública)

| Campo | Descripción | Obligatorio |
|-------|-------------|-------------|
| **Código Empleado** | Identificador único | ✅ |
| **Posición** | Puesto presupuestario | ✅ |
| **Horario** | Jornada laboral | ✅ |
| **Fecha Ingreso** | Fecha de nombramiento | ✅ |

> 📋 **NOTA:** En instituciones públicas, el salario se toma automáticamente de la posición presupuestaria asignada.

#### Campos Específicos Mostrados

✅ **Posición** - Lista de posiciones presupuestarias disponibles  
❌ **Cargo** - No se muestra (es privado)  
❌ **Función** - No se muestra (es privado)  
❌ **Partida** - No se muestra (es privado)  
❌ **Sueldo Individual** - No se muestra (se toma de la posición)

### 8. Editar Empleado Existente

**Acceso:** **Empleados → Lista** → Clic en nombre del empleado

- Los mismos campos del registro aplican
- El salario se actualiza automáticamente si se cambia la posición
- Mantener historial de cambios de posición

### 9. Lista de Empleados - Vista Pública

La tabla de empleados muestra:

| Columna | Descripción |
|---------|-------------|
| **Código** | Identificador del empleado |
| **Nombre** | Nombres y apellidos |
| **Posición** | Puesto presupuestario actual |
| **Salario** | Sueldo según posición |
| **Estado** | Activo/Inactivo |
| **Acciones** | Editar, Ver detalles |

---

## Sistema de Conceptos y Fórmulas

### 10. Conceptos para Instituciones Públicas

#### Conceptos Típicos de Ingresos

| Concepto | Fórmula | Descripción |
|----------|---------|-------------|
| **Sueldo Base** | `SALARIO` | Salario según posición |
| **Bonificación Incentivo** | `250` | Fijo según ley |
| **Bonificación Profesional** | `SI(ANTIGUEDAD>=5,SALARIO*0.15,0)` | 15% después de 5 años |
| **Aguinaldo Proporcional** | `SALARIO/12` | 1/12 del salario mensual |

#### Conceptos de Descuentos

| Concepto | Fórmula | Descripción |
|----------|---------|-------------|
| **IGSS Laboral** | `SALARIO*0.0483` | 4.83% del salario |
| **ISR** | `SI(SALARIO>5000,SALARIO*0.05,0)` | 5% si excede Q5,000 |
| **Préstamos** | `ACREEDOR(EMPLEADO,1)` | Según tabla de acreedores |

### 11. Variables Disponibles

Para instituciones públicas, las variables calculadas son:

| Variable | Descripción | Valor Ejemplo |
|----------|-------------|---------------|
| **SALARIO** | Sueldo de la posición presupuestaria | 8500.00 |
| **SUELDO** | Alias de SALARIO | 8500.00 |
| **FICHA** | Código del empleado | "RH001" |
| **EMPLEADO** | Alias de FICHA | "RH001" |
| **HORAS** | Horas laborales semanales | 40 |
| **ANTIGUEDAD** | Años de servicio | 3 |

### 12. Crear Conceptos Personalizados

**Acceso:** **Conceptos → Nuevo Concepto**

#### Ejemplo: Bonificación por Responsabilidad

```
Descripción: Bonificación Responsabilidad
Tipo: Ingreso
Fórmula: SI(ANTIGUEDAD>=3,SALARIO*0.10,0)
Frecuencia: Mensual
```

Configuraciones:
- ☑️ **Imprime en Detalles** - Aparece en el recibo de pago
- ☐ **Prorratea** - No se prorratea
- ☐ **Permite Modificar Valor** - No editable manualmente
- ☑️ **Es Valor de Referencia** - Se usa en otros cálculos
- ☑️ **Incluir en Monto Cálculo** - Afecta totales
- ☐ **Permitir Monto Cero** - No permite valor cero

---

## Generación de Planillas

### 13. Crear Nueva Planilla

**Acceso:** **Planillas → Nueva Planilla**

#### Configuración de Planilla

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Descripción** | Nombre de la planilla | "Planilla Enero 2025" |
| **Fecha Desde** | Inicio del período | 01/01/2025 |
| **Fecha Hasta** | Final del período | 31/01/2025 |
| **Tipo** | Tipo de planilla | Ordinaria |
| **Frecuencia** | Periodicidad | Mensual |
| **Situación** | Estado laboral | En Servicio |

#### Selección de Empleados

- **Todos los empleados activos** (recomendado)
- **Por posición específica**
- **Selección manual**

### 14. Procesar Planilla

**Paso 1:** Clic en **"Procesar Planilla"** en la lista

**Paso 2:** El sistema ejecuta:
- Validación de conceptos aplicables
- Cálculo de fórmulas con salarios de posiciones
- Aplicación de descuentos y deducciones
- Generación de totales

**Paso 3:** Revisión de resultados

### 15. Regenerar Empleado Específico

Si hay cambios en un empleado particular:

**Paso 1:** Ingresar a la planilla procesada  
**Paso 2:** Clic en **"Regenerar"** junto al empleado  
**Paso 3:** Se recalcula solo ese empleado sin afectar los demás

---

## Reportes y Consultas

### 16. Reportes Disponibles

#### Reporte de Planilla Completa
- **Formato:** PDF
- **Contenido:** Todos los empleados con detalles
- **Totales:** Por concepto y generales

#### Reporte Individual
- **Formato:** PDF
- **Contenido:** Recibo de pago individual
- **Uso:** Entrega al empleado

#### Reporte Resumen Ejecutivo
- **Contenido:** Totales por departamento/posición
- **Uso:** Presentación a autoridades

### 17. Consultas Especializadas

#### Por Posición Presupuestaria
- Listar todos los empleados de una posición específica
- Calcular costo total por posición
- Identificar posiciones vacantes

#### Por Partida Presupuestaria
- Agrupar gastos por partida
- Verificar ejecución presupuestaria
- Generar reportes para contraloría

---

## Solución de Problemas

### 18. Problemas Comunes

#### El empleado no aparece en la planilla
**Causa:** No tiene posición asignada  
**Solución:** Asignar una posición presupuestaria válida

#### El salario calculado es incorrecto
**Causa:** Posición con sueldo cero o inválido  
**Solución:** Verificar y actualizar el sueldo de la posición

#### No se muestran conceptos en el cálculo
**Causa:** Conceptos no aplicables al tipo "En Servicio"  
**Solución:** Revisar configuración de situaciones en conceptos

### 19. Validaciones del Sistema

El sistema valida automáticamente:

- ✅ Empleado debe tener posición asignada
- ✅ Posición debe tener sueldo configurado
- ✅ Conceptos deben estar activos
- ✅ Fórmulas deben ser válidas
- ✅ Empleado debe estar activo

### 20. Contacto y Soporte

Para soporte técnico contactar:
- **Email:** soporte@planilla-sistema.com
- **Teléfono:** 2411-9999
- **Horario:** Lunes a Viernes, 8:00 - 17:00

---

## Anexos

### Anexo A: Códigos de Posiciones Comunes

| Código | Posición | Salario Base |
|--------|----------|-------------|
| 001-001 | Ministro/Director General | Q 25,000.00 |
| 001-002 | Viceministro/Subdirector | Q 20,000.00 |
| 002-001 | Director de Área | Q 15,000.00 |
| 003-001 | Jefe de Departamento | Q 12,000.00 |
| 004-001 | Coordinador | Q 10,000.00 |
| 005-001 | Especialista Senior | Q 8,500.00 |
| 006-001 | Analista | Q 7,000.00 |
| 007-001 | Asistente | Q 5,500.00 |

### Anexo B: Partidas Presupuestarias Detalladas

#### Grupo 0 - Servicios Personales

- **011** Personal Permanente
- **012** Personal Permanente Eventual  
- **021** Personal Supernumerario
- **022** Personal por Contrato
- **023** Jornales
- **029** Otras Remuneraciones de Personal Temporal

#### Renglones Específicos

- **011-001** Sueldos
- **011-002** Salarios  
- **021-001** Sueldos de Personal Supernumerario
- **022-001** Servicios Técnicos y Profesionales

---

*© 2025 Sistema de Planillas MVC - Instituciones Públicas*  
*Versión 2.0 - Todos los derechos reservados*