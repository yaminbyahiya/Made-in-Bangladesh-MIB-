<?php

namespace TMAddons\Elementor\Builder\Conditions;

defined( 'ABSPATH' ) || exit;

class Archive extends Condition_Base {
	public static function get_priority() {
		return 80;
	}

	public function get_name() {
		return 'archive';
	}

	public function get_label() {
		return esc_html__( 'Archives', 'tm-addons-for-elementor' );
	}

	public function get_all_label() {
		return esc_html__( 'All Archives', 'tm-addons-for-elementor' );
	}

	public function check( $args ) {
		if ( isset( $args['post_type'] ) ) {
			return is_post_type_archive( $args['post_type'] ) || ( 'post' === $args['post_type'] && is_home() );
		}

		return is_archive() || is_home() || is_search();
	}
}
