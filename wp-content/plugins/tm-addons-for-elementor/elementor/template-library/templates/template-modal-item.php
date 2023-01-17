<?php
/**
 * Template Item
 */

?>
<div class="tm-template-library__wrapper">
	<div class="tm-template-library-template-body">
		<div class="tm-template-library-template-screenshot">
			<div class="tm-template-library-template-preview">
				<i class="eicon-plus-circle-o"></i>
			</div>
			<img src="{{ thumbnail }}" alt="{{ title }}" loading="lazy">
		</div>
	</div>
	<div class="tm-template-library-template-footer">
		<div class="tm-template-library-template-title">{{{ title }}}</div>
		<button class="tm-template-library-template-action tm-template-insert">
			<i class="eicon-file-download"></i>
			<span class="tm-button-title"><?php echo wp_kses_post( __( 'Insert', 'tm-addons' ) ); ?></span>
		</button>
	</div>
</div>
