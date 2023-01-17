<?php

use TMAddons\Elementor\Builder\Builder as Builder;

defined( 'ABSPATH' ) || exit;

function tm_addons_do_location( $location ) {
	$builder = Builder::instance();

	return $builder->get_locations()->do_location( $location );
}

function tm_addons_location_exits( $location, $check_match = false ) {
	$builder = Builder::instance();

	return $builder->get_locations()->location_exits( $location, $check_match );
}

/**
 * Usage
 */
// if ( function_exists( 'tm_addons_location_exits' ) && tm_addons_location_exits( 'tm_footer', true ) ) {
// 	tm_addons_do_location( 'tm_footer' );
// }
