;# DO NOT UNDER ANY CIRCUMSTANCES REMOVE THE BELOW LINES! #
<?php
die;
?>
;# OK, YOU CAN START EDITING NOW. #

[Core]
; Modes: 0=production 1=testing
mode = 2

[BitCoin]
host = occult.xf-liam.com ; Bitcoin host, IPv6 allowed if supported by system.
rpcuser = bitcoinrpc; Bitcoin rpc user, as defined in bitcoin.conf
rpcpassword = 88FF6dUNtmVfxzX4xJV3sEsvMYaRW4DpZ19MWhvun42z; Bitcoin rpc password, as defined in bitcoin.conf
rpcssl = 0 ; Boolean value. If true, connects to RPC using SSL. (This is discouraged and will trigger a warning if enabled.)

[FrontEnd]
title = Murder Bitcoin Node
donateaddress = 1GezsstKExB7DWKhhiPtxXgWA7BXRSfsGr
friendlyurl = 0
resolvehostname = 1
extrajs = ; Any extra header js, such as tracking code
