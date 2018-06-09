<?php
/*
bitcoinPay.php
	See https://github.com/robclark56/bitcoinPay-PHP

FUNCTIONS
This file performs a number of different fuctions, depending on how it is called.

	* [Mode 2] 
		Checks blockchain to see if unpaid payment request(s) have been paid (settled)
	* no POST parameters: 
		Exits quietly (not used)
	* [Mode 1] otherwise
		Performs backend functions and passes results back to bitcoinPay.js

   
SYNTAX MODE 1 (Interactive): 
	This is typically called in this mode from an eStore checkout page.
        
	CASE 1: Display 'Get Payment Request' page
		https://my.estore.com/bitcoinPay/bitcoinPay.php[?wallet=WalletName]
        	
		GET Parameters:
			wallet        = [Optional] a wallet name (e.g. if wallet=MyWallet, then MyWallet.php must exist)
			                Default: The wallet name is taken from bitcoinPay_conf.php
		POST Parameters: 
			amount        = BTC amount in fiat currency (e.g. 80.0)
			amount_format = as above but formated for display (e.g. '$80.00')
			memo          = memo text (e.g. 'Order 42')
			currency      = 3 character currency from https://en.wikipedia.org/wiki/ISO_4217#Active_codes (e.g. 'USD', 'EUR', etc)
			callback      = a URL to callback with payment result (e.g. https://your.domain/StoreCallback.php)
 
		Returns 
			HTML webpage with BTC value and a [Get Payment Request] button	
	
        CASE 2: getInvoice
        	https://my.estore.com/bitcoinPay/bitcoinPay.php[?wallet=WalletName]
        	
		GET Parameters:
			wallet        = [Optional] a wallet name (e.g. if wallet=MyWallet, then MyWallet.php must exist)
			                Default: The wallet name is taken from bitcoinPay_conf.php
		POST Parameters: 
		        Action        = getinvoice
			amount        = BTC amount in satoshis (e.g. 1003003)
			memo          = memo text (e.g. 'Order 42')
			currency      = 3 character currency from https://en.wikipedia.org/wiki/ISO_4217#Active_codes (e.g. 'USD', 'EUR', etc)
			currencyAmount= the invoice value in fiat (eg. 80.00 for $80.00) 
			callback      = a URL to callback with payment result (e.g. https://your.domain/StoreCallback.php)
 
		Returns JSON encoding of these values:
	 		Error         = Only if an error occured
	 		Invoice       = Payment Request  
	                Address       = Bitcoin address
	 		BTC           = Bitcoin amount
	                Memo          = the POST memo text
	                Expiry        = Seconds until this Payment Request expires
	                
	CASE 3: checkSettled
		https://my.estore.com/bitcoinPay/bitcoinPay.php?[?&wallet=WalletName]	

		GET Parameters
			wallet        = [Optional] a wallet name (e.g. if wallet=MyWallet, then MyWallet.php must exist)
			                Default: The wallet name is taken from bitcoinPay_conf.php
		POST Parameters
			Action        = checksettled
			address       = A Bitcoin address
			btc           = Amount in BTC
			memo          = memo text (e.g. 'Order 42')
			
		Returns JSON encoding of these values:	
		  	settled       = true (if the payment is complete), else false 
			confirmations = the number of confirmations at this time
			
			
SYNTAX MODE 2 (Cron):		
		URL: https://my.estore.com/bitcoinPay/bitcoinPay.php?checksettled
		or
		CLI: $ php bitcoinPay.php checksettled

		This is 'cron job' mode. All pending payments are checked. If a settled payment is found, the callback URL is called
		with these POST parameters:
		       	 
   		[status] => 'Paid' or 'underPaid'
    		[data] => Array(
           		[id] => internal database id. Can be ignored.
            		[wallet_name] => e.g. wallet_testnet
            		[address] => The bitcoin address that received the payment
            		[BTC] => The bitcoin value received
            		[memo] => e.g. Order 42
            		[currency] => e.g. USD
            		[amount] => Fiat value of invoice. e.g. 80  (for $80)
            		[minConfirmations] => Minimum confirmations required for this value transaction
            		[callback] => e.g. https://my.estore.com/bitcoinPay/StoreCallback.php
            		[gmt_request] => The GMT time the payment request was generated. e.g. 2018-06-04 07:55:00
            		[gmt_first_seen] => The GMT time transaction was broadcast to the blockchain  e.g. 2018-06-04 07:57:48
            		[gmt_expiry_limit] => The GMT time the payment request expired.  e.g. 2018-06-04 08:10:00
            		[gmt_mined] => The GMT time the transaction was mined. e.g. 2018-06-04 08:15:36
            		[gmt_mine_limit] => The GMT time by which the payment must have been mined by. e.g. 2018-06-04 10:55:00
            		[confirmations] => Confirmations at this time
            		[txid] => Transaction ID. e.g. e0aa695a827...f57424887243
            		[settled] => true = Payment complete. (Should never be false)
        		)
    		[hash] => ([data][address], encrypted with the Private Key) e.g. 5678077bf0cbd...58a08b709ae3c6. 
    			To check these data are from the correct source: 
    				([data][address]) must equal ([hash], decrypted with the Public Key).
    		[fiatValue] => Array  (
            		[original] => The original invoice vali in fiat. e.g. 80
            		[now] => The value of the BTC when this callback was sent. 79.8221771016
        	   	)

Instructions:
 
1. Install these files on your webserver. 
	Note: Due to JavaScript security, bitcoinPay.php must be hosted at the same domain as bitcoinPay.js
	
	
2. Update the bitcoinPay_conf.php file.
		
3. Open with browser: 
	https://your.domain/path/StoreCheckout.php
*/

