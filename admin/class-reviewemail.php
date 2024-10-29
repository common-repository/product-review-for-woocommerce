<?php 

namespace PISOL\REVIEW\ADMIN;

use PISOL\REVIEW\FRONT\ReviewForm as FrontReviewForm;
class ReviewEmail{

    private $order_id;
    private $order;
    private $to;
    private $subject;
    private $message;
    private $headers;
    private $short_codes = array();

    function __construct($order_id){
        $this->order_id = $order_id;
        $this->order = wc_get_order($order_id);

        if(!$this->order){
            return;
        }

        add_action('pisol_review_email_header', array($this, 'header'),10, 1);
        add_action('pisol_review_email_footer', array($this, 'footer'));
        add_filter('woocommerce_email_styles', [$this, 'email_styles']);

        $this->headers = array('Content-Type: text/html; charset=UTF-8');
        $this->to = $this->get_to();
        $this->subject = $this->get_subject();
        $this->message = $this->get_message();
        $this->from = get_option('pisol_review_reminder_email_from_address', '');
        $this->from_name = get_option('pisol_review_reminder_email_from', get_option('blogname'));
        $this->reply_to = get_option('pisol_review_reminder_email_reply_address', '');

    }

    function sendEmail(){
        \WC_Emails::instance();
        $email = new \WC_Email();
        add_filter( 'woocommerce_email_from_address', [$this, 'custom_woocommerce_email_from_address'], 10, 1 );
        add_filter( 'woocommerce_email_from_name', [$this, 'custom_woocommerce_email_from_name'], 10, 1 );

        if(!empty($this->reply_to) && is_email($this->reply_to)){
            $this->headers[] = 'Reply-To: '.$this->reply_to;
        }

        if(!empty(get_option('pisol_review_unsubscribe',1))){
            $unsubscribe_link = $this->get_unsubscribe_link();
            $this->headers[] = 'List-Unsubscribe: <'.$unsubscribe_link.'>';
        }

		return $email->send( $this->to, $this->subject, $this->message, $this->headers, array());
    }

    static function sendReviewEmail($order_id){
        $obj = new self($order_id);
        return $obj->sendEmail();
    }

    function get_to(){
        return $this->order->get_billing_email();
    }

    function short_codes($message){
        $unsubscribe_link_text = get_option('pisol_review_unsubscribe_link_text', "Click to unsubscribe");
        if(empty($this->short_codes)){
                $this->short_codes = array(
                    '{customer_name}' => $this->order->get_billing_first_name().' '.$this->order->get_billing_last_name(),
                    '{review_link}' => '<a href="'.esc_url($this->get_review_link()).'" class="button">'.esc_html__('Review','product-review-for-woocommerce').'</a>',
                    '{site_title}' => get_option('blogname'),
                    '{order_no}' => '#'.$this->order->get_order_number(),
                    '{order_date}' => $this->order->get_date_created()->date('Y-m-d'),
                    '{WooCommerce}' => '<a href="https://www.piwebsolution.com" target="_blank">PI WebSolution</a>',
                    '{products}'=> $this->get_products(),
                    '{unsubscribe}' => sprintf('<a href="%s" class="unsubscribe-link">%s</a>', $this->get_unsubscribe_link(), $unsubscribe_link_text)
                );
        }

        return str_replace(array_keys($this->short_codes), array_values($this->short_codes), $message);
    }

    function get_subject(){
        $subject = get_option('pisol_review_reminder_email_subject', '[{site_title}] Share your experience with us');
        $subject = $this->short_codes($subject);
        return $subject;
    }

    function get_review_link(){
        return FrontReviewForm::get_review_link($this->order_id);
    }

    public function get_message() {
        $email_heading = get_option( 'pisol_review_reminder_email_heading', 'How was your experience?' );

        $message = get_option('pisol_review_reminder_email_body', "Dear {customer_name},\n\nThank you for shopping with us. We hope you are enjoying your purchase. We would love to hear your thoughts on your recent purchase. Please take a moment to share your experience with us.\n\nThank you for your time.\n\nBest regards,\n{site_title}");

        $message .= '<div class="review-button-container"><a href="'.esc_url($this->get_review_link()).'" class="button review-button">'.esc_html__('Review now', 'product-review-for-woocommerce').'</a></div>';

        $message = $this->short_codes($message);


		// Buffer.
		ob_start();

		do_action( 'pisol_review_email_header', $email_heading, null );

		echo wp_kses_post(wpautop( wptexturize( $message ) )); // WPCS: XSS ok.

		do_action( 'pisol_review_email_footer', null );

		// Get contents.
		$message = ob_get_clean();
        $message = $this->short_codes($message);
		return $message;
	}



    function header($email_heading){
        wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
    }

    public function footer() {
        if(!empty(get_option('pisol_review_unsubscribe',1))){
            $unsubscribe_text = get_option('pisol_review_reminder_email_unsubscribe_text', "We value your feedback, but if you'd prefer not to receive further review reminders, please {unsubscribe}");
        }else{
            $unsubscribe_text = '';
        }

		wc_get_template( 'email-footer.php', [
            'unsubscribe_text' => get_option('pisol_review_reminder_email_unsubscribe_text', ''),
        ], '', plugin_dir_path( __FILE__ ).'/partials/' );
	}

    function email_styles($css){
        $custom_css = "
            #template_header_image img{
                height:70px;
                width:auto;
            }

            .review-button-container{
                text-align: center;
                margin-top: 20px;
                margin-bottom:20px;
            }

            .review-button{
                background-color: #007cba;
                color: #ffffff;
                text-decoration: none;
                padding: 10px 20px;
                display: inline-block;
                border-radius: 3px;
            }

            .product-image{
                width: 50px;
                height: auto;
            }

            .product-name-col{
                vertical-align: middle;
                padding:6px !important;
            }

            .product-thumbnail-col{
                padding:6px !important;
            }

            .product-detail{
                margin-top:20px;
                margin-bottom:20px;
                border-collapse: collapse;
            }

            .review-star{
                text-decoration: none;
                color: #007cba;
                font-size: 20px;
            }

            .unsubscribe-link{
                color:#f00;
            }
        ";
        return $css.$custom_css;
    }

    function get_products(){
        $products = $this->order->get_items();
        $product_list = '';
        $row = '';
        foreach($products as $product){
            $product_obj = $product->get_product();

            if($product_obj->is_type('variation')){
                $product_parent = wc_get_product($product_obj->get_parent_id());

                if(!$product_parent->get_reviews_allowed()) continue;
            }else{
                if(!$product_obj->get_reviews_allowed()) continue;
            }

            $row .= '<tr>';
            $row .= '<td class="product-thumbnail-col">'.wp_kses_post( $product_obj->get_image( 'thumbnail', ['class'=>'product-image'] )).'</td>';
            $row .= '<td class="product-name-col">';
            $row .= wp_kses_post( $product->get_name() );
            $row .= '<br>';
            $row .= '<a href="'.esc_url($this->get_review_link()).'" class="review-star">&#9733;&#9733;&#9733;&#9733;&#9733;</a>';
            $row .= '</td>';
            $row .= '</tr>';
        }
        if(!empty($row)){
            $product_list = '<table class="product-detail"><tbody>'.$row.'</tbody></table>';
        }
        return $product_list;
    }

    function custom_woocommerce_email_from_address( $from_email ) {
        return $this->from; // Change this to your desired From email address
    }
    
    function custom_woocommerce_email_from_name( $from_name ) {
        return $this->from_name; // Change this to your desired From name
    }

    function get_unsubscribe_link(){
        return FrontReviewForm::get_unsubscribe_link($this->order_id);
    }

}