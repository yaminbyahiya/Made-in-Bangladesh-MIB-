<?php
/**
 * Templates Loader Error
 */

?>
<div class="elementor-library-error">
	<div class="elementor-library-error-message">
		<?php
		echo wp_kses_post( __( 'Template couldn\'t be loaded.', 'tm-addons' ) );
		?>
	</div>
</div>
