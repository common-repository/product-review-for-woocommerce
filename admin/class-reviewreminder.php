<?php
namespace PISOL\REVIEW\ADMIN;

class ReviewReminder{

    private $settings = array();

    private $active_tab;

    private $this_tab = 'default';

    private $tab_name = "Review Reminder";

    private $setting_key = 'review_reminder';

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
                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Auto reminders for customer reviews','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('Configure the plugin to automatically or manually send follow-up emails (reminders) that gather product reviews.','product-review-for-woocommerce')),

                array('field'=>'pisol_review_automatic_reminder', 'default'=> 0, 'type'=>'switch', 'label'=>__('Automatic reminder','product-review-for-woocommerce'),'desc'=>__('Enable automatic follow-up emails to invite reviews. ','product-review-for-woocommerce')),

                array('field'=>'pisol_review_reminder_delay', 'label'=>__('Reminder delay (days)','product-review-for-woocommerce'), 'desc'=>__('If automatic reminder option is enabled then, reminder email will be send X days after the order state changes to configured order state','product-review-for-woocommerce'), 'type'=>'number', 'default'=> 2, 'min'=> 0),

                array('field'=>'pisol_review_order_status', 'default'=>'wc-completed', 'type'=>'select', 'label'=>__('Order status', 'product-review-for-woocommerce'),'desc'=>__('Auto reminder will be send X days after the order changes to this state, and once the order has changed to this state then only you can trigger a manual reminder email as well','product-review-for-woocommerce'),'value'=>array('wc-completed'=>__('Completed','product-review-for-woocommerce'), 'wc-processing'=>__('Processing','product-review-for-woocommerce'))),

                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('When you can send reminder for review','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('Under which conditions you can send reminder for review','product-review-for-woocommerce')),

                array('field'=>'pisol_review_manual_reminder', 'default'=> 1, 'type'=>'switch', 'label'=>__('Manual reminder','product-review-for-woocommerce'),'desc'=>__('Allows you to manually send review reminder email','product-review-for-woocommerce')),

                array('field'=>'pisol_review_moderation', 'default'=> 1, 'type'=>'switch', 'label'=>__('Moderation of reviews','product-review-for-woocommerce'),'desc'=>__('This allow you to moderate the review submitted by your verified customer, this only work for the review submitted through this plugin','product-review-for-woocommerce')),

                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Review reminder permission','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('Take user permission on checkout page to send review reminder email','product-review-for-woocommerce')),

                array('field'=>'pisol_review_reminder_permission', 'default'=> 0, 'type'=>'switch', 'label'=>__('Ask for permission','product-review-for-woocommerce'),'desc'=>__('If enabled customer will be shown a checkbox on the checkout page to take permission for Reminder reminder email, if user has not given permission then review reminder will not be send for that order','product-review-for-woocommerce')),

                array('field'=>'pisol_review_reminder_permission_required', 'default'=> 0, 'type'=>'switch', 'label'=>__('Make review permission must for placing order','product-review-for-woocommerce'),'desc'=>__('Customer has to provide this permission without this permission user will not be able to place an order','product-review-for-woocommerce')),

                array('field'=>'pisol_review_permission_text', 'label'=>__('I accept to receive review requests via email ','product-review-for-woocommerce'), 'desc'=>__('Text shown next to the permission checkbox on the checkout page','product-review-for-woocommerce'), 'type'=>'text', 'default'=> 'I accept to receive review requests via email'),
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
        $this->tab_name = __("Review Reminder", "product-review-for-woocommerce");
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        ?>
        <a class=" pi-side-menu  <?php echo esc_attr($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url(admin_url( 'admin.php?page='.$page.'&tab='.$this->this_tab )); ?>">
        <span class="dashicons dashicons-star-filled"></span> <?php echo esc_html( $this->tab_name ); ?> 
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


