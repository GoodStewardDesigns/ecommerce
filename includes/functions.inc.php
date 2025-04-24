<?php

/*
    *   File name: functions.inc.php
    *   Created by: Brandon Hills
    *   Contact: brandon@goodstewarddesigns.com
    *   Last modified: 4/18/25
    *
    *   - Define reusable functions here:
*/

//  Meta data assignment to reduce duplicate code:
function applyMetaData($entry, $basePath, $slug) {
    global $meta_title, $meta_description, $meta_img, $meta_img_path;

    $meta_title = $entry['metaTitle'] ?? $meta_title;
    $meta_description = $entry['metaDescription'] ?? $meta_description;
    
    if (!empty($entry['metaImage'])) {
    
        $og_img_path = $basePath . '/' . $slug . '/' . $entry['metaImage'];
    
        if (file_exists(BASE_URI . $og_img_path)) {
            $meta_img = BASE_URL . $og_img_path;
        } else {
            error_log("Missing og:image at expected path: $og_img_path");
        }
            
    }
}  //  End of applyMetaData().

// SPAM filter function for contact forms:
function spam_filter($value) {

    // List of values to filter out:
    $bad_values = ['to:', 'cc:', 'bcc:', 'content-type:', 'mime-version:', 'multipart-mixed:', 'content-transfer-encoding:'];

    // Return empty string if bad values found:
    foreach ($bad_values as $v) {
        if (stripos($value, $v) !== false) return '';
    }

    // Replace newline characters with spaces:
    $value = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $value);

    //Return the value:
    return trim($value);

} // End of spam_filter().

# === PHPMailer === #
//  Replaces php mail().

//  Load PHPMailer once:
$phpmailer_loader_path = match (ENVIRONMENT) {
    'development' => 'C:/xampp/php-libs/phpmailer/load_phpmailer.php',
    'staging', 'production' => '/home/brandonhills/php-libs/phpmailer/load_phpmailer.php',
    default => null
};

if (!empty($phpmailer_loader_path) && file_exists($phpmailer_loader_path)) {
    require_once $phpmailer_loader_path;
} else {
    $msg = "PHPMailer loader not found at: $phpmailer_loader_path";
    if (ENVIRONMENT === 'development') {
        trigger_error($msg, E_USER_WARNING);
    } else {
        error_log($msg);
    }
}

/**
 * Send an email using PHPMailer.
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $htmlBody HTML content of the message
 * @param string $altBody (optional) Plain text version
 * @param array $headers (optional) Array of extra headers like from name/email
 * @return bool True on success, false on failure
 */

function send_email($to, $subject, $htmlBody, $altBody = '', $headers = [], $smtp_override = []) {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true); //  Use full namespace since it's included externally

    //  Choose SMTP config
    global $smtp;  //  loaded via config.inc.php
    $settings = array_merge($smtp ?? [], $smtp_override);  //  allow for override if needed.

    try {
        $mail->isSMTP();
        $mail->Timeout = 10;  //  Timeout after 10 seconds instead of hanging.
        $mail->SMTPDebug = 0;  //  Disable verbose debug.
        $mail->CharSet = 'UTF-8';
        $mail->Host = $settings['host'] ?? 'smtp.default.com';
        $mail->SMTPAuth = true;
        $mail->Username = $settings['username'] ?? 'default@example.com';
        $mail->Password = $settings['password'] ?? '';
        $mail->SMTPSecure = $settings['secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $settings['port'] ?? 587;

        $fromEmail = $headers['from_email'] ?? $settings['username'];
        $fromName = $headers['from_name'] ?? 'Website';
        $mail->setFrom($fromEmail, $fromName);

        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        $errorMsg = "PHPMailer Error: " . $mail->ErrorInfo;

        //  Log the actual exception message too:
        error_log($errorMsg);
        error_log("Exception message: " . $e->getMessage());

        return false;
    }
}  // End of send_email().

