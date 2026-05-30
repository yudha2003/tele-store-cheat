<?php
namespace App\Libraries;

use App\Models\Config;

class Wijayapay
{
    public string $code_merchant, $api_key;
    public function __construct()
    {
        $config = Config::first();
        if ($config->payments) {
            $this->code_merchant = $config->payments['wijayapay']['code_merchant'] ?? null;
            $this->api_key       = $config->payments['wijayapay']['api_key'] ?? null;
        }
    }
    public function request($idOrder, $jumlah, $method)
    {
        $data = [
            'code_merchant' => $this->code_merchant,
            'api_key'       => $this->api_key,
            'ref_id'        => $idOrder,
            'code_payment'  => $method,
            'nominal'       => $jumlah,
        ];

        $signature = md5($data['code_merchant'] . $data['api_key'] . $data['ref_id']);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_URL            => 'https://wijayapay.com/api/transaction/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Signature: ' . $signature,
            ],
            CURLOPT_FAILONERROR    => false,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        ]);

        $response = json_decode(curl_exec($curl), true);
        $error    = curl_error($curl);

        if ($error) {
            return ['success' => false, 'msg' => 'Curl error'];
        }
        if (! isset($response['success']) || ! $response['success']) {
            $err = strtolower($response['message'] ?? 'Unknown error');

            return ['success' => false, 'msg' => $err];
        }
        return $response;
    }
}
