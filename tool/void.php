<?php

require __DIR__ . '/_config.php';

use Checkout\CheckoutSdk;
use Checkout\Environment;
use Checkout\CheckoutApiException;
use Checkout\Payments\VoidRequest;

// Get saved config
$config = Config::$session['config'];
// Create API client
$checkoutApi = CheckoutSdk::builder()->staticKeys()
    ->secretKey($config['apiSecretKey'])
    ->environment($config['isSandbox'] ? Environment::sandbox() : Environment::production()) // or production()
    ->logger(new Logger) //optional, for a custom Logger
    ->build();

// Get the transactionId from query parameters
$transactionId = (string) $_GET['transactionId'];
// Get the order from session
$order = Config::$session['order'];

// Check transactionId (Optional)
if ($order['transactionId'] != $transactionId) {
    die(sprintf("<script>alert('TransactionId doesn\'t match');location.href='%s';</script>", Config::$indexUrl));
}

$paymentsClient = $checkoutApi->getPaymentsClient();
$voidRequest = new VoidRequest;
$voidRequest->reference = ($order['params']['reference']) ? $order['params']['reference'] . "R" . (string) substr(round(microtime(true) * 1000), -3) : null;

try {

    $response = $paymentsClient->voidPayment($transactionId, $voidRequest);
    // var_dump($response['http_metadata']);exit;

} catch (CheckoutApiException $e) {
    
    // var_dump($e->http_metadata);exit();
    // var_dump($e->error_details);exit();
    die(sprintf("<script>alert('Refund Failed\\nErrorType: HTTP code not 200\\nErrorMessage: {$e->getMessage()}');location.href='%s';</script>", Config::$successUrl));
}

if (!isset($response['action_id'])) {
    die(sprintf("<script>alert('Refund Failed\\nErrorCode: {$response['returnCode']}\\nErrorMessage: {$response['returnMessage']}');location.href='%s';</script>", Config::$successUrl));
}

// Redirect to successful page
header("Location: " . Config::$getActionsUrl . "?transactionId={$transactionId}");