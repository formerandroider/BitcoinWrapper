;# DO NOT UNDER ANY CIRCUMSTANCES REMOVE THE BELOW LINES! #
<?php
die;
?>
;# OK, YOU CAN START EDITING NOW. #

[Core]
; Modes: 0=production 1=testing
mode = 2

[BitCoin]
host = 127.0.0.1 ; Bitcoin host, IPv6 allowed if supported by system.
rpcuser = ; Bitcoin rpc user, as defined in bitcoin.conf
rpcpassword = ; Bitcoin rpc password, as defined in bitcoin.conf
rpcssl = 1 ; Boolean value. If true, connects to RPC using SSL

[FrontEnd]
title = Murder Bitcoin Node
donateaddress =
friendlyurl = 0
resolvehostname = 1
resolver = 192.168.43.1
resolvetimeout = 10