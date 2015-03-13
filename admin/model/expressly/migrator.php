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
     * Updates the module passwrd
     * @param unknown $merchantUrl is the current merchant URL
     * @param unknown $newPass is the new password
     */
    public function updateModulePassword($merchantUrl, $newPass) {
    	$success = true;
    	
    	$newPass = stripslashes($newPass);
    	$newPass = str_replace('"', "", $newPass);
    	$newPass = str_replace("'", "", $newPass);
    	
    	if(!$this->sendNewModulePassword($merchantUrl, $this->getAuthToken(), $newPass)) {
    		$success = false;
    	} else {
    		$this->updateModulePasswordInDatabase($newPass);
    	}
    	return $success;
    }
    
    /**
     * Updates the redirect destination.
     * @param unknown $newDestination is the new destination to redirect the user
     */
    public function updateRedirectDestination($newDestination) {
    	$this->db->query("UPDATE " . DB_PREFIX . "expressly_migrator_options SET option_value = '".$newDestination."' WHERE option_name = 'redirect_destination'");
    }
    
	/**
	 * Installs the module for the first time.
	 */
	public function install($merchantUrl) {
	    try {
    	    $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'version'");
	    } catch( Exception $e) {
	        $password = htmlspecialchars(md5(uniqid(rand(), true)));
	        
	        $this->sendInitialPassword($merchantUrl, $password);
	        
	        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "expressly_migrator_options (
              `id` int(11) NOT NULL auto_increment,
              `option_name` text,
              `option_value` text,
              PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
	        
	        $this->db->query("INSERT INTO " . DB_PREFIX . "expressly_migrator_options VALUES (1,'version','0.1.0');");
            $this->db->query("INSERT INTO " . DB_PREFIX . "expressly_migrator_options VALUES (2,'module_password','".$password."');");
            $this->db->query("INSERT INTO " . DB_PREFIX . "expressly_migrator_options VALUES (3,'post_checkout_box','false');");
            $this->db->query("INSERT INTO " . DB_PREFIX . "expressly_migrator_options VALUES (4,'redirect_enabled','true');");
            $this->db->query("INSERT INTO " . DB_PREFIX . "expressly_migrator_options VALUES (5,'redirect_to_login','true');");
            $this->db->query("INSERT INTO " . DB_PREFIX . "expressly_migrator_options VALUES (6,'redirect_destination','index.php?route=checkout/cart');");
	    }
	}
	
	/**
	 * Getter of redirect to checkout option.
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
	 * Updates the module password in the database
	 */
	private function updateModulePasswordInDatabase($newPass) {
		$this->db->query("UPDATE " . DB_PREFIX . "expressly_migrator_options SET option_value = '".$newPass."' WHERE option_name = 'module_password'");
	}
	
	/**
	 * Send the module password to the servlet
	 */
	private function sendNewModulePassword($merchantUrl, $oldPass, $newPass) {
		$data = array (
				'oldPass' => $oldPass,
				'newPass' => $newPass
		);
	
		$options = array (
				'http' => array (
						'header' => "Content-Type: application/x-www-form-urlencoded\r\nReferer: " . $merchantUrl . "\r\n",
						'method' => 'POST',
						'content' => http_build_query ( $data )
				)
		);
	
		$context = stream_context_create ( $options );
		return "ok" == file_get_contents (ExpresslyHelper::SERVLET_URL."/updateModulePassword", false, $context );
	}
	
	/**
	 * Sends the initial password to the servlet
	 * @param unknown $password
	 */
	private function sendInitialPassword($merchantUrl, $password) {
	    $data = array (
	            'newPass' => $password,
	            'webshopSystem' => 'OpenCart'
	    );
	
	    $options = array (
	            'http' => array (
	                    'header' => "Content-Type: application/x-www-form-urlencoded\r\nReferer: " . $merchantUrl . "\r\n",
	                    'method' => 'POST',
	                    'content' => http_build_query ( $data )
	            )
	    );
	
	    $context = stream_context_create ( $options );
	    file_get_contents (ExpresslyHelper::SERVLET_URL."/saveModulePassword", false, $context );
	}
}
?>