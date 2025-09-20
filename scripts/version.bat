@echo off
REM Script de Windows para facilitar la actualización de versión
REM Uso: version.bat [roadmap|claude|info|check]

cd /d "%~dp0.."

if "%1"=="roadmap" (
    echo Actualizando version desde ROADMAP.md...
    php scripts/update_version.php --from-roadmap
    goto :end
)

if "%1"=="claude" (
    echo Actualizando version desde CLAUDE.md...
    php scripts/update_version.php --from-claude
    goto :end
)

if "%1"=="info" (
    php scripts/update_version.php --info
    goto :end
)

if "%1"=="check" (
    php scripts/update_version.php --check
    goto :end
)

if "%1"=="help" (
    goto :help
)

if "%1"=="" (
    goto :help
)

echo Opcion no reconocida: %1
goto :help

:help
echo.
echo ================================================
echo    Script de Actualizacion de Version
echo ================================================
echo.
echo Uso: version.bat [opcion]
echo.
echo Opciones:
echo   roadmap   - Actualizar desde ROADMAP.md
echo   claude    - Actualizar desde CLAUDE.md
echo   info      - Mostrar informacion de version
echo   check     - Verificar actualizaciones
echo   help      - Mostrar esta ayuda
echo.
echo Ejemplos:
echo   version.bat roadmap
echo   version.bat info
echo.

:end
pause