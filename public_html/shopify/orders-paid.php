<?php

require_once dirname(__DIR__) . '/private/model/Order.class.php';
require_once dirname(__DIR__) . '/private/model/Customer.class.php';
require_once dirname(__DIR__) . '/private/model/OrderLine.class.php';
require_once dirname(__DIR__) . '/private/dataService.php';
require_once dirname(__DIR__) . '/private/mailService.php';

define('SHOPIFY_APP_SECRET', '2cb4cc2fa2b4a4aef608e92489a4bd71fbc587546b94bc28f302bfbd3acad0c5');
$HMAC_HEADER = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? null;

function writeLine($line)
{
	$file = fopen('orders-paid-passed.txt', 'w');
	fwrite($file, "LINE PASSED : $line\n");
	fclose($file);
}


if ($HMAC_HEADER !== null) 
{
	function verify_webhook($data, $hmac_header)
	{
		$calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
		return hash_equals($hmac_header, $calculated_hmac);
	}

	$hmac_header = $HMAC_HEADER;
	$data = file_get_contents('php://input');
	$verified = verify_webhook($data, $hmac_header);
	error_log('Webhook verified: ' . var_export($verified, true) . ' - hmac header: ' . $HMAC_HEADER); //check error.log to see the result

	if ($verified) {
		$file = fopen('orders-paid-passed.txt', 'w');
		fwrite($file, $data);
		fclose($file);

		$decodedData = json_decode($data);
		$newOrder = new Order();
		$mailService = new MailService();
		
		try
		{
			$dataService = new DataService();
			$shopifyCustomer = $decodedData->customer ?? null;

			if($shopifyCustomer !== null)
			{
				$newCustomer = new Customer();
				$newCustomer->fillWithShopifyObject($shopifyCustomer);
				$customer = $dataService->findCustomerByShopifyID($shopifyCustomer->id);
				if($customer === null)
				{
					//new shopify customer, add to our database

					if($decodedData->billing_address !== null)
					{
						if($newCustomer->FirstName == null)
							$newCustomer->FirstName = $decodedData->billing_address->first_name;
						if($newCustomer->LastName == null)
							$newCustomer->LastName = $decodedData->billing_address->last_name;
						if($newCustomer->Email == null)
							$newCustomer->Email = $decodedData->email;
					}

					$newCustomerID = $dataService->addCustomer($newCustomer);
					$newOrder->CustomerID = $newCustomerID;
				}
				else
				{
					//linking to order
					if(($updatedAt = $shopifyCustomer->updated_at) != null)
					{
						if(strtotime($customer->LastUpdate) < strtotime($updatedAt))
						{
							$customer->Email = $shopifyCustomer->email;
							$customer->FirstName = $shopifyCustomer->first_name;
							$customer->LastName = $shopifyCustomer->last_name;
							$dataService->updateCustomer($customer);
						}
					}
					
					$newOrder->CustomerID = $customer->ID;
				}
			}
			else
			{
				//customer ordered when offline
				if($decodedData->email !== null)
				{
					//email was given, so maybe the customer exists in database
					$email = $decodedData->email;
					$customer = $dataService->findCustomerByEmail($email);

					if($customer == null)
					{
						//totally new customer, create in database
						$newCustomer = new Customer();

						if($decodedData->billing_address !== null)
						{
							$newCustomer->FirstName = $decodedData->billing_address->first_name;
							$newCustomer->LastName = $decodedData->billing_address->last_name;
							$newCustomer->Email = $decodedData->email;
						}

						$newCustomerID = $dataService->addCustomer($newCustomer);
						$newOrder->CustomerID = $newCustomerID;
					}
					else
					{
						//existing customer, linking to order
						$newOrder->CustomerID = $customer->ID;
					}
				}
				else
				{
					//order without customer ( ??? )
				}
			}

			$newOrder->fillWithShopifyObject($decodedData);
			$result = $dataService->addOrder($newOrder);
			$errorsList = $result['errors'];
			$insertedID = $result['insert_id'];

			if(!empty($errorsList)) throw new Exception(join('<br>', $errorsList));
			else
			{
				$shopifyOrderLines = $decodedData->line_items;
				$orderLines = [];
				foreach($shopifyOrderLines as $shopifyOrderLine)
				{
					$orderLine = new OrderLine();
					$orderLine->fillWithShopifyObject($shopifyOrderLine);
					$orderLine->OrderID = $insertedID;
					$orderLines[] = $orderLine;
				}

				$dataService->addOrderLines($orderLines);
			}
			
		} catch (Exception $e)
		{
			$mailService->notifyAdminError("orders-paid.php", $e->getMessage());
            http_response_code(401);
            exit(-1);
		}

		http_response_code(200);
		exit(0);
	}
}

$file = fopen('orders-paid.txt', 'w');
fwrite($file, 'false');
fclose($file);
http_response_code(401);
