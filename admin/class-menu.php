<?php
namespace PISOL\REVIEW\ADMIN;

class Menu{

    public $menu;

    static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    function __construct(){
        add_action( 'admin_menu', array($this,'plugin_menu') );
    }

    function plugin_menu(){

        $require_capability = Access::getCapability();
        
        $menu = add_menu_page(
            __('Review reminder','product-review-for-woocommerce'), 
            __('Review reminder','product-review-for-woocommerce'), 
            $require_capability, 
            'product-review-for-woocommerce',  
            array($this, 'menu_option_page'), 
            PISOL_REVIEW_URL.'/admin/img/pi.svg' ,
            6  
        );

        add_action("load-".$menu, array($this,'menu_page_style_js'));
 
    }

    function menu_option_page(){
        if(function_exists('settings_errors')){
            settings_errors();
        }
        ?>
        <div class="bootstrap-wrapper">
        <div class="container mt-2">
            <div class="row">
                    <div class="col-12">
                        <div class='bg-dark border-bottom'>
                        <div class="row">
                            <div class="col-12 col-sm-2 py-2">
                            <a href="https://www.piwebsolution.com/" target="_blank"><img class="img-fluid ml-2" src="<?php echo esc_url(PISOL_REVIEW_URL); ?>admin/img/pi-web-solution.svg"></a>
                            </div>
                            <div class="col-12 col-sm-10 d-flex text-center small">
                                
                            </div>
                        </div>
                        </div>
                    </div>
            </div>
            <div class="row">
                <div class="col-12">
                <div class="bg-light px-3">
                    <div class="row">
                        <div class="col-12 col-md-2 px-0 border-right">
                        <?php do_action(PISOL_REVIEW_NAME.'_tab'); ?>
                        </div>
                        <div class="col">
                        <?php do_action(PISOL_REVIEW_NAME.'_tab_content'); ?>
                        </div>
                        <?php do_action(PISOL_REVIEW_NAME.'_promotion'); ?>
                    </div>
                </div>
                </div>
            </div>
        </div>
        </div>
        <?php
    }

    function menu_page_style_js(){
        wp_enqueue_style(PISOL_REVIEW_NAME.'_bootstrap', plugins_url('css/style.css', __FILE__), array(), PISOL_REVIEW_VERSION);
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-ui',  plugins_url('css/jquery-ui.css', __FILE__));
    }    

    
}