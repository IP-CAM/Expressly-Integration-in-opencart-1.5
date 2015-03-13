<?php

/**
 * Helper class for Expressly Migrator module
 * @author Expressly Limited
 *
 */
class ExpresslyHelper {
	const SERVLET_URL = "https://buyexpressly.com/expresslymod";
	const POPUP_URL = "http://buyexpressly.com/website/popup_demo_a_blue/index.php";
	const POPUP_MOBILE_URL = "http://buyexpressly.com/website/popup_demo_a_blue/index.php";
	
	/**
	 * Getter of redirect user option.
	 */
	public static function isRedirectEnabled($db) {
		$query = $db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'redirect_enabled'");
		return "true" == $query->row['option_value'];
	}
	
	/**
	 * Getter of redirect to login option.
	 */
	public static function isRedirectToLoginEnabled($db) {
		$query = $db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'redirect_to_login'");
		return "true" == $query->row['option_value'];
	}
	
	/**
	 * Getter of post checkout box option.
	 */
	public static function isPostCheckoutBoxEnabled($db) {
		$query = $db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'post_checkout_box'");
		return "true" == $query->row['option_value'];
	}
	
	/**
	 * Getter of the authentication token
	 */
	public static function getAuthToken($db){
		$query = $db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'module_password'");
		return $query->row['option_value'];
	}
	
	/**
	 * Getter of the authentication token
	 */
	public static function getRedirectDestination($db){
		$query = $db->query("SELECT option_value FROM " . DB_PREFIX . "expressly_migrator_options WHERE option_name = 'redirect_destination'");
		return $query->row['option_value'];
	}
}
?>