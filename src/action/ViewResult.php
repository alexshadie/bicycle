<?php


namespace alexshadie\bicycle\action;


class ViewResult extends ActionResult
{
    /** @var string */
    protected $view;
    /** @var array */
    protected $params;
    /** @var string */
    protected $layout;

    public function __construct(string $view, array $params, string $layout = 'public', int $code = 200)
    {
        parent::__construct($code);
        $this->view = $view;
        $this->layout = $layout;
        $this->params = $params;
    }

    /**
     * @return string
     * @throws \Exception
     * @throws \Throwable
     */
    public function getResult(): string
    {
        $cwd = getcwd();
        chdir(PATH_TO_VIEWS);
        $layoutFile = 'layout/' . $this->layout . ".php";
        $this->params['view_file'] = $viewFile = $this->view . ".php";
        try {
            ob_start();
            //  No layout support
            if ($this->layout) {
                $this->secureRender($layoutFile, $this->params);
            } else {
                $this->secureRender($viewFile, $this->params);
            }
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        chdir($cwd);
        return $output;
    }

    /**
     * @param $layout
     * @param $data
     */
    protected function secureRender($layout, $data)
    {
        extract($data);
        include func_get_arg(0);
    }

    public function setCommonParams(array $commonParams)
    {
        $this->params = array_merge($commonParams, $this->params);
    }
}