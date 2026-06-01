<?php
namespace App\Libraries;

use App\Models\Config;

class Pakasir
{
    public string $project;
    public string $api_key;

    /**
     * Payment methods yang tersedia di Pakasir
     */
    public const METHODS = [
        'qris',
        'bri_va',
        'bni_va',
        'bca_va',
        'cimb_niaga_va',
        'sampoerna_va',
        'bnc_va',
        'maybank_va',
        'permata_va',
        'atm_bersama_va',
        'artha_graha_va',
    ];

    private const BASE_URL = 'https://app.pakasir.com';

    public function __construct()
    {
        $config = Config::first();
        if ($config->payments) {
            $this->project = $config->payments['pakasir']['project'] ?? '';
            $this->api_key = $config->payments['pakasir']['api_key'] ?? '';
        }
    }

    private function request(string $method, string $url, array $body = []): array
    {
        $curl = curl_init();

        $options = [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_FAILONERROR    => false,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        ];

        if (strtoupper($method) === 'POST') {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        curl_setopt_array($curl, $options);

        $response = json_decode(curl_exec($curl), true);
        $error    = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($error) {
            return ['success' => false, 'msg' => 'Curl error: ' . $error];
        }

        if ($httpCode >= 400 || ! is_array($response)) {
            $msg = is_array($response) ? ($response['message'] ?? 'Unknown error') : 'Invalid response';
            return ['success' => false, 'msg' => strtolower($msg)];
        }

        return array_merge(['success' => true], $response);
    }

    private function basePayload(string $orderId, int $amount): array
    {
        return [
            'project'  => $this->project,
            'order_id' => $orderId,
            'amount'   => $amount,
            'api_key'  => $this->api_key,
        ];
    }

    public function paymentUrl(string $orderId, int $amount, ?string $redirect = null, bool $qrisOnly = false): string
    {
        $url = self::BASE_URL . "/pay/{$this->project}/{$amount}?order_id={$orderId}";

        if ($redirect) {
            $url .= '&redirect=' . urlencode($redirect);
        }

        if ($qrisOnly) {
            $url .= '&qris_only=1';
        }

        return $url;
    }
    public function createTransaction(string $orderId, int $amount, string $method): array
    {
        $url  = self::BASE_URL . "/api/transactioncreate/{$method}";
        $body = $this->basePayload($orderId, $amount);

        $result = $this->request('POST', $url, $body);

        if (! $result['success']) {
            return $result;
        }

        if (empty($result['payment'])) {
            return ['success' => false, 'msg' => 'payment data not found in response'];
        }

        return $result;
    }

    public function simulatePayment(string $orderId, int $amount): array
    {
        $url  = self::BASE_URL . '/api/paymentsimulation';
        $body = $this->basePayload($orderId, $amount);

        return $this->request('POST', $url, $body);
    }

    public function cancelTransaction(string $orderId, int $amount): array
    {
        $url  = self::BASE_URL . '/api/transactioncancel';
        $body = $this->basePayload($orderId, $amount);

        return $this->request('POST', $url, $body);
    }

    public function getTransaction(string $orderId, int $amount): array
    {
        $query = http_build_query([
            'project'  => $this->project,
            'amount'   => $amount,
            'order_id' => $orderId,
            'api_key'  => $this->api_key,
        ]);

        $url = self::BASE_URL . '/api/transactiondetail?' . $query;

        $result = $this->request('GET', $url);

        if (! $result['success']) {
            return $result;
        }

        if (empty($result['transaction'])) {
            return ['success' => false, 'msg' => 'transaction data not found in response'];
        }

        return $result;
    }
    public function parseWebhook(?string $rawBody = null): ?array
    {
        $rawBody = $rawBody ?? file_get_contents('php://input');
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return null;
        }

        $required = ['amount', 'order_id', 'project', 'status', 'payment_method'];
        foreach ($required as $key) {
            if (! isset($payload[$key])) {
                return null;
            }
        }

        if ($payload['project'] !== $this->project) {
            return null;
        }

        return $payload;
    }
}
