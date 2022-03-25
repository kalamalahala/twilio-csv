<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://thejohnson.group/
 * @since      1.0.0
 *
 * @package    Twilio_Csv
 * @subpackage Twilio_Csv/public
 */

 // Twilio Dependency
 require_once( plugin_dir_path(__FILE__) . '/../twilio/Twilio/autoload.php' );
 use Twilio\Rest\Client;

 // json_encode dependency from github
 require_once( plugin_dir_path(__FILE__) . '/../vendor/autoload.php' );
 use Shuchkin\SimpleXLSX;

//  C:\Users\solod\Desktop\repos\twilio-csv\vendor\shuchkin\simplexlsx\src\SimpleXLSX.php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Twilio_Csv
 * @subpackage Twilio_Csv/public
 * @author     Tyler Karle <solo.driver.bob@gmail.com>
 */
class Twilio_Csv_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/twilio-csv-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/twilio-csv-public.js', array( 'jquery' ), $this->version, false );

	}

	function process_pending_messages( $contact_data, $num_entries ) {
		if (!$contact_data) { return false; }

		global $wpdb;
		$csv_table = $wpdb->prefix.'twilio_csv_entries';
		$csv_data = array(
			'id' => '',
			'date' => '',
			'contact_data' => $contact_data,
			'num_entries' => $num_entries,
			'send_count' => 0,
			'success_count' => 0,
			'fail_count' => 0
		);
		$insert_csv = $wpdb->insert($csv_table, $csv_data, null);
		if ($insert_csv) {
			return true;
		} else {
			return false;
		}
		
		// $query_INSERT = $wpdb->insert();


		// request plugin options from admin panel including user ID and Auth token
		// $api_details = get_option('twilio-csv');
		// if (is_array($api_details) and count($api_details) != 0) {
		// 	$TWILIO_SID = $api_details['api_sid'];
		// 	$TWILIO_TOKEN = $api_details['api_auth_token'];
		// }

		// // init message contents? comment this and come back to it
		// $to        = (isset($_POST['numbers'])) ? $_POST['numbers'] : '';
		// $sender_id = (isset($_POST['sender']))  ? $_POST['sender']  : '';
		// $message   = (isset($_POST['message'])) ? $_POST['message'] : '';



		// $client = new Client($TWILIO_SID, $TWILIO_TOKEN);
		// $xlsx = SimpleXLSX::parse('');
	}

	public function twilio_csv_public_shortcodes () {

		function print_some_stuff ( $atts ) {
			$atts = shortcode_atts( array(
				'content' => 'blank or not really'
			), $atts, 'print_some_stuff');

			$content = (isset($atts['content'])) ? $atts['content'] : 'but actually blank or something idk';
			return $content;
		}

	}


	// this is now the shortcode function registered in the public class
	// this is the HTML Layout for the form since it doesn't like to be included, although script tags could be used as require/include()
	public function create_csv_upload_form( $atts ) {
		
		$atts = shortcode_atts( array( 
			'pagination' => 10
		), $atts, 'create_csv_upload_form');

		$list_csv_contents = '';

		if (isset($_FILES['csv-upload'])) {
			if ($xlsx = SimpleXLSX::parse($_FILES['csv-upload']['tmp_name'])) {

				$header_values = $json_rows = [];

				// Get Header values, strip that row, then load all rows into a [int][ $header_values => $value ] array 
				foreach ($xlsx->rows() as $k => $r) {
					if ($k === 0) {
						$header_values = $r;
						continue;
					}
					// @todo hardcoding CellPhone for now, $header_values[14] or $header_values['CellPhone']
					// or maybe this?: ignore rows that do not have anything in column 14
					if (!$r[14]) { continue; }

					$json_rows[] = array_combine($header_values, $r);
				}

				$trim_rows = count($json_rows);

				// attempt to add CSV to database
				if (!empty($json_rows) && $_POST['confirm-upload'] == 'confirm') {
					try {
						$json_data = json_encode($json_rows);
						$file_to_wpdb = $this->process_pending_messages($json_data, $trim_rows);
						$list_csv_contents .= ($file_to_wpdb) ? '<div class="alert-success">File sucessfully parsed, trimmed, and added to database.</div>' : '';
					} catch (Exception $e) {
						echo 'Error: ' . $e;
					}
				}
				// print('<pre>');
				// print('Calling process_pending_messages to see var dump: ' . $process_file);
				// // print_r($json_rows);
				// // print_r($json_rows[0]);				
				// // print_r($json_rows[1]);
				// // var_dump($header_values);				
				// print('</pre>');

				
				$dim = $xlsx->dimension();
				$cols = $dim[0];
				$pagination_value = $atts['pagination'];
				$rows = $dim[1] - 1;



				// create associative array of Column Names
				// $sheet_columns = array();
				// for ($i = 0; $i < $cols; $i ++) {
				// 	$sheet_columns .= isset($json_rows[$k][$r]) ? $json_rows[$k][$r] : 'zzz' ) . '</td>';
				// }
				
				$list_csv_contents .= '<h2>Contents of File</h2>';
				$list_csv_contents .= '<p>' . $rows . ' entries in file. Displaying ' . $pagination_value .' per page.</p>';
				$list_csv_contents .= '<p>' . $trim_rows . ' after trimming !CellPhone.</p>';
				$list_csv_contents .= '<table border="1" cellpadding="3" style="border-collapse: collapse">';

				$row_count = 0;
				foreach ($json_rows as $k => $r) {
					if ($row_count > $pagination_value) {
						break;
					}
					// ignore rows that do not have a cell phone value
					if (!$r['CellPhone']) { continue; }
					//      if ($k == 0) continue; // skip first row
					$list_csv_contents .= '<tr>';
					for ($i = 0; $i < $cols; $i ++) {
						$list_csv_contents .= '<td>' . ( isset($json_rows[$k][$header_values[$i]]) ? $json_rows[$k][$header_values[$i]] : 'zzz' ) . '</td>';
					}
					$list_csv_contents .= '</tr>';
					$row_count++;
				}
				$list_csv_contents .= '</table>';
			} else {
				$list_csv_contents .= SimpleXLSX::parseError();
			}
		}

		$upload_form = '    <div class="twilio-csv-form-container">
        <form
        name="twilio-csv-upload-form"
        action=""
        method="post"
        enctype="multipart/form-data"
        >
        <div class="upload-section">
        <label for="csv-upload">Upload Contacts</label>
        <input
          type="file"
          id="csv-upload"
          name="csv-upload"
          accept=".csv,.xls,.xlsx"
        />
        ' . ((!empty($list_csv_contents)) ? '<div class="list-csv-contents">' . $list_csv_contents . '</div>' : '') . 
        '<input type="checkbox" value="confirm" name="confirm-upload" checked>
		<label for="confirm-upload">Add file to database?</label>
		<div class="submit-contacts-to-twilio">
          <input type="submit" value="Submit" name="csv-submit">
        </div>
        </div>

      </form>
    </div>';
	return $upload_form;
	}

	function twilio_csv_register_shortcodes() {
		add_shortcode( 'create_csv_upload_form', array( $this, 'create_csv_upload_form' ) );
	}
	
} //  classTwilio_Csv_Public()
?>