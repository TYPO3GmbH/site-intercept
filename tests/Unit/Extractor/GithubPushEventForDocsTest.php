<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GithubPushEventForDocs;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class GithubPushEventForDocsTest extends TestCase
{
    private $payload = [
        'ref' => 'refs/tags/1.2.3',
        'repository' => [
            'clone_url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git',
        ],
    ];

    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $subject = new GithubPushEventForDocs($this->generateRequestStackWithPayload($this->payload));
        $this->assertSame('1.2.3', $subject->versionNumber);
        $this->assertSame('https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git', $subject->repositoryUrl);
    }

    /**
     * @test
     */
    public function constructorExtractsFromBranch()
    {
        $payload = $this->payload;
        $payload['ref'] = 'refs/heads/latest';
        $subject = new GithubPushEventForDocs($this->generateRequestStackWithPayload($payload));
        $this->assertSame('latest', $subject->versionNumber);
        $this->assertSame('https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git', $subject->repositoryUrl);
    }

    /**
     * @test
     */
    public function constructorThrowsWithInvalidVersion()
    {
        $this->expectException(DoNotCareException::class);
        $payload = $this->payload;
        $payload['ref'] = 'refs/foo/latest';
        new GithubPushEventForDocs($this->generateRequestStackWithPayload($payload));
    }

    /**
     * @test
     */
    public function constructorThrowsWithEmptyRepository()
    {
        $this->expectException(DoNotCareException::class);
        $payload = $this->payload;
        $payload['repository']['clone_url'] = '';
        new GithubPushEventForDocs($this->generateRequestStackWithPayload($payload));
    }

    /**
     * @param array $payload
     * @return RequestStack
     */
    private function generateRequestStackWithPayload(array $payload): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [], [], [], [], json_encode($payload)));

        return $requestStack;
    }
}
