# NOWHERE NEAR READY ... UNDER CONSTRUCTION #


# bitcoinPay-PHP
The files in this project will allow you to safely accept Bitcoin payments on your online store (eStore).

![bitcoinPay GIF](images/bitcoinPay_demo.gif)

## Features ##
* Support for:
  * mainnet and testnet
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
  * Encryption protected messaging from bitcoinPay back to the eCommerce site.
  * CSS formatting

If want to tip me you can use my LightningTip as below.
(_https_ not used as this is hosted on a free web server without SSL certificates. You will not be entering any sensitive data.)
* [mainnet](http://raspibolt.epizy.com/LT/lightningTip.php)
* [testnet](http://raspibolt.epizy.com/LT/lightningTip.php?testnet=1)

## Credit ##
bitcoinPay-PHP is based on [LightningTip-PHP](https://github.com/robclark56/lightningtip-PHP), which in turn is based on [LightningTip](https://github.com/michael1011/lightningtip/blob/master/README.md) by [michael1011](https://github.com/michael1011/lightningtip).
## Requirements ##
* a webserver that supports [PHP](http://www.php.net/), [mySQL](https://www.mysql.com/), and [cron jobs](https://en.wikipedia.org/wiki/Cron).
## Security ## 
At no point do you enter any of your bitcoin private keys. No hacker can spend your bitcoins. 
## eCommerce Example ##
The intended audience for this project is users that have an existing online eCommerce site (eStore). Typically the customer ends up at a _checkout confirmation_ webpage with some _Pay Now_ button(s).

In this project we include a very simple dummy eStore checkout page that serves as an example of how to deploy _bitcoinPay_. 
  
## Design ##
The basic flow is as follows:

1. eStore displays a shopping cart page with a total payable (Fiat currency)
1. User clicks _Pay Button_  => PHP file converts fiat value to BTC, and returns a confirmation pagee
1. User clicks _Get Payment Request_ => Javascript passes values to PHP file which responds with a Payment Request
1. PHP file continuously monitors blockchain for matching transctions
1. Customer makes payment with wallet
1. If/When payment has sufficient confirmations => Secure message sent back to eStore with payment status ('Paid' or 'Underpaid') and details.

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
## Extended Public Keys ##
This project takes advantage of the concept of _Extended Public Keys_ (xpub). For a full understanding, see [Mastering Bitcoin, Andreas M. Antonopoulos, Chapter 5](https://github.com/bitcoinbook/bitcoinbook/blob/develop/ch05.asciidoc).

![HD Wallet Image](images/HD-wallet.png)

The important things to note are:
* An xpub can generate 
  * ALL of the public keys & addresses in your wallet.
  * NONE of the private keys in your wallet, so can not be used to spend your bitcoins.
* Each level of the tree in the above image has a different xpub.
  * The xpub at the master ('m') level can generate addresses for many different coins (Bitcoin, Litecoin,...). We do not want to use the xpub from this level. 
  * The xpub from the bitcoin level is what is needed for this project.
  
### Where do I get my xpub? ###
Your wallet software will give you your xpub:

1. Electrum: Open the wallet you want to receive funds into. Wallet --> Information.
1. Make your own: 
   * Go to https://iancoleman.io/bip39/
   * Generate a new 12-word seed
   * Select Coin: _BTC-Bitcoin_ for mainnet, or _BTC-Bitcoin Testnet_ for testnet
   * Copy the _Account Extended Public Key_ (not the _BIP32 Extended Public Key_)
1. Other wallets: Check your documentation.

### How does bitcoinPay-PHP get the next receiving address from the xpub? ###
There is an undocumented feature at the [smartbit.com.au API](https://www.smartbit.com.au/api). If you give an xpub to the _address_ API call, it returns the next un-used receiving address.

[Try it!](https://api.smartbit.com.au/v1/blockchain/address/xpub6DFUsfUukGFu5E1rjZZpwGXVw8wUcrvhxzgFgCFCdyT3nxsbQoax9BLME3pY8j2j81ewhF95gbSRiBnmseGy69E2ZYKbHrmBjwtyXkGeSES)

### What the?   xpub, ypub, zpub, tpub, upub, vpub ###
The 1st character of an Extended Public Key tells you what sort of wallet it comes from. As this is written, the [smartbit.com.au API](https://www.smartbit.com.au/api) supports only _xpub_ and _tpub_.

| Address Type  | mainnet | testnet|
|----:|-------|-------|
|P2PKH| xpub | tpub|
| (eg)|(1xxxxxx)|(mxxxxx)|
|P2PKH| ypub | upub|
|   (eg)|(3xxxxx) | (2xxxx) |
|Bech32| zpub | vpub|
|    (eg)| (bc1xxx) |  ?? | 

## Monitoring for Payments ##
This is done by a [cron job](https://en.wikipedia.org/wiki/Cron). The timing logic is as below. _EXPIRY_SECONDS_ & _MINE_SECONDS_ are set in the configuration file.

* __EXPIRY_SECONDS__ defines a time window that starts as soon as the Payment Pequest is generated, and ends EXPIRY_SECONDS later. For a payment to be received it must be broadcast to the blockchain within that window. It does not have to be confirmed within that window. If the payment is broadcast after EXPIRY_SECONDS, bitcoinPay will not track the payment. This window adds a degree of protection when the FIAT/BTC exchange rate is rapidly changing.
* __MINE_SECONDS__ defines a time interval that starts as soon as the Payment Request is generated, and ends MINE_SECONDS later. A non-expired payment that is mined (include in a block) within this window, and has sufficient confirmations is accepted as PAID. This window protects for the case when the sender does not include sufficient miner fee and inclusion in the blockchain takes too long. 

The cron job runs periodically to check pending payments. `bitcoinPay.php`is designed to be that cron job, if:

* called as a URL with one GET parameter as follows `https://.../bitcoinPay.php?checksettled`, or
* called from the command-line as follows:  ` php bitcoinPay.php checksettled`
     
     
 The logic used is as follows:
     
 |Transaction received  within EXPIRY_SECONDS |Mined within MINE_SECONDS|Current Currency Value >= Invoice Currency Value|           Result |
 | :---: | :---: | :---: | :---: |
 |Yes|Yes|True|Paid|
 |Yes|Yes|False|Paid|
 |Yes|No|True|Paid|
 |Yes|No|False|Underpaid|
 |No|N/A|N/A|Not Tracked|



## Prepare Web Server ##
xxx


## How to install ##
* Download the [latest release](https://github.com/robclark56/lightningPay-PHP/releases), and unzip.
* From the _resources_ folder: Upload these files to your webserver:
  * StoreCheckout.php
  * widgetPay_conf.php
  * widgetPay.php
  * widgetPay.js
  * widgetPay.css
  * widget_light.css (Optional)
* Edit 
  * `widget_conf.php`. ??????
  * the _CHANGE ME_ section of `widgetPay.js`.

## How to test ##
Use your browser to visit these URLs:

* `https://your.web.server/path/StoreCheckout.php`
* `https://your.web.server/path/StoreCheckout.php?order_id=100`
* `https://your.web.server/path/StoreCheckout.php?testnet=1`
* `https://your.web.server/path/StoreCheckout.php?testnet=1&order_id=100`

or you can check my test sites here:

(_https_ not used as this is hosted on a free web server without SSL certificates. You will not be entering any sensitive data.)

* [Order for USD 80.00](http://raspibolt.epizy.com/WP/StoreCheckout.php)


## How to Use ##
Copy the contents of the head tag from `widgetPay.php` into the head section of the HTML file you want to show widgetPay in. The div below the head tag is widgetPay itself. Paste it into any place in the already edited HTML file on your server.


There is a light theme available for widgetPay. If you want to use it, uncomment this line in your widgetPay.php file:

```
<link rel="stylesheet" href="widgetPay_light.css">
```

**Do not use widgetPay on XHTML** sites. That causes some weird scaling issues.

