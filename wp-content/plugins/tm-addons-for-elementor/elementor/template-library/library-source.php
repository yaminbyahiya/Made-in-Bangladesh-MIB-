<?php

namespace TMAddons\Elementor\TemplateLibrary;

use Elementor\TemplateLibrary\Source_Base;
use Elementor\Plugin;

defined( 'ABSPATH' ) || die();

class Library_Source extends Source_Base {

	/**
	 * Template library data cache
	 */
	const LIBRARY_CACHE_KEY = 'tm_library_cache';

	/**
	 * Template info api url
	 *
	 */
	// const API_TEMPLATES_INFO_URL = 'http://localhost/minimog/wp-json/tm/v2/templates';

	/**
	 * Template data api url
	 */
	// const API_TEMPLATE_DATA_URL = 'http://localhost/minimog/wp-json/tm/v2/templates/%d';

	/**
	 * Template types info api url
	 */
	// const API_TEMPLATE_TYPES_INFO_URL = 'http://localhost/minimog/wp-json/tm/v2/template_types';
	public function get_title() {
		return __( 'TM Library', 'tm-addons' );
	}

	public function register_data() {
	}

	public function save_item( $template_data ) {
		return new \WP_Error( 'invalid_request', 'Cannot save template to a tm library' );
	}

	public function update_item( $new_data ) {
		return new \WP_Error( 'invalid_request', 'Cannot update template to a tm library' );
	}

	public function delete_template( $template_id ) {
		return new \WP_Error( 'invalid_request', 'Cannot delete template from a tm library' );
	}

	public function export_template( $template_id ) {
		return new \WP_Error( 'invalid_request', 'Cannot export template from a tm library' );
	}

	public function get_types() {
		$key_cache = 'tm_templates_type_cache';

		$types = get_transient( $key_cache );

		if ( false === $types ) {
			$api_url = apply_filters( 'tm_addons/elementor/template_types', '' );

			if ( ! $api_url ) {
				return false;
			}

			$response = wp_remote_get(
				$api_url,
				array(
					'timeout'   => 60,
					'sslverify' => false,
				)
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			$types = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $types ) || ! is_array( $types ) ) {
				return false;
			}

			set_transient( $key_cache, $types, DAY_IN_SECONDS );
		}

		return $types;
	}

	public function get_tags() {
		$key_cache = 'tm_templates_tags_cache';

		$tags = get_transient( $key_cache );

		if ( false === $tags ) {
			$api_url = apply_filters( 'tm_addons/elementor/template_tags', '' );

			if ( ! $api_url ) {
				return false;
			}

			$response = wp_remote_get(
				$api_url,
				array(
					'timeout'   => 60,
					'sslverify' => false,
				)
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			$tags = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $tags ) || ! is_array( $tags ) ) {
				return false;
			}

			set_transient( $key_cache, $tags, DAY_IN_SECONDS );
		}

		return $tags;
	}

	/**
	 * Get remote template.
	 *
	 * Retrieve a single remote template from Elementor.com servers.
	 *
	 * @param int $template_id The template ID.
	 *
	 * @return array Remote template.
	 */
	public function get_item( $template_id ) {
		$templates = $this->get_items();

		return $templates[ $template_id ];
	}

	public function get_items( $args = [] ) {
		$library_data = self::get_library_data();

		$templates = [];

		if ( ! empty( $library_data ) ) {
			foreach ( $library_data as $template_data ) {
				$templates[] = $this->prepare_template( $template_data );
			}
		}

		return $templates;
	}

	/**
	 * Get library data
	 *
	 * @param boolean $force_update
	 *
	 * @return array
	 */
	public static function get_library_data( $force_update = false ) {
		self::request_library_data( $force_update );

		$data = get_option( self::LIBRARY_CACHE_KEY );

		if ( empty( $data ) ) {
			return [];
		}

		return $data;
	}

	/**
	 * Get library data from remote source and cache
	 *
	 * @param boolean $force_update
	 *
	 * @return array
	 */
	private static function request_library_data( $force_update = false ) {
		$templates_info_url = apply_filters( 'tm_addons/elementor/templates_info_api', '' );

		if ( ! $templates_info_url ) {
			return false;
		}

		$data = get_option( self::LIBRARY_CACHE_KEY );

		if ( $force_update || false === $data ) {
			$timeout = ( $force_update ) ? 25 : 8;

			$response = wp_remote_get( $templates_info_url, [
				'timeout' => $timeout,
			] );

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				update_option( self::LIBRARY_CACHE_KEY, [] );

				return false;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $data ) || ! is_array( $data ) ) {
				update_option( self::LIBRARY_CACHE_KEY, [] );

				return false;
			}

			update_option( self::LIBRARY_CACHE_KEY, $data, 'no' );
		}

		return $data;
	}

	/**
	 * Prepare template items to match model
	 *
	 * @param array $template_data
	 *
	 * @return array
	 */
	private function prepare_template( array $template_data ) {
		return [
			'template_id'   => $template_data['id'],
			'source'        => $this->get_id(),
			'title'         => $template_data['title'],
			'author'        => $template_data['author'],
			'thumbnail'     => $template_data['thumbnail'],
			'preview'       => $template_data['thumbnail'],
			'date'          => $template_data['created_at'],
			'type'          => $template_data['type'],
			'tags'          => $template_data['tags'],
			'url'           => $template_data['url'],
			'page_settings' => $template_data['page_settings'],
		];
	}


	public function get_id() {
		return 'tm-library';
	}

	/**
	 * Get remote template data.
	 *
	 * Retrieve the data of a single remote template from Elementor.com servers.
	 *
	 * @return array|\WP_Error Remote Template data.
	 */
	public function get_data( array $args, $context = 'display' ) {
		$data = self::request_template_data( $args['template_id'] );

		$data = json_decode( $data, true );

		if ( empty( $data ) || empty( $data['content'] ) ) {
			throw new \Exception( __( 'Template does not have any content', 'tm-addons' ) );
		}

		$data['content'] = $this->replace_elements_ids( $data['content'] );
		$data['content'] = $this->process_export_import_content( $data['content'], 'on_import' );

		$post_id  = $args['editor_post_id'];
		$document = Plugin::instance()->documents->get( $post_id );

		if ( $document ) {
			$data['content'] = $document->get_elements_raw_data( $data['content'], true );
		}

		return $data;
	}

	public static function request_template_data( $template_id ) {
		if ( empty( $template_id ) ) {
			return false;
		}

		$template_data_url = apply_filters( 'tm_addons/elementor/template_data_api', '' );

		if ( ! $template_data_url ) {
			return false;
		}

		$api_url = sprintf( $template_data_url, $template_id );

		$body = [
			'home_url' => trailingslashit( home_url() ),
			'version'  => TM_ADDONS_VER,
		];

		$response = wp_remote_get(
			$api_url,
			[
				'body'    => $body,
				'timeout' => 25,
			]
		);

		return wp_remote_retrieve_body( $response );
	}
}
