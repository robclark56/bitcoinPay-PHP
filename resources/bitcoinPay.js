// bitcoinPay.js
//  Javascript for bitcoinPay (see: https://github.com/robclark56/bitcoinPay-PHP)
//

///////// CHANGE ME ////////////
// e.g. If bitcoinPay.php is available at   https://my.domain/bitcoinPay/bitcoinPay.php, 
//      Then set UrlFilePath to: "/bitcoinPay/bitcoinPay.php"
var UrlFilePath =  "/bitcoinPay/bitcoinPay.php";
///////// END CHANGE ME ////////


var requestUrl = window.location.protocol + "//" + window.location.hostname + UrlFilePath;
var bitcoinLogo = '&#x20bf;';

// To prohibit multiple requests at the same time
var running = false;

var invoice;
var qrCode;
var defaultGetInvoice;

// Data capacities for QR codes with mode byte and error correction level L (7%)
// Shortest invoice: 194 characters
// Longest invoice: 1223 characters (as far as I know)
var qrCodeDataCapacities = [
    {"typeNumber": 9, "capacity": 230},
    {"typeNumber": 10, "capacity": 271},
    {"typeNumber": 11, "capacity": 321},
    {"typeNumber": 12, "capacity": 367},
    {"typeNumber": 13, "capacity": 425},
    {"typeNumber": 14, "capacity": 458},
    {"typeNumber": 15, "capacity": 520},
    {"typeNumber": 16, "capacity": 586},
    {"typeNumber": 17, "capacity": 644},
    {"typeNumber": 18, "capacity": 718},
    {"typeNumber": 19, "capacity": 792},
    {"typeNumber": 20, "capacity": 858},
    {"typeNumber": 21, "capacity": 929},
    {"typeNumber": 22, "capacity": 1003},
    {"typeNumber": 23, "capacity": 1091},
    {"typeNumber": 24, "capacity": 1171},
    {"typeNumber": 25, "capacity": 1273}
];

// TODO: solve this without JavaScript
// Fixes weird bug which moved the button up one pixel when its content was changed
window.onload = function () {
    var button = document.getElementById("bitcoinPayGetInvoice");
    button.style.height = (button.clientHeight + 1) + "px";
    button.style.width = (button.clientWidth + 1) + "px";
};

var wallet= getVal('wallet');  
console.log('wallet = ' + wallet); 
if ( wallet !== null ) {
	requestUrl = requestUrl + '?wallet=' + wallet;
	console.log('requestUrl = ' + requestUrl ); 
}

// TODO: show invoice even if JavaScript is disabled
// TODO: fix scaling on phones
// TODO: show price in dollar?
function getPayReq() {
    if (running === false) {
        running = true;

        var payValue = document.getElementById("bitcoinPayAmount");
        
 
	if (payValue.value !== "") {
		if (!isNaN(payValue.value)) {
		var request = new XMLHttpRequest();

		request.onreadystatechange = function () {
			if (request.readyState === 4) {
			 console.log("RESPONSE: " + request.responseText);
                         try {
                            var json = JSON.parse(request.responseText);
                            if (request.status === 200 && json.Error === null) {
                                console.log("Got invoice: " + json.Invoice);
                                console.log("Invoice expires in: " + json.Expiry);
                                console.log("Starting listening for invoice to get settled");							
                                listenInvoiceSettled(json.Address,json.BTC,json.Memo);
                                invoice = json.Invoice;

                                // Update UI
                                var wrapper = document.getElementById("bitcoinPay");
                                wrapper.innerHTML = "<a>Please make this Bitcoin Payment</a>";
                                wrapper.innerHTML += "<input type='text' class='bitcoinPayInput' id='bitcoinPayInvoice' onclick='copyInvoiceToClipboard()' value='" + invoice + "' readonly>";
                                wrapper.innerHTML += "<div id='bitcoinPayQR'></div>";
                                wrapper.innerHTML += "<div id='bitcoinPayTools'>" +
                                    "<button class='bitcoinPayButton' id='bitcoinPayCopy' onclick='copyInvoiceToClipboard()'>Copy</button>" +
                                    "<button class='bitcoinPayButton' id='bitcoinPayOpen'>Open</button>" +
                                    "<a id='bitcoinPayExpiry'></a>" +
                                    "</div>";
                                starTimer(json.Expiry, document.getElementById("bitcoinPayExpiry"));

                                // Fixes bug which caused the content of #bitcoinPayTools to be visually outside of #bitcoinPay
                                document.getElementById("bitcoinPayTools").style.height = document.getElementById("bitcoinPayCopy").clientHeight + "px";
                                document.getElementById("bitcoinPayOpen").onclick = function () {
                                    location.href = json.Invoice;
                                };
                                showQRCode();
                                running = false;
                            } else {
                                showErrorMessage(json.Error);
                            }
                         } catch (exception) {
                            console.error(exception);
                            showErrorMessage("Failed to reach backend");
                        }
                    }
                };
                console.log('RequestURL = ' + requestUrl );
                request.open("POST", requestUrl , true);
		request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		var memo = document.getElementById("bitcoinPayMessage").value;
		var currency = document.getElementById("bitcoinPayCurrency").value;
		var currencyAmount = document.getElementById("bitcoinPayCurrencyAmount").value;
		var callback = document.getElementById("bitcoinPayCallback").value;		
                var params = "Action=getinvoice&amount=" + parseInt(payValue.value) + "&message=" + encodeURIComponent(memo);
                params += '&currencyAmount=' + parseFloat(currencyAmount);
                params += '&currency=' + encodeURIComponent(currency);
                params += '&callback=' + encodeURIComponent(callback);
		console.log(params);
		request.send(params);				
                var button = document.getElementById("bitcoinPayGetInvoice");
                defaultGetInvoice = button.innerHTML;
                button.innerHTML = "<div class='spinner'></div>";
            } else {
                showErrorMessage("Payment amount must be a number");
            }
        } else {
            showErrorMessage("No amount set");
        }

    } else {
        console.warn("Last request still pending");
    }
}

