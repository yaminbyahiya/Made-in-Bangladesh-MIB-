<?php

namespace TMAddons\Elementor\Builder\Library;

use Elementor\Core\DocumentTypes\Post;

class Footer extends Library_Base {
	/**
	 * Get document properties.
	 *
	 * Retrieve the document properties.
	 *
	 * @since  2.0.0
	 * @access public
	 * @static
	 *
	 * @return array Document properties.
	 */
	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['tm_location'] = 'tm_footer';

		return $properties;
	}

	public static function get_type() {
		return 'tm_footer';
	}

	/**
	 * Get document title.
	 *
	 * Retrieve the document title.
	 *
	 * @since  2.0.0
	 * @access public
	 * @static
	 *
	 * @return string Document title.
	 */
	public static function get_title() {
		return esc_html__( 'TM Footer', 'tm-addons-for-elementor' );
	}

	public static function get_plural_title() {
		return esc_html__( 'TM Footers', 'tm-addons-for-elementor' );
	}

	protected static function get_site_editor_type() {
		return 'footer';
	}

	public function get_css_wrapper_selector() {
		return '.elementor-' . $this->get_main_id();
	}

	/**
	 * @since  3.1.0
	 * @access protected
	 */
	protected function register_controls() {
		parent::register_controls();

		Post::register_style_controls( $this );

		$this->update_control( 'section_page_style', [
			'label' => esc_html__( 'Style', 'tm-addons-for-elementor' ),
		] );
	}
}
