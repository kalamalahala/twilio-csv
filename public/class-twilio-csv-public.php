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
require_once(plugin_dir_path(__FILE__) . '/../twilio/Twilio/autoload.php');

use Twilio\Rest\Client;
// use Twilio\TwiML\MessagingResponse;
// use Twilio\TwiML\TwiML;

// json_encode dependency from github
require_once(plugin_dir_path(__FILE__) . '/../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!function_exists('wp_handle_upload')) {
	require_once(ABSPATH . 'wp-admin/includes/file.php');
}

// use Shuchkin\SimpleXLSX as XLSX;
// use Shuchkin\SimpleXLS as XLS;

// if (isset($_FILES['csv-upload'])) {

// 	// check file type and parse with correct class
// 	$file_type = pathinfo($_FILES['csv-upload']['name'], PATHINFO_EXTENSION);

// 	switch ($file_type) {
// 		case 'xls':
// 			$use_type = 'Shuchkin\SimpleXLS';
// 			break;
// 		case 'xlsx':
// 			$use_type = 'Shuchkin\SimpleXLSX';
// 			break;
// 		default:
// 			return 'Unsupported file type.';
// 	}

// }

// use $use_type;

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
class Twilio_Csv_Public
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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/twilio-csv-public.css', array(), null, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/twilio-csv-public.js', array('jquery'), false, false);
	}

	function process_pending_messages($contact_data, $num_entries, $file_data)
	{
		if (!$contact_data) {
			return false;
		}

		$return_html = '<ul>';
		$new_contacts = 0;
		$contact_decoded = json_decode($contact_data);

		global $wpdb;
		$csv_table = $wpdb->prefix . 'twilio_csv_entries';
		$contact_table = $wpdb->prefix . 'twilio_csv_contacts';

		$csv_data = array(
			'id' => '',
			'date' => $file_data['date'],
			'contact_data' => $contact_data,
			'num_entries' => $num_entries,
			'send_count' => 0,
			'success_count' => 0,
			'fail_count' => 0,
			'file_name' => $file_data['file_name']
		);

		$insert_csv = $wpdb->insert($csv_table, $csv_data, null);
		$query_status = ($insert_csv) ? true : false;
		$existing_ids = array();

		try {
			$get_ids = $wpdb->get_results('SELECT * FROM ' . $contact_table);
		} catch (Exception $error) {
			echo $error . '<br>Unable to get results';
		}

		foreach ($get_ids as $entry) {
			array_push($existing_ids, $entry->unique_id);
		}

		foreach ($contact_decoded as $contact) {
			$full_name = $contact->{'First Name'} . $contact->{'Last Name'} . $contact->CellPhone;
			$unique_id = hash('sha256', $full_name);
			$contact_entry = array(
				'id' => '',
				'first_name' => $contact->{'First Name'},
				'last_name' => $contact->{'Last Name'},
				'phone_number' => $contact->CellPhone,
				'email' => $contact->EmailAddress,
				'unique_id' => $unique_id
			);

			if (in_array($unique_id, $existing_ids)) {
				$return_html .= '<li>' . $contact_entry['first_name'] . ' ' . $contact_entry['last_name'] . ' skipped, contact exists.</li>';
			} else {
				try {
					$add_contact = $wpdb->insert($contact_table, $contact_entry, null);
					$return_html .= '<li>' . $contact_entry['first_name'] . ' ' . $contact_entry['last_name'] . ' added to contact list</li>';
					$new_contacts++;
				} catch (Exception $error) {
					echo $error;
				}
			}
		}
		$return_html .= '<li>Processing complete.</li></ul>';


		if ($query_status && $new_contacts > 0) {
			return $return_html;
		} else if ($query_status) {
			return $return_html . '<p>.XLSX File added to database, no contacts created.</p>';
		} else {
			return 'Submission failure.';
		}
		return false;
	}

	public function twilio_csv_public_shortcodes()
	{

		function print_some_stuff($atts)
		{
			$atts = shortcode_atts(array(
				'content' => 'blank or not really'
			), $atts, 'print_some_stuff');

			$content = (isset($atts['content'])) ? $atts['content'] : 'but actually blank or something idk';
			return $content;
		}
	}


	// this is now the shortcode function registered in the public class
	// this is the HTML Layout for the form since it doesn't like to be included, although script tags could be used as require/include()
	public function create_csv_upload_form($atts)
	{
		// init settings
		$atts = shortcode_atts(array(
			'pagination' => 10
		), $atts, 'create_csv_upload_form');
		$list_csv_contents = '';

		// begin parse if file exists
		if (isset($_FILES['csv-upload'])) {

			// Check file extension and abort if not xlsx
			$extension = ucfirst(pathinfo($_FILES['csv-upload']['name'], PATHINFO_EXTENSION));
			if ($extension !== 'Xlsx') {
				return 'File not in .xlsx format.';
			}

			// save uploaded file and return array
			$wp_uploaded_file = wp_handle_upload($_FILES['csv-upload'], array('test_form' => FALSE));

			try {
				$file_type = IOFactory::identify($wp_uploaded_file['file']);
			} catch (Exception $identify_error) {
				echo 'Identify Error: ' . $identify_error;
				die;
			}

			try {
				$reader = IOFactory::createReader($file_type);
			} catch (Exception $read_error) {
				echo 'Reader Error: ' . $read_error;
				die;
			}

			try {
				$parsed_file = $reader->load($wp_uploaded_file['file']);
			} catch (Exception $parse_error) {
				echo 'Parse Error: ' . $parse_error;
				die;
			}

			try {
				$file_info = $reader->listWorksheetInfo($wp_uploaded_file['file']);
			} catch (Exception $parse_error) {
				echo 'File Info failed: ' . $parse_error;
				die;
			}

			if ($parsed_file) {
				$header_values = $json_rows = [];

				$upload_array = $parsed_file->getActiveSheet()->toArray();
				foreach ($upload_array as $row => $cell) {
					if ($row === 0) {
						if ($cell[0] !== 'Office') {
							$list_csv_contents .= 'Unexpected format! "Office" was not found in the very first cell. Was this a RMS file?';
							break;
						}
						$header_values = $cell;
						continue;
					}
					if (!$cell[14]) {
						continue;
					}
					$remove_items = array('-', '(', ')', '+', ' ');
					$cell[14] = str_replace($remove_items, '', $cell[14]);
					if ($cell[14][0] == '1' && strlen($cell[14]) == '10') {
						$cell[14] = substr($cell[14], 1);
					}

					$json_rows[] = array_combine($header_values, $cell);
				}


				// print('<pre>');
				// var_dump($json_rows);
				// print('</pre>');
				// die;

				// // Get Header values, strip that row, then load all rows into a [int][ $header_values => $value ] array 
				// foreach ($parsed_file->rows() as $k => $r) {

				// 	// Check for "Office" in first row and first column, abort if not present
				// 	// otherwise assign header_values
				// 	if ($k === 0) {
				// 		if ($r[0] !== 'Office') {
				// 			return 'Unexpected format!';
				// 		}
				// 		$header_values = $r;
				// 		continue;
				// 	}
				// 	// @todo hardcoding CellPhone for now, $header_values[14] or $header_values['CellPhone']
				// 	// or maybe this?: ignore rows that do not have anything in column 14
				// 	if (!$r[14]) {
				// 		continue;
				// 	}

				// 	//FORMAT CELL PHONE
				// 	$remove_items = array('-', '(', ')', '+', ' ');
				// 	$r[14] = str_replace($remove_items, '', $r[14]);
				// 	if ($r[14][0] == '1' && strlen($r[14]) == '10') {
				// 		$r[14] = substr($r[14], 1);
				// 	}

				// 	$json_rows[] = array_combine($header_values, $r);
				// }

				$trim_rows = count($json_rows);

				$file_data = array();
				$file_data['file_name'] = $_FILES['csv-upload']['tmp_name'];
				$file_data['rows'] = $trim_rows;
				$file_data['date'] = date('g:i:s A m/d/Y', strtotime('now -5 hours'));

				// attempt to add CSV to database
				if (!empty($json_rows) && $_POST['confirm-upload'] == 'confirm') {
					try {
						$json_data = json_encode($json_rows);
						$file_to_wpdb = $this->process_pending_messages($json_data, $trim_rows, $file_data);
						$list_csv_contents .= ($file_to_wpdb) ? '<div class="alert-success">' . $file_to_wpdb . '</div>' : '';
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


				// $dim = $parsed_file->dimension();
				// $pagination_value = $atts['pagination'];
				foreach ($file_info as $worksheet) {
					$cols = $worksheet['totalColumns'];
					$rows = $worksheet['totalRows'] - 1;
				}

				$skipped = $rows - $trim_rows;



				// create associative array of Column Names
				// $sheet_columns = array();
				// for ($i = 0; $i < $cols; $i ++) {
				// 	$sheet_columns .= isset($json_rows[$k][$r]) ? $json_rows[$k][$r] : 'zzz' ) . '</td>';
				// }

				$list_csv_contents .= '<div class="file-contents"><h4>Contents of File</h4>';
				$list_csv_contents .= '<p>' . $rows . ' entries in file according to phpSpreadSheet. ';
				$list_csv_contents .= $trim_rows . ' entries passed to the database after skipping ' . $skipped . ' applicants without cell phones.</p></div>';
				// $list_csv_contents .= '<table border="1" cellpadding="3" style="border-collapse: collapse">';

				// $row_count = 0;
				// foreach ($json_rows as $k => $r) {
				// 	if ($row_count > $pagination_value) {
				// 		break;
				// 	}
				// 	// ignore rows that do not have a cell phone value
				// 	if (!$r['CellPhone']) {
				// 		continue;
				// 	}
				// 	//      if ($k == 0) continue; // skip first row
				// 	$list_csv_contents .= '<tr>';
				// 	for ($i = 0; $i < $cols; $i++) {
				// 		$list_csv_contents .= '<td>' . (isset($json_rows[$k][$header_values[$i]]) ? $json_rows[$k][$header_values[$i]] : 'zzz') . '</td>';
				// 	}
				// 	$list_csv_contents .= '</tr>';
				// 	$row_count++;
				// }
				// $list_csv_contents .= '</table>';
			} else {
				$list_csv_contents .= 'Parse error.';
			}
		}

		$upload_form = '    <div class="twilio-csv-form-container">
        <form
        name="twilio-csv-upload-form"
        id="twilio-csv-upload-form"
        action=""
        method="post"
        enctype="multipart/form-data"
        >
        <div class="upload-section">
        <label for="csv-upload">Upload Contacts (.xlsx)
        <input
          type="file"
          id="csv-upload"
          name="csv-upload"
          accept=".xlsx"
		  class="upload-file"
        /></label><p id="file-name" class="file-name"></p></div>
        ' . ((!empty($list_csv_contents)) ? '<div class="list-csv-contents">' . $list_csv_contents . '</div>' : '') .
			'<div class="confirm-upload"><label for="confirm-upload"><input type="checkbox" value="confirm" name="confirm-upload" checked>
		Add file to database?</label></div>
		<div class="submit-contacts-to-twilio">
          <input type="submit" value="Submit" name="csv-submit">
        </div>

      </form>
    </div>';

		return $upload_form;
	}

	public function select_uploaded_csv_files($atts)
	{
		// require_once(__DIR__ . '/js/twilio-csv-extra.js');
		// sets atts and initial array of options to ten and zero
		$atts = shortcode_atts(array(
			'pagination' => 10
		), $atts, 'select_uploaded_csv_files');
		$option_group = '';

		// go get entries from database
		global $wpdb;
		$csv_table = $wpdb->prefix . 'twilio_csv_entries';
		$table_contents = $wpdb->get_results('SELECT * FROM ' . $csv_table . ' ORDER BY id DESC;');

		// loop table_contents into option group
		$entry_array = array();
		foreach ($table_contents as $entry) {
			array_push($entry_array, json_decode($entry->contact_data));
			$option_group .= '<option value="' . $entry->id . '">' . $entry->date . ' - ' . $entry->num_entries . ' Entries</option>';
		}


		// print('<pre>');
		// print_r($entry_array);
		// print('</pre>');

		$embedded_page = get_page_link();

		// form HTML with looped option group
		$selector_form = '<div class="twilio-csv-viewer">
									<form
									name="twilio-csv-viewer"
									id="twilio-csv-viewer"
									action="' . $embedded_page . '?mode=send"
									method="post"
									enctype="application/x-www-form-urlencoded"
									onsubmit="return confirm(\'Do you really want to submit the form?\');"
									>
										<div class="view-section">
											<label for="csv-select">Select Uploaded CSV
											<select type="select" id="csv-select" name="csv-select">
											' . $option_group . '
											</select>
											</label>
											</div>

										<div class="submit-contacts-to-twilio">
											
											<div class="twilio-body">
											<label for="body">Message Body
												<select name="body" id="message-body">
												<option value="message-1">Hey FIRSTNAME, we saw your resume...</option> 
												</select>
											</label>
											</div>
											
											<div class="confirm-twilio">
											<label for="confirm-twilio">
											<input type="checkbox" value="confirm" name="confirm-twilio" required /> Confirm selected message?
											</label>
												<input type="submit" value="Submit" name="csv-submit" class="fusion-button button-3d button-medium button-default button-2" />
											</div>
											
										</div>
											
										</form>
										<div class="api-information"></div>
									</div>';
		// <textarea width="400" height="120" name="body" maxlength="155" placeholder="Maximum character length: 155" required /></textarea>
		return $selector_form;
	}

	public function twilio_csv_show_results()
	{
		// Exit unless the stars are aligned
		if (!$_POST['csv-submit']) return 'Form was not submitted.';
		if ($_POST['confirm-twilio'] !== 'confirm') return 'Confirmation box wasn\'t checked.';
		if (!$_POST['body']) return 'No message to send!';

		// Start tracking execution time
		$start_time = microtime(true);

		// Go get relevant JSON data and decode for PHP
		global $wpdb;
		$csv_table = $wpdb->prefix . 'twilio_csv_entries';
		$results = $wpdb->get_results('SELECT contact_data FROM ' . $csv_table . ' WHERE id=' . $_POST['csv-select'] . ';');
		foreach ($results as $entry) {
			// Everyone on the uploaded xlsx file
			$contact_array = json_decode($entry->contact_data);
		}

		// Go get API Keys and open a new Client
		$api_details = get_option('twilio-csv');
		if (is_array($api_details) and count($api_details) != 0) {
			$TWILIO_SID = $api_details['api_sid'];
			$TWILIO_TOKEN = $api_details['api_auth_token'];
		}
		$client = new Client($TWILIO_SID, $TWILIO_TOKEN);

		$message_result_list = '<ul>';
		$message_count = 0;

		// List of programmed messages with replacement variables.
		$messages = array();
		$messages['message-1'] = 'Hey FIRSTNAME, my name is Ariel with The Johnson Group. We saw your resume online. Are you still looking for a career opportunity?';
		$selected_message = $messages[$_POST['body']];

		// Process list of contacts with selected message
		foreach ($contact_array as $contact) {
			$recipient = $contact->CellPhone;
			// var_dump($contact);
			$TWILIO_MESSAGE_BODY = str_replace('FIRSTNAME', $contact->{'First Name'}, $selected_message);

			try {
				$send_message = $client->messages->create(
					$recipient,
					[
						'body' => $TWILIO_MESSAGE_BODY,
						'from' => 'MGed693e77e70d6f52882605d37cc30d4c'
					]
				);
				if ($send_message) $message_result_list .= '<li>Message sent to <a href="tel:' . $recipient . '" title="Call ' . $recipient . '">' . $recipient . '</a></li>';
				$message_count++; // total messages sent
			} catch (\Throwable $throwable) {
				return GFCommon::log_error($throwable);
			}
		}

		// Get total execution time in milliseconds.
		$total_time = round((microtime(true) - $start_time)*1000);

		return '<div class="results">Run time: ' . $total_time . ' milliseconds. Messages processed: ' . $message_count . '. Results below: ' . $message_result_list . '</ul></div>';
	}
	/**
	 * Single Message form sender and POST handler. 
	 *
	 * @return HTML
	 */
	function send_single_message()
	{
		// status init
		$message_sent = false;

		// check for form submission
		if ($_POST['single-submit']) {

			// get plugin options and loop through them
			$api_details = get_option('twilio-csv');
			if (is_array($api_details) and count($api_details) != 0) {
				$TWILIO_SID = $api_details['api_sid'];
				$TWILIO_TOKEN = $api_details['api_auth_token'];
			}

			// create message request with authorization
			$client = new Client($TWILIO_SID, $TWILIO_TOKEN);
			$TWILIO_MESSAGE_BODY = $_POST['message-body'];

			try {
				$send_message = $client->messages->create(
					$_POST['single-to'],
					[
						'body' => $TWILIO_MESSAGE_BODY,
						'from' => 'MGed693e77e70d6f52882605d37cc30d4c'
					]
				);
				// set status to success
				if ($send_message) $message_sent = true;
			} catch (\Throwable $throwable) {
				$single_result = $throwable->getMessage();
				return $throwable->getMessage();
			}
			// add Results Box text
			$single_result = 'Message sent to ' . $_POST['single-to'] . '.';
		}

		// HTML
		$results_box = '<div class="results_container">' . $single_result . '</div>';
		$form = '    <div class="send_single_form_container">
		<form action="" name="send-single-sms" method="post" id="send-single-sms" enctype="application/x-www-form-urlencoded">
		  <div class="select-recipient">
			<label for="single-to">Enter Phone Number <span class="required">*</span></label>
			<input type="tel" id="single-to" name="single-to" pattern="+1 [0-9]{3} [0-9]{3} [0-9]{4}" maxlength="12" required
			placeholder="+1 386 868 9059" />
		  </div><div class="message-body">
			<label for="message-body">Message Body <span class="required">*</span></label>
			<textarea id="message-body" name="message-body" rows="7" cols="40" placeholder="Message body here ..."
			  required></textarea>
		  </div><div class="submit-area">
			<label for="single-submit">Submit SMS Message</label>
			<input type="submit" value="Submit" name="single-submit" id="single-submit" />
		  </div>
		</form>
	  </div>';

		// Always display the form, optionally include results box if message was sent
		if ($message_sent) {
			return $results_box . $form;
		} else {
			return $form;
		}
	}

	function handle_incoming_message_from_twilio()
	{


		// creating a webhook to handle POST from twilio

		if (!$_POST['body']) return;

		$gforms_consumer = "ck_6a4204b5c2e658c7511d1eac3bfc25efb3337922";
		$gforms_secret = "cs_056ef416b003f7c6c78d922c687e9351da20c1a9";
		$url = "https://thejohnson.group/wp-json/gf/v2/forms/80/entries";
		$method = "POST";
		$args = array();

		$from = $_POST['from'];
		$body = $_POST['body'];
		$date_timestamp = new DateTime();

		$body_content = '{
			"date_created" : ' . $date_timestamp . ',
			"is_starred"   : 0,
			"is_read"      : 0,
			"ip"           : "::1",
			"source_url"   : "",
			"currency"     : "USD",
			"created_by"   : 1,
			"user_agent"   : "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0",
			"status"       : "active",
			"1"            : ' . $from . ',
			"3"            : ' . $body . '
			}';

		require_once('class-oauth-request.php');
		$oauth = new OAuth_Request($url, $gforms_consumer, $gforms_secret, $method, $args);

		$response = wp_remote_request(
			$oauth->get_url(),
			array(
				'method' => $method,
				'body' => $body_content,
				'headers' => array('Content-Type' => 'application/json')
			)
		);

		// Check the response code.
		if (wp_remote_retrieve_response_code($response) != 200 || (empty(wp_remote_retrieve_body($response)))) {
			// If not a 200, HTTP request failed.
			die('There was an error attempting to access the API.');
		} else {
			return 'Message sent';
		}
	}


	// begin webhook
	function register_twilio_csv_route()
	{
		register_rest_route('twilio_csv/v1', '/receive_sms', array(
			'methods' => 'POST',
			'callback' => array($this, 'trigger_receive_sms')
		));
	}
	

	function trigger_receive_sms()
	{
		// Twilio Key List:
		// ToCountry, ToState, SmsMessageSid, NumMedia, ToCity, FromZip, SmsSid, FromState, SmsStatus, FromCity
		// Body, To, From
		// FromCountry, MessagingServiceSid, ToZip, NumSegments, ReferralNumMedia, MessageSid, AccountSid, ApiVersion

		if (!isset($_POST)) die;
		
		$message_array = explode(' ', $_POST['body']);
		if (!in_array('yes', $message_array)) die;

		/*
		* Add message to front end for further work
		*/
		
		$form_entry = array();
		$name = array();
		$response_text = '';

		$api_details = get_option('twilio-csv');
		if (is_array($api_details) and count($api_details) != 0) {
			$TWILIO_SID = $api_details['api_sid'];
			$TWILIO_TOKEN = $api_details['api_auth_token'];
		}
		$twilio = new Client($TWILIO_SID, $TWILIO_TOKEN);
		$phone_number = $twilio->lookups->v1->phoneNumbers($_POST['From'])->fetch(['type' => ['caller-name']]);
		$caller_id = $phone_number->callerName;

		$trimmed_number = substr($_POST['From'], 2);

		global $wpdb;
		$table = $wpdb->prefix . 'twilio_csv_contacts';

		try {
			$number_lookup = $wpdb->get_results('SELECT * FROM ' . $table);
			if ($number_lookup) {
				foreach ($number_lookup as $sender) {
					if ($sender->phone_number == $trimmed_number) {
						$first_name = $sender->first_name;
						$last_name = $sender->last_name;
						$phone_number = $sender->phone_number;
						$email = $sender->email;
					}
				}
			} else {
				$response_text .= 'Number Lookup was empty.';
			}
		} catch (Exception $error) {
			$response_text .= 'Number Lookup failed: ' . $error;
		}

		$form_entry['id'] = '';
		$form_entry['form_id'] = '80';
		$form_entry['created_by'] = '';
		$form_entry['date_created'] = '';
		$form_entry['is_starred'] = 'false';
		$form_entry['is_read'] = 'false';
		$form_entry['ip'] = '';
		$form_entry['source_url'] = '';
		$form_entry['post_id'] = '15948';
		$form_entry['status'] = 'active';
		$form_entry['1'] = $_POST['From'];
		$form_entry['3'] = $_POST['Body'];
		$form_entry['4.3'] = $first_name;
		$form_entry['4.6'] = $last_name;
		$form_entry['5'] = (!empty($caller_id)) ? $caller_id : 'Caller ID Unavailable';
		$form_entry['6'] = 'Replied Yes';
		$form_entry['7'] = $email;
		$form_entry['8'] = 'Active';

		try {
			$submission = GFAPI::add_entry($form_entry);
			if ($submission) {
				$response_text .= 'Okay ' . $first_name . ', I will reach back out via a phone call soon.';
			}
		} catch (Exception $error) {
			$response_text .= $error;
		}

		echo header('content-type: text/xml');

		echo <<<RESPOND
		<?xml version="1.0" encoding="UTF-8"?>
		<Response>
		  <Message>$response_text</Message>
		</Response>
		RESPOND;
		die();
	}

	// end webhook

	function twilio_csv_gravity_view_update_handler() {
		if (!isset($_GET['lead_id'])) { return; }
		require_once(plugin_dir_path(__FILE__) . '/partials/class-twilio-csv-update-handler.php');
	}

	function twilio_csv_register_gravity_view_update_handler() {
		add_shortcode('update_handler', array($this, 'twilio_csv_gravity_view_update_handler'));
	}

	function twilio_csv_register_shortcodes_handle()
	{
		add_shortcode('msg_handler', array($this, 'handle_incoming_message_from_twilio'));
	}

	function twilio_csv_register_shortcodes_send_single()
	{
		add_shortcode('send_single_message', array($this, 'send_single_message'));
	}

	function twilio_csv_register_shortcodes_create()
	{
		add_shortcode('create_csv_upload_form', array($this, 'create_csv_upload_form'));
	}


	function twilio_csv_register_shortcodes_select()
	{
		add_shortcode('select_uploaded_csv_files', array($this, 'select_uploaded_csv_files'));
	}


	function twilio_csv_register_shortcodes_send()
	{
		add_shortcode('twilio_csv_show_results', array($this, 'twilio_csv_show_results'));
	}


	/**
	 * This hooks into the recruiting page and deposits some javascript, but doesn't seem to function yet.
	 *
	 * @return javascript supposed to update the file-name element
	 */
	function twilio_csv_add_javascript() {
		if (is_page('15948')) {
		?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					const file_uploader = document.getElementById("csv-upload");
					const file_text = document.getElementById("file-name");
	
					/* file.onclick = alert("Yeah the script is working"); */
						
					function update_file_size(e) {
						const [file] = e.target.files;
						// Get the file name and size
						const { name: fileName, size } = file;
						// Convert size in bytes to kilo bytes
						const fileSize = (size / 1000).toFixed(2);
						// Set the text content
						const fileNameAndSize = `${fileName} - ${fileSize} KB and a bunch of other stuff`;
						file_text.textContent = fileNameAndSize;
						
					}

					jQuery("#csv-upload").on("change", update_file_size(this));
					jQuery("#twilio-csv-upload-form").on("click", alert("test alert"));

				});
			</script>
			<?php
		}
	}
} //  class Twilio_Csv_Public()
