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

// description to be inserted into the error reports
// This should be added in case several projects use this library to report errors
$XMPP_ERROR['config']['project_name'] = 'My Website';

// shall this library track it's own error in the error reports?
// I would enable this only if the test.php works fine.
// TRUE or FALSE
$XMPP_ERROR['config']['self_track'] = false;

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
// add more array elements for several recipients
$XMPP_ERROR['config']['xmpp_recipient'] = array('recipient1@google.com');

// other variables to include in the report (strip the $!)
// the contents of these variables will be printed as of their status at script
// shutdown
$XMPP_ERROR['config']['track_globals'] = array('MY_GLOBAL', 'OTHER_GLOBAL');

// track if the same function was called with the same arguments more than once
$XMPP_ERROR['config']['track_doublecalls'] = false;

// if you want to the error report time stampts match your current timezone,
// set this variable to a valid Unix Timezone
// (https://en.wikipedia.org/wiki/List_of_tz_database_time_zones)
// otherwise set to FALSE
$XMPP_ERROR['config']['reports_timezone'] = 'Asia/Hong_Kong';

// If you want to ignore errors of certain files, please add a substring of the
// path to this array; Error level 1 (E_ERROR) will still be reported
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

// Archive limit for reports; This will create daily archives of errors in the
// above path. Should be in the format of relative dates as found in
// http://php.net/manual/en/datetime.formats.relative.php
// it will pick all errors from the resulting day and archive them.
// set to FALSE if you want to disable archiving and use logrotate instead
$XMPP_ERROR['config']['reports_archive_date'] = "7 days ago";

// URL where the above files can be reached
// no trailing slash
$XMPP_ERROR['config']['reports_url'] = 'http://my_website.net/errors';