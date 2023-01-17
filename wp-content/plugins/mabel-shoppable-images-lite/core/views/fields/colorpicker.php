<?php
/** @var \MABEL_SILITE\Core\Models\ColorPickerOption $option */
?>

<input
	type="text"
	name="<?php echo $option->name; ?>"
	value="<?php echo htmlspecialchars($option->value);?>"
	<?php echo isset($option->dependency) ? 'data-dependency="' . htmlspecialchars(json_encode($option->dependency,ENT_QUOTES)) . '"':''; ?>
	class="color-picker"
/>

<?php
	if(isset($option->extra_info))
		echo '<div class="p-t-1 extra-info">' . esc_html($option->extra_info) .'</div>';
?>