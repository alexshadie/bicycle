<?php


namespace alexshadie\bicycle\controllers;


use alexshadie\bicycle\Container;
use Psr\Log\LoggerInterface;

class ControllerFactory
{
    /** @var LoggerInterface */
    private $logger;
    /** @var Container */
    private $container;
    /** @var Controller[] */
    private $controllers = [];

    public function __construct(Container $container, LoggerInterface $logger, $controllerNamespace)
    {
        $this->logger = $logger;
        $this->container = $container;
        $this->controllerNamespace = $controllerNamespace;
    }

    public function __call($name, $arguments)
    {
        if (isset($this->controllers[$name])) {
            return $this->controllers[$name];
        }
        $matches = [];
        if (!preg_match('!get(.*Controller)!', $name, $matches)) {
            throw new \ErrorException("Cannot call ControllerFactory::$name");
        }
        $controllerName = $matches[1];
        $controllerClass = $this->controllerNamespace . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new \ErrorException("Unknown controller, class {$controllerClass}, {$matches[1]}");
        };
        return $this->controllers[$name] = $this->getController($controllerClass);
    }

    /**
     * @param $controllerClass
     * @return Controller
     * @throws \Exception
     */
    protected function getController($controllerClass)
    {
        $class = null;
        try {
            $class = new \ReflectionClass($controllerClass);
        } catch (\ReflectionException $e) {
            throw new \Exception("Cannot instantiate class", $e);
        }

        $ctor = $class->getConstructor();
        $ctorArgs = $ctor->getParameters();
        $args = [];
        foreach ($ctorArgs as $ctorArg) {
            if (!$ctorArg->getType()) {
                throw new \ErrorException("Undefined constructor args param '" . $ctorArg->getName() . "' for controller " . $controllerClass);
            }
            $arg = $this->container->getByType($ctorArg->getType()->getName());
            if (!$arg) {
                throw new \ErrorException(
                    "Constructor arg '" . $ctorArg->getName() . "' " .
                    "of type ' " . $ctorArg->getType() . "' " .
                    "for controller " . $controllerClass . " not found in container"
                );
            }
            $args[] = $arg;
        }

        /** @var Controller $obj */
        $obj = $class->newInstanceArgs($args);
        $obj->setContainer($this->container);
        $obj->setLogger($this->logger);

        return $obj;
    }
}