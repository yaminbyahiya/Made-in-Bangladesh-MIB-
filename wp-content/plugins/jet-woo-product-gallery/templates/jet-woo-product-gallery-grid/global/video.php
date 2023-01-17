<?php
/**
 * JetGallery Grid video template.
 */

if ( ! $this->gallery_has_video() ) {
	return null;
}

$ratio_classes       = [];
$play_button_html    = $this->get_play_button_html( $settings );
$video_thumbnail_url = $this->get_video_thumbnail_url();
$overlay_styles      = ! empty( $video_thumbnail_url ) ? 'style="background-image: url(' . $video_thumbnail_url . ');"' : '';

if ( 'self_hosted' !== $video_type ) {
	$ratio_classes = [
		'jet-woo-product-video-aspect-ratio',
		'jet-woo-product-video-aspect-ratio--' . $settings['aspect_ratio'],
	];
}
?>

<div class="jet-woo-product-gallery__image-item">
	<div class="jet-woo-product-gallery__image jet-woo-product-gallery--with-video">
		<?php
		printf( '<div class="jet-woo-product-video %s">%s</div>', implode( ' ', $ratio_classes ), $video );
		printf( '<div class="jet-woo-product-video__overlay" %s>%s</div>', $overlay_styles, $play_button_html );
		?>
	</div>
</div>