<?php


namespace alexshadie\bicycle\action;

class RedirectResult extends ActionResult
{
    /** @var string */
    private $url;

    /**
     * RedirectResult constructor.
     * @param string $url
     * @param int $code
     */
    public function __construct(string $url, int $code = 301)
    {
        parent::__construct($code);
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getResult(): string
    {
        return $this->url;
    }
}