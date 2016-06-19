<?php
declare(strict_types = 1);

namespace T3G\Intercept\Library;

class CurlGerritPostRequest
{
    protected $baseUrl = "https://review.typo3.org/a/";

    public function postRequest($apiPath, $postFields)
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $this->baseUrl . $apiPath,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($postFields),
                CURLOPT_HTTPHEADER => [
                    "authorization: Basic dHlwbzNjb21fYmFtYm9vOjBMZnhjbFVackVRSWhDM2JmZ0lSZTJNUVBnc1I1cEljcWIvZ2dZUy9Kdw==",
                    "cache-control: no-cache",
                    "content-type: application/json"
                ],
            ]
        );
        curl_exec($curl);
        //$err = curl_error($curl);
        curl_close($curl);
    }
}