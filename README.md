XMPP_ERROR
==========

A lightweight PHP error managemnt tool that reports errors to you via XMPP/Jabber

REQUIREMENTS: 
* JAXL: http://jaxl.readthedocs.org/en/latest/
  This library will allow PHP to send XMPP/Jabber messages
* 2 Jabber accounts: One for the sending PHP script (server), one for the 
  receiving user or admin (client)
* A writable directory on your webserver where error reports are stored. Those
  are attached to the XMPP messages since they are too large to be sent. For 
  security reasons, you can make this directory password protected via .htaccess

FEATURES:
* Sending error messages of any level (error, warning, notice etc)
* Excluding certain errors or files from generating a message
* Tracking of functions and their arguments or variables throughout the script 
  to identify procedure paths taken and variable changes
* Millisecond-timing of all steps
* In-process errors from the start of the script until shutdown

INSTALLATION:
* Setup the required 2 XMPP accounts on your XMPP server
* Setup an XMPP client to receive messages for the admin XMPP account
* Configure config.default.php as required
* Rename config.default.php to config.php
* Test the setup by running test.php
* Include the file xmpp_error.php in your project, possibly at first.

TODO:
* Better config variable naming
* Test and enable error message archiving with .gz
* Add CSS for the error messages
* Allow for several XMPP Client accounts