<?php

namespace MABEL_SILITE\Core\Common
{

	use MABEL_SILITE\Core\Common\Linq\Enumerable;
	use MABEL_SILITE\Core\Common\Managers\Config_Manager;
	use MABEL_SILITE\Core\Models\Inline_Style;

	class Presentation_Base
	{
		private $script_dependencies;
		private $scripts;
		/**
		 * @var Inline_Style[]
		 */
		private $inline_styles;
		private $styles;
		public $loader;
		/**
		 * @var array with key, value pairs to send to the frontend.
		 */
		private $script_variables;

		public function __construct()
		{
			$this->loader = Registry::get_loader();
			$this->script_dependencies = array();
			$this->scripts = array();
			$this->styles = array();
			$this->inline_styles = array();
			$this->script_variables = array();
		}

		/**
		 * @param $id
		 * @param $file
		 * @param string|array $dependencies
		 */
		public function add_script($id, $file)
		{
			$this->scripts[$id] = $file;
		}

		public function add_style($id, $file)
		{
			$this->styles[$id] = $file;
		}

		public function add_inline_style(Inline_Style $style)
		{
			array_push($this->inline_styles, $style);
		}

		public function add_script_variable($key, $val)
		{
			$this->script_variables[$key] = $val;
		}

		public function add_script_dependencies($dependencies)
		{
			if(is_string($dependencies))
				array_push($this->script_dependencies, $dependencies);
			elseif(is_array($dependencies))
				$this->script_dependencies = array_merge($this->script_dependencies,$dependencies);
		}

		public function register_scripts()
		{
			foreach ($this->scripts as $id => $file)
			{
				wp_enqueue_script($id, Config_Manager::$url . $file, $this->script_dependencies, Config_Manager::$version, false);
				//TODO: ideally, this would be global script vars, and not repeated over each script handle.
				if(sizeof($this->script_variables) > 0) {
					wp_localize_script($id,'mabel_script_vars',$this->script_variables);
				}
			}
		}

		public function register_styles()
		{
			foreach($this->styles as $id => $file)
			{
				wp_enqueue_style(
					$id,
					Config_Manager::$url . $file,
					array(),
					Config_Manager::$version,
					'all'
				);
			}

			foreach($this->inline_styles as $style)
			{
				$str = join('',Enumerable::from($style->styles)->select(function($v,$k){
					return $k .':'.$v.';';
				})->toArray());

				wp_add_inline_style( $style->handle, $style->rule . '{' . wp_strip_all_tags($str) . '}' );
			}
		}

		public function add_ajax_function($name,$component,$callable,$frontend = true,$backend = true)
		{
			if($frontend)
				$this->loader->add_action('wp_ajax_nopriv_' . $name,$component,$callable);
			if($backend)
				$this->loader->add_action('wp_ajax_' . $name,$component,$callable);
		}
	}
}
