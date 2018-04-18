<?php

/*
 * Copyright (C) 2017 uncovery
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

function xmpp_lib_sendxmpp($message) {
    global $XMPP_ERROR;
    $conf = $XMPP_ERROR['config']['xmpp_lib']['sendxmpp'];
    $username = $conf['username'];
    $password = $conf['password'];
    $server = $conf['server'];
    $msg_save = addslashes($message);

    foreach ($XMPP_ERROR['config']['xmpp_recipient'] as $rcpt) {
        $command = "echo \"$msg_save\" | sendxmpp -t -n -u $username -j $server -p $password $rcpt";
        exec($command);
    }
}

function xmpp_lib_xmpp_perl($message) {
    global $XMPP_ERROR;
    $conf = $XMPP_ERROR['config']['xmpp_lib']['sendxmpp'];
    $username = $conf['username'];
    $password = $conf['password'];
    $server = $conf['server'];
    $msg_save = addslashes($message);

    $path = 'perl ' . __DIR__ . "/xmpp.pl";

    foreach ($XMPP_ERROR['config']['xmpp_recipient'] as $rcpt) {
        $recipient_split = explode("@", $rcpt);
        // $target_user = $recipient_split[0];
        $target_host = $recipient_split[1];

        $command = "$path $server $server $username $password $rcpt '$msg_save'";
        exec($command);
    }
}

