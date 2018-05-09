# LightningTip-PHP
A simple way to accept tips via the Lightning Network on your website. 

If want to tip me you can use my LightningTip as below.
(Ignore any https certificate errors as this is hosted on a free webserver and you will not be entering any sensitive data.)
* [mainnet](https://raspibolt.epizy.com/LT/lightningTip.php)
* [testnet](https://raspibolt.epizy.com/LT/lightningTip.php?testnet=1)

<img src="https://i.imgur.com/0mOEgTf.gif" width="240">

## Credit ##
Kudos to [michael1011](https://github.com/michael1011/lightningtip) for the original [LightningTip](https://github.com/michael1011/lightningtip/blob/master/README.md). The difference between the two projects are shown in this table.

||LightningTip|LightningTip-PHP|
|--|--|--|
|Backend|An executable<br>(always running)|PHP|
|Email notification|Yes|Yes|
|lnd communication|gRPC|REST|
|testnet/mainnet selection|No|Yes|
|Keeps track<br>of tips?|Yes|No|

## Why PHP? ##
Installing an executable either on the lnd host, or on a 3rd party web host can be problematic. Using PHP improves portability and removes the need for a separate executable running as a service.
## Security ## 
The _invoice.macroon_ file limits the functionality available to LightningTip.php to only invoice related functions. Importantly, if someone steals your _invoice.macaroon_, they can NOT spend any of your funds.
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
* Download the [latest release](https://github.com/robclark56/lightningtip/releases), and unzip.
* From the _frontend_ folder: Upload these files to your webserver:
  * lightningTip.php
  * lightningTip.js
  * lightningTip.css
  * lightningTip_light.css (Optional)
* Edit the _CHANGE ME_ section of `lightningTip.php`. This is where you enter the HEX version of your _invoice.macaroon_.
* Edit the _CHANGE ME_ section of `lightningTip.js`.
* Copy the contents of the head tag from `lightningTip.php` into the head section of the HTML file you want to show LightningTip in. The div below the head tag is LightningTip itself. Paste it into any place in the already edited HTML file on your server.


There is a light theme available for LightningTip. If you want to use it **add** this to the head tag of your HTML file:

```
<link rel="stylesheet" href="lightningTip_light.css">
```

**Do not use LightningTip on XHTML** sites. That causes some weird scaling issues.

That's it! The only things you need to take care of is keeping the LND node and web server online. LightningTip will take care of everything else.

## How to run ##
Use your browser to visit either of these:

* `https://your.web.server/path/lightningTip.php`
* `https://your.web.server/path/lightningTip.php?testnet=1`


