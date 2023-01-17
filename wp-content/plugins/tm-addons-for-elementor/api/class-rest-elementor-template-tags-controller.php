<?php
namespace TMAddons\Api;

use \WP_REST_Controller;

class REST_Elementor_Template_Tags_Controller extends WP_REST_Controller {
	/**
	 * Taxonomy
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * Constructor.
	 *
	 * @since 4.7.0
	 */
	public function __construct() {
		$this->taxonomy = 'tm_template_tag';
		$this->namespace = 'tm/v2';
		$this->rest_base = 'template_tags';
	}


	public function register_routes() {
		// template
		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_templates' ),
					'permission_callback' => array( $this, 'get_permission_callback' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the post.' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_template' ),
					'permission_callback' => array( $this, 'get_permission_callback' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get permission callback.
	 *
	 * Default controller permission callback.
	 * By default endpoint will inherit the permission callback from the controller.
	 * By default permission is `current_user_can( 'administrator' );`.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function get_permission_callback( $request ) {
		return true;
	}

	public function get_templates( $request ) {
		$args = [
			'taxonomy' => $this->taxonomy,
			'hide_empty' => false,
		];

		$terms = get_terms($args);

		$data = [];

		foreach( $terms as $term ) {
			$data[] = $this->get_term( $term->term_id );
		}

		$response = rest_ensure_response( $data );

		return $response;
	}

	public function get_template( $request ) {
		$term = $this->get_term( $request['id'] );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$response = rest_ensure_response( $term );

		return $response;
	}

	protected function get_term( $id ) {
		$error = new \WP_Error(
			'rest_term_invalid_id',
			__( 'Invalid post ID.' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$term = get_term( $id, $this->taxonomy );

		if ( empty( $term ) || is_wp_error( $term ) ) {
			return $error;
		}

		$post_data = [
			'id'    => $term->term_id,
			'title' => $term->name,
			'slug'  => $term->slug,
		];

		return $post_data;
	}
}