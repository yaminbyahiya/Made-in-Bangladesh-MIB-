<?php
/**
 * Plugin Name: Insight Product Brands for Woocommerce
 * Description: Add brands for products
 * Author: ThemeMove
 * Author URI: https://thememove.com
 * Version: 1.1.0
 * Text Domain: insight-product-brands
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'INSIGHT_PRODUCT_BRANDS_DIR', plugin_dir_path( __FILE__ ) );
define( 'INSIGHT_PRODUCT_BRANDS_URL', plugin_dir_url( __FILE__ ) );
define( 'INSIGHT_PRODUCT_BRANDS_VERSION', '1.1.0' );
define( 'INSIGHT_PRODUCT_BRANDS_ASSETS_URI', INSIGHT_PRODUCT_BRANDS_URL . '/assets' );

if ( ! function_exists( 'insight_product_brand_placeholder_img_src' ) ) {
	function insight_product_brand_placeholder_img_src() {
		$src = INSIGHT_PRODUCT_BRANDS_ASSETS_URI . '/images/placeholder.jpg';

		return apply_filters( 'insight_product_brands_placeholder_img_src', $src );
	}
}

class Insight_Product_Brands {

	protected static $instance = null;

	const TAXONOMY_NAME = 'product_brand';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function initialize() {
		add_action( 'plugins_loaded', [ $this, 'load_text_domain' ] );

		add_action( 'admin_init', [ $this, 'add_permalink_setting' ] );
		add_action( 'admin_init', [ $this, 'save_permalink_setting' ] );

		add_action( 'woocommerce_register_taxonomy', [ $this, 'register_taxonomy' ] );

		// Add meta data fields.
		add_action( self::TAXONOMY_NAME . '_add_form_fields', [ $this, 'add_brand_fields' ] );
		add_action( self::TAXONOMY_NAME . '_edit_form_fields', [ $this, 'edit_brand_fields' ] );

		// Save meta data.
		add_action( 'created_term', [ $this, 'save_brand_fields' ], 10, 3 );
		add_action( 'edit_term', [ $this, 'save_brand_fields' ], 10, 3 );

		// Show term meta data columns.
		add_filter( 'manage_edit-' . self::TAXONOMY_NAME . '_columns', [ $this, 'manage_table_columns' ] );
		add_filter( 'manage_' . self::TAXONOMY_NAME . '_custom_column', [ $this, 'manage_table_column' ], 10, 3 );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
	}

	public function load_text_domain() {
		load_plugin_textdomain( 'insight-product-brands', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	public function add_permalink_setting() {
		add_settings_field(
			'insight_product_brand_slug',
			__( 'Product brand base', 'insight-product-brands' ),
			array( $this, 'output_permalink_setting_field' ),
			'permalink',
			'optional'
		);
	}

	public function save_permalink_setting() {
		if ( ! is_admin() ) {
			return;
		}

		// We need to save the options ourselves; settings api does not trigger save for the permalinks page.
		if ( isset( $_POST['insight_product_brand_slug'] ) ) {
			$slug = untrailingslashit( $_POST['insight_product_brand_slug'] );
			update_option( 'insight_product_brand_slug', $slug );
		}
	}

	public function output_permalink_setting_field() {
		$slug = get_option( 'insight_product_brand_slug' );
		?>
		<input name="insight_product_brand_slug" type="text" class="regular-text code"
		       value="<?php echo esc_attr( $slug ); ?>"
		       placeholder="<?php echo esc_attr_x( 'product-brand', 'slug', 'insight-product-brands' ) ?>"/>
		<?php
	}

	public function register_taxonomy() {
		$slug = get_option( 'insight_product_brand_slug', '' );

		if ( empty( $slug ) ) {
			$slug = 'product-brand';
		}

		$labels = array(
			'name'                       => _x( 'Product brands', 'taxonomy general name', 'insight-product-brands' ),
			'singular_name'              => _x( 'Product brand', 'taxonomy singular name', 'insight-product-brands' ),
			'search_items'               => __( 'Search Brand', 'insight-product-brands' ),
			'popular_items'              => __( 'Popular Brands', 'insight-product-brands' ),
			'all_items'                  => __( 'All Brands', 'insight-product-brands' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Brand', 'insight-product-brands' ),
			'update_item'                => __( 'Update Brand', 'insight-product-brands' ),
			'add_new_item'               => __( 'Add New Brand', 'insight-product-brands' ),
			'new_item_name'              => __( 'New Brand Name', 'insight-product-brands' ),
			'separate_items_with_commas' => __( 'Separate brands with commas', 'insight-product-brands' ),
			'add_or_remove_items'        => __( 'Add or remove brands', 'insight-product-brands' ),
			'choose_from_most_used'      => __( 'Choose from the most used brands', 'insight-product-brands' ),
			'not_found'                  => __( 'No brands found.', 'insight-product-brands' ),
			'menu_name'                  => __( 'Brands', 'insight-product-brands' ),
			'back_to_items'              => __( 'Back to brands', 'insight-product-brands' ),
		);

		$args = array(
			'hierarchical'          => false,
			'label'                 => _x( 'Brands', 'taxonomy general name', 'insight-product-brands' ),
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'show_in_rest'          => true,
			'capabilities'          => array(
				'manage_terms' => 'manage_product_terms',
				'edit_terms'   => 'edit_product_terms',
				'delete_terms' => 'delete_product_terms',
				'assign_terms' => 'assign_product_terms',
			),
			'rewrite'               => array(
				'slug'         => apply_filters( 'insight/product_brand/slug', $slug ),
				'with_front'   => false,
				'hierarchical' => true,
			),
		);

		register_taxonomy( self::TAXONOMY_NAME, 'product', $args );
	}

	public function add_brand_fields() {
		?>
		<div class="form-field term-url-wrap">
			<label for="tag-product-brand-url"><?php esc_html_e( 'Url', 'insight-product-brands' ); ?></label>
			<input type="text" name="product_brand_url" id="tag-product-brand-url"/>
			<p><?php esc_html_e( 'Set Brand External Url (if you set the url, when visitor click on a brand name, this url will be displayed instead of brand page )', 'insight-product-brands' ); ?>
				.</p>
		</div>
		<div class="form-field term-thumbnail-wrap">
			<label><?php esc_html_e( 'Thumbnail', 'insight-product-brands' ); ?></label>

			<div class="ipb-media-wrap">
				<div style="float: left; margin-right: 10px;" class="ipb-media-image">
					<img src="<?php echo esc_url( insight_product_brand_placeholder_img_src() ); ?>" width="60px"
					     height="60px"
					     data-src-placeholder="<?php echo esc_attr( insight_product_brand_placeholder_img_src() ); ?>"
					/></div>
				<div style="line-height: 60px;">
					<input type="hidden" class="ipb-media-input" name="product_brand_thumbnail_id"/>
					<button type="button"
					        class="ipb-media-upload button"><?php esc_html_e( 'Upload/Add image', 'insight-product-brands' ); ?></button>
					<button type="button"
					        class="ipb-media-remove button"><?php esc_html_e( 'Remove image', 'insight-product-brands' ); ?></button>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	public function edit_brand_fields( $term ) {
		$thumbnail_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
		$thumbnail    = $thumbnail_id ? wp_get_attachment_thumb_url( $thumbnail_id ) : insight_product_brand_placeholder_img_src();

		$url = get_term_meta( $term->term_id, 'url', true );
		?>
		<tr class="form-field term-url-wrap">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Url', 'insight-product-brands' ); ?></label></th>
			<td>
				<input type="text" name="product_brand_url" value="<?php echo esc_attr( $url ); ?>"/>
				<p><?php esc_html_e( 'Set Brand External Url (if you set the url, when visitor click on a brand name, this url will be displayed instead of brand page )', 'insight-product-brands' ); ?>
					.</p>
			</td>
		</tr>
		<tr class="form-field term-thumbnail-wrap">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Thumbnail', 'insight-product-brands' ); ?></label>
			</th>
			<td>
				<div class="ipb-media-wrap">
					<div style="float: left; margin-right: 10px;" class="ipb-media-image">
						<img src="<?php echo esc_url( $thumbnail ); ?>" width="60px" height="60px"
						     data-src-placeholder="<?php echo esc_attr( insight_product_brand_placeholder_img_src() ); ?>"/>
					</div>
					<div style="line-height: 60px;">
						<input type="hidden"
						       class="ipb-media-input"
						       name="product_brand_thumbnail_id"
						       value="<?php echo esc_attr( $thumbnail_id ); ?>"/>
						<button type="button" class="ipb-media-upload button">
							<?php esc_html_e( 'Upload/Add image', 'insight-product-brands' ); ?>
						</button>
						<button type="button" class="ipb-media-remove button">
							<?php esc_html_e( 'Remove image', 'insight-product-brands' ); ?>
						</button>
					</div>
					<div class="clear"></div>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * @param        $term_id
	 * @param string $tt_id
	 * @param string $taxonomy
	 *
	 * Save term meta data
	 */
	public function save_brand_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( self::TAXONOMY_NAME !== $taxonomy ) {
			return;
		}

		if ( ! empty( $_POST['product_brand_thumbnail_id'] ) ) {
			update_term_meta( $term_id, 'thumbnail_id', absint( $_POST['product_brand_thumbnail_id'] ) );
		} else {
			delete_term_meta( $term_id, 'thumbnail_id' );
		}

		if ( isset( $_POST['product_brand_url'] ) ) {
			update_term_meta( $term_id, 'url', esc_url_raw( $_POST['product_brand_url'] ) );
		}
	}

	public function manage_table_columns( $columns ) {
		$new_columns = array();

		if ( isset( $columns['cb'] ) ) {
			$new_columns['cb'] = $columns['cb'];
			unset( $columns['cb'] );
		}

		$new_columns['thumbnail'] = __( 'Thumbnail', 'insight-product-brands' );

		$columns = array_merge( $new_columns, $columns );

		$columns['url'] = __( 'Url', 'insight-product-brands' );

		$columns['handle'] = '';

		return $columns;
	}

	public function manage_table_column( $columns, $column, $id ) {
		if ( 'thumbnail' === $column ) {
			$thumbnail_id = get_term_meta( $id, 'thumbnail_id', true );

			if ( $thumbnail_id ) {
				$image = wp_get_attachment_thumb_url( $thumbnail_id );
			} else {
				$image = insight_product_brand_placeholder_img_src();
			}

			// Prevent esc_url from breaking spaces in urls for image embeds. Ref: https://core.trac.wordpress.org/ticket/23605 .
			$image   = str_replace( ' ', '%20', $image );
			$columns .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr__( 'Thumbnail', 'insight-product-brands' ) . '" class="wp-post-image" height="48" width="48" />';
		}

		if ( 'url' === $column ) {
			$url = get_term_meta( $id, 'url', true );

			$columns .= $url;
		}

		if ( 'handle' === $column ) {
			$columns .= '<input type="hidden" name="term_id" value="' . esc_attr( $id ) . '" />';
		}

		return $columns;
	}

	public function admin_scripts() {
		$screen = get_current_screen();

		if ( 'edit-product_brand' === $screen->id ) {
			wp_enqueue_media();
			wp_enqueue_script( 'insight-product-brand-media', INSIGHT_PRODUCT_BRANDS_ASSETS_URI . '/admin/js/media-upload.js', [ 'jquery' ], null, true );
		}
	}
}

Insight_Product_Brands::instance()->initialize();

