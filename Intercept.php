<?php

require_once __DIR__ . '/vendor/autoload.php';

$requestDispatcher = new \T3G\Intercept\RequestDispatcher();
$requestDispatcher->dispatch();