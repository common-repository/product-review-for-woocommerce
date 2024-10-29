<?php

namespace PISOL\REVIEW\ADMIN;

class ReviewDisplay{
    private $settings = array();

    private $active_tab;

    private $this_tab = 'review_display';

    private $tab_name = "Review Display";

    private $setting_key = 'review_display';

    static $instance = null;

    static function get_instance(){
        if(is_null(self::$instance)){
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
                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Review Display Template','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('This allows you to change the review display style template','product-review-for-woocommerce')),

                array('field'=>'pisol_review_template', 'default'=> '1', 'type'=>'select', 'label'=>__('Review template', 'product-review-for-woocommerce'),'desc'=>__('Select the template you will like to use for the product page review display','product-review-for-woocommerce'),'value'=>array('default'=>__('Your theme default template','product-review-for-woocommerce'), '1'=>__('Custom template 1','product-review-for-woocommerce'))),

                array('field'=>'pisol_review_show_review_tab', 'default'=> 0, 'type'=>'number', 'label'=>__('Show review tab when the review count is more then','product-review-for-woocommerce'),'desc'=>__('This allow you to hide review tab on product page if the no. of review for that product is less then this number, E.g: if you set it to 10, then if certain product has less then 10 review then it will not show the review tab on that product','product-review-for-woocommerce'), 'min'=> 0, 'step'=> 1    ),

                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Review Display Setting','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('This setting will only work if you have selected our custom template in above setting ','product-review-for-woocommerce')),

    
                array('field'=>'pisol_review_display_form', 'default'=> 0, 'type'=>'switch', 'label'=>__('Show review form on product page','product-review-for-woocommerce'),'desc'=>__('Show review form on the product page','product-review-for-woocommerce')),
                array('field'=>'pisol_review_display_verified_tag', 'default'=> 1, 'type'=>'switch', 'label'=>__('Display verified buyer tag','product-review-for-woocommerce'), 'desc'=>__('There will show a tag below the review submitted by a verified buyer','product-review-for-woocommerce')),

                array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Load more review by ajax (Only works with our Review template)','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('Below setting allow you to give option to load more review by ajax','product-review-for-woocommerce')),

                array('field'=>'pisol_review_load_more', 'default'=> 1, 'type'=>'switch', 'label'=>__('Enable load more option','product-review-for-woocommerce'), 'desc'=>__('When customer will click on this Local more review button it will load more reviews','product-review-for-woocommerce')),

                array('field'=>'pisol_review_loaded_review', 'default'=> 'append', 'type'=>'select', 'label'=>__('Loaded review should','product-review-for-woocommerce'),'desc'=>__('Loaded review should be appended to existing review or replace the present review','product-review-for-woocommerce'), 'value'=>array('append'=>__('Append below the existing review','product-review-for-woocommerce'), 'replace'=>__('Replace existing review','product-review-for-woocommerce'))),

                array('field'=>'pisol_review_load_more_text', 'default'=> 'Load more review', 'type'=>'text', 'label'=>__('Load more review','product-review-for-woocommerce'),'desc'=>__('this text is shown inside the load more review button','product-review-for-woocommerce')),

                array('field'=>'pisol_review_previous', 'default'=> 'Previous', 'type'=>'text', 'label'=>__('Previous','product-review-for-woocommerce'),'desc'=>__('this text is shown inside the previous button','product-review-for-woocommerce')),

                array('field'=>'pisol_review_next', 'default'=> 'Next', 'type'=>'text', 'label'=>__('Next','product-review-for-woocommerce'),'desc'=>__('this text is shown inside the next button','product-review-for-woocommerce')),
                
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
        $this->tab_name = __("Review Display", "product-review-for-woocommerce");
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        ?>
        <a class=" pi-side-menu  <?php echo esc_attr($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url(admin_url( 'admin.php?page='.$page.'&tab='.$this->this_tab )); ?>">
        <span class="dashicons dashicons-star-filled"></span> <?php echo esc_html($this->tab_name); ?> 
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