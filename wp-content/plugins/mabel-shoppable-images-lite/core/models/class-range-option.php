<?php

namespace MABEL_SILITE\Core\Models {

	class Range_Option extends Option
	{
		public $min;
		public $max;
		public $step;

		public function __construct($id, $value, $title, $min, $max, $step = 1, $extra = null, Option_Dependency $dependency = null)
		{
			parent::__construct($id,$value,$title,$extra,$dependency);

			$this->min = $min;
			$this->max = $max;
			$this->step = $step;
		}

	}
}