<?php

require_once __DIR__ . '/private/model/Order.class.php';
require_once __DIR__ . '/private/dataService.php';

define('SHOPIFY_APP_SECRET', '2cb4cc2fa2b4a4aef608e92489a4bd71fbc587546b94bc28f302bfbd3acad0c5');
$HMAC_HEADER = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? null;


if ($HMAC_HEADER !== null) 
{
	function verify_webhook($data, $hmac_header)
	{
		$calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
		return hash_equals($hmac_header, $calculated_hmac);
	}

	$hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
	$data = file_get_contents('php://input');
	$verified = verify_webhook($data, $hmac_header);
	error_log('Webhook verified: ' . var_export($verified, true)); //check error.log to see the result

	if ($verified) {
		$file = fopen('passed.txt', 'w');
		fwrite($file, $data);
		fclose($file);

		$decodedData = json_decode($data);
		$newOrder = new Order();
		
		try
		{
			$dataService = new DataService();
			$newOrder->fillWithShopifyObject($decodedData);
			$errorsList = $dataService->addOrder($newOrder);
			//TODO: Save customer email

			$file = fopen('errors.txt', 'w');
			fwrite($file, '[' . date('d M Y H:i:s') . '] ' . var_export($errorsList, true) . "\n");
			fclose($file);
			
		} catch (Exception $e)
		{
			$file = fopen('errors.txt', 'a');
			fwrite($file, $e->getTraceAsString() . "\n");
			fclose($file);
		}

		http_response_code(200);
		exit(0);
	}
}

$file = fopen('verified.txt', 'w');
fwrite($file, 'false');
fclose($file);
http_response_code(401);
