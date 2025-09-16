# Manual de Usuario - Sistema de Planillas
## Empresas Privadas

---

**Versión:** 2.0  
**Fecha:** Septiembre 2025  
**Dirigido a:** Administradores de Recursos Humanos - Empresas Privadas

---

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Configuración Inicial](#configuración-inicial)
3. [Administración de Empleados](#administración-de-empleados)
4. [Sistema de Conceptos y Fórmulas](#sistema-de-conceptos-y-fórmulas)
5. [Gestión de Acreedores y Deducciones](#gestión-de-acreedores-y-deducciones)
6. [Generación de Planillas](#generación-de-planillas)
7. [Reportes y Consultas](#reportes-y-consultas)
8. [Solución de Problemas](#solución-de-problemas)

---

## Introducción

Este manual está específicamente diseñado para **empresas privadas** que utilizan el Sistema de Planillas MVC. Las empresas privadas tienen características particulares:

- ✅ Salarios individualizados por empleado
- ✅ Flexibilidad en estructura organizacional
- ✅ Gestión simplificada de cargos y funciones
- ✅ Control directo sobre remuneraciones

### Características Exclusivas para Empresas Privadas

- **Salarios individuales** por empleado
- **Estructura organizacional flexible**
- **Sin restricciones presupuestarias**
- **Gestión ágil de personal**
- **Campos opcionales** para mayor flexibilidad

### Diferencias con Instituciones Públicas

| Aspecto | Empresa Privada | Institución Pública |
|---------|----------------|---------------------|
| **Módulo Estructura** | ❌ No disponible | ✅ Completo |
| **Salarios** | Individual por empleado | Según posición presupuestaria |
| **Flexibilidad** | Total | Limitada por presupuesto |
| **Campos obligatorios** | Mínimos esenciales | Posición requerida |

---

## Configuración Inicial

### 1. Configurar Tipo de Empresa

**Paso 1:** Navegar a **Configuración → Empresa**

**Paso 2:** En el campo **"Tipo de Institución"**, seleccionar **"Empresa Privada"**

```
Tipo de Institución: [Empresa Privada ▼]

📋 INFORMACIÓN:
- Empresa Privada: Los salarios se configuran individualmente por empleado
- Campos cargo, función y partida son opcionales
- Mayor flexibilidad en la gestión de personal
```

**Paso 3:** Completar información empresarial:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre** | Razón social | Tecnología Innovadora S.A. |
| **Dirección** | Dirección fiscal | 12 Calle 1-25, Zona 10, Guatemala |
| **Teléfono** | Número principal | 2333-4000 |
| **Email** | Correo corporativo | rrhh@tecinnova.com |
| **NIT** | Número de identificación | 12345678-9 |
| **Moneda** | Moneda de operación | Quetzales (Q) |

**Paso 4:** Hacer clic en **"Guardar Configuración"**

### 2. Configuración de Parámetros

#### Configuración de Moneda
- **Símbolo:** Q (automático)
- **Formato:** 1,234.56
- **Decimales:** 2

#### Configuración Regional
- **Zona horaria:** America/Guatemala
- **Idioma:** Español
- **Formato de fecha:** DD/MM/AAAA

---

## Administración de Empleados

### 3. Registrar Nuevo Empleado

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

#### Datos Laborales (Empresa Privada)

| Campo | Descripción | Obligatorio |
|-------|-------------|-------------|
| **Código Empleado** | Identificador único | ✅ |
| **Cargo** | Puesto de trabajo | ❌ Opcional |
| **Función** | Función específica | ❌ Opcional |
| **Partida** | Clasificación interna | ❌ Opcional |
| **Sueldo Individual** | Salario mensual | ✅ **Requerido** |
| **Horario** | Jornada laboral | ✅ |
| **Fecha Ingreso** | Fecha de contratación | ✅ |

> 💰 **IMPORTANTE:** En empresas privadas, cada empleado tiene su propio **Sueldo Individual** que debe configurarse manualmente.

#### Campos Específicos Mostrados

❌ **Posición** - No se muestra (es público)  
✅ **Cargo** - Lista de cargos disponibles (opcional)  
✅ **Función** - Lista de funciones (opcional)  
✅ **Partida** - Lista de partidas internas (opcional)  
✅ **Sueldo Individual** - Campo numérico obligatorio

### 4. Ejemplo de Registro Completo

```
=== DATOS PERSONALES ===
Nombres: Juan Carlos
Apellidos: García López  
DPI: 2545123456101
NIT: 4567891-2
Teléfono: 55123456
Email: jgarcia@empresa.com

=== DATOS LABORALES ===
Código: EMP001
Cargo: Gerente de Ventas
Función: Supervisión de Equipo Comercial
Partida: Administración
Sueldo Individual: Q 8,500.00
Horario: Lunes a Viernes 8:00-17:00
Fecha Ingreso: 15/01/2025
```

### 5. Editar Empleado Existente

**Acceso:** **Empleados → Lista** → Clic en nombre del empleado

#### Modificaciones Permitidas
- ✅ Cambiar sueldo individual en cualquier momento
- ✅ Actualizar cargo, función, partida como necesario
- ✅ Modificar datos personales y de contacto
- ✅ Ajustar horarios y fechas

#### Historial de Cambios
El sistema mantiene registro de:
- Cambios de salario con fechas
- Modificaciones de cargo/función
- Actualizaciones de datos personales

### 6. Lista de Empleados - Vista Privada

La tabla de empleados muestra:

| Columna | Descripción |
|---------|-------------|
| **Código** | Identificador del empleado |
| **Nombre** | Nombres y apellidos completos |
| **Cargo** | Puesto actual (si está asignado) |
| **Salario** | Sueldo individual configurado |
| **Estado** | Activo/Inactivo |
| **Acciones** | Editar, Ver detalles, Eliminar |

---

## Sistema de Conceptos y Fórmulas

### 7. Conceptos para Empresas Privadas

#### Conceptos Típicos de Ingresos

| Concepto | Fórmula | Descripción |
|----------|---------|-------------|
| **Salario Base** | `SALARIO` | Sueldo individual del empleado |
| **Bono Productividad** | `SI(HORAS>40,SALARIO*0.10,0)` | 10% si trabaja más de 40h |
| **Comisiones** | `SALARIO*0.05` | 5% del salario base |
| **Horas Extra** | `(HORAS-40)*25` | Q25 por hora extra |
| **Aguinaldo** | `SALARIO/12` | Proporcional mensual |

#### Conceptos de Descuentos

| Concepto | Fórmula | Descripción |
|----------|---------|-------------|
| **IGSS Laboral** | `SALARIO*0.0483` | 4.83% obligatorio |
| **ISR** | `SI(SALARIO>5000,(SALARIO-5000)*0.05,0)` | 5% sobre exceso Q5,000 |
| **Préstamo Personal** | `ACREEDOR(EMPLEADO,1)` | Según acuerdo individual |
| **Seguro Médico** | `150` | Aporte fijo mensual |

### 8. Variables Disponibles

Para empresas privadas, las variables calculadas son:

| Variable | Descripción | Valor Ejemplo |
|----------|-------------|---------------|
| **SALARIO** | Sueldo individual configurado | 8500.00 |
| **SUELDO** | Alias de SALARIO | 8500.00 |
| **FICHA** | Código del empleado | "EMP001" |
| **EMPLEADO** | Alias de FICHA | "EMP001" |
| **HORAS** | Horas laborales semanales | 45 |
| **ANTIGUEDAD** | Años de servicio | 2 |

### 9. Crear Conceptos Personalizados

**Acceso:** **Conceptos → Nuevo Concepto**

#### Ejemplo 1: Bono por Antigüedad

```
Descripción: Bono Antigüedad
Tipo: Ingreso
Fórmula: SI(ANTIGUEDAD>=2,SALARIO*0.05*ANTIGUEDAD,0)
Frecuencia: Mensual
Situación: En Servicio
```

#### Ejemplo 2: Descuento por Tardanzas

```
Descripción: Descuento Tardanzas
Tipo: Descuento
Fórmula: SI(HORAS<40,SALARIO*0.02,0)
Frecuencia: Mensual
Situación: En Servicio
```

#### Configuraciones Recomendadas

**Para Bonos e Incentivos:**
- ☑️ **Imprime en Detalles** - Visible en recibo
- ☐ **Prorratea** - Usualmente no
- ☑️ **Permite Modificar Valor** - Flexibilidad manual
- ☑️ **Es Valor de Referencia** - Para otros cálculos
- ☑️ **Incluir en Monto Cálculo** - Afecta totales
- ☑️ **Permitir Monto Cero** - Si no aplica

---

## Gestión de Acreedores y Deducciones

### 10. Configurar Acreedores

**Acceso:** **Acreedores → Nuevo Acreedor**

#### Tipos Comunes en Empresas Privadas

| Tipo | Ejemplo | Uso |
|------|---------|-----|
| **Préstamos Personales** | Banco Industrial | Descuentos por préstamo |
| **Seguros** | Seguros G&T | Prima mensual |
| **Cooperativas** | COOSECREB | Ahorros y préstamos |
| **Pensiones** | Plan Jubilación | Aporte voluntario |

#### Ejemplo de Configuración

```
Nombre: Préstamos Bancarios
Descripción: Descuentos por préstamos personales
Estado: Activo
Tipo: Deducción Fija
```

### 11. Asignar Deducciones a Empleados

**Acceso:** **Empleados → [Seleccionar empleado] → Deducciones**

#### Proceso de Asignación

**Paso 1:** Seleccionar acreedor de la lista
**Paso 2:** Configurar deducción:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Monto** | Cantidad a descontar | Q 350.00 |
| **Fecha Inicio** | Cuándo inicia | 01/02/2025 |
| **Fecha Fin** | Cuándo termina | 01/02/2026 |
| **Frecuencia** | Periodicidad | Mensual |

**Paso 3:** Activar deducción

### 12. Función ACREEDOR en Fórmulas

La función `ACREEDOR(EMPLEADO, id_acreedor)` busca automáticamente el monto asignado:

```
Concepto: Préstamo Banco
Fórmula: ACREEDOR(EMPLEADO, 1)
```

Esto descuenta automáticamente el monto configurado para cada empleado.

---

## Generación de Planillas

### 13. Crear Nueva Planilla

**Acceso:** **Planillas → Nueva Planilla**

#### Configuración Típica

| Campo | Valor Recomendado | Descripción |
|-------|-------------------|-------------|
| **Descripción** | "Planilla Febrero 2025" | Identificación clara |
| **Fecha Desde** | 01/02/2025 | Inicio período |
| **Fecha Hasta** | 28/02/2025 | Final período |
| **Tipo** | Ordinaria | Tipo estándar |
| **Frecuencia** | Mensual | Más común |
| **Situación** | En Servicio | Empleados activos |

#### Selección de Empleados

**Opción 1: Todos los empleados**
- Incluye automáticamente todos los empleados activos
- Recomendado para planillas regulares

**Opción 2: Selección por cargo**
- Filtrar por cargo específico
- Útil para bonos especiales

**Opción 3: Selección manual**
- Marcar empleados individuales
- Para planillas extraordinarias

### 14. Procesar Planilla

**Paso 1:** En la lista de planillas, clic **"Procesar Planilla"**

**Paso 2:** El sistema ejecuta automáticamente:
1. **Validación inicial** - Verifica datos de empleados
2. **Cálculo de variables** - SALARIO, HORAS, ANTIGUEDAD
3. **Evaluación de fórmulas** - Aplica conceptos configurados
4. **Cálculo de deducciones** - Procesa función ACREEDOR()
5. **Generación de totales** - Suma ingresos y descuentos

**Paso 3:** Revisión de resultados

#### Información Mostrada Post-Procesamiento

```
Planilla: Febrero 2025
Estado: Procesada ✅
Empleados: 25
Total Ingresos: Q 187,500.00
Total Descuentos: Q 45,230.00
Líquido a Pagar: Q 142,270.00
```

### 15. Regenerar Cálculos

#### Regeneración Individual
**Cuándo usar:** Cambios en un empleado específico

**Proceso:**
1. Entrar a la planilla procesada
2. Localizar al empleado
3. Clic **"Regenerar"**
4. Solo recalcula ese empleado

#### Regeneración Completa
**Cuándo usar:** Cambios en conceptos o fórmulas generales

**Proceso:**
1. **Planillas → [Planilla] → "Reprocesar Completa"**
2. Confirmar acción
3. Recalcula todos los empleados

---

## Reportes y Consultas

### 16. Reportes de Planilla

#### Reporte Completo (PDF)
**Contenido:**
- Lista completa de empleados
- Desglose por concepto
- Totales generales
- Firmas de autorización

#### Recibos Individuales (PDF)
**Contenido:**
- Datos del empleado
- Conceptos aplicados
- Cálculo detallado
- Total líquido a recibir

#### Reporte Resumen Ejecutivo
**Contenido:**
- Totales por departamento/cargo
- Comparativo mensual
- Indicadores clave
- Gráficos de distribución

### 17. Consultas Especializadas

#### Por Rango Salarial
- Empleados por rango de sueldo
- Análisis de equidad salarial
- Distribución de remuneraciones

#### Por Cargo/Función
- Agrupación por posición
- Costos promedio por cargo
- Análisis organizacional

#### Por Período
- Comparativos mensuales
- Tendencias de costos
- Variaciones estacionales

### 18. Exportación de Datos

#### Formatos Disponibles
- **PDF** - Reportes oficiales
- **Excel** - Análisis adicional
- **CSV** - Integración con otros sistemas

#### Datos Exportables
- Planillas completas
- Datos de empleados
- Historial de pagos
- Configuración de conceptos

---

## Solución de Problemas

### 19. Problemas Comunes y Soluciones

#### El empleado no aparece en la planilla
**Causas posibles:**
- Empleado inactivo
- Sin sueldo individual configurado
- Fecha de ingreso posterior al período

**Solución:**
1. Verificar estado del empleado (Activo/Inactivo)
2. Confirmar que tiene sueldo individual asignado
3. Verificar fechas de ingreso vs. período de planilla

#### Los cálculos de conceptos son incorrectos
**Causas posibles:**
- Error en fórmulas
- Variables mal configuradas
- Conceptos inactivos

**Solución:**
1. Probar fórmula en **Conceptos → [Concepto] → "Probar Fórmula"**
2. Verificar que el concepto esté activo
3. Validar que la situación coincida con el empleado

#### La función ACREEDOR no funciona
**Causas posibles:**
- Deducción no asignada al empleado
- ID de acreedor incorrecto en la fórmula
- Fechas de vigencia expiradas

**Solución:**
1. Verificar en **Empleados → [Empleado] → Deducciones**
2. Confirmar ID del acreedor en la fórmula
3. Revisar fechas de inicio y fin de la deducción

#### Error en totales de planilla
**Causas posibles:**
- Conceptos duplicados
- Configuración incorrecta de "Incluir en Monto Cálculo"
- Fórmulas con errores lógicos

**Solución:**
1. Revisar configuración de cada concepto
2. Verificar que no haya conceptos duplicados
3. Validar fórmulas con empleado de prueba

### 20. Mejores Prácticas

#### Gestión de Empleados
- ✅ Mantener códigos de empleado únicos y significativos
- ✅ Actualizar salarios individuales oportunamente
- ✅ Documentar cambios de cargo/función
- ✅ Revisar periodicamente empleados inactivos

#### Configuración de Conceptos
- ✅ Usar nombres descriptivos para conceptos
- ✅ Probar fórmulas antes de activar
- ✅ Documentar la lógica de fórmulas complejas
- ✅ Revisar configuraciones periódicamente

#### Procesamiento de Planillas
- ✅ Hacer backup antes de procesar planillas grandes
- ✅ Verificar datos antes del procesamiento final
- ✅ Mantener historial de planillas procesadas
- ✅ Documentar cualquier ajuste manual

### 21. Contacto y Soporte

Para asistencia técnica:
- **Email:** soporte@planilla-sistema.com
- **Teléfono:** 2333-5000
- **WhatsApp:** +502 5555-1234
- **Horario:** Lunes a Viernes, 7:30 - 18:00

**Soporte Prioritario:** Para empresas con más de 50 empleados

---

## Anexos

### Anexo A: Fórmulas Comunes

#### Cálculo de ISR Simplificado
```
SI(SALARIO<=5000, 0, 
   SI(SALARIO<=20000, (SALARIO-5000)*0.05,
      SI(SALARIO<=40000, 750+(SALARIO-20000)*0.07,
         2150+(SALARIO-40000)*0.10)))
```

#### Bono de Productividad Escalonado
```
SI(ANTIGUEDAD>=5, SALARIO*0.15,
   SI(ANTIGUEDAD>=3, SALARIO*0.10,
      SI(ANTIGUEDAD>=1, SALARIO*0.05, 0)))
```

#### Horas Extra con Diferentes Tarifas
```
SI(HORAS<=40, 0,
   SI(HORAS<=48, (HORAS-40)*(SALARIO/160)*1.5,
      (8*(SALARIO/160)*1.5)+((HORAS-48)*(SALARIO/160)*2)))
```

### Anexo B: Códigos de Empleado Sugeridos

#### Por Departamento
- **ADM001** - Administración
- **VNT001** - Ventas  
- **MKT001** - Marketing
- **TEC001** - Tecnología
- **FIN001** - Finanzas
- **RRH001** - Recursos Humanos

#### Por Nivel Jerárquico
- **GER001** - Gerencia
- **SUP001** - Supervisión
- **ANA001** - Analistas
- **AST001** - Asistentes
- **AUX001** - Auxiliares

### Anexo C: Plantilla de Conceptos Inicial

| Concepto | Tipo | Fórmula | Frecuencia |
|----------|------|---------|------------|
| Salario Base | Ingreso | SALARIO | Mensual |
| Bonificación Decreto | Ingreso | 250 | Mensual |
| IGSS Laboral | Descuento | SALARIO*0.0483 | Mensual |
| ISR | Descuento | SI(SALARIO>5000,(SALARIO-5000)*0.05,0) | Mensual |
| Aguinaldo Proporcional | Ingreso | SALARIO/12 | Mensual |

---

*© 2025 Sistema de Planillas MVC - Empresas Privadas*  
*Versión 2.0 - Todos los derechos reservados*