<?php // Handle update requests for embedded Gravity View

$dispositions = array(
    'e' => 'Email Sent',
    'r' => 'Rejected',
    'c' => 'Call Back',
    'd' => 'Complete',
    'a' => 'Active'
);
$contact_id = (isset($_GET['lead_id'])) ? $_GET['lead_id'] : null;
$new_status = (isset($_GET['lead_action'])) ? $dispositions[$_GET['lead_action']] : null;
$action_type = (!is_null($new_status)) ? $_GET['lead_action'] : null;
$form_id = 80;

if (!is_null($contact_id) && !is_null($new_status)) {
    
    try {
        update_gf_entry_with_status($contact_id, $new_status, $action_type, $form_id);
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

function update_gf_entry_with_status(int $entry, string $status, string $action, int $form_id) {
    $form = GFAPI::get_entry($entry);

    switch ($action) {
        case 'e': // Update Status and Mark as Complete
            $form['6'] = $status;
            $form['8'] = 'Complete';
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
        $form_model = GFAPI::get_form('80');
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