/**
 * Creates a call
 */
function createCall(method, url, callbackOnSuccess, callbackOnFail, authHeader, postMarameters) {
	var xhr = new XMLHttpRequest();
	xhr.open(method, url, true);
	
	if (!xhr) {
		throw new Error('Failed to create AJAX request');
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
		document.querySelector("#expressly_popup_page .cancel").style.display = "none";
		document.querySelector("#expressly_popup_page .ok").style.display = "none";
		document.querySelector("#expressly_popup_page .expressly_loader").style.display = "block";
		
		var subscribeCheckbox = document.getElementById('subscribeNewsletter');
		var subscribe = subscribeCheckbox ? subscribeCheckbox.checked : true;
		
		createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/migration&data=" + hashParameters + "&subscribeNewsLetter=" + subscribe, function(xhr) {
			var responseText = xhr.responseText;
			if (xhr.readyState == 4 && xhr.status == 200) {
				redirectUser();
			} else if(xhr.readyState == 4 && xhr.status == 204) {
				alert("Migration error - User does not exist in A");
			} else if(xhr.readyState == 4 && xhr.status == 409) {
				if(isRedirectToLoginEnabled) {
					var loginDataArray = xhr.responseText.split("|");
					setCookie("expresslylogindata", loginDataArray[0]);
					
					createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/addProductAndCoupon&user_email="+loginDataArray[0]+"&product_id="+loginDataArray[1]+"&coupon_code="+loginDataArray[2], function() {
						alert("You are already registered. Please login with you username and password.");
						window.location.replace(baseUrl + "index.php?route=account/login");
			        });
				} else {
					alert("You are already registered. Please login with you username and password.");
				}
			} else if(xhr.readyState == 4 && xhr.status == 500) {
				if(xhr.responseText != "") {
					alert("Oops, something went wrong on our side. You can still access the amazing offer with this coupon code " + xhr.responseText + ".");
				} else {
					alert("Oops, something went wrong on our side. We are working hard to fix it. You can still shop at this website.");
				}
				document.querySelector("#expressly_popup_page .cancel").style.display = "block";
				document.querySelector("#expressly_popup_page .expressly_loader").style.display = "none";
			} else {
				alert("Oops, something went wrong on our side. We are working hard to fix it. You can still shop at this website.");
				document.querySelector("#expressly_popup_page .cancel").style.display = "block";
				document.querySelector("#expressly_popup_page .expressly_loader").style.display = "none";
			}
		}, function(xhr) {
			alert("Oops, something went wrong on our side. We are working hard to fix it. You can still shop at this website.");
			document.querySelector("#expressly_popup_page .cancel").style.display = "block";
			document.querySelector("#expressly_popup_page .expressly_loader").style.display = "none";
		});
	}
}

$(document).ready(function(){
	if(window.location.hash != "") {
		popupOpen();
	}
});

/**
 * Redirects the user to the desired page
 */
function redirectUser() {
	if(isRedirectEnabled) {
		window.location.replace(baseUrl + redirectDestination);
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

/**
 * Opens the terms and conditions window.
 */
function openTerms() {
	window.open(baseUrl + "terms-and-conditions");
}

/**
 * Opens the privacy-policy
 */
function openPrivacy() {
	window.open(baseUrl + "privacy-policy");
}
