<?php

/*
 bitcoinPay_conf.php
 
 Configuration values for bitcoinPay
 
 See https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md

*/
// eStore Name
define('ESTORE_NAME' ,'My eStore');

// Timout Values. For details, see https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md#monitoring-for-payments 
define('EXPIRY_SECONDS' ,'900');	//e.g. 15 mins	
define('MINE_SECONDS'   ,'10800');	//e.g. 3 hours. Should be larger than EXPIRY_SECONDS		

// The WORKING_DIRECTORY is required in cron mode. Set it to the full disk path to the location of the bitcoinPay files
//  e.g.  '/home/user/public_html/bitcoinPay'
define('WORKING_DIRECTORY','/home/user/public_html/bitcoinPay');	//Used in cron mode.

// Extended Public Keys
// See https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md#1-get-your-xpub--tpub
//  One line per wallet
//  No limit to number of wallets, but xpub for DEFAULT_WALLET must exist
$xpub['wallet_mainnet'] = 'xpub.....1Dwb';
$xpub['wallet_testnet'] = 'tpub.....7RkP';

// The name of the default wallet.
define('DEFAULT_WALLET'   ,'wallet_testnet');

// Default Currency. 
// 3 character code from https://en.wikipedia.org/wiki/ISO_4217
// Used for Manual Input mode
define('DEFAULT_CURRENCY','USD');

//Default Callback
// Used for Manual Input mode
define('DEFAULT_CALLBACK','https://my.estore.com/bitcoinPay/StoreCallback.php');

// eStore PrivateKey. Used to sign callback messages to the eStore, after payment has been confirmed.
// See https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md#2-generate-privatepublic-key-pair
define('ESTORE_PRIV_KEY',
'-----BEGIN RSA PRIVATE KEY-----
[... lines deleted ...]
-----END RSA PRIVATE KEY-----'

);

// Database values
// See https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md#3-create-sql-database
define('DB_USER','xxxxx');
define('DB_PASS','xxxxx');	
define('DB_HOST','xxxxx');			
define('DB_BASE','xxxxx');		  

//Optional EMAIL notifications
// To disable, leave EMAIL_TO as '' (empty string)
define('EMAIL_TO'       ,'me@my.estore.com'); 	//e.g. 'me@my.estore.com' Empty string disables emails
define('EMAIL_TO_NAME'  ,'bitcoinPay'); 	      //e.g. 'My Name'
define('EMAIL_FROM'     ,'me@my.estore.com'); 	//e.g. 'me@my.estore.com'
define('EMAIL_FROM_NAME','bitcoinPay');

?>
