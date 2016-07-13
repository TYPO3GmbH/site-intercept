<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests\Unit;

use Monolog\Logger;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use T3G\Intercept\GithubToGerritController;
use T3G\Intercept\InterceptController;
use T3G\Intercept\RequestDispatcher;

class RequestDispatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $githubToGerritController;
    protected $logger;

    /**
     * @var RequestDispatcher
     */
    protected $requestDispatcher;

    /**
     * @var InterceptController|ObjectProphecy
     */
    protected $interceptController;


    public function setUp()
    {
        $this->interceptController = $this->prophesize(InterceptController::class);
        $this->githubToGerritController = $this->prophesize(GithubToGerritController::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->requestDispatcher = new RequestDispatcher($this->interceptController->reveal(), $this->githubToGerritController->reveal(), $this->logger->reveal());
    }

    /**
     * @test
     * @return void
     */
    public function dispatchDispatchesToPostBuildIfPayloadGiven()
    {
        $_POST['payload'] = 'foo';

        $this->requestDispatcher->dispatch();

        $this->interceptController->postBuildAction()->shouldHaveBeenCalled();
    }

    /**
     * @test
     * @return void
     */
    public function dispatchDispatchesToNewBuildActionIfChangeUrlAndPatchSetGiven()
    {
        $_POST = [
            'patchset' => '3',
            'changeUrl' => 'https://review.typo3.org/48574/',
            'branch' => 'master'
        ];

        $this->requestDispatcher->dispatch();

        $this->interceptController->newBuildAction()->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function dispatchLogsRequestsItCouldNotDispatch()
    {
        $_REQUEST['something'] = 'else';
        $this->requestDispatcher->dispatch();
        $this->logger->warning(Argument::containingString('something'))->shouldHaveBeenCalled();
    }

    /**
     * @test
     * @return void
     */
    public function dispatchDoesNotLogRequestsItCouldDispatch()
    {
        $_POST = [
            'patchset' => '3',
            'changeUrl' => 'https://review.typo3.org/48574/',
            'branch' => 'master'
        ];
        $this->requestDispatcher->dispatch();
        $this->logger->warning(Argument::any())->shouldNotHaveBeenCalled();
        $this->logger->error(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @return void
     */
    public function dispatchLogsExceptions()
    {
        $this->interceptController->newBuildAction()->willThrow(\InvalidArgumentException::class);

        $_POST = [
            'patchset' => '3',
            'changeUrl' => 'https://review.typo3.org/48574/',
            'branch' => 'master'
        ];

        $this->requestDispatcher->dispatch();
        $this->logger->error(Argument::containingString('ERROR'))->shouldHaveBeenCalled();
    }

    /**
     * @test
     * @return void
     */
    public function dispatchDispatchesToGithubController()
    {
        $_GET = ['github' => 1];

        $this->requestDispatcher->dispatch();

        $this->githubToGerritController->transformPullRequestToGerritReview(Argument::any())->shouldHaveBeenCalled();
    }
}
