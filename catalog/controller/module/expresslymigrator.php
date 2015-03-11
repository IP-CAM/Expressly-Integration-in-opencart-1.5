<?php

/**
 * Expressly migrator for OpenCart
 * 
 * @author Expressly Limited
 *
 */
class ControllerModuleExpresslymigrator extends Controller {

	const SERVLET_URL = "https://buyexpressly.com/expresslymod";
	
	/**
	 * Deletes an user by email
	 * Used by the servlet, to delete the test users
	 */
	public function deleteUserByMail() {
		$this->load->model('expressly/migrator');
		
		if($this->model_expressly_migrator->isAuthorizedRequest(getallheaders())){
			$this->load->model('account/customer');
			
			$customer = $this->model_account_customer->getCustomerByEmail($this->request->get['user_mail']);
			$this->model_expressly_migrator->deleteCustomer($customer['customer_id']);
		} else {
			http_response_code(401);
		}
	}
	
	/**
	 * Updates the post checkout content.
	 */
	public function updatePostCheckout() {
		$this->load->model('expressly/migrator');
		if($this->model_expressly_migrator->isAuthorizedRequest(getallheaders())){
			$this->model_expressly_migrator->updatePostCheckoutBoxEnabled($this->request->get['post-checkout-box']);
		} else {
			http_response_code(401);
		}
	}
	
	/**
	 * Updates the redirect to checkout content.
	 */
	public function updateRedirectToCheckout() {
		$this->load->model('expressly/migrator');
		if($this->model_expressly_migrator->isAuthorizedRequest(getallheaders())){
			$this->model_expressly_migrator->updateRedirectToCheckoutEnabled($this->request->get['redirect-to-checkout']);
		} else {
			http_response_code(401);
		}
	}
	
	/**
	 * Updates the redirect to login content.
	 */
	public function updateRedirectToLogin() {
		$this->load->model('expressly/migrator');
		if($this->model_expressly_migrator->isAuthorizedRequest(getallheaders())){
			$this->model_expressly_migrator->updateRedirectToLoginEnabled($this->request->get['redirect-to-login']);
		} else {
			http_response_code(401);
		}
	}
	
	
    /**
     * Index method
     */
    public function index() {
        $this->load->model('expressly/migrator');
        
        // set title of the page
        $this->document->setTitle("Expressly");
        
        // define template file
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/expresslymigrator.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/module/expresslymigrator.tpl';
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
        
        $this->document->addScript('catalog/view/javascript/expresslymigrator.js');
        $this->document->addScript('catalog/view/javascript/popupbox.js');
        
        $this->data['base'] = $this->getBaseUrl();
        $this->data['isRedirectToCheckoutEnabled'] = $this->model_expressly_migrator->isRedirectToCheckoutEnabled();
        $this->data['isRedirectToLoginEnabled'] = $this->model_expressly_migrator->isRedirectToLoginEnabled();
        
        // call the "View" to render the output
        $this->response->setOutput($this->render());
    }
    
    /**
     * Gets a customer by it's e-mail address
     */
    public function getUser() {
    	$this->load->model('expressly/migrator');
    	
    	if($this->model_expressly_migrator->isAuthorizedRequest(getallheaders())){
	    	$responseObject = array();
	    	$addresses = array();
	    	
	        $this->load->model('account/customer');
	        
	        $customer = $this->model_account_customer->getCustomerByEmail($this->request->get['user_email']);
	        
	        foreach ($this->model_expressly_migrator->getAddresses($customer['customer_id']) as $id => $address) {
	        	$addresses[] = $address;
	        }
	
	        $responseObject['customer'] = $customer;
	        $responseObject['addresses'] = $addresses;
	        
	        $this->response->setOutput(json_encode($responseObject));
    	} else {
			http_response_code(401);
		}
    }
	
