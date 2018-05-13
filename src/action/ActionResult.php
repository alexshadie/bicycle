<?php

namespace alexshadie\bicycle\action;

abstract class ActionResult
{
    /** @var int */
    protected $code;

    /**
     * ActionResult constructor.
     * @param $code
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    abstract public function getResult(): string;
}