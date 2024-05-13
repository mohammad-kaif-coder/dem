<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyCalls extends Model {

    public function graph($options) {
        $shop = (isset($options['shop']) ? $options['shop'] : 'staging-justhype.myshopify.com');
        $url = 'https://' . $shop . '/admin/api/2020-01/graphql.json';

        $req = [];
        if (isset($options['query'])) {
            $req = ["query" => $options['query']];
        }
        $req = json_encode($req);

        $request_headers = array(
            "Content-type: application/json; charset=utf-8",
            'Expect:',
            'X-Shopify-Access-Token: ' . $options['access_token'],
            'Accept: ' . 'application/json'
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
//        print_R($result);
        curl_close($ch);

        $result = preg_replace("/[\r\n]+/", " ", $result);
        $result = utf8_encode($result);

        return json_decode($result);
    }

    public function graph_limit($type) {
        $limit = [
            'orders' => 2,
            'products' => 2,
            'customers' => 2,
            'basic_data' => 250,
        ];

        return (isset($limit[$type])) ? $limit[$type] : 20;
    }

    public function curlCall($url, $request_headers, $method, $payload = '') {
        $ch = curl_init($url);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } elseif ($method == "DELETE") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->getCurlHeaders($response);
    }

    public function getCurlHeaders($response) {
        $headers = [];
        $parts = explode("\r\n\r\nHTTP/", $response);
        $parts = (count($parts) > 1 ? 'HTTP/' : '') . array_pop($parts);
        list($header_text, $body) = explode("\r\n\r\n", $parts, 2);

        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0)
                $headers['http_code'] = $line;
            else {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }
        }
        return ["headers" => $headers, "body" => $body];
    }

}
