# bitcoinPay-PHP
The files in this project will allow you to safely accept Bitcoin payments on your online PHP-based store (eStore).

| Checkout Mode | Manual Mode |
|---|---|
|![bitcoinPay GIF](images/bitcoinPay_demo.gif)|![bitcoinPayManual GIF](images/bitcoinPayManual_demo.gif)|

## FEATURES ##
* Support for:
  * Two Modes
    * Checkout Mode: eStore provides memo & fiat values
    * Manual Mode: User provides memo & fiat values
  * mainnet and testnet
  * Multiple fiat currencies
  * P2PKH addresses (e.g. 1xxxxxxxx).
    * Segwit support is not available as this is written. If/When Segwit address generation is supported at  https://www.smartbit.com.au/api then this code (without change) will support Segwit.
  * Exchange Rate fluctuation protection. Protection in cases of late payment broadcasts and/or late transaction mining. 
  * Each new payment to an unused bitcoin address. With support for multiple payments to same address.
  * QR Code Payment Request
  * Copy to clipboard
  * Error handling
  * Variable Confirmations. E.g. buying a low value sticker requires only 1 confirmation. Buying a car requires 6 confirmations.
  * Multiple wallets
  * Live exchange rate conversions between Fiat and BTC
  * Encryption protected messaging from bitcoinPay back to the eStore site.
  * CSS formatting

