<?php

namespace MABEL_SILITE\Code\Models
{

	use MABEL_SILITE\Core\Common\Managers\Settings_Manager;

	class Shoppable_Image_VM
	{
		public $show_error;
		public $image;
		public $icon;
		public $size;
		public $button_text;
		public $tags;

		public function __construct()
		{
			$this->tags = array();
			$this->button_text = Settings_Manager::get_setting('buttontext');
		}
	}
}