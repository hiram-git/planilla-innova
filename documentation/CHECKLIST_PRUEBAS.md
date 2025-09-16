# 📋 CHECKLIST DE PRUEBAS - SISTEMA DE PLANILLAS MVC
## Versión: 2.0 | Fecha: Agosto 2025

> **Guía completa para testing de todos los módulos del sistema**  
> Compatible con: Monday.com, Todoist, Notion, Asana, Trello

---

## 🎯 **CONFIGURACIÓN INICIAL**

### **Prerequisitos del Entorno**
- [ ] Servidor web Apache/Nginx funcionando
- [ ] PHP 8.1+ instalado y configurado
- [ ] MySQL/MariaDB 8.0+ funcionando
- [ ] Base de datos instalada desde install.sql
- [ ] Archivo .env configurado correctamente
- [ ] Permisos de carpetas configurados (storage, uploads)
- [ ] SSL/HTTPS configurado (producción)

### **Datos de Prueba Iniciales**
- [ ] Usuario admin creado (admin/password)
- [ ] Empresa configurada con datos reales
- [ ] Al menos 5 empleados de prueba creados
- [ ] Horarios de trabajo configurados
- [ ] Estructura organizacional básica (cargos, partidas, funciones)

---

## 🔐 **MÓDULO: AUTENTICACIÓN Y SEGURIDAD**

### **Login y Sesiones**
- [ ] Acceder con credenciales correctas
- [ ] Intentar acceso con credenciales incorrectas
- [ ] Verificar redirección automática si no autenticado
- [ ] Probar logout y limpiar sesión
- [ ] Verificar timeout de sesión automático
- [ ] Probar acceso directo a URLs protegidas

### **Control de Acceso**
- [ ] Crear usuario con rol limitado
- [ ] Verificar permisos por módulo según rol
- [ ] Probar acceso denegado a funciones sin permiso
- [ ] Verificar botones CRUD habilitados/deshabilitados por rol
- [ ] Probar bypass de permisos (intentos de hack)

### **Seguridad**
- [ ] Verificar tokens CSRF en formularios
- [ ] Probar inyección SQL en campos de entrada
- [ ] Verificar sanitización de datos de entrada
- [ ] Probar XSS en campos de texto
- [ ] Verificar encriptación de contraseñas en BD

---

## 👥 **MÓDULO: GESTIÓN DE USUARIOS**

### **CRUD Usuarios**
- [ ] Crear nuevo usuario con todos los campos
- [ ] Listar usuarios con paginación
- [ ] Buscar usuarios por nombre/email
- [ ] Editar información de usuario existente
- [ ] Cambiar contraseña de usuario
- [ ] Desactivar/activar usuario
- [ ] Eliminar usuario (soft delete)

### **Roles y Permisos**
- [ ] Crear nuevo rol personalizado
- [ ] Asignar permisos específicos a rol
- [ ] Modificar permisos de rol existente
- [ ] Asignar rol a usuario
- [ ] Cambiar rol de usuario
- [ ] Eliminar rol no utilizado

---

## 🏢 **MÓDULO: CONFIGURACIÓN EMPRESARIAL**

### **Datos de Empresa**
- [ ] Actualizar información básica (nombre, NIT, dirección)
- [ ] Subir y cambiar logo empresarial
- [ ] Configurar moneda y símbolo
- [ ] Actualizar datos de contacto
- [ ] Verificar que cambios se reflejen en reportes

### **Estructura Organizacional**
- [ ] Crear departamentos
- [ ] Crear cargos con códigos
- [ ] Crear partidas presupuestarias
- [ ] Crear funciones de puesto
- [ ] Crear horarios de trabajo personalizados
- [ ] Verificar relaciones entre elementos

---

## 👤 **MÓDULO: GESTIÓN DE EMPLEADOS**

