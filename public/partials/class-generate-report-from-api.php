<?php

/**
 * Class TwilioCsvReports
 * 
 * Display statistics from the Twilio API
 * 
 * Twilio Message Database Columns:
 * sid
 * account_sid
 * messaging_service_sid
 * num_segments
 * status
 * from
 * to
 * direction
 * body
 * num_media
 * error_code
 * error_message
 * price
 * price_unit
 * api_version
 * date_created
 * date_sent
 * date_updated
 * uri
 * 
 * */
define('TWILIO_ACCOUNT_SID', 'ACd4b4efa2054f2aaf8c06ab0693f3f65b');
define('TWILIO_PRIMARY_NUMBER', '+13868886995');
define('TWILIO_MESSAGING_SID', 'MGed693e77e70d6f52882605d37cc30d4c');

class TwilioCsvReports
{

    private $api_key;
    private $api_token;
    private $api_url_base;
    private $twilio;
    private $twilio_phone_number;
    private $message_database;

    public function __construct()
    {
        $twilio_csv_options = get_option('twilio-csv');
        $this->api_key = $twilio_csv_options['api_sid'] ?? null;
        $this->api_token = $twilio_csv_options['api_auth_token'] ?? null;
        $this->twilio_phone_number = $twilio_csv_options['sending_number'] ?? null;
        $this->message_database = 'twilio_csv_messages';

        // If these options aren't set, redirect to Options page: https://thejohnson.group/wp-admin/options-general.php?page=twilio-csv
        if (empty($this->api_key) || empty($this->api_token)) {
            wp_redirect(admin_url('options-general.php?page=twilio-csv'));
            exit;
        }

        $this->api_url_base = 'https://api.twilio.com/2010-04-01/Accounts/' . $this->api_key . '/';
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

    public function get_sending_number()
    {
        return $this->twilio_phone_number;
    }

    public function get_api_url_base()
    {
        return $this->api_url_base;
    }

    public function get_api_url(string $url = '')
    {
        return $this->api_url_base . $url;
    }

    public function get_most_recent_timestamp() {
        // Get the most recent timestamp from the database:
        global $wpdb;
        $table_name = $wpdb->prefix . $this->message_database;
        $sql = "SELECT MAX(date_created) AS date_created FROM $table_name";
        $result = $wpdb->get_results($sql);
        return $result[0]->date_created ?? null;
    }

    public function get_outbound( $from = null, Datetime $start_date = null, Datetime $end_date = null, $request_size = null, $page_size = 100 )
    {
        $filters = [];
        $filters['From'] = $from ?? $this->twilio_phone_number;
        if ($start_date) { $filters['dateSentAfter'] = $start_date; }
        if ($end_date) { $filters['dateSentBefore'] = $end_date; }

        try 
        { 
            $outbound = $this->twilio->messages->stream(
                $filters,
                $request_size,
                $page_size
            );
            
        } catch (Twilio\Exceptions\DeserializeException $e) {
        } catch (Twilio\Exceptions\TwilioException $e) {
        }
        return $outbound;
    }
    // $next_page = null;
    // $current_page = $outbound->firstPage;
    // $page_num = 1;
    // $record_count = 0;
    // do {
    //     print("Page $page_num\n");
    //     $page_num = $page_num + 1;
    //     $next_page = $current_page->nextPage();
    //     $current_page = $next_page;
    //     $record_count++;
    // } while ( $next_page && $page_num < $page_size );

    // print("Total Records Retrieved from Twilio: $record_count\n");

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

    // CRUD Functions for Messages Table
    private function insert_message( $message_data )
    {   
        $print_date = $message_data->date_created;
        $print_date = date('Y-m-d H:i:s', strtotime($print_date));
        $message = "Message $message_data->sid created on $print_date\n";
        $query = array(
            'sid' => $message_data->sid,
            'account_sid' => $message_data->accountSid,
            'messaging_service_sid' => $message_data->messagingServiceSid,
            'num_segments' => $message_data->numSegments,
            'status' => $message_data->status,
            'from' => $message_data->from,
            'to' => $message_data->to,
            'direction' => $message_data->direction,
            'body' => $message_data->body,
            'num_media' => $message_data->numMedia,
            'error_code' => $message_data->errorCode,
            'error_message' => $message_data->errorMessage,
            'price' => $message_data->price,
            'price_unit' => $message_data->priceUnit,
            'api_version' => $message_data->apiVersion,
            'date_created' => $message_data->dateCreated,
            'date_sent' => $message_data->dateSent,
            'date_updated' => $message_data->dateUpdated,
            'uri' => $message_data->uri
        );
        return $message;

        global $wpdb;
        $table_name = $wpdb->prefix . $this->message_database;

        // Check for sid duplicates before creating entry
        $sql = "SELECT COUNT(*) AS count FROM $table_name WHERE sid = '$message_data->sid'";
        $result = $wpdb->get_results($sql);
        var_dump ($result[0]->count);
        if ($result[0]->count == 0) {
            try {
                $entry = $wpdb->insert($table_name, $query);
                var_dump($entry);
            } catch (Exception $e) {
                print "Error: $e";
            }
            print 'Inserted record, supposedly';
            die;
        }
    }

    private function translate_datetime_from_twilio($args) {
        return false;
    }
}


