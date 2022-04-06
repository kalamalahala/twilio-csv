<?php // Handle update requests for Gravity View

$dispositions = array(
    'e' => 'Email Sent',
    'r' => 'Rejected',
    'c' => 'Call Back'
);
$contact_id = (isset($_GET['lead_id'])) ? $_GET['lead_id'] : null;
$new_status = (isset($_GET['lead_action'])) ? $dispositions[$_GET['lead_action']] : null;

if (!is_null($contact_id) && !is_null($new_status)) {
    try {
        update_gf_entry_with_status($contact_id, $new_status);
    } catch (Exception $e) {
        print 'Error updating entry. Stack trace: <pre>';
        var_dump($e);
        print '</pre>';
        die;
    }
}

function update_gf_entry_with_status(int $entry, string $action) {
    // disposition
}

// class-twilio-csv-update-handler.php
?>