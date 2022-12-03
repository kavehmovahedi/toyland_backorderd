!function(r) {
    woodmartThemeModule.addToCartAllTypesFix = function() {
        0 != woodmart_settings.ajax_add_to_cart && (woodmartThemeModule.$body.on("submit", "form.cart", function(a) {
            var d, t = r(this), o = t.parents(".single-product-page");
            (o = 0 === o.length ? t.parents(".product-quick-view") : o).hasClass("product-type-external") || o.hasClass("product-type-zakeke") || o.hasClass("product-type-gift-card") || void 0 !== a.originalEvent && r(a.originalEvent.submitter).hasClass("wd-buy-now-btn") || (a.preventDefault(),
            d = t.find(".single_add_to_cart_button"),
            o = t.serialize(),
            o += "&action=woodmart_ajax_add_to_cart",
            d.val() && (o += "&add-to-cart=" + d.val()),
            d.removeClass("added not-added"),
            d.addClass("loading"),
            woodmartThemeModule.$body.trigger("adding_to_cart", [d, o]),
            r.ajax({
                url: woodmart_settings.ajaxurl,
                data: o,
                method: "POST",
                success: function(a) {
                    var t, o, e;
                    a && (window.location.toString().replace("add-to-cart", "added-to-cart"),
                    a.error && a.product_url ? window.location = a.product_url : "yes" === woodmart_settings.cart_redirect_after_add ? window.location = woodmart_settings.cart_url : (d.removeClass("loading"),
                    t = a.fragments,
                    o = a.cart_hash,
                    t && r.each(t, function(a) {
                        r(a).addClass("updating")
                    }),
                    t && r.each(t, function(a, t) {
                        r(a).replaceWith(t)
                    }),
                    (e = r(".woocommerce-notices-wrapper")).empty(),
                    0 < a.notices.indexOf("error") ? (e.append(a.notices),
                    d.addClass("not-added")) : ("widget" === woodmart_settings.add_to_cart_action && r.magnificPopup && r.magnificPopup.close(),
                    d.addClass("added"),
                    woodmartThemeModule.$body.trigger("added_to_cart", [t, o, d]))))
                },
                error: function() {
                    console.log("ajax adding to cart error")
                },
                complete: function() {}
            }))
        }),
        woodmartThemeModule.$body.on("click", ".variations_form .wd-buy-now-btn", function(a) {
            var t = r(this).siblings(".single_add_to_cart_button");
            "undefined" != typeof wc_add_to_cart_variation_params && t.hasClass("disabled") && (a.preventDefault(),
            t.hasClass("wc-variation-is-unavailable") ? alert(wc_add_to_cart_variation_params.i18n_unavailable_text) : t.hasClass("wc-variation-selection-needed") && alert(wc_add_to_cart_variation_params.i18n_make_a_selection_text))
        }))
    }
    ,
    r(document).ready(function() {
        document.querySelector('#wd-add-to-cart-all-types-js').remove()
        woodmartThemeModule.addToCartAllTypesFix()
    })
}(jQuery);