include "bitcoinPay_conf.php";
define('CHECK_SETTLED','checksettled');
define('BITCOIN_LOGO'   ,'&#x20bf;');


if(  ($argv[1] == CHECK_SETTLED)  	//called with command line argument (e.g. cron job)
   || isset($_GET[CHECK_SETTLED])	//called as URL (testing)
  ){
 //cron mode
 $_POST['Action'] = CHECK_SETTLED;
 chdir(WORKING_DIRECTORY);
} else {
 $amount         =$_POST['amount'];
 $amount_format  =$_POST['amount_format'];
 $memo           =$_POST['memo'];
 $currency       =$_POST['currency'];
 $currencyAmount =$_POST['currencyAmount'];
 $address        =$_POST['address'];
 $btc            =$_POST['btc'];
 $memo           =$_POST['memo'];
 $callback       =$_POST['callback'];

 $walletName = $_GET['wallet']?$_GET['wallet']:DEFAULT_WALLET;
 include "$walletName.php";
 $Wallet = new Wallet;
}

switch($_POST['Action']){
 //MODE 1, CASE 2
 case 'getinvoice':
 	$PR = $Wallet->getPaymentRequest($_POST['message'],$_POST['amount']);
 	if(empty($PR['error'])){
   	  $DB = new bpDatabase;
 	  $DB->newPR($PR['address'],$PR['payment_request'], $_POST['message'],$currency,$currencyAmount,$PR['BTC'], 
 	  	      EXPIRY_SECONDS, MINE_SECONDS,
 	              $Wallet->getMinConfirmations($PR['BTC']),
 	              $callback, $walletName
 	              );
 	}
	//Comment out next 1 line if you do not want to receive GetInvoice notifications
	bitcoinPaySendEmail(EMAIL_TO, EMAIL_TO_NAME,'[bitcoinPay] GetInvoice',print_r($PR,1)."\n\nFile:".__FILE__);
 	
	echo json_encode(array
	 (
	 'Error'     =>$PR['error'],
	 'Invoice'   =>$PR['payment_request'],
	 'Address'   =>$PR['address'],
	 'BTC'	     =>$PR['BTC'],
	 'Memo'      =>$_POST['message'],
	 'Expiry'    =>EXPIRY_SECONDS
	 )		
	);
       exit;

  //MODE 1, CASE 3 ($address & $btc set)
  //MODE 2         ($address & $btc not set)
  case CHECK_SETTLED:
     $CS = checkSettled($Wallet,$address,$btc);
     //If txid exists then payment has been broadcast to blockchain
     if($CS['txid']){
      $DB = new bpDatabase;
      $DB->settledPR($address,$btc,$memo,$CS['confirmations']);
     }
     if($CS['error']){
      echo json_encode($CS);
     } else {
      echo json_encode(array 
       (
       'settled'      =>!empty($CS['txid']),
       'confirmations'=>$CS['confirmations']
       )
      );
     }
     exit;
 
  default:
   if(empty($_POST['memo'])) exit;

   // fall through to displaying the HTML
}

