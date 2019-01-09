<?php


namespace alexshadie\bicycle;


class DefaultConfigFileResolver implements ConfigFileResolverInterface
{
    public function getConfig(string $configPath, string $mode)
    {
        return $configPath . "config." . $mode . ".yml";
    }
}