<?php
declare(strict_types = 1);
namespace T3G\Intercept;

use T3G\Intercept\Library\CurlBambooGetRequest;

class BambooStatusInformation
{

    /**
     * @var \T3G\Intercept\Library\CurlBambooGetRequest
     */
    private $requester;

    public function __construct(CurlBambooGetRequest $requester = null)
    {
        $this->requester = $requester ?: new CurlBambooGetRequest();
    }

    public function transform(string $buildKey) : array
    {
        $jsonResponse = $this->requester->getBuildStatus($buildKey);
        $result = [];
        $response = json_decode($jsonResponse, true);
        $result = $this->getInformationFromLabels($response, $result);
        $result['success'] = $response['successful'];
        return $result;
    }

    /**
     * @param string $name
     * @return int
     */
    protected function extractValue(string $name) : int
    {
        $splitted = explode('-', $name);
        $value = array_pop($splitted);
        return (int)$value;
    }

    /**
     * @param array $response
     * @param array $result
     * @return array
     */
    protected function getInformationFromLabels(array $response, array $result) : array
    {
        $labels = $response['labels']['label'];
        $resultKeys = ['change', 'patchset'];
        foreach ($labels as $label) {
            $name = $label['name'];
            foreach ($resultKeys as $key) {
                if (strpos($name, $key) === 0) {
                    $result[$key] = $this->extractValue($name);
                }

            }
        }
        return $result;
    }
}
