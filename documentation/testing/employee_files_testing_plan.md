# Plan de Pruebas - Módulo Expedientes de Empleados

**Módulo**: Employee Files
**Ruta**: `/panel/employee-files/create/{employee_id}`
**Fecha**: 10 de Enero, 2026
**Versión**: v3.5.17

---

## 📋 Resumen Ejecutivo

Este documento detalla el plan de pruebas para el módulo de Expedientes de Empleados, el cual permite gestionar 13 tipos de expedientes diferentes con 68 subtipos únicos, cada uno con formularios dinámicos específicos.

**Total de Combinaciones a Probar**: 68 formularios diferentes

---

## 🎯 Objetivos de las Pruebas

1. Verificar que cada tipo y subtipo carga el formulario dinámico correcto
2. Validar campos obligatorios y opcionales en cada formulario
3. Comprobar carga de archivos adjuntos
4. Verificar generación automática de número de documento
5. Validar persistencia de datos en base de datos
6. Comprobar edición y actualización de expedientes

---

## 📊 Catálogo de Tipos y Subtipos

### 1. Estudios Académicos (13 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Diplomado | institución*, título obtenido*, fecha inicio, fecha fin, número registro, número resolución, archivo título |
| 2 | Técnico | (mismos campos) |
| 3 | Maestría | (mismos campos) |
| 4 | Bachiller | (mismos campos) |
| 5 | Ingeniería | (mismos campos) |
| 6 | En Derecho Administrativo | (mismos campos) |
| 7 | Primaria | (mismos campos) |
| 8 | Licenciatura | (mismos campos) |
| 9 | Doctorado | (mismos campos) |
| 10 | Profesorado | (mismos campos) |
| 11 | Post Grado | (mismos campos) |
| 12 | No Especificado | (mismos campos) |
| 13 | Primer Ciclo | (mismos campos) |