### **CRUD Empleados**
- [ ] Crear empleado con datos completos
- [ ] Subir foto de empleado
- [ ] Asignar posición, cargo, departamento
- [ ] Asignar horario de trabajo
- [ ] Configurar fecha de ingreso
- [ ] Listar empleados con filtros
- [ ] Buscar empleado por ID/nombre
- [ ] Editar datos de empleado
- [ ] Desactivar empleado
- [ ] Reactivar empleado inactivo

### **Validaciones**
- [ ] Validar formato de employee_id único
- [ ] Validar formato de DPI/documento
- [ ] Validar fechas (nacimiento no futura)
- [ ] Validar campos obligatorios
- [ ] Validar formato de email/teléfono

### **Listados y Reportes**
- [ ] DataTable con paginación server-side
- [ ] Ordenamiento por columnas
- [ ] Filtros por posición, estado, departamento
- [ ] Exportar lista de empleados a Excel/PDF
- [ ] Ver organigrama visual (si implementado)

---

## 💰 **MÓDULO: CONCEPTOS DE NÓMINA**

### **CRUD Conceptos**
- [ ] Crear concepto de ingreso con fórmula
- [ ] Crear concepto de deducción con fórmula
- [ ] Configurar concepto con valor fijo
- [ ] Configurar concepto con fórmula variable
- [ ] Editar concepto existente
- [ ] Desactivar/activar concepto
- [ ] Eliminar concepto no utilizado

### **Fórmulas Avanzadas**
- [ ] Probar fórmula básica: SALARIO * 0.10
- [ ] Probar fórmula con condicionales: (SALARIO > 5000) ? 100 : 50
- [ ] Probar función ACREEDOR(EMPLEADO, id)
- [ ] Probar variables: SALARIO, HORAS, DIAS, etc.
- [ ] Validar sintaxis de fórmula en tiempo real
- [ ] Probar modal de prueba de fórmulas

### **Configuración de Conceptos**
- [ ] Configurar tipos de planilla aplicables
- [ ] Configurar frecuencias aplicables
- [ ] Configurar situaciones laborales aplicables
- [ ] Configurar categorías de reporte
- [ ] Configurar orden de cálculo
- [ ] Probar checkboxes: imprime_detalles, prorratea, etc.

---

## 🏦 **MÓDULO: ACREEDORES Y DEDUCCIONES**

### **Gestión de Acreedores**
- [ ] Crear acreedor (banco, cooperativa, etc.)
- [ ] Configurar tipo de acreedor
- [ ] Editar información de acreedor
- [ ] Listar acreedores activos
- [ ] Desactivar acreedor
- [ ] Eliminar acreedor sin deducciones

### **Deducciones por Empleado**
- [ ] Asignar deducción a empleado específico
- [ ] Configurar monto de deducción
- [ ] Establecer fechas de inicio/fin
- [ ] Listar deducciones por empleado
- [ ] Editar monto de deducción
- [ ] Desactivar deducción temporalmente
- [ ] Eliminar deducción finalizada

### **Asignación Masiva**
- [ ] Seleccionar múltiples empleados
- [ ] Asignar mismo acreedor a grupo
- [ ] Configurar montos diferentes por empleado
- [ ] Aplicar cambios masivos
- [ ] Verificar asignaciones correctas

---

## 📊 **MÓDULO: PLANILLAS DE NÓMINA**

### **Creación de Planillas**
- [ ] Crear planilla nueva con fechas período
- [ ] Seleccionar tipo de planilla
- [ ] Configurar frecuencia y situación laboral
- [ ] Agregar empleados manualmente
- [ ] Agregar empleados masivamente por filtro
- [ ] Verificar empleados incluidos correctamente

### **Procesamiento de Planillas**
- [ ] Procesar planilla completa
- [ ] Verificar cálculos de conceptos automáticos
- [ ] Verificar aplicación de deducciones
- [ ] Revisar totales por empleado
- [ ] Revisar totales generales de planilla
- [ ] Regenerar empleado individual
- [ ] Regenerar planilla completa

