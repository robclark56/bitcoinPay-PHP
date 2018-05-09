<?php
/*
///////////////////////////////////////////////////////////////
CREDIT
 This extends the excellent work done by michael1011 at 
    https://github.com/michael1011/lightningtip
///////////////////////////////////////////////////////////////
	
lightningTip.php
	Companion file for lightningTip.js

FUNCTIONS
If called with 
	* no POST parameters:
		Displays the lightningTip HTML page
	* with POST parameters
		Performs backend functions. I.e. querying the lnd instance and passing results back to lightningTip.js
   
SYNTAX:  
	https://your.domain/lightningTip.php[?testnet=1]
		Displays lightningTip HTML
		The backend queries:
			* the mainnet lnd if testnet is not set as a GET parameter 
			* the testnet lnd if the testnet GET paramenter is set and non-zero			 
			   
	https://your.domain/lightningTip.php[?testnet=1]
		Method: POST
		Parameters: 
			Action    = 'getinvoice'
			Amount    = xxxxx (satoshis)
			Message   = 'Some Text'
		Returns: JSON {"Invoice":"xxxx","Expiry":"yyyy","r_hash_str":"zzzz"}
			xxxx = LN Payment request
			yyyy = expiry seconds
			zzzz = HEX representation of 32 byte base64 r_hash
						
	https://your.domain/lightningTip.php[?testnet=1]
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
		  the REST port is accessable from the web server hosting lightningTip.
	   
2. Convert the invoice.macaroon from your lnd to HEX.
	Linux:    xxd -ps -u -c 1000  /path/to/invoice.macaroon 
	Generic:  http://tomeko.net/online_tools/file_to_hex.php?lang=en   
 
3. Update the CHANGE_ME section below.
 
4. Install these files on your webserver. 
	Note: Due to JavaScript security, lightningTip.php must be hosted at the same domain as lightningTip.js
	
	lightningTip.js
	lightningTip.php
	lightningTip.css
	lightningTip_light.css (optional)
		
5. Open with browser: 
	https://your.domain/path/lightningTip.php
	https://your.domain/path/lightningTip.php?testnet=1
 
*/

////////  CHANGE ME SECTION ///////
define('LND_IP','CHANGE_ME'); //IP address or FQDN (domain name) of lnd server
define('LND_PORT_MAINNET','CHANGE_ME');
define('LND_PORT_TESTNET','CHANGE_ME');  	//Optional
define('INVOICE_MACAROON_HEX',			//No spaces
       'CHANGE_ME'
	   );
define('EXPIRY','1800');			//seconds

//Optional EMAIL notifications
// To disable, leave EMAIL_TO as '' (empty string)
define('EMAIL_TO'       ,'CHANGE_ME'); //e.g. 'me@my.domain' Empty string disables emails
define('EMAIL_TO_NAME'  ,'CHANGE_ME'); //e.g. 'My Name'
define('EMAIL_FROM'     ,'CHANGE_ME'); //e.g. 'me@my.domain'
define('EMAIL_FROM_NAME','LightningTip');

////////  END CHANGE ME SECTION ///////


if($_GET['testnet']){
   define('LND_PORT',LND_PORT_TESTNET);	
} else {
   define('LND_PORT',LND_PORT_MAINNET);		
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
 curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Grpc-Metadata-macaroon: $invoice_macaroon_hex"
    ));
 $response = curl_exec($ch);
 curl_close($ch);
 return json_decode($response);
}	

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

switch($_POST['Action']){

 case 'getinvoice':
 	$PR = getPaymentRequest($_POST['Message'],$_POST['Amount'], EXPIRY);
	//Comment out next 1 line if you do not want to receive GetInvoice messages
	lightningTipSendEmail(EMAIL_TO, EMAIL_TO_NAME,'[LightningTip] GetInvoice',print_r($PR,1));
 	
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
	  lightningTipSendEmail(
		EMAIL_TO, EMAIL_TO_NAME,
		'[LightningTip] Invoice Settled: '.$Invoice->value. ' sat',
		"Memo: $Invoice->memo\nValue: $Invoice->value (sat)"
		);
  }
  echo json_encode($Invoice);
  exit;
 
  default:
   // fall through to displaying the HTML
}
?>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="lightningTip.css">
    <script async defer src="https://cdn.rawgit.com/kazuhikoarase/qrcode-generator/886a247e/js/qrcode.js"></script>
    <script async defer src="lightningTip.js"></script>
</head>

<div id="lightningTip" <?php if($_GET['testnet']) echo ' class="testnet"';?>>
    <p id="lightningTipLogo">⚡</p>
    <a>Send a tip via Lightning</a>

    <div id="lightningTipInputs">
        <input type="number" class="lightningTipInput" id="lightningTipAmount" placeholder="Amount in satoshi">
        <br>
        <div class="lightningTipInput" id="lightningTipMessage" placeholder="A message you want to add" oninput="divRestorePlaceholder(this)" onblur="divRestorePlaceholder(this)" contenteditable></div>

        <button class="lightningTipButton" id="lightningTipGetInvoice" onclick="getInvoice()"><?php if($_GET['testnet']) echo '[Testnet] ';?>Get request</button>

        <div>
            <a id="lightningTipError"></a>
        </div>

    </div>

</div>

<?php
function lightningTipSendEmail($to,$to_name,$subject,$body){
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
