# Usage

## Pay
```
    $payConfig = new PaymentConfig($config['merchant_code'], $config['merchant_name'], $config['merchant_private_key'], $config['channel_code']);
    $payConfig->setPlatPublicKey($config['plat_public_key']);
    $strategy = PaymentFactory::getInstance($payConfig);
    $payRequest = new PayRequest($orderNo, $amount, $user->username);
    /** @var @var PaymentResponse $resp */
    $resp = (new PaymentContext($strategy))->pay($payRequest);
    
    if ($resp->successFlag !== 'SUCCESS') {
      // failed
    } else {
      // 
    }
```

## Payout

```
    $payConfig = new PaymentConfig($withdrawConfig['merchant_code'], $withdrawConfig['merchant_name'], $withdrawConfig['merchant_private_key'], $withdrawConfig['method']);
    $payConfig->setPlatPublicKey($withdrawConfig['plat_public_key']);
    $strategy = PaymentFactory::getInstance($payConfig);
    $payoutClient = new PaymentContext($strategy);

    $payoutRequest = new PayoutRequest($order['order_no'], $order['amount'], $order->user_withdraw_account->real_name, $order->user_withdraw_account->pix_account, $order->user_withdraw_account->cpf);
    $cardType  =$order->user_withdraw_account->type == 1 ? 'CPF' : 'PHONE';
    $walletId = $order->user_withdraw_account->type == 1 ? $order->user_withdraw_account->cpf : $order->user_withdraw_account->pix_account;
    $description = 'test cash';
    $payoutRequest->setExtraInfo($cardType, $walletId, $description);
    /** @var PaymentContext $payoutClient */
    $response = $payoutClient->payout($payoutRequest);
    
    if ($resp->successFlag !== 'SUCCESS') {
      // failed
    } else {
      // 
    }
```
