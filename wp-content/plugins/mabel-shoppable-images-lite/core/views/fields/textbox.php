<?php
/** @var \MABEL_SILITE\Core\Models\Text_Option $option */
use MABEL_SILITE\Core\Common\Managers\Config_Manager;
if(!defined('ABSPATH')){
	die;
}

?>

<input
	class="widefat"
	type="text"
	name="<?php echo $option->name; ?>"
	value="<?php echo htmlspecialchars($option->value);?>"
    <?php echo isset($option->dependency) ? 'data-dependency="' . htmlspecialchars(json_encode($option->dependency,ENT_QUOTES)) . '"':''; ?>
/>
<?php
	$option->display_help();
	if(isset($option->extra_info))
		echo '<div class="p-t-1 extra-info">' . esc_html($option->extra_info) .'</div>';
?>
