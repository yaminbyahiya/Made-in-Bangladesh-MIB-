<?php

namespace TMAddons\Elementor\Builder\Conditions;

defined( 'ABSPATH' ) || exit;

class Singular extends Condition_Base {
	public static function get_priority() {
		return 60;
	}

	public function get_name() {
		return 'singular';
	}

	public function get_label() {
		return esc_html__( 'Singular', 'tm-addons-for-elementor' );
	}

	public function get_all_label() {
		return esc_html__( 'All Singular', 'tm-addons-for-elementor' );
	}

	public function check( $args ) {
		if ( isset( $args['post_type'] ) ) {
			if ( isset( $args['id'] ) ) {
				$id = (int) $args['id'];

				return is_singular() && get_queried_object_id() === $id;
			}

			return is_singular( $args['post_type'] );
		}

		// return ( is_singular() && ! is_embed() ) || is_404();
		return is_singular();
	}
}
