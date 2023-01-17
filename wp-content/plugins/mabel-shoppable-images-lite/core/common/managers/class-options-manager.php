<?php

namespace MABEL_SILITE\Core\Common\Managers
{
	use MABEL_SILITE\Core\Models\Hidden_Option;
	use MABEL_SILITE\Core\Models\Option;
	use MABEL_SILITE\Core\Models\Option_Section;
	use Exception;

	/**
	 * Register all options to show in admin screens.
	 * Class Options_Manager
	 * @package MABEL_SILITE\Core
	 */
	class Options_Manager
	{
		/**
		 * @var Option_Section[] list of sections with options.
		 */
		private $sections;

		/**
		 * @var Option[] list of hidden settings that need to be in the form for a round trip.
		 */
		private $hidden_settings;

		public function __construct()
		{
			$this->sections = array();
			$this->hidden_settings = array();
		}

		#region Option Adding

		public function add_option($section_id, Option $option)
		{
			$section = $this->get_section($section_id);
			if($section == null)
				throw new Exception("Couldn't add option to section. Section doesn't exist");
			$option->name = $this->set_option_name($option);
			$section->add_option($option);
			return $option;
		}

		public function add_hidden_setting($setting_id, $value)
		{
			$option = new Hidden_Option($setting_id,$value,null);
			$option->name = $this->set_option_name($option);
			array_push($this->hidden_settings, $option);
		}

		private function set_option_name(Option $o)
		{
			return Config_Manager::$settings_key . '[' . $o->id . ']';
		}
		#endregion

		#region Section Code
		/**
		 * Add a setting section.
		 * @param $id string
		 * @param $title string
		 * @param $icon string
		 * @param $active boolean
		 */
		public function add_section($id, $title, $icon, $active = false)
		{
			array_push($this->sections, new Option_Section($id, $title, $icon,$active));
		}

		private function get_section($section_id)
		{
			foreach($this->sections as $section)
			{
				if($section_id === $section->id)
					return $section;
			}

			return null;
		}

		public function get_sections()
		{
			return $this->sections;
		}

		public function get_hidden_settings()
		{
			return $this->hidden_settings;
		}
		#endregion
	}
}