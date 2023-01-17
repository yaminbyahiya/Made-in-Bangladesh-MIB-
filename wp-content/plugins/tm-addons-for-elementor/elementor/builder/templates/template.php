<?php

namespace TMAddons\Elementor\Builder\Templates;

use TMAddons\Elementor\Builder\Builder;

class Template {
	private static $_instance = null;

	public function __construct() {
		add_action( 'elementor/editor/footer', [ $this, 'modal' ] );
		add_action( 'elementor/editor/footer', [ $this, 'templates' ] );

		add_action( 'wp_ajax_tm_condition_search', [ $this, 'condition_search' ] );
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function condition_search() {
		$return = array();

		$search_term = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';
		$post_type   = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';

		$search_results = new \WP_Query( array(
			's'                   => $search_term,
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => 1,
			'posts_per_page'      => -1,
		) );

		if ( $search_results->have_posts() ) :
			while ( $search_results->have_posts() ) : $search_results->the_post();
				$return[] = [
					'id'   => $search_results->post->ID,
					'text' => $search_results->post->post_title,
				];
			endwhile;
		endif;
		echo json_encode( $return );
		die;
	}

	public function templates() {
		$this->condition_field_template();
	}

	public function condition_field_template() {
		?>
		<script type="text/template" id="tmpl-tm-condition-field">
			<?php $this->condition_fields( array(), 'template' ); ?>
		</script>
		<?php
	}

	public function condition_fields( $conditions = [], $context = 'display' ) {
		$settings   = $this->get_settings();
		$conditions = 'display' == $context ? $conditions : array( 1 );

		foreach ( $conditions as $index => $condition ) {
			$parsed_condition = Builder::instance()->get_conditions()->parse_condition( $condition );
			?>
			<div
				id="tm-condition-form__field-<?php echo 'display' == $context ? esc_attr( $index ) : '{{data.count}}'; ?>"
				class="tm-condition-form__field"
				data-index="<?php echo 'display' == $context ? esc_attr( $index ) : '{{data.count}}'; ?>">
				<div class="tm-condition-form__conditions">
					<?php
					foreach ( $settings as $name => $setting ) {
						$setting['name']       = 'display' == $context ? "tm_condition[$index][$name]" : '{{data.name}}[{{data.count}}][' . $name . ']';
						$setting['value']      = ! empty( $parsed_condition[ $name ] ) ? $parsed_condition[ $name ] : '';
						$setting['class']      = 'tm-condition-form__condition tm-condition-form__condition-' . $name;
						$setting['attributes'] = array( 'data-name' => $name );
						$setting['__instance'] = $parsed_condition;

						$this->setting_field( $setting, $context );
					}
					?>
				</div>
				<div class="tm-condition-form__remove"><i class="eicon-close" aria-hidden="true"></i></div>
			</div>
			<?php
		}
	}

	public function get_settings() {
		$settings = [
			'type'     => [
				'type'    => 'select',
				'name'    => 'type',
				'options' => $this->get_types(),
			],
			'singular' => [
				'type'       => 'select',
				'name'       => 'singular',
				'options'    => $this->get_post_type_list(),
				'dependency' => [
					'type' => 'singular',
				],
			],
			'archive'  => [
				'type'       => 'select',
				'name'       => 'archive',
				'options'    => $this->get_archive_list(),
				'dependency' => [
					'type' => 'archive',
				],
			],
			'id'       => [
				'type'       => 'select2',
				'name'       => 'id',
				'options'    => [],
				'dependency' => [
					'type'      => 'singular',
					'singular!' => '',
				],
			],
		];

		return $settings;
	}

	public function get_types() {
		$names = [
			'general'  => esc_html__( 'Entire Site', 'tm-addons-for-elementor' ),
			'singular' => esc_html__( 'Singular', 'tm-addons-for-elementor' ),
			'archive'  => esc_html__( 'Archive', 'tm-addons-for-elementor' ),
		];

		return $names;
	}

	public function get_post_type_list() {
		$_post_types = get_post_types( [ 'show_in_nav_menus' => true, ], 'objects' );
		$post_types  = [];

		$post_types[''] = esc_html__( 'All Singular', 'tm-addons-for-elementor' );
		foreach ( $_post_types as $post_type ) {
			$post_types[ $post_type->name ] = $post_type->label;
		}

		return $post_types;
	}

	public function get_archive_list() {
		$_post_types  = $this->get_post_type_list();
		$archives     = [];
		$archives[''] = esc_html__( 'All Archives', 'tm-addons-for-elementor' );
		foreach ( $_post_types as $post_type => $label ) {
			if ( ! get_post_type_archive_link( $post_type ) ) {
				continue;
			}

			$archives[ $post_type ] = sprintf( esc_html__( '%s Archive', 'tm-addons-for-elementor' ), $label );
		}

		return $archives;
	}

	public function setting_field( $args ) {
		$args = wp_parse_args( $args, array(
			'name'        => '',
			'type'        => 'text',
			'placeholder' => '',
			'value'       => '',
			'class'       => '',
			'input_class' => '',
			'input_id'    => '',
			'attributes'  => array(),
			'options'     => array(),
			'dependency'  => array(),
			'__instance'  => null,
		) );

		// Field Attributes
		$field_attributes = array(
			'class'     => $args['class'],
			'data-name' => $args['name'],
		);

		if ( ! empty( $args['attributes'] ) ) {
			foreach ( $args['attributes'] as $attr_name => $attr_value ) {
				$field_attributes[ $attr_name ] = is_array( $attr_value ) ? implode( ' ', $attr_value ) : $attr_value;
			}
		}

		if ( ! empty( $args['dependency'] ) ) {
			$field_attributes['data-dependency'] = json_encode( $args['dependency'] );
		}

		if ( ! $this->check_setting_field_visible( $args['dependency'], $args['__instance'] ) ) {
			$field_attributes['class'] .= ' hidden';
		}

		$field_attributes_string = '';

		foreach ( $field_attributes as $name => $value ) {
			$field_attributes_string .= " $name=" . '"' . esc_attr( $value ) . '"';
		}

		// Input Attributes
		$input_attributes = array(
			'id'    => $args['input_id'],
			'name'  => $args['name'],
			'class' => $args['input_class'],
		);

		if ( ! empty( $args['placeholder'] ) ) {
			$input_attributes['placeholder'] = $args['placeholder'];
		}

		if ( ! empty( $args['options'] ) && 'select' != $args['type'] ) {
			foreach ( $args['options'] as $attr_name => $attr_value ) {
				$input_attributes[ $attr_name ] = is_array( $attr_value ) ? implode( ' ', $attr_value ) : $attr_value;
			}
		}

		$input_attributes_string = '';

		foreach ( $input_attributes as $name => $value ) {
			$input_attributes_string .= " $name=" . '"' . esc_attr( $value ) . '"';
		}

		// Start render
		echo '<div ' . $field_attributes_string . '>';

		switch ( $args['type'] ) {
			case 'select':
				if ( empty( $args['options'] ) ) {
					break;
				}
				?>
				<select <?php echo $input_attributes_string; ?>>
					<?php foreach ( $args['options'] as $value => $label ) : ?>
						<option
							value="<?php echo esc_attr( $value ) ?>" <?php selected( true, in_array( $value, (array) $args['value'] ) ) ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php
				break;
			case 'select2':
				$values = [];

				if ( $args['value'] && $args['value'] != get_the_ID() ) {
					$post = get_post( $args['value'] );

					if ( $post ) {
						$values[ $post->ID ] = $post->post_title;
					}
				}

				?>
				<select <?php echo $input_attributes_string; ?>>
					<?php if ( ! empty( $values ) ) : ?>
						<?php foreach ( $values as $value => $label ) : ?>
							<option
								value="<?php echo esc_attr( $value ) ?>" <?php selected( true, in_array( $value, (array) $args['value'] ) ) ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</select>
				<?php
				break;

			default:
				?>
				<input type="<?php echo esc_attr( $args['type'] ) ?>"
				       value="<?php echo esc_attr( $args['value'] ); ?>" <?php echo $input_attributes_string ?>/>
				<?php
				break;
		}

		// End render
		echo '</div>';
	}

	/**
	 * Check setting field visiblity
	 *
	 * @param array $de
	 *
	 * @return bool
	 */
	protected function check_setting_field_visible( $dependency, $values = null ) {
		if ( empty( $dependency ) ) {
			return true;
		}

		if ( null === $values ) {
			return true;
		}

		foreach ( $dependency as $dependency_key => $dependency_value ) {
			preg_match( '/([a-z_\-0-9]+)(!?)$/i', $dependency_key, $dependency_key_parts );

			$pure_dependency_key    = $dependency_key_parts[1];
			$is_negative_dependency = ! ! $dependency_key_parts[2];

			if ( ! isset( $values[ $pure_dependency_key ] ) || null === $values[ $pure_dependency_key ] ) {
				return false;
			}

			$instance_value = $values[ $pure_dependency_key ];

			/**
			 * If the $dependency_value is a non empty array - check if the $dependency_value contains the $instance_value,
			 * If the $instance_value is a non empty array - check if the $instance_value contains the $dependency_value
			 * otherwise check if they are equal. ( and give the ability to check if the value is an empty array )
			 */
			if ( is_array( $dependency_value ) && ! empty( $dependency_value ) ) {
				$is_contains = in_array( $instance_value, $dependency_value, true );
			} elseif ( is_array( $instance_value ) && ! empty( $instance_value ) ) {
				$is_contains = in_array( $dependency_value, $instance_value, true );
			} else {
				$is_contains = $instance_value === $dependency_value;
			}

			if ( ( $is_negative_dependency && $is_contains ) || ( ! $is_negative_dependency && ! $is_contains ) ) {
				return false;
			}
		}

		return true;
	}

	public function modal() {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$document = Builder::instance()->get_document( $post_id );

		if ( ! $document ) {
			return;
		}

		?>
		<div class="tm-modal tm-modal-editor">
			<div class="tm-modal__backdrop"></div>
			<div class="tm-modal__container">
				<div class="tm-modal__header">
					<div
						class="tm-modal__title"><?php echo esc_html__( 'Conditions', 'tm-addons-for-elementor' ); ?></div>
					<div class="tm-modal__close-button"><i class="eicon-close" aria-hidden="true"></i></div>
				</div>
				<div class="tm-modal__content">
					<div class="tm-modal__content-wrapper">
						<div
							class="tm-modal__content-heading"><?php echo esc_html__( 'Where Do You Want to Display Your Revision?', 'tm-addons-for-elementor' ); ?></div>
						<?php $this->form( $document ); ?>
					</div>
				</div>
				<div class="tm-modal__footer">
					<a href="#"
					   class="tm-modal__save-button"><?php echo esc_html__( 'Save', 'tm-addons-for-elementor' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	public function form( $document ) {
		$conditions = $document->get_meta( '_tm_conditions' );
		$conditions = $conditions ? $conditions : [];
		?>
		<form method="post" class="tm-condition-form">
			<div class="tm-condition-form__content">
				<div class="tm-condition-form__fields"><?php $this->condition_fields( $conditions ) ?></div>
				<div class="tm-condition-form__action">
					<a href="#" class="tm-condition-form__add-new" data-name="tm_condition"
					   data-count="<?php echo esc_attr( count( $conditions ) ) ?>"><?php echo esc_html__( '+ Add Condition', 'tm-addons-for-elementor' ) ?></a>
				</div>
			</div>
		</form>
		<?php
	}
}
