<?php

namespace TMAddons;

class Elementor {
	private static $_instance = null;

	/**
	 * Elementor modules
	 *
	 * @var array
	 */
	public $module = [];

	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );

		$this->includes();
		$this->hooks();
	}

	public function includes() {
		// Function
		include_once( TM_ADDONS_DIR . 'elementor/function.php' );
	}

	public function hooks() {
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor_scripts' ] );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_editor_styles' ] );

		add_action( 'elementor/preview/enqueue_styles', [ $this, 'enqueue_preview_styles' ] );

		add_action( 'elementor/init', [ $this, 'init_modules' ] );
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public static function is_preview() {
		return \Elementor\Plugin::$instance->preview->is_preview_mode() || is_preview();
	}

	public function autoload( $class ) {
		if ( 0 !== strpos( $class, __NAMESPACE__ ) ) {
			return;
		}

		$filename = strtolower(
			preg_replace(
				[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
				[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
				$class
			)
		);

		$filename = TM_ADDONS_DIR . $filename . '.php';

		if ( is_readable( $filename ) ) {
			include( $filename );
		}
	}

	public function enqueue_editor_styles() {
		wp_enqueue_style( 'tm-addons-elementor-editor-css', TM_ADDONS_URL . 'assets/css/elementor/editor.css', null, TM_ADDONS_VER );
		wp_enqueue_style( 'tm-addons-elementor-template-library-css', TM_ADDONS_URL . 'assets/css/elementor/template-library.css', null, TM_ADDONS_VER );
	}

	public function enqueue_editor_scripts() {
		// Used same handle to avoid duplicate script.
		if ( ! wp_script_is( 'jquery-elementor-select2', 'registered' ) ) {
			wp_register_script( 'jquery-elementor-select2', TM_ADDONS_URL . 'assets/js/libs/select2.js', [ 'jquery' ], '4.1.0', true );
		}

		wp_enqueue_script( 'tm-addons-elementor-editor', TM_ADDONS_URL . 'assets/js/elementor/editor.js', [ 'jquery-elementor-select2' ], null, true );

		wp_enqueue_script( 'tm-addons-elementor-modules', TM_ADDONS_URL . 'assets/js/elementor/modules.js', [ 'jquery' ], null, true );

		wp_enqueue_script( 'tm-addons-elementor-template-library', TM_ADDONS_URL . 'assets/js/elementor/template-library.js', [ 'jquery' ], null, true );

		wp_localize_script(
			'tm-addons-elementor-editor',
			'$tmAddonsConditionData',
			array(
				'ajax_url'                 => admin_url( 'admin-ajax.php' ),
				'condition_button_text'    => esc_html__( 'Conditions', 'tm-addons-for-elementor' ),
				'condition_id_placeholder' => esc_html__( 'All', 'tm-addons-for-elementor' ),
			)
		);

		wp_localize_script(
			'tm-addons-elementor-template-library',
			'$tmAddonsTemplateData',
			apply_filters(
				'tm-addons-elementor/template-library/localize',
				array(
					'icon'              => TM_ADDONS_URL . 'assets/images/icon.png',
					'add_template_text' => esc_html__( 'Add Template', 'tm-addons' ),
				)
			)
		);
	}

	public function enqueue_preview_styles() {
		wp_enqueue_style( 'tm-addons-elementor-editor-preview', TM_ADDONS_URL . 'assets/css/elementor/editor-preview.css', null, TM_ADDONS_VER );
	}

	/**
	 * Init modules
	 */
	public function init_modules() {
		$this->modules['builder']          = \TMAddons\Elementor\Builder\Builder::instance();
		$this->modules['template-library'] = \TMAddons\Elementor\TemplateLibrary\Template_Library::instance();

		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$this->modules['custom-css'] = \TMAddons\Elementor\CustomCss\Custom_CSS::instance();

			new \TMAddons\Elementor\Shortcode\Shortcode();
		}
	}
}

Elementor::instance();
