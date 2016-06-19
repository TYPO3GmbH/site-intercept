<?php
declare(strict_types = 1);

namespace T3G\Intercept\Library;

class CurlBambooGetRequest
{

    public function getBuildStatus($buildKey)
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => "https://bamboo.typo3.com/rest/api/latest/result/$buildKey?os_authType=basic&expand=labels",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "authorization: Basic d2I6dHk5MDMhISEx",
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "x-atlassian-token: nocheck"
                ],
            ]
        );

        $response = curl_exec($curl);
        //$err = curl_error($curl);

        curl_close($curl);

        return $response;
    }
}