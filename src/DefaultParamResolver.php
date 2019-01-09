<?php


namespace alexshadie\bicycle;


class DefaultParamResolver implements ParamResolverInterface
{
    public function appendParams(array $params)
    {
        return $params;
    }
}