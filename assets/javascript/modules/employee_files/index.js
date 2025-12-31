/**
 * Modulo: Expedientes de Empleados
 * Funcionalidades: DataTables, selects dinamicos, formularios por tipo/subtipo
 */

$(document).ready(function() {
    const urls = window.APP_CONFIG?.urls || {};
    const panelUrl = urls.panel_url || "/panel";
    const api = {
        subtypes: `${panelUrl}/employee-files/subtypes`,
        form: `${panelUrl}/employee-files/form`,
        nextDocumentNumber: `${panelUrl}/employee-files/nextDocumentNumber`
    };

    const $form = $("#employee-file-form");
    const $documentNumber = $form.find("input[name='document_number']");
    const $documentDate = $form.find("input[name='document_date']");
    const fileId = parseInt($form.data("file-id") || 0, 10);
    const initialCorrelativeState = {
        typeId: $("#type_id").val() || "",
        subtypeId: $("#subtype_id").val() || "",
        documentDate: $documentDate.val() || ""
    };
    let correlativeDirty = false;

    const $table = $("#employeeFilesTable");
    if ($table.length) {
        $table.DataTable({
            language: {
                url: urls.datatables_spanish || "/assets/js/datatables-spanish.json"
            },
            order: [[2, "desc"]],
            pageLength: 25,
            responsive: true
        });
    }

    if ($(".select2").length) {
        $(".select2").select2({
            theme: "bootstrap4",
            width: "100%"
        });
    }

    if (typeof window.bsCustomFileInput !== "undefined") {
        window.bsCustomFileInput.init();
    }

    const $previewModal = $("#attachmentPreviewModal");
    const $previewTitle = $("#attachmentPreviewTitle");
    const $previewMessage = $("#attachmentPreviewMessage");
    const $previewImage = $("#attachmentPreviewImage");
    const $previewIframe = $("#attachmentPreviewIframe");
    const $previewDownload = $("#attachmentPreviewDownload");

    function resetAttachmentPreview() {
        $previewMessage.addClass("d-none").text("");
        $previewImage.addClass("d-none").attr("src", "");
        $previewIframe.addClass("d-none").attr("src", "");
    }

    function showAttachmentPreview(url, mimeType, name, downloadUrl) {
        resetAttachmentPreview();

        if ($previewTitle.length) {
            $previewTitle.text(name || "Adjunto");
        }

        if ($previewDownload.length && downloadUrl) {
            $previewDownload.attr("href", downloadUrl);
        }

        if (mimeType && mimeType.indexOf("image/") === 0) {
            $previewImage.attr("src", url).removeClass("d-none");
        } else if (mimeType && mimeType.indexOf("pdf") !== -1) {
            $previewIframe.attr("src", url).removeClass("d-none");
        } else {
            $previewMessage
                .text("Vista previa no disponible para este tipo de archivo.")
                .removeClass("d-none");
        }

        if ($previewModal.length) {
            $previewModal.modal("show");
        }
    }

    function hasCorrelativeChanged() {
        return ($("#type_id").val() || "") !== initialCorrelativeState.typeId ||
            ($("#subtype_id").val() || "") !== initialCorrelativeState.subtypeId ||
            ($documentDate.val() || "") !== initialCorrelativeState.documentDate;
    }

    function clearDocumentNumber() {
        if ($documentNumber.length) {
            $documentNumber.val("");
        }
    }

    function refreshDocumentNumber() {
        if (!$form.length || !$documentNumber.length) {
            return;
        }

        const typeId = $("#type_id").val();
        const subtypeId = $("#subtype_id").val();
        const documentDate = $documentDate.val();

        if (!typeId || !subtypeId || !documentDate) {
            if (!fileId || correlativeDirty) {
                clearDocumentNumber();
            }
            return;
        }

        if (fileId && !correlativeDirty) {
            return;
        }

        $.get(api.nextDocumentNumber, {
            type_id: typeId,
            subtype_id: subtypeId,
            document_date: documentDate
        })
            .done(function(response) {
                if (!response || !response.success || !response.document_number) {
                    clearDocumentNumber();
                    return;
                }
                $documentNumber.val(response.document_number);
            })
            .fail(function() {
                clearDocumentNumber();
            });
    }

    function markCorrelativeChange() {
        if (fileId && !correlativeDirty && hasCorrelativeChanged()) {
            correlativeDirty = true;
        }
        refreshDocumentNumber();
    }

    function initDatePickers(context) {
        const $context = context ? $(context) : $(document);
        $context.find(".date-picker").each(function() {
            const $input = $(this);
            $input.daterangepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                showDropdowns: true,
                locale: {
                    format: "YYYY-MM-DD",
                    applyLabel: "Aceptar",
                    cancelLabel: "Cancelar",
                    daysOfWeek: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
                    monthNames: [
                        "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                        "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
                    ]
                }
            });

            $input.on("apply.daterangepicker", function(ev, picker) {
                $input.val(picker.startDate.format("YYYY-MM-DD"));
                if ($input.attr("name") === "document_date") {
                    markCorrelativeChange();
                }
            });
            $input.on("cancel.daterangepicker", function() {
                $input.val("");
                if ($input.attr("name") === "document_date") {
                    markCorrelativeChange();
                }
            });

            if ($input.val()) {
                const picker = $input.data("daterangepicker");
                picker.setStartDate($input.val());
                picker.setEndDate($input.val());
            }
        });
    }

    function initDateTimePickers(context) {
        const $context = context ? $(context) : $(document);
        $context.find(".datetime-picker").each(function() {
            const $input = $(this);
            $input.daterangepicker({
                singleDatePicker: true,
                autoUpdateInput: false,
                showDropdowns: true,
                timePicker: true,
                timePicker24Hour: true,
                locale: {
                    format: "YYYY-MM-DD HH:mm",
                    applyLabel: "Aceptar",
                    cancelLabel: "Cancelar",
                    daysOfWeek: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
                    monthNames: [
                        "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                        "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
                    ]
                }
            });

            $input.on("apply.daterangepicker", function(ev, picker) {
                $input.val(picker.startDate.format("YYYY-MM-DD HH:mm"));
            });
            $input.on("cancel.daterangepicker", function() {
                $input.val("");
            });

            if ($input.val()) {
                const picker = $input.data("daterangepicker");
                picker.setStartDate($input.val());
                picker.setEndDate($input.val());
            }
        });
    }

    function refreshDynamicForm() {
        const $form = $("#employee-file-form");
        const $type = $("#type_id");
        const $subtype = $("#subtype_id");
        const $container = $("#dynamic-fields-container");

        if (!$form.length || !$type.length || !$subtype.length || !$container.length) {
            return;
        }

        const typeId = $type.val();
        const subtypeId = $subtype.val();
        const fileId = $form.data("file-id") || 0;

        if (!typeId || !subtypeId) {
            $container.html("");
            return;
        }

        $.get(api.form, { type_id: typeId, subtype_id: subtypeId, employee_file_id: fileId })
            .done(function(response) {
                if (!response || !response.success) {
                    $container.html("");
                    return;
                }
                $container.html(response.html || "");
                if (typeof window.bsCustomFileInput !== "undefined") {
                    window.bsCustomFileInput.init();
                }
                initDatePickers($container);
                initDateTimePickers($container);
            })
            .fail(function() {
                $container.html("");
            });
    }

    $("#type_id").on("change", function() {
        const typeId = $(this).val();
        const $subtype = $("#subtype_id");
        const $container = $("#dynamic-fields-container");

        $container.html("");
        $subtype.empty().append('<option value="">Seleccione...</option>');
        clearDocumentNumber();
        if (fileId) {
            correlativeDirty = true;
        }

        if (!typeId) {
            return;
        }

        $.get(api.subtypes, { type_id: typeId })
            .done(function(response) {
                if (!response || !response.success) {
                    return;
                }
                (response.data || []).forEach(function(item) {
                    $subtype.append(
                        $('<option>', { value: item.id, text: item.name })
                    );
                });
                $subtype.trigger("change.select2");
            });
    });

    $("#subtype_id").on("change", function() {
        refreshDynamicForm();
        markCorrelativeChange();
    });

    $(document).on("click", ".btn-preview-attachment", function(event) {
        event.preventDefault();
        const $button = $(this);
        const previewUrl = $button.data("preview-url") || "";
        const downloadUrl = $button.data("download-url") || previewUrl;
        const mimeType = ($button.data("mime") || "").toString();
        const name = ($button.data("name") || "").toString();

        if (!previewUrl) {
            return;
        }

        showAttachmentPreview(previewUrl, mimeType, name, downloadUrl);
    });

    if ($previewModal.length) {
        $previewModal.on("hidden.bs.modal", function() {
            resetAttachmentPreview();
        });
    }

    $(document).on("change", "input[name='document_date']", function() {
        markCorrelativeChange();
    });

    initDatePickers();
    initDateTimePickers();

    const $dynamicContainer = $("#dynamic-fields-container");
    if ($dynamicContainer.length && !$dynamicContainer.children().length) {
        const hasType = $("#type_id").val();
        const hasSubtype = $("#subtype_id").val();
        if (hasType && hasSubtype) {
            refreshDynamicForm();
        }
    }

    refreshDocumentNumber();
});
