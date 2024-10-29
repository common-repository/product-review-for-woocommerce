<?php

namespace PISOL\REVIEW\ADMIN;

use PISOL\REVIEW\FRONT\ReviewForm;

class ReviewStats{

    static $instance = null;

    static function get_instance(){
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct(){
        add_filter( 'manage_edit-shop_order_columns', array($this,'add_review_details_column') );
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array($this,'add_review_details_column') ); //hpos

        add_action( 'manage_shop_order_posts_custom_column', array($this,'review_details') );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', array($this,'review_details_hpos'),10, 2 ); //hpos
    }

    function add_review_details_column($columns){
        $columns['pisol_review_details'] = __('Review', 'product-review-for-woocommerce');
        return $columns;
    }

    function review_details($column){
        global $post;
        $order = wc_get_order( $post->ID );

        if(empty($order)) return;

        if ( 'pisol_review_details' === $column ) {
            $this->print_review_details($order);
        }

    }

    function review_details_hpos($column, $order){

        if(empty($order)) return;

        if ( 'pisol_review_details' === $column ) {
            $this->print_review_details($order);
        }
        
    }

    function print_review_details($order){
        echo '<div id="review-stats-'.esc_attr($order->get_id()).'">';
        self::review_stats($order);
        echo '</div>';
    }

    static function review_stats($order){
        
        if(self::isReviewClosed($order)){
            $average_rating = (float)self::getAverageRating($order);
            $percent = ($average_rating / 5) * 100;
            /* translators: %d: Average rating */
            echo '<div class="star-rating" style="'.esc_attr("--rating: $percent%;").'" title="'.esc_attr(sprintf(__('Average ration: %d','product-review-for-woocommerce'), $average_rating)).'"></div><br>';
            return;
        }

        $manual_count = self::manualReviewReminderCount($order);
        $auto_count = self::autoReviewReminderCount($order);
        if($manual_count + $auto_count > 0){
            echo '<span class="pisol_review_count review_submitted" style="color:red">'.esc_html__('Review pending','product-review-for-woocommerce').'</span><br>';
        }else{
            echo '<span class="pisol_review_count review_submitted" style="color:red">'.esc_html__('No reminder sent','product-review-for-woocommerce').'</span><br>';
        }

        if($manual_count > 0 && self::manualReminderEnabled()){
            /* translators: %d: Manual reminder request count */
            echo '<span class="pisol_review_count manual_count">'.esc_html(sprintf(__('Manual reminder send: %d', 'product-review-for-woocommerce'), esc_html($manual_count))).'</span><br>';
        }
        
        if(self::autoReminderEnabled()){
            if($auto_count > 0){
                /* translators: %d: How many auto reminder where send */
                echo '<span class="pisol_review_count auto_count">'.esc_html(sprintf(__('Auto reminder send: %d', 'product-review-for-woocommerce'), esc_html($auto_count))).'</span><br>';
            }else{
                $auto_reminder_date = self::autoReminderScheduledOn($order);
                if($auto_reminder_date){
                    /* translators: %s: Date on which the auto reminder email will be send */
                    echo '<span class="pisol_review_count auto_count">'.esc_html(sprintf(__('Auto reminder scheduled on: %s', 'product-review-for-woocommerce'), esc_html($auto_reminder_date))).'</span> <a class="remove-reminder" data-order_id="'.esc_attr($order->get_id()).'" data-nonce="'.esc_attr(wp_create_nonce('remove_scheduled_reminder')).'"  title="'.esc_attr(__('Remove scheduled reminder','product-review-for-woocommerce')).'">Remove</a><br>';
                }else{
                    echo '<span class="pisol_review_count auto_count">'.esc_html__('Auto reminder not scheduled','product-review-for-woocommerce').'</span><br>';
                }
            }
        }
    }

    static function addReview($order, $product_id, $rating, $review, $verified = 1, $name = '', $custom_review_parameters_ratings = array()){

        $available_names = ReviewForm::get_display_names( $order );

        $review_data = array(
            'comment_post_ID' => $product_id,
            'comment_author' => !empty($name) && in_array($name, $available_names) ? $name : $order->get_billing_first_name(),
            'comment_author_email' => $order->get_billing_email(),
            'comment_content' => $review,
            'comment_approved' => $verified,
            'comment_meta' => array(
                'rating' => $rating,
                'order_id' => $order->get_id(),
                'product_id' => $product_id,
            ),
        );

        foreach($custom_review_parameters_ratings as $key => $value){
            $review_data['comment_meta']['pi_custom_review_parameter_rating:'.$key] = $value;
        }
        
        $comment_id = wp_insert_comment($review_data);
        if ( is_wp_error( $comment_id ) ) {
            return false;
        }
        return true;
    }


    static function manualReviewReminderCountIncrement($order){
        $count = $order->get_meta('_pisol_manual_review_reminder_count', true);
        $count = $count ? $count : 0;
        $count++;
        $order->update_meta_data('_pisol_manual_review_reminder_count', $count);
        $order->save();
    }

    static function manualReviewReminderCount($order){
        $count = $order->get_meta('_pisol_manual_review_reminder_count', true);
        $count = $count ? $count : 0;
        return $count;
    }

    static function autoReviewReminderCountIncrement($order){
        $count = $order->get_meta('_pisol_auto_review_reminder_count', true);
        $count = $count ? $count : 0;
        $count++;
        $order->update_meta_data('_pisol_auto_review_reminder_count', $count);
        $order->save();
    }

    static function autoReviewReminderCount($order){
        $count = $order->get_meta('_pisol_auto_review_reminder_count', true);
        $count = $count ? $count : 0;
        return $count;
    }

    static function closeReview($order){
        $order->update_meta_data('_pisol_review_submitted', true);
        $order->save();
    }

    static function isReviewClosed($order){
        return $order->get_meta('_pisol_review_submitted', true) ? true : false;
    }

    static function updateAverageRating($order, $rating){
        $order->update_meta_data('_pisol_review_average_rating', $rating);
        $order->save();
    }

    static function getAverageRating($order){
        return $order->get_meta('_pisol_review_average_rating', true);
    }

    static function autoReminderScheduledOn($order){
        $timestamp = wp_next_scheduled('pisol_review_send_auto_reminder', [$order->get_id()]);
        $date_format  =  get_option( 'date_format' );
        return $timestamp ? gmdate($date_format, $timestamp) : false;
    }

    static function manualReminderEnabled(){
        return !empty(get_option('pisol_review_manual_reminder', 1)) ? true : false;
    }

    static function autoReminderEnabled(){
        return !empty(get_option('pisol_review_automatic_reminder', 0)) ? true : false;
    }
}