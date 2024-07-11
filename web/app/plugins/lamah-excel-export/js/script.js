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
          success: function () {},
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

  $("#l-bt-1").on("click", function (e) {
      e.preventDefault();
      startOrderExport();
  });

  $("#l-bt-2").on("click", function (e) {
      e.preventDefault();
      downloadExport();
  });
});
