<?php

namespace TMAddons\Api;

defined( 'ABSPATH' ) || exit;

class Api {
	public function __construct() {
		include_once( TM_ADDONS_DIR . 'api/class-rest-elementor-templates-controller.php' );
		include_once( TM_ADDONS_DIR . 'api/class-rest-elementor-template-types-controller.php' );
		include_once( TM_ADDONS_DIR . 'api/class-rest-elementor-template-tags-controller.php' );

		add_action( 'rest_api_init', [ $this, 'templates_routes' ] );
	}

	public function templates_routes() {
		$templates = new REST_Elementor_Templates_Controller();
		$templates->register_routes();

		$types = new REST_Elementor_Template_Types_Controller();
		$types->register_routes();

		$tags = new REST_Elementor_Template_Tags_Controller();
		$tags->register_routes();
	}
}

new Api();
