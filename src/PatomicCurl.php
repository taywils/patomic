<?php

require_once __DIR__ . "/../vendor/autoload.php";

interface HttpRequest
{
    public function setOption($name, $value);
    public function execute();
    public function getInfo($name);
    public function close();
}

/**
 * Basic utility class for common cURL functions
 * @see http://stackoverflow.com/questions/7911535/
 */
class PatomicCurl implements HttpRequest
{
    private $handle = null;

    public function __construct($url) {
        $this->handle = curl_init($url);
    }

    public function setOption($name, $value) {
        curl_setopt($this->handle, $name, $value);
    }

    public function execute() {
        return curl_exec($this->handle);
    }

    public function getInfo($name) {
        return curl_getinfo($this->handle, $name);
    }

    public function close() {
        curl_close($this->handle);
    }
}