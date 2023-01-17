<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue child scripts
 */
if ( ! function_exists( 'minimog_child_enqueue_scripts' ) ) {
	function minimog_child_enqueue_scripts() {
		wp_enqueue_style( 'minimog-child-style', get_stylesheet_directory_uri() . '/style.css' );
	}
}
add_action( 'wp_enqueue_scripts', 'minimog_child_enqueue_scripts', 15 );
/**
 * This filter add your custom fonts to dropdown list using in Elementor Editor.
 */
add_filter( 'elementor/fonts/additional_fonts', 'add_custom_fonts', 10, 999 );
function add_custom_fonts( $fonts ) {
    $additional_fonts = [
        'AlternateGotNo2D' => 'minimog',
		'AlternateGotNo3D' => 'minimog',
        'NeueHaasUnica' => 'minimog',
    ];
    return array_merge( $fonts, $additional_fonts );
}
/**
 * This is sample code to add custom font in Minimog
 * This filter add your custom fonts to dropdown list using in Customize.
 *
 * @since Minimog 1.8.5
 */
add_filter('minimog/fonts/additional_fonts', 'minimog_child_add_custom_fonts');
function minimog_child_add_custom_fonts($additional_fonts) {
	if( ! isset( $additional_fonts['AlternateGotNo2D'] )) {
		$additional_fonts['AlternateGotNo2D'] = 'AlternateGotNo2D';
	}
	if( ! isset( $additional_fonts['AlternateGotNo3D'] )) {
		$additional_fonts['AlternateGotNo3D'] = 'AlternateGotNo3D';
	}

	if( ! isset( $additional_fonts['NeueHaasUnica'] )) {
		$additional_fonts['NeueHaasUnica'] = 'NeueHaasUnica, sans-serif';
	}

	return $additional_fonts;
}

/**
 * This filter embed your font file to frontend.
 */
add_filter('minimog/custom_fonts/enqueue', 'minimog_child_enqueue_custom_fonts');
function minimog_child_enqueue_custom_fonts($font_family) {
	if ( strpos( $font_family, 'AlternateGotNo2D' ) !== false ) {
		wp_enqueue_style( 'font-alternate-gothic', get_stylesheet_directory_uri() . '/assets/fonts/alternate-gothic/stylesheet.css', null, null );
	}
	if ( strpos( $font_family, 'AlternateGotNo3D' ) !== false ) {
		wp_enqueue_style( 'font-alternate-gothic-no3-d', get_stylesheet_directory_uri() . '/assets/fonts/alternate-gothic-no3-d/stylesheet.css', null, null );
	}
	if ( strpos( $font_family, 'NeueHaasUnica' ) !== false ) {
		wp_enqueue_style( 'font-neue-haas-unica', get_stylesheet_directory_uri() . '/assets/fonts/neue-haas-unica/stylesheet.css', null, null );
	}
}

