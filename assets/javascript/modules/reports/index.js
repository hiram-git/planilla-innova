/**
 * Módulo JavaScript: Reports Index
 * Maneja la funcionalidad de la vista principal de reportes
 */

// Namespace para el módulo
window.ReportsModule = (function() {
    'use strict';
    
    // Variables privadas del módulo
    let initialized = false;
    let tooltipsInitialized = false;
    
    // Configuración
    const config = {
        selectors: {
            cards: '.card',
            pdfButtons: 'a[href*="planilla-pdf"]',
            reportButtons: '.btn-report-action',
            reportGroup: '.btn-group-reports'
        },
        animations: {
            hoverDuration: 300,
            buttonRestoreDelay: 3000,
            toastTimeout: 5000
        },
        classes: {
            disabled: 'disabled',
            shadowLg: 'shadow-lg',
            shadow: 'shadow'
        }
    };
    
    // Funciones privadas
    function initializeCardAnimations() {
        $(config.selectors.cards).hover(
            function() {
                $(this).addClass(config.classes.shadowLg).removeClass(config.classes.shadow);
            },
            function() {
                $(this).removeClass(config.classes.shadowLg).addClass(config.classes.shadow);
            }
        );
        
        console.log('Card hover animations initialized');
    }
    
    function initializePDFButtons() {
        $(config.selectors.pdfButtons).off('click.reports').on('click.reports', function(e) {
            const planillaName = $(this).closest('tr').find('strong').first().text();
            const $btn = $(this);
            
            // Prevenir múltiples clics
            if ($btn.hasClass(config.classes.disabled)) {
                e.preventDefault();
                return false;
            }
            
            handlePDFGeneration($btn, planillaName);
            
            return true; // Permitir que el enlace funcione
        });
        
        console.log('PDF generation buttons initialized');
    }
    
    function handlePDFGeneration($btn, planillaName) {
        // Guardar estado original
        const originalContent = $btn.html();
        const originalClasses = $btn.attr('class');
        
        // Mostrar estado de carga
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Generando...');
        $btn.addClass(config.classes.disabled);
        
        // Mostrar notificación
        showProcessingToast(planillaName);
        
        // Restaurar botón después del delay configurado
        setTimeout(function() {
            if ($btn.length) { // Verificar que el elemento aún existe
                $btn.html(originalContent);
                $btn.attr('class', originalClasses);
            }
        }, config.animations.buttonRestoreDelay);
        
        // Analytics/Logging (opcional)
        logReportGeneration('PDF', planillaName);
    }
    
    function showProcessingToast(planillaName) {
        if (typeof toastr !== 'undefined') {
            toastr.info(
                `Generando reporte PDF para: ${planillaName}`, 
                'Procesando',
                {
                    timeOut: config.animations.toastTimeout,
                    progressBar: true,
                    closeButton: true
                }
            );
        } else {
            console.log(`Generating PDF report for: ${planillaName}`);
        }
    }
    
    function logReportGeneration(type, planillaName) {
        // Log para debugging o analytics
        console.log(`Report generated - Type: ${type}, Planilla: ${planillaName}, Time: ${new Date().toISOString()}`);
        
        // Aquí se podría agregar tracking de analytics si es necesario
        // gtag('event', 'report_generated', { type: type, planilla: planillaName });
    }
    
    function initializeTooltips() {
        if (tooltipsInitialized) return;
        
        // Inicializar tooltips con configuración optimizada
        $('[data-toggle="tooltip"]').tooltip({
            trigger: 'hover focus',
            delay: { show: 500, hide: 100 },
            placement: 'top',
            template: '<div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
        });
        
        tooltipsInitialized = true;
        console.log('Tooltips initialized');
    }
    
    function initializeReportButtons() {
        // Agregar efecto hover mejorado para botones de reporte
        $(config.selectors.reportButtons).hover(
            function() {
                $(this).addClass('btn-hover-effect');
            },
            function() {
                $(this).removeClass('btn-hover-effect');
            }
        );
        
        // Manejar clics en botones de reporte para feedback visual
        $(config.selectors.reportButtons).off('click.reports-feedback').on('click.reports-feedback', function() {
            const $btn = $(this);
            const reportType = getReportType($btn);
            
            // Efecto visual rápido
            $btn.addClass('btn-clicked');
            setTimeout(() => $btn.removeClass('btn-clicked'), 200);
            
            // Log del tipo de reporte
            console.log(`Report button clicked: ${reportType}`);
        });
        
        console.log('Report buttons enhanced');
    }
    
    function getReportType($btn) {
        const href = $btn.attr('href') || '';
        
        if (href.includes('planilla-pdf')) return 'PDF Planilla';
        if (href.includes('comprobantes')) return 'Comprobantes';
        if (href.includes('acreedores')) return 'Acreedores';
        if (href.includes('exports')) return 'Exportaciones';
        
        return 'Unknown';
    }
    
    function handleResponsiveDesign() {
        // Optimizar para dispositivos móviles
        function checkMobileView() {
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                // Ajustar tooltips para móviles
                $('[data-toggle="tooltip"]').tooltip('dispose');
                // En móviles, usar click en lugar de hover para tooltips
                $('[data-toggle="tooltip"]').tooltip({
                    trigger: 'click',
                    placement: 'bottom'
                });
            } else {
                // Restaurar tooltips normales para desktop
                if (tooltipsInitialized) {
                    $('[data-toggle="tooltip"]').tooltip('dispose');
                    initializeTooltips();
                }
            }
        }
        
        // Verificar al cargar y al redimensionar
        checkMobileView();
        $(window).on('resize', debounce(checkMobileView, 250));
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    function addCustomStyles() {
        // Agregar estilos dinámicos para efectos que no están en CSS
        const styles = `
        <style id="reports-dynamic-styles">
        .btn-hover-effect {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn-clicked {
            transform: scale(0.95);
            transition: transform 0.1s ease;
        }
        
        .card-loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        @media (max-width: 768px) {
            .tooltip {
                font-size: 0.9rem;
            }
        }
        </style>
        `;
        
        if (!$('#reports-dynamic-styles').length) {
            $('head').append(styles);
        }
    }
    
    // API pública del módulo
    return {
        // Inicialización principal
        init: function(options = {}) {
            if (initialized) {
                console.warn('ReportsModule already initialized');
                return;
            }
            
            // Merge configuración desde PHP si está disponible
            if (typeof window.REPORTS_CONFIG !== 'undefined') {
                Object.assign(config, window.REPORTS_CONFIG);
            }
            
            // Merge configuración personalizada
            Object.assign(config, options);
            
            $(document).ready(function() {
                console.log('Initializing Reports Module...');
                
                // Agregar estilos dinámicos
                addCustomStyles();
                
                // Inicializar componentes
                initializeCardAnimations();
                initializePDFButtons();
                initializeReportButtons();
                initializeTooltips();
                handleResponsiveDesign();
                
                initialized = true;
                console.log('Reports Module initialized successfully');
            });
        },
        
        // Reinicializar tooltips (útil después de actualizar contenido)
        refreshTooltips: function() {
            $('[data-toggle="tooltip"]').tooltip('dispose');
            tooltipsInitialized = false;
            initializeTooltips();
        },
        
        // Agregar nueva funcionalidad de reportes
        addCustomReportHandler: function(selector, handler) {
            $(document).off('click.custom-report', selector).on('click.custom-report', selector, handler);
        },
        
        // Estado del módulo
        isInitialized: function() {
            return initialized;
        },
        
        // Configuración actual
        getConfig: function() {
            return { ...config };
        }
    };
})();

// Auto-inicialización
ReportsModule.init();