jQuery(document).ready(function($) {
    var withdrawalModal = $('<div class="withdrawal-modal">' +
        '<div class="withdrawal-modal-content">' +
            '<h2>' + tipping_addons.i18n.withdrawal_title + '</h2>' +
            '<form class="withdrawal-form">' +
                '<div>' +
                    '<label for="withdrawal_amount">' + tipping_addons.i18n.amount + '</label>' +
                    '<input type="number" id="withdrawal_amount" name="withdrawal_amount" min="10" step="0.01" required />' +
                    '<div class="error-message"></div>' +
                '</div>' +
                '<div>' +
                    '<label for="paypal_email">' + tipping_addons.i18n.paypal_email + '</label>' +
                    '<input type="email" id="paypal_email" name="paypal_email" required />' +
                '</div>' +
                '<input type="hidden" name="withdrawal_nonce" value="' + tipping_addons.withdrawal_nonce + '" />' +
                '<button type="submit" class="button">' + tipping_addons.i18n.withdraw + '</button>' +
            '</form>' +
        '</div>' +
    '</div>');

    $('body').append(withdrawalModal);

    $('.withdraw-money-btn').on('click', function() {
        console.log('Withdraw button clicked');

        var availableBalance = $(this).data('balance');
        $('#withdrawal_amount').attr('max', availableBalance);
        $('.withdrawal-modal').fadeIn();
    });

    $('.withdrawal-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut();
        }
    });

    $('.withdrawal-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var amount = $('#withdrawal_amount').val();
        var paypalEmail = $('#paypal_email').val();

        $.ajax({
            url: tipping_addons.ajax_url,
            type: 'POST',
            data: {
                action: 'process_artist_withdrawal',
                withdrawal_nonce: form.find('[name="withdrawal_nonce"]').val(),
                amount: amount,
                paypal_email: paypalEmail
            },
            success: function(response) {
                if (response.success) {
                    $('.available-balance').html(response.data.new_balance);
                    $('.withdrawal-modal').fadeOut();
                    location.reload();
                } else {
                    form.find('.error-message')
                        .html(response.data.message)
                        .show();
                }
            }
        });
    });
});
