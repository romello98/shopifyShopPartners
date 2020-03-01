<?php

require_once __DIR__ . '/private/dataService.php';

$action = get_clean_obtain('action');
$dataService = new DataService();

switch($action)
{
    case 'ORDERS_BY_PAYMENT_REQUEST_ID':
        $paymentRequestID = get_clean_obtain('ID');
        $orders = $dataService->getOrdersByPaymentRequestIDAndCurrentUser($paymentRequestID);
        echo json_encode($orders);
        http_response_code(200);
    break;
    case 'GET_PROMOTION_TOOLS_BY_PRODUCT_ID':
        $productID = get_clean_obtain('ID');
        $path =  __DIR__ . "/promotion-tools/product-$productID/";
        $files = array_diff(scandir($path), array('.', '..'));
        $files = array_values($files);
        echo json_encode($files);
        http_response_code(200);
    break;
    default:
        header('Location: /');
    break;
}

exit(0);