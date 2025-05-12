<nav class="site-navbar">
    <div class="navbar-container">
        <!-- Left side - Login and Register buttons -->
        <div class="navbar-left">
            <?php if (!is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(wp_login_url()); ?>" class="login-button">
                    <?php echo esc_html__('Login', 'tipping-addons-jetengine'); ?>
                </a>
                <a href="/artist-registration/" class="register-button">
                    <?php echo esc_html__('Register', 'tipping-addons-jetengine'); ?>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url(wp_logout_url()); ?>" class="login-button">
                    <?php echo esc_html__('Logout', 'tipping-addons-jetengine'); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Right side - Cart icon -->
        <div class="navbar-right">
            <a href="<?php echo esc_url($cart_url); ?>" class="cart-icon">
                <div class="cart-icon-wrapper">
                    <img src="<?php echo esc_url($icon_url); ?>" alt="Cart">
                    <?php if ($cart_count > 0) : ?>
                        <span class="cart-item-count"><?php echo esc_html($cart_count); ?></span>
                    <?php endif; ?>
                </div>
            </a>
        </div>
    </div>
</nav>

<style>
    .site-navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: transparent;
        z-index: 1000;
    }

    .navbar-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 2rem;
        width: 100%;
    }

    .navbar-left,
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .register-button {
        display: inline-flex;
        align-items: center;
        color: #0EA0E4;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .register-button:hover {
        color: #108dc7;
        text-decoration: none;
    }

    .login-button {
        display: inline-flex;
        flex: none;
        width: auto;
        align-items: center;
        justify-content: center;
        vertical-align: middle;
        text-align: center;
        white-space: nowrap;
        word-wrap: break-word;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-color: #0EA0E4;
        border: none;
        color: #fff;
        border-radius: 100px;
        cursor: pointer;
        padding: 5px 12px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: background-color 0.2s ease;
    }

    .login-button:hover {
        background-color: #108dc7;
        color: #fff;
        text-decoration: none;
    }

    .cart-icon {
        text-decoration: none;
        position: relative;
        display: flex;
        align-items: center;
    }

    .cart-icon-wrapper {
        position: relative;
    }

    .cart-icon-wrapper img {
        width: 24px;
        height: 24px;
    }

    .cart-item-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ff4444;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 12px;
        min-width: 16px;
        text-align: center;
    }

    @media (max-width: 768px) {
        .navbar-container {
            padding: 0.8rem 1rem;
        }

        .login-button {
            padding: 0.4rem 1rem;
        }
    }
</style>