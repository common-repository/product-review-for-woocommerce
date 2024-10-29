<?php 

namespace PISOL\REVIEW\FRONT;

use PISOL\REVIEW\ADMIN\ReviewStats;

class ReviewForm{

    static $instance = null;

    static $endpoint = 'product-review';

    static function get_instance(){
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct(){
        add_action( 'init', array($this, 'add_endpoint') );
        add_filter('template_include', array($this, 'custom_endpoint_template'));

        add_action('wp_ajax_pisol_submit_review', array($this, 'save_review'));
        add_action('wp_ajax_nopriv_pisol_submit_review', array($this, 'save_review'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), PHP_INT_MAX);
    }

    function add_endpoint(){
        add_rewrite_endpoint( self::$endpoint,  EP_ROOT | EP_PAGES );
        if(empty( get_option('pisol_review_flush_endpoint', '') )){
            do_action( 'woocommerce_flush_rewrite_rules' );
            update_option('pisol_review_flush_endpoint', 'yes');
        }
    }

    function is_my_custom_endpoint() {
        global $wp_query;
        return isset($wp_query->query_vars[self::$endpoint]);
    }

    function custom_endpoint_template($template) {
        global $wp_query;
    
        if (isset($wp_query->query_vars[self::$endpoint])) {
            // Load your custom template
            $new_template = PISOL_REVIEW_PATH . 'templates/product-review.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }

    static function get_review_link($order_id){
        $endpoint_slug = self::$endpoint;
        $endpoint_url = home_url("/$endpoint_slug/");
        $order = wc_get_order($order_id);
        $order_key = $order->get_order_key();
        return add_query_arg(array('review_key' => $order_key), $endpoint_url);
    }

    static function get_unsubscribe_link($order_id){
        $endpoint_url = admin_url('admin-post.php');
        $order = wc_get_order($order_id);
        $order_key = $order->get_order_key();
        return add_query_arg(array('action' => 'pisol_review_unsubscribe', 'user' => $order_key), $endpoint_url);
    }


    static function is_valid_order($order_key){
        $order_id = wc_get_order_id_by_order_key( $order_key );
        $order = wc_get_order($order_id);
        if ($order) {
            return $order;
        } else {
            return false; // or handle the case where the order is not found
        }
    }

    function save_review(){

        if (isset($_POST['review_key']) && !empty($_POST['review_key'])) {
            $review_key = sanitize_text_field($_POST['review_key']);
        } else {
            $review_key = false;
        }
        
        $order = self::is_valid_order($review_key);
       
        $errors = self::validate($order);

        if(!empty($errors)){
            wp_send_json_error($errors);
        }

        $products = self::get_products($order);
        $item_count = 0;
        $total_rating = 0;
        $name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';
        foreach($products as $product){
            if(isset($_POST['rating'][$product]) && in_array($_POST['rating'][$product], array(1,2,3,4,5))){
                $rating = absint($_POST['rating'][$product]);
            }else{
                $rating = 5;
            }

            if(isset($_POST['review'][$product])){
                $review = wp_kses_post($_POST['review'][$product]);
            }else{
                $review = '';
            }

            $custom_review_parameters = ReviewForm::get_custom_review_parameters($product);
            $custom_review_parameters_ratings = array();
            foreach($custom_review_parameters as $parameter){
                if(isset($_POST['parameter_rating'][$product][$parameter]) && in_array($_POST['parameter_rating'][$product][$parameter], array(1,2,3,4,5))){
                    $custom_review_parameters_ratings[$parameter] = absint($_POST['parameter_rating'][$product][$parameter]);
                }
            }

            $verified = !empty(get_option('pisol_review_moderation', '1')) ? 0 : 1;
            ReviewStats::addReview($order, $product, $rating, $review, $verified, $name, $custom_review_parameters_ratings);
            $total_rating += $rating;
            $item_count++;
        }

        $average_rating = $total_rating / $item_count;
        ReviewStats::updateAverageRating($order, $average_rating);
        ReviewStats::closeReview($order);

        $success_message = self::get_form_success_message();
        wp_send_json_success(array('message' => sprintf('<h1 class="form-title">%s</h1>', $success_message)));
        
        exit;
    }

    static function get_products($order){
        $items = $order->get_items();
        $products = array();
        foreach($items as $item){
            $products[] = $item->get_product_id();
        }
        return array_unique($products);
    }

    static function validate($order){
        $errors = array();

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ($_POST['_wpnonce'])), 'pisol_submit_review' ) ) {
            $errors['general'] = __('Security check failed', 'product-review-for-woocommerce');
            return $errors;
        }

        if($order === false) {
            $errors['general-error'] = __('Invalid order', 'product-review-for-woocommerce');
            return $errors;
        }

        $products = self::get_products($order);

        $min_char_length = self::get_min_char_length();
        $max_char_length = self::get_max_char_length();

        foreach($products as $product){
            $product_obj = wc_get_product($product);
            
            if(!$product_obj->get_reviews_allowed()) continue;

            if(!isset($_POST['rating'][$product]) || !in_array($_POST['rating'][$product], array(1,2,3,4,5))){
                $errors['rating-error-'.$product] = self::get_rating_error_message();
            }

            if(self::isReviewRequired() && (!isset($_POST['review'][$product]) || empty($_POST['review'][$product]))){
                $errors['review-error-'.$product] = self::get_review_error_message();
            }

            if(!empty($min_char_length) && is_numeric($min_char_length) && isset($_POST['review'][$product])){
                if(mb_strlen($_POST['review'][$product], 'UTF-8') < $min_char_length){
                    $short_of = $min_char_length - mb_strlen($_POST['review'][$product], 'UTF-8');
                    $errors['review-error-'.$product] = sprintf(__('Minimum %d characters required, add %d characters more', 'product-review-for-woocommerce'), $min_char_length, $short_of);
                }
            }

            if(!empty($max_char_length) && is_numeric($max_char_length) && isset($_POST['review'][$product])){
                if(mb_strlen($_POST['review'][$product], 'UTF-8') > $max_char_length){
                    $excess_of = mb_strlen($_POST['review'][$product], 'UTF-8') - $max_char_length;
                    $errors['review-error-'.$product] = sprintf(__('Maximum %d characters allowed, remove %d characters', 'product-review-for-woocommerce'), $max_char_length, $excess_of);
                }
            }

            $custom_review_parameters = ReviewForm::get_custom_review_parameters($product);
            foreach($custom_review_parameters as $parameter){
                $required = get_post_meta($parameter, 'required', true);
                if($required){
                    if(!isset($_POST['parameter_rating'][$product][$parameter]) || (isset($_POST['parameter_rating'][$product][$parameter]) && !in_array($_POST['parameter_rating'][$product][$parameter], array(1,2,3,4,5)))){
                        $errors['parameter-rating-error-'.$product.'-'.$parameter] = self::get_rating_error_message();
                    }
                }
            }
        }
        return $errors;
    }

