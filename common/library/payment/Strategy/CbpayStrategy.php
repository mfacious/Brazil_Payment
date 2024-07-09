<?php

namespace app\common\library\payment\Strategy;

use app\common\library\Log;
use app\common\library\payment\PaymentConfig;
use app\common\library\payment\PaymentResponse;
use app\common\library\payment\PaymentStrategy;
use app\common\library\payment\Request\PayoutRequest;
use app\common\library\payment\Request\PayRequest;
use Psr\Log\LoggerInterface;
use Config;
use Exception;

class CbpayStrategy implements PaymentStrategy
{
    public string $merchantCode;
    public string $mchPrivateKey;
    public string $payNotifyUrl = '';
    public string $payoutNotifyUrl = '';
    protected $logger;

    private string $payRequestUrl = 'https://pay3.cbpay888.com/';
    private string $cashRequestUrl = 'https://pay3.cbpay888.com/payout';

    public function __construct(PaymentConfig $paymentConfig, LoggerInterface $logger = null)
    {
        $logger = $logger ?: new Log();
        $this->logger = $logger;
        $this->mchPrivateKey = $paymentConfig->merchantPrivateKey ?: Config::get('cbpay.mchPrivateKey');
        $this->merchantCode = $paymentConfig->merchantCode ?: Config::get('cbpay.merchantCode');
        $this->setPayNotifyUrl();
        $this->setPayoutNotifyUrl();
    }

    public function setPayNotifyUrl()
    {
        $this->payNotifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/callback/cbpay/pay';
    }

    public function setPayoutNotifyUrl()
    {
        $this->payoutNotifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/callback/cbpay/payout';
    }

    public function pay(PayRequest $req): PaymentResponse
    {
        $params = array(
            'version' => '1.0.0',
            'mer_id' => $this->merchantCode,
            'order_id' => $req->orderNo,
            'amount' => $req->amount,
            'order_time' => date('Y-m-d H:i:s'),
            'notifyurl' => $this->payNotifyUrl,
            'paytype' => '1001',
            'name' => $req->username,
            'email' => $req->username . '@faker.com',
            'body' => $req->description,
            'method' => 'md5',
        );
        $params['sign'] = $this->privateKeyEncrypt($params);
        $response = $this->doRequest($this->payRequestUrl, $params);
        return $this->adaptResponse($response);
    }

    public function payout(PayoutRequest $req): PaymentResponse
    {
        $params = array(
            'mer_id' => $this->merchantCode,
            'order_id' => $req->orderNo,
            'amount' => $req->amount,
            'order_time' => date('Y-m-d H:i:s'),
            'currency' => 'BRL',
            'notifyurl' => $this->payoutNotifyUrl,
        );

        if ($req->cardType == 'CPF') {
            $data = array(
                'accountType' => 'PIX_CPF',
                'cpf' => $req->cpf,
                'accountName' => $req->username
            );
        } else {
            if (filter_var($req->pixAccount, FILTER_VALIDATE_EMAIL)) {
                $data = array(
                    'accountType' => 'PIX_EMAIL',
                    'email' => $req->pixAccount,
                    'accountName' => $req->username
                );
            } else {
                $data = array(
                    'accountType' => 'PIX_PHONE',
                    'phone' => $req->pixAccount,
                    'accountName' => $req->username
                );
            }
        }

        $this->buildCashParams($params, $data);
        $response = $this->doRequest($this->cashRequestUrl, $params);
        $r = new PaymentResponse();
        $r->isSuccess = $response['success'];
        $r->successFlag = $response['success'] ? 'SUCCESS' : '';
        $r->respMessage = $response['msg'];
        $r->platOrderNum = $response['sys_order_id'];
        $r->originJson = json_encode($response);
        return $r;
    }

    public function checkSign($param)
    {
        $sign = $param['sign'] ?? '';
        unset($param['sign']);
        if ($this->privateKeyEncrypt($param) == $sign) {
            return true;
        }
        return false;
    }

    private function privateKeyEncrypt(array $params): string
    {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value){
            $signStr .= $key."=".$value."&";
        }

        $signStr = substr($signStr,0,-1);
        return strtoupper(md5($signStr.$this->mchPrivateKey));
    }

    private function adaptResponse($response): PaymentResponse
    {
        $r = new PaymentResponse();
        if ($response['status'] && $this->checkSign($response)) {
            $r->isSuccess = true;
            $r->successFlag = 'SUCCESS';
            $r->respMessage = $response['msg'];
            $r->payURL = $response['url'] ?? '';
            $r->platOrderNum = '';
        } else {
            $r->isSuccess = false;
            $r->successFlag = '';
            $r->respMessage = $response['msg'];
        }
        $r->originJson = json_encode($response);
        return $r;
    }

    protected function doRequest($requestUrl, $params)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($params)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $this->logger->log('info', 'CBPAY API request:' . json_encode(['requestUrl' => $requestUrl, 'postFields' => json_encode($params), 'response' => $response]));
        if (! $err) {
            return json_decode($response, true);
        } else {
            throw new Exception("CBPAY remote request failed with status: " . $httpCode . ", error" . $err);
        }
    }

    protected function buildCashParams(&$params, $data)
    {
        ksort($params);
        $stringA = http_build_query($params);
        ksort($data);
        $stringB = http_build_query($data);
        $signStr = urldecode($stringA) . '&' . $stringB;
        $params['sign'] = strtoupper(md5($signStr.$this->mchPrivateKey));
        $params['data'] = json_encode($data);
    }
}