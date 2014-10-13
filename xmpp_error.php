<?php
global $XMPP_ERROR;
require_once('config.php');

// this variable tracks if an actual error occured and is set in XMPP_ERROR_handler
$XMPP_ERROR['error'] = false;
// this variable tracks if a manually triggered error and is set in XMPP_ERROR_handler
$XMPP_ERROR['error_manual'] = false;

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

// XMPP_ERROR_trace(__FUNCTION__, func_get_args());

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
function XMPP_ERROR_trace($type, $data) {
    global $XMPP_ERROR;
    // insert the current time and passed variables
    $XMPP_ERROR[XMPP_ERROR_ptime()][][$type] = $data;
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
    $now = $date_obj->format('Y-m-d_H:i:s') . substr((string)microtime(), 1, 8) . "_" . rand(0, 9999999999999);

    // path to store the attached message
    $path = $XMPP_ERROR['config']['reports_path'] . "/$year/$month/$day/$hour/";
    // url to reach the message
    $url = $XMPP_ERROR['config']['reports_url'] . "/$year/$month/$day/$hour/";
    // final filename
    $file = "Error_$now.html";
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
        XMPP_ERROR_archive();
    }

    // add the configured header for the attached message
    $msg_text = $XMPP_ERROR['config']['reports_header'];
    // initialize the variable
    $main_error = '';

    // we have a main error that triggered the message
    $msg_text .= "<div class=\"main\"><h1>Main Error</h1>\n";
    // in case the main error is an array, print it nicely
    if (is_array($error)) {
        foreach ($error as $title => $text) {
            $msg_text .= "<div class=\"main_sub\"><h2>$title:</h2>\n" . XMPP_ERROR_array2text($text) . "<br>\n";
        }
    } else if (is_object($error)) {
        $main_error .= "Multiple Variables";
        $msg_text .= var_export($error, true);
    } else {
        $msg_text .= "$error";
        $main_error .= $error;
    }
    $msg_text .= "</div>\n";


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
        $msg_text .= "<div class=\"others\"><h2>$title:</h2>\n" . XMPP_ERROR_array2text($text) . "</div>\n";
    }

    // now add the configured footer
    $msg_text .= $XMPP_ERROR['config']['reports_footer'];

    // write the whole thing to a file
    $check = file_put_contents($path . $file, $msg_text);
    // check if it worked
    if (!$check) {
        die("could not write error file to path $path, please check permissions");
    }
    // send the message with the URL to the attachement to XMPP client
    XMPP_ERROR_send_msg("$main_error at $url$file");
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
    $error_types = array(
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
    );

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
    $referer = 'n/a';
    $s_server = filter_input_array(INPUT_SERVER, FILTER_SANITIZE_STRING);
    if (isset($s_server['HTTP_REFERER'])) {
        $referer = $s_server['HTTP_REFERER'];
    }
    // format the actual error message
    $text = "$time $errortype: $errstr in line $errline of file $errfile, referer $referer";
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
    XMPP_ERROR_trace("shutdown", "shutdown");
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
    $ignore_errors = $XMPP_ERROR['config']['ignore_type'];
    $ignore_path = $XMPP_ERROR['config']['ignore_warnings'];
    if (in_array($err_no, $ignore_errors)) {
        return false;
    } else if ($err_no == 8) {
        foreach ($ignore_path as $ignore_string) {
            if (strpos($path, $ignore_string) != false) {
                return false;
            }
        }
    }
    return true;
}

/**
 * zip messages more than one month old
 * this still needs to be tested.
 *
 * @global array $XMPP_ERROR
 */
function XMPP_ERROR_archive() {
    global $XMPP_ERROR;
    // get the relative day
    $date_obj = new DateTime($XMPP_ERROR['config']['reports_archive_date']);
    $year = $date_obj->format('Y');
    $month = $date_obj->format('m');
    $day = $date_obj->format('d');

    // create paths
    $day_path = $XMPP_ERROR['config']['reports_path'] . "/$year/$month/$day";
    $archive_file = $XMPP_ERROR['config']['reports_path'] . "/XMPP_ERRROR_archive-$year-$month-$day.zip";

    // check if there is anything to archive
    if (file_exists($day_path)) {
        // archive the folder
        $zip_check = XMPP_ERROR_zipTree($day_path, $archive_file);
        if (!$zip_check && $XMPP_ERROR['config']['self_track']) {
             XMPP_ERROR_trigger("Archive $archive_file failed");
        }
        // delete archived files by removing the directory of that day
        $rm_check = XMPP_ERROR_delTree($day_path);
        if (!$rm_check && $XMPP_ERROR['config']['self_track']) {
            XMPP_ERROR_trace("Archive remove $day_path failed");
        }
    } else {
        XMPP_ERROR_trace("No archive created", "PAth $day_path does not exist");
    }
}

/**
 * Recursive deletion of non-empty directories
 * source: http://php.net/manual/en/function.rmdir.php
 * @param string $dir
 */
function XMPP_ERROR_delTree($dir){
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
        if (is_dir("$dir/$file")) {
            XMPP_ERROR_delTree("$dir/$file");
        } else {
            unlink("$dir/$file");
        }
    }
    return rmdir($dir);
}

/**
 * Zip a folder
 * Source: http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php
 * 
 * @global type $XMPP_ERROR
 * @param type $source
 * @param type $destination
 * @return boolean
 */
function XMPP_ERROR_zipTree($source, $destination) {
    global $XMPP_ERROR;
    if ($XMPP_ERROR['config']['self_track']) {
        XMPP_ERROR_trace(__FUNCTION__, func_get_args());
    }
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }

    $real_source = str_replace('\\', '/', realpath($source));
    if (is_dir($real_source) === true) {
        $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($real_source), 
                RecursiveIteratorIterator::SELF_FIRST);
        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);
            // Ignore "." and ".." folders
            if (in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) ) {
                continue;
            }
            $file = realpath($file);
            if (is_dir($file) === true) {
                $zip->addEmptyDir(str_replace($real_source . '/', '', $file . '/'));
            } else if (is_file($file) === true) {
                $zip->addFromString(str_replace($real_source . '/', '', $file), file_get_contents($file));
            }
        }
    } else if (is_file($real_source) === true) {
        $zip->addFromString(basename($real_source), file_get_contents($real_source));
    }

    return $zip->close();
}

/**
 * Reformat variabled into HTML-ready and readable text
 *
 * @param type $data
 * @return string
 */
function XMPP_ERROR_array2text($data) {
    $type = strtoupper(gettype($data));

    $out = "$type: ";
    if (is_bool($data)) {
        if ($data) {
            return $out . "TRUE";
        } else {
            return $out . "FALSE";
        }
    } if (is_object($data)) {
        return $out . var_export($data, true);
    } else if (is_array($data)) {
        $out .= "(" . count($data) . ")<ul>\n";
        foreach ($data as $key => $value) {
            if (!is_numeric($key)) {
                $key = "'$key'";
            }
            $out .="<li><strong>$key</strong> => "
                . XMPP_ERROR_array2text($value)
                . "</li>\n";
        }
        $out .= "</ul>";
        return $out;
    } else {
        return $out . $data;
    }
}

/**
* Return the time between the start of the script until now
*
* @return   time in seconds
*/
function XMPP_ERROR_ptime() {
    $now = microtime(true);
    $overall = $now - XMPP_ERROR_START_TIME;
    $time_str = number_format($overall, 3, ".", "'") . " sec";
    return $time_str;
}