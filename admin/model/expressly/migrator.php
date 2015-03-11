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
     * Updates the module passwrd
     * @param unknown $merchantUrl is the current merchant URL
     * @param unknown $newPass is the new password
     */
    public function updateModulePassword($merchantUrl, $newPass) {
    	$success = true;
    	if(!$this->sendNewModulePassword($merchantUrl, $this->getAuthToken(), $newPass)) {
    		$success = false;
    	} else {
    		$this->updateModulePasswordInDatabase($newPass);
    	}
    	return $success;
    }
    
	/**
	 * Installs the module for the first time.
	 */
	public function install($merchantUrl) {
	    try {
    	    $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'version'");
	    } catch( Exception $e) {
	        $password = md5(uniqid(rand(), true));
	        
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
            $this->db->query("INSERT INTO " . DB_PREFIX . "expressly_migrator_options VALUES (4,'redirect_to_checkout','true');");
            $this->db->query("INSERT INTO " . DB_PREFIX . "expressly_migrator_options VALUES (5,'redirect_to_login','true');");
	    }
	}
	
	/**
	 * Getter of redirect to checkout option.
	 */
	public function isRedirectToCheckoutEnabled() {
		$query = $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'redirect_to_checkout'");
		return "true" == $query->row['option_value'];
	}
	
	/**
	 * Getter of redirect to login option.
	 */
	public function isRedirectToLoginEnabled() {
		$query = $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'redirect_to_login'");
		return "true" == $query->row['option_value'];
	}
	
	/**
	 * Getter of post checkout box option.
	 */
	public function isPostCheckoutBoxEnabled() {
		$query = $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'post_checkout_box'");
		return "true" == $query->row['option_value'];
	}
	
	/**
	 * Getter of the authentication token
	 */
	public function getAuthToken(){
		$query = $this->db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'module_password'");
		return $query->row['option_value'];
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
		return "ok" == file_get_contents (self::SERVLET_URL."/updateModulePassword", false, $context );
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
	    file_get_contents (self::SERVLET_URL."/saveModulePassword", false, $context );
	}
}
?>