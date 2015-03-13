<?php
require_once DIR_SYSTEM.'helper/expressly.php';

/**
 * Expressly migrator for OpenCart
 * 
 * @author Expressly Limited
 *
 */
class ControllerModuleExpresslymigrator extends Controller {
	
	/**
	 * Gets a customer name by it's e-mail address
	 */
	public function getUserName() {
		$this->load->model('expressly/migrator');
	
		if ($this->model_expressly_migrator->isAuthorizedRequest(getallheaders())) {
			$this->load->model('account/customer');
			
			if(isset($this->request->get['user_email'])) {
				$customer = $this->model_account_customer->getCustomerByEmail($this->request->get['user_email']);
				
				if($customer != null) {
					$this->response->setOutput($customer['firstname']);
				} else {
					header("HTTP/1.0 204 No Content");
				}
			} else {
				header("HTTP/1.0 204 No Content");
			}
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	/**
	 * Loads the popup content
	 */
	public function getPopupContent() {
		$popupUrl = $this->isMobile() ? ExpresslyHelper::POPUP_MOBILE_URL : ExpresslyHelper::POPUP_URL;
		
		$paramtersToServlet = array (
				'data' => $this->request->get ['data']
		);
		
		$paramtersToPopup = array ();
		
		$options = array (
				'http' => array (
						'header' => "Content-type: application/x-www-form-urlencoded\r\n",
						'method' => 'GET',
						'ignore_errors' => true
				)
		);
		
		$context = stream_context_create($options);
		
		$userNameAndCouponCode = file_get_contents(ExpresslyHelper::SERVLET_URL."/getUserName?".http_build_query($paramtersToServlet), false, $context);
		$popupContent = file_get_contents($popupUrl.http_build_query($paramtersToPopup), false, $context);
		
		if($userNameAndCouponCode != "" && strpos($userNameAndCouponCode, "|") !== false) {
			$this->load->model('expressly/migrator');
			$responseArray = explode("|", $userNameAndCouponCode);
			
			$couponDiscount = $this->model_expressly_migrator->getCouponDiscount($responseArray[1]);
			
			$popupContent = str_replace("Customer", $responseArray[0], $popupContent);
			$popupContent = str_replace("discount", $couponDiscount, $popupContent);
		}
		
		$this->response->setOutput($popupContent);
	}
	
	/**
	 * Deletes an user by email
	 * Used by the servlet, to delete the test users
	 */
	public function deleteUserByMail() {
		$this->load->model ( 'expressly/migrator' );
		
		if ($this->model_expressly_migrator->isAuthorizedRequest ( getallheaders () )) {
			$this->load->model ( 'account/customer' );
			
			$customer = $this->model_account_customer->getCustomerByEmail ( $this->request->get ['user_mail'] );
			$this->model_expressly_migrator->deleteCustomer ( $customer ['customer_id'] );
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	/**
	 * Updates the post checkout content.
	 */
	public function updatePostCheckout() {
		$this->load->model ( 'expressly/migrator' );
		if ($this->model_expressly_migrator->isAuthorizedRequest ( getallheaders () )) {
			$this->model_expressly_migrator->updatePostCheckoutBoxEnabled ( $this->request->get ['post-checkout-box'] );
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	/**
	 * Updates the redirect to checkout content.
	 */
	public function updateRedirectEnabled() {
		$this->load->model ( 'expressly/migrator' );
		if ($this->model_expressly_migrator->isAuthorizedRequest ( getallheaders () )) {
			$this->model_expressly_migrator->updateRedirectEnabled($this->request->get['redirect-enabled']);
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	/**
	 * Updates the redirect to login content.
	 */
	public function updateRedirectToLogin() {
		$this->load->model ( 'expressly/migrator' );
		if ($this->model_expressly_migrator->isAuthorizedRequest ( getallheaders () )) {
			$this->model_expressly_migrator->updateRedirectToLoginEnabled ( $this->request->get ['redirect-to-login'] );
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	/**
	 * Index method
	 */
	public function index() {
		$this->load->model ( 'expressly/migrator' );
		
		// set title of the page
		$this->document->setTitle ( "Expressly" );
		
		// define template file
		if (file_exists ( DIR_TEMPLATE . $this->config->get ( 'config_template' ) . '/template/module/expresslymigrator.tpl' )) {
			$this->template = $this->config->get ( 'config_template' ) . '/template/module/expresslymigrator.tpl';
		} else {
			$this->template = 'default/template/module/expresslymigrator.tpl';
		}
		
		// define children templates
		$this->children = array (
				'common/column_left',
				'common/column_right',
				'common/content_top',
				'common/content_bottom',
				'common/footer',
				'common/header' 
		);
		
		$this->document->addScript ( 'admin/view/javascript/expresslymigrator.js' );
		$this->document->addScript ( 'catalog/view/javascript/popupbox.js' );
		
		$this->data ['base'] = $this->getBaseUrl();
		$this->data ['isRedirectEnabled'] = $this->model_expressly_migrator->isRedirectEnabled();
		$this->data ['isRedirectToLoginEnabled'] = $this->model_expressly_migrator->isRedirectToLoginEnabled();
		$this->data['redirectDestination'] = $this->model_expressly_migrator->getRedirectDestination();
		
		// call the "View" to render the output
		$this->response->setOutput ( $this->render () );
	}
	
	/**
	 * Gets a customer by it's e-mail address
	 */
	public function getUser() {
		$this->load->model('expressly/migrator');
		
		if ($this->model_expressly_migrator->isAuthorizedRequest(getallheaders())) {

			$responseObject = array();
			$addresses = array();
			
			$this->load->model('account/customer');
			
			// For compatibility.
			$customer = array();
			
			if(isset($this->request->get['user_email'])) {
				$customer = $this->model_account_customer->getCustomerByEmail($this->request->get ['user_email']);
				$customer['password'] = "0";
				$customer['salt'] = "0";
			}
			
			if ($customer != null && $customer != "") {
				foreach($this->model_expressly_migrator->getAddresses($customer['customer_id']) as $id => $address) {
					$addresses[] = $address;
				}
			}
			
			$responseObject ['customer'] = $customer;
			$responseObject ['addresses'] = $addresses;
			
			$this->response->setOutput(json_encode($responseObject));
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	/**
	 * Stores the user by the given parameter
	 */
	public function storeUser() {
		$this->load->model ( 'expressly/migrator' );
		
		if ($this->model_expressly_migrator->isAuthorizedRequest ( getallheaders () )) {
			$this->load->model ( 'account/customer' );
			$this->load->model ( 'checkout/coupon' );
			
			if (isset ( $this->request->post ['parameter'] )) {
				$requestObject = json_decode ( html_entity_decode ( $this->request->post ['parameter'] ), true );
				
				$customerInRequest = $requestObject ['customer'];
				$addressesInRequest = $requestObject ['addresses'];
				$couponCodeInRequest = isset ( $requestObject ['coupon_code'] ) ? $requestObject ['coupon_code'] : null;
				
				if ($this->model_account_customer->getCustomerByEmail ( $customerInRequest ['email'] ) == null) {
					
				    if(!isset($customerInRequest['status']) || $customerInRequest['status'] == null || $customerInRequest['status'] == "") {
				        $customerInRequest['status'] = 1;
				        $customerInRequest['password'] = "0";
				        $customerInRequest['salt'] = "0";
				    }
				    
					$this->model_expressly_migrator->addCustomer($customerInRequest);
					$newUser = $this->model_account_customer->getCustomerByEmail ( $customerInRequest ['email'] );
					
					foreach ( $addressesInRequest as $address ) {
							if ($address ['country_id'] == "") {
								if ($address ['iso_code_2'] != "") {
									$country = $this->model_expressly_migrator->getCountryByIsoCode2 ( $address ['iso_code_2'] );
									
									if ($country != null && $country != "") {
										$address ['country_id'] = $country ['country_id'];
										$address ['country'] = $country ['name'];
										$address ['iso_code_3'] = $country ['iso_code_3'];
										$address ['address_format'] = $country ['address_format'];
										
										if (isset($address ['zone']) && $address ['zone'] != null && $address ['zone'] != "") {
											$zone = $this->model_expressly_migrator->getZoneByCountryIdAndName ( $country ['country_id'], $address ['zone'] );
											if ($zone != null && $zone != "") {
												$address ['zone_id'] = $zone ['zone_id'];
												$address ['zone_code'] = $zone ['code'];
											}
										}
									}
								}
							}
							
							$this->model_expressly_migrator->addAddress ( $newUser ['customer_id'], $address );
					}
					
					$this->response->setOutput($newUser['customer_id']);
				} else {
					$this->response->setOutput ( "This customer email already exists" );
				}
			}
		} else {
			header("HTTP/1.0 401 Unauthorized");
		}
	}
	
	/**
	 * Endpoint for the migration logic.
	 */
	public function migration() {
		$status = "";
		$returnContent = "";
		
		$paramters = array (
				'data' => $this->request->get ['data'] 
		);
		
		$options = array (
				'http' => array (
						'header' => "Content-type: application/x-www-form-urlencoded\r\nReferer: " . $this->getBaseUrl () . "\r\n",
						'method' => 'GET',
						'ignore_errors' => true 
				) 
		);
		
		$context = stream_context_create ( $options );
		$content = file_get_contents(ExpresslyHelper::SERVLET_URL . "/newmigration?" . http_build_query ( $paramters ), false, $context );
		
		foreach ( $http_response_header as $header ) {
			$headerparts = explode ( ":", $header );
			
			if (count ( $headerparts ) == 1) {
				header ( $header );
				$statusParts = explode ( " ", $header );
				$status = $statusParts [1];
				break;
			}
		}
		
		if (intval ( $status ) == 200) {
			$this->load->model ( 'checkout/coupon' );
			$responseArray = explode ( "|", $content );
			
			$customer = $this->loginUser ($responseArray[0], $responseArray[1], $responseArray[2]);
			
			$this->sendPasswordResetMail($customer['email']);
			
			if($customer != null && isset($this->request->get['subscribeNewsLetter']) && $this->request->get['subscribeNewsLetter'] == "true") {
				$this->load->model('expressly/migrator');
				$this->model_expressly_migrator->editNewsletter(1, $customer['customer_id']);
			}
		} else {
			$returnContent = $content;
		}
		
		$this->response->setOutput($returnContent);
	}
	
	/**
	 * Adds a product and a coupon to the cart.
	 */
	public function addProductAndCoupon() {
		$productId = $this->request->get ['product_id'];
		$couponCode = $this->request->get ['coupon_code'];
		$userEmail = $this->request->get ['user_email'];
		
		$this->load->model ( 'account/customer' );
		$customer = $this->model_account_customer->getCustomerByEmail($userEmail);
		
		$this->addProductAndCouponToCart($productId, $couponCode, $customer['customer_id']);
	}
	
	/**
	 * Checks if the given user has any orders.
	 */
	public function checkUserHasAnyOrder() {
		$this->load->model('expressly/migrator');
		$this->load->model('account/customer');
		
		if ($this->model_expressly_migrator->isAuthorizedRequest(getallheaders())) {
			$customer = $this->model_account_customer->getCustomerByEmail($this->request->get['user_email']);
            
            if($customer != null && $customer != "") {
			  echo $this->model_expressly_migrator->hasAnyOrders($customer['customer_id']) ? 1 : 0;
			} else {
			    echo 0;
			}
		}
	}
	
	/**
	 * Log the current user in.
	 */
	private function loginUser($userId, $productId, $couponCode) {
		$this->load->model ( 'account/customer' );
		$customer = $this->model_account_customer->getCustomer ( $userId );
		$this->customer->login ( $customer ['email'], '', true );
		
		$this->addProductAndCouponToCart($productId, $couponCode, $userId);
		
		return $customer;
	}
	
	/**
	 * Adds a product and a coupon to the cart.
	 * 
	 * @param $productId is
	 *        	the product id
	 * @param $couponCode is
	 *        	the coupon code.
	 * @param $userEmail is
	 *        	the email address of the user
	 */
	private function addProductAndCouponToCart($productId, $couponCode, $userId) {
		$this->load->model('expressly/migrator');
		
		if(!$this->model_expressly_migrator->hasAnyOrders($userId)) {
			if ($productId != null || $couponCode != null) {
				$this->cart->clear ();
				$this->cart->add ( $productId, 1, null );
				
				if ($couponCode != null) {
					$this->session->data ['coupon'] = $couponCode;
				}
			}
		}
	}
	
	/**
	 * Gets the base URL of the system
	 */
	private function getBaseUrl() {
		$baseUrl;
		if (isset ( $this->request->server ['HTTPS'] ) && (($this->request->server ['HTTPS'] == 'on') || ($this->request->server ['HTTPS'] == '1'))) {
			$baseUrl = $this->config->get ( 'config_ssl' );
		} else {
			$baseUrl = $this->config->get ( 'config_url' );
		}
		
		return $baseUrl;
	}
	
	/**
	 * Sends the password reset e-mail
	 */
	private function sendPasswordResetMail($userMail) {
		$this->language->load('account/forgotten');
		$this->language->load('mail/forgotten');
		$this->language->load('expressly/migrator');
		
		$password = substr(sha1(uniqid(mt_rand(), true )), 0, 10);
		
		$this->model_account_customer->editPassword($userMail, $password);
		
		$subject = sprintf($this->language->get('text_subject'), $this->config->get('config_name'));
		
		$message = sprintf($this->language->get('password_reset_text_line_1'), $this->config->get('config_name'))."\n\n";
		$message .= sprintf($this->language->get('password_reset_text_line_2'))."\n\n";
		$message .= $password;
		
		$mail = new Mail ();
		$mail->protocol = $this->config->get('config_mail_protocol');
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->hostname = $this->config->get('config_smtp_host');
		$mail->username = $this->config->get('config_smtp_username');
		$mail->password = $this->config->get('config_smtp_password');
		$mail->port = $this->config->get('config_smtp_port');
		$mail->timeout = $this->config->get('config_smtp_timeout');
		$mail->setTo($userMail);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($this->config->get('config_name'));
		$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
		$mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
		$mail->send();
		
	}
	
	/**
	 * Checks if the user agent is mobile or not.
	 * @return boolean
	 */
	private function isMobile() {
		$useragent=$_SERVER['HTTP_USER_AGENT'];
		return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4));
	}
}

?>
