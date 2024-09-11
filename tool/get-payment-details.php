<?php

require __DIR__ . '/_config.php';

use Checkout\CheckoutSdk;
use Checkout\Environment;
use Checkout\CheckoutApiException;

$input = $_POST;
$input['isSandbox'] = (isset($input['isSandbox'])) ? true : false;
// Merchant config option
if (isset($input['merchant'])) {
    $merchant = Merchant::getMerchant($input['merchant']);
    $input['apiSecretKey'] = $merchant['apiSecretKey'];
}

try {
    // Create API client
    $checkoutApi = CheckoutSdk::builder()->staticKeys()
        ->secretKey($input['apiSecretKey'])
        ->environment($input['isSandbox'] ? Environment::sandbox() : Environment::production()) // or production()
        ->logger(new Logger) //optional, for a custom Logger
        ->build();
} catch (\Throwable $e) {
    die("SDK Error: " . $e->getMessage());
}

// Get the transactionId from query parameters
$transactionId = (string) $input['transactionId'];

$paymentsClient = $checkoutApi->getPaymentsClient();

try {

    $response = $paymentsClient->getPaymentDetails($transactionId);
    // var_dump($response);exit();

} catch (CheckoutApiException $e) {
    
    // var_dump($e);
    die(sprintf("<script>alert('Refund Failed\\nErrorType: HTTP code not 200\\nErrorMessage: {$e->getMessage()}');location.href='%s';</script>", Config::$successUrl));
}

if (empty($response)) {
    die(sprintf("<script>alert('Transaction not found');location.href='%s';</script>", Config::$successUrl));
}

Config::$session['order'] = [
    'isSuccessful' => true,
    'transactionId' => $response["id"],
    'params' => $response,
    'isSandbox' => $input['isSandbox'], 
];
// Save input for next process and next form
Config::$session['config'] = $input;

// Redirect to successful page
header("Location: " . Config::$successUrl);