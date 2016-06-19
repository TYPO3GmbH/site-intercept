<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use T3G\Intercept\Library\CurlGerritPostRequest;

class GerritInformer
{
    /**
     * @var \T3G\Intercept\Library\CurlGerritPostRequest
     */
    private $requester;

    public function __construct(CurlGerritPostRequest $requester = null)
    {
        $this->requester = $requester ?: new CurlGerritPostRequest();
    }

    /**
     * @param array $buildInformation
     * @return void
     */
    public function voteOnGerrit(array $buildInformation)
    {
        $apiPath = $this->constructApiPath($buildInformation);

        $verification = $buildInformation['success'] ? '+1' : '-1';

        $postFields = [
            'message' => "Build completed.",
            'labels' => [
                'Verified' => $verification
            ]
        ];
        $this->requester->postRequest($apiPath, $postFields);
    }

    /**
     * @param array $buildInformation
     * @return string
     */
    protected function constructApiPath(array $buildInformation) : string
    {
        return 'changes/' . $buildInformation['change'] . '/revisions/' . $buildInformation['patchset'] . '/review';
    }
}