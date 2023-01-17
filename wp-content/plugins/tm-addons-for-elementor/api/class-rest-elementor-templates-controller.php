<?php

namespace TMAddons\Api;

use \WP_REST_Controller;

class REST_Elementor_Templates_Controller extends WP_REST_Controller {
	/**
	 * Post type.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $post_type;

	/**
	 * Constructor.
	 *
	 * @since 4.7.0
	 */
	public function __construct() {
		$this->post_type = 'tm_template';
		$this->namespace = 'tm/v2';
		$this->rest_base = 'templates';
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

	/**
	 * Retrieves a collection of posts.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_templates( $request ) {
		$args = [
			'post_type'   => $this->post_type,
			'numberposts' => -1,
		];

		$data = [];

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$data[] = $this->get_post( $post->ID, 'info' );
		}

		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get the post, if the ID is valid.
	 *
	 * @since 4.7.2
	 *
	 * @param int $id Supplied ID.
	 *
	 * @return WP_Post|WP_Error Post object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $id, $name = '' ) {
		$error = new \WP_Error(
			'rest_post_invalid_id',
			__( 'Invalid post ID.' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$post = get_post( (int) $id );

		$template_data = '';

		if ( class_exists( '\Elementor\Plugin' ) ) {
			$document      = \Elementor\Plugin::$instance->documents->get( $id );
			$template_data = ( $document ) ? $document->get_export_data() : '';
		}

		if ( empty( $post ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return $error;
		}

		$tags = [];
		$cats = [];

		$tag_terms = get_the_terms( $post->ID, 'tm_template_tag' );
		$cat_terms = get_the_terms( $post->ID, 'tm_template_type' );

		if ( ! empty( $tag_terms ) && ! is_wp_error( $tag_terms ) ) {
			foreach ( $tag_terms as $term ) {
				$tags[] = $term->slug;
			}
		}

		if ( ! empty( $cat_terms ) && ! is_wp_error( $cat_terms ) ) {
			foreach ( $cat_terms as $term ) {
				$cats[] = $term->slug;
			}
		}

		$post_data = [
			'title'         => $post->post_title,
			'page_settings' => ! empty( $template_data['settings'] ) ? $template_data['settings'] : '',
		];

		if ( 'info' === $name ) {
			$post_data['id']         = $post->ID;
			$post_data['author']     = get_the_author_meta( 'display_name', $post->post_author );
			$post_data['thumbnail']  = get_the_post_thumbnail_url( $post->ID );
			$post_data['created_at'] = date( "U", strtotime( $post->post_date ) );
			$post_data['url']        = get_the_permalink( $post->ID );
			$post_data['type']       = $cats;
			$post_data['tags']       = $tags;
		} else {
			$post_data['content'] = ! empty( $template_data['content'] ) ? $template_data['content'] : $post->post_content;
		}

		return $post_data;
	}

	/**
	 * Retrieves a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_template( $request ) {
		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$response = rest_ensure_response( $post );

		return $response;
	}
}
