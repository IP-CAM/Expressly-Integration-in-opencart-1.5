<?php

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
		$this->load->language('module/expresslymigrator');
		$this->load->model('expressly/migrator');
		
		$this->updateModulePassword();
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->document->addScript('view/javascript/expresslymigrator.js');
		$this->document->addScript('view/javascript/expresslyAdmin.js');
		$this->document->addStyle('view/stylesheet/expresslymigrator.css');

		$this->load->model('setting/setting');

		$this->data['heading_title'] = $this->language->get('heading_title');
		
		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/expresslymigrator', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->load->model('design/layout');

		$this->data['layouts'] = $this->model_design_layout->getLayouts();
		
		$this->data['action'] = $this->url->link('module/expresslymigrator', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['redirectToCheckout'] = $this->model_expressly_migrator->isRedirectToCheckoutEnabled();
		$this->data['postCheckoutBox'] = $this->model_expressly_migrator->isPostCheckoutBoxEnabled();
		$this->data['redirectToLogin'] = $this->model_expressly_migrator->isRedirectToLoginEnabled();
		$this->data['modulePass'] = $this->model_expressly_migrator->getAuthToken();
		$this->data['base'] = HTTP_CATALOG;
		$this->data['token'] = $this->session->data['token'];
		
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
		$this->data['fail'] = "";
		$this->data['success'] = "";
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if($this->model_expressly_migrator->updateModulePassword(HTTP_CATALOG, $this->request->post['modulePass'])) {
				$this->data['success'] = "Module password has been updated successfully.";
			} else {
				$this->data['fail'] = "Failed to send new password to expressly.";
			}
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