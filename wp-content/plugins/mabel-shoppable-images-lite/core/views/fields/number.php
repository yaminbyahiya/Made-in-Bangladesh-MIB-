<?php
/** @var \MABEL_SILITE\Core\Models\Text_Option $option */
if(!defined('ABSPATH')){
	die;
}

$has_pre_text = false;

if(isset($option->pre_text)) {
	echo '<span>' . esc_html($option->pre_text) . '</span>';
	$has_pre_text = true;
}
?>

<input
	class="<?php echo $has_pre_text ? '' : 'widefat'; ?>"
	style="<?php echo $has_pre_text? 'width:100px;' : ''; ?>"
	type="number"
	name="<?php echo $option->name; ?>"
	value="<?php echo htmlspecialchars($option->value);?>"
	<?php echo isset($option->dependency) ? 'data-dependency="' . htmlspecialchars(json_encode($option->dependency,ENT_QUOTES)) . '"':''; ?>
/>
<?php

if(isset($option->post_text))
	echo '<span>' . esc_html($option->post_text) . '</span>';

if(isset($option->extra_info))
	echo '<div class="p-t-1 extra-info">' . esc_html($option->extra_info) .'</div>';

?>
