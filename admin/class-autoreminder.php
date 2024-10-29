<?php 

namespace PISOL\REVIEW\ADMIN;

use PISOL\REVIEW\FRONT\Review as ReviewFront;

class AutoReminder{

    static $instance = null;

    static function get_instance(){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct()
    {
        add_action('pisol_review_send_auto_reminder', array($this, 'send_auto_reminder'), 10, 1);

        add_action('woocommerce_order_status_changed', array($this, 'order_status_changed'), 10, 3);

        add_action('wp_ajax_pisol_review_remove_scheduled_reminder', [$this, 'remove_scheduled_reminder_event']);
    }


    function send_auto_reminder($order_id){

        if(empty($order_id) || !is_numeric($order_id)){
            return;
        }

        if(!ReviewStats::autoReminderEnabled()){
            return;
        }

        $order = wc_get_order($order_id);
        
        if(!$order){
            return;
        }

        if(ReviewStats::isReviewClosed($order)){
            return;
        }

        $result = ReviewEmail::sendReviewEmail($order_id);
        ReviewStats::autoReviewReminderCountIncrement($order);
    }

    function order_status_changed($order_id, $old_status, $new_status){

        $set_auto_reminder_for_state = get_option('pisol_review_order_status', 'wc-completed');

        $set_auto_reminder_for_state = str_replace('wc-', '', $set_auto_reminder_for_state);

        $send_reminder_after = abs(get_option('pisol_review_reminder_delay', 2));

        $order = wc_get_order($order_id);
        
        if(!$order){
            return;
        }

        if(ReviewStats::isReviewClosed($order) || ReviewFront::review_possible($order) === false || ReviewFront::have_permission_to_send_review_email($order) === false || ReviewFront::is_blacklisted($order) === true){
            return;
        }

        if($new_status == $set_auto_reminder_for_state){
            $reminder_time = time() + ( $send_reminder_after * DAY_IN_SECONDS );
            /** check if the reminder is already scheduled  */
            if ( wp_next_scheduled( 'pisol_review_send_auto_reminder', array($order_id) ) ) {
                return;
            }
            
            wp_schedule_single_event( $reminder_time, 'pisol_review_send_auto_reminder', array($order_id) );
        }

    }

    static function remove_scheduled_reminder($order_id){
        $timestamp = wp_next_scheduled('pisol_review_send_auto_reminder', [$order_id]);
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'pisol_review_send_auto_reminder', [$order_id]);
            return true;
        }
        return false;
    }

    function remove_scheduled_reminder_event(){

        if(!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash ($_POST['_wpnonce'])), 'remove_scheduled_reminder')){
            wp_send_json_error(['message' => __('Invalid nonce', 'product-review-for-woocommerce')]);
        }

        if(!current_user_can('manage_woocommerce')){
            wp_send_json_error(['message' => __('You do not have permission to do this', 'product-review-for-woocommerce')]);
        }

        if(!isset($_POST['order_id'])){
            wp_send_json_error(['message' => __('Order id not found', 'product-review-for-woocommerce')]);
        }

        $order_id = absint( $_POST['order_id'] );

        if ( ! $order_id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid order id', 'product-review-for-woocommerce' ) ] );
        }

        $success = self::remove_scheduled_reminder($order_id);

        if($success){
            $order = wc_get_order($order_id);

            if(!$order){
                wp_send_json_error(['message' => __('Order not found', 'product-review-for-woocommerce')]);
            }

            ob_start();
            ReviewStats::review_stats($order);
            $review_stats = ob_get_clean();
            wp_send_json_success(array('message' => __('Review reminder email sent successfully.', 'product-review-for-woocommerce'), 'review_stats' => $review_stats, 'order_id' => $order_id));
            wp_send_json_success(['message' => __('Reminder removed successfully', 'product-review-for-woocommerce'), 'review_stats' => $review_stats, 'order_id' => $order_id]);
            
        }else{
            wp_send_json_error(['message' => __('Failed to remove reminder', 'product-review-for-woocommerce')]);
        }
    }
}