function validate_contact_form($action = 'contact_form') {

    //  reCAPTCHA V3
    $token = $_POST['recaptcha-token'] ?? '';
    $secret = RECAPTCHA_SECRET_KEY;

    //  Check with Google:
    $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secret) . '&response=' . urlencode($token));
    if ($response === false) {
        error_log('Failed to contact Google reCAPTCHA server.');
        return 'Unable to verify reCAPTCHA at this time.';
    } else {
        $data = json_decode($response, true);
    }

    //  Log full reCAPTCHA response for debugging:
    if (ENVIRONMENT === 'development' || ENVIRONMENT === 'staging') {
        $log_path = get_log_file('recaptcha-debug.log');
        rotate_log_file($log_path);
        $message = date('Y-m-d H:i:s') . ' - ' . print_r($data, true) . "\n";
        file_put_contents($log_path, $message, FILE_APPEND);
    }
    
    //  Check response:
    if (!is_array($data) || empty($data['success']) || $data['score'] < 0.5 || $data['action'] !== $action) {
        error_log('reCAPTCHA failed: ' . json_encode($data));
        return 'Failed reCAPTCHA verification.';
    }

    //  Time-based validation:
    $load_time = $_POST['load-time'] ?? 0;
    if (time() - $load_time < 2) {
        return 'Form submitted too quickly (bot-like behavior).';
    }

    return true;  //  Passed all checks

}  //  End of validate_contact_form().

function is_rate_limited($ip = null, $limit = 5, $minutes = 5) {

    //  Determine IP address:
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'];

    //  Define log file path:
    $log_file = get_log_file('ip-rate-limit.log');

    //  If log file doesn't exist, create it:
    if (!$log_file) {
        return true;  //  Fail safe: allow through if path missing.
    }

    if (!file_exists($log_file)) {
        touch($log_file);
    }

    //  Define the time window:
    $now = time();
    $window_start = $now - ($minutes * 60);

    //  Check log entries:
    $entries = file($log_file, FILE_IGNORE_NEW_LINES) ?: [];
    $entries = array_filter($entries, function ($line) use ($window_start) {

        //  Remove entries older that time window:
        [$ts] = explode('|', $line);
        return $ts >= $window_start;
    });

    //  Count entries for this IP
    $entries_for_ip = array_filter($entries, function ($line) use ($ip) {
        return strpos($line, "|$ip|") !== false;
    });

    if (count($entries_for_ip) >= $limit) {
        return true;  //  Block it.
    }

    //  Log this attempt:
    $entries[] = "$now|$ip|{$_SERVER['REQUEST_URI']}";
    file_put_contents($log_file, implode("\n", $entries));

    //  Allow request:
    return false;


}  //  End of is_rate_limited().

function json_error($message, $code = 400, $log = true) {
    http_response_code($code);
    if ($log) {
        error_log("Form Error: $message");
    }
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}  //  End of json_error().

function json_success($message) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => $message]);
    exit;
}  //  End of json_success().

function build_email_template($subject, $data) {
    $style = 'font-family: sans-serif; font-size: 16px; color: #333;';
    $wrapper = 'max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; background-color: #f9f9f9;';
    $titleStyle = 'margin-bottom: 20px; font-size: 20px; font-weight: bold;';

    $html = "<div style='$wrapper'>";
    $html .= "<h2 style='$titleStyle'>$subject</h2>";
    $html .= "<table cellpadding='10' cellspacing='0' style='width: 100%; $style'>";

    foreach ($data as $label => $value) {
        if (!empty($value)) {
            $label = ucwords(str_replace('-', ' ', $label));
            $html .= "
                <tr>
                    <td style='font-weight: bold; width: 30%; vertical-align: top;'>$label:</td>
                    <td>$value</td>
                </tr>
            ";
        }
    }

    $html .= "</table></div>";
    return $html;
}

# **** CRM Integration **** #
# ************************* #

