<?php
/**
 * Modal Share
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="minimog-modal modal-product-share" id="modal-product-share">
	<div class="modal-overlay"></div>
	<div class="modal-content">
		<div class="button-close-modal" role="button" aria-label="<?php esc_attr_e( 'Close', 'minimog' ); ?>"></div>
		<div class="modal-content-wrap">
			<div class="modal-content-inner">
				<?php
				$social_sharing = \Minimog::setting( 'social_sharing_item_enable' );
				if ( ! empty( $social_sharing ) ) :
					?>
					<div class="product-share">
						<div class="form-control">
							<label>
								<?php esc_html_e( 'Copy link', 'minimog' ); ?>
							</label>
							<input type="text" readonly disabled
							       value="<?php echo esc_url( $product->get_permalink() ); ?>">
						</div>
						<div class="product-share-list">
							<label>
								<?php esc_html_e( 'Share', 'minimog' ); ?>
							</label>
							<div class="share-list">
								<?php \Minimog_Templates::get_sharing_list( [
									'tooltip_position' => 'top-right',
								] ); ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
