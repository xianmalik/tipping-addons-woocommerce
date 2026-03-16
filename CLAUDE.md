# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called **JetEngine Tipping Addons** (v1.8.5) — a music artist marketplace and tipping system that integrates WooCommerce, JetEngine, and Elementor. Artists register, upload songs (max 5), receive tips, and withdraw earnings via PayPal.

## Development Setup

This is a pure PHP WordPress plugin with no build system. To develop:

1. Install in WordPress: symlink or copy the plugin directory to `wp-content/plugins/`
2. Activate from WordPress Admin → Plugins
3. Required plugins: WooCommerce, JetEngine, Elementor

No `composer.json`, `package.json`, or test suite exists.

## Architecture

**Entry point**: `tipping-addons-jetengine.php` — Singleton class `TippingAddonsJetEngine` that:
- Validates dependencies (WooCommerce, JetEngine, Elementor) before loading
- Creates `music_artist_vendor` user role on activation
- Creates `wp_song_tips` database table on activation
- Loads all modules via `include_once`

**Module layout**:
```
includes/
  admin/admin-panel.php          # WP admin dashboard showing all tips
  frontend/sticky-cart.php       # Floating cart icon shortcode [sticky_cart]
  woocommerce/cart-integration.php  # Cart price hooks, tip tracking
  widgets/tip-widget.php         # Elementor widget (+/- tip amount UI)
  integrations/paypal.php        # PayPal OAuth + Payouts API
  users/
    artist-vendor.php            # Registration, AJAX handlers
    dashboard.php                # Artist dashboard page
    withdrawal.php               # Withdrawal form + AJAX
    songs/{add,edit,delete,manage}.php  # Song/product CRUD
  templates/                     # PHP partials for dashboard UI
assets/css/                      # Scoped stylesheets per feature
assets/js/                       # Vanilla JS for form interactions
```

## Key Patterns

**WordPress options** (stored in `wp_options`):
- `tipping_product_id` — WooCommerce product ID used as the tip product
- `tipping_paypal_client_id` / `tipping_paypal_client_secret`
- `tipping_paypal_sandbox` — boolean toggle

**Custom DB table**: `wp_song_tips` — columns: `song_id`, `amount`, `customer`, `date`

**AJAX endpoints**: All prefixed `wp_ajax_` / `wp_ajax_nopriv_` — see `artist-vendor.php` and `withdrawal.php`

**Asset enqueuing**: Done per-module in `wp_enqueue_scripts` hooks with capability checks to avoid loading everywhere

**Song limits**: Artists are capped at 5 songs; enforced in `songs/add.php`
