<?php

namespace MABEL_SILITE\Core\Common
{

	class Frontend extends Presentation_Base
	{
		public function __construct()
		{
			parent::__construct();
			$this->add_script_variable('ajaxurl',admin_url('admin-ajax.php'));
			$this->loader->add_action( 'wp_enqueue_scripts', $this, 'register_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $this, 'register_scripts' );
		}

	}
}