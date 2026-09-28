/* BrandSure — Media Library picker for the settings screen.
   Fills the adjacent text field with the chosen attachment ID and shows a
   thumbnail. The field also accepts a plain URL typed by hand. */
(function ($) {
  "use strict";

  $(document).on("click", ".brandsure-img__pick", function (e) {
    e.preventDefault();

    var $wrap = $(this).closest(".brandsure-img");
    var $val = $wrap.find(".brandsure-img__val");
    var $prev = $wrap.find(".brandsure-img__prev");

    var frame = wp.media({
      title: "Select image",
      button: { text: "Use this image" },
      library: { type: "image" },
      multiple: false
    });

    frame.on("select", function () {
      var att = frame.state().get("selection").first().toJSON();
      $val.val(att.id);

      var thumb =
        att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
      $prev.html(
        $("<img>", { src: thumb, alt: "" }).css({
          maxHeight: "60px",
          verticalAlign: "middle"
        })
      );
    });

    frame.open();
  });
})(jQuery);
