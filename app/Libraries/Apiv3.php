<?php

namespace App\Libraries;

use Exception;

class Apiv3
{
    public function generate($data)
    {
        try {
            $url = $data['url']['register'] ?? '';
            $apiKey = $data['api_key'] ?? '';
            $postData = [
                'game'        => $data['game'] ?? '',
                'duration'    => (int) ($data['duration'] ?? 1),
                'max_devices' => (int) ($data['max_devices'] ?? 1),
                'bulk_key'    => (int) ($data['bulk_key'] ?? 1),
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($postData),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $apiKey,
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            return json_decode($response, true);
        } catch (Exception $e) {
            return false;
        }
    }

    public function reset($data)
    {
        try {
            $url = $data['url']['reset'] ?? '';
            $apiKey = $data['api_key'] ?? '';
            $postData = [
                'user_key' => $data['user_key'] ?? '',
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($postData),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $apiKey,
                ],
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            return json_decode($response, true);
        } catch (Exception $e) {
            return false;
        }
    }
}