function listenInvoiceSettled(address,BTC,memo) {
	var interval = setInterval(function () {
	var request = new XMLHttpRequest();
		
	//Prevent multiple calls for same invoice settled over slow networks.
	var IsSettled = false;
	if ( IsSettled == true) {
	 return;
	}
	console.log('listenInvoiceSettled BTC:' + BTC + ' Address:' + address  + 'Memo:' + memo);
	request.onreadystatechange = function () {
		if (request.readyState === 4 && request.status === 200) {
		  console.log("RESPONSE: " + request.responseText);
		  var json = JSON.parse(request.responseText);
		  console.log('settled = ' + json.settled);
		  if (json.settled) {
			console.log("Invoice settled with " + json.confirmations + " confirmations");
			IsSettled = true;
			clearInterval(interval);
			showThankYouScreen();
		  }
		}
	};
            	
	request.open("POST", requestUrl , true);
	request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	var params = "Action=checksettled&btc=" + BTC + "&address=" + encodeURIComponent(address) + "&memo=" + encodeURIComponent(memo)  ;
	//console.log('PARAMS:' + params);
	request.send(params);

        }, 10000);

}

function showThankYouScreen() {
    var wrapper = document.getElementById("bitcoinPay");
    wrapper.innerHTML  = '<p id="bitcoinPayLogo">' + bitcoinLogo + '</p>';
    wrapper.innerHTML += '<a id="bitcoinPayFinished">Payment Notification Received</a>';
    wrapper.innerHTML += '<p>Thank you!</p>';
    wrapper.innerHTML += '<p>You can close this window as we wait for the Payment Confirmations.<p>';
}

function starTimer(duration, element) {
    showTimer(duration, element);

    var interval = setInterval(function () {
        if (duration > 1) {
            duration--;
            showTimer(duration, element);
        } else {
            showExpired();
            clearInterval(interval);
        }

    }, 1000);

}

function showTimer(duration, element) {
    var seconds = Math.floor(duration % 60);
    var minutes = Math.floor((duration / 60) % 60);
    var hours = Math.floor((duration / (60 * 60)) % 24);

    seconds = addLeadingZeros(seconds);
    minutes = addLeadingZeros(minutes);

    if (hours > 0) {
        element.innerHTML = hours + ":" + minutes + ":" + seconds;

    } else {
        element.innerHTML = minutes + ":" + seconds;
    }

}

function showExpired() {
    var wrapper = document.getElementById("bitcoinPay");
    wrapper.innerHTML  = '<p id="bitcoinPayLogo">' + bitcoinLogo + '</p>';
    wrapper.innerHTML += '<a id="bitcoinPayFinished">Your payment request expired!</a>';
}

function addLeadingZeros(value) {
    return ("0" + value).slice(-2);
}

function showQRCode() {
    var element = document.getElementById("bitcoinPayQR");

    createQRCode();

    element.innerHTML = qrCode;
    var size = document.getElementById("bitcoinPayInvoice").clientWidth + "px";
    var qrElement = element.children[0];
    qrElement.style.height = size;
    qrElement.style.width = size;
}

function createQRCode() {
    var invoiceLength = invoice.length;

    // Just in case an invoice bigger than expected gets created
    var typeNumber = 26;
    for (var i = 0; i < qrCodeDataCapacities.length; i++) {
        var dataCapacity = qrCodeDataCapacities[i];
        if (invoiceLength < dataCapacity.capacity) {
            typeNumber = dataCapacity.typeNumber;
            break;
        }

    }

    console.log("Creating QR code with type number: " + typeNumber);

    var qr = qrcode(typeNumber, "L");

    qr.addData(invoice);
    qr.make();

    qrCode = qr.createImgTag(6, 6);
}

function copyInvoiceToClipboard() {
    var element = document.getElementById("bitcoinPayInvoice");

    element.select();
    document.execCommand('copy');
    console.log("Copied invoice to clipboard");
}

function showErrorMessage(message) {
    running = false;
    console.error(message);
    var error = document.getElementById("bitcoinPayError");
    error.parentElement.style.marginTop = "0.5em";
    error.innerHTML = message;
    var button = document.getElementById("bitcoinPayGetInvoice");

    // Only necessary if it has a child (div with class spinner)
    if (button.children.length !== 0) {
        button.innerHTML = defaultGetInvoice;
    }

}

function divRestorePlaceholder(element) {
    // <br> and <div><br></div> mean that there is no user input
    if (element.innerHTML === "<br>" || element.innerHTML === "<div><br></div>") {
        element.innerHTML = "";
    }
}

function getVal(str) {
    var v = window.location.search.match(new RegExp('(?:[\?\&]'+str+'=)([^&]+)'));
    return v ? v[1] : null;
}

function updateBTC(exchRate) {
    var fiat = document.getElementById('bitcoinPayFiatInput').value;
    var BTC  = fiat/exchRate;
    var MSG  = document.getElementById('bitcoinPayMessage').value;
    var INV  = document.getElementById('bitcoinPayGetInvoice');

    if((fiat) && (MSG)&&(MSG.trim()!='')){
        INV.style.visibility='visible';        
    } else {
        INV.style.visibility='hidden';     
    }
    document.getElementById('bitcoinPayBTC').value = BTC.toFixed(8);
    document.getElementById('bitcoinPayAmount').value = BTC.toFixed(8) * 100000000;
    document.getElementById('bitcoinPayCurrencyAmount').value = fiat;
}
