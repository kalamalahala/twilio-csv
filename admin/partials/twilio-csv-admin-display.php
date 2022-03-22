<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://thejohnson.group/
 * @since      1.0.0
 *
 * @package    Twilio_Csv
 * @subpackage Twilio_Csv/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<form method="POST" action='options.php'>
    <?php
         settings_fields($this->plugin_name);
         do_settings_sections('twilio-csv-settings-page');

         submit_button();  
   ?>
</form>