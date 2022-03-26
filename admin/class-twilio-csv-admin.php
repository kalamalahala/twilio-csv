<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://thejohnson.group/
 * @since      1.0.0
 *
 * @package    Twilio_Csv
 * @subpackage Twilio_Csv/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Twilio_Csv
 * @subpackage Twilio_Csv/admin
 * @author     Tyler Karle <solo.driver.bob@gmail.com>
 */

 // Import Twilio Functionality
 require_once( plugin_dir_path(__FILE__) . '/../twilio/Twilio/autoload.php' );
 use Twilio\Rest\Client;
class Twilio_Csv_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Twilio_Csv_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Twilio_Csv_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/twilio-csv-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Twilio_Csv_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Twilio_Csv_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/twilio-csv-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 *  Register the administration menu for this plugin into the WordPress Dashboard
	 * @since    1.0.0
	 */

	public function add_twilio_csv_admin_settings()
	{

		/*
		* Add a settings page for this plugin to the Settings menu.
		*
		* Administration Menus: http://codex.wordpress.org/Administration_Menus
		*
		*/
		add_options_page(
			'TWILIO CSV SETTINGS',
			'TWILIO CSV',
			'manage_options',
			$this->plugin_name,
			array($this, 'display_twilio_csv_settings_page')
		);
	}

	/**
	 * Render the settings page for this plugin.( The html file )
	 *
	 * @since    1.0.0
	 */

	public function display_twilio_csv_settings_page()
	{
		include_once('partials/twilio-csv-admin-display.php');
	}

	/**
	 * Registers and Defines the necessary fields we need.
	 *
	 */
	public function twilio_csv_admin_settings_save()
	{

		register_setting($this->plugin_name, $this->plugin_name, array($this, 'plugin_options_validate'));

		add_settings_section('twilio_csv_main', 'Main Settings', array($this, 'twilio_csv_section_text'), 'twilio-csv-settings-page');

		add_settings_field('api_sid', 'API SID', array($this, 'twilio_csv_setting_sid'), 'twilio-csv-settings-page', 'twilio_csv_main');

		add_settings_field('api_auth_token', 'API AUTH TOKEN', array($this, 'twilio_csv_setting_token'), 'twilio-csv-settings-page', 'twilio_csv_main');

		// add_settings_field('sending_number', 'Primary Sending Phone Number', )
	}

	/**
	 * Displays the settings sub header
	 *
	 */
	public function twilio_csv_section_text()
	{
		echo '<h3>Edit api details</h3>';
	}

	/**
	 * Renders the sid input field
	 *
	 */
	public function twilio_csv_setting_sid()
	{

		$options = get_option($this->plugin_name);
		echo "<input id='plugin_text_string' name='$this->plugin_name[api_sid]' size='40' type='text' value='{$options['api_sid']}' />";
	}

	/**
	 * Renders the auth_token input field
	 *
	 */
	public function twilio_csv_setting_token()
	{
		$options = get_option($this->plugin_name);
		echo "<input id='plugin_text_string' name='$this->plugin_name[api_auth_token]' size='40' type='text' value='{$options['api_auth_token']}' />";
	}

	public function twilio_csv_sending_number()
	{
		$options = get_option($this->plugin_name);
		echo "<input id='plugin_text_string' name='$this->plugin_name[sending_number]' size='40' type='text' value='{$options['sending_number']}' />";
	}

	/**
	 * Sanitises all input fields.
	 *
	 */
	public function plugin_options_validate($input)
	{
		$newinput['api_sid'] = trim($input['api_sid']);
		$newinput['api_auth_token'] = trim($input['api_auth_token']);
		$newinput['sending_number'] = trim($input['sending_number']);
		
		return $newinput;
	}


	/**
	 * Register the sms page in the admin area
	 *
	 * @since 1.0.0
	 */
	public function register_twilio_csv_sample_page()
	{
		add_submenu_page(
			'tools.php',
			__('TWILIO CSV SMS Sample', $this->plugin_name . '-sms'),
			__('Send SMS', $this->plugin_name . '-sms'),
			'manage_options',
			$this->plugin_name . '-sms',
			array(
				$this,
				'display_twilio_csv_sample_page'
			)
		);
	}

	/**
	 * Display the content located in the partials file
	 *
	 * @return void
	 */
	public function display_twilio_csv_sample_page()
	{
		include_once('partials/twilio-csv-admin-sms.php');
	}

	public function send_message()
	{
		if (!isset($_POST['send_sms_message'])) {
			return;
		}

		$to        = (isset($_POST['numbers'])) ? $_POST['numbers'] : '';
		$sender_id = (isset($_POST['sender']))  ? $_POST['sender']  : '';
		$message   = (isset($_POST['message'])) ? $_POST['message'] : '';

		$api_details = get_option('twilio-csv');
		if (is_array($api_details) and count($api_details) != 0) {
			$TWILIO_SID = $api_details['api_sid'];
			$TWILIO_TOKEN = $api_details['api_auth_token'];
		}
		try{
			// $to = explode(',', $to);

			$client = new Client($TWILIO_SID, $TWILIO_TOKEN);

			  $response = $client->messages->create(
					$to,
					array(
					  'from' => $sender_id,
					 'body' => $message
					)
				);

			   self::DisplaySuccess();

		} catch (Exception $e) {

			self::DisplayError( $e->getMessage() );
		}           
	}

			/**
		 * Designs for displaying Notices
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var $message - String - The message we are displaying
		 * @var $status   - Boolean - its either true or false
		 */
		public static function admin_notice($message, $status = true) {
			$class =  ($status) ? 'notice notice-success' : 'notice notice-error';
			$message = __( $message, 'sample-text-domain' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
		}

		/**
		 * Displays Error Notices
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		public static function DisplayError($message = "Aww!, there was an error.") {
			add_action( 'admin_notices', function() use($message) {
			self::admin_notice($message, false);
			} );
		}

		/**
		 * Displays Success Notices
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		public static function DisplaySuccess($message = "Successful!") {
			add_action( 'admin_notices', function() use($message) {
			self::admin_notice($message, true);
			} );
		}

}
