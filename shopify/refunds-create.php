<?php

require_once dirname(__DIR__) . '/private/model/Order.class.php';
require_once dirname(__DIR__) . '/private/dataService.php';

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
		$file = fopen('refunds-create-passed.txt', 'w');
		fwrite($file, $data);
		fclose($file);

		$decodedData = json_decode($data);
		$order = new Order();
		
		try
		{
            $dataService = new DataService();
            $shopifyID = $decodedData->order_id;
            if(isset($shopifyID))
            {
                $isSuccess = $dataService->setOrderCanceled($shopifyID);
                if($isSuccess)
                {
                    http_response_code(200);
                    exit(0);
                }
                else
                {
                    http_response_code(401);
                    exit(-1);
                }
            }
            exit(0);
			
		} catch (Exception $e)
		{
            http_response_code(401);
			$file = fopen('refunds-errors.txt', 'a');
			fwrite($file, '[' . date('d M Y H:i:s') . '] ' . $e->getTraceAsString() . "\n\n");
			fclose($file);
		}

		exit(-1);
    }
    
    $file = fopen('refunds-create.txt', 'w');
    fwrite($file, '401 error');
    fclose($file);
    http_response_code(401);
}