## CREDIT ##
bitcoinPay-PHP is based on [LightningTip-PHP](https://github.com/robclark56/lightningtip-PHP), which in turn is based on [LightningTip](https://github.com/michael1011/lightningtip/blob/master/README.md) by [michael1011](https://github.com/michael1011/lightningtip).
## REQUIREMENTS ##
A webserver that supports:

* [PHP](http://www.php.net/), 
* [mySQL](https://www.mysql.com/), and 
* [cron jobs](https://en.wikipedia.org/wiki/Cron).
## SECURITY ## 
At no point do you enter any of your bitcoin private keys. No hacker can spend your bitcoins. 

You need to keep your webserver secure, as a hacker with sufficient privileges could exchange his/her xpub for yours and customers would start paying the hacker.
## ECOMMERCE EXAMPLE - CHECKOUT MODE ##
The intended audience for this project is users that have an existing online eCommerce site (eStore). Typically the customer ends up at a _checkout confirmation_ webpage with some _Pay Now_ button(s).

In this project we include a very simple dummy eStore checkout page that serves as an example of how to deploy _bitcoinPay_. 
  
## DESIGN ##
The basic flow is as follows:

1. Checkout Mode:
   1. eStore displays a shopping cart page with a total payable (Fiat currency)
   1. User clicks _Pay Button_  => Redirected to PHP file which converts fiat value to BTC, and returns a confirmation page
1. Manual Mode
   1. User enters Memo and fiat value. PHP/Javascript calculates BTC value.
1. User clicks _Get Payment Request_ => Javascript passes values to PHP file which responds with a Payment Request
1. The PHP file continuously monitors the blockchain for matching transactions
1. Customer makes payment with wallet
1. If/When payment has sufficient confirmations => PHP file sends a secure message back to eStore with payment status ('Paid' or 'Underpaid') and details.
1. eStore checks message validity, and then takes appropriate action for 2 possible cases: 'Paid' or 'Underpaid'

```                                    
    [eStore]<----- 'Paid'/'Underpaid'------\ 
        |                                  |
        |                                  ^
        \/                                 |
    [Web Browser,.js,.css]<----HTTP---->[.php]--[database]
        |                                  |
       [QR]                          [Blockchain Explorer]
        |                                  |
        \/                                 |
    [Bitcoin Wallet] -----------------[Blockchain]	
```
## EXTENDED PUBLIC KEYS ##
This project takes advantage of the concept of _Extended Public Keys_ (xpub). For a full understanding, see [Mastering Bitcoin, Andreas M. Antonopoulos, Chapter 5](https://github.com/bitcoinbook/bitcoinbook/blob/develop/ch05.asciidoc).

![HD Wallet Image](images/HD-wallet.png)

The important things to note are:
* An xpub can generate 
  * ALL of the public keys & addresses in your wallet.
  * NONE of the private keys in your wallet, so can not be used to spend your bitcoins.
* Each level of the tree in the above image has a different xpub.
  * The xpub at the master ('m') level can generate addresses for many different coins (Bitcoin, Litecoin,...). We do not want to use the xpub from this level. 
  * The xpub from the bitcoin (or Bitcoin-Testnet) level is what is needed for this project.
  
### How does bitcoinPay-PHP get the next receiving address from the xpub? ###
There is an undocumented feature at the [smartbit.com.au API](https://www.smartbit.com.au/api). If you give an xpub to the _address_ API call, it returns the next un-used receiving address.

[Try it!](https://api.smartbit.com.au/v1/blockchain/address/xpub6DFUsfUukGFu5E1rjZZpwGXVw8wUcrvhxzgFgCFCdyT3nxsbQoax9BLME3pY8j2j81ewhF95gbSRiBnmseGy69E2ZYKbHrmBjwtyXkGeSES)

### What the ?   xpub, ypub, zpub, tpub, upub, vpub ###
The 1st character of an Extended Public Key tells you what sort of wallet it comes from. As this is written, the [smartbit.com.au API](https://www.smartbit.com.au/api) supports only _xpub_ and _tpub_.

| Address Type  | mainnet | testnet|
|----:|-------|-------|
|P2PKH (eg) | xpub (1xxxxxx)| tpub (mxxxxxx)|
|P2SH (eg)| ypub (3xxxxx)| upub (2xxxxx)|
|Bech32 (eg)| zpub (bc1xxx)| vpub (tb1xxx)|

[More info ...](https://support.samourai.io/article/49-xpub-s-ypub-s-zpub-s)

## MONITORING FOR PAYMENTS ##
This is done by a [cron job](https://en.wikipedia.org/wiki/Cron). The timing logic is as below. _EXPIRY_SECONDS_ & _MINE_SECONDS_ are set in the configuration file.

* __EXPIRY_SECONDS__ defines a time window that starts as soon as the Payment Pequest is generated, and ends EXPIRY_SECONDS later. For a payment to be received it must be broadcast to the blockchain within that window. It does not have to be confirmed within that window. If the payment is broadcast after EXPIRY_SECONDS, bitcoinPay will not track the payment. This window adds a degree of protection when the FIAT/BTC exchange rate is rapidly changing.
* __MINE_SECONDS__ defines a time interval that starts as soon as the Payment Request is generated, and ends MINE_SECONDS later. A non-expired payment that is mined (include in a block) within this window, and has sufficient confirmations is accepted as PAID. This window protects for the case when the sender does not include sufficient miner fee and inclusion in the blockchain takes too long, again risking invoice under-payment in fiat value. 

The cron job runs periodically to check pending payments. `bitcoinPay.php`can be used as the file for that cron job, if:

* called as a URL with one GET parameter as follows `https://my.estore.com/bitcoinPay/bitcoinPay.php?checksettled`, or
* called from the command-line as follows:  `$ php bitcoinPay.php checksettled`
     
 The logic used is as follows:
     
 |Transaction received  within EXPIRY_SECONDS |Mined within MINE_SECONDS|Current Currency Value >= Invoice Currency Value|           Result |
 | :---: | :---: | :---: | :---: |
 |Yes|Yes|True|Paid|
 |Yes|Yes|False|Paid|
 |Yes|No|True|Paid|
 |Yes|No|False|Underpaid|
 |No|N/A|N/A|Not Tracked|

## PREPARATION ##
### 1. Get your xpub & tpub ###
Your wallet software will give your xpub/tpub. Examples shown below.

1. Coinomi: Select Bitcoin -> (3-dot menu) -> Account Details
1. Electrum: Open the wallet you want to receive funds into. Wallet --> Information.
1. Make your own: 
   * Go to https://iancoleman.io/bip39/
   * Generate __AND SAVE__ a new 12-word seed
   * Select Coin: __BTC-Bitcoin__ for mainnet, or __BTC-Bitcoin Testnet__ for testnet
   * Copy the _Account Extended Public Key_ (not the _BIP32 Extended Public Key_)
1. Other wallets: Check your documentation.

### 2. Generate Private/Public key pair ##
To generate a Private/Public key pair, use one of these options:  

1. Upload [generateKeys.php](https://github.com/robclark56/bitcoinPay-PHP/blob/master/utilities/generateKeys.php) to your host computer. Then run from the command line interface: `$ php generateKeys.php`
1. [http://travistidwell.com/jsencrypt/demo/](http://travistidwell.com/jsencrypt/demo/)  (save page and run offline for extra safety)

Save these keys locally for now. They will look something like this:
```
-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCQ6cZssvv0DNrh5qTDq3VnT8c41V34lTa5YFgE3itTEsxBFgUl
[... lines deleted...]
fqE1sl6cOF5yhsoYdQ2L0uJOqBS6rkqtbnN44pSzMDph
-----END RSA PRIVATE KEY-----

-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCQ6cZssvv0DNrh5qTDq3VnT8c4
[... lines deleted...]
U4UZulZEer8ss8l62QIDAQAB
-----END PUBLIC KEY-----
```

### 3. Create SQL Database ###
You will need to create a mySQL database. Consult your host server documentation.

For example, if you have access to cPanel, [these instructions](https://support.hostgator.com/articles/cpanel/how-do-i-create-a-mysql-database-a-user-and-then-delete-if-needed) can help.

After you have created your database you should have this information:

|-- Parameter --|------------------ Value ----------------|--- Comment ---|
|---------|-----|-------|
|User||Give _ALL PRIVILEGES_|
|Password||||
|Host||Often is _localhost_|
|Database name|||

## INSTALLATION ##
* Create a folder on your webserver to host the bitcoinPay files. Consult your webserver documentation for details on where html files are stored.
  * e.g.: `.../public_html/bitcoinPay`
  * e.g.: `.../var/www/html/bitcoinPay`
  * e.g.: `.../htdocs/bitcoinPay`
  
* Download the [latest release](https://github.com/robclark56/bitcoinPay-PHP/releases), and unzip.
* Upload all files from the unzipped _resources_ folder to your webserver folder. __Note__: Due to JavaScript security, bitcoinPay.php must be hosted at the same domain as bitcoinPay.js
* Edit files.
  * __bitcoinPay_conf.php__: Edit  values as needed. Leave _WALLET_DEFAULT_ set to _wallet_testnet_.
  * __StoreCheckout.php__: Edit the CHANGE_ME section.
  * __StoreCallback.php__: Edit the CHANGE_ME section.
     * See note below on [Email Special Consideration](https://github.com/robclark56/bitcoinPay-PHP/blob/master/README.md#email-special-consideration)
  * __bitcoinPay.js__: Edit the CHANGE_ME section.
* Create cron job to periodically check pending payments. Examples on how to run the cron job every 15 minutes are:
  * Servers with normal crontab-style cron jobs:
    * `*/15	*	*	*	*	/usr/bin/php /home/user/public_html/bitcoinPay/bitcoinPay.php checksettled`
  * Servers only allowing URL-style cron jobs:
    * Every 15 mins: `https://my.estore.com/bitcoinPay/bitcoinPay.php?checksettled`
  * Other: 
    * Consult your documentation
    
### Email Special Consideration  ###
Some webserver hosts do not permit use of the PHP mail() function for security reasons. If you are in this category, there is a workaround available in bitcoinPay.

* You will need to edit the _bitcoinPaySendEmail()_ function in these 2 files:
  * StoreCallback.php
  * bitcoinPay.php
* Read the comments in the _bitcoinPaySendEmail()_ function in either of these files
* Install PHPMailer in a folder called `PHPMailer`
* Edit the _bitcoinPaySendEmail()_ function in the 2 files as below
  * Change ` if(false){ //false = use PHPMailer`
  * Edit all lines with CHANGE_ME
  
## TESTING - CHECKOUT MODE ##
Use your browser to visit your URLs like this:

* `https://my.estore.com/bitcoinPay/bitcoinPay.php?checksettled`
  * Note: This displays nothing if there are no pending payments, so _blank screen_ is a good response. The only point in trying this is to confirm there are no PHP configuration or syntax errors.
* `https://my.estore.com/bitcoinPay/StoreCallback.php`
  * You should receive an email with this is the body: "Hacking Attempt???". This is the expected response when this file is called with the wrong, or no, POST parameters.
* `https://my.estore.com/bitcoinPay/StoreCheckout.php`
* `https://my.estore.com/bitcoinPay/StoreCheckout.php?order_id=100`
* `https://my.estore.com/bitcoinPay/StoreCheckout.php?wallet=wallet_testnet`
* `https://my.estore.com/bitcoinPay/StoreCheckout.php?wallet=wallet_mainnet`
* `https://my.estore.com/bitcoinPay/StoreCheckout.php?wallet=wallet_mainnet&order_id=100`

or you can check my test site here:

(_https_ not used as this is hosted on a free web server without SSL certificates. You will not be entering any sensitive data.)

* [Order for USD 80.00 (mainnet)](http://raspibolt.epizy.com/bitcoinPay/StoreCheckout.php?wallet=wallet_mainnet) CAREFUL: Don't send me real BTC.
* [Order for USD 5.00 (testnet)](http://raspibolt.epizy.com/bitcoinPay/StoreCheckout.php?wallet=wallet_testnet&order_id=100)

## TESTING - MANUAL MODE ##
Confirm Checkout Mode (above) is working.

Use your browser to visit your URL like this:

* `https://my.estore.com/bitcoinPay/bitcoinPay.php`

or you can check my test site here:

* [mainnet](http://raspibolt.epizy.com/bitcoinPay/bitcoinPay.php?wallet=wallet_mainnet) CAREFUL: Don't send me real BTC.
* [testnet](http://raspibolt.epizy.com/bitcoinPay/bitcoinPay.php?wallet=wallet_testnet)

## LIVE USAGE ##

### 1a. Checkout Mode: Create a page on your eStore with a form something like this: ###
```php
<?php
$order_id     = CHANGE_ME; //Some unique order identifier  
$order_value  = CHANGE_ME; //Fiat order value
$amount_format= CHANGE_ME; //eg '$'.number_format($order_value,2)
$currency     = CHANGE_ME; //eg 'USD', 'EUR', ...
$callback_url = CHANGE_ME: //eg 'https://my.estore.com/bitcoinpay/StoreCallback.php'
$bitcoinPayPHP= CHANGE_ME; //eg 'bitcoinPay/bitcoinPay.php'
?>
<form action="<?php echo $bitcoinPayPHP;?>" method="post"> 
 <table cellpadding="10">
  <tr>
   <th>Order Number: </th>
   <td><input type="input" name="memo" value="<?php echo $order_id;?>" readonly ><td>
  </tr>
  <tr>
   <th>Order Total: </th>
   <td><input type="input" name="amount_format" value="<?php echo $amount_format;?>"  readonly ><td>
  </tr>
  <tr><td colspan="2" align="right"><button type="submit">Continue...</button></td></tr>
 </table>
 <input type="hidden" name="amount" value="<?php echo $order_value;?>">
 <input type="hidden" name="currency" value="<?php echo $currency;?>"> 
 <input type="hidden" name="callback" value="<?php echo $callback_url;?>">
</form>
```
### 1b. Manual Mode ###
Just visit your url like: `https://my.estore.com/bitcoinPay/bitcoinPay.php`
### 2. Process Payment Notifications ###
Edit _StoreCallback.php_ and change these two sections as appropriate for your eStore.
```php
case 'fullyPaid':
   //Add code here to process fully paid order
   break;
   
  case 'underPaid':
  //Add code here to process under-paid order
  break;
```
### 3. Edit __bitcoinPay_conf.php__ to set the default wallet to your mainnet wallet. ###
```php
define('DEFAULT_WALLET'   ,'wallet_mainnet');	
```

### 4. There is a light theme available for bitcoinPay. ###
If you want to use it, uncomment this line in your bitcoinPay.php file:
```
<link rel="stylesheet" href="bitcoinPay_light.css">
```
### 5. Do not use bitcoinPay on XHTML sites ### 
That causes some weird scaling issues.

## LOCK DOWN SECURITY ##
### 1. Set files to Read Only ###
Example using the shell command line:
```
$ cd <bitcoinPay folder>
$ chmod 0444 *
$ cd ..
$ chmod 0555 <bitcoinPay folder>
```

---
If want to tip me, you can use my [LightningTip](https://github.com/robclark56/lightningtip-PHP "lightningTip-PHP") as below.
(_https_ not used as these are hosted on a free web server without SSL certificates. You will not be entering any sensitive data.)
* [mainnet](http://raspibolt.epizy.com/LT/lightningTip.php)
* [testnet](http://raspibolt.epizy.com/LT/lightningTip.php?testnet=1)

