<?php

namespace NICO0420\LinePay;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use Exception;

class Client
{
    /**
     * LINE Pay base API host
     */
    const API_HOST = 'https://api-pay.line.me';

    /**
     * LINE Pay Sandbox base API host
     */
    const SANDBOX_API_HOST = 'https://sandbox-api-pay.line.me';

    /**
     * LINE Pay API URI list
     *
     * @var array
     */
    protected static $apiUris = [
        'request' => '/v3/payments/request',
        'confirm' => '/v3/payments/{transactionId}/confirm',
        'refund' => '/v3/payments/{transactionId}/refund',
        'details' => '/v3/payments',
        'check' => '/v3/payments/requests/{transactionId}/check',
        'authorizationsCapture' => '/v3/payments/authorizations/{transactionId}/capture',
        'authorizationsVoid' => '/v3/payments/authorizations/{transactionId}/void',
        'preapproved' => '/v3/payments/preapprovedPay/{regKey}/payment',
        'preapprovedCheck' => '/v3/payments/preapprovedPay/{regKey}/check',
        'preapprovedExpire' => '/v3/payments/preapprovedPay/{regKey}/expire'
    ];

    /**
     * HTTP Client
     *
     * @var GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * PSR-7 Request
     *
     * @var GuzzleHttp\Psr7\Request
     */
    protected $request;

    /**
     * Saved LINE Pay Channel Secret for v3 API Authentication
     *
     * @var string
     */
    protected $channelSecret;

    /**
     * __construct
     *
     * @param string $url
     */
    public function __construct(array $params)
    {
        $channelId = isset($params['channelId']) ? $params['channelId'] : null;
        $channelSecret = isset($params['channelSecret']) ? $params['channelSecret'] : null;
        $isSandbox = isset($params['isSandbox']) ? $params['isSandbox'] : false;

        // Check
        if (!$channelId || !$channelSecret) {
            throw new Exception("channelId and channelSecret are required.", 400);
        }

        // Base Uri
        $baseUri = ($isSandbox) ? self::SANDBOX_API_HOST : self::API_HOST;

        // Headers
        $headers = [
            'Content-Type' => 'application/json',
            'X-LINE-ChannelId' => $channelId,
        ];

        $this->channelSecret = (string) $channelSecret;

        // Load GuzzleHttp\Client
        $this->httpClient = new HttpClient([
            'base_uri' => $baseUri,
            'headers' => $headers,
            'http_errors' => false,
        ]);

        return $this;
    }

    /**
     * Get LINE Pay signature for authentication
     *
     * @param string $channelSecret
     * @param string $uri
     * @param string $queryOrBody
     * @param string $nonce
     * @return string
     */
    public static function getAuthSignature($channelSecret, $uri, $queryOrBody, $nonce)
    {
        $authMacText = $channelSecret . $uri . $queryOrBody . $nonce;
        return base64_encode(hash_hmac('sha256', $authMacText, $channelSecret, true));
    }

