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

/**
 * This file is used to test your XMPP_ERROR Config. It's usable as-is, no
 * changes required. You can modify it to add different error scenarios to
 * better understand XMPP_ERROR behavior.
 */
echo "This is the xmpp_error test page<br>";

require_once('xmpp_error.php');

// test if JAXL Messages can be sent:
XMPP_ERROR_send_msg("This is a test XMPP Message");

// testing a trace
testing_trace(1,2,3);

// testing a notice for an non-initialized variable
echo "test";

// testing a manual trigger
testing_trigger();

echo "Test page fully loaded";

/**
 * Testing a trace in a function.
 * This should show up in the logfile with the arguments
 *
 * @param type $a
 * @param type $b
 * @param type $c
 */
function testing_trace($a, $b, $c) {
    XMPP_ERROR_trace(__FUNCTION__, func_get_args());
}

/**
 * Manually trigger an error
 */
function testing_trigger() {
    $text = "This is a manually triggered error";
    XMPP_ERROR_trigger($text);
}

