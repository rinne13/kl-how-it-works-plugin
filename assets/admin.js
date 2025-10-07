(function ($) {
  $(document).on("click", "#kl-hiw-add", function () {
    const $wrap = $("#kl-hiw-steps");
    const tmpl = $("#kl-hiw-row-template").html();
    const idx = $wrap.find(".kl-hiw-row").length;
    const html = tmpl.replace(/__i__/g, idx);
    $wrap.append(html);
  });

  $(document).on("click", "#kl-hiw-steps .link-delete", function () {
    $(this).closest(".kl-hiw-row").remove();
  });
})(jQuery);
