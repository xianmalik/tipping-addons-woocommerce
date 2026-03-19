<p align="center">
    <h1 align="center">Paper Tipping Addons</h1>
    <h4 align="center">By: <a href="https://paperhouse.agency">PaperHouse Agency</a></h4>
</p>

<p align="center">
    <img alt="Version" src="https://img.shields.io/badge/Version-1.8.5-9ea1ab?style=for-the-badge&labelColor=3a3a3a&logo=git&logoColor=9EA1AB">
    <img alt="PHP" src="https://img.shields.io/badge/PHP-WordPress-9ea1ab?style=for-the-badge&labelColor=3a3a3a&logo=php&logoColor=9EA1AB">
    <img alt="WooCommerce" src="https://img.shields.io/badge/WooCommerce-Integration-9ea1ab?style=for-the-badge&labelColor=3a3a3a&logo=woocommerce&logoColor=9EA1AB">
</p>

<p align="center">
    A music artist marketplace and tipping system for WordPress — built on WooCommerce, JetEngine, and Elementor.
</p>

<p align="center">
    <a href="https://github.com/xianmalik/tipping-addons-woocommerce">
        <img alt="Repo Size" src="https://img.shields.io/github/repo-size/xianmalik/tipping-addons-woocommerce?color=%239ea1ab&label=SIZE&logo=square&style=for-the-badge&logoColor=D9E0EE&labelColor=3a3a3a"/></a>
    <a href="https://github.com/xianmalik/tipping-addons-woocommerce/stargazers">
        <img alt="Stars" src="https://img.shields.io/github/stars/xianmalik/tipping-addons-woocommerce?style=for-the-badge&logo=starship&color=9ea1ab&logoColor=D9E0EE&labelColor=3a3a3a"></a>
</p>

<hr />

<p align="center">
    <h2 align="center">Requirements</h2>
</p>

| Dependency | Purpose |
|---|---|
| WordPress | Core platform |
| WooCommerce | Cart, orders, product management |
| JetEngine | Dynamic content / meta fields |
| Elementor | Page builder (tip widget) |

<p align="center">
    <h2 align="center">Installation</h2>
</p>

```bash
# 1) Copy or symlink the plugin folder into your WordPress install
cp -r paper-tipping-addons /path/to/wp-content/plugins/

# 2) Activate from WordPress Admin → Plugins

# On activation the plugin automatically creates:
#   - The music_artist_vendor user role
#   - The wp_song_tips database table
#   - All required WooCommerce account rewrite endpoints
```

<p align="center">
    <h2 align="center">Features</h2>
</p>

**For Artists**
- **Registration** — dedicated form via `[artist_registration_form]` shortcode; users assigned the `music_artist_vendor` role
- **Song Management** — upload up to 5 songs per artist (cover image, preview clip, full MP3, full WAV)
- **Artist Profile** — bio and profile picture editable from the My Account dashboard
- **My Tips** — earnings summary per song with total tips received
- **Withdrawals** — withdraw earnings to a PayPal account (minimum $10); full withdrawal history shown

**For Fans / Customers**
- **Tip Widget** — Elementor widget placed on any song page; fans pick an amount and add it to the WooCommerce cart
- **Sticky Cart** — floating cart icon via `[paperhouse_cart_icon]` shortcode that updates live on tip addition
- **Song Downloads** — purchased/tipped songs available under **My Account → Song Downloads**

**For Admins**
- **Song Tips panel** — WP Admin → Song Tips shows every tip with song, amount, customer, and date
- **PayPal Settings** — Admin → Song Tips → PayPal Settings to configure OAuth credentials and sandbox mode

<p align="center">
    <h2 align="center">Shortcodes</h2>
</p>

| Shortcode | Description |
|---|---|
| `[artist_registration_form]` | Renders the artist registration form |
| `[paperhouse_cart_icon]` | Renders the sticky floating cart icon |

<p align="center">
    <h2 align="center">WooCommerce Endpoints</h2>
</p>

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

<p align="center">
    <h2 align="center">Project Structure</h2>
</p>

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
templates/                      # PHP partials — logic files set vars then include these
assets/
  css/                          # Scoped stylesheets (artist, frontend, woocommerce)
  js/                           # Vanilla JS (artist, frontend)
  images/
    cart-icon.png               # Sticky cart icon
    logo.webp                   # Site logo used in login form
```

<p align="center">
    <h2 align="center">Database</h2>
</p>

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

<p align="center">
    <h2 align="center">WordPress Options</h2>
</p>

| Option key | Description |
|---|---|
| `tipping_paypal_client_id` | PayPal app Client ID |
| `tipping_paypal_client_secret` | PayPal app Client Secret |
| `tipping_paypal_sandbox` | `'1'` = sandbox, `'0'` = live |

<p align="center">
    <h2 align="center">Development Notes</h2>
</p>

- No build system — pure PHP/CSS/JS, no `npm` or `composer`
- Path constants `PAPER_TIPPING_PATH` and `PAPER_TIPPING_URL` defined in the main file and used everywhere
- All HTML lives in `templates/` — logic files only set variables then `include` the template
- Reusable queries go through `ArtistQuery`, file uploads through `UploadHandler`
- Artists are capped at **5 songs** — enforced in both `add.php` and the AJAX handler
- Withdrawal minimum is **$10** — enforced server-side in `withdrawal.php`

<p align="center">
    <h2 align="center">License</h2>
</p>

<p align="center">
This project is open source and available under the <a href="LICENSE">MIT License</a>.
</p>

<p align="center">
    <h2 align="center">Author</h2>
</p>

<p align="center">
    <strong>Malik Zubayer Ul Haider</strong><br>
    <a href="https://xianmalik.com">Website</a> •
    <a href="https://github.com/xianmalik">GitHub</a> •
    <a href="https://linkedin.com/in/xianmalik">LinkedIn</a>
</p>
