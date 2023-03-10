<?php
/**
 * JetGallery Anchor Nav thumbnails template.
 */


$image_src                = wp_get_attachment_image_src( $attachment_id, 'full' );
$image                    = $this->get_gallery_image( $attachment_id, $settings['image_size'], $image_src, false );
$anchor_nav_controller_id = $this->get_unique_controller_id();

array_push( $anchor_nav_controller_ids, $anchor_nav_controller_id );
?>

<div class="jet-woo-product-gallery__image-item" id="<?php echo $anchor_nav_controller_id ?>">
	<div class="jet-woo-product-gallery__image <?php echo $zoom ?>">
		<?php
		if ( $enable_gallery && 'button' === $gallery_trigger ) {
			$this->get_gallery_trigger_button( $this->render_icon( 'gallery_button_icon', '%s', '', false ) );
		}

		printf(
			'<a class="jet-woo-product-gallery__image-link %s" href="%s" itemprop="image" title="%s" rel="prettyPhoto%s">%s</a>',
			$trigger_class,
			esc_url( $image_src[0] ),
			get_post_field( 'post_title', $attachment_id ),
			$gallery,
			$image
		);
		?>
	</div>
</div>