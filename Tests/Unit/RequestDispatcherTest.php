<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests\Unit;

use Monolog\Logger;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use T3G\Intercept\InterceptController;
use T3G\Intercept\RequestDispatcher;

class RequestDispatcherTest extends \PHPUnit_Framework_TestCase
{

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
        $this->requestDispatcher = new RequestDispatcher($this->interceptController->reveal());
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
        $logger = $this->prophesize(Logger::class);
        $requestDispatcher = new RequestDispatcher($this->interceptController->reveal(), $logger->reveal());
        $_REQUEST['something'] = 'else';
        $requestDispatcher->dispatch();
        $logger->warning(Argument::containingString('something'))->shouldHaveBeenCalled();
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
        $logger = $this->prophesize(Logger::class);
        $requestDispatcher = new RequestDispatcher($this->interceptController->reveal(), $logger->reveal());
        $requestDispatcher->dispatch();
        $logger->warning(Argument::any())->shouldNotHaveBeenCalled();
        $logger->error(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @return void
     */
    public function dispatchLogsExceptions()
    {
        $this->interceptController->newBuildAction()->willThrow(\InvalidArgumentException::class);

        $logger = $this->prophesize(Logger::class);
        $requestDispatcher = new RequestDispatcher($this->interceptController->reveal(), $logger->reveal());

        $_POST = [
            'patchset' => '3',
            'changeUrl' => 'https://review.typo3.org/48574/',
            'branch' => 'master'
        ];

        $requestDispatcher->dispatch();
        $logger->error(Argument::containingString('ERROR'))->shouldHaveBeenCalled();
    }
}
