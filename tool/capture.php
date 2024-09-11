<?php

require __DIR__ . '/_config.php';

use Checkout\CheckoutSdk;
use Checkout\Environment;
use Checkout\CheckoutApiException;
use Checkout\Payments\CaptureRequest;

// Get saved config
$config = Config::$session['config'];
// Create LINE Pay client
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
$captureRequest = new CaptureRequest;
$captureRequest->amount = (isset($_GET['amount']) && $_GET['amount']!="") ? (integer) $_GET['amount'] : null;
$captureRequest->reference = ($order['params']['reference']) ? $order['params']['reference'] . "R" . (string) substr(round(microtime(true) * 1000), -3) : null;

try {

    $response = $paymentsClient->capturePayment($transactionId, $captureRequest);
    // var_dump($response['http_metadata']);exit;

} catch (CheckoutApiException $e) {
    
    // var_dump($e->http_metadata);exit();
    // var_dump($e->error_details);exit();
    die(sprintf("<script>alert('Refund Failed\\nErrorType: HTTP code not 200\\nErrorMessage: {$e->getMessage()}');location.href='%s';</script>", Config::$successUrl));
}

if (!isset($response['action_id'])) {
    die(sprintf("<script>alert('Refund Failed\\nErrorCode: {$response['returnCode']}\\nErrorMessage: {$response['returnMessage']}');location.href='%s';</script>", Config::$successUrl));
}

# Get actions
// try {

//     $response = $paymentsClient->getPaymentActions($transactionId);

// } catch (CheckoutApiException $e) {
    
//     // var_dump($e);exit();
//     die(sprintf("<script>alert('Refund Failed\\nErrorType: HTTP code not 200\\nErrorMessage: {$e->getMessage()}');location.href='%s';</script>", Config::$successUrl));
// }

// if (empty($response)) {
//     die(sprintf("<script>alert('Transaction not found');location.href='%s';</script>", Config::$successUrl));
// }

// Redirect to successful page
header("Location: " . Config::$getActionsUrl . "?transactionId={$transactionId}");