<?php

namespace Minimog\Woo;

defined( 'ABSPATH' ) || exit;

class Product_Variation {
	protected static $instance = null;

	private static $gallery_size = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function initialize() {
		add_filter( 'woocommerce_available_variation', [ $this, 'update_gallery_image_src' ], 20, 3 );
	}

	public function get_gallery_image_size() {
		if ( null === self::$gallery_size ) {
			$thumbnail_size = \Minimog_Woo::instance()->get_single_product_image_size( 100 );
			$site_layout    = \Minimog_Woo::instance()->get_single_product_site_layout();

			if ( 'wide' === $site_layout ) {
				$thumbnail_size = \Minimog_Woo::instance()->get_single_product_image_size( 130 );
			}

			self::$gallery_size = apply_filters( 'minimog/single_product/feature_slider/thumbnail_size', $thumbnail_size );
		}

		return self::$gallery_size;
	}

	/**
	 * Change gallery thumbnail size
	 *
	 * @param                       $settings
	 * @param \WC_Product_Variable  $product
	 * @param \WC_Product_Variation $variation
	 *
	 * @return mixed
	 */
	public function update_gallery_image_src( $settings, $product, $variation ) {
		$attachment_id = $variation->get_image_id();

		$attachment = get_post( $attachment_id );

		if ( $attachment && 'attachment' === $attachment->post_type ) {
			$gallery_src = \Minimog_Image::get_attachment_url_by_id( [
				'id'   => $attachment_id,
				'size' => $this->get_gallery_image_size(),
			] );

			$settings['image']['gallery_thumbnail_src'] = $gallery_src;
		}

		return $settings;
	}
}

Product_Variation::instance()->initialize();
