(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

})( jQuery );

// When a user clicks an Action Button, send a POST request to the recruiting page
// with the parameters included in the anchor tag that was clicked
// Action Button IDs:
// twilio-email-button
// twilio-reject-button
// twilio-callback-button
// twilio-markcomplete-button
/*

jQuery(document).ready(function($) {
	jQuery('#twilio-email-button').click(function(e) {
		// Prevent the default action of the anchor tag from happening
		e.preventDefault();
		// Get the anchor tag that was clicked
		var anchor = jQuery(this);
		// Hide the button and display a loading spinner Font Awesome 4
		anchor.hide();
		anchor.after('<i class="fas fa-spinner fa-spin" id="loading-icon"></i>');
		// Collect parameters from the href attribute of the anchor tag
		var parameters = anchor.attr('href').split('?')[1];
		// break parameters into key value pairs
		var params = parameters.split('&');
		// Create an object to hold the parameters
		var data = {};
		// Loop through the parameters and add them to the data object
		for (var i = 0; i < params.length; i++) {
			var pair = params[i].split('=');
			data[pair[0]] = pair[1];
		}

		// Send the POST request to the recruiting page using post, not ajax
		jQuery.ajax({
			type: 'POST',
			url: 'https://thejohnson.group/agent-portal/recruiting/',
			data: data,
			success: function(response) {
				// Hide the loading spinner, show a checkmark success message
				jQuery('#loading-icon').hide();
				anchor.after('<i class="fas fa-check" id="success-icon"></i> Sent!');
			},
			error: function(response) {
				// Hide the loading spinner, show an error message
				jQuery('#loading-icon').hide();
				anchor.after('<i class="fas fa-times" id="error-icon"></i> Error!');
			}
		});
	});

	} );*/