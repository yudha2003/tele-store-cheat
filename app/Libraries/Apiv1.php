<?php

namespace App\Libraries;

use App\Models\ConfigProvider;
use Exception;
use Illuminate\Support\Str;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;

use DOMDocument;

class Apiv1
{
    public function order($data)
    {
        $datas = [
            'nama' => $data['name'],
            'api_key' => $data['api_key'],
            'duration' => $data['durasi'],
            'code_game' => $data['code_game'],
            'max_devices' => 1,
            'action' => 'register'
        ];
        $datas2 = ['game' => $data['code_game'], 'durasi' => $data['durasi']];
        $datas = array_merge($datas, $datas2);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $data['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($datas),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $response = curl_exec($curl);
        return json_decode($response, true);
    }
    public function getGame($data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $data['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $response = curl_exec($curl);
        return json_decode($response, true);
    }
    public function reset($data)
    {
        $datas = [
            'api_key' => $data['api_key'],
            'license' => $data['license'],
            'action' => 'reset_license'
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $data['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($datas),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $response = curl_exec($curl);
        return json_decode($response, true);
    }
}
