/**
 * Expressly module administration related JavaScript logic
 */
var loadingMessage = "&nbsp;....................................................................................";
var postData = 'parameter={"customer":{"customer_id":"1","store_id":"0","firstname":"test","lastname":"test","email":"endpoint.test@buyexpressly.com","telephone":"test","fax":"","password":"dadf3ad86be88565ea6642be4fb00d4d87d62cba","salt":"63193aff4","cart":"a:0:{}","wishlist":"","newsletter":"0","address_id":"1","customer_group_id":"1","ip":"127.0.0.1","status":"1","approved":"1","token":"","date_added":"2014-12-12 10:15:52"},"addresses":[{"address_id":"1","firstname":"test","lastname":"test","company":"","company_id":"","tax_id":"","address_1":"test","address_2":"","postcode":"test","city":"test","zone_id":"3513","zone":"Aberdeen","zone_code":"ABN","country_id":"222","country":"United Kingdom","iso_code_2":"GB","iso_code_3":"GBR","address_format":""},{"address_id":"6","firstname":"aaa","lastname":"sss","company":"","company_id":"","tax_id":"","address_1":"asdas","address_2":"","postcode":"ads","city":"asd","zone_id":"83","zone":"Constantine","zone_code":"CON","country_id":"3","country":"Algeria","iso_code_2":"DZ","iso_code_3":"DZA","address_format":""}]}';
var crossIco = '<img src="view/image/cross.png">';
var tickIco = '<img src="view/image/tick.png">';

// TODO: fix calls after security.

/**
 * Shows the content of the desired how to fix div
 */
function showHowToFixContent(element) {
	document.querySelector('#' + element + " .howtofix_content").style.display = "inline";
}

/**
 * Runs the endpoint tests.
 */
function runEndpointTests() {
	checkSelfStoreUserEndpount();
	checkServletEndpoints();
}

/**
 * Checks the store user endpoint
 */
function checkSelfStoreUserEndpount() {
	document.querySelector('#checkStep1').innerHTML += loadingMessage;
	// Needs to embedd the calls inside eachother to create a sequence
	// Check store user endpoint
	createCall("POST", baseUrl + "index.php?route=module/expresslymigrator/storeUser", function(data) {
		
		var newUserId = data.responseText.split("|")[0];
		
		if(data.readyState == 4 && data.status == 200 && !isNaN(newUserId)) {
			createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/deleteUserByMail&user_mail=endpoint.test@buyexpressly.com", function(data) {
				document.querySelector('.modulechecstep_1_result').innerHTML = tickIco;
				checkSelfUserInformationEndpoint();
			}, function () {}, "Expressly " + modulePass);
		} else {
			document.querySelector('.modulechecstep_1_result').innerHTML = crossIco;
			document.querySelector('#modulechecstep_1_howtofix').style.display = "inline";
		}
	}, function () {}, "Expressly " + modulePass, postData);
}

/**
 * Checks the get user information endpoint.
 */
