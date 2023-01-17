<?php

namespace MABEL_SILITE\Code\Controllers
{

	use MABEL_SILITE\Core\Common\Frontend;
	use MABEL_SILITE\Core\Common\Managers\Config_Manager;
	use MABEL_SILITE\Core\Common\Managers\Settings_Manager;
	use MABEL_SILITE\Core\Models\Inline_Style;

	if(!defined('ABSPATH')){die;}

	class Public_Controller extends Frontend
	{
		public function __construct()
		{
			parent::__construct();

			$this->add_script_dependencies('jquery');
			$this->add_script(Config_Manager::$slug,'public/js/public.min.js');
			$this->add_style(Config_Manager::$slug,'public/css/public.min.css');


			$style = new Inline_Style(Config_Manager::$slug,'span.mb-siwc-tag',array(
				'margin-left' => '-'.intval(Settings_Manager::get_setting('tagsize')/2) .'px',
				'margin-top' => '-'.intval(Settings_Manager::get_setting('tagsize')/2) .'px',
				'color' => Settings_Manager::get_setting('tagfgcolor'),
				'width' => Settings_Manager::get_setting('tagsize') .'px',
				'height' => Settings_Manager::get_setting('tagsize') .'px',
				'line-height' => Settings_Manager::get_setting('tagsize') .'px',
				'background' => Settings_Manager::get_setting('tagbgcolor'),
				'font-size' => Settings_Manager::get_setting('iconsize') .'px',
				'border-radius' => Settings_Manager::get_setting('tagborderradius') .'%',
			));

			$this->add_inline_style($style);
		}
	}
}