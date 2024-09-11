<?php

require __DIR__ . '/_config.php';

// Route process
$route = isset($_GET['route']) ? $_GET['route'] : null;
switch ($route) {
  case 'clear':
    session_destroy();
    // Redirect back
    header('Location: ./index.php');
    break;

  case 'order':
  case 'index':
  default:
    # code...
    break;
}

// Get the order from session
$order = isset(Config::$session['order']) ? Config::$session['order'] : [];
// Get last form data if exists
$config = isset(Config::$session['config']) ? Config::$session['config'] : [];
// Get merchant list if exist
$merchants = Merchant::getList();
// Get log
$logs = isset(Config::$session['logs']) ? Config::$session['logs'] : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" type="image/x-icon" class="js-site-favicon" href="https://github.com/fluidicon.png">
    <title>Tool - yidas/checkout-sdk-php</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
      pre.log {
        word-break: break-all; 
        white-space: pre-wrap; 
        font-size: 9pt;
        background-color: #f5f5f5;
        padding: 5px;
      }
    </style>
    <script>
      /**
       * Form Submit for Online or Offline API ways
       * 
       * @param element form
       */
      function formSubmit(form) {
        form.action = "auth.php";
        if (form.transactionId.value) {
          form.action = "get-payment-details.php";
        }
        form.submit();
        return;
      }
    </script>
