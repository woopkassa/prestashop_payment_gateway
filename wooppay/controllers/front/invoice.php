<?php

class WooppayInvoiceModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$cart = $this->context->cart;
		$total = $cart->getOrderTotal(true);

		if (Configuration::get('WOOPPAY_CURRENCY') == 1) {
			$currencyId = Currency::getIdByIsoCode('KZT');
			if ($cart->id_currency != $currencyId) {
				$fromCurrency = new Currency($cart->id_currency);
				$toCurrency = new Currency($currencyId);
				$total = Tools::convertPriceFull($total, $fromCurrency, $toCurrency);
			}
		}
		try {
			$client = new WooppaySoapClient(Configuration::get('WOOPPAY_SOAP_URL'));
			if ($client->login(Configuration::get('WOOPPAY_LOGIN'), Configuration::get('WOOPPAY_PASSWORD'))) {
				$prefix = trim(Configuration::get('WOOPPAY_INVOICE_PREFIX'));
				$invoice_request = new CashCreateInvoiceByServiceRequest();
				$invoice_request->referenceId = $prefix . $cart->id;
				$invoice_request->backUrl = $this->context->link->getModuleLink('wooppay', 'success', array('cart_id' => $cart->id));
				$invoice_request->requestUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/modules/wooppay/callback.php?cart_id=' . $cart->id . '&key=' . md5($cart->id);
				$invoice_request->addInfo = 'Оплата заказа №' . $cart->id;
				$invoice_request->amount = $total;
				$invoice_request->serviceName = Configuration::get('WOOPPAY_SERVICE_NAME');
				$invoice_request->description = 'Оплата заказа №' . $cart->id;
				$invoice_request->userEmail = $this->context->customer->email;
				$invoiceAddress = new Address($this->context->cart->id_address_invoice);
				$phone = $invoiceAddress ? $invoiceAddress->phone : '';
				$invoice_request->userPhone = $phone;
				$invoice_data = $client->createInvoice($invoice_request);
				$this->context->smarty->assign(array(
					'url' => $invoice_data->response->operationUrl
				));
				$customer = new Customer((int)$this->context->cart->id_customer);
				$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
				$this->module->validateOrder((int)$this->context->cart->id, Configuration::get('PS_OS_BANKWIRE'), $total, $this->module->displayName, null, array(), null, false,
					$customer->secure_key);
				Tools::redirect($invoice_data->response->operationUrl);
			} else {
				$logger = new FileLogger(0);
				$logger->setFilename(_PS_ROOT_DIR_ . '/log/debug.log');
				$logger->logDebug('Не удалось авторизоваться в системе Wooppay');
				return;
			}
		} catch (Exception $exception) {
			$logger = new FileLogger(0);
			$logger->setFilename(_PS_ROOT_DIR_ . '/log/debug.log');
			$logger->logDebug('Произошла ошибка при создание инвойса');
			return;
		}
	}
}