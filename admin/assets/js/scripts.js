/*! wp-ulike-pro - v1.9.3
 *  https://wpulike.com
 *  TechnoWich 2025;
 */


/* ================== admin/assets/js/src/scripts.js =================== */


/**
 * wp ulike admin statistics
 */
(function ($) {
  // on document ready
  $(function () {});

  $(".wp-ulike-pro-ajax-button-field").on("click", function (e) {
    e.preventDefault();
    if (
      confirm("Are you sure you want to make this change in your database?")
    ) {
      var $self = $(this),
        $loaderElement = $self.closest(".wp-ulike-pro-ajax-button");

      $loaderElement.addClass("wp-ulike-is-loading");
      $.ajax({
        data: {
          action: "wp_ulike_ajax_button_field",
          nonce: $self.data("nonce"),
          type: $self.data("type"),
          method: $self.data("action"),
        },
        dataType: "json",
        type: "POST",
        timeout: 10000,
        url: UlikeProAdminCommonConfig.AjaxUrl,
        success: function (response) {
          $loaderElement.removeClass("wp-ulike-is-loading");
          $self.addClass("wp-ulike-success-primary");
          $self.prop("value", response.data.message);
        },
      });
    }
  });

  $("#wp-ulike-pro-generate-api-key").on("click", function (e) {
    e.preventDefault();

    $(".wp-ulike-pro-api-keys").addClass("wp-ulike-is-loading");
    $.ajax({
      data: {
        action: "wp_ulike_generate_api_key",
        nonce: $(this).siblings("#wp-ulike-pro-api-keys-nonce-field").val(),
      },
      dataType: "json",
      type: "POST",
      url: UlikeProAdminCommonConfig.AjaxUrl,
      success: function (response) {
        $(".wp-ulike-pro-api-keys").removeClass("wp-ulike-is-loading");
        var $noticeElement = $("#wp-ulike-pro-api-keys-info-message");
        var noticeClassname = response.data.success ? "success" : "danger";
        // Update content
        $noticeElement
          .html(response.data.message)
          .removeClass()
          .addClass("ulf-submessage ulf-submessage-" + noticeClassname);

        if (typeof response.data.content !== "undefined") {
          $(".wp-ulike-pro-api-keys table tbody").html(response.data.content);
        }
      },
    });
  });
})(jQuery);