</head>
<body>
<div style="padding:30px 10px; max-width: 600px; margin: auto;">
  <h3>Checkout API Tool <a href="https://github.com/yidas/checkout-sdk-php"><img src="https://github.com/favicon.ico" height="20" width="20"></a></h3>

  <?php if($route=='order'): ?>
  <?php $status = (!isset($order['isSuccessful'])) ? 'none' : (($order['isSuccessful']) ? 'successful' : 'failed') ?>

  <div class="alert alert-<?php if($status=='none'):?>warning<?php elseif($status=='successful'):?>success<?php else:?>danger<?php endif?>" role="alert">
  <h4 class="alert-heading"><?php if($status=='none'):?>Transaction not found<?php elseif($status=='successful'):?>Transaction complete<?php else:?>Transaction failed<?php endif?>!</h4>
    <?php if($status!='none'):?>
    <?php if($status=='failed'):?>
    <hr>
    <p>ErrorCode: <?=$order['confirmCode']?></p>
    <p>ErrorMessage: <?=$order['confirmMessage']?></p>
    <?php endif ?>
    <hr>
    <p>PaymentId: <?=$order['transactionId']?></p>
    <p>Reference: <?= isset($order['params']['reference']) ? $order['params']['reference'] : 'No reference'?></p>
    <p>Amount: <?=$order['params']['amount']?></p>
    <p>Currency: <?=$order['params']['currency']?></p>
    <hr>
    <p>Environment: <?php if($order['isSandbox']):?>Sandbox<?php else:?>Real<?php endif ?></p>
    <?php endif ?>
    <?php if(isset($order['actions'])):?>
      <hr>
      <p><strong>Action List</strong></p>
      <?php foreach ($order['actions'] as $key => $action): ?>
      <p>Id: <?=$action['id']?></p>
      <ul>
        <!-- <li>Id: <?=$action['id']?></li> -->
        <li>Type: <?=$action['type']?></li>
        <li>Processed on (<?=date('c', strtotime($action['processed_on']))?>)</li>
        <li>Amount: <?=$action['amount']?></li>
        <li>Approved: <?=$action['approved'] ? "True" : "False"?></li>
      </ul>
      <?php endforeach ?>
      <a href="./get-payment-actions.php?transactionId=<?=$order['transactionId']?>" class="btn btn-info">Update Actions</a>
    <?php endif ?>
    <?php if(isset($config['captureFalse'])):?>
      <hr>
      <p><strong>Capture Info</strong></p>
      <p>Pay Status: <?=isset($order['info']['payStatus']) ? $order['info']['payStatus'] : ''?></p>
      <div class="clearfix">
        <div class="float-left">
          <div class="input-group">
            <input type="text" id="capture-amount" class="form-control" placeholder="Amount" size="7">
            <div class="input-group-append">
              <button class="btn btn-primary" type="button" onclick="location.href='./capture.php?transactionId=<?=$order['transactionId']?>&amount=' + document.getElementById('capture-amount').value">Capture</button>
            </div>
          </div>
        </div>
        <div class="float-right">
          <a href="./void.php?transactionId=<?=$order['transactionId']?>" class="btn btn-danger">Void</a>
        </div>
      </div>
    <?php endif ?>
    <hr>
    <div class="clearfix">
      <div class="float-left">
        <a href="./index.php" class="btn btn-light">Go Back</a>
      </div>
      <div class="float-right">
        <?php if($status=='successful'):?>

        <div class="input-group">
          <input type="text" id="refund-amount" class="form-control" placeholder="Amount" size="7">
          <div class="input-group-append">
            <button class="btn btn-danger" type="button" onclick="location.href='./refund.php?transactionId=<?=$order['transactionId']?>&amount=' + document.getElementById('refund-amount').value">Refund</button>
          </div>
        </div>
        <!-- <input type="text" class="form-control" size="5" style="display: inline; width: 50px;" />
        <a href="./refund.php?transactionId=<?=$order['transactionId']?>" class="btn btn-danger">Refund</a> -->
        <?php endif ?>
      </div>
    </div>
  </div>

  <?php else: ?>

  <form method="POST" onsubmit="formSubmit(this);return;">
    <?php if($merchants): ?>
    <div class="merchant-block form-group" data-block-id="config" style="display: none;">
      <label for="inputChannelId">Merchant (<a class="btn-merchant-switch" href="javascript:void(0);" data-block-id="config">Switch to Custom</a>)</label>
      <select class="form-control" name="merchant" disabled>
      <?php foreach($merchants as $key => $merchant): ?>
        <option value="<?=$key?>" <?php if(isset($config['merchant']) && $config['merchant']==$key):?>selected<?php endif ?>><?=isset($merchant['title']) ? $merchant['title'] : (isset($merchant['keyId']) ? "KeyId: {$merchant['keyId']}" : "(Merchant - {$key})")?></option>
      <?php endforeach ?>
      </select>
     </div>
    <?php endif ?>
    <div class="merchant-block" data-block-id="custom">
      <div class="form-group">
        <label for="inputChannelSecret">ApiSecretKey <?php if($merchants): ?>(<a class="btn-merchant-switch" href="javascript:void(0);" data-block-id="custom">Switch to Config</a>)<?php endif ?></label>
        <input type="text" class="form-control" id="inputChannelSecret" name="apiSecretKey" placeholder="Enter ApiSecretKey" value="<?=(!isset($config['merchant']) && isset($config['ApiSecretKey'])) ? $config['ApiSecretKey'] : ''?>" required>
      </div>
    </div>
    <div class="form-group">
      <label for="inputProductName">ProductName</label>
      <input type="text" class="form-control" id="inputProductName" name="productName" placeholder="Your product name"  value="<?=isset($config['productName']) ? $config['productName'] : 'QA Service Pack'?>">
    </div>
    <div class="form-group">
      <label for="inputAmount">Amount (<a href="https://www.checkout.com/docs/payments/accept-payments/calculating-the-amount" target="_blank">minor currency unit</a>)</label>
      <input type="text" class="form-control" id="inputAmount" name="amount" placeholder="Your product amount" value="<?=isset($config['amount']) ? $config['amount'] : '1200'?>" required value="250">
    </div>
    <div class="form-group">
      <label for="inputCurrency">Currency</label>
      <input type="text" class="form-control" id="inputCurrency" name="currency" placeholder="Currency" value="<?=isset($config['currency']) ? $config['currency'] : 'TWD'?>" required>
    </div>
    <div class="form-group">
      <label for="inputCurrency">PAN (Card Number)</label>
      <input type="text" class="form-control" id="inputPan" name="pan" placeholder="The card number" value="<?=isset($config['pan']) ? $config['pan'] : '4242424242424242'?>" required>
    </div>
    <div class="form-group">
      <label for="inputCurrency">Expired Date</label>
      <div class="container">
        <div class="row">
            <input type="text" class="form-control col-sm-2" id="inputExpiryMonth" name="expiryMonth" placeholder="MM" value="<?=isset($config['expiryMonth']) ? $config['expiryMonth'] : date("m")?>" required>
            <input type="text" class="form-control col-sm-3" id="inputExpiryYear" name="expiryYear" placeholder="YYYY" value="<?=isset($config['expiryYear']) ? $config['expiryYear'] : (date("Y")+10)?>" required>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col col-4">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputSandbox" name="isSandbox" <?=isset($config['isSandbox']) && !$config['isSandbox'] ? '' : 'checked'?>>
          <label class="form-check-label" for="inputSandbox">Sandbox</label>
        </div>
      </div>
      <div class="col col-8 text-right">
        <a href="javascript:void(0);" data-toggle="collapse" data-target="#collapseMoreSettings">More Settings</a>
        |
        <a href="javascript:void(0);" data-toggle="modal" data-target="#logModal">View Logs</a>
      </div>
    </div>
    <div class="collapse" id="collapseMoreSettings">
      <div class="card card-body">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputCaptureFalse" name="captureFalse" <?=isset($config['captureFalse']) ? 'checked' : ''?>>
          <label class="form-check-label" for="inputCaptureFalse">Capture: <code>false</code></label>
        </div>
        <hr>
        <div class="form-group">
          <label>Overwrite Fields</label>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">OrderId</span>
            </div>
            <input type="text" name="orderId" class="form-control" placeholder="Fill in to overwrite orderId">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">ImageUrl</span>
            </div>
            <input type="text" name="imageUrl" class="form-control" placeholder="Fill in to overwrite imageUrl (Online Only)">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">ConfirmUrl</span>
            </div>
            <input type="text" name="confirmUrl" class="form-control" placeholder="Fill in to overwrite confirmUrl (Online Only)">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">CancelUrl</span>
            </div>
            <input type="text" name="cancelUrl" class="form-control" placeholder="Fill in to overwrite cancelUrl (Online Only)">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">appPackageName</span>
            </div>
            <input type="text" name="appPackageName" class="form-control" placeholder="redirectUrls.appPackageName (Online Only)">
          </div>
        </div>
        <hr>
        <div class="form-group">
          <label>Search Transaction by Id</label>
          <div class="input-group">
            <input type="text" class="form-control" name="transactionId" placeholder="Input CKO paymentId to search">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" type="submit">Submit</button>
            </div>
          </div>
        </div>
        <hr>
        <div class="form-group">
          <label>Rewrite Request Body <font color="#cccccc"><i>(JSON string will be decoded then encoded)</i></font></label>
          <div class="input-group">
            <textarea class="form-control" name="requestBody" id="" rows="4" style="font-size: 9pt;"></textarea>
          </div>
        </div>
      </div>
    </div>
    <hr>
    <div class="row">
      <div class="col col-12 col-md-4" style="padding-bottom:5px;">
        <button type="submit" class="btn btn-primary btn-block">Request New Order</button>
      </div>
      <!-- <div class="col col-12 col-md-8 text-right" style="padding-bottom:5px;">
        <?php if(isset($order['isSuccessful'])):?><a href="./index.php?route=order" class="btn btn-info">Check Last Order</a><?php endif ?>
        <button type="reset" class="btn btn-success">Reset</button>
        <button type="button" class="btn btn-danger" onclick="if(confirm('Confirm to clear saved form data?')){location.href='?route=clear'}">Clear</button>
      </div> -->
      <div class="col col-12 col-md-4" style="padding-bottom:5px;">
      <?php if(isset($order['isSuccessful'])):?>
        <a href="./index.php?route=order" class="btn btn-info btn-block">Review Last Order</a>
      <?php endif ?>
      </div>
      <div class="col col-12 col-md-2" style="padding-bottom:5px;">
        <button type="reset" class="btn btn-success btn-block">Reset</button>
      </div>
      <div class="col col-12 col-md-2" style="padding-bottom:5px;">
        <button type="button" class="btn btn-danger btn-block" onclick="if(confirm('Confirm to clear saved form data?')){location.href='?route=clear'}">Clear</button>
      </div>
    </div>
  </form>

  <!-- Template -->
  <script type="text/template" id="eventsCodeTemplate">
    <div class="input-group input-group-sm">
      <div class="input-group-prepend">
        <span class="input-group-text" style="min-width: 120px;">Code</span>
      </div>
      <input type="text" name="eventsCode[]" class="form-control" placeholder="extras.events.code">
    </div>
    <div class="input-group input-group-sm">
      <div class="input-group-prepend">
        <span class="input-group-text" style="min-width: 120px;">TotalAmount</span>
      </div>
      <input type="number" name="eventsTotalAmount[]" class="form-control" placeholder="extras.events.totalAmount">
    </div>
    <div class="input-group input-group-sm">
      <div class="input-group-prepend">
        <span class="input-group-text" style="min-width: 120px;">Quantity</span>
      </div>
      <input type="number" name="eventsProductQuantity[]" class="form-control" placeholder="extras.events.productQuantity">
    </div>
  </script>
  <!-- /Template -->

  <?php endif ?>

  <!-- Modal for log -->
  <div class="modal fade" id="logModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Log (Reset by New Order)</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
        <?php foreach ((array) $logs as $key => $log): ?>
          <?php if($key!==0):?>
          <hr>
          <?php endif ?>
          <div>
            <p><strong><?=$log['name']?></strong> (<?=$log['datetime']?>)</p>
            <?php if($log['fromSDK']):?>
            <div class="alert alert-light small" role="alert">
              <?=$log['msg']?><br>
            </div>
            <?php else:?>
            <div class="alert alert-light small" role="alert">
              <strong><?=$log['method']?></strong> <?=$log['uri']?><br>
              <strong>TransferTime</strong>: <?=$log['transferTime']?> s
            </div>
            <p>Request body: <small>(<?=$log['request']['datetime']?>)</small></p>
            <pre class="log"><?=$log['request']['content']?></pre>
            <p>Response body: <small>(<?=$log['response']['datetime']?>)</small></p>
            <pre class="log"><?=$log['response']['content']?></pre>
            <?php endif ?>
          </div>
        <?php endforeach ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

