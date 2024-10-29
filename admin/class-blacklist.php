<?php
namespace PISOL\REVIEW\ADMIN;

use PISOL\REVIEW\FRONT\BlackListDB;

use PISOL\REVIEW\FRONT\ReviewForm;

class BlackList{

    private $settings = array();

    private $active_tab;

    private $this_tab = 'blacklist';

    private $tab_name;

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

        $this->settings = [];
        

        if($this->this_tab == $this->active_tab){
            add_action(PISOL_REVIEW_NAME.'_tab_content', array($this,'tab_content'));
        }

        add_action(PISOL_REVIEW_NAME.'_tab', array($this,'tab'),2);

        add_action('wp_ajax_pisol_review_add_black_listed_email', array($this,'add_email_to_blacklist'));

        add_action('wp_ajax_pisol_review_remove_black_listed_email', array($this,'remove_email_from_blacklist'));

        add_action('admin_post_pisol_review_unsubscribe', array($this, 'add_to_blacklist'));

    }

    function register_settings(){   

        foreach($this->settings as $setting){
            FormMaker::register_setting( $this->setting_key, $setting);
        }
    
    }

    function tab(){
        $this->tab_name = __('Blacklist', "product-review-for-woocommerce");
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        ?>
        <a class=" pi-side-menu  <?php echo esc_attr($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url(admin_url( 'admin.php?page='.$page.'&tab='.$this->this_tab )); ?>">
        <span class="dashicons dashicons-editor-ol"></span> <?php echo esc_html( $this->tab_name ); ?> 
        </a>
        <?php
    }

    function tab_content(){
       $page_no = isset($_GET['pageno']) ? sanitize_text_field($_GET['pageno']) : 1;
       $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
       
        $count = BlackListDB::get_email_count($search);
       
       
       $per_page = 40;
       $pages = ceil($count / $per_page);
       $next_page = $page_no + 1;
       if($next_page > $pages){
           $next_page = 0;
       }

       $prev_page = $page_no - 1;
        if($prev_page < 1){
            $prev_page = 0;
        }

        
        if(!empty($search)){
            $emails = BlackListDB::search_blacklisted_emails($search, $page_no, $per_page);
        }else{
            $emails = BlackListDB::get_blacklisted_emails($page_no, $per_page);
        }

        include_once PISOL_REVIEW_PATH.'admin/partials/blacklist.php';
    }

    function add_email_to_blacklist(){
        if(!check_ajax_referer( 'add-email-to-blacklist', '_wpnonce', false )){
            wp_send_json_error( 'Nonce verification failed' );
            return;
        }

        if(!current_user_can('manage_options')){
            wp_send_json_error('You do not have permission to add email to blacklist');
        }

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if(!empty($email)){
            BlackListDB::add_email_to_blacklist($email);
        }else{
            wp_send_json_error('Invalid email id');
        }
        wp_send_json_success();
    }

    function remove_email_from_blacklist(){
        if(!check_ajax_referer( 'delete-email-from-blacklist', '_wpnonce', false )){
            wp_send_json_error( 'Nonce verification failed' );
            return;
        }

        if(!current_user_can('manage_options')){
            wp_send_json_error('You do not have permission to remove email from blacklist');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if($id > 0){
            BlackListDB::remove_email_by_id($id);
        }else{
            wp_send_json_error('Invalid email id');
        }
        wp_send_json_success();
    }

    function add_to_blacklist(){
        if(!isset($_GET['user']) || empty($_GET['user'])){
            wp_die('Invalid link');
        }

        $order_key = sanitize_text_field($_GET['user']);

        $order = ReviewForm::is_valid_order($order_key);

        if(!is_object($order) || empty($order)){
            wp_die('Invalid order');
        }

        $email = $order->get_billing_email();

        BlackListDB::add_email_to_blacklist($email);

        wp_die('You are now unsubscribed from review reminder email list');
    }

}


