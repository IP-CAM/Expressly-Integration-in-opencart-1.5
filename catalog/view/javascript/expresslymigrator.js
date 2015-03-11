var newCustomerName = "";
var newDiscount = "";

/**
 * Creates a CORS request
 */
function createCORSRequest(method, url) {
	var xhr = new XMLHttpRequest();
	
	if (typeof XDomainRequest != "undefined") {
		xhr = new XDomainRequest();
		xhr.open(method, url);
	} else if ("withCredentials" in xhr) {
		xhr.open(method, url, true);
	} else {
		xhr = null;
	}
	return xhr;
}

/**
 * Creates a call
 */
function createCall(method, url, callbackOnSuccess, callbackOnFail, authHeader, postMarameters) {
	var xhr = createCORSRequest(method, url);

	if (!xhr) {
		throw new Error('CORS not supported');
	}

	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	if(authHeader) {
		xhr.setRequestHeader('Authorization', authHeader);
	}
	
	xhr.onload = function() {
		callbackOnSuccess(xhr);
	};

	xhr.onerror = function() {
		callbackOnFail(xhr);
	};

	if(postMarameters) {
		xhr.send(postMarameters);
	} else {
		xhr.send();
	}
}

/**
 * Starts the migration process.
 */
function expresslyTrigger() {
	
	var hashParameters = location.href.split("#")[1];

	if (hashParameters) {
		
		createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/migration&data=" + encodeURIComponent(hashParameters), function(xhr) {
			var responseText = xhr.responseText;
			if (xhr.readyState == 4 && xhr.status == 200) {

				var responseArray = xhr.responseText.split(";");
				
				// Used by the offer frame logic to update the content.
				document.body.innerHTML += '<input type="hidden" id="expresslyCr" name="expresslyCr" value="' + responseArray[0] + '"/>';
				
				newCustomerName = responseArray[0];
				newDiscount = responseArray[1];

				hideWhiteOverlay();
			} else if(xhr.readyState == 4 && xhr.status == 204) {
				hideWhiteOverlay();
				alert("Migration error - User does not exist in A");
			} else if(xhr.readyState == 4 && xhr.status == 409) {
				if(isRedirectToLoginEnabled) {
					var loginDataArray = xhr.responseText.split("|");
					setCookie("expresslylogindata", loginDataArray[0]);
					
					createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/addProductAndCoupon&user_email="+loginDataArray[0]+"&product_id="+loginDataArray[1]+"&coupon_code="+loginDataArray[2], function() {
						hideWhiteOverlay();
						alert("You already have an account here.");
						
						window.location.replace(baseUrl + "index.php?route=account/login");
			        });
				} else {
					hideWhiteOverlay();
					alert("You already have an account here.");
				}
			} else {
				hideWhiteOverlay();
				alert("Migration fail");
			}
		}, function(xhr) {
			hideWhiteOverlay();
			alert("Migration fail");
		});
	}
}

$(document).ready(function(){
	if(window.location.hash != "") {
		popupOpen();
	}
	
	expresslyTrigger();
});


/**
 * Event handler for iframe loaded
 */
function offerIframeLoaded() {
	if(newCustomerName != "" && newDiscount != "") {
		document.getElementById("expresslyOfferFrame").contentWindow.postMessage('updateUserData:' + newCustomerName + ';' + newDiscount + '%', '*');
	}
}

/**
 * Redirects to the checkout page
 */
function redirectToCheckout() {
	if(isRedirectToCheckoutEnabled) {
		window.location.replace(baseUrl + "index.php?route=checkout/checkout");
	} else {
		window.location.replace(baseUrl);
	}
}

/**
 * Sets a cookie
 * @param name is the cookie name
 * @param value is the cookie value
 */
function setCookie(name, value) {
	var d = new Date();
	d.setTime(d.getTime() + (10 * 1000));
	var expires = "expires=" + d.toUTCString();
	document.cookie = name + "=" + value + "; " + expires;
}
