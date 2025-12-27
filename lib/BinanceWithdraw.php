<?php
class BinanceWithdraw {
    private $apiKey;
    private $secretKey;
    private $baseUrl = 'https://api.binance.com';

    public function __construct($apiKey, $secretKey) {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

    private function signature($queryString) {
        return hash_hmac('sha256', $queryString, $this->secretKey);
    }

    public function withdraw($coin, $network, $address, $amount, $meta_order_id) {
        $endpoint = '/sapi/v1/capital/withdraw/apply';
        
        // Map Network Names to Binance Standards
        // User inputs: BEP20, TRC20, ERC20
        // Binance Standards: BSC, TRX, ETH
        $netMap = [
            'BEP20' => 'BSC',
            'TRC20' => 'TRX',
            'ERC20' => 'ETH',
            'BTC'   => 'BTC'
        ];
        
        $binanceNetwork = $netMap[strtoupper($network)] ?? $network;

        $params = [
            'coin' => strtoupper($coin),
            'network' => $binanceNetwork,
            'address' => $address,
            'amount' => $amount,
            'withdrawOrderId' => $meta_order_id, // Custom ID for tracking
            'timestamp' => round(microtime(true) * 1000)
        ];

        // Build Query String
        $queryString = http_build_query($params);
        $signature = $this->signature($queryString);
        $url = $this->baseUrl . $endpoint . '?' . $queryString . '&signature=' . $signature;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-MBX-APIKEY: ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            return ['success' => false, 'error' => curl_error($ch)];
        }
        
        curl_close($ch);
        $json = json_decode($response, true);

        // Binance Success Response: {"id": "string"}
        if (isset($json['id'])) {
            return ['success' => true, 'id' => $json['id'], 'raw' => $json];
        } else {
            return ['success' => false, 'error' => $json['msg'] ?? 'Unknown Binance Error', 'raw' => $json];
        }
    }
}
