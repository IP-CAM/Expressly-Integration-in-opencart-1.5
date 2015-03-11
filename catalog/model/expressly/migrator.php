<?php

/**
 * Modell class for Expressly Migrator related functions
 * 
 * @author Expressly Limited
 *
 */
class ModelExpresslyMigrator extends Model {
	
    const SERVLET_URL = "https://buyexpressly.com/expresslymod";
    
    /**
     * Checks if the request is authorized or not.
     */
    public function isAuthorizedRequest($request) {
    	$returnValue = false;
    	
    	if(isset($request["Authorization"])) {
	    	$auth = $request["Authorization"];
	    	$authParts = explode (" ", $auth);
	    	$returnValue = $authParts [0] == "Basic" && $authParts [1] == $this->getAuthToken();
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
	public function updateRedirectToCheckoutEnabled($newVal) {
		$this->db->query("UPDATE " . DB_PREFIX . "expressly_migrator_options SET option_value = '".$newVal."' WHERE option_name = 'redirect_to_checkout'");
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
	 * Getter of redirect to checkout option.
	 */
	public function isRedirectToCheckoutEnabled() {
	    $query = $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'redirect_to_checkout'");
	    return $query->row['option_value'];
	}
	
	/**
	 * Getter of redirect to login option.
	 */
	public function isRedirectToLoginEnabled() {
	    $query = $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'redirect_to_login'");
	    return $query->row['option_value'];
	}
	
	/**
	 * Getter of post checkout box option.
	 */
	public function isPostCheckoutBoxEnabled() {
	    $query = $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'post_checkout_box'");
	    return $query->row['option_value'];
	}
	
	/**
	 * Getter of the authentication token
	 */
	public function getAuthToken(){
		$query = $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'module_password'");
		return $query->row['option_value'];
	}
}
?>