**Campos del Formulario**:
- ✅ Institución* (text)
- ✅ Título obtenido* (text)
- ✅ Fecha inicio (date)
- ✅ Fecha fin (date)
- ✅ Número de registro (text)
- ✅ Número de resolución (text)
- ✅ Archivo del título (file: .pdf, image/*)

---

### 2. Capacitación (5 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Curso | nombre evento*, organizador, horas, fecha evento, certificado |
| 2 | Charla | (mismos campos) |
| 3 | Taller | (mismos campos) |
| 4 | Jornada | (mismos campos) |
| 5 | Seminario | (mismos campos) |

**Campos del Formulario**:
- ✅ Nombre del evento* (text)
- ✅ Institución organizadora (text)
- ✅ Horas de duración (number, step 0.5)
- ✅ Fecha del evento (date)
- ✅ Certificado (file: .pdf, image/*)

---

### 3. Permisos (10 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Otros | fecha/hora inicio, fecha/hora fin, motivo, jefe inmediato, archivo |
| 2 | Enfermedad | (mismos campos) |
| 3 | Eventos Académicos | (mismos campos) |
| 4 | Misión Oficial | (mismos campos) |
| 5 | Nacimiento | (mismos campos) |
| 6 | Duelo | (mismos campos) |
| 7 | Otros asuntos personales | (mismos campos) |
| 8 | Representación Gremial | (mismos campos) |
| 9 | Cita Médica | (mismos campos) |
| 10 | Matrimonio | (mismos campos) |

**Campos del Formulario**:
- ✅ Fecha y hora inicio (datetime)
- ✅ Fecha y hora fin (datetime)
- ✅ Motivo detallado (textarea)
- ✅ Jefe inmediato (text)
- ✅ Archivo justificativo (file: .pdf, image/*)

---

### 4. Amonestaciones (2 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Escrita | fecha incidente, descripción, resolución, archivo |
| 2 | Verbal | (mismos campos) |

**Campos del Formulario**:
- ✅ Fecha del incidente (date)
- ✅ Descripción del hecho (textarea)
- ✅ Resolución (text)
- ✅ Archivo de resolución (file: .pdf, image/*)

---

### 5. Movimiento de Personal (4 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Asignación | fecha movimiento, cargo anterior, nuevo cargo, resolución, archivo |
| 2 | Designación | (mismos campos) |
| 3 | Traslado | (mismos campos) |
| 4 | Préstamo Interinstitucional | (mismos campos) |

**Campos del Formulario**:
- ✅ Fecha del movimiento (date)
- ✅ Cargo/posición anterior (text)
- ✅ Nuevo cargo/posición (text)
- ✅ Resolución (text)
- ✅ Documento del movimiento (file: .pdf, image/*)

---

### 6. Evaluación de Desempeño (4 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Bueno | período, fecha evaluación, evaluador, puntaje |
| 2 | No Satisfactorio | (mismos campos) |
| 3 | Excelente | (mismos campos) |
| 4 | Regular | (mismos campos) |

**Campos del Formulario**:
- ✅ Período evaluado (text)
- ✅ Fecha de evaluación (date)
- ✅ Evaluador (text)
- ✅ Puntaje (number, step 0.01)

---

### 7. Vacaciones (6 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Acción de Personal (AUMENTA) | período inicio, período fin, días, saldo anterior, saldo posterior |
| 2 | Inicialización Período | (mismos campos) |
| 3 | Resuelto Normal | (mismos campos) |
| 4 | Acción de Personal (DISMINUYE) | (mismos campos) |
| 5 | Migración Período | (mismos campos) |
| 6 | Resuelto Especial | (mismos campos) |

**Campos del Formulario**:
- ✅ Período inicio (date)
- ✅ Período fin (date)
- ✅ Días solicitados (number, step 1)
- ✅ Saldo anterior (number, step 0.01)
- ✅ Saldo posterior (number, step 0.01)

---

### 8. Tiempo Compensatorio (2 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Aumenta | fecha movimiento, horas ajustadas, saldo anterior, saldo posterior |
| 2 | Disminuye | (mismos campos) |

**Campos del Formulario**:
- ✅ Fecha del movimiento (date)
- ✅ Horas ajustadas (number, step 0.25)
- ✅ Saldo anterior (number, step 0.25)
- ✅ Saldo posterior (number, step 0.25)

---

### 9. Documento (2 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Licencia | tipo documento, fecha emisión, fecha vencimiento, entidad emisora, archivo |
| 2 | Cédula/RUC | (mismos campos) |

**Campos del Formulario**:
- ✅ Tipo de documento (text)
- ✅ Fecha de emisión (date)
- ✅ Fecha de vencimiento (date)
- ✅ Entidad emisora (text)
- ✅ Documento principal (file: .pdf, image/*)

---

### 10. Experiencia (2 subtipos)

| # | Subtipo | Campos Específicos |
|---|---------|-------------------|
| 1 | Trabajo Realizado | institución, cargo, fecha inicio, fecha fin, referencia |
| 2 | Labor Realizada | (mismos campos) |

**Campos del Formulario**:
- ✅ Institución/empresa (text)
- ✅ Cargo desempeñado (text)
- ✅ Fecha inicio (date)
- ✅ Fecha fin (date)
- ✅ Referencia/Contacto (text)

---

### 11. Licencias con Sueldo (5 subtipos)

**Campos Comunes** (todos los subtipos):
- ✅ Fecha inicio* (date)
- ✅ Fecha fin* (date)
- ✅ Total de días concedidos* (number, step 1)
- ✅ Goza de remuneración* (select: Si/No, default: Si)
- ✅ Motivo (textarea)
- ✅ Autorizado por (text)
- ✅ Resolución (text)
- ✅ Documento de respaldo (file: .pdf, image/*)

| # | Subtipo | Campos Adicionales Específicos |
|---|---------|-------------------------------|
| 1 | Representación de la Institución, Estado o País | + Institución/evento representado, lugar, descripción actividad |
| 2 | Estudios | + Programa/cursos, institución educativa, horario (select: Mañana/Tarde/Noche) |
| 3 | Representación de la asociación de servidor | + Nombre asociación, cargo que representa, actividad gremial |
| 4 | Capacitación | + Nombre curso/evento, entidad organizadora, duración en horas |
| 5 | RAZONES EXTRAORDINARIAS | + Descripción de la razón extraordinaria |

---

### 12. Licencias sin Sueldo (4 subtipos)

**Campos Comunes** (todos los subtipos):
- ✅ Fecha inicio* (date)
- ✅ Fecha fin* (date)
- ✅ Total de días* (number, step 1)
- ✅ Goza de remuneración (text, readonly, default: "No")
- ✅ Motivo (textarea)
- ✅ Aprobado por (text)
- ✅ Documento de aprobación (file: .pdf, image/*)

| # | Subtipo | Campos Adicionales Específicos |
|---|---------|-------------------------------|
| 1 | Asumir cargo de elección popular | + Cargo público, entidad, período del cargo |
| 2 | Asuntos Personales | + Motivo detallado |
| 3 | Asumir cargo de libre nombramiento y remoción | + Cargo, institución, copia del nombramiento* (file) |
| 4 | Estudiar | + Programa de estudios, institución, duración del programa |

---

### 13. Licencias Especiales (4 subtipos)

**Campos Comunes** (todos los subtipos):
- ✅ Fecha inicio* (date)
- ✅ Fecha fin (si aplica) (date)
- ✅ Total de días (o hasta recuperación)* (text)
- ✅ Diagnóstico o justificación médica* (textarea)
- ✅ Centro médico (text)
- ✅ Médico tratante (text)
- ✅ Dictamen médico o certificado* (file: .pdf, image/*)

| # | Subtipo | Campos Adicionales Específicos |
|---|---------|-------------------------------|
| 1 | Enfermedad Profesional | + Informe de accidente/enfermedad profesional, entidad que emite |
| 2 | Riesgos Profesionales | + Informe de accidente/enfermedad profesional, entidad que emite |
| 3 | Enfermedad/Incapacidad superior quince días | + Días de reposo prescritos |
| 4 | Gravidez | + Fecha probable de parto, semanas de gestación, prenatal/postnatal (select) |

---

## 🧪 Casos de Prueba

### Prueba 1: Carga de Formularios Dinámicos

**Objetivo**: Verificar que cada tipo y subtipo cargue su formulario correcto

**Pasos**:
1. Acceder a `/panel/employee-files/create/2`
2. Seleccionar un tipo en el select "Tipo"
3. Verificar que el select "Subtipo" se carga automáticamente con los subtipos correspondientes
4. Seleccionar un subtipo
5. Verificar que el formulario dinámico se carga con los campos correctos

**Criterios de Éxito**:
- ✅ Select de subtipos carga por AJAX
- ✅ Formulario dinámico se renderiza correctamente
- ✅ Campos obligatorios marcados con asterisco (*)
- ✅ Tipos de campos correctos (text, date, textarea, select, file, number)

**Matriz de Prueba**: Probar TODAS las 68 combinaciones tipo-subtipo

---

### Prueba 2: Validación de Campos Obligatorios

**Objetivo**: Verificar que los campos marcados como obligatorios no permitan enviar formulario vacío

**Casos**:

#### 2.1 Campos Base (todos los tipos)
- ❌ Enviar sin fecha de documento → Error esperado
- ❌ Enviar sin tipo seleccionado → Error esperado
- ❌ Enviar sin subtipo seleccionado → Error esperado

#### 2.2 Campos Dinámicos Específicos

**Estudios Académicos**:
- ❌ Enviar sin institución → Error esperado
- ❌ Enviar sin título obtenido → Error esperado
- ✅ Enviar con solo campos obligatorios → Éxito

**Capacitación**:
- ❌ Enviar sin nombre del evento → Error esperado
- ✅ Enviar solo con nombre → Éxito

**Licencias con Sueldo**:
- ❌ Enviar sin fecha inicio → Error esperado
- ❌ Enviar sin fecha fin → Error esperado
- ❌ Enviar sin total de días → Error esperado
- ✅ Enviar con campos obligatorios → Éxito

**Licencias Especiales**:
- ❌ Enviar sin diagnóstico → Error esperado
- ❌ Enviar sin dictamen médico (archivo) → Error esperado
- ✅ Enviar con todos los campos obligatorios → Éxito

---

### Prueba 3: Carga de Archivos

**Objetivo**: Verificar que los campos tipo "file" permiten subir archivos correctamente

**Casos**:

#### 3.1 Tipos de Archivo Permitidos
- ✅ Subir PDF (.pdf) → Éxito
- ✅ Subir imagen JPG (.jpg) → Éxito
- ✅ Subir imagen PNG (.png) → Éxito
- ✅ Subir imagen GIF (.gif) → Éxito
- ❌ Subir archivo no permitido (.exe, .zip) → Error esperado

#### 3.2 Tamaño de Archivo
- ✅ Subir archivo < 5MB → Éxito
- ❌ Subir archivo > 5MB → Error esperado

#### 3.3 Archivos Múltiples
- ✅ Subir archivos adjuntos adicionales → Éxito
- ✅ Subir archivo específico del formulario (ej: title_file en Estudios) → Éxito
- ✅ Subir ambos tipos en la misma transacción → Éxito

**Formularios con Campos File a Probar**:
1. Estudios Académicos → title_file
2. Capacitación → certificate_file
3. Permisos → justification_file
4. Amonestaciones → resolution_file
5. Movimiento de Personal → resolution_file
6. Documento → document_file
7. Licencias con Sueldo → resolution_file
8. Licencias sin Sueldo → approval_document
9. Licencias sin Sueldo (subtipo específico) → appointment_file
10. Licencias Especiales → medical_file

---

### Prueba 4: Generación de Número de Documento

**Objetivo**: Verificar que el número de documento se genera automáticamente

**Pasos**:
1. Seleccionar tipo y subtipo
2. Ingresar fecha del documento
3. Verificar que el campo "Número de documento" se autocompleta

**Criterios de Éxito**:
- ✅ Formato: `TIPO-SUBTIPO-AÑO-SECUENCIA`
- ✅ El número se actualiza al cambiar tipo, subtipo o fecha
- ✅ El número es único por combinación tipo-subtipo-año

---

### Prueba 5: Persistencia de Datos

**Objetivo**: Verificar que los datos se guardan correctamente en base de datos

**Pasos**:
1. Crear un expediente completo para cada tipo
2. Verificar redirección a listado de expedientes
3. Verificar mensaje de éxito
4. Acceder a detalle del expediente creado
5. Verificar que todos los datos se guardaron correctamente

**Tablas a Verificar**:
- `employee_files` → datos base + extra_fields (JSON)
- `employee_file_attachments` → archivos adjuntos

**Criterios de Éxito**:
- ✅ Todos los campos dinámicos se guardan en `extra_fields` como JSON
- ✅ Archivos se guardan en `storage/tenants/{tenant}/files/employee_files/employee_{id}/`
- ✅ Relaciones foreign key correctas
- ✅ Timestamps correctos

---

### Prueba 6: Edición de Expedientes

**Objetivo**: Verificar que los expedientes pueden editarse correctamente

**Pasos**:
1. Crear un expediente
2. Acceder a edición desde `/panel/employee-files/edit/{id}`
3. Verificar que formulario carga con datos existentes
4. Modificar campos
5. Guardar cambios
6. Verificar que los cambios se aplicaron

**Casos Específicos**:

#### 6.1 Cambio de Tipo/Subtipo
- ⚠️ Cambiar a tipo diferente → Se pierde formulario dinámico anterior (esperado)
- ✅ Mantener mismo tipo y cambiar subtipo → Campos comunes se preservan

#### 6.2 Actualización de Archivos
- ✅ Agregar nuevos archivos → Se agregan a los existentes
- ✅ Eliminar archivos existentes → Se eliminan correctamente
- ✅ Mantener archivos existentes → No se duplican

---

### Prueba 7: Campos Especiales

**Objetivo**: Verificar comportamiento de campos especiales

#### 7.1 Campos tipo SELECT

**Licencias con Sueldo - Estudios → Horario**:
- ✅ Opciones: Mañana, Tarde, Noche
- ✅ Placeholder: "Seleccione..."
- ✅ Valor se guarda correctamente

**Licencias con Sueldo - Todos → Goza de remuneración**:
- ✅ Opciones: Si, No
- ✅ Default: "Si"
- ✅ Valor se guarda correctamente

**Licencias sin Sueldo - Todos → Goza de remuneración**:
- ✅ Valor: "No"
- ✅ Campo readonly
- ✅ No se puede modificar

**Licencias Especiales - Gravidez → Prenatal/Postnatal**:
- ✅ Opciones: Prenatal, Postnatal
- ✅ Placeholder: "Seleccione..."
- ✅ Valor se guarda correctamente

#### 7.2 Campos tipo NUMBER con STEP

**Capacitación → Horas de duración**:
- ✅ Step: 0.5
- ✅ Acepta decimales: 1.5, 2.0, 8.5
- ❌ Rechaza valores no múltiplos de 0.5

**Evaluación → Puntaje**:
- ✅ Step: 0.01
- ✅ Acepta hasta 2 decimales: 85.50, 92.75

**Tiempo Compensatorio → Horas ajustadas**:
- ✅ Step: 0.25
- ✅ Acepta cuartos de hora: 1.25, 2.50, 3.75

#### 7.3 Campos tipo DATETIME

**Permisos → Fecha y hora inicio/fin**:
- ✅ Permite seleccionar fecha y hora
- ✅ Formato correcto en base de datos
- ✅ Validación: hora fin > hora inicio

---

### Prueba 8: Casos Límite (Edge Cases)

#### 8.1 Datos Vacíos/Nulos
- ✅ Campos opcionales vacíos → Se guardan como NULL
- ✅ Textareas vacíos → Se guardan como NULL
- ✅ Selects sin selección → Error si es requerido

#### 8.2 Caracteres Especiales
- ✅ Texto con acentos: "José García" → Se guarda correctamente (UTF-8)
- ✅ Texto con símbolos: "Título en Derecho & Administración" → Se guarda correctamente
- ✅ JSON encoding correcto en extra_fields

#### 8.3 Nombres de Archivo
- ✅ Archivo con espacios: "certificado 2024.pdf" → Se sanitiza
- ✅ Archivo con caracteres especiales → Se sanitiza
- ✅ Archivo con nombre muy largo (>100 chars) → Se trunca correctamente

---

## 📝 Checklist de Pruebas por Tipo

### ✅ Estudios Académicos (13 subtipos)
- [ ] Diplomado - Formulario carga correctamente
- [ ] Técnico - Formulario carga correctamente
- [ ] Maestría - Formulario carga correctamente
- [ ] Bachiller - Formulario carga correctamente
- [ ] Ingeniería - Formulario carga correctamente
- [ ] En Derecho Administrativo - Formulario carga correctamente
- [ ] Primaria - Formulario carga correctamente
- [ ] Licenciatura - Formulario carga correctamente
- [ ] Doctorado - Formulario carga correctamente
- [ ] Profesorado - Formulario carga correctamente
- [ ] Post Grado - Formulario carga correctamente
- [ ] No Especificado - Formulario carga correctamente
- [ ] Primer Ciclo - Formulario carga correctamente
- [ ] Validación campos obligatorios (institución*, título*)
- [ ] Carga de archivo título
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Capacitación (5 subtipos)
- [ ] Curso - Formulario carga correctamente
- [ ] Charla - Formulario carga correctamente
- [ ] Taller - Formulario carga correctamente
- [ ] Jornada - Formulario carga correctamente
- [ ] Seminario - Formulario carga correctamente
- [ ] Validación campo obligatorio (nombre evento*)
- [ ] Campo horas con step 0.5
- [ ] Carga de certificado
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Permisos (10 subtipos)
- [ ] Otros - Formulario carga correctamente
- [ ] Enfermedad - Formulario carga correctamente
- [ ] Eventos Académicos - Formulario carga correctamente
- [ ] Misión Oficial - Formulario carga correctamente
- [ ] Nacimiento - Formulario carga correctamente
- [ ] Duelo - Formulario carga correctamente
- [ ] Otros asuntos personales - Formulario carga correctamente
- [ ] Representación Gremial - Formulario carga correctamente
- [ ] Cita Médica - Formulario carga correctamente
- [ ] Matrimonio - Formulario carga correctamente
- [ ] Campos datetime funcionan correctamente
- [ ] Carga de archivo justificativo
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Amonestaciones (2 subtipos)
- [ ] Escrita - Formulario carga correctamente
- [ ] Verbal - Formulario carga correctamente
- [ ] Textarea descripción funciona
- [ ] Carga de archivo resolución
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Movimiento de Personal (4 subtipos)
- [ ] Asignación - Formulario carga correctamente
- [ ] Designación - Formulario carga correctamente
- [ ] Traslado - Formulario carga correctamente
- [ ] Préstamo Interinstitucional - Formulario carga correctamente
- [ ] Campos cargo anterior/nuevo
- [ ] Carga de documento movimiento
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Evaluación de Desempeño (4 subtipos)
- [ ] Bueno - Formulario carga correctamente
- [ ] No Satisfactorio - Formulario carga correctamente
- [ ] Excelente - Formulario carga correctamente
- [ ] Regular - Formulario carga correctamente
- [ ] Campo puntaje con step 0.01
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Vacaciones (6 subtipos)
- [ ] Acción de Personal (AUMENTA) - Formulario carga correctamente
- [ ] Inicialización Período - Formulario carga correctamente
- [ ] Resuelto Normal - Formulario carga correctamente
- [ ] Acción de Personal (DISMINUYE) - Formulario carga correctamente
- [ ] Migración Período - Formulario carga correctamente
- [ ] Resuelto Especial - Formulario carga correctamente
- [ ] Campos numéricos de saldo funcionan
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Tiempo Compensatorio (2 subtipos)
- [ ] Aumenta - Formulario carga correctamente
- [ ] Disminuye - Formulario carga correctamente
- [ ] Campo horas con step 0.25
- [ ] Cálculo de saldos
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Documento (2 subtipos)
- [ ] Licencia - Formulario carga correctamente
- [ ] Cédula/RUC - Formulario carga correctamente
- [ ] Campos fecha emisión/vencimiento
- [ ] Carga de documento principal
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Experiencia (2 subtipos)
- [ ] Trabajo Realizado - Formulario carga correctamente
- [ ] Labor Realizada - Formulario carga correctamente
- [ ] Campos fecha inicio/fin
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Licencias con Sueldo (5 subtipos)
- [ ] Representación Institución/Estado/País - Formulario completo
- [ ] Estudios - Formulario completo + select horario
- [ ] Representación asociación servidor - Formulario completo
- [ ] Capacitación - Formulario completo
- [ ] RAZONES EXTRAORDINARIAS - Formulario completo
- [ ] Campos comunes en todos
- [ ] Select "Goza de remuneración" default "Si"
- [ ] Carga de documento respaldo
- [ ] Campos específicos por subtipo
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Licencias sin Sueldo (4 subtipos)
- [ ] Asumir cargo elección popular - Formulario completo
- [ ] Asuntos Personales - Formulario completo
- [ ] Asumir cargo libre nombramiento - Formulario + archivo* obligatorio
- [ ] Estudiar - Formulario completo
- [ ] Campos comunes en todos
- [ ] Campo "Goza de remuneración" readonly "No"
- [ ] Carga de documento aprobación
- [ ] Campos específicos por subtipo
- [ ] Guardado correcto en BD
- [ ] Edición funcional

### ✅ Licencias Especiales (4 subtipos)
- [ ] Enfermedad Profesional - Formulario completo + campos profesionales
- [ ] Riesgos Profesionales - Formulario completo + campos profesionales
- [ ] Enfermedad/Incapacidad >15 días - Formulario completo + días reposo
- [ ] Gravidez - Formulario completo + select prenatal/postnatal
- [ ] Campos comunes en todos
- [ ] Campo diagnóstico* obligatorio
- [ ] Archivo dictamen médico* obligatorio
- [ ] Campos específicos por subtipo
- [ ] Guardado correcto en BD
- [ ] Edición funcional

---

## 🔍 Pruebas de Regresión

Después de cualquier cambio en el código, verificar:

1. **Carga de Subtipos por AJAX**:
   - Seleccionar tipo → Subtipos se cargan correctamente
   - Cambiar tipo → Subtipos se actualizan
   - Respuesta JSON correcta

2. **Carga de Formulario Dinámico por AJAX**:
   - Seleccionar tipo y subtipo → Formulario se renderiza
   - Cambiar subtipo → Formulario se actualiza
   - HTML renderizado correctamente

3. **Número de Documento Automático**:
   - Endpoint `/panel/employee-files/next-document-number` responde
   - Formato correcto del número
   - Incremento secuencial

4. **Subida de Archivos**:
   - Archivos se guardan en ruta correcta
   - Nombres se sanitizan
   - Tamaños se validan

5. **Listado de Expedientes**:
   - Tabla carga correctamente
   - Filtros funcionan
   - Acciones (ver, editar, eliminar) funcionan

---

## 📊 Reporte de Pruebas

### Plantilla de Reporte

```markdown
**Fecha**: __________
**Probador**: __________
**Versión**: __________

#### Resumen
- Total casos probados: ___/68
- Casos exitosos: ___
- Casos fallidos: ___
- Bloqueadores: ___

#### Casos Fallidos
| Tipo | Subtipo | Error | Severidad | Notas |
|------|---------|-------|-----------|-------|
|      |         |       |           |       |

#### Observaciones
-
-

#### Firma
__________
```

---

## 🚀 Recomendaciones

1. **Orden de Pruebas**: Comenzar con tipos simples (2-4 subtipos) antes de probar los complejos (13 subtipos)

2. **Empleado de Prueba**: Usar employee_id=2 para todas las pruebas

3. **Datos de Prueba**: Preparar archivos de prueba de diferentes tipos y tamaños

4. **Navegadores**: Probar en Chrome, Firefox, Edge

5. **Responsive**: Probar en resolución 1024px y superior

6. **Automatización**: Considerar automatizar con Selenium/Puppeteer para las 68 combinaciones

---

## 📚 Referencias

- **Controlador**: `app/Controllers/EmployeeFileController.php:513` (método `getDynamicFieldsConfig()`)
- **Migración**: `database/migrations/tenant/2025_12_29_create_employee_files_catalogs.sql`
- **Changelog**: `documentation/changelog/v3.5.16.md`
- **Ruta**: `/panel/employee-files/create/{employee_id}`

---

**Fin del Documento**
