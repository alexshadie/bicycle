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

    public function init()
    {
        $this->beforeInit();

        $this->bootstrap = new ($this->bootstrapClass)($this->root);

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