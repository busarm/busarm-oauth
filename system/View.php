<?php

namespace System;

use System\Dto\BaseDto;
use System\Dto\CollectionBaseDto;

abstract class View
{
    /**
     * @param BaseDto|array|null $data
     * @param array $httpHeaders
     */
    public function __construct(protected BaseDto|array|null $data = null, protected $headers = array())
    {
    }

    /**
     * Fetches the view result intead of sending it to the output buffer
     *
     * @param BaseDto|array|null $data View Data
     * @param array $headers Http headers
     * @return string
     */
    public static function load(BaseDto|array|null $data = null, $headers = array())
    {
        $view = new static($data, $headers);
        $view->start();
        $view->render();
        return $view->end();
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function addHeader($name, $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Fetch view file
     *
     * @param string $path
     * @param bool $return
     * @return void 
     */
    public function include($path, $return = false)
    {
        $params = [];

        if ($this->data instanceof CollectionBaseDto) $params = $this->data->toArray();
        else if ($this->data instanceof BaseDto) $params = $this->data->toArray();
        else if (is_array($this->data) || is_object($this->data)) $params = (array) $this->data;
        else if (is_string($this->data)) $params  = ['data' => $this->data];

        $content = app()->loader->view($path, $params, $return);

        if (!$return) echo $content;
        else return $content;
    }

    /**
     * 
     * Renders the view
     *
     * @return void
     */
    public abstract function render();

    /**
     * 
     * Get view data
     *
     * @return BaseDto|array|null
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Start output buffer
     * 
     * @return void
     */
    protected function start()
    {
        ob_start();
    }

    /**
     * End output buffer
     * 
     * @return string
     */
    protected function end()
    {
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * @param bool $continue
     */
    public function send($continue = false)
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return;
        }

        // clean buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // start buffer
        $this->start();
        $this->render();
        $content = $this->end();

        return app()->response->addHttpHeaders($this->headers)->html($content, 200, $continue);
    }
}
