<?php

const BASEPATH = __DIR__;
$GLOBALS['gitOutput'] = '';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();