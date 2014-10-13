<?php

$XMPP_ERROR['config']['project_name'] = 'My Website';

// currently, XMPP_ERROR only supports JAXL as a xmpp sending module
$XMPP_ERROR['config']['name'] = 'JAXL';
$XMPP_ERROR['config']['username'] = 'sending-account@google.com';
$XMPP_ERROR['config']['password'] = 'my_password';
$XMPP_ERROR['config']['auth-type'] = 'DIGEST-MD5';
$XMPP_ERROR['config']['recipient'] = 'recipient-account@google.com';
// path to the folder where jaxl.php is located. No trailing slash
$XMPP_ERROR['config']['path'] = '/home/my_website/includes/jaxl';

// other variables to include in the report (strip the $!)
$XMPP_ERROR['config']['track_globals'] = array('MY_GLOBAL', 'OTHER_GLOBAL');

// no trailing slash
$XMPP_ERROR['config']['file_path'] = '/home/my_website/public_html/errors';
// no trailing slash
$XMPP_ERROR['config']['url'] = 'http://my_website.net/errors';

$XMPP_ERROR['config']['timezone'] = 'Asia/Hong_Kong';

$XMPP_ERROR['config']['ignore_warnings'] = array('jaxl');
$XMPP_ERROR['config']['ignore_type'] = array(8192);

$XMPP_ERROR['config']['header'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <title>[' . $XMPP_ERROR['config']['project_name'] . '] XMPP ERROR Report</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    </head>
    <body>';

$XMPP_ERROR['config']['footer'] = '    </body>
</html>';