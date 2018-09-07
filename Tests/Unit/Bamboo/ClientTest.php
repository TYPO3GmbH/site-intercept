<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests\Unit\Bamboo;

use GuzzleHttp\Client as GuzzleClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use T3G\Intercept\Bamboo\Client;
use T3G\Intercept\Github\DocumentationRenderingRequest;

class ClientTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function triggerNewCoreBuildThrowsExceptionIfBranchToProjectMappingLookupFails()
    {
        $client = new Client();
        $client->setBranchToProjectKey(['klaus' => 'fritz']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1472210110);
        $client->triggerNewCoreBuild('foo', 3, 'master');
    }

    /**
     * @test
     * @dataProvider getPossibleDocumentationPlanTrigger
     */
    public function documentationPlanTriggerCallsExpectedUris(
        string $versionNumber,
        string $repositoryUrl,
        string $expectedUri
    ) {
        $guzzleClientMock = $this->getMockBuilder(GuzzleClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['post'])
            ->getMock();
        $responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();

        $documentationRenderingRequestMock = $this->getMockBuilder(DocumentationRenderingRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $documentationRenderingRequestMock->expects($this->any())
            ->method('getVersionNumber')
            ->willReturn($versionNumber);
        $documentationRenderingRequestMock->expects($this->any())
            ->method('getRepositoryUrl')
            ->willReturn($repositoryUrl);

        $guzzleClientMock->expects($this->once())
            ->method('post')
            ->with($expectedUri)
            ->willReturn($responseMock);

        $subjet = new Client(null, $guzzleClientMock);
        $subjet->triggerDocumentationPlan($documentationRenderingRequestMock);
    }

    public function getPossibleDocumentationPlanTrigger(): array
    {
        return [
            'Draft Branch' => [
                'versionNumber' => 'draft',
                'repositoryUrl' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-TCA.git',
                'expectedUri' => 'latest/queue/CORE-DR?stage=&executeAllStages=&os_authType=basic&bamboo.variable.VERSION_NUMBER=draft&bamboo.variable.REPOSITORY_URL=https%3A%2F%2Fgithub.com%2FTYPO3-Documentation%2FTYPO3CMS-Reference-TCA.git',
            ],
            'Version' => [
                'versionNumber' => '9.5',
                'repositoryUrl' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-TCA.git',
                'expectedUri' => 'latest/queue/CORE-DR?stage=&executeAllStages=&os_authType=basic&bamboo.variable.VERSION_NUMBER=9.5&bamboo.variable.REPOSITORY_URL=https%3A%2F%2Fgithub.com%2FTYPO3-Documentation%2FTYPO3CMS-Reference-TCA.git',
            ],
        ];
    }
}
