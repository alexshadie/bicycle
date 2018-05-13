<?php


namespace alexshadie\bicycle;

use alexshadie\bicycle\controllers\ControllerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class Engine extends \flight\Engine
{
    /** @var LoggerInterface */
    private $logger;
    /** @var ContainerInterface */
    private $container;

    public function __construct(LoggerInterface $logger, ContainerInterface $container)
    {
        $this->logger = $logger;
        $this->container = $container;
        parent::__construct();
    }

    /**
     * @param array $routes
     * @throws \Exception
     */
    public function setRoutes($routes)
    {
        foreach ($routes as $key => $route) {
            list($controller, $action) = explode(':', $route);
            $controllerInstance = null;

            try {
                /** @var ControllerFactory $controllerFactory */
                $controllerFactory = $this->container->get('controllerFactory');
                $controllerInstance = $controllerFactory->__call('get' . ucfirst($controller), []);
            } catch (ServiceNotFoundException $e) {
                throw new \Exception("Service not found", 0, $e);
            }
            if (!method_exists($controllerInstance, $action)) {
                throw new \Exception("Invalid action {$action}");
            }
            $this->route($key, [$controllerInstance, 'exec_' . $action]);
        }
    }

    public function initialize()
    {

    }
}