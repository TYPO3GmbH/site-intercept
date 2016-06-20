<?php
declare(strict_types = 1);

namespace T3G\Intercept\Library;

class CurlBambooRequests
{

    protected $baseUrl = 'https://bamboo.typo3.com/rest/api/';

    /**
     * @codeCoverageIgnore postman generated curl request
     * @param string $buildKey
     * @return string
     */
    public function getBuildStatus(string $buildKey) : string
    {
        $apiPath = '/latest/result/' . $buildKey;
        $apiPathParams = '?os_authType=basic&expand=labels';

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $this->baseUrl . $apiPath . $apiPathParams,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'accept: application/json',
                    'authorization: Basic d2I6dHk5MDMhISEx',
                    'cache-control: no-cache',
                    'content-type: application/json',
                    'x-atlassian-token: nocheck'
                ],
            ]
        );

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    /**
     * Triggers new build in project CORE-GTC
     *
     * @codeCoverageIgnore postman generated curl request
     * @param string $changeUrl
     * @param int $patchset
     */
    public function triggerNewCoreBuild(string $changeUrl, int $patchset)
    {
        $apiPath = 'latest/queue/CORE-GTC';
        $apiPathParams = '?stage=&os_authType=basic&executeAllStages=&bamboo.variable.changeUrl=' .
                         $changeUrl . '&bamboo.variable.patchset=' . $patchset;

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $this->baseUrl . $apiPath . $apiPathParams,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => [
                    "authorization: Basic d2I6dHk5MDMhISEx",
                    "cache-control: no-cache",
                    "x-atlassian-token: nocheck"
                ],
            ]
        );

        curl_exec($curl);
        curl_close($curl);
    }
}