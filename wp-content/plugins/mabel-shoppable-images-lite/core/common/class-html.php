<?php

namespace MABEL_SILITE\Core\Common
{

	use MABEL_SILITE\Core\Common\Managers\Config_Manager;
	use MABEL_SILITE\Core\Models\Checkbox_Option;
	use MABEL_SILITE\Core\Models\ColorPicker_Option;
	use MABEL_SILITE\Core\Models\Container_Option;
	use MABEL_SILITE\Core\Models\Custom_Option;
	use MABEL_SILITE\Core\Models\Dropdown_Option;
	use MABEL_SILITE\Core\Models\Number_Option;
	use MABEL_SILITE\Core\Models\Option;
	use MABEL_SILITE\Core\Models\Range_Option;
	use MABEL_SILITE\Core\Models\Text_Option;

	class Html
	{
		/**
		 * Echo a view
		 * @param $view
		 * @param $model
		 */
		public static function partial($view,$model)
		{
			ob_start();
			include Config_Manager::$dir . $view . '.php';
			echo ob_get_clean();
		}

		/**
		 * return a view.
		 * @param $view
		 * @param $model
		 *
		 * @return string
		 */
		public static function view($view,$model)
		{
			ob_start();
			include Config_Manager::$dir . 'code/views/' . $view . '.php';
			return ob_get_clean();
		}

		public static function option(Option $option)
		{
			$field_dir = Config_Manager::$dir . 'core/views/fields/';

			if($option instanceof Checkbox_Option) {
				return require $field_dir . 'checkbox.php';
			}

			if($option instanceof Dropdown_Option) {
				return require $field_dir . 'dropdown.php';
			}

			// Needs to be checked before Text_Option as it derives from it.
			if($option instanceof Number_Option) {
				return require $field_dir . 'number.php';
			}

			if($option instanceof Text_Option) {
				return require $field_dir . 'textbox.php';
			}

			if($option instanceof ColorPicker_Option) {
				return require $field_dir . 'colorpicker.php';
			}

			if($option instanceof Range_Option) {
				return require $field_dir . 'rangeslider.php';
			}

			if($option instanceof Custom_Option) {
				$data = $option->data;
				$slug = Config_Manager::$slug;
				return  require Config_Manager::$dir . 'admin/views/' . $option->template . '.php';
			}

			if($option instanceof Container_Option) {
				return require $field_dir . 'container-option.php';
			}

		}

	}
}