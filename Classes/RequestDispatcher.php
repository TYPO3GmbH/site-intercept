<?php
declare(strict_types = 1);

namespace T3G\Intercept;

class RequestDispatcher
{

    /**
     * @var \T3G\Intercept\InterceptController
     */
    private $interceptController;

    public function __construct(InterceptController $interceptController = null)
    {
        $this->interceptController = $interceptController ?: new InterceptController();
    }

    public function dispatch()
    {
        if (!empty($_POST['payload'])) {
            $this->interceptController->postBuildAction();
        }
        if (!empty($_POST['changeUrl']) && !empty($_POST['patchset'])) {
            $this->interceptController->newBuildAction();
        }
    }
}