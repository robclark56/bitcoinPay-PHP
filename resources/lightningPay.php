<?php
/*
	
lightningPay.php
	Companion file for lightningPay.js

FUNCTIONS
If called with 
	* no POST parameters:
		exits quietly
	* with POST parameters
		Performs backend functions. I.e. querying the lnd instance and passing results back to lightningPay.js
   
SYNTAX:  
	https://your.domain/lightningPay.php[?testnet=1]
	This is typically called in this mode from an eCommerce checkout page
		Method: POST
		Parameters: 
			amount    = amount in local currency. Must be a number (e.g. 80)
			amount_format = as above but formated for displayn(e.g. ' $80.00')
			memo = memo text (e.g. 'Order 42')
			currency = 3 character currency from https://bitpay.com/exchange-rates  (e.g. 'USD', 'EUR', etc)
		Displays:
			webpage for customer to confirm and request payment request
								 
			   
	https://your.domain/lightningPay.php[?testnet=1]
		Method: POST
		Parameters: 
			Action    = 'getinvoice'
			Amount    = xxxxx (satoshis)
			Message   = 'Some Text'
		Returns: JSON {"Invoice":"xxxx","Expiry":"yyyy","r_hash_str":"zzzz"}
			xxxx = LN Payment request
			yyyy = expiry seconds
			zzzz = HEX representation of 32 byte base64 r_hash
						
	https://your.domain/lightningPay.php[?testnet=1]
		Method: POST
		Parameters: 
			Action     = 'invoicesettled'
			r_hash_str = HEX encoded 32 byte version of the Invoice r_hash
		Returns: JSON <Invoice>	

Design:
    [Web Browser]<----HTTP---->[.php,.css,.js]<----HTTP---->[LND]																		   
                                                               /\
                                                               |
                                                               |
    [LN Wallet] --------------------gRPC-----------------------+	


			   
Instructions:
1. LND:
	Make sure 
		* REST is enabled. 
		* Firewalls and/or router port forwarders are updated so that 
		  the REST port is accessable from the web server hosting lightningPay.
	   
2. Convert the invoice.macaroon from your lnd to HEX.
	Linux:    xxd -ps -u -c 1000  /path/to/invoice.macaroon 
	Generic:  http://tomeko.net/online_tools/file_to_hex.php?lang=en   
 
3. Install these files on your webserver. 
	Note: Due to JavaScript security, lightningPay.php must be hosted at the same domain as lightningPay.js
	
	StoreCheckout.php
	lightningPay_conf.php 
	lightningPay.js
	lightningPay.php
	lightningPay.css
	lightningPay_light.css (optional)
	
4. Update the lightningPay_conf.php file.
		
5. Open with browser: 
	https://your.domain/path/StoreCheckout.php
	https://your.domain/path/StoreCheckout.php?testnet=1
 
*/

include "lightningPay_conf.php";

$amount=$_POST['amount'];
$amount_format=$_POST['amount_format'];
$memo=$_POST['memo'];
$currency=$_POST['currency'];
$testnet=$_GET['testnet'];

if($testnet){
   define('LND_PORT',LND_PORT_TESTNET);	
} else {
   define('LND_PORT',LND_PORT_MAINNET);		
}

$json = json_decode(@file_get_contents("https://bitpay.com/api/rates/BTC/$currency"));
if(empty($json)){
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Lightning Pay Error</title></head>
<body><h2>Error</h2>Unable to get Exchange Rate</body>
</html>
<?php
 exit;
} else {
 $ExchRate = max(1,$json->rate);
 $BTC = number_format($_POST['amount'] / $ExchRate,8);
 $satoshi = $BTC * 100000000;
}

