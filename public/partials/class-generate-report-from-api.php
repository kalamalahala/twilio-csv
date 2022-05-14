<?php

/**
 * Class TwilioCsvReports
 * 
 * Display statistics from the Twilio API
 * */
define( 'TWILIO_ACCOUNT_SID', 'ACd4b4efa2054f2aaf8c06ab0693f3f65b');
define( 'TWILIO_PRIMARY_NUMBER', '+13868886995');
define( 'TWILIO_MESSAGING_SID', 'MGed693e77e70d6f52882605d37cc30d4c');

class TwilioCsvReports
{

    private $api_key;
    private $api_token;
    private $api_url_base;
    private $twilio;
    private $twilio_phone_number;
    private $twilio_messaging_sid;
   
    public function __construct()
    {
        $this->api_key = 'ACd4b4efa2054f2aaf8c06ab0693f3f65b';
        $this->api_token = get_option('twilio_api_token'); 
        $this->twilio_phone_number =  '+13868886995';
        $this->twilio_messaging_sid = 'MGed693e77e70d6f52882605d37cc30d4c';
        $this->api_url_base = 'https://api.twilio.com/2010-04-01/';
        $this->twilio = new Twilio\Rest\Client($this->api_key, $this->api_token);
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
    
    public function get_outbound() 
    {
        $outbound = $this->twilio->messages->read(
            [
                'From' => $this->twilio_phone_number
            ],
            null,
            null
        );

        
        return $outbound;
    }

    public function get_inbound()
    {
        $inbound = $this->twilio->messages->read(
            [
                'To' => $this->twilio_phone_number
            ],
            null,
            null
        );

        return $inbound;
    }
}
