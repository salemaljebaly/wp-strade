jQuery(document).ready(function ($) {
  $("#media-sync").on("click", function (e) {
    e.preventDefault();

    jQuery.ajax({
      url: ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "sync_media_products",
        security: ajax_object.security,
      },
      beforeSend: function () {
        var $newElement = $('<p id="l-bt-3" style="line-height: 2.15384615; margin: 0;">جاري المزامنة ..</p>');
        $('#media-sync').replaceWith($newElement);
        // console.log("Ajax Before Send");
      },
      success: function () {},
      error: function () {
        // console.log("Ajax error");

      },
      complete: function () {
        // console.log("Ajax completed");
      },
    });
  });
});
