<?php

namespace TMAddons\Elementor\TemplateLibrary;

defined( 'ABSPATH' ) || die();

use Elementor\Core\Common\Modules\Ajax\Module as Ajax;
use Elementor\Core\Base\Module;
use Elementor\Plugin;

class Template_Library extends Module {
	private $sources = array();

	public function __construct() {
		add_action( 'elementor/editor/footer', [ $this, 'load_footer_scripts' ] );

		// Register AJAX hooks
		add_action( 'elementor/ajax/register_actions', [ $this, 'register_ajax_actions' ] );

		$this->register_sources();

		add_filter( 'tm-addons-elementor/template-library/localize', [ $this, 'localize_tabs' ] );
	}

	/**
	 * Undocumented function
	 *
	 * @return Library_Source
	 */
	public function register_sources() {
		$sources = [
			'tm-library' => new Library_Source(),
		];

		foreach ( $sources as $key => $class ) {
			$this->add_source( $key, $class );
		}
	}

	public function add_source( $key, $class ) {
		$this->sources[ $key ] = new $class();
	}

	public function get_name() {
		return 'tm-template-library';
	}

	public function localize_tabs( $data ) {
		$tabs    = $this->get_template_tabs();
		$keys    = array_keys( $tabs );
		$default = $keys[0];

		$data['tabs']       = $this->get_template_tabs();
		$data['defaultTab'] = $default;

		return $data;
	}

	public function get_template_tabs() {
		return [
			''      => [
				'title' => esc_html__( 'All', 'tm-addons' ),
			],
			'page'  => [
				'title' => esc_html__( 'Pages', 'tm-addons' ),
			],
			'block' => [
				'title' => esc_html__( 'Blocks', 'tm-addons' ),
			],
		];
	}

	/**
	 * Add Templates Scripts
	 *
	 * Load required templates for the templates library.
	 *
	 * @since  3.6.0
	 * @access public
	 */
	public function load_footer_scripts() {

		$scripts = glob( TM_ADDONS_DIR . 'elementor/template-library/templates/*.php' );

		array_map(
			function( $file ) {

				$name = basename( $file, '.php' );
				ob_start();
				include $file;
				printf( '<script type="text/html" id="tmpl-tm-%1$s">%2$s</script>', $name, ob_get_clean() );

			},
			$scripts
		);
	}

	public function register_ajax_actions( Ajax $ajax ) {
		$ajax->register_ajax_action( 'tm_get_library_data', function( $data ) {
			if ( ! current_user_can( 'edit_posts' ) ) {
				throw new \Exception( 'Access Denied' );
			}

			if ( ! empty( $data['editor_post_id'] ) ) {
				$editor_post_id = absint( $data['editor_post_id'] );

				if ( ! get_post( $editor_post_id ) ) {
					throw new \Exception( __( 'Post not found', 'tm-addons' ) );
				}

				Plugin::instance()->db->switch_to_post( $editor_post_id );
			}

			$source_name = isset( $data['source'] ) ? esc_attr( $data['source'] ) : '';

			if ( ! $source_name ) {
				return false;
			}

			$source = isset( $this->sources[ $source_name ] ) ? $this->sources[ $source_name ] : false;

			$source::get_library_data( ! empty( $data['sync'] ) );

			return [
				'templates' => $source->get_items(),
				'types'     => $source->get_types(),
				'tags'      => $source->get_tags(),
			];
		} );

		$ajax->register_ajax_action( 'tm_get_template_data', function( $data ) {
			if ( ! current_user_can( 'edit_posts' ) ) {
				throw new \Exception( 'Access Denied' );
			}

			if ( ! empty( $data['editor_post_id'] ) ) {
				$editor_post_id = absint( $data['editor_post_id'] );

				if ( ! get_post( $editor_post_id ) ) {
					throw new \Exception( __( 'Post not found', 'tm-addons' ) );
				}

				Plugin::instance()->db->switch_to_post( $editor_post_id );
			}

			if ( empty( $data['template_id'] ) ) {
				throw new \Exception( __( 'Template id missing', 'tm-addons' ) );
			}

			$result = self::get_template_data( $data );

			return $result;
		} );
	}

	public function get_template_data( $data ) {
		$source_name = isset( $data['source'] ) ? esc_attr( $data['source'] ) : '';

		if ( ! $source_name ) {
			return false;
		}

		$source = isset( $this->sources[ $source_name ] ) ? $this->sources[ $source_name ] : false;

		$template = $source->get_data( $data );

		return $template;
	}
}
