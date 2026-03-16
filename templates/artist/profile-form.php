<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $artist_bio, $profile_image_id, $first_name, $last_name, $display_name
?>
<div class="artist-profile-wrapper">
    <h2><?php _e('Artist Profile Settings', 'paper-tipping-addons'); ?></h2>

    <form id="artist-profile-form" method="post" enctype="multipart/form-data">
        <div class="form-message"></div>

        <div class="profile-image-section">
            <label><?php _e('Profile Picture', 'paper-tipping-addons'); ?></label>
            <div class="current-profile-image">
                <?php if ($profile_image_id) :
                    echo wp_get_attachment_image($profile_image_id, 'thumbnail');
                else : ?>
                    <div class="profile-placeholder">
                        <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                <?php endif; ?>
            </div>
            <input type="file" name="profile_image" id="profile_image" accept="image/*" />
            <small><?php _e('Recommended size: 300x300 pixels', 'paper-tipping-addons'); ?></small>
        </div>

        <p>
            <label for="first_name"><?php _e('First Name', 'paper-tipping-addons'); ?></label>
            <input type="text" name="first_name" id="first_name"
                value="<?php echo esc_attr($first_name); ?>"
                placeholder="<?php _e('Enter your first name', 'paper-tipping-addons'); ?>" />
        </p>

        <p>
            <label for="last_name"><?php _e('Last Name', 'paper-tipping-addons'); ?></label>
            <input type="text" name="last_name" id="last_name"
                value="<?php echo esc_attr($last_name); ?>"
                placeholder="<?php _e('Enter your last name', 'paper-tipping-addons'); ?>" />
        </p>

        <p>
            <label for="display_name"><?php _e('Display Name', 'paper-tipping-addons'); ?></label>
            <input type="text" name="display_name" id="display_name"
                value="<?php echo esc_attr($display_name); ?>"
                readonly disabled />
            <small><?php _e('This is how your name will appear publicly. You can change your display name from account details tab', 'paper-tipping-addons'); ?></small>
        </p>

        <p>
            <label for="artist_bio"><?php _e('Bio', 'paper-tipping-addons'); ?></label>
            <textarea name="artist_bio" id="artist_bio" rows="5"
                placeholder="<?php _e('Tell your fans about yourself', 'paper-tipping-addons'); ?>"><?php echo esc_textarea($artist_bio); ?></textarea>
            <small><?php _e('Write a short bio to introduce yourself to your fans', 'paper-tipping-addons'); ?></small>
        </p>

        <p>
            <input type="hidden" name="action" value="update_artist_profile" />
            <input type="hidden" name="profile_nonce" value="<?php echo wp_create_nonce('update_artist_profile_nonce'); ?>" />
            <button type="submit" class="button"><?php _e('Save Changes', 'paper-tipping-addons'); ?></button>
        </p>
    </form>
</div>
