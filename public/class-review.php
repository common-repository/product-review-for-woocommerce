<?php

namespace PISOL\REVIEW\FRONT;

class Review{

    static $instance = null;

    static function get_instance(){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    static function get_review_template(){
        $template = get_option('pisol_review_template', '1');
        return $template;
    }

    static function get_description($comment){
        return $comment->comment_content ?? '';
    }

    static function get_profile_image($comment){
        if(!is_object($comment)) return;

        return get_avatar( $comment, apply_filters( 'woocommerce_review_gravatar_size', '60' ), '' );
    }

    static function get_customer_name($comment){
        return $comment->comment_author ?? '';
    }

    static function get_comment_date($comment){
        if(!is_object($comment)) return;

        return get_comment_date('M Y', $comment );
    }

    static function get_rating($comment){
        if(!is_object($comment)) return;

        return intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
    }

    static function get_rating_stars($comment){
        if(!is_object($comment)) return;

        return wc_get_rating_html( self::get_rating($comment) );
    }

    static function is_verified_buyer($comment){
        if(!is_object($comment)) return false;

        return wc_customer_bought_product( $comment->comment_author_email, $comment->user_id, $comment->comment_post_ID );
    }

    static function get_verified_tag($comment){
        if(self::is_verified_buyer($comment) && get_option('pisol_review_display_verified_tag', 1)){
            $img = PISOL_REVIEW_URL.'public/img/verified.svg';
            return sprintf('<span class="verified-tag"><img src="%s"> %s</span>', $img, __('Verified buyer', 'product-review-for-woocommerce'));
        }
        return ;
    }

    static function product_review_form(){
        return !empty(get_option('pisol_review_display_form', 0)) ? true : false;
    }

    static function get_min_review_count($product){
        return abs(get_option('pisol_review_show_review_tab', 0));
    }

    static function review_possible($order){
        if(!is_object($order)) return false;

        if($order->get_item_count() == 0) return false;

        foreach($order->get_items() as $item){
            $product = $item->get_product();
            if($product->get_reviews_allowed()){
                return true;
            }
        }

        return false;
    }

    static function have_permission_to_send_review_email($order){
        $ask_for_permission = get_option('pisol_review_reminder_permission', 0);

        if(empty($ask_for_permission)) return true;
        
        if(!is_object($order)) return false;

        $permission = $order->get_meta( '_review_email_accepted', true);

        return !empty($permission) ? true : false;
    }

    static function is_blacklisted($order){
        if(!is_object($order)) return false;

        $email = $order->get_billing_email();

        return BlackListDB::is_email_blacklisted($email);
        
    }

    static function get_star_rating_counts( $product_id ) {
        global $wpdb;
    
        // Query to count each rating (1 to 5 stars) for the given product
        $query = "
            SELECT meta_value AS rating, COUNT(meta_value) as count
            FROM {$wpdb->commentmeta} cm
            INNER JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
            WHERE c.comment_post_ID = %d
            AND c.comment_approved = 1
            AND cm.meta_key = 'rating'
            AND cm.meta_value IN (1, 2, 3, 4, 5)
            GROUP BY meta_value
            ORDER BY meta_value DESC
        ";
    
        // Execute the query with the product ID
        $results = $wpdb->get_results( $wpdb->prepare( $query, $product_id ), OBJECT_K );
    
        // Initialize the array for star counts
        $star_counts = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];
    
        // Populate the star counts from the query results
        foreach ( $results as $rating => $data ) {
            $star_counts[ intval( $rating ) ] = intval( $data->count );
        }
    
        return $star_counts;
    }
    
    static function get_custom_review_parameter_rating_average($review_parameter, $product_id){
        global $wpdb;

        $meta_key = "pi_custom_review_parameter_rating:{$review_parameter}";

        // Query to select all comment meta data related to a product
        $query = $wpdb->prepare("
                    SELECT AVG(CAST(meta_value AS DECIMAL(10,2))) AS average_rating
                    FROM {$wpdb->commentmeta}
                    WHERE meta_key = %s AND comment_id IN (
                        SELECT comment_ID
                        FROM {$wpdb->comments}
                        WHERE comment_post_ID = %d
                        AND comment_approved = 1
                    )
                ", $meta_key, $product_id);
    
        // Get the results from the database
        $average_rating = $wpdb->get_var( $query );
    
        return $average_rating;
    }

    static function get_custom_review_parameter_rating_count($review_parameter, $product_id){
        global $wpdb;

        $meta_key = "pi_custom_review_parameter_rating:{$review_parameter}";

        // Query to select all comment meta data related to a product
        $query = $wpdb->prepare("
                    SELECT COUNT(meta_value) AS average_rating
                    FROM {$wpdb->commentmeta}
                    WHERE meta_key = %s AND comment_id IN (
                        SELECT comment_ID
                        FROM {$wpdb->comments}
                        WHERE comment_post_ID = %d
                        AND comment_approved = 1
                    )
                ", $meta_key, $product_id);
    
        // Get the results from the database
        $average_rating = $wpdb->get_var( $query );
    
        return $average_rating;
    }
    
}