/**
 * Send POST data to an external API using cURL
 * 
 * @param string $url The API endpoint URL
 * @param array $payload Data to send (associative array)
 * @param array $headers Options custom headers (e.g., auth tokens)
 * @return array|false The decoded JSON response or false on failure
 */

function send_api_request($url, $payload, $headers = []) {

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json'
        ], $headers),
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('API cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }

    
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($status >= 200 && $status < 300) {
        return $decoded;
    } else {
        error_log("API Request failed. Status: $status, Response: $response");
        return false;
    }
}  //  End of send_api_request().

# **** CRM Integration **** #
# ************************* #

# **** Credential Manager **** #
# **************************** #

/**
 * Get the full path to a credentials file safely.
 * 
 * @param string $filename Name of the credential file (e.g., smtp_settings_project.php)
 * @return string|false Full path if found, false if not
 */

function get_credential_file($filename) {
    global $credential_path;

    if (empty($credential_path)) {
        error_log('Missing $credential_path global definition.');
        return false;
    }

    $path = $credential_path . '/' . $filename;

    if (!file_exists($path)) {
        error_log("Credential file not found: $path");
        return false;
    }

    return $path;

}  //  End of get_credential_file().

/**
 * Validate that an array contains all required keys with non-empty values.
 * 
 * @param array $array The array to check (e.g., $smtp)
 * @param array $required_keys List of required keys
 * @param string $context Context label for error messages
 * @return bool True if valid, otherwise false
 */

function validate_credential_keys($array, $required_keys, $context = 'Config') {
    foreach ($required_keys as $key) {
        if (empty($array[$key])) {
            $msg = "Missing required {$context} value: {$key}";

            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                trigger_error($msg, E_USER_ERROR);
            } else {
                error_log($msg);
                die('A system error occurred.');
            }

            return false;
        }
    }

    return true;

}  //  End of validate_credential_keys().

# **** Credential Manager **** #
# **************************** #

# ********************* #
# **** Log Manager **** #

/**
 * Get the full path to a log file safely.
 * 
 * @param string $filename Name of the log file (e.g., error.log)
 * @return string|false Full path if LOG_PATH found, false if not
 */

function get_log_file($filename) {
    if (!defined('LOG_PATH')) {
        error_log('LOG_PATH not defined');
        return false;
    }

    $filename = basename($filename);  //  Prevent path traversal
    return LOG_PATH . $filename;
 
}  //  End of get_log_file().

/**
 * Rotate a log file if it exceeds the size limit.
 * Archives the current log as a gzip file and deletes old archives if over limit.
 * 
 * @param string $log_file_path Full path to the log file.
 * @param int $max_size Maximum size in bytes before rotation (default: 1 MB).
 * @param int $max_archives Maximum number of archives to keep (default: 10).
 */
function rotate_log_file($log_file_path, $max_size = 1048576, $max_archives = 10) {
    if (!file_exists($log_file_path)) return;

    if (filesize($log_file_path) > $max_size) {
        $log_dir = dirname($log_file_path);
        $log_name = basename($log_file_path, '.log');
        $timestamp = date('Ymd_His');
        $archive_plain = "$log_dir/{$log_name}-$timestamp.log";
        $archive_gz = $archive_plain . '.gz';

        rename($log_file_path, $archive_plain);

        //  Compress the rotated log:
        $contents = file_get_contents($archive_plain);
        $gz = gzopen($archive_gz, 'w9');
        gzwrite($gz, $contents);
        gzclose($gz);
        unlink($archive_plain);

        //  Clean old archives
        $archives = glob("$log_dir/{$log_name}-*.log.gz");
        if (count($archives) > $max_archives) {
            sort($archives);
            foreach (array_slice($archives, 0, count($archives) - $max_archives) as $old) {
                unlink($old);
            }
        }
    }
}

# **** Log Manager **** #
# ********************* #
