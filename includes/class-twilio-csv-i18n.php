<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://thejohnson.group/
 * @since      1.0.0
 *
 * @package    Twilio_Csv
 * @subpackage Twilio_Csv/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Twilio_Csv
 * @subpackage Twilio_Csv/includes
 * @author     Tyler Karle <solo.driver.bob@gmail.com>
 */
class Twilio_Csv_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'twilio-csv',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
