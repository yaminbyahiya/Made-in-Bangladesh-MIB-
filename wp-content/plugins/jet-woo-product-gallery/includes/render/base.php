<?php
/**
 * Base class for galleries renderer.
 */

use Jet_Gallery\Embed;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Gallery_Render_Base' ) ) {

	/**
	 * Define Jet_Gallery_Render_Base abstract class.
	 */
	abstract class Jet_Gallery_Render_Base {

		private $settings;
		private $type;
		private $attributes_variation_images = '';

		public $__new_icon_prefix = 'selected_';

		/**
		 * Constructor of the class.
		 *
		 * @param array  $settings
		 * @param string $type
		 */
		public function __construct( $settings = [], $type = '' ) {

			$this->settings = $this->get_parsed_settings( $settings );
			$this->type     = $type;

		}

		/**
		 * Returns editor type for proper rendering.
		 *
		 * @return string
		 */
		public function get_editor_type() {
			return $this->type;
		}

		/**
		 * Returns gallery settings.
		 *
		 * @param null $setting
		 *
		 * @return false|mixed
		 */
		public function get_settings( $setting = null ) {
			if ( $setting ) {
				return isset( $this->settings[ $setting ] ) ? $this->settings[ $setting ] : false;
			} else {
				return $this->settings;
			}
		}

		/**
		 * Returns parsed settings.
		 *
		 * @param array $settings
		 *
		 * @return array
		 */
		public function get_parsed_settings( $settings = [] ) {

			$defaults = $this->default_settings();
			$settings = wp_parse_args( $settings, $defaults );

			foreach ( $defaults as $key => $default_value ) {
				if ( null === $settings[ $key ] ) {
					$settings[ $key ] = $default_value;
				}
			}

			return $settings;

		}

		/**
		 * Returns gallery default settings.
		 *
		 * @return array
		 */
		public function default_settings() {
			return [
				'gallery_source'          => 'manual',
				'product_id'              => '',
				'disable_feature_image'   => false,
				'enable_zoom'             => false,
				'zoom_magnify'            => 1,
				'gallery_key'             => '',
				'enable_feature_image'    => false,
				'gallery_images'          => [],
				'enable_video'            => false,
				'video_type'              => 'youtube',
				'youtube_url'             => 'https://www.youtube.com/watch?v=CJO0u_HrWE8',
				'vimeo_url'               => 'https://vimeo.com/235215203',
				'self_hosted_url'         => [],
				'custom_placeholder'      => [],
				'enable_gallery'          => false,
				'gallery_trigger_type'    => 'button',
				'gallery_button_icon'     => [],
				'gallery_show_caption'    => true,
				'gallery_show_fullscreen' => true,
				'gallery_show_zoom'       => true,
				'gallery_show_share'      => true,
				'gallery_show_counter'    => true,
				'gallery_show_arrows'     => true,
				'video_display_in'        => 'content',
				'aspect_ratio'            => '16-9',
				'first_place_video'       => false,
				'autoplay'                => false,
				'mute'                    => false,
				'loop'                    => false,
				'show_play_button'        => true,
				'play_button_type'        => 'icon',
				'play_button_icon'        => [],
				'play_button_image'       => [],
				'popup_button_icon'       => [],
			];
		}

		/**
		 * Returned selected setting value.
		 *
		 * @param null  $setting
		 * @param false $default
		 *
		 * @return false|mixed
		 */
		public function get( $setting = null, $default = false ) {
			if ( isset( $this->settings[ $setting ] ) ) {
				return $this->settings[ $setting ];
			} else {
				$defaults = $this->default_settings();

				return $defaults[ $setting ] ?? $default;
			}
		}

		/**
		 * Returned gallery content.
		 *
		 * @return false|string
		 */
		public function get_content() {

			ob_start();
			$this->render();
			return ob_get_clean();

		}

		/**
		 * Call the name of the gallery.
		 *
		 * @return mixed
		 */
		abstract public function get_name();

		/**
		 * Render gallery content.
		 *
		 * @return void
		 */
		abstract public function render();

		/**
		 * Call the render function from the exact Render instance.
		 *
		 * @return void
		 */
		public function render_content() {
			$this->render();
		}

		/**
		 * Get render gallery content.
		 *
		 * Returns render gallery content depending on source.
		 *
		 * @since  2.1.0
		 * @since  2.1.9 Added fallback values. Added `jet-gallery/render/attachments-id` hook for different source type
		 *         attachments ids handling.
		 * @access public
		 *
		 * @return void
		 */
		public function get_render_gallery_content() {

			global $post;

			$settings            = $this->get_settings();
			$source              = $settings['gallery_source'] ?? 'manual';
			$post_id             = null;
			$attachment_ids      = [];
			$with_featured_image = false;

			switch ( $source ) {
				case 'products':
					if ( ! empty( $settings['product_id'] ) ) {
						$product = wc_get_product( $settings['product_id'] );
					} else {
						$product = wc_get_product();
					}

					if ( ! empty( $product ) ) {
						if ( 'variable' === $product->get_type() ) {
							$this->attributes_variation_images = $this->get_variation_images_data_json( $post, $product, $settings );
						}

						$post_id             = $product->get_id();
						$attachment_ids      = $product->get_gallery_image_ids();
						$with_featured_image = ! filter_var( $settings['disable_feature_image'], FILTER_VALIDATE_BOOLEAN );
					}

					break;

				case 'cpt':
					$post_id             = $post->ID;
					$with_featured_image = filter_var( $settings['enable_feature_image'], FILTER_VALIDATE_BOOLEAN );

					if ( ! empty( $settings['gallery_key'] ) ) {
						$gallery_data = get_post_meta( $post->ID, $settings['gallery_key'], true );

						if ( is_array( $gallery_data ) ) {
							foreach ( $gallery_data as $data ) {
								$attachment_ids[] = $data['id'];
							}
						} else {
							if ( empty( $gallery_data ) ) {
								$gallery_data = $settings['gallery_key'];
							}

							$gallery_data = explode( ',', $gallery_data );

							foreach ( $gallery_data as $data ) {
								if ( is_numeric( $data ) ) {
									$attachment_ids[] = $data;
								} elseif ( filter_var( $data, FILTER_VALIDATE_URL ) !== false ) {
									$attachment_ids[] = attachment_url_to_postid( $data );
								}
							}
						}
					}

					break;

				case 'manual':
					$selected_images = $settings['gallery_images'] ?? [];

					if ( ! empty( $selected_images ) ) {
						foreach ( $selected_images as $image ) {
							if ( $image ) {
								$attachment_ids[] = $image['id'];
							}
						}
					}

					break;

				default:
					$attachment_ids = apply_filters( 'jet-gallery/render/attachments-id', $attachment_ids, $settings );
					break;
			}

			if ( $with_featured_image || $attachment_ids ) {
				$this->open_wrap();
				include $this->get_global_template( 'index' );
				$this->close_wrap();
			} else {
				printf(
					'<div class="jet-woo-product-gallery__content">%s</div>',
					__( 'Gallery not found.', 'jet-woo-product-gallery' )
				);
			}

		}

		/**
		 * Open wrap.
		 *
		 * Open standard wrapper.
		 *
		 * @since  2.1.0
		 * @since  2.1.9 Added hook `jet-gallery/render/wrapper-attrs` for additional wrapper attributes. Code refactor.
		 * @access public
		 *
		 * @return void
		 */
		public function open_wrap() {

			$builder_class = 'blocks' === $this->get_editor_type() ? 'blocks-' . $this->get_name() : 'elementor-' . $this->get_name();
			$attrs         = [
				'class'                 => 'jet-woo-product-gallery ' . $builder_class,
				'data-gallery-settings' => $this->get_gallery_setting_json(),
			];

			if ( ! empty( $this->attributes_variation_images ) ) {
				$attrs['data-variation-images'] = $this->attributes_variation_images;
			}

			$attrs = apply_filters( 'jet-gallery/render/wrapper-attrs', $attrs, $this );

			echo '<div ' . jet_woo_product_gallery_tools()->implode_html_attributes( $attrs ) . '>';

		}

		/**
		 * Close wrap.
		 *
		 * Close standard wrapper.
		 *
		 * @since  2.1.0
		 * @access public
		 *
		 * @return void
		 */
		public function close_wrap() {
			echo '</div>';
		}

		/**
		 * Get variation images data JSON.
		 *
		 * Returns generated products variations images data JSON.
		 *
		 * @since  2.0.0
		 * @simce  2.1.7 Added `jet-gallery/render/variation-images` hook for custom products variation data.
		 * @access public
		 *
		 * @param object $post     Global post instance.
		 * @param object $_product Current product instance.
		 * @param array  $settings Widgets settings list.
		 *
		 * @return string
		 */
		public function get_variation_images_data_json( $post, $_product, $settings ) {

			$variation_images = [];
			$variations       = $_product->get_available_variations();

			foreach ( $variations as $variation ) {
				$variation_props = wc_get_product_attachment_props( $variation['image_id'], $post );

				if ( isset( $settings['thumbs_image_size'] ) ) {
					$variation_src = wp_get_attachment_image_src( $variation['image_id'], $settings['thumbs_image_size'] );

					$variation_props['thumb_src']   = $variation_src[0];
					$variation_props['thumb_src_w'] = $variation_src[1];
					$variation_props['thumb_src_h'] = $variation_src[2];
				}

				$variation_src = wp_get_attachment_image_src( $variation['image_id'], $settings['image_size'] );

				$variation_props['src']    = $variation_src[0];
				$variation_props['src_w']  = $variation_src[1];
				$variation_props['src_h']  = $variation_src[2];
				$variation_props['srcset'] = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $variation['image_id'], $settings['image_size'] ) : false;
				$variation_props['sizes']  = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $variation['image_id'], $settings['image_size'] ) : false;

				$variation_images[ $variation['image_id'] ] = $variation_props;
			}

			$variation_images = apply_filters( 'jet-gallery/render/variation-images', $variation_images, $post, $_product, $settings );

			return htmlspecialchars( json_encode( $variation_images ) );

		}

		/**
		 * Returns featured image placeholder depending on gallery source.
		 *
		 * @return string
		 */
		public function get_featured_image_placeholder() {
			if ( 'products' === $this->get( 'gallery_source' ) ) {
				return wc_placeholder_img_src( 'large' );
			} else {
				$placeholder_image = jet_woo_product_gallery()->plugin_url( 'assets/images/placeholder.png' );

				return apply_filters( 'jet-gallery/render/get_placeholder_image_src', $placeholder_image );
			}
		}

		/**
		 * Get gallery settings JSON.
		 *
		 * Returns generates gallery setting JSON.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return string
		 */
		public function get_gallery_setting_json() {

			$settings = $this->get_settings();

			$gallery_settings = [
				'enableGallery' => filter_var( $settings['enable_gallery'], FILTER_VALIDATE_BOOLEAN ),
				'enableZoom'    => filter_var( $settings['enable_zoom'], FILTER_VALIDATE_BOOLEAN ),
				'zoomMagnify'   => $settings['zoom_magnify'] ?? 1,
				'caption'       => filter_var( $settings['gallery_show_caption'], FILTER_VALIDATE_BOOLEAN ),
				'zoom'          => filter_var( $settings['gallery_show_zoom'], FILTER_VALIDATE_BOOLEAN ),
				'fullscreen'    => filter_var( $settings['gallery_show_fullscreen'], FILTER_VALIDATE_BOOLEAN ),
				'share'         => filter_var( $settings['gallery_show_share'], FILTER_VALIDATE_BOOLEAN ),
				'counter'       => filter_var( $settings['gallery_show_counter'], FILTER_VALIDATE_BOOLEAN ),
				'arrows'        => filter_var( $settings['gallery_show_arrows'], FILTER_VALIDATE_BOOLEAN ),
			];

			if ( $this->gallery_has_video() ) {
				$gallery_settings['hasVideo']      = true;
				$gallery_settings['videoType']     = jet_woo_gallery_video_integration()->get_video_type( $settings );
				$gallery_settings['videoIn']       = $settings['video_display_in'];
				$gallery_settings['videoAutoplay'] = filter_var( $settings['autoplay'], FILTER_VALIDATE_BOOLEAN );
				$gallery_settings['videoLoop']     = filter_var( $settings['loop'], FILTER_VALIDATE_BOOLEAN );
				$gallery_settings['videoFirst']    = 'content' === $settings['video_display_in'] ? filter_var( $settings['first_place_video'], FILTER_VALIDATE_BOOLEAN ) : false;
			}

			return htmlspecialchars( json_encode( $gallery_settings ) );

		}

		/**
		 * Check video existence.
		 *
		 * @return bool
		 */
		public function gallery_has_video() {

			$video_url = $this->get_video_url();

			if ( empty( $video_url ) ) {
				return false;
			}

			return true;

		}

		/**
		 * Return url on iframe video placeholder.
		 *
		 * @param $type
		 * @param $url
		 *
		 * @return string
		 */
		public function get_video_iframe_thumbnail_url( $type, $url ) {

			$oembed = _wp_oembed_get_object();
			$data   = $oembed->get_data( $url );

			if ( ! $data ) {
				return '';
			}

			$thumb_url = $data->thumbnail_url;

			if ( 'youtube' === $type ) {
				$thumb_url = str_replace( '/hqdefault.', '/maxresdefault.', $thumb_url );
			}

			return esc_url( $thumb_url );

		}

		/**
		 * Check if video has custom placeholder.
		 *
		 * @return bool
		 */
		public function video_has_custom_placeholder( $settings ) {
			return ! empty( jet_woo_gallery_video_integration()->get_video_custom_placeholder( $settings ) );
		}

		/**
		 * Return url for video thumbnail.
		 *
		 * @return string
		 */
		public function get_video_thumbnail_url() {

			$thumb_url  = '';
			$settings   = $this->get_settings();
			$video_url  = $this->get_video_url();
			$video_type = jet_woo_gallery_video_integration()->get_video_type( $settings );

			if ( ! $this->gallery_has_video() ) {
				return '';
			}

			if ( $this->video_has_custom_placeholder( $settings ) ) {
				$video_placeholder_id  = jet_woo_gallery_video_integration()->get_video_custom_placeholder( $settings );
				$video_placeholder_src = wp_get_attachment_image_src( $video_placeholder_id, 'full' );
				$thumb_url             = $video_placeholder_src[0];
			} elseif ( in_array( $video_type, [ 'youtube', 'vimeo' ] ) ) {
				$thumb_url = $this->get_video_iframe_thumbnail_url( $video_type, $video_url );
			}

			if ( empty( $thumb_url ) ) {
				return '';
			}

			return esc_url( $thumb_url );

		}

		/**
		 * Video HTML.
		 *
		 * Return generated video HTML.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return mixed
		 */
		public function get_video_html() {

			$settings  = $this->get_settings();
			$video_url = $this->get_video_url();

			if ( ! $this->gallery_has_video() ) {
				return '';
			}

			if ( 'self_hosted' === jet_woo_gallery_video_integration()->get_video_type( $settings ) ) {
				$self_hosted_params = $this->get_self_hosted_video_params();
				$self_hosted_attrs  = [
					'class' => 'jet-woo-product-video-player jet-woo-product-video-mejs-player',
					'src'   => $video_url,
				];

				if ( filter_var( $settings['show_play_button'], FILTER_VALIDATE_BOOLEAN ) ) {
					$self_hosted_attrs['class'] .= ' jet-woo-product-video-custom-play-button';
				}

				$attributes = array_merge( $self_hosted_attrs, $self_hosted_params );
				$video_html = '<video ' . jet_woo_product_gallery_tools()->implode_html_attributes( $attributes ) . '></video>';
			} else {
				$embed_params  = $this->get_embed_video_params();
				$embed_options = [
					'lazy_load' => false,
				];

				$embed_attr = [
					'class' => 'jet-woo-product-video-iframe',
					'allow' => 'autoplay;encrypted-media',
				];

				$video_html = Embed::get_embed_html( $video_url, $embed_params, $embed_options, $embed_attr );
			}

			return $video_html;

		}

		/**
		 * Get self hosted video param.
		 *
		 * Return list of the parameters for self hosted video.
		 *
		 * @since  1.0.0
		 * @since  2.1.9 Added `muted` param with autoplay for proper work after page refresh.
		 * @access public
		 *
		 * @return array
		 */
		public function get_self_hosted_video_params() {

			$settings = $this->get_settings();
			$params   = [];

			if ( 'content' === $settings['video_display_in'] && filter_var( $settings['autoplay'], FILTER_VALIDATE_BOOLEAN ) ) {
				$params['autoplay'] = '';
				$params['muted']    = '';
			}

			if ( filter_var( $settings['loop'], FILTER_VALIDATE_BOOLEAN ) ) {
				$params['loop'] = '';
			}

			if ( filter_var( $settings['mute'], FILTER_VALIDATE_BOOLEAN ) ) {
				$params['muted'] = '';
			}

			$params['style'] = 'max-width: 100%;';

			$controls = [ 'playpause', 'current', 'progress', 'duration', 'volume', 'fullscreen' ];

			$params['data-controls'] = esc_attr( json_encode( $controls ) );

			return $params;

		}

		/**
		 * Get embed video params.
		 *
		 * Return embedded video parameters list.
		 *
		 * @since  1.0.0
		 * @since  2.1.9 Code refactor.
		 * @access public
		 *
		 * @return array
		 */
		public function get_embed_video_params() {

			$settings   = $this->get_settings();
			$video_type = jet_woo_gallery_video_integration()->get_video_type( $settings );
			$params     = [
				'autoplay' => filter_var( $settings['autoplay'], FILTER_VALIDATE_BOOLEAN ) ? '1' : '0',
				'loop'     => filter_var( $settings['loop'], FILTER_VALIDATE_BOOLEAN ) ? '1' : '0',
			];

			switch ( $video_type ) {
				case 'youtube':
					$params['mute'] = filter_var( $settings['mute'], FILTER_VALIDATE_BOOLEAN ) ? '1' : '0';

					if ( '1' === $params['loop'] ) {
						$video_properties   = Embed::get_video_properties( esc_url( $this->get_video_url() ) );
						$params['playlist'] = $video_properties['video_id'];
					}

					if ( '1' === $params['autoplay'] ) {
						$params['mute'] = '1';
					}

					break;

				case 'vimeo':
					$params['muted']     = filter_var( $settings['mute'], FILTER_VALIDATE_BOOLEAN ) ? '1' : '0';
					$params['autopause'] = '0';

					if ( '1' === $params['autoplay'] ) {
						$params['muted'] = '1';
					}

					break;
			}

			if ( 'popup' === $settings['video_display_in'] ) {
				$params['autoplay'] = '0';
			}

			return $params;

		}

		/**
		 * Return video url.
		 *
		 * @return string
		 */
		public function get_video_url() {

			$video_url = '';
			$settings  = $this->get_settings();

			switch ( jet_woo_gallery_video_integration()->get_video_type( $settings ) ) {
				case 'self_hosted':
					$video_id  = jet_woo_gallery_video_integration()->get_self_hosted_video_id( $settings );
					$video_url = wp_get_attachment_url( $video_id );
					break;
				case 'youtube':
					$video_url = jet_woo_gallery_video_integration()->get_youtube_video_url( $settings );
					break;
				case 'vimeo':
					$video_url = jet_woo_gallery_video_integration()->get_vimeo_video_url( $settings );
					break;
			}

			if ( ! $video_url ) {
				return '';
			}

			return esc_url( $video_url );

		}

		/**
		 * Print HTML icon template.
		 *
		 * @param array  $setting
		 * @param string $format
		 * @param string $icon_class
		 * @param bool   $echo
		 *
		 * @return void|string
		 */
		public function render_icon( $setting = null, $format = '%s', $icon_class = '', $echo = true ) {

			$settings    = $this->get_settings();
			$new_setting = $this->__new_icon_prefix . $setting;
			$migrated    = isset( $settings['__fa4_migrated'][ $new_setting ] );
			$is_new      = empty( $settings[ $setting ] ) && class_exists( 'Elementor\Icons_Manager' ) && \Elementor\Icons_Manager::is_migration_allowed();
			$icon_html   = '';

			if ( $is_new || $migrated ) {
				$attr = [ 'aria-hidden' => 'true' ];

				if ( ! empty( $icon_class ) ) {
					$attr['class'] = $icon_class;
				}

				if ( isset( $settings[ $new_setting ] ) ) {
					ob_start();

					\Elementor\Icons_Manager::render_icon( $settings[ $new_setting ], $attr );

					$icon_html = ob_get_clean();
				}
			} else if ( ! empty( $settings[ $setting ] ) && ! is_array( $settings[ $setting ] ) ) {
				if ( empty( $icon_class ) ) {
					$icon_class = $settings[ $setting ];
				} else {
					$icon_class .= ' ' . $settings[ $setting ];
				}

				$icon_html = sprintf( '<i class="%s" aria-hidden="true"></i>', $icon_class );
			} else {
				$icon_html = $this->get_inline_svg( $settings[ $setting ] );
			}

			if ( empty( $icon_html ) ) {
				return;
			}

			if ( ! $echo ) {
				return sprintf( $format, $icon_html );
			}

			printf( $format, $icon_html );

		}

		/**
		 * Get inline svg.
		 *
		 * @param $value
		 *
		 * @return false|string
		 */
		public function get_inline_svg( $value ) {

			if ( ! isset( $value['id'] ) ) {
				return '';
			}

			$attachment_file = get_attached_file( $value['id'] );

			if ( ! $attachment_file ) {
				return '';
			}

			return file_get_contents( $attachment_file );

		}

		/**
		 * Get gallery image.
		 *
		 * Returns image HTML for gallery widget.
		 *
		 * @since  2.1.7
		 * @access public
		 *
		 * @param mixed  $id   Image ID.
		 * @param string $size Image size.
		 * @param string $src  Image source.
		 * @param bool   $main Image type.
		 *
		 * @return string
		 */
		public function get_gallery_image( $id, $size, $src, $main ) {

			$attr = [
				'title'                   => _wp_specialchars( get_post_field( 'post_title', $id ), ENT_QUOTES, 'UTF-8', true ),
				'data-caption'            => _wp_specialchars( get_post_field( 'post_excerpt', $id ), ENT_QUOTES, 'UTF-8', true ),
				'data-src'                => esc_url( $src[0] ),
				'data-large_image'        => esc_url( $src[0] ),
				'data-large_image_width'  => esc_attr( $src[1] ),
				'data-large_image_height' => esc_attr( $src[2] ),
				'class'                   => esc_attr( $main ? 'wp-post-image' : 'wp-post-gallery' ),
			];

			$attr = apply_filters( 'jet-gallery/render/image-attr', $attr, $id, $size, $main );

			return wp_get_attachment_image( $id, $size, false, $attr );

		}

		/**
		 * Print photoswipe gallery trigger button.
		 *
		 * @param $icon
		 */
		public function get_gallery_trigger_button( $icon ) {
			printf( '<a href="#" class="jet-woo-product-gallery__trigger"><span class="jet-woo-product-gallery__trigger-icon jet-product-gallery-icon">%s</span></a>', $icon );
		}

		/**
		 * Get slider arrow.
		 *
		 * Returns swiper slider arrow html.
		 *
		 * @since  2.1.0
		 * @since  2.1.9 Added specific class handling for thumbnails pagination arrows.
		 * @access public
		 *
		 * @param array  $classes List of classes.
		 * @param string $arrow   Arrow html.
		 *
		 * @return string
		 */
		public function get_slider_arrow( $classes, $arrow, $main ) {

			if ( empty( $arrow ) ) {
				return '';
			}

			if ( ! $main ) {
				$classes[] = 'jet-thumb-swiper-nav';
			}

			$arrow_format = sprintf( '<span class="jet-product-gallery-icon %s">%s</span>', implode( ' ', $classes ), $arrow );

			return apply_filters( 'jet-woo-product-gallery/slider/arrows-format', $arrow_format );

		}

		/**
		 * Returns columns classes string.
		 *
		 * @param array $columns
		 *
		 * @return string
		 */
		public function col_classes( $columns = [] ) {

			$columns = wp_parse_args( $columns, [
				'desk' => 1,
				'tab'  => 1,
				'mob'  => 1,
			] );

			$classes = [];

			foreach ( $columns as $device => $cols ) {
				if ( ! empty( $cols ) ) {
					$classes[] = sprintf( 'grid-col-%1$s-%2$s', $device, $cols );
				}
			}

			return implode( ' ', $classes );

		}

		/**
		 * Get columns settings.
		 *
		 * @param array $settings
		 *
		 * @return array
		 */
		public function get_columns_settings( $settings = [] ) {

			$desktop_col = ! empty( $settings['columns'] ) ? absint( $settings['columns'] ) : 4;
			$tablet_col  = ! empty( $settings['columns_tablet'] ) ? absint( $settings['columns_tablet'] ) : $desktop_col;
			$mobile_col  = ! empty( $settings['columns_mobile'] ) ? absint( $settings['columns_mobile'] ) : $tablet_col;

			return [
				'desk' => $desktop_col,
				'tab'  => $tablet_col,
				'mob'  => $mobile_col,
			];
		}

		/**
		 * Get image but url.
		 *
		 * Returns image tag or raw SVG.
		 *
		 * @since 1.0.0
		 * @since 2.1.9 Updated `$attr` handling.
		 *
		 * @param string $url  Image URL.
		 * @param array  $attr Image additional parameters.
		 *
		 * @return string
		 */
		public function get_image_by_url( $url = 'null', $attr = [] ) {

			$url = esc_url( $url );

			if ( empty( $url ) ) {
				return null;
			}

			$ext  = pathinfo( $url, PATHINFO_EXTENSION );
			$attr = array_merge( [ 'alt' => '' ], $attr );

			if ( 'svg' !== $ext ) {
				return sprintf( '<img src="%s" %s>', $url, jet_woo_product_gallery_tools()->implode_html_attributes( $attr ) );
			}

			$base_url = network_site_url( '/' );
			$svg_path = str_replace( $base_url, ABSPATH, $url );
			$key      = md5( $svg_path );
			$svg      = get_transient( $key );

			if ( ! $svg ) {
				$svg = file_get_contents( $svg_path );
			}

			if ( ! $svg ) {
				return sprintf( '<img src="%s" %s>', $url, jet_woo_product_gallery_tools()->implode_html_attributes( $attr ) );
			}

			set_transient( $key, $svg, DAY_IN_SECONDS );

			unset( $attr['alt'] );

			return sprintf( '<div %2$s>%1$s</div>', $svg, jet_woo_product_gallery_tools()->implode_html_attributes( $attr ) );

		}

		/**
		 * Get play button html.
		 *
		 * Returns galleries video play button html.
		 *
		 * @since  2.1.9
		 * @access public
		 *
		 * @param array $settings List of settings.
		 *
		 * @return string
		 */
		public function get_play_button_html( $settings ) {

			$html = '';

			if ( filter_var( $settings['show_play_button'], FILTER_VALIDATE_BOOLEAN ) ) {
				if ( 'icon' === $settings['play_button_type'] ) {
					$control = sprintf(
						'<span class="jet-woo-product-video__play-button-icon jet-product-gallery-icon">%s</span>',
						$this->render_icon( 'play_button_icon', '%s', '', false )
					);
				} else {
					$control = $this->get_image_by_url( $settings['play_button_image']['url'], [
						'class' => 'jet-woo-product-video__play-button-image',
						'alt'   => __( 'Play Video', 'jet-woo-product-gallery' ),
					] );
				}

				$screen_reader = sprintf( '<span class="screen-reader-text">%s</span>', __( 'Play Video', 'jet-woo-product-gallery' ) );
				$html          = sprintf( '<div class="jet-woo-product-video__play-button" role="button">%s %s</div>', $control, $screen_reader );
			}

			return $html;

		}

		/**
		 * Returns gallery wrapper array of classes depending on widget settings.
		 *
		 * @param array $classes
		 * @param null  $settings
		 *
		 * @return array|mixed
		 */
		public function get_wrapper_classes( $classes = [], $settings = null ) {

			if ( isset( $settings['gallery_trigger_type'] ) && 'button' === $settings['gallery_trigger_type'] ) {
				$classes[] = isset( $settings['gallery_button_position'] ) ? 'jet-woo-product-gallery__trigger--' . $settings['gallery_button_position'] : '';

				if ( isset( $settings['show_on_hover'] ) && filter_var( $settings['show_on_hover'], FILTER_VALIDATE_BOOLEAN ) ) {
					$classes[] = 'jet-woo-product-gallery__trigger--show-on-hover';
				}
			}

			return $classes;

		}

		/**
		 * Get globally affected template.
		 *
		 * @param null $name
		 *
		 * @return bool|mixed|string
		 */
		public function get_global_template( $name = null ) {
			return jet_woo_product_gallery()->get_template( $this->get_name() . '/global/' . $name . '.php' );
		}

	}

}
