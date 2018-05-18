# NOWHERE NEAR READY ... UNDER CONSTRUCTION #


# widgetPay-PHP
A simple and modularlzed template that can be used to accept eCommerce payments on your eCommerce website. The Javascript frontend runs on the customer's browser, and passes order details the PHP backend. Details of how to pay are then passed back to the frontend for display to the customer.

![widgetPay GIF](images/lwidgetPayDemo.gif)

## Features ##
* Modular code
* Timeout support
* QR Code support
* Copy support
* Error handling
* Payment Received support
* Optional parament support (e.g. mainnet/testnet for crypto payments)

If want to tip me you can use my LightningTip as below.
(_https_ not used as this is hosted on a free web server without SSL certificates. You will not be entering any sensitive data.)
* [mainnet](http://raspibolt.epizy.com/LT/lightningTip.php)
* [testnet](http://raspibolt.epizy.com/LT/lightningTip.php?testnet=1)

## Credit ##
widhgetPay-PHP is based on [LightningTip-PHP](https://github.com/robclark56/lightningtip-PHP), which in turn is based on [LightningTip](https://github.com/michael1011/lightningtip/blob/master/README.md) by [michael1011](https://github.com/michael1011/lightningtip).
## Requirements ##
* a webserver that supports [PHP](http://www.php.net/) and [curl](https://curl.haxx.se/)
## Security ## 
This is a template with dummy inputs and outputs. No security issues.
## eCommerce Example ##
The intended audience for this project is users that have an existing online eCommerce site. Typically the customer ends up at a _checkout confirmation_ webpage with some _Pay Now_ button(s).

In this project we include a very simple dummy eCommerce checkout page that serves as an example of how to deploy _widgetPay_. 
  
## Prepare Web Server ##
Your webserver will need to have the _php-curl_ package installed. 

On a typical Linux webserver you can check as follows. The example below shows that it is installed.
```bash
$ dpkg -l php-curl
Desired=Unknown/Install/Remove/Purge/Hold
| Status=Not/Inst/Conf-files/Unpacked/halF-conf/Half-inst/trig-aWait/Trig-pend
|/ Err?=(none)/Reinst-required (Status,Err: uppercase=bad)
||/ Name                   Version          Architecture     Description
+++-======================-================-================-=================================================
ii  php-curl               1:7.0+49         all              CURL module for PHP [default]
```
If you see `no packages found matching php-curl` then install as follows.
```
$ sudo apt-get update
$ sudo apt-get install php-curl
```


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

