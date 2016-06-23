<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use Monolog\Logger;

class RequestDispatcher
{
    use Traits\Logger;

    /**
     * @var \T3G\Intercept\InterceptController
     */
    private $interceptController;

    public function __construct(InterceptController $interceptController = null, Logger $logger = null)
    {
        $this->interceptController = $interceptController ?: new InterceptController();
        $this->setLogger($logger);
    }

    public function dispatch()
    {
        try {
            if (!empty($_POST['payload'])) {
                $this->interceptController->postBuildAction();
            }
            if (!empty($_POST['changeUrl']) && !empty($_POST['patchset']) && !empty($_POST['branch'])) {
                $this->interceptController->newBuildAction();
            }
        } catch (\Exception $e) {
            $this->logger->error('ERROR:"' . $e->getMessage() . '"" in ' . $e->getFile() . ' line ' . $e->getLine());
        }
        $this->logger->warning('Could not dispatch request. Request Data:' . "\n" . var_export($_REQUEST, true));
    }
}