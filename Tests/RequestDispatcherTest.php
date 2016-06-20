<?php
declare(strict_types = 1);
namespace T3G\Tests;

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
        $_GET = [
            'patchset' => '3',
            'changeUrl' => 'https://review.typo3.org/#/c/48574/'
        ];

        $this->requestDispatcher->dispatch();

        $this->interceptController->newBuildAction()->shouldHaveBeenCalled();
    }
}
