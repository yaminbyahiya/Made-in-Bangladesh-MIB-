<?php
/**
 * Template Library Modal Header
 */

use PremiumAddons\Includes\Helper_Functions;

?>
<span class="tm-template-modal-header-logo-icon">
	<img src="<?php echo esc_url( TM_ADDONS_URL . 'assets/images/icon.png' ); ?>">
</span>
<span class="tm-template-modal-header-logo-label">
	<?php echo wp_kses_post( __( 'Templates', 'tm-addons' ) ); ?>
</span>

