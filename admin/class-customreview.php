<?php
namespace PISOL\REVIEW\ADMIN;

use PISOL\REVIEW\FRONT\BlackListDB;

use PISOL\REVIEW\FRONT\ReviewForm;

class CustomReview{

    private $settings = array();

    private $active_tab;

    private $this_tab = 'custom_review';

    private $tab_name;

    private $setting_key = 'custom_review_fields';

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

        add_action('wp_ajax_pisol_review_add_edit_review_parameter', array($this,'add_edit_review_parameter'));

        add_action('wp_ajax_pisol_review_delete_review_parameter', array($this,'remove_review_parameter'));

        add_action('admin_post_pisol_review_unsubscribe', array($this, 'add_to_blacklist'));

        add_action( 'product_cat_add_form_fields',  array($this, 'pisol_add_review_parameters_to_category'), 10, 1 );

        add_action( 'product_cat_edit_form_fields', array($this, 'pisol_edit_review_parameters_to_category'), 10, 2 );

        add_action( 'created_product_cat', array($this, 'pisol_save_review_parameters_in_category'), 10, 1 );
        add_action( 'edited_product_cat', array($this, 'pisol_save_review_parameters_in_category'), 10, 1 );

    }

    function register_settings(){   

        foreach($this->settings as $setting){
            FormMaker::register_setting( $this->setting_key, $setting);
        }
    
    }

    function tab(){
        $this->tab_name = __('Review parameters', "product-review-for-woocommerce");
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        ?>
        <a class=" pi-side-menu  <?php echo esc_attr($this->active_tab == $this->this_tab ? 'bg-primary' : 'bg-secondary'); ?>" href="<?php echo esc_url(admin_url( 'admin.php?page='.$page.'&tab='.$this->this_tab )); ?>">
        <span class="dashicons dashicons-editor-ol"></span> <?php echo esc_html( $this->tab_name ); ?> 
        </a>
        <?php
    }

    function tab_content(){
       
        include_once PISOL_REVIEW_PATH.'admin/partials/review-parameters.php';
    }

    function add_edit_review_parameter(){
        //check for nonce 
        if ( ! isset( $_POST['review_parameter_nonce'] ) || ! wp_verify_nonce( $_POST['review_parameter_nonce'], 'review_parameter_action' ) ) {
            wp_send_json_error( 'Nonce verification failed' );
        }

        if(empty($_POST['review_parameter_question']) || empty($_POST['review_parameter_label'])){
            wp_send_json_error( 'Cant leave field empty' );
        }

        
        // I will like to save the received data as a custom post type 
        $review_parameter = array(
            'post_title'    => sanitize_text_field($_POST['review_parameter_question']),
            'post_status'   => 'publish',
            'post_type'     => 'pi_review_parameter',
            'meta_input'    => array(
                'label'     => sanitize_text_field($_POST['review_parameter_label']),
                'required'  => isset($_POST['review_parameter_required']) ? 1 : 0,
                'default_rating' => in_array($_POST['review_parameter_default_rating'], [1,2,3,4,5]) ? sanitize_text_field($_POST['review_parameter_default_rating']) : ''
            )
        );

        if (!empty($_POST['review_parameter_id'])) {
            $review_parameter['ID'] = intval($_POST['review_parameter_id']);
            $result = wp_update_post($review_parameter);
            
            if (is_wp_error($result)) {
                wp_send_json_error( 'Failed to update review parameter' );
            } else {
                wp_send_json_success( 'Review parameter updated successfully' );
            }
        } else {
            $review_parameter_id = wp_insert_post($review_parameter);

            if($review_parameter_id){
                wp_send_json_success( 'Review parameter added successfully' );
            }else{
                wp_send_json_error( 'Failed to add review parameter' );
            }
        }

    }

    function remove_review_parameter(){
        //check for nonce 
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'remove_review_parameter_action' ) ) {
            wp_send_json_error( 'Nonce verification failed' );
        }

        if(empty($_POST['review_parameter_id'])){
            wp_send_json_error( 'Cant leave field empty' );
        }

        //make sure he is admin
        if(!current_user_can('manage_options')){
            wp_send_json_error( 'You are not allowed to perform this action' );
        }

        $review_parameter_id = sanitize_text_field($_POST['review_parameter_id']);

        $result = wp_delete_post($review_parameter_id);

        if($result){
            wp_send_json_success( 'Review parameter removed successfully' );
        }else{
            wp_send_json_error( 'Failed to remove review parameter' );
        }
    }

    function pisol_add_review_parameters_to_category( $taxonomy ) {
        ?>
        <div class="form-field term-review-parameters-wrap">
            <label for="review_parameters"><?php esc_html_e('Review Parameters', 'product-review-for-woocommerce'); ?></label>
            <?php
            $review_parameters = get_posts(array(
                'post_type' => 'pi_review_parameter',
                'posts_per_page' => -1,  // Get all review parameters
            ));
    
            if( $review_parameters ) {
                echo '<select name="review_parameters[]" id="review_parameters" multiple style="width:100%">';
                foreach( $review_parameters as $parameter ) {
                    echo '<option value="'.esc_attr( $parameter->ID ).'">'.esc_html( $parameter->post_title ).'</option>';
                }
                echo '</select>';
                echo '<p>' . esc_html__('Select the review parameters that will apply to all products in this category.', 'product-review-for-woocommerce') . '</p>';
            } else {
                echo '<p>' . esc_html__('No review parameters found. Please create some first.', 'product-review-for-woocommerce') . '</p>';
            }
            ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            if ($.fn.selectWoo) {
                $('#review_parameters').selectWoo({
                    placeholder: "<?php esc_html_e('Select Review Parameters', 'product-review-for-woocommerce'); ?>",
                    allowClear: true
                });
            }
        });
        </script>
        <?php
    }

    function pisol_edit_review_parameters_to_category( $term, $taxonomy ) {
        // Get previously saved review parameters for this category
        $selected_parameters = get_term_meta( $term->term_id, 'review_parameters', true );
        if ( !is_array($selected_parameters) ) $selected_parameters = array();
    
        ?>
        <tr class="form-field term-review-parameters-wrap">
            <th scope="row" valign="top"><label for="review_parameters"><?php esc_html_e('Review Parameters', 'product-review-for-woocommerce'); ?></label></th>
            <td>
                <?php
                $review_parameters = get_posts(array(
                    'post_type' => 'pi_review_parameter',
                    'posts_per_page' => -1,  // Get all review parameters
                ));
    
                if( $review_parameters ) {
                    echo '<select name="review_parameters[]" id="review_parameters" multiple style="width:100%">';
                    foreach( $review_parameters as $parameter ) {
                        $selected = in_array( $parameter->ID, $selected_parameters ) ? 'selected' : '';
                        echo '<option value="'.esc_attr( $parameter->ID ).'" '.esc_attr( $selected ).'>'.esc_html( $parameter->post_title ).'</option>';
                    }
                    echo '</select>';
                    echo '<p>' . esc_html__('Select the review parameters that will apply to all products in this category.', 'product-review-for-woocommerce') . '</p>';
                } else {
                    echo '<p>' . esc_html__('No review parameters found. Please create some first.', 'product-review-for-woocommerce') . '</p>';
                }
                ?>
            </td>
        </tr>
        <script>
        jQuery(document).ready(function($) {
            if ($.fn.selectWoo) {
                $('#review_parameters').selectWoo({
                    placeholder: "<?php esc_html_e('Select Review Parameters', 'product-review-for-woocommerce'); ?>",
                    allowClear: true
                });
            }
        });
        </script>
        <?php
    }

    function pisol_save_review_parameters_in_category( $term_id ) {
        if( isset($_POST['review_parameters']) && is_array($_POST['review_parameters']) ) {
            $review_parameters = array_map( 'intval', $_POST['review_parameters'] );
            update_term_meta( $term_id, 'review_parameters', $review_parameters );
        } else {
            delete_term_meta( $term_id, 'review_parameters' );
        }
    }
    
}


