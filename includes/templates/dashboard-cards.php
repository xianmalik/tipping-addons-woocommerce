<?php if (!defined('ABSPATH')) exit; ?>
<div class="dashboard-summary">
    <div class="summary-item">
        <div class="summary-item-header">
            <div class="summary-item-icon">
                <i class="fas fa-coins"></i>
            </div>
            <h3><?php _e('Total Tips Received', 'paper-tipping-addons'); ?></h3>
        </div>
        <p class="total-amount"><?php echo wc_price($total_tips); ?></p>
    </div>
    
    <div class="summary-item">
        <div class="summary-item-header">
            <div class="summary-item-icon">
                <i class="fas fa-music"></i>
            </div>
            <h3><?php _e('Total Songs Added', 'paper-tipping-addons'); ?></h3>
        </div>
        <p class="total-count"><?php echo esc_html($total_songs); ?></p>
    </div>
</div>