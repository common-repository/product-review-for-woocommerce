<?php

namespace PISOL\REVIEW\FRONT;

class ReviewDisplay{

    static $instance = null;

    public $review_template = 'default';

    static function get_instance(){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct(){

        $this->review_template = Review::get_review_template();

        add_filter( 'comments_template', [$this,'custom_review_template'], PHP_INT_MAX, 1 );

        add_filter('wc_get_template', [$this,'custom_single_review_template'], PHP_INT_MAX, 5);

        add_action('wp_enqueue_scripts', [$this,'enqueue_scripts']);

        add_filter( 'woocommerce_product_tabs', [$this, 'disable_reviews_if_less_than_ten'], PHP_INT_MAX );

        add_action('wp_ajax_load_reviews_by_page', [$this, 'load_reviews_by_page']);
        add_action('wp_ajax_nopriv_load_reviews_by_page', [$this, 'load_reviews_by_page']);

        add_filter('option_page_comments', [$this, 'set_comments_paging_on']);
    }

    function custom_review_template($template){

        if($this->review_template == '1'){
            $template = PISOL_REVIEW_PATH . 'templates/single-product-reviews-template-1.php';
        }
        
        return $template;
    }

    function enqueue_scripts(){
        if($this->review_template == '1'){
            wp_enqueue_style('pisol-review-style', PISOL_REVIEW_URL . 'public/css/template-1.css', [], PISOL_REVIEW_VERSION);
        }
    }

    function custom_single_review_template($template, $template_name, $args, $template_path, $default_path){
        if($template_name == 'single-product/review.php'){
            if($this->review_template == '1'){
                $template = PISOL_REVIEW_PATH . 'templates/review-template-1.php';
            }
        }
       return $template;
    }

    function disable_reviews_if_less_than_ten( $tabs ) {
        global $product;
    
        // Get the ID of the current product
        $product_id = $product->get_id();
    
        // Get the count of approved reviews
        $review_count = get_comments_number($product_id);
        
        $min_count = Review::get_min_review_count($product);
        // Check if the review count is less than 10
        if ( $review_count < $min_count ) {
            unset( $tabs['reviews'] ); // Remove the reviews tab
        }
    
        return $tabs;
    }

    function load_reviews_by_page() {
        $page = intval($_POST['page']);
        $product_id = intval($_POST['product_id']);
        $per_page = get_option('comments_per_page',3);
        // Adjust query arguments for the paginated reviews
        $args = array(
            'post_id' => $product_id,
            'status' => 'approve',
            'orderby' => 'comment_date_gmt',
			'order'   => 'ASC',
        );
    
        // Fetch the reviews using wp_list_comments
        ob_start();
        wp_list_comments(apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments', 'per_page' => $per_page, 'page' => $page ) ) , get_comments($args));
        $reviews = ob_get_clean();

        $prev = $page - 1;
        $next = $page + 1;

        if($prev < 1){
            $prev = 0;
        }

        if(empty($reviews)){
            $next = 0;
        }
    
        wp_send_json_success(['reviews' => $reviews, 'prev' => $prev, 'next' => $next ]);
    }

    function set_comments_paging_on($value){
        if(get_option('pisol_review_load_more', 1)){
            return 1;
        }

        return $value;
    }

    static function show_review_count_stats($product){
        //check if it is product id or product object
        if(is_numeric($product)){
            $product = wc_get_product($product);
        }

        if(!$product) return;

        wc_get_template( 'review-count-stats.php', [
            'average' => $product->get_average_rating(),
            'reviews_count' => $product->get_review_count(),
            'count' => $product->get_review_count(),
        ], '', plugin_dir_path( __FILE__ ).'/partials/' );
    }

    static function show_custom_parameter_review($product){
        //check if it is product id or product object
        if(is_numeric($product)){
            $product = wc_get_product($product);
        }

        if(!$product) return;

        $custom_review_parameters = ReviewForm::get_custom_review_parameters($product->get_id());

        wc_get_template( 'custom-parameter-review.php', [
            'product' => $product,
            'custom_review_parameters' => $custom_review_parameters,
        ], '', plugin_dir_path( __FILE__ ).'/partials/' );
    }

    static function show_rating_stats($product){
        //check if it is product id or product object
        if(is_numeric($product)){
            $product = wc_get_product($product);
        }

        if(!$product) return;

        $each_rating_count = Review::get_star_rating_counts($product->get_id());

        wc_get_template( 'rating-stats.php', [
            'each_rating_count' => $each_rating_count,
            'count' => $product->get_review_count(),
        ], '', plugin_dir_path( __FILE__ ).'/partials/' );
    }
}