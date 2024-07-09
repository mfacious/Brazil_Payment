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

class ToppayStrategy implements PaymentStrategy
{
    public string $platPublicKey;
    public string $mchPrivateKey;
    public string $merchantCode;
    public string $payNotifyUrl = '';
    public string $payoutNotifyUrl = '';
    protected $logger;
    private string $payRequestUrl = 'https://bra-openapi.toppay.asia/gateway/prepaidOrder';
    private string $cashRequestUrl = 'https://bra-openapi.toppay.asia/gateway/cash';
    private string $balanceRequestUrl = 'https://bra-openapi.toppay.asia/gateway/interface/getBalance';
    private string $queryRequestUrl = 'https://bra-openapi.toppay.asia/gateway/query';

    public function __construct(PaymentConfig $paymentConfig, LoggerInterface $logger = null)
    {
        $logger = $logger ?: new Log();
        $this->logger = $logger;
        $this->platPublicKey = $paymentConfig->platPublicKey ?: Config::get('toppay.platPublicKey');
        $this->mchPrivateKey = $paymentConfig->merchantPrivateKey ?: Config::get('toppay.mchPrivateKey');
        $this->merchantCode = $paymentConfig->merchantCode ?: Config::get('toppay.merchantCode');
        $this->setPayNotifyUrl();
        $this->setPayoutNotifyUrl();
    }

    public function setPayNotifyUrl()
    {
        $this->payNotifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/callback/toppay/pay';
    }

    public function setPayoutNotifyUrl()
    {
        $this->payoutNotifyUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/callback/toppay/payout';
    }

    public function pay(PayRequest $req): PaymentResponse
    {
        $params = array(
            'merchantCode' => $this->merchantCode,
            'orderType' => '0',
            'method' => 'PIX',
            'orderNum' => $req->orderNo,
            'payMoney' => intval($req->amount) / 100,
            'name' => $req->username,
            'notifyUrl' => $this->payNotifyUrl,
            'description' => $req->description
        );
        $this->buildParams($params);
        $response = $this->doRequest($this->payRequestUrl, $params);
        return $this->adaptResponse($response);
    }

    private function buildParams(&$params)
    {
        ksort($params);
        $paramsStr = '';
        foreach ($params as $v) {
            $paramsStr = $paramsStr . $v;
        }
        $params['sign'] = $this->privateKeyEncrypt($paramsStr);
    }

    public function privateKeyEncrypt(string $params): string
    {
        $privateKey = '-----BEGIN PRIVATE KEY-----' . "\n" . $this->mchPrivateKey . "\n" . '-----END PRIVATE KEY-----';
        $piKey = openssl_pkey_get_private($privateKey);
        $crypto = '';
        foreach (str_split($params, 117) as $chunk) {
            openssl_private_encrypt($chunk, $encryptData, $piKey);
            $crypto .= $encryptData;
        }

        return base64_encode($crypto);
    }

    protected function doRequest($requestUrl, $params)
    {
        $postFields = json_encode($params);
        $contentLength = strlen($postFields);
        $contentType = 'application/json';
        $ch = curl_init();
        $this->logger->log('info', 'TOPPAY request info:' . json_encode(['requestUrl' => $requestUrl, 'postFields' => $postFields, 'contentLength' => $contentLength]));

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: ' . $contentType,
                'Content-Length: ' . $contentLength
            )
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 200) {
            $this->logger->log('info', 'TOPPAY response info:' . $response);
            $result = json_decode($response, true);
            if (isset($result['platSign'])) {
                $result['decryptStr'] = $this->publicKeyDecrypt($result['platSign']);
            }
            return $result;
        } else {
            throw new Exception("remote request failed with status: " . $httpCode);
        }
    }

    public function publicKeyDecrypt(string $crypto): string
    {
        $publicKey = '-----BEGIN PUBLIC KEY-----' . "\n" . $this->platPublicKey . "\n" . '-----END PUBLIC KEY-----';
        $data = base64_decode($crypto);
        $publicKey = openssl_pkey_get_public($publicKey);
        $decryptString = '';
        foreach (str_split($data, 128) as $chunk) {
            openssl_public_decrypt($chunk, $decryptData, $publicKey);
            $decryptString .= $decryptData;
        }

        return $decryptString;
    }

    private function adaptResponse($response): PaymentResponse
    {
        $r = new PaymentResponse();
        $r->isSuccess = $response['platRespCode'] == 'SUCCESS';
        $r->successFlag = $response['platRespCode'];
        $r->respMessage = $response['platRespMessage'];
        $r->platOrderNum = $response['platOrderNum'] ?? '';
        $r->payURL = $response['url'] ?? '';
        $r->originJson = json_encode($response);
        return $r;
    }

    public function payout(PayoutRequest $req): PaymentResponse
    {
        $params = array(
            'merchantCode' => $this->merchantCode,
            'orderType' => '0',
            'method' => 'DISBURSEMENT',
            'orderNum' => $req->orderNo,
            'money' => intval($req->amount) / 100,
            'feeType' => '0',
            'name' => $req->username,
            'pixType' => 'CPF',
            'pixAccount' => $req->pixAccount,
            'taxNumber' => $req->cpf,
            'notifyUrl' => $this->payoutNotifyUrl,
            'description' => $req->description,
        );
        $this->buildParams($params);
        $response = $this->doRequest($this->cashRequestUrl, $params);
        return $this->adaptResponse($response);
    }

    /**
     * 订单查询
     *
     * @param string $orderNo 内部订单号
     * @param string $platOrderNo 平台订单号
     * @param string $queryType 查询类型 代收:ORDER_QUERY.代付:CASH_QUERY
     * @return mixed
     * @throws Exception
     */
    public function orderQuery(string $orderNo, string $platOrderNo, string $queryType = 'ORDER_QUERY')
    {
        $params = [
            'merchantCode' => $this->merchantCode,
            'platOrderNum' => $platOrderNo,
            'orderNum' => $orderNo,
            'queryType' => $queryType,
        ];
        $this->buildParams($params);
        return $this->doRequest($this->queryRequestUrl, $params);
    }

    /**
     * 账户余额查询
     * @param string $currency 币种,BRL(不传展示所有货币单位的余额)
     * @return mixed
     * @throws Exception
     */
    public function balanceQuery(string $currency = 'BRL')
    {
        $params = [
            'merchantCode' => $this->merchantCode,
            'currency' => $currency,
        ];
        $this->buildParams($params);
        return $this->doRequest($this->balanceRequestUrl, $params);
    }
}