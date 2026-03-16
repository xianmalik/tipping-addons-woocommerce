function increaseTip(button) {
    var input = button.parentElement.querySelector('.tip-amount');
    input.value = parseFloat(input.value) + 1;
    validateTip(input);
}

function decreaseTip(button) {
    var input = button.parentElement.querySelector('.tip-amount');
    input.value = Math.max(1, parseFloat(input.value) - 1);
    validateTip(input);
}

function validateTip(i) {
    var v = i.value;
    var ss = i.selectionStart;
    var resetCursor = function () { i.setSelectionRange(ss, ss); };

    if (/^[0]*\.00$/.test(v)) {
        i.value = '';
    } else if (/^[0-9.]+$/.test(v)) {
        var p = v.indexOf('..');
        if (p >= 0) {
            i.value = v.replace('..', '.');
            resetCursor();
        } else if (v.split('.').length - 1 > 1) {
            var j = v.indexOf('.');
            i.value = v.split('').filter(function (c, k) { return k <= j || c !== '.'; }).join('');
            resetCursor();
        } else {
            i.value = (+v).toFixed(2);
            resetCursor();
        }
    } else {
        i.value = v.replace(/[^0-9.]/g, '');
        resetCursor();
    }
}

function addTipToCart(button) {
    var container  = button.closest('.tip-widget-container');
    var amount     = container.querySelector('.tip-amount').value;
    var postId     = container.dataset.postId;
    var pageTitle  = container.dataset.postTitle;
    var nonce      = container.dataset.nonce;
    var ajaxUrl    = container.dataset.ajaxUrl;

    jQuery.ajax({
        url:  ajaxUrl,
        type: 'POST',
        data: {
            action:    'add_tip_to_cart',
            amount:    amount,
            post_id:   postId,
            page_title: pageTitle,
            nonce:     nonce
        },
        success: function (response) {
            if (response.success) {
                var cartIcon  = document.querySelector('.cart-icon-wrapper');
                var cartCount = document.querySelector('.cart-item-count');
                var newCount  = parseInt((cartCount && cartCount.textContent) || '0') + 1;

                if (cartCount) {
                    cartCount.textContent = newCount;
                } else if (cartIcon) {
                    var el = document.createElement('span');
                    el.className   = 'cart-item-count';
                    el.textContent = '1';
                    cartIcon.appendChild(el);
                }

                if (cartIcon) {
                    cartIcon.classList.add('bounce');
                    setTimeout(function () { cartIcon.classList.remove('bounce'); }, 1000);
                }
            }
        }
    });
}

jQuery(document).ready(function ($) {
    $(document).on('click', '.tip-decrease', function () { decreaseTip(this); });
    $(document).on('click', '.tip-increase', function () { increaseTip(this); });
    $(document).on('keyup',  '.tip-amount',  function () { validateTip(this); });
    $(document).on('click',  '.tip-now-button', function () { addTipToCart(this); });
});
