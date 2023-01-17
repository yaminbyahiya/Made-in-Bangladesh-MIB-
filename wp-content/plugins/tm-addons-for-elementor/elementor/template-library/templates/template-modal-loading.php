<?php
/**
 * Templates Loader View
 */

?>
<div class="elementor-loader-wrapper">
	<div class="elementor-loader">
		<div class="elementor-loader-boxes">
			<div class="elementor-loader-box"></div>
			<div class="elementor-loader-box"></div>
			<div class="elementor-loader-box"></div>
			<div class="elementor-loader-box"></div>
		</div>
	</div>
	<div class="elementor-loading-title"><?php echo wp_kses_post( __( 'Loading', 'tm-addons' ) ); ?></div>
</div>
