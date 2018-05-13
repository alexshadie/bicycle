<?php


namespace alexshadie\bicycle\action;


class RestJsonResult extends ActionResult
{
    /** @var string */
    private $data;

    /**
     * TextResult constructor.
     * @param array $data
     * @param int $code
     */
    public function __construct(array $data, int $code = 200)
    {
        parent::__construct($code);
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getResult(): string
    {
        return json_encode($this->data);
    }
}