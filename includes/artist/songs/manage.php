<?php
if (!defined('ABSPATH')) {
    exit;
}

class ManageSongsHandler
{
    public function handle_manage()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id    = get_current_user_id();
        $max_songs  = 5;
        $products   = ArtistQuery::get_artist_products($user_id);
        $song_count = count($products);

        include PAPER_TIPPING_PATH . 'templates/artist/songs/manage-songs.php';
    }
}