### **Estados de Planilla**
- [ ] Cambiar de NUEVA a PROCESANDO
- [ ] Cambiar de PROCESANDO a PROCESADA
- [ ] Cerrar planilla (CERRADA)
- [ ] Anular planilla si es necesario
- [ ] Verificar restricciones por estado

### **Validación de Cálculos**
- [ ] Verificar fórmula SALARIO_BASE = posición.sueldo
- [ ] Verificar cálculo IGSS_LABORAL = SALARIO * 0.0483
- [ ] Verificar función ACREEDOR con empleado específico
- [ ] Verificar conceptos condicionales
- [ ] Verificar montos cero permitidos/no permitidos

---

## 📈 **MÓDULO: REPORTES Y ESTADÍSTICAS**

### **Dashboard Principal**
- [ ] Verificar estadísticas de empleados
- [ ] Verificar gráficos de asistencia (si implementado)
- [ ] Verificar métricas de planillas recientes
- [ ] Verificar datos en tiempo real
- [ ] Verificar responsive en móvil/tablet

### **Reportes de Planillas**
- [ ] Generar reporte PDF de planilla
- [ ] Verificar datos correctos en PDF
- [ ] Verificar formato y diseño
- [ ] Exportar detalle de planilla a Excel
- [ ] Generar comprobantes individuales
- [ ] Imprimir múltiples comprobantes

### **Reportes de Empleados**
- [ ] Listar empleados activos/inactivos
- [ ] Reporte por departamento
- [ ] Reporte por rango salarial
- [ ] Exportar datos de empleados
- [ ] Generar estadísticas de RRHH

---

## 🕒 **MÓDULO: CONTROL DE TIEMPO (Opcional)**

### **Registro de Asistencia**
- [ ] Marcar entrada de empleado
- [ ] Marcar salida de empleado
- [ ] Calcular horas trabajadas automáticamente
- [ ] Registrar horas extra
- [ ] Aplicar descuentos por tardanzas
- [ ] Generar reportes de asistencia

### **Horarios y Turnos**
- [ ] Configurar horarios flexibles
- [ ] Asignar turnos especiales
- [ ] Calcular diferencias de horario
- [ ] Reportes de puntualidad
- [ ] Exportar registros de asistencia

---

## 🔍 **MÓDULO: BÚSQUEDAS Y FILTROS**

### **Sistema de Búsqueda**
- [ ] Búsqueda global en empleados
- [ ] Filtros combinados (posición + estado)
- [ ] Búsqueda por rango de fechas
- [ ] Autocompletado en campos de búsqueda
- [ ] Limpiar filtros aplicados
- [ ] Recordar filtros por sesión

### **DataTables Avanzadas**
- [ ] Paginación server-side
- [ ] Ordenamiento múltiple
- [ ] Búsqueda individual por columna
- [ ] Exportar resultados filtrados
- [ ] Selección múltiple de registros

---

## 🔧 **MÓDULO: CONFIGURACIÓN DEL SISTEMA**

### **Configuraciones Generales**
- [ ] Modificar configuraciones de aplicación
- [ ] Actualizar URLs base del sistema
- [ ] Configurar límites de paginación
- [ ] Configurar timeouts de sesión
- [ ] Habilitar/deshabilitar módulos

### **Mantenimiento**
- [ ] Limpiar logs antiguos del sistema
- [ ] Ejecutar limpieza de archivos temporales
- [ ] Verificar espacio en disco
- [ ] Realizar respaldo de base de datos
- [ ] Restaurar respaldo si es necesario

---

## 📱 **PRUEBAS DE USABILIDAD**

### **Responsive Design**
- [ ] Probar en pantalla desktop (1920x1080)
- [ ] Probar en tablet (768x1024)
- [ ] Probar en móvil (375x667)
- [ ] Verificar menús contraíbles en móvil
- [ ] Probar tablas responsivas
- [ ] Verificar formularios en pantallas pequeñas

### **Navegación**
- [ ] Probar menú lateral (sidebar)
- [ ] Verificar breadcrumbs
- [ ] Probar botones "Volver"
- [ ] Verificar enlaces internos
- [ ] Probar navegación con teclado
- [ ] Verificar shortcuts de teclado

