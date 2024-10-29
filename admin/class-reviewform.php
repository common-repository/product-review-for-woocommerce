<?php 

namespace PISOL\REVIEW\ADMIN;

class ReviewForm{
    private $settings = array();

    private $active_tab;

    private $this_tab = 'review_form';

    private $tab_name = "Review Form";

    private $setting_key = 'review_form';

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
                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Review form','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('This section allows you to control order review form setting','product-review-for-woocommerce')),

                array('field'=>'pisol_review_required', 'default'=> 1, 'type'=>'switch', 'label'=>__('Make review as required field','product-review-for-woocommerce'),'desc'=>__('Make review as required field','product-review-for-woocommerce')),

                array('field'=>'pisol_review_default_rating', 'label'=>__('Default rating','product-review-for-woocommerce'), 'desc'=>__('When user first opens the form this rating will be selected by default','product-review-for-woocommerce'), 'type'=>'select', 'default'=> 'none', 'value'=> array('none'=>__('None','product-review-for-woocommerce'), 1=>1, 2=>2, 3=>3, 4=>4, 5=>5)),

                array('field'=>'pisol_review_form_logo', 'type'=>'image','label'=>__('Logo shown on top of review form','product-review-for-woocommerce'),'desc'=>__('This logo will be shown on the top of the review form','product-review-for-woocommerce')),

                array('field'=>'pisol_review_logo_alignment', 'type'=>'select','label'=>__('Logo alignment','product-review-for-woocommerce'), 'desc'=>__('Select the alignment of the logo','product-review-for-woocommerce'), 'default'=> 'center', 'value'=> array('center'=>__('Center','product-review-for-woocommerce'), 'left'=>__('Left','product-review-for-woocommerce'), 'right'=>__('Right','product-review-for-woocommerce'))),

                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Review length','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('Configure review length in characters','product-review-for-woocommerce')),

                array('field'=>'pisol_review_min_char_length', 'default'=> 50, 'type'=>'number', 'label'=>__('Minimum character length','product-review-for-woocommerce'),'desc'=>__('Minimum character required in review','product-review-for-woocommerce'), 'step'=> 1, 'min'=> 0),

                array('field'=>'pisol_review_max_char_length', 'default'=> 2000, 'type'=>'number', 'label'=>__('Maximum character length','product-review-for-woocommerce'),'desc'=>__('Minimum character limit for review user cant add more then this many character','product-review-for-woocommerce'), 'step'=> 1, 'min'=> 0),

                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Labels','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('Set different text shown on the review form','product-review-for-woocommerce')),

                array('field'=>'pisol_review_form_title', 'default'=> 'Order review form', 'type'=>'text', 'label'=>__('Title text shown above the review form','product-review-for-woocommerce'),'desc'=>__('Make review as required field','product-review-for-woocommerce')),

                array('field'=>'pisol_review_form_description', 'default'=> 'Please review the following items purchased by you', 'type'=>'text', 'label'=>__('Small description','product-review-for-woocommerce'),'desc'=>__('Small description is shown just below the title','product-review-for-woocommerce')),

                array('field'=>'pisol_review_form_submit', 'default'=> 'Submit Review', 'type'=>'text', 'label'=>__('Submit button text','product-review-for-woocommerce'),'desc'=>__('Text shown on the submit button','product-review-for-woocommerce')),

                array('field'=>'pisol_review_form_review_placeholder', 'default'=> 'Write your review', 'type'=>'text', 'label'=>__('Review placeholder','product-review-for-woocommerce'),'desc'=>__('Text shown inside the review textarea field','product-review-for-woocommerce')),

                array('field'=>'pisol_review_form_success_msg', 'default'=> 'Review submitted successfully.', 'type'=>'text', 'label'=>__('Review submitted message','product-review-for-woocommerce'),'desc'=>__('This message is shown when the form is submitted successfully','product-review-for-woocommerce')),

                array('field'=>'pisol_review_select_name_text', 'default'=> 'Display name', 'type'=>'text', 'label'=>__('Display name','product-review-for-woocommerce'),'desc'=>__('This is shown next to the name options user can select to show along with there review','product-review-for-woocommerce')),


                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Error messages','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('Different error messages shown to the customer','product-review-for-woocommerce')),

                array('field'=>'pisol_review_form_rating_error', 'default'=> 'Select a ration between 1 to 5', 'type'=>'text', 'label'=>__('Rating not selected error message','product-review-for-woocommerce'),'desc'=>__('Error message shown when user does not select a rating','product-review-for-woocommerce')),

                array('field'=>'pisol_review_form_review_error', 'default'=> 'Review is required for product', 'type'=>'text', 'label'=>__('Review not entered error message','product-review-for-woocommerce'),'desc'=>__('Error message shown when user does not enter a review','product-review-for-woocommerce')),

                

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
        $this->tab_name = __("Review Form", "product-review-for-woocommerce");
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        ?>
        <a class=" pi-side-menu  <?php echo esc_attr($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url(admin_url( 'admin.php?page='.$page.'&tab='.$this->this_tab )); ?>">
        <span class="dashicons dashicons-welcome-widgets-menus"></span> <?php echo esc_html( $this->tab_name ); ?> 
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