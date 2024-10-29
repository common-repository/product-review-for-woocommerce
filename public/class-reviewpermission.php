<?php 

namespace PISOL\REVIEW\FRONT;

class ReviewPermission{
    static $instance = null;

    static function get_instance(){
        if(is_null(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct()
    {
        $review_permission_needed = get_option('pisol_review_reminder_permission', 0);

        if(empty($review_permission_needed)) return; 

        add_action( 'woocommerce_checkout_terms_and_conditions', [$this, 'add_review_consent_checkbox' ] );  

        add_filter( 'woocommerce_checkout_posted_data', array($this, 'posted_data') );

        add_action( 'woocommerce_checkout_update_order_meta', [$this, 'save_review_consent_checkbox'], 10, 2 );

        add_action( 'woocommerce_checkout_process', [$this, 'validate_review_consent_checkbox'] );

    }

    function add_review_consent_checkbox() {
        $permission_is_required_field = empty(get_option('pisol_review_reminder_permission_required', 0)) ? false : true;

        $text = get_option('pisol_review_permission_text', 'I accept to receive review requests via email');
        
        woocommerce_form_field( '_review_email_accepted', array(
            'type'    => 'checkbox',
            'class'   => array('form-row'),
            'label'   => $text,
            'required' => $permission_is_required_field,
        ));
    }

    function posted_data($data){
        if ( isset( $_POST['_review_email_accepted'] ) ) {
            $data['_review_email_accepted'] =  1 ;
        } else {
            $data['_review_email_accepted'] =  0 ;
        }
        return $data;
    }

    function save_review_consent_checkbox( $order_id, $data ) {
        $order = wc_get_order( $order_id );

        if(empty($order)) return;

        if ( isset( $data['_review_email_accepted'] ) ) {
            $order->update_meta_data( '_review_email_accepted', 1 );
        } else {
            $order->update_meta_data( '_review_email_accepted', 0 );
        }
        $order->save();
    }

    function validate_review_consent_checkbox() {
        $permission_is_required_field = get_option('pisol_review_reminder_permission_required', 0);
        
        if ( !empty($permission_is_required_field) && !isset( $_POST['_review_email_accepted'] ) ) {
            wc_add_notice( __( 'Please provide your consent to receive a review emails', 'product-review-for-woocommerce' ), 'error' );
        }
    }

}