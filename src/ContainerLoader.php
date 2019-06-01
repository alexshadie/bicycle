<?php

namespace alexshadie\bicycle;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Compiler\ResolveParameterPlaceHoldersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Initializes Symfony Container from configuration files
 */
class ContainerLoader
{
    /**
     * @param string $configFile
     * @param array $additionalParams
     * @param string $containerDumpFile
     * @param string $containerClassPrefix
     * @param bool $debug
     * @return ContainerInterface
     * @throws \Exception
     */
    public function load(
        $configFile,
        array $additionalParams,
        $containerDumpFile,
        $containerClassPrefix,
        $debug
    )
    {
        if ($containerDumpFile) {
            $class = $containerClassPrefix . 'ProjectServiceContainer';
            $configCache = new ConfigCache($containerDumpFile, $debug);
            if ($configCache->isFresh() && !$debug) {
                require_once $containerDumpFile;
                $container = new $class();
            } else {
                $container = $this->compileContainer($configFile, $additionalParams);

                $dumper = new PhpDumper($container);
                if (!$debug) {
                    $configCache->write(
                        $dumper->dump([
                            'base_class' => 'alexshadie\bicycle\Container',
                            'class' => $class,
                        ]),
                        $container->getResources()
                    );
                    require_once $containerDumpFile;
                } else {
                    $containerCode = $dumper->dump([
                        'base_class' => 'alexshadie\bicycle\Container',
                        'class' => $class,
                    ]);
                    eval(
                    str_replace("<?php", "", $containerCode)
                    );
                }
                $container = new $class();
            }
        } else {
            $container = $this->compileContainer($configFile, $additionalParams);
        }

        return $container;
    }

    /**
     * Compiles container to file
     * @param string $configFile
     * @param array $additionalParams
     * @return ContainerBuilder
     * @throws \Exception
     */
    private function compileContainer($configFile, array $additionalParams)
    {
        $container = new ContainerBuilder();

        $container->set("container", $container);

        $fileLocator = new FileLocator(dirname($configFile));
        $loaderResolver = new LoaderResolver(array(
            new YamlFileLoader($container, $fileLocator),
            new XmlFileLoader($container, $fileLocator),
        ));
        $loaderResolver->resolve($configFile)->load($configFile);

        foreach ($additionalParams as $name => $value) {
            $container->setParameter($name, $value);
        }
        $passes = $container->getCompilerPassConfig()->getOptimizationPasses();
        foreach ($passes as $k => $pass) {
            if ($pass instanceof ResolveParameterPlaceHoldersPass) {
                unset($passes[$k]);
                break;
            }
        }
        $container->getCompilerPassConfig()->setOptimizationPasses($passes);

        $container->compile();

        return $container;
    }
}
