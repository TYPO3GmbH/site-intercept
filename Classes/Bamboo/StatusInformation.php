<?php
declare(strict_types = 1);

namespace T3G\Intercept\Bamboo;

/**
 * Class BambooStatusInformation
 *
 * Responsible for:
 * * Extracting and transforming bamboo status information
 *
 * @package T3G\Intercept
 */
class StatusInformation
{

    /**
     * @var Client
     */
    private $requester;

    public function __construct(Client $requester = null)
    {
        $this->requester = $requester ?: new Client();
    }

    public function transform(string $buildKey) : array
    {
        $jsonResponse = $this->requester->getBuildStatus($buildKey)->getBody();
        $result = [];
        $response = json_decode((string)$jsonResponse, true);
        $result = $this->getInformationFromLabels($response, $result);
        $result['buildUrl'] = 'https://bamboo.typo3.com/browse/' . $response['buildResultKey'];
        $result['success'] = $response['successful'];
        $result['buildTestSummary'] = $response['buildTestSummary'];
        $result['prettyBuildCompletedTime'] = $response['prettyBuildCompletedTime'];
        $result['buildDurationInSeconds'] = $response['buildDurationInSeconds'];
        return $result;
    }

    /**
     * @param string $name
     * @return int
     */
    protected function extractValueForNameFromMinusSeparatedString(string $name) : int
    {
        $split = explode('-', $name);
        return (int)array_pop($split);
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
                $key = trim('_', $key);
                // A hack to cope with a bamboo hack which prefixes or suffixes keys with underscore '_'
                if (strpos($name, $key) === 0) {
                    $result[$key] = $this->extractValueForNameFromMinusSeparatedString($name);
                }
            }
        }
        return $result;
    }
}