switch($_POST['Action']){

 case 'getinvoice':
 	$PR = getPaymentRequest($_POST['Message'],$_POST['Amount'], EXPIRY);
	//Comment out next 1 line if you do not want to receive GetInvoice messages
	lightningPaySendEmail(EMAIL_TO, EMAIL_TO_NAME,'[LightningPay] GetInvoice',print_r($PR,1));
 	
	echo json_encode(array(
		'Invoice'   =>$PR->payment_request,
		'Expiry'    =>EXPIRY,
		'r_hash_str'=>bin2hex(base64_decode($PR->r_hash))
		)
	);
  exit;

 case 'invoicesettled':
  $Invoice = lookupInvoice($_POST['r_hash_str']);  
  if(isset($Invoice->settled) && $Invoice->settled){
	  lightningPaySendEmail(
		EMAIL_TO, EMAIL_TO_NAME,
		"[LightningPay] Invoice Settled: $Invoice->value sat",
		"Memo: $Invoice->memo\nValue: $Invoice->value (sat)"
		);
  }
  echo json_encode($Invoice);
  exit;
 
  default:
   if(empty($_POST['memo'])) exit;

   // fall through to displaying the HTML
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>LightningPay</title>
    <link rel="stylesheet" href="lightningPay.css">
    <!-- <link rel="stylesheet" href="lightningPay_light.css"> -->
    <script async defer src="https://cdn.rawgit.com/kazuhikoarase/qrcode-generator/886a247e/js/qrcode.js"></script>
    <script async defer src="lightningPay.js"></script>
</head>

<body>

<div id="lightningPay" <?php if($_GET['testnet']) echo ' class="testnet"';?>>
 <p id="lightningPayLogo">⚡</p>
 <a>Send a Payment via Lightning</a>
 <div id="lightningPayInputs">
  <input type="text" class="lightningPayInput" value="<?php echo "&#8383 $BTC";?>" disabled><br>
  <input type="text" class="lightningPayInput" value="<?php echo "$currency $amount_format";?>" disabled><br>
  <input type="text" class="lightningPayInput" id="lightningPayMessage" value="<?php echo $_POST['memo']?>" disabled><br>
  <input type="hidden" id="lightningPayAmount" value="<?php echo $satoshi;?>">
  <button class="lightningPayButton" id="lightningPayGetInvoice" onclick="getInvoice()"><?php if($_GET['testnet']) echo '[Testnet] ';?>Get request</button>
  <div>  <a id="lightningPayError"></a> </div>
 </div>
</div>
</body>
</html>


<?php
function lookupInvoice($r_hash_str){
 $lnd_ip              = LND_IP;
 $lnd_port            = LND_PORT;
 $invoice_macaroon_hex= INVOICE_MACAROON_HEX; 
					 
 $ch = curl_init("https://$lnd_ip:$lnd_port/v1/invoice/$r_hash_str");
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Grpc-Metadata-macaroon: $invoice_macaroon_hex"
    ));
 $response = curl_exec($ch);
 curl_close($ch);
 return json_decode($response);
}

function getPaymentRequest($memo='',$satoshi=0,$expiry=EXPIRY){
 $lnd_ip              = LND_IP;
 $lnd_port            = LND_PORT;
 $invoice_macaroon_hex= INVOICE_MACAROON_HEX;
 
 $data = json_encode(array(
			"memo"   => (TESTNET=='true'?'[TESTNET] ':'').$memo,
 			"value"  => "$satoshi",
			"expiry" =>  $expiry
			  )     
                     ); 					 
 $ch = curl_init("https://$lnd_ip:$lnd_port/v1/invoices");
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_POST, 1);
 curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 curl_setopt($ch, CURLOPT_HTTPHEADER, array("Grpc-Metadata-macaroon: $invoice_macaroon_hex"));
 $response = curl_exec($ch);
 curl_close($ch);
 return json_decode($response);
}	

function lightningPaySendEmail($to,$to_name,$subject,$body){
  // By default, this function uses the built in PHP mail() function.
  // If your hosting service does not allow PHP mail(), then PHPMailer may work for you. 
  //   See more info here: https://infinityfree.net/support/how-to-send-email-with-gmail-smtp/
  //   Note: The PHPMailer instructions work with more than just gmail.
  //
  if(empty($to)) return;
  
  if($_GET['testnet']) $subject = "[Testnet] $subject";
  
  if(true){ //false = use PHPMailer
	mail("$to_name <$to>",$subject,$body,"From: ".EMAIL_FROM_NAME." <".EMAIL_FROM.">");  
  } else {
	date_default_timezone_set('Australia/Perth');
	require '../PHPMailer/PHPMailerAutoload.php';
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->Host       = 'CHANGE_ME'; // Which SMTP server to use.
	$mail->Port       = CHANGE_ME; 	 // Which port to use, 587 is the default port for TLS security.
	$mail->SMTPSecure = 'tls'; // Which security method to use. TLS is most secure.	$mail->SMTPAuth   = true;  // Whether you need to login. This is almost always required.
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
