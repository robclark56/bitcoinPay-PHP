# LightningPay-PHP
A simple way to accept order payments via the Lightning Network on your eCommerce website. 
![LightningPay GIF](images/lightningPayDemo.gif)

If want to tip me you can use my LightningTip as below.
(_https_ not used as this is hosted on a free web server without SSL certificates. You will not be entering any sensitive data.)
* [mainnet](http://raspibolt.epizy.com/LT/lightningTip.php)
* [testnet](http://raspibolt.epizy.com/LT/lightningTip.php?testnet=1)

## Credit ##
LightningPay-PHP is based on [LightningTip-PHP](https://github.com/robclark56/lightningtip-PHP), which in turn is based on [LightningTip](https://github.com/michael1011/lightningtip/blob/master/README.md) by [michael1011](https://github.com/michael1011/lightningtip).
## Requirements ##
* one [lnd](https://github.com/lightningnetwork/lnd) instance
* a webserver that supports [PHP](http://www.php.net/) and [curl](https://curl.haxx.se/)
## Security ## 
The _invoice.macroon_ file limits the functionality available to LightningPay.php to only invoice related functions. Importantly, if someone steals your _invoice.macaroon_, they can NOT spend any of your funds.
## eCommerce Example ##
The intended audience for this project is users that have an existing online eCommerce site. Typically the customer ends up at a _checkout confirmation_ webpage with some _Pay Now_ button(s).

In this project we include a very simple dummy eCommerce checkout page that serves as an example of how to deploy _lightningPay_. 
## Prepare LND ##
* Enable REST on your lnd instance(s). See  the _restlisten_ parameter in the [lnd documentation](https://github.com/lightningnetwork/lnd/blob/master/sample-lnd.conf).
* Open any necessary firewall ports on your lnd host, and router port-forwards as needed.
* Generate a hex version of the _invoice.macaroon_ file on your lnd instance.
  * Linux:    `xxd -ps -u -c 1000  /path/to/invoice.macaroon `
  * Generic:  [http://tomeko.net/online_tools/file_to_hex.php?lang=en](http://tomeko.net/online_tools/file_to_hex.php?lang=en)
  
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
  * lightningPay_conf.php
  * lightningPay.php
  * lightningPay.js
  * lightningPay.css
  * lightningPay_light.css (Optional)
* Edit 
  * `lightningPay_conf.php`. This is where you enter the HEX version of your _invoice.macaroon_.
  * the _CHANGE ME_ section of `lightningPay.js`.

## How to test ##
Use your browser to visit these URLs:

* `https://your.web.server/path/StoreCheckout.php`
* `https://your.web.server/path/StoreCheckout.php?order_id=100`
* `https://your.web.server/path/StoreCheckout.php?testnet=1`
* `https://your.web.server/path/StoreCheckout.php?testnet=1&order_id=100`

or you can check my test sites here:

(_https_ not used as this is hosted on a free web server without SSL certificates. You will not be entering any sensitive data.)

* [mainnet (USD 80.00)](http://raspibolt.epizy.com/LP/StoreCheckout.php)
* [mainnet (USD 0.10)](http://raspibolt.epizy.com/LP/StoreCheckout.php?order_id=100)
* [testnet (USD 80.00)](http://raspibolt.epizy.com/LP/StoreCheckout.php?testnet=1)
* [testnet (USD 0.10)](http://raspibolt.epizy.com/LP/StoreCheckout.php?testnet=1&order_id=100)

## How to Use ##
Copy the contents of the head tag from `lightningPay.php` into the head section of the HTML file you want to show LightningPay in. The div below the head tag is LightningPay itself. Paste it into any place in the already edited HTML file on your server.


There is a light theme available for LightningPay. If you want to use it, uncomment this line in your lightningPay.php file:

```
<link rel="stylesheet" href="lightningPay_light.css">
```

**Do not use LightningPay on XHTML** sites. That causes some weird scaling issues.

That's it! The only things you need to take care of is keeping the LND node and web server online. LightningPay will take care of everything else.
