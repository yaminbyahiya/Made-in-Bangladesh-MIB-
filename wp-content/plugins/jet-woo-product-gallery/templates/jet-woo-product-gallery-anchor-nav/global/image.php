<?php
/**
 * JetGallery Anchor Nav main image template.
 */

$image_id                 = get_post_thumbnail_id( $post_id );
$image_src                = wp_get_attachment_image_src( $image_id, 'full' );
$image                    = $this->get_gallery_image( $image_id, $settings['image_size'], $image_src, true );
$anchor_nav_controller_id = $anchor_nav_controller_ids[0];

if ( $this->gallery_has_video() && $first_place_video ) {
	array_push( $anchor_nav_controller_ids, $this->get_unique_controller_id() );
}
?>

<div class="jet-woo-product-gallery__image-item featured" id="<?php echo $anchor_nav_controller_id; ?>">
	<div class="jet-woo-product-gallery__image <?php echo $zoom ?>">
		<?php
		printf(
			'<a class="jet-woo-product-gallery__image-link %s" href="%s" itemprop="image" title="%s" rel="prettyPhoto%s">%s</a>',
			$trigger_class,
			esc_url( $image_src[0] ),
			esc_attr( get_post_field( 'post_title', $image_id ) ),
			$gallery,
			$image
		);

		if ( $enable_gallery && 'button' === $gallery_trigger ) {
			$this->get_gallery_trigger_button( $this->render_icon( 'gallery_button_icon', '%s', '', false ) );
		}
		?>
	</div>
</div>