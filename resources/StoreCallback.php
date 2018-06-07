<?php

/*
 StoreCallback.php
 
 Example file used to notify on online store that a Bitcoin payment request has been confirmed
 
 Typically called by bitcoinPay.php as https://my.estore.com/bitcoinPay/StoreCallback.php 
  with these POST values  (testnet values shown in this example):
  
    [status] => fullyPaid
    [data] => Array(
            [id] => 54
            [address] => mgjQF......AyEASgW
            [BTC] => 0.01060340
            [memo] => Order 42
            [currency] => USD
            [amount] => 80
            [minConfirmations] => 5
            [callback] => https://my.estore.com/bitcoinPay/StoreCallback.php
            [btc] => 0.01060340
            [confirmations] => 6
            [txid] => 34859e48e7106.....1d36788f5708a9
            [first_seen_gmt] => 2018-06-01 06:43:03
            [settled] => 1
            )
    [hash] => 19e2d328f20701c3....2b90a8316f47b26d
 
 
 Security:
 To counter man-in-the-middle attacks, the [hash] must be verfied as ([data][address], hashed with PRIVATE KEY).
 
*/

// --- START CHANGE_ME ------------------------------------------

define('EMAIL_TO'       ,'me@my.estore.com');  //Leave blank to disable email notification
define('EMAIL_TO_NAME'  ,'Manager');
define('EMAIL_FROM'     ,'me@my.estore.com');
define('EMAIL_FROM_NAME','eStore Callback');

$eStorePubKey = 
'-----BEGIN PUBLIC KEY-----
[... lines removed ...]
-----END PUBLIC KEY-----'
;

// ---- END CHANGE_ME --------------------------------------------


// Decrypt hash
openssl_public_decrypt(hex2bin($_POST['hash']), $decrypt_hash, $eStorePubKey);

// Check and process
if($_POST['data']['address'] && $decrypt_hash === $_POST['data']['address']){
 switch($_POST['status']){
  case 'fullyPaid':
   $message = $_POST['status'];
   //Add code here to process fully paid order
   break;
   
  case 'underPaid':
   $message = $_POST['status'];
   //Add code here to process under-paid order
   break;
  
  default:
   $message = 'Unknown status:'.$_POST['status'];
 }
} else {
 $message = 'Hacking Attempt???';
}

// Notify
bitcoinPaySendEmail(EMAIL_TO,EMAIL_TO_NAME,__FILE__,"$message\n\nPOST:".print_r($_POST,1));

//////////////////////////////////////////////////////////////////////////////

function bitcoinPaySendEmail($to,$to_name,$subject,$body){
  // By default, this function uses the built in PHP mail() function.
  // If your hosting service does not allow PHP mail(), then PHPMailer may work for you. 
  //   See more info here: https://infinityfree.net/support/how-to-send-email-with-gmail-smtp/
  //   Note: The PHPMailer instructions work with more than just gmail.
  //
  if(empty($to)) return;
  
  if(true){ //false = use PHPMailer
	mail("$to_name <$to>",$subject,$body,"From: ".EMAIL_FROM_NAME." <".EMAIL_FROM.">");  
  } else {
	date_default_timezone_set('CHANGE_ME'); 	//eg 'Australia/Perth'
	require '../PHPMailer/PHPMailerAutoload.php';	//CHANGE_ME if needed
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host       = 'CHANGE_ME'; // Which SMTP server to use.
	$mail->Port       = CHANGE_ME; 	 // Which port to use, 587 is the default port for TLS security.
	$mail->SMTPSecure = 'tls'; // Which security method to use. TLS is most secure.	
	$mail->SMTPAuth   = true;  // Whether you need to login. This is almost always required.
	$mail->Username   = 'CHANGE_ME'; 
	$mail->Password   = 'CHANGE_ME'; 
	$mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME); 
	$mail->addAddress($to, $name); 
	$mail->Subject = $subject; 
	$mail->Body = $body; 
	$mail->send();
  }	  
}

?>