### **Experiencia de Usuario**
- [ ] Verificar mensajes de éxito/error claros
- [ ] Probar tooltips informativos
- [ ] Verificar loading spinners
- [ ] Probar modales de confirmación
- [ ] Verificar campos de ayuda contextual

---

## ⚡ **PRUEBAS DE RENDIMIENTO**

### **Carga de Datos**
- [ ] Probar con 100+ empleados
- [ ] Probar planilla con 500+ empleados
- [ ] Verificar tiempo de carga < 3 segundos
- [ ] Probar paginación con grandes datasets
- [ ] Verificar memoria utilizada por PHP

### **Concurrencia**
- [ ] Múltiples usuarios simultáneos (5+)
- [ ] Procesamiento simultáneo de planillas
- [ ] Acceso concurrente a mismos datos
- [ ] Verificar locks de base de datos
- [ ] Probar bajo carga sostenida

### **Base de Datos**
- [ ] Verificar queries optimizadas (< 100ms)
- [ ] Probar con 10,000+ registros históricos
- [ ] Verificar índices funcionando
- [ ] Monitorear uso de CPU/RAM
- [ ] Probar backups/restore

---

## 🐛 **PRUEBAS DE ERRORES**

### **Manejo de Errores**
- [ ] Desconectar base de datos y probar
- [ ] Llenar disco y probar uploads
- [ ] Corromper archivo de configuración
- [ ] Probar con datos inválidos en formularios
- [ ] Verificar páginas 404 personalizadas
- [ ] Probar timeouts de red

### **Validaciones Edge Cases**
- [ ] Caracteres especiales en nombres (ñ, á, etc.)
- [ ] Fechas extremas (1900, 2100)
- [ ] Números muy grandes/muy pequeños
- [ ] Campos vacíos obligatorios
- [ ] Duplicados no permitidos
- [ ] Límites de longitud de campos

### **Recuperación de Errores**
- [ ] Reintento automático en fallos de red
- [ ] Rollback en transacciones fallidas
- [ ] Logs de errores completos
- [ ] Notificaciones de errores críticos
- [ ] Recuperación de sesión perdida

---

## 🔐 **PRUEBAS DE SEGURIDAD**

### **Vulnerabilidades Comunes**
- [ ] SQL Injection en todos los formularios
- [ ] XSS (Cross-Site Scripting)
- [ ] CSRF (Cross-Site Request Forgery)
- [ ] Path Traversal (../../etc/passwd)
- [ ] File Upload vulnerabilities
- [ ] Session Hijacking

### **Autenticación y Autorización**
- [ ] Brute force en login (rate limiting)
- [ ] Escalación de privilegios
- [ ] Bypass de autenticación
- [ ] Token manipulation
- [ ] Privilege escalation
- [ ] Session fixation

### **Configuración de Servidor**
- [ ] Headers de seguridad (HSTS, CSP, etc.)
- [ ] Configuración HTTPS correcta
- [ ] Archivos sensibles no accesibles
- [ ] Logs no expuestos públicamente
- [ ] Error messages sin información sensible

---

## 📊 **PRUEBAS DE INTEGRACIÓN**

### **APIs y Servicios Externos**
- [ ] Integración con sistemas de RRHH (si aplica)
- [ ] Conexión con bancos (si aplica)
- [ ] Servicios de email/notificaciones
- [ ] Integración con contabilidad (si aplica)
- [ ] APIs de terceros funcionando

### **Importación/Exportación**
- [ ] Importar empleados desde Excel
- [ ] Exportar planillas a diferentes formatos
- [ ] Migración de datos desde sistema anterior
- [ ] Sincronización bidireccional
- [ ] Validación de formatos de datos

---

## 🚀 **PRUEBAS DE DESPLIEGUE**

