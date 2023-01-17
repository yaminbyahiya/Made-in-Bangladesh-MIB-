<?php

namespace TMAddons\Elementor\Builder\Classes;

use TMAddons\Elementor;
use TMAddons\Elementor\Builder\Library;
use TMAddons\Elementor\Builder\Builder;
use Elementor\Core\Files\CSS\Post as Post_CSS;

class Locations {
	public function __construct() {
		add_filter( 'elementor/admin/create_new_post/meta', [ $this, 'filter_add_location_meta_on_create_new_post' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	public function enqueue_styles() {
		if ( Elementor::is_preview() ) {
			return;
		}

		$locations = $this->get_locations();

		if ( empty( $locations ) ) {
			return;
		}

		$current_post_id = get_the_ID();

		/** @var Post_CSS[] $css_files */
		$css_files = [];

		foreach ( $locations as $location ) {
			$documents = Builder::instance()->get_conditions()->get_documents_for_location( $location );
			foreach ( $documents as $document ) {
				$post_id = $document->get_post()->ID;

				// Don't enqueue current post here (let the  preview/frontend components to handle it)
				if ( $current_post_id !== $post_id ) {
					$css_file    = new Post_CSS( $post_id );
					$css_files[] = $css_file;
				}
			}
		}

		if ( ! empty( $css_files ) ) {
			// Enqueue the frontend styles manually also for pages that don't built with Elementor.
			\Elementor\Plugin::$instance->frontend->enqueue_styles();

			// Enqueue after the frontend styles to override them.
			foreach ( $css_files as $css_file ) {
				$css_file->enqueue();
			}
		}
	}

	private function get_locations() {
		$core_locations = [ 'tm_footer' ];

		return $core_locations;
	}

	public function do_location( $location ) {
		$documents_by_conditions = Builder::instance()->get_conditions()->get_documents_for_location( $location );

		$document_ids = [];

		foreach ( $documents_by_conditions as $document_id => $document ) {
			$document_ids[ $document_id ] = $document_id;
		}

		if ( empty( $document_ids ) ) {
			return;
		}

		$document_id = key( $document_ids );
		$document    = Builder::instance()->get_document( $document_id );

		if ( empty( $document ) ) {
			return;
		}

		echo $document->get_content();
	}

	public function location_exits( $location = '', $check_match = false ) {
		$locations      = $this->get_locations();
		$location_exits = in_array( $location, $locations );

		if ( $location_exits && $check_match ) {
			$location_exits = ! ! Builder::instance()->get_conditions()->get_documents_for_location( $location );
		}

		return $location_exits;
	}

	public function filter_add_location_meta_on_create_new_post( $meta ) {
		if ( ! empty( $_GET['meta_location'] ) ) {
			$meta[ Library\Library_Base::LOCATION_META_KEY ] = $_GET['meta_location'];
		}

		return $meta;
	}
}
