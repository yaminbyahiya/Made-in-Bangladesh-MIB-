<?php
/** @var \MABEL_SILITE\Core\Models\Range_Option $option */
if(!defined('ABSPATH')){ die; }
?>

<input
	style="opacity: 0;"
	name="<?php echo $option->name; ?>"
	type="range"
	min="<?php echo $option->min; ?>"
	max="<?php echo $option->max; ?>"
	step="<?php echo $option->step; ?>"
	value="<?php echo $option->value; ?>"
/>

<?php
	if(isset($option->extra_info))
		echo '<div class="p-t-1 extra-info">' . esc_html($option->extra_info) .'</div>';
?>

