#!/usr/bin/perl
use strict;
use Net::XMPP;
my ($server, $host, $user, $password, $recip, $msg) = @ARGV;
if(! $recip || ! $msg) {
    print 'Syntax: $0 <recipient> <message>\n';
    exit;
}
my $con = new Net::XMPP::Client(
    debuglevel=>0,
    

);
my $status = $con->Connect(
    hostname => $server,
    connectiontype => 'tcpip',
    tls => 1
);
die('ERROR: XMPP connection failed') if ! defined($status);
my @result = $con->AuthSend(
    hostname => $host,
    username => $user,
    password => $password);
die('ERROR: XMPP authentication failed') if $result[0] ne 'ok';
die('ERROR: XMPP message failed')
    if ($con->MessageSend(to => $recip, body => $msg) != 0);
print 'Success!\n';