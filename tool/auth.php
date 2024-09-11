<?php

require __DIR__ . '/_config.php';

use Checkout\CheckoutSdk;
use Checkout\Environment;
use Checkout\CheckoutApiException;
use Checkout\Payments\Request\PaymentRequest;

$input = $_POST;
$input['isSandbox'] = (isset($input['isSandbox'])) ? true : false;
// Merchant config option

if (isset($input['merchant'])) {
    $merchant = Merchant::getMerchant($input['merchant']);
    $input['apiSecretKey'] = $merchant['apiSecretKey'];
}

try {
    $checkoutApi = CheckoutSdk::builder()->staticKeys()
    ->secretKey($input['apiSecretKey'])
    ->environment($input['isSandbox'] ? Environment::sandbox() : Environment::production()) // or production()
    ->logger(new Logger) //optional, for a custom Logger
    ->build();

} catch (\Throwable $e) {
    die("SDK Error: " . $e->getMessage());
}

$paymentsClient = $checkoutApi->getPaymentsClient();
$paymentRequest = new PaymentRequest;
$paymentRequest->source = (object) [
    'type' => 'card',
    'number' => $input['pan'],
    'expiry_month' => $input['expiryMonth'],
    'expiry_year' => $input['expiryYear'],
];
$paymentRequest->amount = $input['amount']; // Decimal points format for CKO TWD 
$paymentRequest->currency = $input['currency'];
$paymentRequest->reference = ($input['orderId']) ? $input['orderId'] : "SN" . date("YmdHis") . (string) substr(round(microtime(true) * 1000), -3);

// Capture: false
if (isset($input['captureFalse'])) {
    $paymentRequest->capture = false;
}

try {

    $response = $paymentsClient->requestPayment($paymentRequest);
    // var_dump($response['http_metadata']);exit;

} catch (CheckoutApiException $e) {
    
    // var_dump($e);
    die(sprintf("<script>alert('Refund Failed\\nErrorType: HTTP code not 200\\nErrorMessage: {$e->getMessage()}');location.href='%s';</script>", Config::$successUrl));
}

if ($response['response_code'] != "10000") {
    Config::$session['cko-sdk-tool']['order']['isSuccessful'] = false;
    Config::$session['cko-sdk-tool']['order']['confirmCode'] = $response['response_code'];
    Config::$session['cko-sdk-tool']['order']['confirmMessage'] = $response['response_summary'];
    die(sprintf("<script>alert('Details Failed\\nErrorCode: {$response['response_code']}\\nErrorMessage: {$response['response_summary']}');location.href='%s';</script>", Config::$successUrl));
}

Config::$session['order'] = [
    'isSuccessful' => true,
    'transactionId' => $response["id"],
    'params' => $response,
    'isSandbox' => $input['isSandbox'], 
];
// Save input for next process and next form
Config::$session['config'] = $input;

// Redirect to LINE Pay payment URL 
header("Location: " . Config::$successUrl);