        /**
     * Request payment
     *
     * @param array $bodyParams
     * @return NICO0420\linePay\Response
     */
    public function request($bodyParams)
    {
        return $this->requestHandler('POST', self::$apiUris['request'], null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Client request handler with version
     *
     * @param string $method
     * @param string $uri
     * @param array $queryParams
     * @param array $bodyParams
     * @param array $options
     * @return NICO0420\linePay\Response
     */
    protected function requestHandler($method, $uri, $queryParams = null, $bodyParams = null, $options = [])
    {
        // Headers
        $headers = [];
        // Query String
        $queryString = ($queryParams) ? http_build_query($queryParams) : null;
        $url = ($queryParams) ? "{$uri}?{$queryString}" : $uri ;
        // Body
        $body = ($bodyParams) ? json_encode($bodyParams) : '';

        // Guzzle on_stats
        $stats = null;
        $options['on_stats'] = function (\GuzzleHttp\TransferStats $transferStats) use (&$stats) {
            // Assign object
            $stats = $transferStats;

        };

        $authNonce = date('c') . uniqid('-'); // ISO 8601 date + UUID 1
        $authParams = ($method=='GET' && $queryParams) ? $queryString : (($bodyParams) ? $body : null);
        $headers['X-LINE-Authorization'] = self::getAuthSignature($this->channelSecret, $uri, $authParams, $authNonce);
        $headers['X-LINE-Authorization-Nonce'] = $authNonce;
        

        // Send request with PSR-7 pattern
        $this->request = new Request($method, $url, $headers, $body);

        try {
            $response = $this->httpClient->send($this->request, $options);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return new Response($response, $stats);
    }

    /**
     * Void Authorization
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return NICO0420\linePay\Response
     */
    public function authorizationsVoid($transactionId, $bodyParams = null)
    {
        return $this->requestHandler('POST', str_replace('{transactionId}', $transactionId, self::$apiUris['authorizationsVoid']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * 本API查詢LINE Pay中的交易記錄。您可以查詢授權和購買完成狀態的交易。
     *
     * @param array $queryParams
     * @param string $version API version
     * @return NICO0420\linePay\Response
     */
    public function details($queryParams)
    {
        return $this->requestHandler('GET', self::$apiUris['details'], $queryParams, null, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * Alias of Void Authorization
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return NICO0420\linePay\Response
     */
    public function void($transactionId, $bodyParams = null)
    {
        return $this->authorizationsVoid($transactionId, $bodyParams);
    }

    /**
     * 在用戶確認付款後，商家可透過confirmUrl或Check Payment Status API，來完成交易。
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return NICO0420\linePay\Response
     */
    public function confirm($transactionId, $bodyParams)
    {
        return $this->requestHandler('POST', str_replace('{transactionId}', $transactionId, self::$apiUris['confirm']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 40,
        ]);
    }

    /**
     * 本 API 用以取消已付款(購買完成)的交易，並可支援部分退款。呼叫時需要帶入該筆付款的 LINE Pay 原始交易序號(transactionId)
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return NICO0420\linePay\Response
     */
    public function refund($transactionId, $bodyParams = null)
    {
        return $this->requestHandler('POST', str_replace('{transactionId}', $transactionId, self::$apiUris['refund']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * 本API查詢LINE Pay付款請求的狀態。商家應隔一段時間後直接檢查付款狀態，不透過confirmUrl查看用戶是否已經確認付款，最終判斷交易是否完成。
     *
     * @param integer $transactionId
     * @return NICO0420\linePay\Response
     */
    public function check($transactionId)
    {
        return $this->requestHandler('GET', str_replace('{transactionId}', $transactionId, self::$apiUris['check']), null, null, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * 呼叫Request API發出付款請求時，把"options.payment.capture"設置為false的話，當Confirm API完成付款後，該交易轉換為“待請款狀態”。在此情況下，需呼叫Capture API進行後續請款處理，才能完成所有付款流程。
     *
     * @param integer $transactionId
     * @param array $bodyParams
     * @return NICO0420\linePay\Response
     */
    public function authorizationsCapture($transactionId, $bodyParams)
    {
        return $this->requestHandler('POST', str_replace('{transactionId}', $transactionId, self::$apiUris['authorizationsCapture']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 60,
            ]);
    }

    /**
     * 使用本API之前，您需要先使用Request API和Confirm API，設定自動付款。通過Confirm API回應的RegKey，不經使用者確認，可以直接進行付款。
     *
     * @param integer $regKey
     * @param array $bodyParams
     * @return NICO0420\linePay\Response
     */
    public function preapproved($regKey, $bodyParams = null)
    {
        return $this->requestHandler('POST', str_replace('{regKey}', $regKey, self::$apiUris['preapproved']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 40,
            ]);
    }

    /**
     * 本API查詢已建立的RegKey狀態。
     *
     * @param integer $regKey
     * @param array $queryParams
     * @return NICO0420\linePay\Response
     */
    public function preapprovedCheck($regKey, $queryParams = null)
    {
        return $this->requestHandler('GET', str_replace('{regKey}', $regKey, self::$apiUris['preapprovedCheck']), $queryParams, null, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }

    /**
     * 本API對已建立的RegKey進行過期處理。
     *
     * @param integer $regKey
     * @param array $bodyParams
     * @return NICO0420\linePay\Response
     */
    public function preapprovedExpire($regKey, $bodyParams = null)
    {
        return $this->requestHandler('POST', str_replace('{regKey}', $regKey, self::$apiUris['preapprovedExpire']), null, $bodyParams, [
            'connect_timeout' => 5,
            'timeout' => 20,
            ]);
    }
}
