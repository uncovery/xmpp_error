<?php
/*
 * Copyright (C) 2014 Uncovery
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

global $XMPP_ERROR;
require_once(__DIR__ . '/config.php');

// this variable tracks if an actual error occured and is set in XMPP_ERROR_handler
$XMPP_ERROR['error'] = false;
// this variable tracks if a manually triggered error and is set in XMPP_ERROR_handler
$XMPP_ERROR['error_manual'] = false;

    // list to translate error numbers into error text
$XMPP_ERROR['error_types'] = array(
    1 => 'E_ERROR',
    2 => 'E_WARNING',
    4 => 'E_PARSE',
    8 => 'E_NOTICE',
    16 => 'E_CORE_ERROR',
    32 => 'E_CORE_WARNING',
    64 => 'E_COMPILE_ERROR',
    128 => 'E_COMPILE_WARNING',
    256 => 'E_USER_ERROR',
    512 => 'E_USER_WARNING',
    1024 => 'E_USER_NOTICE',
    2048 => 'E_STRICT',
    4096 => 'E_RECOVERABLE_ERROR',
    8192 => 'E_DEPRECATED',
    16384 => 'E_USER_DEPRECATED',
    // non-standard error types specific for XMPP_ERROR
    99998 => 'E_XMPP_TRIGGER',
    99999 => 'E_XMPP_TRACE',
);

// this defines the start time of the whole script
// if XMPP_ERROR is included too late in the script, this number will not
// be representative
if (!defined ('XMPP_ERROR_START_TIME')) {
    define('XMPP_ERROR_START_TIME', microtime(true));
}

// definre the function that will be called in case of an error
set_error_handler('XMPP_ERROR_handler');
// define the function that will be called at the end of script execution
register_shutdown_function("XMPP_ERROR_shutdown_handler");

/**
 * Register an error for tracking processes.
 * ideally will be called by entering the following line on top of your functions
 * XMPP_ERROR_trace(__FUNCTION__, func_get_args());
 * Above code will register the function name and the associated arguments
 * You can also replace the two arguments with other information you want to track
 * throughout your script such as
 * XMPP_ERROR_trace("my_check_point_name", $check_point_var);
 *
 * @global array $XMPP_ERROR
 * @param mixed $type
 * @param mixed $data
 */
function XMPP_ERROR_trace($type, $data = '') {
    global $XMPP_ERROR;
    // insert the current time and passed variables
    // make sure that there is no entry at the same microsecond
    if (!isset($XMPP_ERROR[XMPP_ERROR_ptime()]["E_XMPP_TRACE"])) {
        $XMPP_ERROR[XMPP_ERROR_ptime()]["E_XMPP_TRACE"][$type] = $data;
    } else {
        // otherwise create a sub-entry
        $XMPP_ERROR[XMPP_ERROR_ptime()][]["E_XMPP_TRACE"][$type] = $data;
    }
}

/**
 * Allows manual insertion of errors that will cause a report in the end
 * Good for catching exceptions and checking for unexpected outcomes
 *
 * @global array $XMPP_ERROR
 * @param string $text
 */
function XMPP_ERROR_trigger($text) {
    global $XMPP_ERROR;
    $XMPP_ERROR['error_manual'] = $text;
    $XMPP_ERROR[XMPP_ERROR_ptime()]["E_XMPP_TRIGGER"] = $text;
}


/**
 * Send an XMPP message to the configured recipient
 * You can use this function also go send texts as a script-output
 *
 * @global array $XMPP_ERROR
 * @global JAXL $client
 * @global type $message
 * @param type $msg
 */
function XMPP_ERROR_send_msg($msg) {
    global $XMPP_ERROR;
    if ($XMPP_ERROR['config']['self_track']) {
        XMPP_ERROR_trace(__FUNCTION__, func_get_args());
    }

    // assume we use JAXL, if other systems should be used, those would have to branch here
    if ($XMPP_ERROR['config']['xmpp_lib_name'] == 'JAXL') {
        require_once $XMPP_ERROR['config']['xmpp_lib_path'] . '/jaxl.php';
        require_once $XMPP_ERROR['config']['xmpp_lib_path'] . '/xmpp/xmpp_msg.php';
    } else {
        die('currently, XMPP_ERROR only supports JAXL as a xmpp sending module');
    }

    // current time
    $date_obj = new DateTime();
    // we allow definition of an alternative timezone to be more admin-friendly
    if ($XMPP_ERROR['config']['reports_timezone']) {
        $date_obj->setTimezone(new DateTimeZone($XMPP_ERROR['config']['reports_timezone']));
    }
    // format time
    $today = $date_obj->format('Y-m-d H:i:s');

    // in case message is an array, format it so that it can be sent
    if (is_array($msg)) {
        $msg = var_export($msg, true);
    }

    global $client, $message;
    // actual message
    $message = '[' . $XMPP_ERROR['config']['project_name'] . "] $today: $msg";

    // configure the XMPP Client to send hte message
    $client = new JAXL(array(
        'jid' => $XMPP_ERROR['config']['xmpp_sender_username'],
        'pass' => $XMPP_ERROR['config']['xmpp_sender_password'],
        'auth_type' => $XMPP_ERROR['config']['xmpp_sender_auth-type'],
        'log_level' => JAXL_ERROR,
        'strict' => false,
    ));
    // add the message
    $client->add_cb('on_auth_success', function() {
        global $client, $message, $XMPP_ERROR;
        foreach ($XMPP_ERROR['config']['xmpp_recipient'] as $recipient) {
            $client->send_chat_msg($recipient, $message);
        }
        $client->send_end_stream();
    });

    // sending of message failed?
    $client->add_cb('on_auth_failure', function($reason) {
        global $client;
        $client->send_end_stream();
        die("XMPP: got on_auth_failure cb with reason $reason");
    });

    // execute JAXL, actually sends the message
    $client->start();
}

