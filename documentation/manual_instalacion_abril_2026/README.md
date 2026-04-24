# Manual de Instalación - INNOVA Planillas v1.0

Documento técnico para instalación del Sistema de Planillas MVC en
Ubuntu 22.04 LTS + Nginx + PHP 8.3 + MySQL.

## Estructura

```
manual_instalacion_abril_2026/
├── metadata.yaml           Variables del documento
├── build.js                Script de build (Node)
├── chapters/               Contenido principal
├── appendices/             Apéndice: Flujo multi-tenant
├── templates/              Plantilla LaTeX eisvogel (compartida)
├── styles/                 CSS print-friendly
├── filters/                Filtros Lua (badges, crossref)
└── dist/                   PDF generado
```

## Compilar

Desde esta carpeta:

```powershell
node build.js pdf        # Genera PDF principal
node build.js clean      # Borra dist/
```

El PDF queda en `dist/manual_instalacion_v1.0.pdf`.

## Requisitos

- Pandoc >= 3.0
- TeX Live o MiKTeX con xelatex
- Node.js (sólo para build.js)

## Fuente del contenido

El cuerpo principal proviene de `documentation/INSTALL_UBUNTU_NGINX.md`.
El apéndice de flujo multi-tenant se redactó específicamente para este manual.
