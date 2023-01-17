<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://discountsuiteforwp.com/?utm_source=WAD%20Free&utm_medium=cpc&utm_campaign=Woocommerce%20All%20Discounts
 * @since             0.1
 * @package           Wad
 *
 * @wordpress-plugin
 * Plugin Name:       Conditional Discounts for WooCommerce by ORION
 * Plugin URI:        https://discountsuiteforwp.com
 * Description:       Manage your shop discounts like a pro.
 * Version:           2.28.2
 * Author:            ORION
 * Author URI:        https://discountsuiteforwp.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-advanced-discounts
 * Domain Path:       /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 6.8.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WAD_VERSION', '2.28.2' );
define( 'WAD_URL', plugins_url('/', __FILE__) );
define( 'WAD_DIR', dirname(__FILE__) );
define( 'WAD_MAIN_FILE', 'woocommerce-all-discounts/wad.php' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wad-activator.php
 */
function activate_wad() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wad-activator.php';
	Wad_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wad-deactivator.php
 */
function deactivate_wad() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wad-deactivator.php';
	Wad_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wad' );
register_deactivation_hook( __FILE__, 'deactivate_wad' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wad.php';
require plugin_dir_path(__FILE__) . 'includes/class-wad-discount.php';
require plugin_dir_path(__FILE__) . 'includes/class-wad-products-list.php';
if(!function_exists("o_admin_fields"))
    require plugin_dir_path(__FILE__) . 'includes/utils.php';
require plugin_dir_path(__FILE__) . 'includes/functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1
 */
function run_wad() {

	$plugin = new Wad();
	$plugin->run();

}
run_wad();