//MODE 1, CASE 1
$testnet = $Wallet->isTestnet();
if($currency){
 $ExchRate = getExchRate($currency);
 if(empty($ExchRate)){
   $CurrencyError = "Unable to get Exchange Rate for $currency";	
 } else {
   $BTC = number_format($_POST['amount'] / $ExchRate,8);
   $satoshi = $BTC * 100000000;
 }
} else {
 $CurrencyError = "Currency not set";	
}

if($CurrencyError){
?>
  <!DOCTYPE html>
  <html>
  <head><meta charset="utf-8"><title>bitcoin Pay Error</title></head>
  <body><h2>Error</h2><?php echo $CurrencyError;?></body>
  </html>	
<?php
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>bitcoinPay</title>
    <link rel="stylesheet" href="bitcoinPay.css">
    <!-- <link rel="stylesheet" href="bitcoinPay_light.css"> -->
    <script async defer src="https://cdn.rawgit.com/kazuhikoarase/qrcode-generator/886a247e/js/qrcode.js"></script>
    <script async defer src="bitcoinPay.js"></script>
</head>

<body>
 <div id="bitcoinPay" <?php if($testnet) echo ' class="testnet"';?>>
  <p id="bitcoinPayLogo"><?php echo BITCOIN_LOGO;?></p>
  <a>Pay by Bitcoin</a>
  <div id="bitcoinPayInputs">
   <input type="text" id="bitcoinPayMessage"        class="bitcoinPayInput"  value="<?php echo $_POST['memo']?>" disabled><br>
   <input type="text" class="bitcoinPayInput" value="<?php echo "$currency $amount_format";?>" disabled><br> 
   <input type="text" class="bitcoinPayInput" value="<?php echo "&#8383 $BTC";?>" disabled><br>
   <input type="hidden" id="bitcoinPayAmount" value="<?php echo $satoshi;?>">
   <input type="hidden" id="bitcoinPayCurrency" value="<?php echo  $currency;?>">
   <input type="hidden" id="bitcoinPayCurrencyAmount" value="<?php echo  $amount;?>">  
   <input type="hidden" id="bitcoinPayCallback" value="<?php echo  $callback;?>">    
   <button class="bitcoinPayButton" id="bitcoinPayGetInvoice" onclick="getPayReq()"><?php if($testnet) echo '[testnet] '?>Get payment request</button>
   <div>  <a id="bitcoinPayError"></a> </div>
  </div>
 </div>
</body>
</html>

<?php
/////////  FUNCTIONS  ///////////////////
function checkSettled($Wallet, $address,$btc){
  //Query blockchain to see if payment(s) have been made
  if($address && $btc){
    //MODE 1, CASE 3
    //Query for 1 payment
    $CP = $Wallet->checkPayment($address,$btc); 
    if($CP && !$CP['success']){
     return array('error' =>"Unable to check address $address");	
    }
    //echo "CP = ".print_r($CP,1);
 // bitcoinPaySendEmail(EMAIL_TO, EMAIL_TO_NAME,'[bitcoinPay] checksettled',print_r($CP,1));
    $transactions = $CP['address']['transactions'];
    if(!$transactions) {
	return array( 'result' =>"no transactions");
    }
    foreach($transactions as $transaction){
     //echo "\n".print_r($transaction['outputs'],1);
     if(!$transaction['outputs']){
	return array('result' =>"no outputs");
     	exit;
     }
     foreach($transaction['outputs'] as $output){
      if($output['addresses'][0]==$address  && $output['value']==$btc){
       return array(
		'address'	=>$address,
		'BTC' 		=>$btc,
		'confirmations' =>$transaction['confirmations'],
		'txid'          =>$transaction['txid'],
		'gmt_first_seen'=>gmdate('Y-m-d H:i:s', $transaction['first_seen']),
		'gmt_mined'     =>gmdate('Y-m-d H:i:s', $transaction['time']),
		'settled'	=>$Wallet->isSettled($transaction['confirmations'],$btc)
		); 		
      }
     }
    } 
  } else {
    //MODE 2
    //Query all unpaid payments
    //Typically only called as a cron job; never from the javascript.
    // Phase 1: Look for new payments that have been broadcast within EXPIRY_SECONDS window 
    // Phase 2: Look for broadcast payments to see if confirmed yet.
    
    $DB = new bpDatabase;
    
    ///// Phase 1
    $NewTransactions = $DB->getNewTransactions();

if($NewTransactions)echo "NewTransactions:".print_r($NewTransactions,1)."\n\n";
    //See if matching transaction exists on blockchain for each NewTransaction
    if($NewTransactions){
     foreach($NewTransactions as $NT){
      //It is possible that each transaction is for a different wallet.
      if(isset($Wallet)) unset($Wallet);
      include_once $NT['wallet_name'].'.php';
      $Wallet = new Wallet;
      
      $CS = checkSettled($Wallet, $NT['address'],$NT['BTC']);
      echo print_r($CS,1);     
      if(isset($CS['gmt_first_seen'])){
       $DB->setConfirmations($NT['id'], 0);
       if(strtotime($NT['gmt_expiry']) > strtotime($CS['gmt_first_seen'])) {
         //Transaction broadcast inside Expiry Window
         $DB->setExpired($NT['id'],0);
       } else {
         //Transaction broadcast outside Expiry Window
         $DB->setExpired($NT['id'],1);
       }     
      } else {
       //Not Broadcast (yet)
       if($NT['expired'])  $DB->setExpired($NT['id'],1);
      }
     }
    }
     
   ///// Phase 2
   //See if any payments that have been broadcast have sufficient confirmations
   $PendingTransactions = $DB->getPendingTransactions();
 if($PendingTransactions)  echo "\nPendingTransactions = ".print_r($PendingTransactions,1);
   if(empty($PendingTransactions))  exit;

   foreach($PendingTransactions as $PT){
     //It is possible that each transaction is for a different wallet.
     if(isset($Wallet)) unset($Wallet);
     include_once $PT['wallet_name'].'.php';
     $Wallet = new Wallet;
     /*    
     [id] => 54
     [address] => mgjQFqyrWNihreGHo4331KgEgHzAyEASgW
     [BTC] => 0.01060340
     [memo] => Order 42
     [currency] => USD
     [amount] => 80
     [minConfirmations] => 5
     [callback] => https://ubwh.com.au/BPbeta1/StoreCallback.php
     [gmt_mine_limit] => 2018-06-01 07:49:37
     */
     $CS = checkSettled($Wallet, $PT['address'],$PT['BTC']);
     /* 
     [address] => mgjQFqyrWNihreGHo4331KgEgHzAyEASgW
     [BTC] => 0.01060340
     [confirmations] => 893
     [txid] => 34859e48e71067dbbd04fe3906ebc64371fe2a4b60465dab5d1d36788f5708a9
     [gmt_first_seen] => 2018-06-01 06:43:03
     [gmt_mined] => 2018-06-01 06:44:38
     [settled] => 1
     */
     echo "CS: ".print_r($CS,1);
     
     if($CS['confirmations'] >= $PT['minConfirmations']){
       $DB->setConfirmations($PT['id'], $CS['confirmations']);
       $CurrentFiatValue = getExchRate($PT['currency']) * $PT['BTC'];
       $OriginalFiatValue = $PT['amount'];
       //Check if mined within Mine Time
       $statusText = 'fullyPaid'; //default
       if(strtotime($PT['gmt_mine_limit']) < strtotime($CS['gmt_mined'])) {
         //Mined after Mine time
         //Check if Fiat value is above or below original transaction value
         if ($CurrentFiatValue < $OriginalFiatValue)   $statusText ='underPaid';
       }      
       
       openssl_private_encrypt($PT[address], $encrypted_address, ESTORE_PRIV_KEY);
       CallBack($PT['callback'],
       		array('status' => $statusText,
       		      'data'   => array_merge($PT,$CS),
       		      'hash'   => bin2hex($encrypted_address),
       		      'fiatValue'  => array('original'=>$OriginalFiatValue,'now'=>$CurrentFiatValue)
       	              )
               );
     }
    }
    exit;
  }
}


function CallBack($URL, $data){
 //Used to callback to the originating eCommerce store
 echo "\nCallBack($URL,$json\n\n";
 if(empty($URL)) return;

  $opts = array('http' =>
    		array(
        	 'method'  => 'POST',
        	 'header'  => 'Content-type: application/x-www-form-urlencoded',
        	 'content' => http_build_query($data)
    	  	)
           );
  $context  = stream_context_create($opts);
  @file_get_contents($URL, false, $context);
}

function getExchRate($currency){
 static $ExchRate; 
 
 if(!$ExchRate[$currency]) {
   $response = json_decode(@file_get_contents("https://bitpay.com/api/rates/BTC/$currency"));
   if($response) $ExchRate[$currency] = $response->rate;
 }  
 return $ExchRate[$currency];
}

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

/////////  CLASSES ///////////////////

class bpDatabase {
  private $db_table='bpRequests';
  private $mysqli;
  
  function __construct(){
   //Open DB Link
   $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_BASE);
   if ($mysqli->connect_errno) {
    bitcoinPaySendEmail(EMAIL_TO, EMAIL_TO_NAME,
    	'[bitcoinPay] Failed to connect to DB',
    	"Error Number: $mysqli->connect_errno\nError Message: $mysqli->connect_error\n\nHOST:".DB_HOST."\nUSER:".DB_USER."\nBASE:".DB_BASE
    	);
    return;
   }
   
   $this->mysqli = $mysqli;
   //Create Table if needed. 
   $sql = "CREATE TABLE IF NOT EXISTS $this->db_table (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `wallet_name` TINYTEXT NOT NULL,
          `address` varchar(255)  NOT NULL,
          `memo` varchar(30)  NOT NULL,
          `BTC` decimal (10,8) NOT NULL,
          `currency` CHAR(3) NOT NULL COMMENT '3 char currency code',
          `amount` FLOAT NOT NULL COMMENT 'Invoice amount in local currency',
          `payment_request` TEXT NOT NULL,
          `expiry_seconds`  INT(6)  NOT NULL COMMENT 'seconds allowed for transaction broadcast',
          `mine_seconds` INT(11) NOT NULL COMMENT 'seconds allowed for transaction to be mined' ,
          `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `confirmations` INT(6) NULL DEFAULT NULL COMMENT 'null=not received, 0,1,... = received with x confirmations',
          `minConfirmations` INT(6) NOT NULL,
          `expired` BOOLEAN NULL DEFAULT NULL COMMENT 'NULL=unknown, 0=Expired with txid, 1=Expired with no TXID',
          `callback` TEXT NULL DEFAULT NULL COMMENT 'Callback URL when confirmed',
          PRIMARY KEY (`id`)
          )";
    mysqli_query($this->mysqli,$sql); 
  }//_construct
  
  function __destruct(){
    mysqli_close($this->mysqli);
  }
  
 public function newPR($address,$payment_request,$message,$currency,$amount,$BTC,$expiry_seconds,$mine_seconds,$minConfirmations,$callback,$walletName){
    $sql  = "INSERT into $this->db_table (wallet_name,address,payment_request,memo,currency,amount,BTC,expiry_seconds,mine_seconds,minConfirmations,callback) ";
    $sql .= "VALUES ('$walletName','$address','$payment_request','$message','$currency','$amount','$BTC','$expiry_seconds','$mine_seconds','$minConfirmations','$callback')";	
    mysqli_query($this->mysqli,$sql);
 }
 
 public function settledPR($address, $btc, $memo, $confirmations){
    $sql ="UPDATE $this->db_table SET confirmations = '$confirmations' WHERE address = '$address' AND BTC = '$btc' AND memo = '$memo'";
    //bitcoinPaySendEmail(EMAIL_TO, EMAIL_TO_NAME,'[bitcoinPay] settled',__FILE__."\n$sql");
    mysqli_query($this->mysqli,$sql);
 }
 
 public function getPendingTransactions(){
  $sql = 'SET time_zone = "+00:00";';  //GMT
  mysqli_query($this->mysqli,$sql);

  $sql = "SELECT id, wallet_name, address, BTC, memo, currency, amount, minConfirmations, callback, timestamp as gmt_request, ".
         "TIMESTAMPADD(second,expiry_seconds,timestamp)   as gmt_expiry_limit, ".
         "TIMESTAMPADD(second,".MINE_SECONDS.",timestamp) as gmt_mine_limit ".
         "FROM $this->db_table ".
         "WHERE expired = 0 AND confirmations < minConfirmations ";       
         
  $q = mysqli_query($this->mysqli,$sql);
  while($row = mysqli_fetch_assoc($q)){
   $PT[] = $row;
  }
  return $PT;
 }
 
 public function getNewTransactions(){
  //Return transactions that have not expired & unknown txid
  $sql = 'SET time_zone = "+00:00";';  //GMT
  mysqli_query($this->mysqli,$sql);
  
  $sql = "SELECT * , TIMESTAMPADD(second,expiry_seconds,timestamp) as gmt_expiry ".
         ", TIMESTAMPDIFF(SECOND, timestamp, CURRENT_TIMESTAMP) - expiry_seconds as ExpiredSeconds ".
         "FROM $this->db_table ".
         "WHERE expired IS NULL "
         //."AND confirmations = 0"
         ;
         
  $q = mysqli_query($this->mysqli,$sql);
  while($row = mysqli_fetch_assoc($q)){
   $NT[] = array('expired'  		=> $row['ExpiredSeconds'] > 0,
   		 'gmt_payment_request'	=> $row['timestamp'],
                 'gmt_expiry'		=> $row['gmt_expiry'],
                 'id'			=> $row['id'],
                 'address' 		=> $row['address'],
                 'BTC' 			=> $row['BTC'],
                 'wallet_name'		=> $row['wallet_name']
   		);
  }
  return $NT;
 }
 
 public function setSettled($id, $confirmations){
  $sql = "UPDATE $this->db_table SET confirmations = '$confirmations' WHERE id = '$id'";
 } 
 

 public function setExpired($id, $expired){
  $sql = "UPDATE $this->db_table SET expired = '$expired' WHERE id='$id'";
  mysqli_query($this->mysqli,$sql);
 }

 public function setConfirmations($id, $confirmations){
   $sql = "UPDATE $this->db_table SET confirmations= '$confirmations' WHERE id='$id'";
   mysqli_query($this->mysqli,$sql);
 }

}

class Wallet_parent{ 
    private $baseurl = 'https://api.smartbit.com.au/v1/blockchain';
    private $testurl = 'https://testnet-api.smartbit.com.au/v1/blockchain';
    protected $xpub;  //'xpub...' = mainnet. 'tpub...' = testnet
    
    public function isTestnet() { 
       return !( stripos( $this->xpub , 'xpub' ) === 0 ||
                 stripos( $this->xpub , 'ypub' ) === 0 ||
                 stripos( $this->xpub , 'zpub' ) === 0 
               );
    } 
    
    public function getPaymentRequest($memo='',$satoshi=0){
      $URL = ($this->isTestnet()?$this->testurl:$this->baseurl)."/address/$this->xpub?tx=0"; 
      $Response = json_decode(@file_get_contents($URL),true);
      if(!$Response || $Response['success'] == false){
       $PR['error']   ="Unable to get payment address";
      } else {
       $PR['address'] = $Response['address']['extkey_next_receiving_address'];
       $PR['BTC']=$satoshi/100000000;
       $PR['payment_request'] = "bitcoin:".$PR['address'];
       $PR['payment_request'] .= "?amount=".$PR['BTC'];
       $PR['payment_request'] .= "&message=".rawurlencode($memo);
       $PR['payment_request'] .= "&label=".rawurlencode($memo);
       $PR['memo']=$memo;
      }
      return $PR;
    }	
    
    public function checkPayment($address,$btc){
     $URL = ($this->isTestnet()?$this->testurl:$this->baseurl)."/address/$address";
     return json_decode(@file_get_contents($URL),true);
    }
    
    public function getMinConfirmations($BTC){
     if($BTC == 0)       return 6;	//Should not happen
     if($BTC < 0.000001) return 0;	//dust
     if($BTC < 0.00001)  return 1;	//sticker
     if($BTC < 0.0001)   return 2;  	//coffee
     if($BTC < 0.001)    return 3;  	//lunch
     if($BTC < 0.01)     return 4;  	//Week's Groceries
     if($BTC < 0.1)      return 5;  	//Big TV
     return 6; 				//Small Car or more
    }
    
    public function isSettled ($confirmations,$BTC){
     return $confirmations >=  $this->getMinConfirmations($BTC);
   }
} 
?>
