<?php
namespace TMAddons\Elementor\Builder\Classes;

use Elementor\TemplateLibrary\Source_Local;
use Elementor\Core\Utils\Exceptions;
use TMAddons\Elementor\Builder\Builder;

class Conditions {

	const FORM_NAME = 'tm_condition';
	const META_KEY = '_tm_conditions';

	private $location_cache = [];

	public function __construct() {
		add_action( 'elementor/ajax/register_actions', [ $this, 'register_ajax_actions' ] );
	}

	/**
	 * @param \Elementor\Core\Common\Modules\Ajax\Module $ajax_manager
	 */
	public function register_ajax_actions( $ajax_manager ) {
		$ajax_manager->register_ajax_action( 'tm_builder_save_conditions', [
			$this,
			'ajax_save_conditions',
		] );

		$ajax_manager->register_ajax_action( 'tm_builder_check_conditions_conflicts', [
			$this,
			'ajax_check_conditions_conflicts',
		] );
	}

	public function ajax_save_conditions( $request ) {
		$conditions = array();
        parse_str( $request['conditions'], $conditions );
		$post_id = $request['editor_post_id'];

		$is_save = $this->save_conditions( $post_id, $conditions[ self::FORM_NAME ] );
	}

	public function ajax_check_conditions_conflicts( $request ) {
		$post_id = $request['editor_post_id'];

		$conditions = array();
        parse_str( $request['conditions'], $conditions );

		$index = $request['index'];

		$condition = $conditions[ self::FORM_NAME ][ $index ];
		$condition = rtrim( implode( '/', $condition ), '/' );

		$document = Builder::instance()->get_document( $post_id );
		$location = $document->get_location();

		$conflicted = array_map( function ( $conflict ) {
			return sprintf(
				'<a href="%s" target="_blank">%s</a>', $conflict['edit_url'], $conflict['template_title']
			);
		}, $this->get_conditions_conflicts_by_location( $condition, $location, $post_id ) );

		if ( empty( $conflicted ) ) {
			return '';
		}

		return esc_html__( 'You have set this location for other templates: ', 'tm-addons-for-elementor' ) .
			' ' .
			implode( ', ', $conflicted );
	}

	public function save_conditions( $template_id, $conditions ) {
		$save_conditions = [];

		foreach( $conditions as $condition ) {
			$save_conditions[] = rtrim( implode( '/', $condition ), '/' );
		}

		$document = Builder::instance()->get_document( $template_id );

		if ( empty( $save_conditions ) ) {
			$is_saved = delete_post_meta( $template_id, self::META_KEY );
		} else {
			$is_saved = $document->update_meta( self::META_KEY, $save_conditions );
		}

		return $is_saved;
	}

	public function get_conditions_conflicts_by_location( $condition, $location, $ignore_post_id = null ) {
		$conflicted = [];

		$query = $this->get_query();

		foreach ( $query->posts as $post_id ) {
			if ( $ignore_post_id === $post_id ) {
				continue;
			}

			$document = Builder::instance()->get_document( $post_id );

			if ( $document ) {
				$document_location = $document->get_location();

				if ( $location == $document_location ) {
					$conditions = $document->get_meta( self::META_KEY );

					if ( false !== array_search( $condition, $conditions, true ) ) {
						$edit_url = $document->get_edit_url();

						$conflicted[] = [
							'template_id'    => $post_id,
							'template_title' => esc_html( get_the_title( $post_id ) ),
							'edit_url'       => $edit_url,
						];
					}
				}
			}
		}

		return $conflicted;
	}

	public function parse_condition( $condition ) {
		list ( $type, $singular, $archive, $id ) = array_pad( explode( '/', $condition ), 4, '' );

		return compact( 'type', 'singular', 'archive', 'id' );
	}

	public function get_query() {
		$post_types = [
			Source_Local::CPT,
		];

		$document_types = \Elementor\Plugin::$instance->documents->get_document_types();

		foreach ( $document_types as $document_type ) {
			if ( $document_type::get_property( 'support_tm_conditions' ) && $document_type::get_property( 'cpt' ) ) {
				$post_types = array_merge( $post_types, $document_type::get_property( 'cpt' ) );
			}
		}

		$query = new \WP_Query( [
			'posts_per_page' => -1,
			'post_type' => $post_types,
			'fields' => 'ids',
			'meta_key' => self::META_KEY,
		] );

		return $query;
	}

	public function get_location_templates( $location ) {
		$conditions_priority = [];

		$query = $this->get_query();

		foreach ( $query->posts as $post_id ) {
			$document = Builder::instance()->get_document( $post_id );
			if ( $document ) {
				$document_location = $document->get_location();

				if ( $location == $document_location ) {
					$conditions = $document->get_meta( self::META_KEY );

					foreach( $conditions as $condition ) {
						$parsed_condition = $this->parse_condition( $condition );

						$type = $parsed_condition['type'];
						$singular = $parsed_condition['singular'];
						$archive = $parsed_condition['archive'];
						$id = $parsed_condition['id'];

						$condition_class_name = ucfirst( $type );
						$condition_class_name = '\\TMAddons\\Elementor\\Builder\\Conditions\\' . $condition_class_name;

						$condition_instance = new $condition_class_name;

						$priority = $condition_instance::get_priority();

						if ( ! $condition_instance ) {
							continue;
						}

						$check_args = [];

						if ( 'singular' === $type ) {
							if ( $singular ) {
								$priority -= 10;
								$check_args['post_type'] = $singular;
							}

							if ( $id ) {
								$priority -= 5;
								$check_args['id'] = $id;
							}
						}

						if ( 'archive' === $type ) {
							if ( $archive ) {
								$priority -= 10;
								$check_args['post_type'] = $archive;
							}
						}

						$condition_pass = $condition_instance->check( $check_args );

						if ( $condition_pass ) {
							$post_status = get_post_status( $post_id );

							if ( 'publish' !== $post_status ) {
								continue;
							}

							$conditions_priority[ $post_id ] = $priority;
						}
					}
				}
			}
		}

		asort( $conditions_priority );

		return $conditions_priority;
	}

	public function get_theme_templates_ids( $location ) {
		$builder = Builder::instance();

		if ( ! empty( $_GET['theme_template_id'] ) ) {
			$force_template_id = $_GET['theme_template_id'];
			$document = $builder->get_document( $force_template_id );
			if ( $document && $location === $document->get_location() ) {

				return [
					$force_template_id => 1,
				];
			}
		}

		$current_post_id = get_the_ID();
		$document = $builder->get_document( $current_post_id );
		if ( $document && $location === $document->get_location() ) {

			return [
				$current_post_id => 1,
			];
		}

		$templates = $this->get_location_templates( $location );

		return $templates;
	}

	/**
	 * @param $location
	 */
	public function get_documents_for_location( $location ) {
		if ( isset( $this->location_cache[ $location ] ) ) {
			return $this->location_cache[ $location ];
		}

		$theme_templates_ids = $this->get_theme_templates_ids( $location );

		$builder = Builder::instance();

		$documents = [];

		foreach ( $theme_templates_ids as $theme_template_id => $priority ) {
			$document = $builder->get_document( $theme_template_id );
			if ( $document ) {
				$documents[ $theme_template_id ] = $document;
			}
		}

		$this->location_cache[ $location ] = $documents;

		return $documents;
	}

	public function clear_location_cache() {
		$this->location_cache = [];
	}
}
