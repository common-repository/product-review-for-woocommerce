<?php

namespace PISOL\REVIEW\FRONT;

class MyAccount{
    static $instance = null;

    private $endpoint;

    static function get_instance(){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct(){

        $enabled = get_option('pisol_review_show_order_review_section', 1);

        if(!$enabled){
            return;
        }

        $slug = get_option('pisol_review_end_point', 'order-review');

        $this->endpoint = empty($slug) ? 'order-review' : sanitize_title($slug);

        add_filter('woocommerce_account_menu_items', array($this, 'myAccountSubLink'));

        add_action( 'init', array($this, 'add_endpoint') );

        add_action( "woocommerce_account_{$this->endpoint}_endpoint", array($this, 'endpoint_content') );
    }

    function myAccountSubLink($menu_links){
        $this->title = get_option('pisol_review_end_point_text', 'Review order');
       
        $menu_links = self::insertAfterKey($menu_links, 'orders', $this->endpoint, $this->title);

        return $menu_links;
    }

    function add_endpoint() {
        add_rewrite_endpoint( $this->endpoint, EP_PAGES );
        if(empty( get_option('pisol_review_flush_endpoint', '') )){
            do_action( 'woocommerce_flush_rewrite_rules' );
            update_option('pisol_review_flush_endpoint', 'yes');
        }
    }

    function endpoint_content($page_no) {
        $current_page    = empty( $page_no ) ? 1 : absint( $page_no );
        $state = get_option('pisol_review_order_status', 'wc-completed');
        $state = str_replace('wc-', '', $state);
		$customer_orders = wc_get_orders(
			
				array(
					'customer' => get_current_user_id(),
					'page'     => $current_page,
                    'status'   => $state,
					'paginate' => true,
                    'meta_key'     => '_pisol_review_submitted',
                    'meta_compare' => 'NOT EXISTS',
				)
			
		);

		wc_get_template(
			'partials/order-review.php',
			array(
				'current_page'    => absint( $current_page ),
				'customer_orders' => $customer_orders,
				'has_orders'      => 0 < $customer_orders->total,
				'wp_button_class' => wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '',
                'endpoint' => $this->endpoint
            ),
            '',
            plugin_dir_path( __FILE__ ) 
		);
    }

    static function insertAfterKey(array $array, $key, $newKey, $newValue) {
        // Find the position of the specified key
        $keys = array_keys($array);
        $pos = array_search($key, $keys);
        
        if ($pos === false) {
            // Key not found, return the original array
            return $array;
        }
        
        // Split the array into two parts
        $before = array_slice($array, 0, $pos + 1, true);
        $after = array_slice($array, $pos + 1, null, true);
        
        // Insert the new key-value pair
        $before[$newKey] = $newValue;
        
        // Merge the arrays and return
        return $before + $after;
    }
}