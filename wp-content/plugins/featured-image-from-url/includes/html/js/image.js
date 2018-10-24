jQuery(document).ready(function ($) {
    disableClick($);
    setTimeout(function () {
        jQuery('div.flex-viewport').each(function (index) {
            jQuery(this).css('height', '');
        });
    }, 500);
});

function disableClick($) {
    if ('<?php echo !fifu_woo_lbox(); ?>') {
        jQuery('.woocommerce-product-gallery__image').each(function (index) {
            jQuery(this).children().click(function () {
                return false;
            });
            jQuery(this).children().children().css("cursor", "default");
        });
    }
}
