# NOWHERE NEAR READY ... UNDER CONSTRUCTION #


# bitcoinPay-PHP
The files in this project will allow you to safely accept Bitcoin payments on your online store.

![bitcoinPay GIF](images/bitcoinPayDemo.gif)

## Features ##
* Support for:
  * mainnet and testnet
  * P2PKH addresses (e.g. 1xxxxx").
    * Segwit support is not available as this is written. If/When Segwit address generation is supported at  https://www.smartbit.com.au/api then this code (without change) will support Segwit.
  * Exchange Rate Fluctuation Protection. Protection against exchange rate fluctuation in cases of late payment broadcasts and/or late transaction mining. 
  * Address Re-use.
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
* a webserver that supports [PHP](http://www.php.net/) and [mySQL](https://www.mysql.com/).
## Security ## 
At no point do you enter any of your bitcoin private keys. No hacker can spend your bitcoins. 
## eCommerce Example ##
The intended audience for this project is users that have an existing online eCommerce site. Typically the customer ends up at a _checkout confirmation_ webpage with some _Pay Now_ button(s).

In this project we include a very simple dummy eCommerce checkout page that serves as an example of how to deploy _bitcoinPay_. 
  
## Design ##
The basic flow is as follows:

1. eCommerce site displays a shopping cart page with a total payable (Fiat currency)
1. User clicks _Pay Button_  => New Javascript page displays a price in BTC
1. User clicks _Get Payment Request_ => PHP file responds with Payment Request
1. Javascript displays QR Payment Request 
1. PHP file continuously monitors blockchain for matching transctions
1. Customer makes payment with wallet
1. If/When payment has sufficient confirmations => Secure message sent back to eCommerce site with payment status ('Paid' or 'Underpaid') and details.

```
                                       
    [eStore]<----- 'Paid'/'Underpaid'------\ 
        |                                  |
        |                                  ^
        \/                                 |
    [Web Browser,.js,.css]<----HTTP---->[.php]--[database]
                                           |
                                   [Blockchain Explorer]
                                           |
    [Bitcoin Wallet] -----------------[Blockchain]	
```
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

