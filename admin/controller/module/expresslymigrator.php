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
	 * Index method
	 */
	public function index() {
		$this->load->model('expressly/migrator');
		
		$this->data['fail'] = "";
		$this->data['success'] = "";
		
		$this->updateModulePassword();
		$this->updateRedirectDestination();
		
		$this->document->setTitle("Expressly");
		
		$this->document->addScript('view/javascript/expresslymigrator.js');
		$this->document->addScript('view/javascript/expresslyAdmin.js');
		$this->document->addStyle('view/stylesheet/expresslymigrator.css');

		$this->load->model('setting/setting');

		$this->data['heading_title'] = "Expressly";
		
		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => "Home",
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => "Module",
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => "Expressly",
			'href'      => $this->url->link('module/expresslymigrator', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->load->model('design/layout');

		$this->data['layouts'] = $this->model_design_layout->getLayouts();
		
		$this->data['action'] = $this->url->link('module/expresslymigrator', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['postCheckoutBox'] = $this->model_expressly_migrator->isPostCheckoutBoxEnabled();
		$this->data['redirectEnabled'] = $this->model_expressly_migrator->isRedirectEnabled();
		$this->data['redirectToLogin'] = $this->model_expressly_migrator->isRedirectToLoginEnabled();
		$this->data['modulePass'] = base64_encode($this->model_expressly_migrator->getAuthToken());
		$this->data['pureModulePass'] = $this->model_expressly_migrator->getAuthToken();
		$this->data['base'] = HTTP_CATALOG;
		$this->data['token'] = $this->session->data['token'];
		$this->data['redirectDestination'] = $this->model_expressly_migrator->getRedirectDestination();
		
		$this->template = 'module/expresslymigrator.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
		
		$this->response->setOutput($this->render());
	}
	
	/**
	 * Updates the module password
	 */
	private function updateModulePassword() {
		if (isset($this->request->post['modulePass'])) {
			if($this->model_expressly_migrator->updateModulePassword(HTTP_CATALOG, $this->request->post['modulePass'])) {
				$this->data['success'] = "Module password has been updated successfully.";
			} else {
				$this->data['fail'] = "Failed to send new password to expressly.";
			}
		}
	}
	
	/**
	 * Updates the user redirect destination
	 */
	private function updateRedirectDestination() {
		if (array_key_exists('redirect-destination', $this->request->post)) {
			
			$redirectDestination = $this->request->post['redirect-destination'];
			
			if(strpos($redirectDestination, HTTP_CATALOG) !== false) {
				$redirectDestination = str_replace(HTTP_CATALOG, "", $redirectDestination);
			}
			
			$this->model_expressly_migrator->updateRedirectDestination($redirectDestination);
			$this->data['success'] = "Redirect destination has been updated successfully.";
		}
	}
	
	/**
	 * Install method
	 */
	public function install() {
	    $this->load->model('expressly/migrator');
	    $this->model_expressly_migrator->install(HTTP_CATALOG);
	}
}

?>