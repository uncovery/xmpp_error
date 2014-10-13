<?php

// description to be inserted into the error reports
// This should be added in case several projects use this library to report errors
$XMPP_ERROR['config']['project_name'] = 'My Website';

// currently, XMPP_ERROR only supports JAXL as a xmpp sending module
// no need to change this
$XMPP_ERROR['config']['xmpp_lib_name'] = 'JAXL';
// enter the username of the sending (server) XMPP account here
// path to the folder where jaxl.php is located. No trailing slash
// this needs to include /xmpp/xmpp_msg.php
$XMPP_ERROR['config']['xmpp_lib_path'] = '/home/my_website/includes/jaxl';

$XMPP_ERROR['config']['xmpp_sender_username'] = 'sending-account@google.com';
// enter the password for the above account here
$XMPP_ERROR['config']['xmpp_sender_password'] = 'my_password';
// enter the AUTH method for the above account here. Possible varibales are from
// JAXL docs: http://jaxl.readthedocs.org/en/latest/users/jaxl_instance.html
// DIGEST-MD5, PLAIN (default), CRAM-MD5, ANONYMOUS, X-FACEBOOK-PLATFORM
$XMPP_ERROR['config']['xmpp_sender_auth-type'] = 'DIGEST-MD5';

// recipient (client) XMPP account username
$XMPP_ERROR['config']['xmpp_recipient'] = 'recipient-account@google.com';

// other variables to include in the report (strip the $!)
// the contents of these variables will be printed as of their status at script
// shutdown
$XMPP_ERROR['config']['track_globals'] = array('MY_GLOBAL', 'OTHER_GLOBAL');

// if you want to the error report time stampts match your current timezone,
// set this variable to a valid Unix Timezone
// (https://en.wikipedia.org/wiki/List_of_tz_database_time_zones)
// otherwise set to FALSE
$XMPP_ERROR['config']['reports_timezone'] = 'Asia/Hong_Kong';

// IF you want to ignore errors of certain files, please add a substring of the
// pato to this array
$XMPP_ERROR['config']['ignore_warnings'] = array('jaxl');

// If you want to ignore certain error types, please add the error numbers to
// this array.
// For the full list see http://hk1.php.net/manual/en/errorfunc.constants.php
// (use the "Value" in the first column)
$XMPP_ERROR['config']['ignore_type'] = array(8192);

// path to where the error reports will be stored. The script will create
// year/month/day/hour subfolders. This path has to be reachable via URL
// no trailing slash
$XMPP_ERROR['config']['reports_path'] = '/home/my_website/public_html/errors';

// URL where the above files can be reached
// no trailing slash
$XMPP_ERROR['config']['reports_url'] = 'http://my_website.net/errors';

// HTML header for the error reports
$XMPP_ERROR['config']['reports_header'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <title>[' . $XMPP_ERROR['config']['project_name'] . '] XMPP ERROR Report</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    </head>
    <body>';

// HTML footer for the error reports
$XMPP_ERROR['config']['reports_footer'] = '    </body>
</html>';