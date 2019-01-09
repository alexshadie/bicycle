<?php

require __DIR__ . "/../vendor/autoload.php";

class Bootstrap extends \alexshadie\bicycle\Bootstrap
{
    public function __construct($pathToRoot)
    {
        parent::__construct($pathToRoot);
        $this->setConfigDir('config-example');
    }
}

class App extends \alexshadie\bicycle\App
{
    protected $bootstrapClass = Bootstrap::class;
    public function beforeInit(): void
    {
        parent::beforeInit();
        ini_set("log_errors", 1);
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');
        date_default_timezone_set("UTC");
    }
}

class DefaultController extends \alexshadie\bicycle\controllers\Controller
{
    public function index()
    {
        return new \alexshadie\bicycle\action\TextResult("Index page");
    }
}

try {
    (new App())->run();
} catch (Exception | Throwable $e) {
    throw $e;
}
