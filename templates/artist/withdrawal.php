<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $available_balance (float), $paypal_email (string), $withdrawal_history (array)
?>
<div class="artist-withdrawal-wrapper">
    <div class="balance-info">
        <p class="available-balance">
            <?php printf(__('Available Balance: %s', 'paper-tipping-addons'), wc_price($available_balance)); ?>
        </p>
        <?php if ($available_balance < 10) : ?>
            <p class="minimum-balance-notice">
                <?php _e('You need a minimum balance of $10 to make a withdrawal.', 'paper-tipping-addons'); ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if ($available_balance >= 10) : ?>
        <button class="withdraw-money-btn button"
            data-balance="<?php echo esc_attr($available_balance); ?>"
            data-email="<?php echo esc_attr($paypal_email); ?>">
            <?php _e('Withdraw Funds', 'paper-tipping-addons'); ?>
        </button>
    <?php endif; ?>

    <?php if (!empty($withdrawal_history) && is_array($withdrawal_history)) : ?>
        <div class="withdrawal-history">
            <h3><?php _e('Withdrawal History', 'paper-tipping-addons'); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th><?php _e('Date', 'paper-tipping-addons'); ?></th>
                        <th><?php _e('Amount', 'paper-tipping-addons'); ?></th>
                        <th><?php _e('PayPal Email', 'paper-tipping-addons'); ?></th>
                        <th><?php _e('Status', 'paper-tipping-addons'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawal_history as $withdrawal) : ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format'), $withdrawal['date']); ?></td>
                            <td><?php echo wc_price($withdrawal['amount']); ?></td>
                            <td><?php echo esc_html($withdrawal['paypal_email']); ?></td>
                            <td class="<?php echo esc_attr($withdrawal['status']); ?>">
                                <?php echo esc_html(ucfirst($withdrawal['status'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
