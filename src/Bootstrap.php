<?php

namespace alexshadie\bicycle;

use alexshadie\bicycle\http\exception\NotFoundException;

class Bootstrap
{
    /** @var string */
    protected $pathToRoot;
    /** @var string */
    protected $pathToApp;
    /** @var string */
    protected $pathToRuntime;
    /** @var string */
    protected $pathToCache;
    /** @var string */
    protected $pathToLogs;

    /** @var string */
    protected $profileName;
    /** @var Container */
    protected $container;

    /** @var string  */
    protected $configDir = "config";

    /** @var string */
    protected $pathToViews = 'views/';
    /** @var string */
    protected static $basePathToViews;
    /** @var string */
    protected static $fullPathToViews;
    /** @var string */
    protected static $savedPathToViews;
    /** @var string */
    protected $configFileResolverClass = DefaultConfigFileResolver::class;
    /** @var string */
    protected $paramResolverClass = DefaultParamResolver::class;

    public static function overridePathToViews($newPath)
    {
        if (is_null(self::$savedPathToViews)) {
            self::$savedPathToViews = self::$fullPathToViews;
        }
        self::$fullPathToViews = self::$basePathToViews . '/' . $newPath;
    }

    public static function getPathToViews()
    {
        $path = self::$fullPathToViews;
        if (self::$savedPathToViews) {
            self::$fullPathToViews = self::$savedPathToViews;
            self::$savedPathToViews = null;
        }
        return $path;
    }

    public function __construct($pathToRoot)
    {
        $this->pathToRoot = rtrim($pathToRoot, '/') . '/';
        $this->pathToApp = $pathToRoot . "src/";
        self::$basePathToViews = $pathToRoot . "src/";
        $this->pathToRuntime = $pathToRoot . "runtime/";
        $this->pathToCache = $this->pathToRuntime . "cache/";
        $this->pathToLogs = $this->pathToRuntime . "logs/";

    }

    public function setConfigDir($dir)
    {
        $this->configDir = $dir;
    }

    public function getMode()
    {
        if (($_SERVER['REMOTE_ADDR']??'') == '127.0.0.1' && ($_SERVER['HTTP_HOST']??'') == 'localhost:8000') {
            define("TESTING", 1);
            return "testing";
        }
        return trim(file_get_contents($this->pathToRoot . ".mode"));
    }

    /**
     * @param $mode
     * @throws \Exception
     */
    public function loadConfiguration($mode, $containerClassPrefix = '')
    {
        $configPath = $this->pathToRoot . $this->configDir . "/";
        if (!$mode) {
            throw new \Exception("Mode not specified");
        }

        $this->profileName = $mode;

        $buildHash = $this->getBuildHash();

        /** @var ConfigFileResolverInterface $configResolver */
        $configResolver = new $this->configFileResolverClass;
        $configFile = $configResolver->getConfig($configPath, $mode);

        $containerParams = [
            'path_to_application' => $this->pathToRoot,
            'path_to_scripts' => $this->pathToApp,
            'path_to_cache' => $this->pathToCache,
            'path_to_logs' => $this->pathToLogs,
            'path_to_www' => $this->pathToRoot . 'public/',
            'path_to_config' => realpath($this->pathToRoot . $this->configDir . '/'),
            'path_to_runtime' => $this->pathToRuntime,
            'profile_name' => $this->profileName,
            'build_hash' => $buildHash,
        ];

        /** @var ParamResolverInterface $paramResolver */
        $paramResolver = new $this->paramResolverClass;
        $containerParams = $paramResolver->appendParams($containerParams);

        self::$fullPathToViews = $this->pathToApp . "/" . $this->pathToViews;

        $containerDumpFile = sprintf('%s/container-%s-%s.php', $this->pathToCache, $containerClassPrefix, $this->profileName);
        $containerLoader = new ContainerLoader();
        $this->container =
            $containerLoader->load(
                $configFile,
                $containerParams,
                $containerDumpFile,
                $containerClassPrefix,
                $mode !== 'production'
            );

        $this->container->set("container", $this->container);

        $this->initApp();
    }

    /**
     * Gets release hash
     * @return bool|int|string
     */
    protected function getBuildHash()
    {
        if (is_file($this->pathToRuntime . 'buildhash')) {
            $buildHash = file_get_contents($this->pathToRuntime . 'buildhash');
        } else {
            $buildHash = time();
            file_put_contents($this->pathToRuntime . 'buildhash', $buildHash);
        }
        return $buildHash;
    }

    private function initApp()
    {
        /** @var Engine $app */
        $app = $this->getContainer()->get('app');
        $app->set("flight.handle_errors", false);
        $app->map('error', function (\Throwable $e) {
            throw $e;
        });
        $app->map('notFound', function () {
            throw new NotFoundException('Path not found: "' . ($_SERVER['REQUEST_URI'] ?? "empty") . '"');
        });
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public function getProfileName()
    {
        return $this->profileName;
    }
}