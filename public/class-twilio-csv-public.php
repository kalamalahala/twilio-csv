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

/**
 *  Create Logging functionality snippet
 */
if (!function_exists('write_log')) {

	function write_log($log)
	{
		if (true === WP_DEBUG) {
			if (is_array($log) || is_object($log)) {
				error_log(print_r($log, true));
			} else {
				error_log($log);
			}
		}
	}
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

	// Return clean formatted cell phone number
	function strip_phone_extras($strip_number)
	{
		$remove_items = array('-', '(', ')', '+', ' ');
		$stripped_number = str_replace($remove_items, '', $strip_number);
		return $stripped_number;
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

		// Display contents of uploaded file to be processed

		if (!is_null($contact_decoded[0]->{'First Name'})) {
			$uploaded_file_type = 'RMS';
		} else if (!is_null($contact_decoded[0]->Name)) {
			$uploaded_file_type = 'RMS2';
		} else {
			print 'Error';
			die;
		}




		foreach ($contact_decoded as $contact) {
			// Handle First name / Last Name formatting
			if ($uploaded_file_type == 'RMS') {
				if (is_null($contact->CellPhone)) {
					write_log('No Cell Phone Number in array, skipping creating contact');
					$return_html .= '<li>No Cell Phone Number in array, skipping creating contact</li>';
					continue;
				}
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
			} else if ($uploaded_file_type == 'RMS2') {
				if (is_null($contact->Telephone)) {
					write_log('No Cell Phone Number in array, skipping creating contact');
					$return_html .= '<li>No Cell Phone Number in array, skipping creating contact</li>';
					continue;
				}
				$name_salt = $contact->Name . $contact->Telephone;
				$unique_id = hash('sha256', $name_salt);
				$first_and_last = explode(' ', $contact->Name);
				$first_name = $first_and_last[0];

				// collect remaining names into $surnames
				$surnames = '';
				foreach ($first_and_last as $key => $value) {
					if ($key > 0) {
						$surnames .= $value . ' ';
					}
				}

				$contact_entry = array(
					'id' => '',
					'first_name' => $first_name,
					'last_name' => $surnames,
					'phone_number' => $contact->Telephone,
					'email' => $contact->Email,
					'unique_id' => $unique_id
				);
			} else {
				write_log('Data format not recognized, contact creation skipped');
				$return_html .= '<li>Data format not recognized, contact creation skipped</li>';
				continue;
			}

			if (in_array($unique_id, $existing_ids)) {
				$return_html .= '<li>' . $contact_entry['first_name'] . ' ' . $contact_entry['last_name'] . ' skipped creating <em>Caller ID</em> , contact exists.</li>';
			} else {
				try {
					$add_contact = $wpdb->insert($contact_table, $contact_entry, null);
					$return_html .= '<li>' . $contact_entry['first_name'] . ' ' . $contact_entry['last_name'] . ' added to contact list</li>';
					$new_contacts++;
				} catch (Exception $error) {
					write_log($error . '<br>Unable to add contact to database');
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
		$allowable_headers = array('Office', 'Record Type');

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
				$file_type = IOFactory::identify($wp_uploaded_file['file']) ?? '';
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
				$phone_numbers_listed = array();
				$parsed_rows = 0;
				$skipped_rows = 0;

				$upload_array = $parsed_file->getActiveSheet()->toArray();
				$first_cell = $upload_array[0][0];

				if (!in_array($first_cell, $allowable_headers)) {
					return '<div class="alert alert-danger">Unrecognized headers. Contact admin.</div>';
				}

				if ($first_cell == 'Office') {
					foreach ($upload_array as $row => $cell) {
						if ($row === 0) {
							$header_values = $cell;
							continue;
						}

						// Hard Code RMS XLSX Files for now till new form is created

						for ($i = 13; $i >= 16; $i++) {
							if (!empty($cell[$i])) {
								strip_phone_extras($cell[$i]);
							}
							if ($cell[$i][0] == '1' && strlen($cell[$i]) == '10') {
								$cell[$i] = substr($cell[$i], 1);
							}
						}

						if (empty($cell[14])) {
							$cell[14] = $cell[16] ?? $cell[13] ?? $cell[15] ?? '';
						}

						$listed_cell_phone_number = $cell[16] ?? $cell[14] ?? $cell[13] ?? $cell[15] ?? '';

						if (!in_array($listed_cell_phone_number, $phone_numbers_listed)) {
							array_push($phone_numbers_listed, $listed_cell_phone_number);
							$json_rows[] = array_combine($header_values, $cell);
							$parsed_rows++;
						} else {
							// Cell phone already exists in temporary array, skip this one
							$skipped_rows++;
							continue;
						}
					}
				} else if ($first_cell == 'Record Type') {
					foreach ($upload_array as $row => $cell) {
						if ($row === 0) {
							$header_values = $cell;
							continue;
						}

						$listed_cell_phone_number = $cell[5] ?? '';
						// Regex to remove all special characters from cell[1]
						// $cell[1] = preg_replace('/(?:[^a-z0-9 ]|(?<=[\'\"])s)/', '', $cell[1]);

						// Remove all special characters from Name field
						$cell[1] = preg_replace('/[^A-Za-z0-9\-\s]/', ' ', $cell[1]);
						$cell[1] = str_replace(array('  ', '   '), ' ', $cell[1]);

						if (!in_array($listed_cell_phone_number, $phone_numbers_listed) && !empty($listed_cell_phone_number)) {
							array_push($phone_numbers_listed, $listed_cell_phone_number);
							$json_rows[] = array_combine($header_values, $cell);
							$parsed_rows++;
						} else {
							// Cell phone already exists in temporary array, skip this one
							$skipped_rows++;
							continue;
						}
					}
				} else {
					$list_csv_contents .= '<div class="alert alert-danger">Unrecognized headers. Contact admin.</div>';
				}


				$trim_rows = count($json_rows);

				$file_data = array();
				$file_data['file_name'] = $_FILES['csv-upload']['tmp_name'];
				$file_data['rows'] = $trim_rows;
				$file_data['date'] = date('g:i:s A m/d/Y', strtotime('now -4 hours'));

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

				foreach ($file_info as $worksheet) {
					$cols = $worksheet['totalColumns'];
					$rows = $worksheet['totalRows'] - 1;
				}

				$list_csv_contents .= '<div class="file-contents"><h4>Contents of File</h4>';
				$list_csv_contents .= '<p>' . $rows . ' rows in the uploaded .xlsx file. ';
				$list_csv_contents .= 'Campaign created with ' . $trim_rows . ' contacts.</p></div>';
				$list_csv_contents .= ($skipped_rows > 0) ? '<div class="alert-warning">' . $skipped_rows . ' entries had matching phone numbers and were not included in the upload.</div>' : '';
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
          <input type="submit" value="Upload Contact List" name="csv-submit"><p class="description-text">This action will create a new Campaign. No text messages will be sent yet.</p>
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
												<option value="message-2">Hi FIRSTNAME, I\'m with Globe Life...</option>
												</select>
											</label>
											</div>
											
											<div class="confirm-twilio">
											<label for="confirm-twilio">
											<input type="checkbox" value="confirm" name="confirm-twilio" required /> Confirm selected message?
											</label>
											<label for="csv-submit">Begin New Text Campaign<div class="csv-submit-button">	
											<input type="submit" value="Submit Bulk SMS to Contact List" name="csv-submit" class="fusion-button button-3d button-medium button-default button-2" /></div></label>
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
		if (!is_null($contact_array[0]->{'First Name'})) {
			$uploaded_file_type = 'RMS';
		} else if (!is_null($contact_array[0]->Name)) {
			$uploaded_file_type = 'RMS2';
		} else {
			print 'Error';
			die;
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
		$contact_count = 0;

		// List of programmed messages with replacement variables.
		$messages = array();
		$contacted_numbers = array();
		$messages['message-1'] = 'Hey FIRSTNAME, my name is Amila with The Johnson Group. We saw your resume online. Are you still looking for a career opportunity?';
		$messages['message-2'] = 'Hi FIRSTNAME, Im Amila with Globe Life - Liberty Division. We received your request for employment consideration. Are you still looking for a career?';
		$selected_message = $messages[$_POST['body']];

		// Process list of contacts with selected message
		foreach ($contact_array as $contact) {
			
			$recipient = $contact->CellPhone ?? $contact->Telephone;
			if ($uploaded_file_type == 'RMS') $first_name = $contact->{'First Name'};
			else if ($uploaded_file_type == 'RMS2') $first_name = explode(' ', $contact->Name)[0];
			if (!$recipient) {
				$message_result_list .= '<li>Error: No Cell Phone or Telephone Number</li>';
				continue;
			}
			
			$TWILIO_MESSAGE_BODY = str_replace('FIRSTNAME', $first_name, $selected_message);
			// Add each message phone number to array of contacted numbers to prevent duplicates on same CSV file.
			if (!in_array($recipient, $contacted_numbers)) {
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
				} catch (\Exception $throwable) {
					GFCommon::log_error($throwable);
					write_log('Error sending message to ' . $recipient . '. Details: ' . $throwable);
				}
			}
			array_push($contacted_numbers, $recipient);
			$contact_count++; // total contacts processed
		}

		// Get total execution time in milliseconds.
		$total_time = round((microtime(true) - $start_time) * 1000);

		return '<div class="results">Run time: ' . $total_time . ' milliseconds. Messages processed: ' . $message_count . ' to ' . $contact_count . ' contacts. ' . 'Results below: ' . $message_result_list . '</ul></div>';
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

		// if (!$_POST['body']) return;

		// $gforms_consumer = "ck_6a4204b5c2e658c7511d1eac3bfc25efb3337922";
		// $gforms_secret = "cs_056ef416b003f7c6c78d922c687e9351da20c1a9";
		// $url = "https://thejohnson.group/wp-json/gf/v2/forms/80/entries";
		// $method = "POST";
		// $args = array();

		// $from = $_POST['from'];
		// $body = $_POST['body'];
		// $date_timestamp = new DateTime();

		// $body_content = '{
		// 	"date_created" : ' . $date_timestamp . ',
		// 	"is_starred"   : 0,
		// 	"is_read"      : 0,
		// 	"ip"           : "::1",
		// 	"source_url"   : "",
		// 	"currency"     : "USD",
		// 	"created_by"   : 1,
		// 	"user_agent"   : "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0",
		// 	"status"       : "active",
		// 	"1"            : ' . $from . ',
		// 	"3"            : ' . $body . '
		// }';

		// require_once('class-oauth-request.php');
		// $oauth = new OAuth_Request($url, $gforms_consumer, $gforms_secret, $method, $args);

		// $response = wp_remote_request(
		// 	$oauth->get_url(),
		// 	array(
		// 		'method' => $method,
		// 		'body' => $body_content,
		// 		'headers' => array('Content-Type' => 'application/json')
		// 		)
		// 	);

		// 	// Check the response code.
		// 	if (wp_remote_retrieve_response_code($response) != 200 || (empty(wp_remote_retrieve_body($response)))) {
		// 		// If not a 200, HTTP request failed.
		// 		die('There was an error attempting to access the API.');
		// 	} else {
		// 		return 'Message sent';
		// 	}
	}


	// begin webhook
	function register_twilio_csv_route()
	{
		register_rest_route('twilio_csv/v1', '/receive_sms', array(
			'methods' => 'POST',
			'callback' => array($this, 'trigger_receive_sms')
		));
		register_rest_route('twilio_csv/v1', '/action_button', array(
			'methods' => 'POST',
			'callback' => array($this, 'trigger_action_button')
		));
	}

	// // create rest hook for action button handler
	// function register_twilio_action_route()
	// {
	// }

	function trigger_action_button()
	{
		if (!isset($_POST)) return;
		echo 'action button triggered';
		wp_die();
	}

	function trigger_receive_sms()
	{
		// Escape if no POST data to webhook
		if (!isset($_POST)) return;

		// Twilio Key List:
		// ToCountry, ToState, SmsMessageSid, NumMedia, ToCity, FromZip, SmsSid, FromState, SmsStatus, FromCity
		// Body, To, From
		// FromCountry, MessagingServiceSid, ToZip, NumSegments, ReferralNumMedia, MessageSid, AccountSid, ApiVersion

		// $message_array = explode(' ', $_POST['body']);
		// if (!in_array('yes', $message_array)) die;

		/*
		* Add message to front end for further work
		*/

		$form_entry = array();
		$name = array();
		$response_text = ''; // response text to twilio


		$trimmed_number = substr($_POST['From'], 2);

		global $wpdb;
		$table = $wpdb->prefix . 'twilio_csv_contacts';
		$phone_number = '';

		try {
			$number_lookup = $wpdb->get_results('SELECT * FROM ' . $table);
			if (!empty($number_lookup)) {
				foreach ($number_lookup as $sender) {
					if ($sender->phone_number == $trimmed_number) {
						$first_name = $sender->first_name;
						$last_name = $sender->last_name;
						$phone_number = $sender->phone_number;
						$email = $sender->email;
					}
				}
			} else {
				$response_text .= 'Number Lookup was empty. Attempted to look up: ' . $trimmed_number . ' against ' . $phone_number . ' in the database.';
			}
		} catch (Exception $error) {
			write_log('Number Lookup failed: ' . $error);
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
		$form_entry['1'] = $_POST['From'] ?? 'POST EMPTY';
		$form_entry['3'] = $_POST['Body'] ?? 'BODY EMPTY';
		$form_entry['4.3'] = $first_name;
		$form_entry['4.6'] = $last_name;
		$form_entry['5'] = (!empty($caller_id)) ? $caller_id : 'Caller ID Unavailable';
		$form_entry['6'] = $_POST['Disposition'] ?? 'DISPOSITION EMPTY';
		$form_entry['7'] = $email ?? 'EMAIL EMPTY';
		$form_entry['8'] = $_POST['Status'] ?? 'STATUS EMPTY';

		try {
			$submission = GFAPI::add_entry($form_entry);
			// if ($submission) {
			// 	$response_text .= 'trigger webhook';
			// }
		} catch (Exception $error) {
			// $response_text .= $error;
			write_log('Create Message Error');
			write_log($error);
		}

		// Message Response Removed

		echo header('content-type: text/xml');
		die;

		// $studioFlowMessage = array_keys($_POST);
		// foreach ($studioFlowMessage as $key => $value) {
		// 	$response_text .= $value . ': ' . $_POST[$value] . '<br>';
		// }


		/*
		* Old bits and pieces
		*/

		// echo <<<RESPOND
		// <?xml version="1.0" encoding="UTF-8" ? >
		// <Response>
		//   <Message>Ahoy from WordPress</Message>
		// </Response>
		// RESPOND;

		// $api_details = get_option('twilio-csv');
		// if (is_array($api_details) and count($api_details) != 0) {
		// 	$TWILIO_SID = $api_details['api_sid'];
		// 	$TWILIO_TOKEN = $api_details['api_auth_token'];
		// }
		// $twilio = new Client($TWILIO_SID, $TWILIO_TOKEN);
		// $phone_number = $twilio->lookups->v1->phoneNumbers($_POST['From'])->fetch(['type' => ['caller-name']]);
		// $caller_id = $phone_number->callerName;
	}

	// end webhook

	function twilio_csv_gravity_view_update_handler()
	{
		if (!isset($_GET['lead_id'])) {
			return;
		}
		// Buffer include
		ob_start();
		$content = require_once(plugin_dir_path(__FILE__) . '/partials/class-twilio-csv-update-handler.php');
		ob_end_clean();
		echo $content;
		return;

	}

	function twilio_csv_display_upload_form()
	{
		require_once(plugin_dir_path(__FILE__) . '/partials/class-twilio-csv-upload-form.php');
		return;
	}

	function twilio_csv_register_display_upload_form()
	{
		add_shortcode('twilio_csv_display_upload_form', array($this, 'twilio_csv_display_upload_form'));
	}

	function twilio_csv_register_gravity_view_update_handler()
	{
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
	function twilio_csv_add_javascript()
	{
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
						const {
							name: fileName,
							size
						} = file;
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