function checkSelfUserInformationEndpoint() {
	var getUserInfoResults = new Array();
	var anyFailedUserInfoTests = false;
	
	document.querySelector('#checkStep2').innerHTML += loadingMessage;
		
	// Check the get user info endpoint
	// Case 1: Check endpoint without token
	createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/getUser&user_id=", function(data) {
		// Endpoint should not be accessible without a token
		getUserInfoResults.push(data.readyState == 4 && data.status == 401);
			
		// Case 2: Check endpoint with token
		createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/getUser&user_id=", function(data) {

			getUserInfoResults.push(data.readyState == 4 && data.status == 200 && data.responseText == '{"customer":[],"addresses":[]}');
			
			// Case 3: check an actual get user info
			createCall("POST", baseUrl + "index.php?route=module/expresslymigrator/storeUser", function(data) {
				
				var newUserId = data.responseText.split("|")[0];
				
				if(data.readyState == 4 && data.status == 200 && !isNaN(newUserId)) {
					createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/getUser&user_email=endpoint.test@buyexpressly.com", function(data) {
						
						getUserInfoResults.push(data.responseText.indexOf('"customer_id":"' + newUserId + '"') > -1);
						
						createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/deleteUserByMail&user_mail=endpoint.test@buyexpressly.com", function(data) {
							document.querySelector('.modulechecstep_1_result').innerHTML = tickIco;
						}, function () {}, "Expressly " + modulePass);
						
						// Check the result
						for(var i = 0; i < getUserInfoResults.length; i++) {
							if(getUserInfoResults[i] == false) {
								anyFailedUserInfoTests = true;
								break;
							}
						}
						
						if(!anyFailedUserInfoTests) {
							document.querySelector('.modulechecstep_2_result').innerHTML = tickIco;
						} else {
							document.querySelector('.modulechecstep_2_result').innerHTML = crossIco;
							document.querySelector('#modulechecstep_2_howtofix').style.display = "inline";
						}
					}, function () {}, "Expressly " + modulePass);
				} else {
					document.querySelector('.modulechecstep_2_result').innerHTML = crossIco;
					document.querySelector('#modulechecstep_2_howtofix').style.display = "inline";
				}
			}, function () {}, "Expressly " + modulePass, postData);
		}, function () {}, "Expressly " + modulePass);
	});
}

/**
 * Checks the external endpoint
 */
function checkServletEndpoints() {
	document.querySelector('#checkStep5').innerHTML += loadingMessage;
	
	createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/migration&data", function (data) {
		if(data.readyState == 4 && data.status == 500) {
			document.querySelector('.modulechecstep_5_result').innerHTML = tickIco;
		} else {
			document.querySelector('.modulechecstep_5_result').innerHTML = crossIco;
			document.querySelector('#modulechecstep_5_howtofix').style.display = "inline";
		}
	}, function() {
		document.querySelector('.modulechecstep_5_result').innerHTML = crossIco;
		document.querySelector('#modulechecstep_5_howtofix').style.display = "inline";
	});
}

/**
 * Updates the postcheckout content appearance
 */
function updatePostCheckoutBox(selfElement, token) {
	createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/updatePostCheckout&post-checkout-box=" + selfElement.checked, function (data) {
	}, function () {}, "Expressly " + modulePass);
}

/**
 * Updates the redirect user option
 */
function updateRedirectEnabled(selfElement) {
	var textField = document.querySelector('#redirect-destination-field');
	textField.disabled = !textField.disabled;
	
	var testLink = document.querySelector('#userRedirectionTestLink');
	if(testLink.style.display == "none") {
		testLink.style.display = "inline";
	} else {
		testLink.style.display = "none";
	}
	
	createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/updateRedirectEnabled&redirect-enabled=" + selfElement.checked, function (data) {
	}, function () {}, "Expressly " + modulePass);
}

/**
 * Updates the redirect to login option
 */
function updateRedirectToLogin(selfElement, token) {
	createCall("GET", baseUrl + "index.php?route=module/expresslymigrator/updateRedirectToLogin&redirect-to-login=" + selfElement.checked, function (data) {
	}, function () {}, "Expressly " + modulePass);
}

/**
 * Tests the user redirection
 */
function testUserRedirection() {
	if(checkNewRedirectAddress()) {
		var destinationValue = document.querySelector('#redirect-destination-field').value;
		
		if(destinationValue.indexOf("http") > -1) {
			window.open(destinationValue);
		} else {
			window.open(baseUrl + destinationValue);
		}
	}
}

/**
 * Checks if the new redirect destination value is valid.
 * @returns {Boolean}
 */
function checkNewRedirectAddress() {
	var returnValue = true;
	var destinationValue = document.querySelector('#redirect-destination-field').value;
	
	if((destinationValue.indexOf("http") > -1 && destinationValue.indexOf(baseUrl) == -1) || destinationValue.indexOf("/") == 0) {
		alert("Please check the format of the redirect url you're pasting. It should be relative to " + baseUrl + ".");
		returnValue = false;
	}
	
	return returnValue;
}