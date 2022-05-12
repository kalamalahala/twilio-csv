<?php // Handle update requests for embedded Gravity View

// Class to handle the update requests

// class Twilio_Csv_Action_Buttons {

//     // Collect passed in AJAX data
//     public function __construct( $data ) {
//         $this->data = $data;
//     }
    

// }

// if (isset($_POST['ajax_handler'])) {
//     echo 'hello';
//     wp_die();
// }

$dispositions = array(
    'e' => 'Email Sent',
    'r' => 'Rejected',
    'c' => 'Call Back',
    'd' => 'Complete',
    'a' => 'Active'
);
$wednesday_or_friday = array(
    'w' => 'Wednesday',
    'f' => 'Friday'
);

$contact_id = (isset($_GET['lead_id'])) ? $_GET['lead_id'] : '';
$new_status = (isset($_GET['lead_action'])) ? $dispositions[$_GET['lead_action']] : null;
$action_type = (!is_null($new_status)) ? $_GET['lead_action'] : '';
$meeting_day = (isset($_GET['meeting_day'])) ? $wednesday_or_friday[$_GET['meeting_day']] : null;
$form_id = 80;

// print 'Page successfully included';
// die;

// Handle AJAX request from Recent Messages Action Buttons
// Scrape POST and return JSON response
// foreach ($_POST as $key => $value) {
//     $payload[] = array(
//         'key' => $key,
//         'value' => $value
//     );
// }
// wp_send_json($payload);
// wp_die($payload);

if (!is_null($contact_id) && !is_null($new_status)) {
    
    try {
        update_gf_entry_with_status($contact_id, $new_status, $action_type, $form_id, $meeting_day);
    } catch (Exception $e) {
        print 'Error updating entry. Stack trace: <pre>';
        var_dump($e);
        print '</pre>';
        die;
    }
}

// function mark_gf_entry_complete(int $entry, string $status) {
//     $form = GFAPI::get_entry($entry);
//     $form['8'] = $status;
//     $result = GFAPI::update_entry($form);
//     return $result;
// }

function update_gf_entry_with_status(int $entry, string $status, string $action, int $form_id, string $meeting_day = null) {
    $form = GFAPI::get_entry($entry);

    switch ($action) {
        case 'e': // Update Status and Mark as Complete
            $form['6'] = $status;
            $form['8'] = 'Complete';
            $form['9'] = (is_null($meeting_day)) ? '' : $meeting_day;
            break;

        case 'r': // Update Status and Mark as Complete
            $form['6'] = $status;
            $form['8'] = 'Complete';
            break;

        case 'd': // just mark as complete
            $form['8'] = 'Complete';
            break;

        case 'a': // mark as Active
            $form['8'] = 'Active';
            break;

        case 'c': // Mark as Call Back and set to Active
            $form['6'] = $status;
            $form['8'] = 'Active';
            break;
    }

    $result = GFAPI::update_entry($form);

    /*
    * Run notifications here if Email Sent
    */

    if ($action == 'e') {
        $form_model = GFAPI::get_form($form_id);
        try {
            GFAPI::send_notifications($form_model, $form);
        } catch (Exception $e) {
            GFCommon::log_error($e);
        }
    }

    return $result;

    // Prior Attempt with submit_form():
    // $input_values = array(
    //     'input_1' => $form['1'], // phone number
    //     'input_5' => $form['5'], // caller id?
    //     'input_7' => $form['7'], // applicant email
    //     'input_4_3' => $form['4.3'], // applicant first name
    //     'input_4_6' => $form['4.6'], // applicant last name
    //     'input_3' => $form['3'], // message body
    //     'input_6' => $form['6'], // disposition
    // );

    // $result = GFAPI::submit_form($form_id, $input_values);
}

// class-twilio-csv-update-handler.php
?>