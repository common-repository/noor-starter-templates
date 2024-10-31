<?php

/**
 * Class for importing fluent data.
 *
 * @package Noor Starter Templates
 */

namespace Noor_Starter_Templates;

use RevSlider;

if ( ! class_exists( 'RevSlider', false ) ) {
	return;
}

/*
 * Class for importing revolution slider.
 */
class Noor_Starter_Templates_RevSlider_Import {
	/**
	 * Import revolution slider.
	 *
	 * @param string $file Path to the revolution slider zip file.
	 */
	public static function import( $file ) {
		if ( ! class_exists( 'RevSlider', false ) ) {
			return 'failed';
		}

		$importer = new RevSlider();
		if ( ! empty( $file ) ) {
			$importer->importSliderFromPost( true, true, $file );
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return 'true';
		}
	}
}