	/**
	 * Stores the user by the given parameter
	 */
	public function storeUser() {
		$this->load->model('expressly/migrator');
		
		if($this->model_expressly_migrator->isAuthorizedRequest(getallheaders())){
			$this->load->model('account/customer');
			$this->load->model('checkout/coupon');
			
			if(isset($this->request->post['parameter'])) {
				$requestObject = json_decode(html_entity_decode($this->request->post['parameter']), true);
				
				$customerInRequest = $requestObject['customer'];
				$addressesInRequest = $requestObject['addresses'];
				$couponCodeInRequest = isset($requestObject['coupon_code']) ? $requestObject['coupon_code'] : null;
				
				if ($this->model_account_customer->getCustomerByEmail($customerInRequest['email']) == null) {
					$customerToStoreArray = $customerInRequest;
					foreach ( $addressesInRequest as $address ) {
						if ($address ['address_id'] == $customerInRequest ['address_id']) {
							$customerToStoreArray = array_merge ( $customerInRequest, $address );
							break;
						}
					}
					
					$this->model_account_customer->addCustomer ( $customerToStoreArray );
					$newUser = $this->model_account_customer->getCustomerByEmail ( $customerInRequest ['email'] );
					
					foreach ( $addressesInRequest as $address ) {
						if ($address ['address_id'] != $customerInRequest ['address_id']) {
							$this->model_expressly_migrator->addAddress ( $newUser ['customer_id'], $address );
						}
					}
					
					$this->response->setOutput($newUser['customer_id']);
				} else {
					$this->response->setOutput("This customer email already exists");
				}
			}
		} else {
			http_response_code(401);
		}
	}
	
	/**
	 * Endpoint for the migration logic.
	 */
	public function migration() {
	    $status = "";
	    $returnContent = "";
	    
		$paramters = array (
				'data' => $this->request->get['data']
		);

		$options = array (
				'http' => array (
						'header' => "Content-type: application/x-www-form-urlencoded\r\nReferer: " . $this->getBaseUrl() . "\r\n",
						'method' => 'GET',
				        'ignore_errors' => true
				)
		);
		
		$context = stream_context_create($options);
		$content = file_get_contents(self::SERVLET_URL."/newmigration?".http_build_query($paramters), false, $context);
		
		foreach($http_response_header as $header) {
		    $headerparts = explode(":", $header);
		
		    if(count($headerparts) == 1) {
		        header($header);
		        $statusParts = explode(" ", $header);
		        $status = $statusParts[1];
		        break;
		    }
		}
		
		if(intval($status) == 409) {
		    $returnContent = $content;
		} else {
		    $this->load->model('checkout/coupon');
		    $responseArray = explode("|", $content);
		    
		    $customer = $this->loginUser($responseArray[0], $responseArray[1], $responseArray[2]);
		    $coupon = $this->model_checkout_coupon->getCoupon($responseArray[2]);
		    
		    $returnContent = $customer['firstname'].";".floor($coupon['discount']);
		}
		$this->response->setOutput($returnContent);
	}
	
	/**
	 * Adds a product and a coupon to the cart.
	 */
	public function addProductAndCoupon() {
	    $productId = $this->request->get['product_id'];
	    $couponCode = $this->request->get['coupon_code'];
	    $userEmail = $this->request->get['user_email'];
	    
	    $this->addProductAndCouponToCart($productId, $couponCode, $userEmail);
	}
	
	/**
	 * Log the current user in.
	 */
	private function loginUser($userId, $productId, $couponCode) {
	    $this->load->model('account/customer');
	    $customer = $this->model_account_customer->getCustomer($userId);
	    $this->customer->login($customer['email'], '', true);
	
	    $this->addProductAndCouponToCart($productId, $couponCode, $customer['email']);
	    
	    return $customer;
	}
	
	/**
	 * Adds a product and a coupon to the cart.
	 * @param $productId is the product id
	 * @param $couponCode is the coupon code.
	 * @param $userEmail is the email address of the user
	 */
	private function addProductAndCouponToCart($productId, $couponCode, $userEmail) {
	   if($productId != null || $couponCode != null) {
	        $this->cart->clear();
	        $this->cart->add($productId, 1, null);
	
	        if ($couponCode != null) {
	            $this->session->data['coupon'] = $couponCode;
	        }
	    }
	}
	
	/**
	 * Gets the base URL of the system
	 */
	private function getBaseUrl() {
		$baseUrl;
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$baseUrl = $this->config->get('config_ssl');
		} else {
			$baseUrl = $this->config->get('config_url');
		}
		
		return $baseUrl;
	}
}

?>