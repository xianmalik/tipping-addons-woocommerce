jQuery(document).ready(function ($) {
    function updateCartIcon(cartCount) {
        var countElement = document.getElementById('cart-item-count');
        if (countElement) {
            countElement.textContent = cartCount;
        }

        var cartIcon = document.querySelector('.cart-icon-wrapper');
        if (cartIcon) {
            cartIcon.classList.remove('cart-bounce');
            void cartIcon.offsetWidth; // force reflow to restart animation
            cartIcon.classList.add('cart-bounce');
        }
    }

    $(document).on('added_to_cart', function (event, fragments) {
        try {
            var cartCount = 0;
            if (fragments && fragments['div.widget_shopping_cart_content']) {
                cartCount = $(fragments['div.widget_shopping_cart_content']).find('.cart-items-count').text() || 0;
            }
            updateCartIcon(cartCount);
        } catch (e) {
            console.log('Error updating cart icon:', e);
        }
    });

    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (settings.url && settings.url.indexOf('add_tip_to_cart') !== -1) {
            try {
                var response = xhr.responseJSON;
                if (response && response.success) {
                    updateCartIcon(response.data.cart_count || 0);
                }
            } catch (e) {
                console.log('Error handling tip addition:', e);
            }
        }
    });
});
