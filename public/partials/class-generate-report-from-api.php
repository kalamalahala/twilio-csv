<?php

/**
 * Class TwilioCsvReports
 * 
 * Display statistics from the Twilio API
 * */

class TwilioCsvReports
{

    private $api_key;
    private $api_token;
    private $api_url_base;
    private $twilio;
    private $twilio_phone_number;
    // Collect API Key and Security Token from WP Options
    public function __construct()
    {
        $this->api_key = get_option('twilio_api_key') ? get_option('twilio_api_key') : TWILIO_ACCOUNT_SID;
        $this->api_token = get_option('twilio_api_token') ? get_option('twilio_api_token') : TWILIO_AUTH_TOKEN;
        $this->api_url_base = 'https://api.twilio.com/2010-04-01/';
        $this->twilio = new Twilio\Rest\Client($this->api_key, $this->api_token);
        $this->twilio_phone_number = get_option('twilio_phone_number') ? get_option('twilio_phone_number') : TWILIO_PRIMARY_NUMBER;
    }

    public function get_api_key()
    {
        return $this->api_key;
    }

    public function get_api_token()
    {
        return $this->api_token;
    }

    public function get_api_url_base()
    {
        return $this->api_url_base;
    }

    public function get_api_url(string $url = '')
    {
        return $this->api_url_base . $url;
    }

    public function get_reports($messaging_sid) 
    {
        $reports = $this->twilio->messages($messaging_sid)->read();
        return $reports;
    }
}
