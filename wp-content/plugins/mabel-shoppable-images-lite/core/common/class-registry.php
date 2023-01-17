<?php

namespace MABEL_SILITE\Core\Common
{
	class Registry
	{

		/**
		 * @var Loader
		 */
		private static $loader;

		/**
		 * @return \MABEL_SILITE\Core\Common\Loader
		 */
		public static function get_loader()
		{
			if(self::$loader === null)
			{
				self::$loader = new Loader();
			}

			return self::$loader;
		}

	}
}