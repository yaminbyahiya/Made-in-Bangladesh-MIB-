<?php

namespace TMAddons\Elementor\Builder\Library;

use Elementor\Modules\Library\Documents\Library_Document;

defined( 'ABSPATH' ) || exit;

abstract class Library_Base extends Library_Document {

	const LOCATION_META_KEY = '_tm_location';

	/**
	 * Get document properties.
	 *
	 * Retrieve the document properties.
	 *
	 * @since  2.0.0
	 * @access public
	 * @static
	 *
	 * @return array Document properties.
	 */
	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['support_kit']           = true;
		$properties['support_tm_conditions'] = true;
		$properties['condition_type']        = 'general';

		return $properties;
	}

	public function get_location() {
		$value = self::get_property( 'tm_location' );

		if ( ! $value ) {
			$value = $this->get_main_meta( self::LOCATION_META_KEY );
		}

		return $value;
	}
}
