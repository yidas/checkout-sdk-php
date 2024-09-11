<?php

require __DIR__ . '/_config.php';

use Checkout\CheckoutSdk;
use Checkout\Environment;
use Checkout\CheckoutApiException;

// Get saved config
$config = Config::$session['config'];

try {
    // Create API client
    $checkoutApi = CheckoutSdk::builder()->staticKeys()
        ->secretKey($config['apiSecretKey'])
        ->environment($config['isSandbox'] ? Environment::sandbox() : Environment::production()) // or production()
        ->logger(new Logger) //optional, for a custom Logger
        ->build();
        
} catch (\Throwable $e) {
    die("SDK Error: " . $e->getMessage());
}

// Get the transactionId from query parameters
$transactionId = (string) $_GET['transactionId'];

$paymentsClient = $checkoutApi->getPaymentsClient();

try {

    $response = $paymentsClient->getPaymentActions($transactionId);
    // var_dump($response);exit();

} catch (CheckoutApiException $e) {
    
    // var_dump($e);
    die(sprintf("<script>alert('Refund Failed\\nErrorType: HTTP code not 200\\nErrorMessage: {$e->getMessage()}');location.href='%s';</script>", Config::$successUrl));
}

if (empty($response)) {
    die(sprintf("<script>alert('Transaction not found');location.href='%s';</script>", Config::$successUrl));
}

$actionsSession = & Config::$session['order']['actions'];
// Reset actios
$actionsSession = [];

foreach ($response['items'] as $key => $action) {
    // switch ($action['type']) {
    //     case 'Capture':
    //         # code...
    //         break;
        
    //     case 'Authorization':
    //     default:
    //         $orderSession = [
    //             'isSuccessful' => true,
    //             'transactionId' => $response["id"],
    //             'params' => $response,
    //             'isSandbox' => $input['isSandbox'], 
    //         ];
    //         break;
    // }
    $actionsSession[] = $action;
}

// Redirect to successful page
header("Location: " . Config::$successUrl);