<?php
namespace PISOL\REVIEW\ADMIN;


class CustomFields{

    static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    } 

    function __construct()
    {
        add_action('pisol_custom_field_review_editor', array($this,'editor'), 10, 2);

        add_action('pisol_custom_field_review_past_reminder_stats', array($this,'reviewStats'), 10, 2);
    }

    function editor($setting, $saved_value){
        $body = wp_kses_post( \WC_Admin_Settings::get_option( $setting['field'], ($setting['default'] ?? '') ) );
        $settings = array (
            'teeny' => false,
            'textarea_rows' => 13
        );

        
        $label = isset($setting['label']) ? $setting['label'] : '';
        $desc = isset($setting['desc']) ? $setting['desc'] : '';
        $links = isset($setting['links']) ? $setting['links'] : '';
        ?>
        <div id="row_<?php echo esc_attr($setting['field']); ?>"  class="row py-4 border-bottom align-items-center <?php echo !empty($setting['class']) ? esc_attr($setting['class']) : ''; ?>">
            <div class="col-12 col-md-3">
            <label class="h6 mb-0" for="<?php echo esc_attr($setting['field']); ?>"><?php echo wp_kses_post($label); ?></label>
            <?php echo wp_kses_post($desc != "" ? '<br><small>'.$desc.'</small><br>': ""); ?>
            <?php echo wp_kses_post($links != "" ? $links: ""); ?>
            </div>
            <div class="col-12 col-md-9">
            <?php wp_editor( $body, esc_attr( $setting['field'] ), $settings ); ?>
            </div>
        </div>
        <?php
    }

    function reviewStats($setting, $saved_value){
        $key = PastOrderReminder::rage_key();
        $order_list = PastOrderReminder::order_list();
        $order_send = get_option($key.'_send', array());
        $order_remaining = array_diff($order_list, $order_send);
        $completed = get_option($key.'_completed', false);

        $total = count($order_list);
        $sent = count($order_send);
        $remaining = count($order_remaining);
        $completed = $completed ? __('Yes', 'product-review-for-woocommerce') : __('No', 'product-review-for-woocommerce');

        $from_date = get_option('pisol_review_from_date', '');
        $to_date = get_option('pisol_review_to_date', '');
        if(!empty($from_date) && !empty($to_date)){
            $smaller = strtotime($from_date) < strtotime($to_date) ? $from_date : $to_date;
            $larger = strtotime($from_date) > strtotime($to_date) ? $from_date : $to_date;
        }else{
            $smaller = '';
            $larger = '';
        }

        echo '<div class="row py-4 border-bottom align-items-center">';
        echo '<div class="col-9">';
        if(!empty($smaller) && !empty($larger)){
            echo "Sending reminder for orders for which review reminder was not send in between <b>".esc_html($smaller)."</b> and <b>".esc_html($larger)."</b> date range";
        }else{
            echo "Sending reminder for all orders for which review reminder was not send";
        }
        echo '</div>';
        echo '<div class="col-3">';
        if(get_option('pisol_review_enable_past_order_reminder', 0)){
            echo '<span class="badge badge-success">'.esc_html( sprintf(__('%d reminder will be send per day', 'product-review-for-woocommerce'), get_option('pisol_review_reminder_rate',24)) ).'</span>';
        }else{
            echo '<span class="badge badge-danger">'.esc_html(__('Disabled', 'product-review-for-woocommerce')).'</span>';
        }
        echo '</div>';
        echo '</div>';
        ?>
        <div id="row_<?php echo esc_attr($setting['field']); ?>"  class="row py-4 border-bottom align-items-center <?php echo !empty($setting['class']) ? esc_attr($setting['class']) : ''; ?>">
        
        <?php
        
        echo '<div class="col-12 col-md-4">';
        echo "<b>Total orders in your given date range</b>: ".esc_html($total)." orders";
        echo '</div>';
        echo '<div class="col-12 col-md-4">';
        echo "<b>Reminder sent for</b>: ".esc_html($sent)." orders";
        echo '</div>';
        echo '<div class="col-12 col-md-4">';
        echo "<b>Reminder remaining to be send for</b>: ".esc_html($remaining) ." orders";
        echo '</div>';
        ?>
        </div>
        <?php 
    }

}