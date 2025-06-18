jQuery(document).ready(function($) {
    // Check withdrawal status every minute for pending withdrawals
    function checkPendingWithdrawals() {
        $('.withdrawal-history tr').each(function() {
            const row = $(this);
            const status = row.find('td.pending');
            
            if (status.length) {
                const batchId = row.data('batch-id');
                if (batchId) {
                    $.ajax({
                        url: tipping_addons.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'check_withdrawal_status',
                            batch_id: batchId,
                            security: tipping_addons.nonce
                        },
                        success: function(response) {
                            if (response.success && response.data.status) {
                                status.removeClass('pending')
                                    .addClass(response.data.status.toLowerCase())
                                    .text(response.data.status);

                                if (response.data.status === 'COMPLETED') {
                                    // Stop checking this withdrawal
                                    row.removeData('batch-id');
                                }
                            }
                        }
                    });
                }
            }
        });
    }

    // Check status every minute for pending withdrawals
    if ($('.withdrawal-history tr td.pending').length) {
        setInterval(checkPendingWithdrawals, 60000);
    }
});
