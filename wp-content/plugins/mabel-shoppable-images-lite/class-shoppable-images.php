<?php

namespace MABEL_SILITE
{

	use MABEL_SILITE\Code\Controllers\Public_Controller;
	use MABEL_SILITE\Code\Controllers\Shortcode_Controller;
	use MABEL_SILITE\Core\Common\Managers\Config_Manager;
	use MABEL_SILITE\Core\Common\Managers\Language_Manager;
	use MABEL_SILITE\Core\Common\Managers\Settings_Manager;
	use MABEL_SILITE\Core\Common\Registry;
	use MABEL_SILITE\Code\Controllers\Admin_Controller;

	if(!defined('ABSPATH')){die;}

	class Shoppable_Images
	{
		/**
		 * @var Language_Manager language manager.
		 */
		protected $language_manager;

		/**
		 * Business_Hours_Indicator constructor.
		 *
		 * @param $dir string
		 * @param $url string
		 * @param $slug string
		 * @param $version string
		 */
		public function __construct($dir, $url, $plugin_base, $name, $version, $settings_key)
		{
			// Init meta info.
			Config_Manager::init($dir, $url, $plugin_base, $version, $settings_key, $name);
		}

		public function run()
		{
			// Init translations.
			$this->language_manager = new Language_Manager();

			// Init settings with defaults.
			Settings_Manager::init(array(
				'tagbgcolor' => "#ffffff",
				'tagfgcolor' => '#7b53c1',
				'tagsize' => 25,
				'iconsize' => 14,
				'tagicon' => 'siwc-icon-plus_thin',
				'tagborderradius' => 50,
				'buttontext' => 'Shop it'
			));

			// Kick off admin page.
			if(is_admin())
				new Admin_Controller();

			// Kick off public side of things.
			new Public_Controller();

			// Register shortcodes
			new Shortcode_Controller();

			// Register post type
			Registry::get_loader()->add_action('init',$this,'register_post_type');
			// Kick off!
			Registry::get_loader()->run();
		}

		function register_post_type() {
			register_post_type('mb_siwc_lite_image',array(
				'public' => false,
				'exclude_from_search' => true,
				'publicly_queryable' => false,
			));
		}
	}
}