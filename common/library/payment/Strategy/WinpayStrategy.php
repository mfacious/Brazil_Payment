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

class WinpayStrategy implements PaymentStrategy
{
    public string $appId;
    public string $merchantCode;
    public string $mchPrivateKey;
    public string $payNotifyUrl = '';
    public string $payoutNotifyUrl = '';
    protected $logger;

    private $payRequestUrl = 'https://api.winpay.site/br/payment.json';
    private $cashRequestUrl = 'https://api.winpay.site/br/payout.json';

    public function __construct(PaymentConfig $paymentConfig, LoggerInterface $logger = null)
    {
        $logger = $logger ?: new Log();
        $this->logger = $logger;
        $this->appId = $paymentConfig->merchantName ?: Config::get('winpay.appId');
        $this->mchPrivateKey = $paymentConfig->merchantPrivateKey ?: Config::get('winpay.mchPrivateKey');
        $this->merchantCode = $paymentConfig->merchantCode ?: Config::get('winpay.merchantCode');
        $this->setPayNotifyUrl();
        $this->setPayoutNotifyUrl();
    }

    public function setPayNotifyUrl()
    {
        $this->payNotifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/callback/winpay/pay';
    }

    public function setPayoutNotifyUrl()
    {
        $this->payoutNotifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/callback/winpay/payout';
    }

    public function pay(PayRequest $req): PaymentResponse
    {
        $params = array(
            'appId' => $this->appId,
            'amount' => $req->amount,
            'countryCode' => 'BR',
            'currencyCode' => 'BRL',
            'type' => '0101',
            'custId' => $this->merchantCode,
            'merchantOrderId' => $req->orderNo,
            'userName' => $req->orderNo,
            'backUrl' => $this->payNotifyUrl,
            'remark' => $req->description
        );
        $this->buildParams($params);
        $response = $this->doRequest($this->payRequestUrl, $params);
        $response['url'] = $response['payContent'] ?? '';
        return $this->adaptResponse($response);
    }

    private function buildParams(&$params)
    {
        ksort($params);
        $params['sign'] = $this->privateKeyEncrypt($params);
    }

    private function privateKeyEncrypt(array $params): string
    {
        $params = array_filter($params);
        $paramsStr = http_build_query($params);
        $paramsStr = urldecode($paramsStr) . '&key=' . $this->mchPrivateKey;
        return md5($paramsStr);
    }

    protected function doRequest($requestUrl, $params)
    {
        $postFields = json_encode($params);
        $contentLength = strlen($postFields);
        $contentType = 'application/json';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'User-Agent: Apifox/1.0.0 (https://apifox.com)',
                'Content-Type: ' . $contentType,
                'Content-Length: ' . $contentLength
            )
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $this->logger->log('info', 'WINPAY API request:' . json_encode(['requestUrl' => $requestUrl, 'postFields' => $postFields, 'contentLength' => $contentLength, 'response' => $response]));
        if (!$err) {
            return json_decode($response, true);
        } else {
            throw new Exception("remote request failed with status: " . $httpCode . ", error" . $err);
        }
    }

    private function adaptResponse($response): PaymentResponse
    {
        $r = new PaymentResponse();
        $r->isSuccess = $response['code'] == '000000';
        $r->successFlag = $response['code'] == '000000' ? 'SUCCESS' : $response['code'];
        $r->respMessage = $response['msg'];
        $r->platOrderNum = $response['order'] ?? '';
        $r->payURL = $response['url'] ?? '';
        $r->originJson = json_encode($response);
        return $r;
    }

    public function payout(PayoutRequest $req): PaymentResponse
    {
        $params = array(
            'amount' => $req->amount,
            'appId' => $this->appId,
            'backUrl' => $this->payoutNotifyUrl,
            'cardType' => $req->cardType,
            'countryCode' => 'BR',
            'currencyCode' => 'BRL',
            'custId' => $this->merchantCode,
            'email' => !empty($req->pixAccount) ? $req->pixAccount : '18172644479@ca.com',
            'merchantOrderId' => $req->orderNo,
            'cpf' => $req->cpf,
            'phone' => !empty($req->pixAccount) ? $req->pixAccount : '18172644479',
            'remark' => $req->description ?? 'default description',
            'type' => 'PIX',
            'userName' => !empty($req->username) ? $req->username : 'anounymous',
            'walletId' => $req->walletId,
        );
        $this->buildParams($params);
        $response = $this->doRequest($this->cashRequestUrl, $params);
        return $this->adaptResponse($response);
    }
}