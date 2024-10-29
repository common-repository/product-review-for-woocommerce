<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://piwebsolution.com/shop
 * @since             1.0.23
 * @package           Shipping_Method_Display_Style_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Customer review for WooCommerce
 * Plugin URI:        https://piwebsolution.com
 * Description:       Customer review for WooCommerce will send a reminder email to customers to review the products they have purchased.
 * Version:           1.0.23
 * Author:            PI Websolution
 * Author URI:        https://www.piwebsolution.com/shop/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       product-review-for-woocommerce
 * Domain Path:       /languages
 * WC tested up to: 9.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if(!is_plugin_active( 'woocommerce/woocommerce.php')){
    add_action( 'admin_notices', function () {
        ?>
        <div class="error notice">
            <p><?php esc_html_e( 'Please Install and Activate WooCommerce plugin, without that this plugin can\'t work', 'product-review-for-woocommerce' ); ?></p>
        </div>
        <?php
    });
    return;
}

/**
 * Declare compatible with HPOS new order table 
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );


register_activation_hook( __FILE__, function(){
    delete_option('pisol_review_flush_endpoint');
});

register_deactivation_hook( __FILE__, function(){
    delete_option('pisol_review_flush_endpoint');
});

define( 'PISOL_REVIEW_VERSION', '1.0.23' );
define( 'PISOL_REVIEW_SLUG', 'product-review-for-woocommerce' );
define( 'PISOL_REVIEW_NAME', 'product_review_for_woocommerce' );
define( 'PISOL_REVIEW_URL', plugin_dir_url(__FILE__));
define( 'PISOL_REVIEW_PATH', plugin_dir_path( __FILE__ ) );
define('PISOL_REVIEW_BASE_DIR', __DIR__);

require_once plugin_dir_path( __FILE__ ) . 'classes/autoloader.php';
require_once plugin_dir_path( __FILE__ ) . 'classes/review.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-bootstrap.php';
require_once plugin_dir_path( __FILE__ ) . 'public/class-bootstrap.php';