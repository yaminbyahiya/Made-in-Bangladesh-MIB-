<?php
	use \MABEL_SILITE\Core\Common\Html;
	/** @var \MABEL_SILITE\Core\Models\Container_Option $option */
?>
<div class="mabel-accordion">
	<button><?php echo $option->button_text; ?></button>
	<div style="display: none;">
		<table class="form-table">
			<?php
				foreach($option->options as $o)
				{
					echo '<tr><th scope="row">'.$o->title.'</th>';
					echo '<td>';
						Html::option($o);
					echo '</td>';
				}
			?>
		</table>
	</div>
</div>