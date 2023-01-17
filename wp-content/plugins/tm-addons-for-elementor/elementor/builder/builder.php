<?php

namespace TMAddons\Elementor\Builder;

use Elementor\Core\Base\Module;
use Elementor\TemplateLibrary\Source_Local;
use TMAddons\Elementor\Builder\Classes;
use TMAddons\Elementor\Builder\Library;
use TMAddons\Elementor\Builder\Templates;

class Builder extends Module {
	public function __construct() {
		parent::__construct();

		Templates\Template::instance();

		$this->add_component( 'tm_conditions', new Classes\Conditions() );
		$this->add_component( 'tm_locations', new Classes\Locations() );

		add_action( 'elementor/documents/register', array( $this, 'register_documents' ) );
		add_filter( 'elementor/document/config', [ $this, 'document_config' ], 10, 2 );
	}

	public function get_name() {
		return 'tm-builder';
	}

	public function register_documents() {
		$document_types = [
			'tm_footer' => Library\Footer::get_class_full_name(),
		];

		foreach ( $document_types as $type => $class_name ) {
			\Elementor\Plugin::$instance->documents->register_document_type( $type, $class_name );
		}
	}

	public function document_config( $config, $post_id ) {
		$document = $this->get_document( $post_id );

		if ( ! $document ) {
			return $config;
		}

		$config = array_replace_recursive( $config, [
			'tm_builder' => [
				'settings' => [
					'location' => $document->get_location(),
				],
			],
		] );

		return $config;
	}

	/**
	 * @param $post_id
	 *
	 * @return Library_Base
	 */
	public function get_document( $post_id ) {
		$document = null;

		try {
			$document = \Elementor\Plugin::$instance->documents->get( $post_id );
		} catch ( \Exception $e ) {
			unset( $e );
		}

		if ( ! empty( $document ) && ! $document instanceof Library\Library_Base ) {
			$document = null;
		}

		return $document;
	}

	/**
	 * @return Classes\Conditions
	 */
	public function get_conditions() {
		return $this->get_component( 'tm_conditions' );
	}

	/**
	 * @return Classes\Locations
	 */
	public function get_locations() {
		return $this->get_component( 'tm_locations' );
	}
}
