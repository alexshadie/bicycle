<?php


namespace alexshadie\bicycle;

abstract class App
{
    protected $bootstrapClass = Bootstrap::class;
    protected $root = __DIR__ . "/../";

    /** @var Bootstrap */
    protected $bootstrap;
    /** @var Container */
    protected $container;
    /** @var Engine */
    protected $app;

    public function beforeInit(): void
    {

    }

    public function afterInit(): void
    {

    }

    public function onBootstrapCreated(): void
    {

    }

    public function init()
    {
        $this->beforeInit();

        $cls = $this->bootstrapClass;
        $this->bootstrap = new $cls($this->root);
        $this->onBootstrapCreated();

        $this->bootstrap->loadConfiguration($this->bootstrap->getMode());

        $this->container = $this->bootstrap->getContainer();
        $this->app = $this->container->get('app');

        $this->afterInit();
    }

    public function beforeRun(): void
    {

    }

    public function afterRun(): void
    {

    }

    public function run()
    {
        $this->init();
        try {
            $this->app->start();
        } catch (\Exception | \Throwable $e) {
            $this->exception($e);
        }
    }

    public function beforeTerminate()
    {

    }

    public function exception(\Throwable $e)
    {
    }
}