@echo off
REM ============================================
REM Script: test_pipeline.bat
REM Descripción: Probar manualmente el pipeline de asistencias
REM Versión: 1.0
REM Fecha: 15-Dic-2025
REM ============================================

echo.
echo ========================================================
echo   TEST MANUAL - Pipeline de Asistencias
echo   Sistema de Planillas Innova v3.5.20
echo ========================================================
echo.

REM Configuración
set PHP_PATH=C:\laragon60\bin\php\php-8.3.11-Win32-vs16-x64\php.exe
set PROJECT_PATH=C:\laragon60\www\planilla-innova

REM Verificar que PHP existe
if not exist "%PHP_PATH%" (
    echo [ERROR] No se encontro PHP en: %PHP_PATH%
    echo Por favor, edita este archivo y ajusta la ruta de PHP.
    pause
    exit /b 1
)

echo [INFO] PHP encontrado: %PHP_PATH%
echo [INFO] Proyecto: %PROJECT_PATH%
echo.

REM Cambiar al directorio del proyecto
cd /d "%PROJECT_PATH%"

echo ========================================================
echo   PASO 1: Sincronizar Marcaciones desde API
echo ========================================================
echo.

"%PHP_PATH%" scripts\cron\sync_attendance.php

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] La sincronizacion fallo con codigo: %ERRORLEVEL%
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo [OK] Sincronizacion completada exitosamente
echo.

timeout /t 3 /nobreak >nul

echo ========================================================
echo   PASO 2: Procesar Pipeline Completo
echo ========================================================
echo.

"%PHP_PATH%" scripts\cron\process_attendance_pipeline.php

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] El procesamiento del pipeline fallo con codigo: %ERRORLEVEL%
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo ========================================================
echo   TEST COMPLETADO EXITOSAMENTE
echo ========================================================
echo.
echo Revisa los logs en: %PROJECT_PATH%\storage\logs
echo.

pause
