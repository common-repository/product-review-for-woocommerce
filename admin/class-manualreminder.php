<?php 

namespace PISOL\REVIEW\ADMIN;

use PISOL\REVIEW\FRONT\Review as ReviewFront;

use PISOL\REVIEW\FRONT\BlackListDB;

class ManualReminder{

    static $instance = null;

    static function get_instance(){
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct(){
        add_filter( 'manage_edit-shop_order_columns', array($this,'add_review_actions_column') );
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array($this,'add_review_actions_column') ); //hpos

        add_action( 'manage_shop_order_posts_custom_column', array($this,'review_actions') );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', array($this,'review_actions_hpos'),10, 2 ); //hpos

        add_action('admin_enqueue_scripts', array($this,'order_page_style_js'));

        add_action('wp_ajax_pisol_review_send_review_reminder', array($this, 'send_review_reminder') );

        add_action('wp_ajax_pisol_review_add_to_blacklist', array($this, 'add_to_blacklist') );
        add_action('wp_ajax_pisol_review_remove_from_blacklist', array($this, 'remove_from_blacklist') );
    }

    function add_review_actions_column($columns){
        $columns['pisol_review_actions'] = __('Review actions', 'product-review-for-woocommerce');
        return $columns;
    }

    function review_actions($column){
        global $post;
        $order = wc_get_order( $post->ID );

        if(empty($order)) return;

        if ( 'pisol_review_actions' === $column ) {
            $actions = array();
            $actions = $this->add_manual_review_reminder_button($actions, $order);
            echo wc_render_action_buttons( $actions );
        }
    }

    function review_actions_hpos($column, $order){

        if(empty($order)) return;

        if ( 'pisol_review_actions' === $column ) {
            $actions = array();
            $actions = $this->add_manual_review_reminder_button($actions, $order);
            echo wc_render_action_buttons( $actions );
        }

    }

    function add_manual_review_reminder_button($actions, $order) {

        if (!ReviewStats::manualReminderEnabled()) {
            return $actions;
        }   

        $actions['pi_send_review_reminder'] = array(
            'url'       => wp_nonce_url(admin_url('admin-ajax.php?action=pisol_review_send_review_reminder&order_id=' . $order->get_id()), 'send_review_reminder'), 
            'name'      => __('Send review reminder', 'product-review-for-woocommerce'), 
            'action'    => 'review send_review_reminder'
        );

        if(ReviewStats::isReviewClosed($order)){
            $actions['pi_send_review_reminder']['action'] = 'review send_review_reminder disabled';
            $actions['pi_send_review_reminder']['name'] = __('Review is already submitted so you cant send more reminders', 'product-review-for-woocommerce');
        }

        if(ReviewFront::review_possible($order) === false){
            $actions['pi_send_review_reminder']['name'] = __('Review is disabled for all the product in this order', 'product-review-for-woocommerce');
            $actions['pi_send_review_reminder']['action'] = 'review send_review_reminder disabled';
        }

        if(ReviewFront::have_permission_to_send_review_email($order) === false){
            $actions['pi_send_review_reminder']['name'] = __('User did not provide the permission to send review email', 'product-review-for-woocommerce');
            $actions['pi_send_review_reminder']['action'] = 'review send_review_reminder review-reminder-without-concent';
        }

        if(ReviewFront::is_blacklisted($order) === true){
            $actions['pi_send_review_reminder']['name'] = __('Email id is in your blacklist', 'product-review-for-woocommerce');
            $actions['pi_send_review_reminder']['action'] = 'review send_review_reminder review-reminder-to-blacklisted-email';

            $actions['pi_remove_from_blacklist'] = array(
                'url'       => wp_nonce_url(admin_url('admin-ajax.php?action=pisol_review_remove_from_blacklist&order_id=' . $order->get_id()), 'remove_from_blacklist'), 
                'name'      => __('Remove from blacklist', 'product-review-for-woocommerce'), 
                'action'    => 'remove_from_blacklist'
            );
        }else{
            $actions['pi_add_to_blacklist'] = array(
                'url'       => wp_nonce_url(admin_url('admin-ajax.php?action=pisol_review_add_to_blacklist&order_id=' . $order->get_id()), 'add_to_blacklist'), 
                'name'      => __('Add to blacklist', 'product-review-for-woocommerce'), 
                'action'    => 'add_to_blacklist'
            );
        }

        
    
        return $actions;
    }

    function send_review_reminder(){

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'product-review-for-woocommerce')));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash ($_GET['_wpnonce'])), 'send_review_reminder')) {
            wp_send_json_error(array('message' => __('Nonce verification failed.', 'product-review-for-woocommerce')));
        }

        $order_id = isset($_GET['order_id']) && !empty($_GET['order_id']) ? absint($_GET['order_id']) : 0;

        if ($order_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid order ID.', 'product-review-for-woocommerce')));
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found.', 'product-review-for-woocommerce')));
            exit;
        }

        $result = ReviewEmail::sendReviewEmail($order_id);

        if($result){
            ReviewStats::manualReviewReminderCountIncrement($order);
            ob_start();
            ReviewStats::review_stats($order);
            $review_stats = ob_get_clean();
            wp_send_json_success(array('message' => __('Review reminder email sent successfully.', 'product-review-for-woocommerce'), 'review_stats' => $review_stats, 'order_id' => $order_id));
        }else{
            wp_send_json_error(array('message' => __('Failed to send review reminder email.', 'product-review-for-woocommerce')));
        }
    }

    function add_to_blacklist(){
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'product-review-for-woocommerce'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash ($_GET['_wpnonce'])), 'add_to_blacklist')) {
            wp_send_json_error( __('Nonce verification failed.', 'product-review-for-woocommerce'));
        }

        $order_id = isset($_GET['order_id']) && !empty($_GET['order_id']) ? absint($_GET['order_id']) : 0;

        if ($order_id <= 0) {
            wp_send_json_error(__('Invalid order ID.', 'product-review-for-woocommerce'));
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(__('Order not found.', 'product-review-for-woocommerce'));
            exit;
        }

        $email = $order->get_billing_email();
        BlackListDB::add_email_to_blacklist($email);
        wp_send_json_success( sprintf(__('Email id %s added to the blacklist', 'product-review-for-woocommerce'), $email) );
    }

    function remove_from_blacklist(){
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'product-review-for-woocommerce') );
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash ($_GET['_wpnonce'])), 'remove_from_blacklist')) {
            wp_send_json_error(__('Nonce verification failed.', 'product-review-for-woocommerce'));
        }

        $order_id = isset($_GET['order_id']) && !empty($_GET['order_id']) ? absint($_GET['order_id']) : 0;

        if ($order_id <= 0) {
            wp_send_json_error(__('Invalid order ID.', 'product-review-for-woocommerce'));
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(__('Order not found.', 'product-review-for-woocommerce'));
            exit;
        }

        $email = $order->get_billing_email();
        BlackListDB::remove_email_id($email);
        wp_send_json_success( sprintf(__('Email id %s removed from the blacklist', 'product-review-for-woocommerce'), $email) );
    }

    function order_page_style_js(){
        wp_enqueue_style(PISOL_REVIEW_NAME.'_bootstrap', plugins_url('css/style.css', __FILE__), array(), PISOL_REVIEW_VERSION);
        wp_enqueue_script(PISOL_REVIEW_NAME.'_script', plugins_url('js/script.js', __FILE__), array('jquery'), PISOL_REVIEW_VERSION, true);
    }
}