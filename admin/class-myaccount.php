<?php
namespace PISOL\REVIEW\ADMIN;

class MyAccount{

    private $settings = array();

    private $active_tab;

    private $this_tab = 'review_orders';

    private $tab_name = "My Account > Order Review";

    private $setting_key = 'review_orders_my_account';

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
                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('My Account &rarr; Order Review','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('this section adds a review order section in the My account page of the customer','product-review-for-woocommerce')),

                array('field'=>'pisol_review_show_order_review_section', 'default'=> 1, 'type'=>'switch', 'label'=>__('Enable','product-review-for-woocommerce'),'desc'=>__('this adds a Order review section in the My Accounts page of the customer','product-review-for-woocommerce')),

                array('field'=>'pisol_review_end_point', 'label'=>__('Review order page url slug','product-review-for-woocommerce'), 'desc'=>__('URL slug of the Review order page, this field cant have blank space','product-review-for-woocommerce'), 'type'=>'text', 'default'=> 'order-review' , 'sanitize_callback' => [__CLASS__,'validatePageSlug']),

                array('field'=>'pisol_review_end_point_text', 'label'=>__('Review order','product-review-for-woocommerce'), 'desc'=>__('Review order tab text','product-review-for-woocommerce'), 'type'=>'text', 'default'=> 'Review order'),
            );
        

        if($this->this_tab == $this->active_tab){
            add_action(PISOL_REVIEW_NAME.'_tab_content', array($this,'tab_content'));
        }

        add_action(PISOL_REVIEW_NAME.'_tab', array($this,'tab'),2);
        
        $this->register_settings();

        add_action( 'update_option_pisol_review_show_order_review_section', [$this, 'value_changed'], 10, 2 );
        add_action( 'update_option_pisol_review_end_point', [$this, 'value_changed'], 10, 2 );

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
        $this->tab_name = __("My Account > Order Review", "product-review-for-woocommerce");
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        ?>
        <a class=" pi-side-menu  <?php echo esc_attr($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url(admin_url( 'admin.php?page='.$page.'&tab='.$this->this_tab )); ?>">
        <span class="dashicons dashicons-align-full-width"></span> <?php echo esc_html( $this->tab_name ); ?> 
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

    /**
     * we want to flush out url when endpoint changes 
     */
    function value_changed( $old_value, $new_value ) {
        // Compare the old and new values
        if ( $old_value !== $new_value ) {
            delete_option('pisol_review_flush_endpoint');
        }
    }

    static function validatePageSlug($input){
        return sanitize_title($input);
    }
}


