<?php

namespace TMAddons\Elementor\Builder\Conditions;

defined( 'ABSPATH' ) || exit;

class General extends Condition_Base {
	public function get_name() {
		return 'tm-addons-for-elementor';
	}

	public function get_label() {
		return esc_html__( 'Entire Site', 'tm-addons-for-elementor' );
	}

	public function get_all_label() {
		return esc_html__( 'Entire Site', 'tm-addons-for-elementor' );
	}

	public function check( $args ) {
		return true;
	}
}
