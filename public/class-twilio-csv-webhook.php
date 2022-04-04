<?php

// creating a webhook to handle POST from twilio

if (!$_POST['body']) die;

$gforms_consumer = "ck_6a4204b5c2e658c7511d1eac3bfc25efb3337922";
$gforms_secret = "cs_056ef416b003f7c6c78d922c687e9351da20c1a9";
$url = "https://thejohnson.group/wp-json/gf/v2/forms/80/entries";
$method = "POST";
$args = array();

$from = $_POST['from'];
$body = $_POST['body'];

$body_content = '{
    "date_created" : '. $date_timestamp .',
    "is_starred"   : 0,
    "is_read"      : 0,
    "ip"           : "::1",
    "source_url"   : "",
    "currency"     : "USD",
    "created_by"   : 1,
    "user_agent"   : "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0",
    "status"       : "active",
    "1"            : '. $from .',
    "3"            : '. $body .'
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
if ( wp_remote_retrieve_response_code( $response ) != 200 || ( empty( wp_remote_retrieve_body( $response ) ) ) ) {
    // If not a 200, HTTP request failed.
    die( 'There was an error attempting to access the API.' );
} else {
    return 'Message sent';
}