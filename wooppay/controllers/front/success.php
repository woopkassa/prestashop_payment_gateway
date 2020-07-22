<?php

class WooppaySuccessModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		parent::initContent();

		if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 || $this->context->cart->id_address_invoice == 0) {
			Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
		}

		$authorized = false;
		foreach (Module::getPaymentModules() as $module) {
			if ($module['name'] == 'wooppay') {
				$authorized = true;
				break;
			}
		}

		if (!$authorized) {
			die(Tools::displayError('This payment method is not available.'));
		}

		if (!isset($_GET['cart_id'])) {
			die(Tools::displayError('Wrong cart id.'));
		}
		$cart_id = $_GET['cart_id'];
		$cart = new Cart($cart_id);
		$customer = new Customer((int)$cart->id_customer);

		Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?key=' . $customer->secure_key . '&id_cart=' . (int)$cart_id . '&id_module=' . (int)$this->module->id . '&id_order=' . (int)Order::getOrderByCartId($cart_id));
	}
}