</div>
<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js" integrity="sha512-a+SUDuwNzXDvz4XrIcXHuCf089/iJAoN4lmrXJg18XnduKK6YlDHNRalv4yd1N40OKI80tFidF+rqTFKGPoWFQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>

  // Merchant config block
  var elBlockConfig = document.querySelector(".merchant-block[data-block-id='config']");

  // jQuery asset loading precaution (ensure that general functionality is available without jQuery)
  if (typeof $ === 'undefined' && elBlockConfig) {
    elBlockConfig.parentNode.removeChild(elBlockConfig);
  }

  // Merchant switch (jQuery required)
  if (elBlockConfig) {

    $(".btn-merchant-switch").click(function () {
      var self = $(this).data("block-id");
      var target = (self==="custom") ? 'config' : 'custom';
      var $selfBlock = $(".merchant-block[data-block-id='" + self + "']");
      var $targetBlock = $(".merchant-block[data-block-id='" + target + "']");
      // Switch
      $selfBlock.find("input, select").prop('disabled', true);
      $selfBlock.hide(300, function () {
        $targetBlock.find("input, select").prop('disabled', false);
        $targetBlock.show(200);
      });
    });
  }

  // EventCode Add Button
  $(".btn-events-code-add").click(function () {
    $("#eventsCodeBlock").append($("#eventsCodeTemplate").html()).append("<br>");
  });

  <?php if($merchants && (!$config || isset($config['merchant']))): ?>
  // Action for merchant config condition
  $(".merchant-block[data-block-id='custom']").find(".btn-merchant-switch").click();
  <?php endif ?>

</script>
</body>
</html>