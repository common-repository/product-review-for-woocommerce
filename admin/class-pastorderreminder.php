<?php
namespace PISOL\REVIEW\ADMIN;

use PISOL\REVIEW\FRONT\Review as ReviewFront;

class PastOrderReminder{

    private $settings = array();

    private $active_tab;

    private $this_tab = 'past-order-reminder';

    private $tab_name;

    private $setting_key = 'past_order_reminder';

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

        $this->settings = [
            array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Past order review reminder email','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('Using this you can configure plugin to send review reminder email for the past orders that where placed before you installed this plugin in your site','product-review-for-woocommerce')),

            array('field'=>'pisol_review_enable_past_order_reminder', 'default'=> '0', 'type'=>'switch', 'label'=>__('Enable', 'product-review-for-woocommerce'),'desc'=>__('Send reminder for past orders placed before installation of this plugin','product-review-for-woocommerce')),

            array('field'=>'pisol_review_from_date', 'default'=> '', 'type'=>'text', 'label'=>__('From date', 'product-review-for-woocommerce'),'desc'=>__('Send reminder for orders placed after this date','product-review-for-woocommerce'), 'sanitize_callback' => [__CLASS__,'validateDate']),
            array('field'=>'pisol_review_to_date', 'default'=> '', 'type'=>'text', 'label'=>__('To date', 'product-review-for-woocommerce'),'desc'=>__('Send reminder for orders placed after this date','product-review-for-woocommerce') , 'sanitize_callback' => [__CLASS__,'validateDate']),


            array('field'=>'pisol_review_reminder_rate', 'default'=> '24', 'type'=>'number', 'label'=>__('No. of review reminder email send per day', 'product-review-for-woocommerce'),'desc'=>__('This helps you in controlling the no. of review reminder email that will be send per day so you don\'t over load your email server','product-review-for-woocommerce'), 'step'=> 1, 'min'=> 1, 'max'=> 96),

            array('field'=>'color-setting', 'class'=> 'bg-primary text-light', 'class_title'=>'text-light font-weight-light h4', 'label'=>__('Reminder send as per your above date range','product-review-for-woocommerce'), 'type'=>'setting_category', 'desc'=>__('Below is the stats showing how much reminders are send and how many are remaining for your above date range','product-review-for-woocommerce')),

