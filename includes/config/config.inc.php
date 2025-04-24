<?php

/*
    *   File name: config.inc.php
    *   Created by: Brandon Hills
    *   Contact: brandon@goodstewarddesigns.com
    *   Last modified: 4/17/25
    *
    *   Configuration file does the following things:
    *   - Has site settings in one location.
    *   - Stores URLs and URIs as constants.
    *   - Sets how errors will be handled.
*/

# ****************** #
# **** SETTINGS **** #

//  Set timezone:
date_default_timezone_set('America/New_York');

//  Get project folder name:
$project_folder = basename(dirname(__DIR__));
$credential_path = realpath(__DIR__ . '/../../../../credentials');
define('LOG_PATH', realpath(__DIR__ . '/../logs') . '/');

//  Automatically detect protocol (HTTP or HTTPS):
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";

//  Get the full domain (e.g., localhost, staging.example.com, example.com):
$host = $_SERVER['HTTP_HOST'];

//  Get the base path (if installed in a subdirectory):
$script_path = dirname($_SERVER['SCRIPT_NAME']);  //  e.g., /myproject
$script_path = rtrim($script_path, '/\\') . '/';  //  Ensure trailing slash.

//  Define BASE_URI and BASE_URL:
define('BASE_URI', $script_path);
define('BASE_URL', $protocol . $host . $script_path);

//  Determine the environment:
$host_prefix = substr($host, 0, 5);
if (in_array($host_prefix, ['local', '127.0', '192.1'])) {

    //  Define the environment:
    define('ENVIRONMENT', 'development');

} elseif (str_contains($host, 'staging')) {

    //  Define the environment:
    define('ENVIRONMENT', 'staging');

} else {

    //  Define the environment:
    define('ENVIRONMENT', 'production');

}

//  Get commonly used functions:
require_once('includes/functions.inc.php');

# **** SETTINGS **** #
# ****************** #

/*
# ************************ #
# **** DATABASE SETUP **** #

$db_file = get_credential_file("db_settings_{$project_folder}.php");
if ($db_file) require_once($db_file);

if (!isset($db) || !is_array($db)) {
    error_log("Database config missing or invalid in file: $db_file");
    die('A system error occurred.');
}

//  Sanity check for required keys:
validate_credential_keys($db, ['host', 'user', 'password', 'name'], 'Database config');

//  Set database connection:
$dbc = @mysqli_connect(
    $db['host'],
    $db['user'],
    $db['password'],
    $db['name']
);

//  MYSQLI error handling:
if (!$dbc) {
    $msg = 'Database connection failed: ' . mysqli_connect_error();
    if (ENVIRONMENT === 'development') {
        trigger_error($msg, E_USER_ERROR);
    } else {
        error_log($msg);
        die('A system error occurred.');
    }
}

//  Set the encoding:
mysqli_set_charset($dbc, 'utf8');

# **** DATABASE SETUP **** #
# ************************ #
*/

# *********************** #
# **** SMTP SETTINGS **** #

$smtp_file = get_credential_file("smtp_settings_{$project_folder}.php");

if ($smtp_file) require_once($smtp_file);

if (!isset($smtp) || !is_array($smtp)) {
    error_log("SMTP config missing or invalid in file: $smtp_file");
    die('A system error occurred.');
}

//  Sanity check for required keys:
validate_credential_keys($smtp, ['host', 'username', 'password', 'port', 'secure'], 'SMTP config');

# **** SMTP SETTINGS **** #
# *********************** #

# **************************** #
# **** RECAPTCHA SETTINGS **** #

$recaptcha_file = get_credential_file("recaptcha_settings_{$project_folder}.php");
if ($recaptcha_file) require_once($recaptcha_file);

//  Sanity check for required source constants:
$required_keys = [
    'RECAPTCHA_SITE_KEY_DEV',
    'RECAPTCHA_SECRET_KEY_DEV',
    'RECAPTCHA_SITE_KEY_LIVE',
    'RECAPTCHA_SECRET_KEY_LIVE'
];

foreach ($required_keys as $key) {
    if (!defined($key)) {
        $msg = "Missing reCAPTCHA source constant: $key";
        if (ENVIRONMENT === 'development') {
            trigger_error($msg, E_USER_ERROR);
        } else {
            error_log($msg);
            die('A system error occurred.');
        }
    }
}

//  Assign environment-appropriate constants:
if (ENVIRONMENT === 'development') {
    define('RECAPTCHA_SITE_KEY', RECAPTCHA_SITE_KEY_DEV);
    define('RECAPTCHA_SECRET_KEY', RECAPTCHA_SECRET_KEY_DEV);
} else {
    define('RECAPTCHA_SITE_KEY', RECAPTCHA_SITE_KEY_LIVE);
    define('RECAPTCHA_SECRET_KEY', RECAPTCHA_SECRET_KEY_LIVE); 
}

# **** RECAPTCHA SETTINGS **** #
# **************************** #

/*
# ************************* #
# **** API CREDENTIALS **** #

$api_file = get_credential_file("api_settings_{$project_folder}.php");
if ($api_file) require_once($api_file);

if (!isset($api) || !is_array($api)) {
    error_log("API config missing or invalid in file: $api_file");
    die('A system error occurred.');
}

//  Sanity check for required keys:
validate_credential_keys($api, ['url', 'secret_key'], 'API config');

# **** API CREDENTIALS **** #
# ************************* #
*/

# ************************** #
# **** ERROR MANAGEMENT **** #

// Create the error handler:
function my_error_handler($e_number, $e_message, $e_file, $e_line, $e_vars = []) {

    //  Format the timestamp:
    $timestamp = date('Y-m-d H:i:s');

    //  Create a readable error type name:
    $error_types = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE ERROR',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER DEPRECATED',
    ];

    $type = $error_types[$e_number] ?? 'UNKNOWN';

    // Build the error message:
    $message = "[$timestamp] [$type] $e_message in $e_file on line $e_line\n";

    //  Add variable dump for debugging:
    if (!empty($e_vars)) {

        $message .= "Variables: " . print_r($e_vars, true) . "\n";

    }

    //  Define log file:
    $log_path = get_log_file('error.log');

    //  Ensure logs directory exists and is secure:
    if (!is_dir(LOG_PATH)) mkdir(LOG_PATH, 0775, true);

    //  Define htaccess file:
    $htaccess_file = LOG_PATH . '.htaccess';


    //  Create the .htaccess file if it doesn't exist:
    if (!file_exists($htaccess_file)) {
            
$htaccess_content = <<<HTACCESS
<FilesMatch "\.(log|txt)$">
    Require all denied
</FilesMatch>
Options -Indexes
HTACCESS;

        file_put_contents($htaccess_file, $htaccess_content);
    }

    //  Ensure gitkeep file to preserve logs folder:
    $gitkeep = LOG_PATH . '.gitkeep';
    if(!file_exists($gitkeep)) file_put_contents($gitkeep, '');
        
    //  Rotate error log if over size limit or archive is over limit:
    rotate_log_file($log_path);

    //  Write the current error to log file:
    error_log($message, 3, $log_path); // 3 = append to file

    //  Show error in browser if debug mode is on
    if (ENVIRONMENT === 'development') {

        echo "<div class='error'><pre>$message</pre></div>";
        debug_print_backtrace();

    } elseif ($e_number != E_NOTICE && $e_number < 2048) {

            http_response_code(500);
            echo '<div class="error">A system error occurred. We apologize for the inconvenience.</div>';

    }
}  //  End of my_error_handler() definition.

//  Use my error handler:
set_error_handler('my_error_handler');

# **** ERROR MANAGEMENT **** #
# ************************** #