<?php
define('LND_IP','CHANGE_ME'); //IP address or FQDN (domain name) of lnd server
define('LND_PORT_MAINNET','CHANGE_ME');
define('LND_PORT_TESTNET','CHANGE_ME');  	//Optional
//No spaces in INVOICE_MACAROON_HEX
define('INVOICE_MACAROON_HEX', 'CHANGE_ME'); 
define('EXPIRY','300');			//seconds

//Optional EMAIL notifications
// To disable, leave EMAIL_TO as '' (empty string)
define('EMAIL_TO'       ,'CHANGE_ME'); //e.g. 'me@my.domain' Empty string disables emails
define('EMAIL_TO_NAME'  ,'LightningPay'); //e.g. 'My Name'
define('EMAIL_FROM'     ,'CHANGE_ME'); //e.g. 'me@my.domain'
define('EMAIL_FROM_NAME','LightningPay');
?>
