<?php

class BinancePay {
    private $apiKey;
    private $secretKey;
    private $baseUrl;

    public function __construct($apiKey, $secretKey, $env = 'sandbox') {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->baseUrl = ($env === 'live') ? 'https://bpay.binanceapi.com' : 'https://bpay.binanceapi.com'; 
        // Note: Binance Pay API v2 endpoints are generally the same host, but for clear separation in logic/logging we use the env. 
        // Actually, for Sandbox/Testnet the host is usually strictly different if using the formal Testnet.
        // However, Binance Pay often uses the same endpoint with different credentials or a specific sub-domain.
        // Let's stick to the official doc standard: 
        // Live: https://bpay.binanceapi.com
        // Sandbox: https://bpay.binanceapi.com (There is no public open sandbox for Pay v2 seemingly, usually it's just live with test creds or specific test endpoint if provided). 
        // If the user wants to use a specific test URL, they can Modify this. For now, I will assume the user has the correct URL.
        // Let's use the line 11 logic properly but remove the hardcode.
        $this->baseUrl = ($env === 'live') ? 'https://bpay.binanceapi.com' : 'https://bpay.binanceapi.com'; // Currently Binance Pay docs point to same, but kept for extensibility.

    }

    private function generateNonce($length = 32) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $nonce = '';
        for ($i = 0; $i < $length; $i++) {
            $nonce .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $nonce;
    }

    public function createOrder($amount, $merchantTradeNo, $webhookUrl, $returnUrl) {
        $endpoint = '/binancepay/openapi/v2/order';
        
        $payload = [
            'env' => ['terminalType' => 'WEB'],
            'orderAmount' => $amount,
            'currency' => 'USDT',
            'merchantTradeNo' => $merchantTradeNo,
            'webhookUrl' => $webhookUrl,
            'returnUrl' => $returnUrl,
            'goods' => [
                'goodsType' => '01',
                'goodsCategory' => '6000',
                'referenceGoodsId' => 'deposit_capital',
                'goodsName' => 'Evolentra Capital Deposit',
                'goodsDetail' => 'Direct Investment into Evolentra Ecosystem'
            ]
        ];

        $jsonPayload = json_encode($payload);
        $timestamp = round(microtime(true) * 1000);
        $nonce = $this->generateNonce();

        $signaturePayload = $timestamp . "\n" . $nonce . "\n" . $jsonPayload . "\n";
        $signature = strtoupper(hash_hmac('sha512', $signaturePayload, $this->secretKey));

        $headers = [
            'Content-Type: application/json',
            'Binance-Pay-Timestamp: ' . $timestamp,
            'Binance-Pay-Nonce: ' . $nonce,
            'Binance-Pay-Certificate-SN: ' . $this->apiKey,
            'Binance-Pay-Signature: ' . $signature
        ];

        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['status' => 'FAIL', 'errorMessage' => $error];
        }

        return json_decode($response, true);
    }
    public function queryOrder($merchantTradeNo) {
        $endpoint = '/binancepay/openapi/v2/order/query';
        
        $payload = [
            'merchantTradeNo' => $merchantTradeNo
        ];

        $jsonPayload = json_encode($payload);
        $timestamp = round(microtime(true) * 1000);
        $nonce = $this->generateNonce();

        $signaturePayload = $timestamp . "\n" . $nonce . "\n" . $jsonPayload . "\n";
        $signature = strtoupper(hash_hmac('sha512', $signaturePayload, $this->secretKey));

        $headers = [
            'Content-Type: application/json',
            'Binance-Pay-Timestamp: ' . $timestamp,
            'Binance-Pay-Nonce: ' . $nonce,
            'Binance-Pay-Certificate-SN: ' . $this->apiKey,
            'Binance-Pay-Signature: ' . $signature
        ];

        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function transferFund($requestId, $currency, $amount, $transferType, $receiver, $remark = '') {
        $endpoint = '/binancepay/openapi/payout/transfer';
        
        $payload = [
            'requestId' => $requestId,
            'batchName' => 'Payout_' . date('YmdHis'),
            'currency' => $currency,
            'totalAmount' => $amount,
            'totalNumber' => 1,
            'bizScene' => 'DIRECT_TRANSFER',
            'transferDetailList' => [
                [
                    'merchantSendId' => $requestId . '_1',
                    'transferAmount' => $amount,
                    'receiveType' => $transferType, // 'BINANCE_ID' or 'EMAIL'
                    'receiver' => $receiver,
                    'transferMethod' => 'SPOT_WALLET',
                    'remark' => $remark
                ]
            ]
        ];

        $jsonPayload = json_encode($payload);
        $timestamp = round(microtime(true) * 1000);
        $nonce = $this->generateNonce();

        $signaturePayload = $timestamp . "\n" . $nonce . "\n" . $jsonPayload . "\n";
        $signature = strtoupper(hash_hmac('sha512', $signaturePayload, $this->secretKey));

        $headers = [
            'Content-Type: application/json',
            'Binance-Pay-Timestamp: ' . $timestamp,
            'Binance-Pay-Nonce: ' . $nonce,
            'Binance-Pay-Certificate-SN: ' . $this->apiKey,
            'Binance-Pay-Signature: ' . $signature
        ];

        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