    static function get_form_title(){
        return get_option('pisol_review_form_title', 'Order review form');
    }

    static function get_form_description(){
        return get_option('pisol_review_form_description', 'Please review the following items purchased by you');
    }

    static function get_form_submit_text(){
        return get_option('pisol_review_form_submit', 'Submit Review');
    }

    static function get_form_review_placeholder(){
        return get_option('pisol_review_form_review_placeholder', 'Write your review');
    }

    static function get_form_success_message(){
        return get_option('pisol_review_form_success_msg', 'Review submitted successfully.');
    }

    static function get_rating_error_message(){
        return get_option('pisol_review_form_rating_error', 'Select a ration between 1 to 5');
    }

    static function get_review_error_message(){
        return get_option('pisol_review_form_review_error', 'Review is required for product');
    }

    static function isReviewRequired(){
        return !empty(get_option('pisol_review_required', 1)) ? true : false;
    }

    static function get_default_rating(){
        return get_option('pisol_review_default_rating', 5);
    }

    static function get_display_names($order){
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $options[] = $first_name.' '.$last_name;
        $options[] = $first_name;
        $options[] = $first_name.' '.substr($last_name, 0, 1);
        $options[] = __('Anonymous','product-review-for-woocommerce');
        return array_unique($options);
    }

    static function get_logo_url(){
        $image_id = get_option('pisol_review_form_logo', '');
        if($image_id){
            $image = wp_get_attachment_image_src($image_id, 'full');
            return isset($image[0]) ? $image[0] : '';
        }
        return '';
    }

    static function get_logo_alignment(){
        $alignment =  get_option('pisol_review_logo_alignment', 'center');

        if(in_array($alignment, array('center', 'left', 'right'))){
            return $alignment.'-align';
        }

        return 'center-align';
    }

    static function get_min_char_length(){
        return get_option('pisol_review_min_char_length', 50);
    }

    static function get_max_char_length(){
        return get_option('pisol_review_max_char_length', 2000);
    }

    function enqueue_scripts(){
        global $template;

        wp_localize_script('jquery', 'pisol_review_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'review_display' => get_option('pisol_review_loaded_review', 'append'),
        ));

        if ($this->is_my_custom_endpoint()) {
            global $wp_styles;
            global $wp_scripts;
    
            // Dequeue all styles
            if (!empty($wp_styles->queue)) {
                foreach ($wp_styles->queue as $style) {
                    wp_dequeue_style($style);
                    wp_deregister_style($style);
                }
            }

            if (!empty($wp_scripts->queue)) {
                foreach ($wp_scripts->queue as $script) {
                    if ($script !== 'jquery') {
                        wp_dequeue_script($script);
                        wp_deregister_script($script);
                    }
                }
            }
    

            wp_enqueue_style('pisol-review-style', PISOL_REVIEW_URL . 'public/css/style.css');
            wp_enqueue_script('pisol-review-script', PISOL_REVIEW_URL . 'public/js/script.js', array('jquery'), null, false);
        }

        if(get_option('pisol_review_load_more', 1) && function_exists('is_product') && is_product()){
            wp_enqueue_script('pisol-review-paging', PISOL_REVIEW_URL . 'public/js/review.js', array('jquery'), null, false);
        }
    
        
    }

    static function get_custom_review_parameters($product_id){
        $terms = get_the_terms( $product_id, 'product_cat' );
        $parameters = array();
        if ( !is_wp_error( $terms ) && !empty( $terms ) ) {
            foreach( $terms as $term ) {
                // Get review parameters for the category
                $review_parameters = get_term_meta( $term->term_id, 'review_parameters', true );
                if ( !empty( $review_parameters ) && is_array( $review_parameters ) ) {
                    $parameters = array_merge($parameters, $review_parameters);
                }
            }
        }

        return $parameters; 
    }
}