### **Entorno de Producción**
- [ ] Deploy en servidor de producción
- [ ] Configurar SSL/HTTPS
- [ ] Configurar copias de seguridad automáticas
- [ ] Configurar monitoreo del servidor
- [ ] Verificar logs de aplicación/servidor

### **Migración de Datos**
- [ ] Migrar datos desde sistema anterior
- [ ] Verificar integridad de datos migrados
- [ ] Probar rollback si es necesario
- [ ] Validar todos los cálculos post-migración
- [ ] Capacitar usuarios en nuevo sistema

### **Go Live**
- [ ] Comunicar a usuarios finales
- [ ] Monitorear sistema primeras 48 horas
- [ ] Soporte inmediato disponible
- [ ] Documentación accesible para usuarios
- [ ] Plan de contingencia listo

---

## 📋 **CRITERIOS DE ACEPTACIÓN**

### **Funcionalidad**
- [ ] ✅ Todos los módulos principales funcionan
- [ ] ✅ Cálculos de planillas son exactos
- [ ] ✅ Reportes generan datos correctos
- [ ] ✅ Sistema de permisos funciona correctamente
- [ ] ✅ No hay errores críticos en logs

### **Performance**
- [ ] ✅ Páginas cargan en < 3 segundos
- [ ] ✅ Procesamiento planilla < 30 segundos
- [ ] ✅ Sistema estable con 10+ usuarios concurrentes
- [ ] ✅ Queries BD optimizadas (< 100ms promedio)
- [ ] ✅ Memoria PHP estable (< 512MB por proceso)

### **Usabilidad**
- [ ] ✅ Interfaz intuitiva y fácil de usar
- [ ] ✅ Responsive en dispositivos móviles
- [ ] ✅ Mensajes de error/éxito claros
- [ ] ✅ Navegación lógica y consistente
- [ ] ✅ Documentación completa disponible

### **Seguridad**
- [ ] ✅ Autenticación y autorización robustas
- [ ] ✅ Datos sensibles encriptados
- [ ] ✅ Logs de actividad funcionando
- [ ] ✅ No vulnerabilidades críticas encontradas
- [ ] ✅ Copias de seguridad automáticas funcionando

---

## 🎯 **CHECKLIST DE ENTREGA FINAL**

### **Documentación Técnica**
- [ ] Manual de usuario actualizado
- [ ] Documentación de APIs (si aplica)
- [ ] Guía de instalación y configuración
- [ ] Documentación de base de datos
- [ ] Plan de mantenimiento y soporte

### **Capacitación**
- [ ] Sesiones de capacitación realizadas
- [ ] Videos tutoriales disponibles
- [ ] Usuarios clave entrenados
- [ ] Documentación de procesos actualizada
- [ ] Canal de soporte establecido

### **Transición**
- [ ] Sistema anterior respaldado
- [ ] Datos migrados y validados
- [ ] Usuarios migrados exitosamente
- [ ] Procesos de negocio actualizados
- [ ] Go-live exitoso sin incidentes críticos

---

## 📞 **CONTACTO Y SOPORTE**

**En caso de encontrar problemas durante las pruebas:**

1. **Documentar el problema**:
   - Pasos para reproducir
   - Mensaje de error completo
   - Browser/dispositivo utilizado
   - Usuario/rol que experimenta el problema

2. **Clasificar la severidad**:
   - 🔴 **Crítico**: Sistema no funciona, pérdida de datos
   - 🟠 **Alto**: Funcionalidad importante afectada
   - 🟡 **Medio**: Problema menor pero molesto
   - 🟢 **Bajo**: Mejora cosmética o sugerencia

3. **Canales de reporte**:
   - Issues en GitHub (si aplica)
   - Email al equipo de desarrollo
   - Sistema de tickets interno
   - Comunicación directa urgente

---

**✅ SISTEMA APROBADO PARA PRODUCCIÓN CUANDO TODOS LOS ITEMS ESTÉN MARCADOS**

*Documento generado para Sistema de Planillas MVC v2.0*  
*Compatible con Monday.com, Todoist, Notion, Asana, Trello y otros gestores de tareas*