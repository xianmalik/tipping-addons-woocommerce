<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $tips (array of stdClass rows from wp_song_tips)
?>
<div class="wrap">
    <h1><?php echo esc_html__('Song Tips', 'paper-tipping-addons'); ?></h1>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Song', 'paper-tipping-addons'); ?></th>
                <th><?php echo esc_html__('Tip Amount', 'paper-tipping-addons'); ?></th>
                <th><?php echo esc_html__('Customer', 'paper-tipping-addons'); ?></th>
                <th><?php echo esc_html__('Date', 'paper-tipping-addons'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tips)) : ?>
                <?php foreach ($tips as $tip) : ?>
                    <tr>
                        <td>
                            <?php
                            $song_title = get_the_title($tip->song_id);
                            echo esc_html($song_title ?: "#$tip->song_id");
                            ?>
                        </td>
                        <td><?php echo esc_html(wc_price($tip->tip_amount)); ?></td>
                        <td><?php echo esc_html($tip->customer_name ?: __('Anonymous', 'paper-tipping-addons')); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tip->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php echo esc_html__('No tips found.', 'paper-tipping-addons'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
