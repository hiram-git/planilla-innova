# 🤖 CLAUDE MEMORY - Sistema de Planillas MVC

## 📝 **Estado Actual - V3.2.1 Motor Fórmulas Optimizado**
- **Fecha**: 18 de Septiembre, 2025
- **Estado**: ✅ **SISTEMA EMPRESARIAL 100% + MOTOR FÓRMULAS OPTIMIZADO**
- **Versión**: 3.2.1 - Core + Acumulados + Reportes + Fórmulas Avanzadas

## 🎯 **Sistema**
Plataforma empresarial de planillas con legislación panameña, acumulados automáticos XIII Mes, reportes PDF profesionales con firmas, y estructura organizacional completa.

## ✅ **Completado (100%)**
- ✅ **Core MVC**: Router + Database + Middleware + CSRF + Roles
- ✅ **Planillas**: Procesamiento + PDF + Estados + Acumulados automáticos
- ✅ **XIII Mes Panamá**: (Salario Anual ÷ 3) - Descuentos legislación
- ✅ **Reportes PDF**: Planillas + Comprobantes + Logos empresariales + Firmas
- ✅ **Módulo Organizacional**: CRUD completo + jerarquías + integración empleados
- ✅ **Sistema Logos**: Dropzone.js + triple logo + reportes PDF
- ✅ **JavaScript Modular**: BaseModule + ES6 + JavaScriptHelper
- ✅ **Motor Fórmulas**: INIPERIODO/FINPERIODO dinámico + tipo_acumulado

## 📄 **Reportes PDF Empresariales**
- **Planillas**: Layout horizontal + logos empresariales + firmas profesionales
- **Comprobantes**: Individuales por empleado + conceptos detallados + logos
- **Triple Logo System**: Logo principal + logo izquierdo reportes + logo derecho reportes
- **Firmas**: Configurables desde BD companies (4 niveles de firma)
- **PDFReportController**: Controlador específico para generación reportes

## 🏢 **Módulo Organizacional Completo**
- **OrganizationalController**: CRUD completo con create/edit/delete
- **Vistas Completas**: Index con organigrama visual + formularios create/edit
- **JavaScript Modular**: organizational/index.js, create.js, edit.js
- **Jerarquías Dinámicas**: Paths automáticos + validación ciclos
- **Integración Empleados**: Campo organigrama_id + foreign key + formularios

## 🎨 **Sistema Logos Empresariales**
- **Dropzone.js Integration**: Upload arrastrando archivos + preview dinámico
- **company/logos.js**: Módulo completo gestión logos con CSRF
- **Dynamic URLs**: Detección automática paths para upload/delete/preview
- **Security**: CSRF tokens + validaciones + preview en tiempo real

## 🔧 **Stack Tecnológico**
- **Backend**: PHP 8.3 + MVC + MySQL (planilla_innova)
- **Frontend**: AdminLTE + Bootstrap 4 + JavaScript ES6 modular
- **Reportes**: TCPDF + diseño empresarial profesional
- **Estado**: Producción estable + arquitectura escalable

## 🔑 **Próximas Fases**
1. **🏢 MULTITENANCY**: Wizard empresas + BD automática
2. **💰 ISR PANAMÁ**: Calculadora impuesto renta + retenciones

---

**✅ SISTEMA EMPRESARIAL COMPLETO + MOTOR FÓRMULAS OPTIMIZADO**

## 🧮 **Motor Fórmulas Conceptos - V3.2.1**
- **Fechas Dinámicas**: INIPERIODO/FINPERIODO con fechas reales planilla_cabecera
- **Función ACUMULADOS**: Manejo correcto conceptos múltiples + preservación quoted strings
- **Categorización**: Campo tipo_acumulado para XIII_MES, VACACIONES, etc.
- **Integración**: PayrollController pasa fechas automáticamente al calculador

# important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
NEVER proactively create documentation files (*.md) or README files. Only create documentation files if explicitly requested by the User.

## 📁 **Estructura de Documentación**
- **CLAUDE.md**: Memoria principal del proyecto (raíz)
- **documentation/**: Directorio para archivos de documentación del proyecto
  - **ROADMAP.md**: Hoja de ruta y planificación  
  - **CHANGELOG.md**: Historial de cambios y versiones
  - **TODO.md**: Lista de tareas pendientes
- **docs/**: Directorio de AdminLTE (NO MODIFICAR)

IMPORTANTE: Todos los archivos de documentación del proyecto deben guardarse en `/documentation` para no confundirlos con `/docs` que pertenece a la plantilla AdminLTE.

      
      IMPORTANT: this context may or may not be relevant to your tasks. You should not respond to this context unless it is highly relevant to your task.