<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected variables (none — form is self-contained)
?>
<div class="artist-registration-form">
    <h2><?php _e('Register as Artist', 'paper-tipping-addons'); ?></h2>

    <div class="registration-message"></div>

    <form id="artist-registration-form" method="post">
        <p>
            <label for="artist_username"><?php _e('Username', 'paper-tipping-addons'); ?> <span class="required">*</span></label>
            <input type="text" name="artist_username" id="artist_username" placeholder="Choose a username" required />
        </p>

        <p>
            <label for="artist_email"><?php _e('Email', 'paper-tipping-addons'); ?> <span class="required">*</span></label>
            <input type="email" name="artist_email" id="artist_email" placeholder="Your email address" required />
        </p>

        <p class="password-field-container">
            <label for="artist_password"><?php _e('Password', 'paper-tipping-addons'); ?> <span class="required">*</span></label>
            <input type="password" name="artist_password" id="artist_password" placeholder="Create a password" required />
            <span class="password-toggle" onclick="togglePasswordVisibility('artist_password')">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </span>
        </p>

        <p class="password-field-container">
            <label for="artist_password_confirm"><?php _e('Confirm Password', 'paper-tipping-addons'); ?> <span class="required">*</span></label>
            <input type="password" name="artist_password_confirm" id="artist_password_confirm" placeholder="Confirm your password" required />
            <span class="password-toggle" onclick="togglePasswordVisibility('artist_password_confirm')">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </span>
        </p>

        <p>
            <label for="artist_first_name"><?php _e('First Name', 'paper-tipping-addons'); ?></label>
            <input type="text" name="artist_first_name" id="artist_first_name" placeholder="Your first name" />
        </p>

        <p>
            <label for="artist_last_name"><?php _e('Last Name', 'paper-tipping-addons'); ?></label>
            <input type="text" name="artist_last_name" id="artist_last_name" placeholder="Your last name" />
        </p>

        <p class="form-submit">
            <input type="hidden" name="action" value="register_artist" />
            <input type="hidden" name="artist_nonce" value="<?php echo wp_create_nonce('artist_registration_nonce'); ?>" />
            <button type="submit" class="register-button"><?php _e('Register', 'paper-tipping-addons'); ?></button>
        </p>

        <p class="login-link">
            <?php _e('Already have an account?', 'paper-tipping-addons'); ?>
            <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php _e('Log in', 'paper-tipping-addons'); ?></a>
        </p>
    </form>
</div>

<script>
    function togglePasswordVisibility(inputId) {
        var input = document.getElementById(inputId);
        input.type = (input.type === 'password') ? 'text' : 'password';
    }
</script>
