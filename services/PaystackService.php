<?php
/**
 * PaystackService.php
 * Service for Paystack payment integration
 */
namespace Services;

class PaystackService {
    private $secretKey;
    private $baseUrl = 'https://api.paystack.co';
    
    public function __construct() {
        $this->secretKey = PAYSTACK_SECRET_KEY;
    }
    
    /**
     * Initiate a payment
     * 
     * @param array $data
     * @return array
     */
    public function initiatePayment($data) {
        // Build payload
        $payload = [
            'email' => $data['email'],
            'amount' => $data['amount'],
            'callback_url' => $data['callback_url'] ?? null,
            'metadata' => $data['metadata'] ?? null
        ];
        
        // Add optional fields
        if (isset($data['reference'])) $payload['reference'] = $data['reference'];
        if (isset($data['currency'])) $payload['currency'] = $data['currency'];
        
        // If phone is set, add mobile money configuration
        if (isset($data['phone'])) {
            $provider = $this->determineProvider($data['phone']);
            
            if ($provider) {
                $payload['mobile_money'] = [
                    'phone' => $data['phone'],
                    'provider' => $provider
                ];
            }
        }
        
        // Make API request
        $response = $this->makeRequest('POST', '/transaction/initialize', $payload);
        
        if (!$response['status']) {
            throw new \Exception($response['message']);
        }
        
        return $response['data'];
    }
    
    /**
     * Verify a transaction
     * 
     * @param string $reference
     * @return array
     */
    public function verifyTransaction($reference) {
        $response = $this->makeRequest('GET', "/transaction/verify/{$reference}");
        
        if (!$response['status']) {
            throw new \Exception($response['message']);
        }
        
        return $response['data'];
    }

    /**
     * Determine mobile money provider by phone number
     * 
     * @param string $phoneNumber
     * @return string|null
     */
    private function determineProvider($phoneNumber) {
        // Map of prefixes to providers
        $providerMappings = [
            '23350' => 'mtn',
            '23354' => 'mtn',
            '23355' => 'mtn',
            '23356' => 'mtn',
            '23357' => 'mtn',
            '23359' => 'mtn',
            '23320' => 'vod',
            '23324' => 'mtn',
            '23327' => 'vod',
            '23326' => 'tgo',
            '23323' => 'tgo',
            '23328' => 'tgo',
        ];
        
        // Clean phone number
        $cleanedNumber = $phoneNumber;
        
        // Check prefixes
        foreach ($providerMappings as $prefix => $provider) {
            if (strpos($cleanedNumber, $prefix) === 0) {
                return $provider;
            }
        }
        
        // Default to MTN if no match
        return 'mtn';
    }
    
    /**
     * Make HTTP request to Paystack API
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $curl = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        
        if ($data && in_array($method, ['POST', 'PUT'])) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }
        
        return json_decode($response, true);
    }
}