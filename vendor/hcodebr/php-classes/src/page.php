<?php


namespace Hcode;

use Rain\Tpl;

class Page
{
    private $tpl;
    private $options = [];
    private $defaults = [
        "data" => []
    ];

    public function __construct($opts = array())
    {
        //caso passe um parametro em opts e de conflito com defaults, vai prevalecer o opts, juntando
        //as duas e mesclando em opitions
        $this->options = array_merge($this->defaults, $opts);
        $config = array(
            "tpl_dir"  => $_SERVER["DOCUMENT_ROOT"] . "/views/",
            "cache_dir"  => $_SERVER["DOCUMENT_ROOT"] . "/views-cache/"
        );

        Tpl::configure($config);

        $this->tpl = new Tpl;

        $this->setData($this->options["data"]);

        $this->tpl->draw("header");
    }

    public function setData($data = array())
    {
        foreach ($data as $key => $value) {
            $this->tpl->assign($key, $value);
        }
    }

    public function setTpl($name, $data = array(), $returnHtml = false)
    {
        $this->setData($data);

       return $this->tpl->draw($name, $returnHtml);
    }

    public function __destruct()
    {
        $this->tpl->draw("footer");
    }
}
