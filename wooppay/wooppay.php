<?php
if (!defined('_PS_VERSION_')) {
	exit;
}

require_once(dirname(__FILE__) . '/classes/WooppaySoapClient.php');

class Wooppay extends PaymentModule
{
	static $vat_enum = array(
		'' => '',
		0 => 0,
		10 => 10,
		20 => 20,
		110 => 110,
		120 => 120,
	);

	public function __construct()
	{
		$this->name = 'wooppay';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'ikolesnikov@wooppay.com';
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		$this->bootstrap = true;
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		parent::__construct();

		$this->displayName = $this->l('Wooppay');
		$this->description = $this->l('Wooppay - payment via Visa/MasterCard');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('paymentReturn') || !$this->registerHook('paymentOptions')) {
			return false;
		}
		return true;
	}

	public function uninstall()
	{
		return parent::uninstall();
	}

	public function getContent()
	{
		$output = null;
		if (Tools::isSubmit('submit' . $this->name)) {
			$login = Tools::getValue('WOOPPAY_LOGIN');
			$password = Tools::getValue('WOOPPAY_PASSWORD');
			$soapUrl = Tools::getValue('WOOPPAY_SOAP_URL');
			$invoicePrefix = Tools::getValue('WOOPPAY_INVOICE_PREFIX');
			$serviceName = Tools::getValue('WOOPPAY_SERVICE_NAME');
			$language = Tools::getValue('WOOPPAY_LANGUAGE');
			$currency = Tools::getValue('WOOPPAY_CURRENCY');

			$errors = $this->validate(
				array(
					array(
						'type' => 'string',
						'name' => $this->l('Login'),
						'value' => $login
					),
					array(
						'type' => 'string',
						'name' => $this->l('Password'),
						'value' => $password
					),
					array(
						'type' => 'string',
						'name' => $this->l('Wooppay SOAP URL'),
						'value' => $soapUrl
					),
					array(
						'type' => 'string',
						'name' => $this->l('Wooppay invoice prefix'),
						'value' => $invoicePrefix
					),
					array(
						'type' => 'string',
						'name' => $this->l('Wooppay service name'),
						'value' => $serviceName
					),
					array(
						'type' => 'string',
						'name' => $this->l('Language'),
						'value' => $language
					),
					array(
						'type' => 'string',
						'name' => $this->l('Currency'),
						'value' => $currency
					),
				)
			);

			if (count($errors)) {
				foreach ($errors as $error) {
					$output .= $this->displayError($error);
				}
			} else {
				Configuration::updateValue('WOOPPAY_LOGIN', $login);
				Configuration::updateValue('WOOPPAY_PASSWORD', $password);
				Configuration::updateValue('WOOPPAY_SOAP_URL', $soapUrl);
				Configuration::updateValue('WOOPPAY_INVOICE_PREFIX', $invoicePrefix);
				Configuration::updateValue('WOOPPAY_SERVICE_NAME', $serviceName);
				Configuration::updateValue('WOOPPAY_LANGUAGE', $language);
				Configuration::updateValue('WOOPPAY_CURRENCY', $currency);
				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		}

		return $output . $this->displayForm();
	}

	private function validate($data)
	{
		$errors = array();
		foreach ($data as $item) {
			switch ($item['type']) {
				case 'string':
					if (!$item['value']) {
						$errors[] = sprintf($this->l('Field %s must be non empty'), $item['name']);
					}
					break;
				case 'number':
					if (!is_numeric($item['value'])) {
						$errors[] = sprintf($this->l('Field %s must be a number'), $item['name']);
					}
					break;
			}
		}

		return $errors;
	}

	public function displayForm()
	{
		// Get default language
		$defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
		$fieldsForm[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Settings'),
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Login'),
					'name' => 'WOOPPAY_LOGIN',
					'size' => 64,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Password'),
					'name' => 'WOOPPAY_PASSWORD',
					'size' => 64,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Wooppay SOAP URL'),
					'name' => 'WOOPPAY_SOAP_URL',
					'size' => 64,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Wooppay invoice prefix'),
					'name' => 'WOOPPAY_INVOICE_PREFIX',
					'size' => 64,
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => $this->l('Wooppay service name'),
					'name' => 'WOOPPAY_SERVICE_NAME',
					'size' => 64,
					'required' => true
				),
				array(
					'type' => 'select',
					'class' => 'fixed-width-xxl',
					'label' => $this->l('Language'),
					'name' => 'WOOPPAY_LANGUAGE',
					'options' => array(
						'query' => array(
							array('id' => 'ru-RU', 'name' => $this->l('Russian')),
							array('id' => 'kk-KZ', 'name' => $this->l('Kazakh')),
							array('id' => 'en-US', 'name' => $this->l('English')),
						),
						'id' => 'id',
						'name' => 'name',
					)
				),
				array(
					'type' => 'select',
					'class' => 'fixed-width-xxl',
					'label' => $this->l('Currency'),
					'name' => 'WOOPPAY_CURRENCY',
					'options' => array(
						'query' => array(
							array('id' => 1, 'name' => $this->l('Site currency')),
							array('id' => 'KZT', 'name' => $this->l('Kazakh tenge')),
						),
						'id' => 'id',
						'name' => 'name',
					)
				),
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right'
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

		// Language
		$helper->default_form_language = $defaultLang;
		$helper->allow_employee_form_lang = $defaultLang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;        // false -> remove toolbar
		$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit' . $this->name;
		$helper->toolbar_btn = array(
			'save' =>
				array(
					'desc' => $this->l('Save'),
					'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
						'&token=' . Tools::getAdminTokenLite('AdminModules'),
				),
			'back' => array(
				'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);

		// Load current value
		$helper->fields_value['WOOPPAY_LOGIN'] = Configuration::get('WOOPPAY_LOGIN');
		$helper->fields_value['WOOPPAY_PASSWORD'] = Configuration::get('WOOPPAY_PASSWORD');
		$helper->fields_value['WOOPPAY_SOAP_URL'] = Configuration::get('WOOPPAY_SOAP_URL');
		$helper->fields_value['WOOPPAY_INVOICE_PREFIX'] = Configuration::get('WOOPPAY_INVOICE_PREFIX');
		$helper->fields_value['WOOPPAY_SERVICE_NAME'] = Configuration::get('WOOPPAY_SERVICE_NAME');
		$helper->fields_value['WOOPPAY_LANGUAGE'] = Configuration::get('WOOPPAY_LANGUAGE');
		$helper->fields_value['WOOPPAY_CURRENCY'] = Configuration::get('WOOPPAY_CURRENCY');

		return $helper->generateForm($fieldsForm);
	}

	public function hookPaymentOptions($params)
	{
		if (!$this->active) {
			return;
		}

		$newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
		$newOption->setCallToActionText($this->l('Wooppay - payment via Visa/MasterCard'))
			->setAction($this->context->link->getModuleLink($this->name, 'invoice', array(), true))
			->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo.png'));
		$payment_options = [
			$newOption,
		];

		return $payment_options;
	}
}