/**
 * Create an error report and send the message out via XMPP
 *
 * @global array $XMPP_ERROR
 * @global type $var_name
 * @param type $error
 */
function XMPP_ERROR_error_report($error) {
    global $XMPP_ERROR;
    if ($XMPP_ERROR['config']['self_track']) {
        XMPP_ERROR_trace(__FUNCTION__, func_get_args());
    }

    // date creation
    $date_obj = new DateTime();
    // we allow definition of an alternative timezone to be more admin-friendly
    if ($XMPP_ERROR['config']['reports_timezone']) {
        $date_obj->setTimezone(new DateTimeZone($XMPP_ERROR['config']['reports_timezone']));
    }
    // date elements for the folders
    $year = $date_obj->format('Y');
    $month = $date_obj->format('m');
    $day = $date_obj->format('d');
    $hour = $date_obj->format('H');
    // this will be the final name of the file. Added some random element in the end to harden URL guessing
    // and prevent overwriting in case of 2 messages in the same microsencond
    $time_now = $date_obj->format('Y-m-d H-i-s') . substr((string)microtime(), 1, 8);
    $rnd_now = str_replace(" ", "_", $time_now) . "_" . rand(0, 9999999999999);

    // path to store the attached message
    $path = $XMPP_ERROR['config']['reports_path'] . "/$year/$month/$day/$hour/";
    // url to reach the message
    $url = $XMPP_ERROR['config']['reports_url'] . "/$year/$month/$day/$hour/";
    // final filename
    $file = "Error_$rnd_now.html";
    // check if we need to create the directory
    if (!file_exists($path)) {
        // create the directory
        $check = mkdir($path, 0777, true);
        // did it work?
        if (!$check) {
            die("Could not create path $path, please check permissions");
        }
    }
    // compress previous months items
    if ($XMPP_ERROR['config']['reports_archive_date']) {
        require_once(__DIR__ . '/archive.inc.php');
        XMPP_ERROR_archive();
    }

    // add the configured header for the attached message

    // HTML header for the error reports
    $msg_text = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
        <head>
            <title>[' . $XMPP_ERROR['config']['project_name'] . '] XMPP ERROR Report</title>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            . XMPP_ERROR_css() . '
        </head>
        <body>';

    // initialize the variable
    $main_error = '';

    // we have a main error that triggered the message
    $msg_text .= "<div class=\"main_data\"><h1>Main Error ($time_now)</h1>\n";
    // in case the main error is an array, print it nicely
    if (is_array($error) || is_object($error)) {
        $main_error .= "Multiple Variables";
    } else {
        $main_error .= $error;
    }
    $msg_text .=  XMPP_ERROR_array2text($error) . "</div>\n";


    // the actual function trace should be on top.
    // strip the XMPP config from report without changing it
    $xmpp_report = $XMPP_ERROR;
    unset($xmpp_report['config']);
    unset($xmpp_report['error']);
    unset($xmpp_report['error_manual']);
    $data['$XMPP_ERROR'] = $xmpp_report;

    // iterate the configured globals and add them to the list
    foreach ($XMPP_ERROR['config']['track_globals'] as $var_name) {
        global $$var_name;
        $data['$' . $var_name] = $$var_name;
    }

    // add a tack trace
    $stack_trace = null;
    $trace = debug_backtrace();
    if (isset($trace[2])) {
        $stack_trace = $trace[2];
    }
    $data['Stack Trace'] = $stack_trace;
    // add other variables
    $data['$_POST'] = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    $data['$_GET'] = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
    $data['$_COOKIE'] = filter_input_array(INPUT_COOKIE, FILTER_SANITIZE_STRING);
    $data['$_SERVER'] = filter_input_array(INPUT_SERVER, FILTER_SANITIZE_STRING);

    // now iterate those all and add them to the attached file
    foreach ($data as $title => $text) {
        $msg_text .= "<div class=\"data_block\"><h2>$title:</h2>\n" . XMPP_ERROR_array2text($text) . "</div>\n";
    }

    // now add the configured footer
    $msg_text .= "    <div id=\"footer\">Created with <a href=\"https://github.com/uncovery/xmpp_error\">XMPP_ERROR</a></div>"
        . "    </body>\n</html>";

    // write the whole thing to a file
    $check = file_put_contents($path . $file, $msg_text);
    // check if it worked
    if (!$check) {
        die("could not write error file to path $path, please check permissions");
    }
    // send the message with the URL to the attachement to XMPP client
    XMPP_ERROR_send_msg("$main_error\nError Report: $url$file");
}

