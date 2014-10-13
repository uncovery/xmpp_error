XMPP_ERROR
==========

A lightweight PHP error management tool that reports errors to you via XMPP/Jabber

#### Requirements: 
* JAXL: http://jaxl.readthedocs.org/en/latest/
  This library will allow PHP to send XMPP/Jabber messages
* 2 Jabber accounts: One for the sending PHP script (server), one for the 
  receiving user or admin (client)
* A writable directory on your webserver where error reports are stored. Those
  are attached to the XMPP messages since they are too large to be sent. For 
  security reasons, you can make this directory password protected via .htaccess

#### Features:
* Sending error messages of any level (error, warning, notice etc)
* Excluding certain error types or files from generating a message
* Tracking of functions and their arguments or variables throughout the script 
  to identify procedure paths taken and variable changes
* Millisecond-timing of all steps
* In-process errors from the start of the script until shutdown
* XMPP status messages outside of error reports to the client
* Multiple recipients for messages

#### Installation:
* Setup the required 2 XMPP accounts on your XMPP server
* Setup an XMPP client to receive messages for the admin XMPP account
* Configure config.default.php as required
* Rename config.default.php to config.php
* Test the setup by running test.php . If you run this from the command line,
  make sure you run it as a user that can write files to the 
  $XMPP_ERROR['config']['path'] folder. If successful, you should get 2 XMPP 
  messages with a timestamp: 
  * This is a test XMPP Message
  * E_NOTICE: Undefined variable: test in line 16 of file /xmpp_error/test.php
  This last message should have a link attached to a HTML file that contains the
  $XMPP_ERROR error report with 3 elements
* Include the file xmpp_error.php in your project, possibly at first.

#### Usage:
* After installation is complete, errors should generate reports in the
  configured folder and send XMPP messages to the configured recipient
* Further, as from the examples in test.php, one can include tracer calls at the 
  beginning of any function to include their names and arguments into the error 
  report. The \_\_FUNCTION\_\_ will set the current function name, and 
  func_get_args() will insert the arguments into the error report. 
  You can use the same function with any other arguments (preferably a 
  description for the first, and a variables for the second to insert a trace 
  for any variables in script.
```php
    function sample_function($a, $b, $c) {
        XMPP_ERROR_trace(__FUNCTION__, func_get_args());
    }
```
* Even further, one can trigger an error report on specific parts of the script.
  This is specially helpful if certain conditions of the script should not be 
  met under any "healthy" conditions:
```php
    function sample_function2($value) {
        if ($value == "good_value_1") {
            // perform action
        } else {
            XMPP_ERROR_trigger("Value has unexpected contents: $value");
        }
```
* Simpler, one can trigger notifications to the admin for not-so-often occuring
  actions to be aware about general activity of the project. This is not an
  error report, but rather a status message.
```php
    function sample_function_user_registration() {
        XMPP_ERROR_send_msg("A new user has registered on the site!");
    }
```

#### ToDo:
* Add CSS for the error messages