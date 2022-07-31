<?php

namespace System\Interfaces;

interface RequestInterface
{
    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function query($name, $default = null);

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function request($name, $default = null);

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function server($name, $default = null);

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function headers($name, $default = null);

    /**
     * @return array
     */
    public function getQueryList();
    /**
     * @return array
     */
    public function getRequestList();
    /**
     * @return array
     */
    public function getServerList();
    /**
     * @return array
     */
    public function getHeaderList();
}
