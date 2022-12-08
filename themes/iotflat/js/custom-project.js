jQuery(document).ready(function ($) {
    var pswp = new PhotoSwipe();

    $('a.lightbox').on("click", function(e) {
        e.preventDefault();

        var c = $('#gallery_container');
        Drupal.behaviors.photoswipe.openPhotoSwipe(0, c);
    });
});


