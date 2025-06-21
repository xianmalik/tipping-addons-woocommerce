<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="dashboard-summary">
    <div class="summary-item">
        <div class="summary-item-header">
            <div class="summary-item-icon">
                <i class="fas fa-coins"></i>
            </div>
            <h3><?php _e('Songs You Have Tipped', 'tipping-addons-jetengine'); ?></h3>
        </div>
        
        <div style="padding: 0 20px 20px;">
            <?php if (!empty($tipped_songs)) : ?>
                <p style="margin-bottom: 15px;">
                    <?php 
                    $tip_count = count($tipped_songs);
                    printf(
                        _n(
                            'You have tipped %d song.',
                            'You have tipped %d songs.',
                            $tip_count,
                            'tipping-addons-jetengine'
                        ),
                        $tip_count
                    );
                    ?>
                </p>
                <a href="<?php echo esc_url(home_url('/my-account/orders/')); ?>" class="button" style="display: inline-block; padding: 5px 0; background-color: transparent; color: #0073aa; text-decoration: none; font-size: 14px; transition: all 0.3s ease;">
                    <?php _e('Show All Tips', 'tipping-addons-jetengine'); ?> <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            <?php else : ?>
                <p style="margin:0;"><?php _e('You haven\'t tipped any songs yet.', 'tipping-addons-jetengine'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div> 