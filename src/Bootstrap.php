<?php

namespace alexshadie\bicycle;

use alexshadie\bicycle\http\exception\NotFoundException;

class Bootstrap
{
    /** @var string */
    private $pathToRoot;
    /** @var string */
    private $pathToApp;
    /** @var string */
    private $pathToRuntime;
    /** @var string */
    private $pathToCache;
    /** @var string */
    private $pathToLogs;

    /** @var string */
    private $profileName;
    /** @var Container */
    private $container;

    /** @var string  */
    private $configDir = "config";

    protected $pathToViews = 'views/';


    public function __construct($pathToRoot)
    {
        $this->pathToRoot = rtrim($pathToRoot, '/') . '/';
        $this->pathToApp = $pathToRoot . "src/";
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

        $configFile = $configPath . "config." . $mode . ".yml";

        $containerParams = [
            'path_to_application' => $this->pathToRoot,
            'path_to_scripts' => $this->pathToApp,
            'path_to_cache' => $this->pathToCache,
            'path_to_logs' => $this->pathToLogs,
            'path_to_www' => $this->pathToRoot . 'public/',
            'path_to_config' => $this->pathToRoot . $this->configDir . '/',
            'profile_name' => $this->profileName,
            'build_hash' => $buildHash,
        ];

        define('PATH_TO_VIEWS', $this->pathToApp . "/" . $this->pathToViews);

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