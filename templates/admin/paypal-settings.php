<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $client_id (string), $client_secret (string), $sandbox_mode (string '0'|'1')
?>
<div class="wrap">
    <h1><?php echo esc_html__('PayPal Settings', 'paper-tipping-addons'); ?></h1>

    <?php if (!empty($saved_notice)) : ?>
        <div class="notice notice-success"><p><?php echo esc_html__('Settings saved successfully!', 'paper-tipping-addons'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('save_paypal_settings', 'paypal_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="tipping_paypal_client_id"><?php echo esc_html__('Client ID', 'paper-tipping-addons'); ?></label>
                </th>
                <td>
                    <input type="text" id="tipping_paypal_client_id" name="tipping_paypal_client_id"
                        value="<?php echo esc_attr($client_id); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="tipping_paypal_client_secret"><?php echo esc_html__('Client Secret', 'paper-tipping-addons'); ?></label>
                </th>
                <td>
                    <input type="password" id="tipping_paypal_client_secret" name="tipping_paypal_client_secret"
                        value="<?php echo esc_attr($client_secret); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php echo esc_html__('Environment', 'paper-tipping-addons'); ?>
                </th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="tipping_paypal_sandbox" value="1"
                                <?php checked('1', $sandbox_mode); ?>>
                            <?php echo esc_html__('Sandbox Mode', 'paper-tipping-addons'); ?>
                        </label>
                        <p class="description">
                            <?php echo esc_html__('Check this to use PayPal Sandbox for testing.', 'paper-tipping-addons'); ?>
                        </p>
                    </fieldset>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="submit_paypal_settings" class="button button-primary"
                value="<?php echo esc_attr__('Save Changes', 'paper-tipping-addons'); ?>">
        </p>
    </form>
</div>
