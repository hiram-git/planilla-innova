/**
 * Módulo: Reportes de Exportación
 * Funcionalidades: Animaciones hover, botones de exportación con loading, tooltips
 */

$(document).ready(function() {
    // Configuración del módulo
    const ReportsExportsModule = {
        // Inicializar módulo
        init() {
            this.initCardAnimations();
            this.initExportButtons();
            this.initTooltips();
        },

        // Animaciones para las tarjetas de exportación
        initCardAnimations() {
            $(".export-card").hover(
                function() {
                    $(this).addClass("shadow-lg").css("transform", "translateY(-5px)");
                },
                function() {
                    $(this).removeClass("shadow-lg").css("transform", "translateY(0)");
                }
            );
        },

        // Manejo de botones de exportación con loading
        initExportButtons() {
            $(".export-btn").click(function() {
                const $btn = $(this);
                const originalText = $btn.html();
                
                // Obtener tipo de exportación del botón
                let exportType = "datos";
                if (originalText.includes("Empleados")) {
                    exportType = "empleados";
                } else if (originalText.includes("Acreedores")) {
                    exportType = "acreedores";
                } else if (originalText.includes("Conceptos")) {
                    exportType = "conceptos";
                }
                
                // Mostrar loading
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Exportando...');
                $btn.addClass("disabled");
                
                // Toast de información (si toastr está disponible)
                if (typeof toastr !== 'undefined') {
                    toastr.info(`Iniciando exportación de ${exportType}`, "Exportando", {
                        timeOut: 3000
                    });
                }
                
                // Restaurar botón después de la exportación
                setTimeout(function() {
                    $btn.html(originalText);
                    $btn.removeClass("disabled");
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.success("Exportación completada", "Éxito");
                    }
                }, 2000);
                
                return true; // Permitir que el enlace funcione
            });
        },

        // Inicializar tooltips
        initTooltips() {
            $("[title]").tooltip();
        }
    };

    // Inicializar módulo
    ReportsExportsModule.init();
});