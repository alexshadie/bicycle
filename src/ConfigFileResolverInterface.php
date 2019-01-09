<?php


namespace alexshadie\bicycle;


interface ConfigFileResolverInterface
{
    public function getConfig(string $configPath, string $mode);
}