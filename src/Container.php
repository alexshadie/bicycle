<?php


namespace alexshadie\bicycle;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Container extends \Symfony\Component\DependencyInjection\Container
{
    private $typesMap = [];

    public function __construct(ParameterBagInterface $parameterBag = null)
    {
        parent::__construct($parameterBag);
    }

    public function getByType($className)
    {
        $this->cacheTypeMap();
        if ($this instanceof $className) {
            return $this;
        }
        foreach ($this->typesMap as $type => $objects) {
            if ($type === $className || is_subclass_of($type, $className)) {
                if (count($objects) == 1) {
                    return $this->get($objects[0]);
                }
                $matching[] = $type;
            }
        }
        return null;
    }

    private function cacheTypeMap()
    {
        if ($this->typesMap) {
            return;
        }
        $reflection = new \ReflectionClass($this);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $name = $method->getName();
            if (preg_match('!^get(.*)Service$!', $name)) {
                $name = lcfirst(preg_replace('!^get(.*)Service$!', '\\1', $name));

                $matches = [];
                preg_match('!@return \\\\([\\\\a-z0-9_]+)!i', $method->getDocComment(), $matches);
                $returnClass = $matches[1];
                if (!$returnClass) {
                    continue;
                }
                if (!isset($this->typesMap[$returnClass])) {
                    $this->typesMap[$returnClass] = [];
                }
                $this->typesMap[strtolower($returnClass)][] = $name;
            }
        }
    }

    public function autowire($className)
    {
        $this->cacheTypeMap();
        $className = strval($className);
        if ($className === strval(self::class)) {
            error_log($className);
            return $this;
        }
        $className = strtolower($className);
        if (!isset($this->typesMap[$className])) {
            throw new \ErrorException("Service with class {$className} not found");
        }

        if (count($this->typesMap[$className]) > 1) {
            throw new \ErrorException("Multiple implementations for class {$className}");
        }

        return $this->get($this->typesMap[$className][0]);
    }
}