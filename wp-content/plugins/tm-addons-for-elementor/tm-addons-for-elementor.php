<?php
/*
Plugin Name: Thememove Addons For Elementor
Description: Additional functions for Elementor
Author: ThemeMove
Version: 1.3.0
Author URI: https://thememove.com
Text Domain: tm-addons-for-elementor
Domain Path: /languages/
Requires at least: 5.7
Requires PHP: 7.0
Elementor tested up to: 3.6.2
*/
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'TM_Addons' ) ) {
	final class TM_Addons {
		/**
		 * Constructor function.
		 */
		public function __construct() {
			$this->define();
			$this->includes();
			$this->init();
		}

		public function define() {
			define( 'TM_ADDONS_VER', '1.3.0' );
			define( 'TM_ADDONS_DIR', plugin_dir_path( __FILE__ ) );
			define( 'TM_ADDONS_URL', plugin_dir_url( __FILE__ ) );
		}

		public function includes() {
			include_once( TM_ADDONS_DIR . 'api/api.php' );
		}

		public function init() {
			add_action( 'plugins_loaded', [ $this, 'init_elementor' ] );
		}

		public function init_elementor() {
			load_plugin_textdomain( 'tm-addons-for-elementor', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

			// Check if Elementor installed and activated.
			if ( ! did_action( 'elementor/loaded' ) ) {
				return;
			}

			// Check for required Elementor version.
			if ( ! version_compare( ELEMENTOR_VERSION, '3.0.0', '>=' ) ) {
				return;
			}

			// Check for required PHP version.
			if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
				return;
			}

			// Once we get here, We have passed all validation checks so we can safely include our plugin.
			include_once( TM_ADDONS_DIR . 'elementor/elementor.php' );
		}
	}

	new TM_Addons();
}
