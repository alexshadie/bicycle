<?php

namespace alexshadie\bicycle\controllers;

use alexshadie\bicycle\action\ActionResult;
use alexshadie\bicycle\action\RedirectResult;
use alexshadie\bicycle\action\ViewResult;
use alexshadie\bicycle\Container;
use alexshadie\bicycle\Engine;
use flight\net\Request;
use flight\net\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

class Controller
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var Container */
    protected $container;
    /** @var bool */
    protected $resultShouldBeReturned = false;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function callMethod($name, $arguments, array $overrideBeans = [])
    {
        list($methodType, $method) = explode("_", $name, 2);

        $reflectionClass = new \ReflectionClass($this);
        $reflectionMethod = $reflectionClass->getMethod($method);

        if (count($arguments) != $reflectionMethod->getNumberOfRequiredParameters()) {
            throw new \ErrorException("Arguments count mismatch");
        }

        $methodArgs = $reflectionMethod->getParameters();
        $params = [];

        foreach ($arguments as $arg) {
            $params[] = $arg;
        }

        $methodArgs = array_slice($methodArgs, count($arguments));

        foreach ($methodArgs as $methodArg) {
            $params[] = $this->container->getByType($methodArg->getClass()->getName());
        }

        switch ($methodType) {
            case 'exec':
                try {
                    $result = $this->beforeAction($this->container->getByType(Request::class), $method, $params);
                    if (!$result instanceof ActionResult) {
                        $this->preCall($method, $params);
                        $result = call_user_func_array([$this, $method], $params);
                        $this->postCall($method, $params);
                    }
                } catch (\Exception $e) {
                    if (method_exists($this, 'handleException')) {
                        $result = $this->handleException($e);
                    } else {
                        $this->failedCall($method, $params);
                        throw $e;
                    }
                }

                if (!$result instanceof ActionResult) {
                    $this->failedCall($method, $params);
                    throw new \ErrorException("Return value must be subclass of ActionResult");
                }

                if ($this->resultShouldBeReturned) {
                    return $this->processResult($result);
                } else {
                    $this->processResult($result)->send();
                }
                break;

            default:
                return call_user_func_array([$this, $method], $params);
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     * @throws \ErrorException
     * @throws \ReflectionException
     */
    public function __call($name, $arguments)
    {
        $this->callMethod($name, $arguments, []);
    }

    /**
     * @param Request $request
     * @param string $method
     * @param array $params
     * @return void|ActionResult
     */
    public function beforeAction(Request $request, string $method, array $params)
    {

    }

    protected function preCall(string $method, array $params)
    {
        return true;
    }

    protected function postCall(string $method, array $params)
    {
        return true;
    }

    protected function failedCall(string $method, array $params)
    {
        return true;
    }

    /**
     * @param ActionResult $result
     * @return Response
     * @throws \Exception
     */
    private function processResult(ActionResult $result)
    {
        $app = null;
        try {
            /** @var Engine $app */
            $app = $this->container->get('app');
        } catch (ContainerExceptionInterface $e) {
            throw new \Exception($e);
        }

        /** @var Response $response */
        $response = $app->response();

        $headers = $result->getHeaders();
        foreach ($headers as $header => $value) {
            $response->header($header, $value);
        }

        if ($result instanceof RedirectResult) {
            $app->_redirect($result->getResult(), $result->getCode());
            die();
        }

        if ($result instanceof ViewResult) {
            $this->prepareView($result);
        }

        $response->status(200)
            ->write($result->getResult());

        return $response;
    }

    /**
     * @param string $beanClass
     * @return object
     * @throws \Exception
     */
    protected function getBeanByInterface($beanClass)
    {
        return $this->container->autowire($beanClass);
    }

    /**
     * Extra view params setter. Use it to fill common blocks and so on.
     * @param ViewResult $result
     */
    protected function prepareView(ViewResult $result)
    {
        $result->setCommonParams([]);
    }

    /**
     * @param bool $resultShouldBeReturned
     * @return Controller
     */
    public function setResultShouldBeReturned(bool $resultShouldBeReturned): Controller
    {
        $this->resultShouldBeReturned = $resultShouldBeReturned;
        return $this;
    }

}