/**
 * The actual error handler. Will be called on each error and should filter for
 * items we do not care about
 *
 * @global type $XMPP_ERROR
 * @param type $errno
 * @param type $errstr
 * @param type $errfile
 * @param type $errline
 * @return type
 */
function XMPP_ERROR_handler($errno, $errstr, $errfile, $errline) {
    global $XMPP_ERROR;

    // list to translate error numbers into error text
    $error_types = $XMPP_ERROR['error_types'];
    // make sure we care about the error
    $check = XMPP_ERROR_filter($errno, $errfile);
    if (!$check) {
        return;
    }
    // translate the error number into text
    $errortype = $error_types[$errno];

    // get the current time
    $time = XMPP_ERROR_ptime();
    // get the referrer
    $referer = '';
    $s_server = filter_input_array(INPUT_SERVER, FILTER_SANITIZE_STRING);
    if (isset($s_server['HTTP_REFERER'])) {
        $referer = "\nReferer: " . $s_server['HTTP_REFERER'];
    }
    $called_url = '';
    if (isset($s_server['SCRIPT_URI'])) {
        $called_url = "\nScript URL: " . $s_server['SCRIPT_URI'] . "?" . $s_server['QUERY_STRING'];
    }
    // format the actual error message
    $text = "$time\n$errortype: $errstr \nSource: Line $errline of file $errfile$referer$called_url";
    // add the error to the list
    XMPP_ERROR_trace($errortype, $text);
    // register that we had an error so the shutdown handler sends an alert
    $XMPP_ERROR['error'] = $text;
}

/**
 * When the script ends, check if an error happened. If so, send a message to XMPP
 *
 * @global array $XMPP_ERROR
 */
function XMPP_ERROR_shutdown_handler() {
    global $XMPP_ERROR;
    XMPP_ERROR_trace("XMPP_ERROR Status", "Script Shutdown");
    if ($XMPP_ERROR['error']) {
        XMPP_ERROR_error_report($XMPP_ERROR['error']);
    } else if ($XMPP_ERROR['error_manual']) {
        XMPP_ERROR_error_report($XMPP_ERROR['error_manual']);
    }
}

/**
 * Check if the current error should be reported or skipped
 *
 * @param type $err_no
 * @param type $path
 * @return boolean
 */
function XMPP_ERROR_filter($err_no, $path) {
    global $XMPP_ERROR;
    if ($XMPP_ERROR['config']['self_track']) {
        XMPP_ERROR_trace(__FUNCTION__, func_get_args());
    }
    $ignore_errors = $XMPP_ERROR['config']['ignore_type'];
    $ignore_path = $XMPP_ERROR['config']['ignore_warnings'];
    if (in_array($err_no, $ignore_errors)) {
        return false;
    } else if ($err_no !== 1) {
        foreach ($ignore_path as $ignore_string) {
            if (strpos($path, $ignore_string) != false) {
                return false;
            }
        }
    }
    return true;
}

/**
 * Reformat variabled into HTML-ready and readable text
 *
 * @param type $variable
 * @return string
 */
function XMPP_ERROR_array2text($variable) {
    global $XMPP_ERROR;
    $string = '';

    switch(gettype($variable)) {
        case 'boolean':
            $string .= $variable ? 'true' : 'false';
            break;
        case 'integer':
        case 'double':
            $string .= $variable;
            break;
        case 'resource':
            $string .= '[Resource]';
            break;
        case 'NULL':
            $string .= "NULL";
            break;
        case 'unknown type':
            $string .= '???';
            break;
        case 'string':
            $string .= '"' . nl2br(htmlentities($variable), false) . '"';
            break;
        case 'object':
            $string .= nl2br(var_export($variable, true));
            break;
        case 'array':
            $string .= " <ol>";
            foreach ($variable as $key => $elem){
                $class = '';
                if (strstr($key, 'XMPP')) {
                    $class = "std_error";
                }
                $string .= "<li $class><strong>$key</strong> &rArr; ";
                if (count($elem) == 0) {
                    $elem_string = "array()</li>";
                } else {
                    $elem_string = XMPP_ERROR_array2text($elem) . "</li>";
                }
                $string .= $elem_string;
            }
            $string .= "</ol>";
            break;
    }

    return $string;
}

/**
* Return the time between the start of the script until now
*
* @return   time in seconds
*/
function XMPP_ERROR_ptime() {
    $now = microtime(true);
    $overall = $now - XMPP_ERROR_START_TIME;
    $time_str = number_format($overall, 6, ".", "'") . " sec";
    return $time_str;
}

/**
 *
 * @return type
 */
function XMPP_ERROR_css() {
    $css = file_get_contents(__DIR__ . '/styles.css', FILE_USE_INCLUDE_PATH);
    if (!$css) {
        XMPP_ERROR_trigger("Could not find CSS File");
    }
    $out = "
    <style type=\"text/css\">
        $css
    </style>";
    return $out;
}