<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Static utility class for all artist/customer WooCommerce queries.
 * Eliminates duplicate get_artist_song_count / earnings queries scattered across includes/.
 */
class ArtistQuery
{
    private static $product_statuses = ['publish', 'draft', 'pending'];

    /**
     * Returns all WP_Post objects (products) authored by $user_id.
     */
    public static function get_artist_products(int $user_id, array $statuses = null): array
    {
        return get_posts([
            'post_type'      => 'product',
            'author'         => $user_id,
            'post_status'    => $statuses ?? self::$product_statuses,
            'posts_per_page' => -1,
        ]);
    }

    /**
     * How many products (songs) has this artist uploaded?
     */
    public static function get_song_count(int $user_id): int
    {
        return count(self::get_artist_products($user_id));
    }

    /**
     * Sum of all completed/processing order line-item totals for this artist's products.
     */
    public static function get_total_earnings(int $user_id): float
    {
        $product_ids = wp_list_pluck(self::get_artist_products($user_id), 'ID');

        if (empty($product_ids)) {
            return 0.0;
        }

        $total  = 0.0;
        $orders = wc_get_orders([
            'limit'  => -1,
            'status' => ['completed', 'processing'],
            'return' => 'ids',
        ]);

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item) {
                if (in_array($item->get_product_id(), $product_ids, true)) {
                    $total += $item->get_total();
                }
            }
        }

        return $total;
    }

    /**
     * Songs (products) that a customer has tipped, returned as [{name, tip_amount}].
     */
    public static function get_customer_tipped_songs(int $user_id): array
    {
        $tipped_songs = [];
        $orders = wc_get_orders([
            'customer' => $user_id,
            'limit'    => -1,
            'status'   => ['completed', 'processing'],
            'return'   => 'ids',
        ]);

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product) {
                    $tipped_songs[] = [
                        'name'       => $product->get_name(),
                        'tip_amount' => $item->get_total(),
                    ];
                }
            }
        }

        return $tipped_songs;
    }
}
