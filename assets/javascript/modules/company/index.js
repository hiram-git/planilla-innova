/**
 * Módulo: Configuración de Empresa
 * Funcionalidades: Validación formulario, cambio de moneda, tooltips
 */

$(document).ready(function() {
    // Configuración del módulo
    const CompanyModule = {
        // Inicializar módulo
        init() {
            this.initFormValidation();
            this.initCurrencyHandler();
            this.initSignaturePreview();
            this.initTooltips();
        },

        // Validación del formulario
        initFormValidation() {
            $("#companyForm").on("submit", function(e) {
                const companyName = $("#company_name").val().trim();
                const ruc = $("#ruc").val().trim();
                
                if (companyName.length < 3) {
                    e.preventDefault();
                    alert("El nombre de la empresa debe tener al menos 3 caracteres");
                    $("#company_name").focus();
                    return false;
                }
                
                if (ruc.length < 6) {
                    e.preventDefault();
                    alert("El RUC debe tener al menos 6 caracteres");
                    $("#ruc").focus();
                    return false;
                }
                
                return true;
            });
        },

        // Manejo de cambio de moneda
        initCurrencyHandler() {
            $("#currency_code").on("change", function() {
                const selectedOption = $(this).find("option:selected");
                const symbol = selectedOption.data("symbol");
                $("#currency_symbol").val(symbol);
            });
        },

        // Vista previa de firmas en tiempo real
        initSignaturePreview() {
            // Campos de firmas
            const signatureFields = {
                'elaborado_por': 'preview_elaborador',
                'cargo_elaborador': 'preview_cargo_elaborador', 
                'jefe_recursos_humanos': 'preview_jefe_rrhh',
                'cargo_jefe_rrhh': 'preview_cargo_jefe'
            };

            // Actualizar vista previa en tiempo real
            Object.keys(signatureFields).forEach(fieldName => {
                $(`#${fieldName}`).on('input keyup', function() {
                    const value = $(this).val().trim();
                    const previewId = signatureFields[fieldName];
                    let defaultValue = 'Por definir';
                    
                    // Valores por defecto específicos
                    if (fieldName === 'cargo_elaborador') {
                        defaultValue = 'Especialista en Nóminas';
                    } else if (fieldName === 'cargo_jefe_rrhh') {
                        defaultValue = 'Jefe de Recursos Humanos';
                    }
                    
                    $(`#${previewId}`).text(value || defaultValue);
                });
            });
        },

        // Inicializar tooltips
        initTooltips() {
            $("[title]").tooltip();
        }
    };

    // Inicializar módulo
    CompanyModule.init();
});