<?php
namespace MABEL_SILITE\Code\Models
{

	class Tag
	{
		public $posX;
		public $posY;
		public $product_id;
		public $title;
		public $price;
		public $link;
		public $thumb;
		public function __construct($x, $y, $pid = null)
		{
			$this->posX = $x;
			$this->posY = $y;
			$this->product_id = $pid;
		}
	}

}
