<?php

/*
 bitcoinPay_conf.php
 
 Configuration values for bitcoinPay
 
 See https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md

*/

// Timout Values. For details, see https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md#monitoring-for-payments 
define('EXPIRY_SECONDS' ,'900');	//e.g. 15 mins	
define('MINE_SECONDS'   ,'10800');	//e.g. 3 hours. Should be larger than EXPIRY_SECONDS		

// The WORKING_DIRECTORY is required in cron mode. Set it to the full disk path to the location of the bitcoinPay files
//  e.g.  '/home/user/public_html/bitcoinPay'
define('WORKING_DIRECTORY','/home/user/public_html/bitcoinPay');	//Used in cron mode.

// The name of the default wallet.
// e.g. 'wallet_testnet'  => 'wallet_testnet.php' contains the wallet definition
define('DEFAULT_WALLET'   ,'wallet_testnet');	

// eStore PrivateKey. Used to sign callback messages to the eStore, after payment has been confirmed.
// See https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md#1-generate-privatepublic-key-pair
define('ESTORE_PRIV_KEY',
'-----BEGIN RSA PRIVATE KEY-----
[... lines deleted ...]
-----END RSA PRIVATE KEY-----'

);

// Database values
// See https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md#2-create-sql-database
define('DB_USER','xxxxx');
define('DB_PASS','xxxxx');	
define('DB_HOST','xxxxx');			
define('DB_BASE','xxxxx');		  

//Optional EMAIL notifications
// To disable, leave EMAIL_TO as '' (empty string)
define('EMAIL_TO'       ,'me@my.estore.com'); 	//e.g. 'me@me@my.estore.com' Empty string disables emails
define('EMAIL_TO_NAME'  ,'bitcoinPay'); 	      //e.g. 'My Name'
define('EMAIL_FROM'     ,'me@my.estore.com'); 	//e.g. 'me@me@my.estore.com'
define('EMAIL_FROM_NAME','bitcoinPay');

?>
