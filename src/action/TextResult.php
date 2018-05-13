<?php


namespace alexshadie\bicycle\action;


class TextResult extends ActionResult
{
    /** @var string */
    private $text;

    /**
     * TextResult constructor.
     * @param string $text
     * @param int $code
     */
    public function __construct(string $text, int $code = 200)
    {
        parent::__construct($code);
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getResult(): string
    {
        return $this->text;
    }
}