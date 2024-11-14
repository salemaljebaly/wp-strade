jQuery(document).ready(function ($) {
    function handleAjaxError() {
        console.log("Ajax error");
    }

    function handleAjaxComplete() {
        console.log("Ajax completed");
    }

    function replaceButtonWithText(buttonSelector, text) {
        const $newElement = $(`<p id="l-bt-3" style="line-height: 2.15384615; margin: 0;">${text}</p>`);
        $(buttonSelector).replaceWith($newElement);
    }

    function startOrderExport() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: "POST",
            data: {
                action: "start_order_export",
                security: ajax_object.security,
            },
            beforeSend: function () {
                replaceButtonWithText('#l-bt-1', 'جاري التصدير ..');
            },
            success: function (response) {
                if (response.status) { 
                    $('#custom-export-button-wrapper').html('<button id="l-bt-2" style="height:32px; color: #ffffff; background: #75B214; border-color: #75B214;" class="button">Download Export</button>');
                } else {
                    alert('Export failed. Please try again.');
                }
            },
            error: handleAjaxError,
            complete: handleAjaxComplete,
        });
    }

    function downloadExport() {
        $.ajax({
            url: ajax_object.ajax_url,
            type: "POST",
            data: {
                action: "download_export",
                security: ajax_object.security,
            },
            success: function (response) {
                const downloadPath = response.downloadPath || '';
                if (downloadPath) {
                    window.location.href = downloadPath;
                }
            },
            error: handleAjaxError,
            complete: function () {
                $.ajax({
                    url: ajax_object.ajax_url,
                    data: {
                        action: "delete_options",
                    },
                });
            }
        });
    }

    // Position the export button on the orders page
    function positionExportButton() {
        const filterButton = $('#order-query-submit');
        const buttonWrapper = $('#custom-export-button-wrapper');

        if (filterButton.length && buttonWrapper.length) {
            buttonWrapper.css('display', 'inline-block');
            filterButton.after(buttonWrapper);
        }
    }

    // Run the positioning when the document is fully loaded
    positionExportButton();

    // Event listeners for buttons
    $("#l-bt-1").on("click", function (e) {
        e.preventDefault();
        startOrderExport();
    });

    $(document).on("click", "#l-bt-2", function (e) {
        e.preventDefault();
        downloadExport();
    });
});
