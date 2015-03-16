<?php
require_once DIR_SYSTEM.'helper/expressly.php';

/**
 * Modell class for Expressly Migrator related functions
 * 
 * @author Expressly Limited
 *
 */
class ModelExpresslyMigrator extends Model {
    
	/**
	 * Updates the newsletter option to the given user
	 * @param unknown $newsletter
	 */
	public function editNewsletter($newsletter, $userId) {
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET newsletter = '" . (int)$newsletter . "' WHERE customer_id = '" . $userId . "'");
	}
	
	/**
	 * Gets the discount amount of the given coupon
	 * @param unknown $code is the coupon code
	 */
	public function getCouponDiscount($code) {
		$status = true;
	
		$coupon_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE code = '" . $this->db->escape($code) . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) AND status = '1'");
	
		if ($coupon_query->num_rows) {
			$coupon_history_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch WHERE ch.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "'");
	
			if ($coupon_query->row['uses_total'] > 0 && ($coupon_history_query->row['total'] >= $coupon_query->row['uses_total'])) {
				$status = false;
			}
	
			if ($coupon_query->row['logged'] && !$this->customer->getId()) {
				$status = false;
			}
	
			if ($this->customer->getId()) {
				$coupon_history_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "coupon_history` ch WHERE ch.coupon_id = '" . (int)$coupon_query->row['coupon_id'] . "' AND ch.customer_id = '" . (int)$this->customer->getId() . "'");
	
				if ($coupon_query->row['uses_customer'] > 0 && ($coupon_history_query->row['total'] >= $coupon_query->row['uses_customer'])) {
					$status = false;
				}
			}
			
		} else {
			$status = false;
		}
	
		if ($status) {
			$amount = floor($coupon_query->row['discount']);
			return $coupon_query->row['type'] == "F" ? $this->currency->format($amount) : $amount."%";
		}
	}
	
    /**
     * Checks if the request is authorized or not.
     */
    public function isAuthorizedRequest($request) {
    	$returnValue = false;

	    if(isset($request["Authorization"])) {
			$auth = $request["Authorization"];
			$authParts = explode (" ", $auth);
			$returnValue = ($authParts[0] == "Expressly" && $authParts[1] == base64_encode($this->getAuthToken()));
	    }
    	
    	return $returnValue;
    }
    
    public function deleteCustomer($customer_id) {
    	$this->db->query("DELETE FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "'");
    	$this->db->query("DELETE FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$customer_id . "'");
    	$this->db->query("DELETE FROM " . DB_PREFIX . "customer_transaction WHERE customer_id = '" . (int)$customer_id . "'");
    	$this->db->query("DELETE FROM " . DB_PREFIX . "customer_ip WHERE customer_id = '" . (int)$customer_id . "'");
    	$this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "'");
    }
    
	/**
	 * Gets all the addresses of the given user.
	 * @return multitype:multitype:unknown string
	 */
	public function getAddresses($userId) {
		$address_data = array ();
		
		$query = $this->db->query ( "SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" .$userId. "'" );
		
		foreach ( $query->rows as $result ) {
			$country_query = $this->db->query ( "SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . ( int ) $result ['country_id'] . "'" );
			
			if ($country_query->num_rows) {
				$country = $country_query->row ['name'];
				$iso_code_2 = $country_query->row ['iso_code_2'];
				$iso_code_3 = $country_query->row ['iso_code_3'];
				$address_format = $country_query->row ['address_format'];
			} else {
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format = '';
			}
			
			$zone_query = $this->db->query ( "SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . ( int ) $result ['zone_id'] . "'" );
			
			if ($zone_query->num_rows) {
				$zone = $zone_query->row ['name'];
				$zone_code = $zone_query->row ['code'];
			} else {
				$zone = '';
				$zone_code = '';
			}
			
			$address_data [$result ['address_id']] = array (
					'address_id' => $result ['address_id'],
					'firstname' => $result ['firstname'],
					'lastname' => $result ['lastname'],
					'company' => $result ['company'],
					'company_id' => $result ['company_id'],
					'tax_id' => $result ['tax_id'],
					'address_1' => $result ['address_1'],
					'address_2' => $result ['address_2'],
					'postcode' => $result ['postcode'],
					'city' => $result ['city'],
					'zone_id' => $result ['zone_id'],
					'zone' => $zone,
					'zone_code' => $zone_code,
					'country_id' => $result ['country_id'],
					'country' => $country,
					'iso_code_2' => $iso_code_2,
					'iso_code_3' => $iso_code_3,
					'address_format' => $address_format 
			);
		}
		
		return $address_data;
	}
	
	/**
	 * Ads an address to the given user.
	 * @param unknown $userId is the ID of the user
	 * @param unknown $data is the address data
	 */
	public function addAddress($userId, $data) {
	    $this->db->query("INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . $userId . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', company_id = '" . $this->db->escape(isset($data['company_id']) ? $data['company_id'] : '') . "', tax_id = '" . $this->db->escape(isset($data['tax_id']) ? $data['tax_id'] : '') . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', zone_id = '" . (int)$data['zone_id'] . "', country_id = '" . (int)$data['country_id'] . "'");
	
	    $address_id = $this->db->getLastId();
	
	    if (!empty($data['default'])) {
	        $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . $userId . "'");
	    }
	
	    return $address_id;
	}
	
	/**
	 * Getter of redirect to checkout option.
	 */
	public function updateRedirectEnabled($newVal) {
		$this->db->query("UPDATE " . DB_PREFIX . "expressly_migrator_options SET option_value = '".$newVal."' WHERE option_name = 'redirect_enabled'");
	}
	
	/**
	 * Getter of redirect to login option.
	 */
	public function updateRedirectToLoginEnabled($newVal) {
		$this->db->query("UPDATE " . DB_PREFIX . "expressly_migrator_options SET option_value = '".$newVal."' WHERE option_name = 'redirect_to_login'");
	}
	
	/**
	 * Getter of post checkout box option.
	 */
	public function updatePostCheckoutBoxEnabled($newVal) {
		$this->db->query("UPDATE " . DB_PREFIX . "expressly_migrator_options SET option_value = '".$newVal."' WHERE option_name = 'post_checkout_box'");
	}
	
	/**
	 * Getter of redirect user option.
	 */
	public function isRedirectEnabled() {
		return ExpresslyHelper::isRedirectEnabled($this->db);
	}
	
	/**
	 * Getter of redirect to login option.
	 */
	public function isRedirectToLoginEnabled() {
		return ExpresslyHelper::isRedirectToLoginEnabled($this->db);
	}
	
	/**
	 * Getter of post checkout box option.
	 */
	public function isPostCheckoutBoxEnabled() {
		return ExpresslyHelper::isPostCheckoutBoxEnabled($this->db);
	}
	
	/**
	 * Getter of the authentication token
	 */
	public function getAuthToken(){
		return ExpresslyHelper::getAuthToken($this->db);
	}
	
	/**
	 * Getter of the redirect destination string
	 */
	public function getRedirectDestination(){
		return ExpresslyHelper::getRedirectDestination($this->db);
	}
	
	/**
	 * Gets a country by ISO 2 code
	 * @param unknown $isoCode2 is the ISO 2 code of the country
	 */
	public function getCountryByIsoCode2($isoCode2) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $isoCode2 . "' AND status = '1'");
		return $query->row;
	}
	
	/**
	 * Gets a zone by the country id, and zone name
	 * @param unknown $countryId is the id of the country
	 * @param unknown $zoneName is the name of the zone
	 */
	public function getZoneByCountryIdAndName($countryId, $zoneName) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE country_id = '".$countryId."' AND name = '" . $zoneName . "' AND status = '1'");
	
		return $query->row;
	}
	
	/**
	 * Checks if the user has any orders.
	 * @param unknown $userId is the id of the user
	 * @return boolean true, when the user has any orders.
	 */
	public function hasAnyOrders($userId) {
		return count($this->getUserOrders($userId)) > 0;
	}
	
	/**
	 * Gets all the orders of a user
	 * @param unknown $userId
	 */
	public function getUserOrders($userId) {
		$query = $this->db->query("SELECT o.order_id, o.firstname, o.lastname, os.name as status, o.date_added, o.total, o.currency_code, o.currency_value FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE o.customer_id = '" . $userId . "' AND o.order_status_id > '0' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.order_id DESC");
		
		return $query->rows;
	}
	
	/**
	 * Adds a custmer.
	 */
	public function addCustomer($data) {
	    $this->db->query("INSERT INTO " . DB_PREFIX . "customer SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', newsletter = '" . (int)$data['newsletter'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', salt = '" . $this->db->escape($salt = substr(md5(uniqid(rand(), true)), 0, 9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "', status = '" . (int)$data['status'] . "', approved = '".(int)$data['approved']."', date_added = NOW()");
	
	    $customer_id = $this->db->getLastId();
	
	    if (isset($data['address'])) {
	        foreach ($data['address'] as $address) {
	            $this->db->query("INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . (int)$customer_id . "', firstname = '" . $this->db->escape($address['firstname']) . "', lastname = '" . $this->db->escape($address['lastname']) . "', company = '" . $this->db->escape($address['company']) . "', company_id = '" . $this->db->escape($address['company_id']) . "', tax_id = '" . $this->db->escape($address['tax_id']) . "', address_1 = '" . $this->db->escape($address['address_1']) . "', address_2 = '" . $this->db->escape($address['address_2']) . "', city = '" . $this->db->escape($address['city']) . "', postcode = '" . $this->db->escape($address['postcode']) . "', country_id = '" . (int)$address['country_id'] . "', zone_id = '" . (int)$address['zone_id'] . "'");
	
	            if (isset($address['default'])) {
	                $address_id = $this->db->getLastId();
	
	                $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . $address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
	            }
	        }
	    }
	}
}
?>