            array('field'=>'pisol_review_past_reminder_stats', 'default'=> '0', 'type'=>'review_past_reminder_stats', 'label'=>__('Enable', 'product-review-for-woocommerce'),'desc'=>__('Send reminder for past orders placed before installation of this plugin','product-review-for-woocommerce')),
        ];
        

        if($this->this_tab == $this->active_tab){
            add_action(PISOL_REVIEW_NAME.'_tab_content', array($this,'tab_content'));
        }

        add_action(PISOL_REVIEW_NAME.'_tab', array($this,'tab'),2);

        add_filter( 'cron_schedules',  array($this,'add_dynamic_cron_interval') );

        $this->register_settings();

        add_action('wp_loaded', array($this, 'set_cron_job'));

        add_action('pisol_review_send_past_order_reminder', array($this, 'send_past_order_reminder'));
        
    }

    function set_cron_job(){
        $enabled = get_option('pisol_review_enable_past_order_reminder', 0);
        $key = self::rage_key();
        $order_list = self::order_list();
        $order_send = get_option($key.'_send', array());
        $order_remaining = array_diff($order_list, $order_send);
        $completed = get_option($key.'_completed', false);
        if(empty($enabled) || empty($order_remaining) || empty($order_list) || !empty($completed)){
            wp_clear_scheduled_hook( 'pisol_review_send_past_order_reminder' );
            return;
        }else{
            if ( ! wp_next_scheduled( 'pisol_review_send_past_order_reminder' ) ) {
                wp_schedule_event( time(), 'pisol_review_frequency', 'pisol_review_send_past_order_reminder' );
            }
        }
        
    }

    static function order_list(){
        $from = get_option('pisol_review_from_date', '');
        $to = get_option('pisol_review_to_date', '');
        $key = self::rage_key();
        $order_list = get_option($key, array());
        if(empty($order_list)){
            $order_list = self::get_order_list($from, $to);
            update_option($key, $order_list);
        }
        return $order_list;
    }

    static function rage_key(){
        $from_date = get_option('pisol_review_from_date', '');
        $to_date = get_option('pisol_review_to_date', '');
        return md5($from_date.$to_date);
    }

    static function get_order_list($from_date, $to_date){
        $state = get_option('pisol_review_order_status', 'wc-completed');
        
        $args = array(
            'limit' => -1,
            'return' => 'ids',
            'status' => $state,
            'meta_key' => '_pisol_review_past_order_reminder_send',
            'meta_compare' => 'NOT EXISTS',
        );
        
        if(!empty($from_date) && !empty($to_date)){
            $smaller = strtotime($from_date) < strtotime($to_date) ? $from_date : $to_date;
            $larger = strtotime($from_date) > strtotime($to_date) ? $from_date : $to_date;
            $args['date_created'] = $smaller.'...'.$larger;
        }

        $orders = wc_get_orders($args);
        return $orders;
    }

    function send_past_order_reminder(){
        $key = self::rage_key();
        $order_list = self::order_list();
        $order_send = get_option($key.'_send', array());
        $order_remaining = array_diff($order_list, $order_send);

        if(empty($order_remaining)){
            update_option('pisol_review_enable_past_order_reminder', 0);
            update_option($key.'_completed', true);            
            return;
        }

        //get the first order from the $order_list 
        $order_id = array_shift($order_remaining);
        if(!empty($order_id)){
           $order_send[] = $order_id;
           update_option($key.'_send', $order_send);
            $order = wc_get_order($order_id);
        
            if(!$order){
                return;
            }

            $manual_reminder_count = (int) ReviewStats::manualReviewReminderCount($order);
            $auto_reminder_count = (int) ReviewStats::autoReviewReminderCount($order);

            if(ReviewStats::isReviewClosed($order) || ReviewFront::review_possible($order) === false || ReviewFront::is_blacklisted($order) === true || ReviewStats::isReviewClosed($order) || $manual_reminder_count > 0 || $auto_reminder_count > 0){

                $order->update_meta_data('_pisol_review_past_order_reminder_send', false);
                $order->save();
                return;

            }else{
                $order->update_meta_data('_pisol_review_past_order_reminder_send', true);
                $order->save();
                do_action('pisol_review_send_auto_reminder', $order_id);
            }
        }
        
    }

    

    function register_settings(){   

        foreach($this->settings as $setting){
            FormMaker::register_setting( $this->setting_key, $setting);
        }
    
    }

    function tab(){
        $this->tab_name = __('Past Order reminder', 'product-review-for-woocommerce');
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        ?>
        <a class=" pi-side-menu  <?php echo esc_attr($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url(admin_url( 'admin.php?page='.$page.'&tab='.$this->this_tab )); ?>">
        <span class="dashicons dashicons-controls-back"></span> <?php echo esc_html( $this->tab_name ); ?> 
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
        <input type="submit" name="submit" id="submit" class="btn btn-primary btn-md my-3" value="<?php echo esc_attr__('Start sending','product-review-for-woocommerce'); ?>">
        </form>
       <?php
    }

    function add_dynamic_cron_interval($schedules) {
        $runs_per_day = get_option('pisol_review_reminder_rate', 24); // Set your desired number of runs per day, can be from 1 to 48

        if( $runs_per_day < 1 || $runs_per_day > 96 || !is_numeric( $runs_per_day ) ) {
            $runs_per_day = 24;
        }

        $interval = self::calculate_cron_interval( $runs_per_day );
    
        // Add a custom schedule
        
        $schedules['pisol_review_frequency'] = array(
            'interval' => $interval,
            'display'  => __( "Run $runs_per_day times a day" ),
        );
    
        return$schedules;
    }

    static function calculate_cron_interval($runs_per_day) {
        $interval_in_seconds = floor( 86400 / $runs_per_day ); // 86400 seconds in a day
        return $interval_in_seconds;
    }

    static function validateDate($date){
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date ? $date : '';
    }
}


