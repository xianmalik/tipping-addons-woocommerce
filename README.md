# Paper Tipping Addons

A custom WordPress plugin built for a music artist marketplace. Artists register, upload songs, receive tips from fans, and withdraw earnings via PayPal. Built on top of WooCommerce, JetEngine, and Elementor.

**Version:** 1.8.5
**Author:** Malik Zubayer
**Text Domain:** `paper-tipping-addons`

---

## Requirements

| Dependency | Purpose |
|---|---|
| WordPress | Core platform |
| WooCommerce | Cart, orders, product management |
| JetEngine | Dynamic content / meta fields |
| Elementor | Page builder (tip widget) |

---

## Installation

1. Copy or symlink the plugin folder into `wp-content/plugins/paper-tipping-addons/`
2. Activate from **WordPress Admin → Plugins**
3. Ensure WooCommerce, JetEngine, and Elementor are installed and active
4. On activation the plugin creates:
   - The `music_artist_vendor` user role
   - The `wp_song_tips` database table
   - All required WooCommerce account rewrite endpoints

---

## Features

### For Artists
- **Registration** — dedicated registration form via `[artist_registration_form]` shortcode; users are assigned the `music_artist_vendor` role
- **Song Management** — upload up to 5 songs per artist (cover image, preview clip, full MP3, full WAV)
- **Artist Profile** — bio and profile picture editable from the My Account dashboard
- **My Tips** — earnings summary per song with total tips received
- **Withdrawals** — withdraw earnings to a PayPal account (minimum $10); full withdrawal history shown

### For Fans / Customers
- **Tip Widget** — Elementor widget placed on any song page; fans pick an amount and add it to the WooCommerce cart
- **Sticky Cart** — floating cart icon shortcode `[musicbae_cart_icon]` that updates live on tip addition
- **Song Downloads** — purchased/tipped songs available under **My Account → Song Downloads**

### For Admins
- **Song Tips panel** — WP Admin → Song Tips shows every tip with song, amount, customer, and date
- **PayPal Settings** — Admin → Song Tips → PayPal Settings to configure PayPal OAuth credentials and sandbox mode

---

## Shortcodes

| Shortcode | Description |
|---|---|
| `[artist_registration_form]` | Renders the artist registration form |
| `[musicbae_cart_icon]` | Renders the sticky floating cart icon |

---

## WooCommerce Account Endpoints

| Endpoint | Description |
|---|---|
| `/my-account/artist-profile/` | Artist profile settings |
| `/my-account/artist-sales/` | Tips summary + withdrawal |
| `/my-account/manage-songs/` | Song list with edit/delete |
| `/my-account/add-song/` | Upload a new song |
| `/my-account/edit-song/` | Edit an existing song |
| `/my-account/delete-song/` | Delete confirmation |
| `/my-account/artist-withdrawal/` | Withdrawal form |
| `/my-account/song-downloads/` | Customer download history |

---

## Project Structure

```
paper-tipping-addons.php        # Plugin entry point — constants, boot, activation hook
includes/
  core/
    class-artist-query.php      # Static DB/query utility (earnings, song count, tipped songs)
    class-upload-handler.php    # Static file upload utility (audio + image with MIME validation)
  admin/
    admin-panel.php             # WP Admin menu: tips table + PayPal settings
    performance-fixes.php       # Admin-side performance tweaks
  artist/
    artist-vendor.php           # Main artist orchestrator (endpoints, menus, registration, profile)
    dashboard.php               # My Account dashboard (artist vs customer view)
    withdrawal.php              # Withdrawal form + AJAX processing
    songs/
      add.php                   # Add song handler
      edit.php                  # Edit song handler
      delete.php                # Delete song handler
      manage.php                # Song list handler
  frontend/
    sticky-cart.php             # Floating cart icon shortcode
    tip-widget.php              # Elementor tip widget
  woocommerce/
    cart-integration.php        # Add-to-cart AJAX, order processing, download email
    cart-filters.php            # Price hooks, cart item meta, order item labels
  integrations/
    paypal.php                  # PayPal OAuth2 + Payouts API
templates/
  admin/
    tips-table.php              # Admin tips list HTML
    paypal-settings.php         # PayPal settings form HTML
  artist/
    registration-form.php       # Artist signup form HTML
    profile-form.php            # Artist profile settings HTML
    sales-content.php           # Tips summary HTML
    withdrawal.php              # Withdrawal widget HTML
    dashboard-cards.php         # Artist dashboard cards HTML
    songs/
      add-form.php              # Add song form HTML
      edit-form.php             # Edit song form HTML
      delete-confirm.php        # Delete confirmation HTML
      manage-songs.php          # Song list HTML
  customer/
    dashboard-user.php          # Customer dashboard HTML
    song-downloads.php          # Customer downloads table HTML
  frontend/
    sticky-cart.php             # Cart icon HTML
    tip-widget.php              # Tip widget HTML
assets/
  css/
    artist/                     # Artist-side styles (my-account, vendor, withdrawal, delete-song)
    frontend/                   # Frontend styles (sticky-cart, tip-widget)
    woocommerce/                # WooCommerce overrides (login form)
  js/
    artist/                     # Artist JS (artist-vendor, withdrawal, artist-profile)
    frontend/                   # Frontend JS (sticky-cart, tip-widget)
  images/
    cart-icon.png               # Sticky cart icon
    logo.webp                   # Site logo used in login form
```

---

## WordPress Options

| Option key | Description |
|---|---|
| `tipping_paypal_client_id` | PayPal app Client ID |
| `tipping_paypal_client_secret` | PayPal app Client Secret |
| `tipping_paypal_sandbox` | `'1'` = sandbox, `'0'` = live |

---

## Database

**Table:** `wp_song_tips`

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `song_id` | bigint | WooCommerce product ID of the tipped song |
| `tip_amount` | decimal | Amount tipped |
| `customer_name` | varchar | Buyer's billing name |
| `customer_id` | bigint | WP user ID (0 = guest) |
| `order_id` | bigint | WooCommerce order ID |
| `song_mp3` | varchar | MP3 download URL |
| `song_wav` | varchar | WAV download URL |
| `created_at` | datetime | Tip timestamp |

---

## Development Notes

- No build system — pure PHP/CSS/JS, no `npm` or `composer`
- Path constants `PAPER_TIPPING_PATH` and `PAPER_TIPPING_URL` are defined in the main file and used everywhere (no `__FILE__` chains)
- All HTML lives in `templates/` — logic files only set variables then `include` the template
- Reusable queries go through `ArtistQuery`, file uploads through `UploadHandler`
- Artists are capped at **5 songs** — enforced in both `add.php` and the AJAX handler
- Withdrawal minimum is **$10** — enforced server-side in `withdrawal.php`
