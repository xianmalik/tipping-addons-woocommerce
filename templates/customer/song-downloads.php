<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $downloads (array of stdClass rows from wp_song_tips)
?>
<?php if (!empty($downloads)) : ?>
    <h2><?php _e('Your Song Downloads', 'paper-tipping-addons'); ?></h2>
    <table class="woocommerce-orders-table">
        <thead>
            <tr>
                <th><?php _e('Song', 'paper-tipping-addons'); ?></th>
                <th><?php _e('Date', 'paper-tipping-addons'); ?></th>
                <th><?php _e('Downloads', 'paper-tipping-addons'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($downloads as $download) : ?>
                <tr>
                    <td><?php echo esc_html(get_the_title($download->song_id)); ?></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($download->created_at))); ?></td>
                    <td>
                        <?php if (!empty($download->song_mp3)) : ?>
                            <a href="<?php echo esc_url($download->song_mp3); ?>" class="button" style="margin-right: 10px;">MP3</a>
                        <?php endif; ?>
                        <?php if (!empty($download->song_wav)) : ?>
                            <a href="<?php echo esc_url($download->song_wav); ?>" class="button">WAV</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
    <p><?php _e('No downloads available.', 'paper-tipping-addons'); ?></p>
<?php endif; ?>
