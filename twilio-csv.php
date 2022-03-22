<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://thejohnson.group/
 * @since             1.0.0
 * @package           Twilio_Csv
 *
 * @wordpress-plugin
 * Plugin Name:       Twilio CSV Upload
 * Plugin URI:        https://thejohnson.group/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Tyler Karle
 * Author URI:        https://thejohnson.group/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       twilio-csv
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'TWILIO_CSV_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-twilio-csv-activator.php
 */
function activate_twilio_csv() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-twilio-csv-activator.php';
	Twilio_Csv_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-twilio-csv-deactivator.php
 */
function deactivate_twilio_csv() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-twilio-csv-deactivator.php';
	Twilio_Csv_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_twilio_csv' );
register_deactivation_hook( __FILE__, 'deactivate_twilio_csv' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-twilio-csv.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_twilio_csv() {

	$plugin = new Twilio_Csv();
	$plugin->run();

}
run_twilio_csv();
