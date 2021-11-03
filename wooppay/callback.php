<?php
/**
 * Process callbacks
 */
require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');
require_once(dirname(__FILE__) . '/classes/WooppaySoapClient.php');

if (isset($_GET['cart_id']) && isset($_GET['key'])) {
	$id = $_GET['cart_id'];
	$key = $_GET['key'];
	if (md5($id) == $key) {
		try {
			$client = new WooppaySoapClient(Configuration::get('WOOPPAY_SOAP_URL'));
			if ($client->login(Configuration::get('WOOPPAY_LOGIN'), Configuration::get('WOOPPAY_PASSWORD'))) {
				$prefix = trim(Configuration::get('WOOPPAY_INVOICE_PREFIX'));
				$order_id = $prefix . $id;
				$invoice_request = new CashCreateInvoiceByServiceRequest();
				$invoice_request->referenceId = $order_id;
				$invoice_request->serviceName = Configuration::get('WOOPPAY_SERVICE_NAME');
				$invoice = $client->createInvoice($invoice_request);
				$operation = $client->getOperationData((int)$invoice->response->operationId);
				if ($operation->response->records[0]->status == WooppayOperationStatus::OPERATION_STATUS_DONE || $operation->response->records[0]->status == WooppayOperationStatus::OPERATION_STATUS_WAITING) {
					$id_order = Order::getOrderByCartId($id);
					if ($id_order) {
						$order = new Order($id_order);
						$order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
						echo json_encode(['data' => 1]);
					} else {
						$logger = new FileLogger(0);
						$logger->setFilename(_PS_ROOT_DIR_ . '/log/debug.log');
						$logger->logDebug('Не найден заказ с id корзины = ' . $id);
					}
					die();
				}
			}
		} catch (Exception $exception) {
			$logger = new FileLogger(0);
			$logger->setFilename(_PS_ROOT_DIR_ . '/log/debug.log');
			$logger->logDebug('Произошла ошибка при проверке платежа, id = ' . $id);
		}
	}
}
return false;
