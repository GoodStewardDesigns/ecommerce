<?php

/*
    *   File name: emailHandler.php
    *   Created by: Brandon Hills
    *   Contact: brandon@goodstewarddesigns.com
    *   Last modified: 4/19/25
    *
    *   This script handles contact form submissions:
    *   - Cleans and validates input.
    *   - Verifies reCAPTCHA + timing + rate limit.
    *   - Sends email.
    *   - Responds with JSON.
*/

require_once('includes/config.inc.php');

//  Set JSON header
header('Content-Type: application/json');




# **** Form Submission **** #
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Invalid request method.', 405);
}

# **** Rate Limiting **** #
if (is_rate_limited()) {
    json_error('Too many submissions. Please try again later.', 429);
}


# **** SPAM & reCAPTCHA Validation **** #
$verify = validate_contact_form();
if ($verify !== true) {
    json_error($verify, 400);
}

# **** Sanitize and validate inputs **** #
$clean = array_map('spam_filter', $_POST);

//  List required fields - minimal upkeep:
$required = ['email', 'message'];
foreach ($required as $key) {
    if (empty($clean[$key])) {
        json_error("Missing required field: $key");
    }
}

//  Simplify vars or create fallbacks for required vars:
$name = $clean['name'] ?: '[No Name]';

//  Validate email:
if (!filter_var($clean['email'], FILTER_VALIDATE_EMAIL)) {
    json_error('Please enter a valid email address.');
}

# **** Compose Email **** #
$to = CONTACT_EMAIL;
$subject = 'Website Contact Request';
$htmlBody = build_email_template($subject, $clean);
$altBody = strip_tags($htmlBody);

//  Headers:
$headers = [
    'from_email' => $clean['email'],
    'from_name' => $name,
];

$sent = send_email($to, $subject, $htmlBody, $altBody, $headers);

/*
# **** Optional: Add to mailing list (via API) **** #
$apiUrl = API_URL;
$payload = [
    'email' => $clean['email'],
    'name' => $name,
    //  'tags' => ['contact-form'], //  Optional: add segmentation/tags.
];

$apiHeaders = [
    'Authorization: ' . API_SECRET_KEY,
];

$apiResponse = send_api_request($apiUrl, $payload, $apiHeaders);

if (!$apiResponse) {
    error_log("Failed to add {$clean['email']} to mailing list.");
    //  Optional: json_error('Could not subscribe to mailing list.', 500);
}
*/

if ($sent) {
    json_success("Thanks, $name! Your message has been sent. 📬");
} else {
    json_error('There was a problem sending your message. Please try again soon.', 500);
}
        
?>