<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Unit\Gerrit;

use T3G\Intercept\Gerrit\CommitMessageCreator;

class CommitMessageCreatorTest extends \PHPUnit_Framework_TestCase
{
    protected $subject = 'Subject: This is a test';
    protected $body = 'This is the body of my message';
    protected $issueNumber = 12345;
    /**
     * @var CommitMessageCreator
     */
    protected $commitMessageCreator;

    public function setUp()
    {
        $this->commitMessageCreator = new CommitMessageCreator();
    }

    public function commitMessageContainsNecessaryPartsDataProvider()
    {
        return [
            'subject' => [$this->subject],
            'TASK prefix' => ['[TASK] Subject'],
            'body' => [$this->body],
            'issueNumber' => ['Resolves: #' . $this->issueNumber],
            'releases' => ['Releases: master']
        ];
    }

    /**
     * @test
     * @dataProvider commitMessageContainsNecessaryPartsDataProvider
     * @param string $expected
     */
    public function commitMessageContainsNecessaryParts(string $expected)
    {
        $message = $this->commitMessageCreator->create($this->subject, $this->body, $this->issueNumber);
        self::assertContains($expected, $message);

    }
}
