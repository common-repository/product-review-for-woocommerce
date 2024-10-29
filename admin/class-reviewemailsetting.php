<?php
namespace PISOL\REVIEW\ADMIN;

class ReviewEmailSetting{

    private $settings = array();

    private $active_tab;

    private $this_tab = 'review_email';

    private $tab_name = "Review Email";

    private $setting_key = 'review_email';

    static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }   
    

    function __construct(){

        if (isset($_GET['tab'])) {
            $this->active_tab = sanitize_text_field($_GET['tab']);
        } else {
            $this->active_tab = 'default';
        }

        $this->settings = array(
                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Review reminder email','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('This section allows you to configure review reminder email related setting','product-review-for-woocommerce')),

                array('field'=>'pisol_review_reminder_email_from', 'label'=>__('"From" name','product-review-for-woocommerce'), 'type'=>'text', 'default'=> get_option( 'blogname' )),

                array('field'=>'pisol_review_reminder_email_from_address', 'label'=>__('"From" address','product-review-for-woocommerce'), 'type'=>'text', 'default'=> '', 'desc' => __('Some hosting provider do not allow to modify from email id so if you have issue in sending email then make this field blank as this can be causing the issue. Make sure the from email id has same domain name as your website','product-review-for-woocommerce')),

                array('field'=>'pisol_review_reminder_email_reply_address', 'label'=>__('"Reply-To" address','product-review-for-woocommerce'), 'type'=>'text', 'default'=> ''),

                array('field'=>'pisol_review_reminder_email_subject', 'label'=>__('Email subject','product-review-for-woocommerce'), 'type'=>'text', 'default'=> "[{site_title}] Share your experience with us"),

                array('field'=>'pisol_review_reminder_email_heading', 'label'=>__('Email heading','product-review-for-woocommerce'), 'type'=>'text', 'default'=> "How was your experience?"),

                array('field'=>'pisol_review_reminder_email_body', 'label'=>__('Email content','product-review-for-woocommerce'), 'type'=>'review_editor', 'default'=> "Dear {customer_name},\n\nThank you for shopping with us. We hope you are enjoying your purchase. We would love to hear your thoughts on your recent purchase. Please take a moment to share your experience with us.\n\n{products}\n\nThank you for your time.\n\nBest regards,\n{site_title}", 'desc' => __('Available placeholders:<br> {customer_name}, {review_link}, {site_title}, {order_no}, {order_date}, {products}','product-review-for-woocommerce')),

                array('field'=>'pisol_review_unsubscribe', 'default'=> 1, 'type'=>'switch', 'label'=>__('Give unsubscribe link in emails','product-review-for-woocommerce'),'desc'=>__('Once user will click on this link his email id will be added in the blacklist so he will not be send and review reminder emails','product-review-for-woocommerce')),

                array('field'=>'pisol_review_reminder_email_unsubscribe_text', 'label'=>__('Unsubscribe message','product-review-for-woocommerce'), 'type'=>'review_editor', 'default'=> "We value your feedback, but if you'd prefer not to receive further review reminders, please {unsubscribe}", 'desc' => __('Available placeholders:<br> {unsubscribe}','product-review-for-woocommerce')),

                array('field'=>'pisol_review_unsubscribe_link_text', 'label'=>__('Unsubscribe link text','product-review-for-woocommerce'), 'type'=>'text', 'default'=> "Click to unsubscribe", 'desc'=>__('This text will be used in the link added by {unsubscribe} short code','product-review-for-woocommerce')),

                array('field'=>'pisol_review_reminder_email_footer', 'label'=>__('Email footer','product-review-for-woocommerce'), 'type'=>'text', 'default'=> "This email was sent by {site_title}."),

            );
        

        if($this->this_tab == $this->active_tab){
            add_action(PISOL_REVIEW_NAME.'_tab_content', array($this,'tab_content'));
        }

        add_action(PISOL_REVIEW_NAME.'_tab', array($this,'tab'),2);
        
        $this->register_settings();

    }

    function register_settings(){   
        
        foreach($this->settings as $setting){
            FormMaker::register_setting( $this->setting_key, $setting);
        }
    
    }

    function delete_settings(){
        foreach($this->settings as $setting){
            delete_option( $setting['field'] );
        }
    }

    function tab(){
        $this->tab_name = __("Review Email", "product-review-for-woocommerce");
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        ?>
        <a class=" pi-side-menu  <?php echo esc_attr($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url(admin_url( 'admin.php?page='.$page.'&tab='.$this->this_tab )); ?>">
        <span class="dashicons dashicons-email"></span> <?php echo esc_html( $this->tab_name ); ?> 
        </a>
        <?php
    }

    function tab_content(){
       ?>
        <form method="post" action="options.php"  class="pisol-setting-form">
        <?php settings_fields( $this->setting_key ); ?>
        <?php
            foreach($this->settings as $setting){
                new FormMaker($setting, $this->setting_key);
            }
        ?>
        <input type="submit" name="submit" id="submit" class="btn btn-primary btn-md my-3" value="<?php echo esc_attr__('Save Changes','product-review-for-woocommerce'); ?>">
        </form>
       <?php
    }
}


