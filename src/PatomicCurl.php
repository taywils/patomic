<?php

namespace taywils\Patomic;

interface HttpRequest
{
    public function setOptionArray($optionsArray);
    public function execute();
    public function getInfo();
    public function close();
    public function error();
}

/**
 * Basic utility class for common cURL functions
 * @see http://stackoverflow.com/questions/7911535/
 */
class PatomicCurl implements HttpRequest
{
    private $handle = null;

    public function __construct() {
        $this->handle = curl_init();
    }

    public function setOptionArray($optionsArray) {
        return curl_setopt_array($this->handle, $optionsArray);
    }

    public function execute() {
        return curl_exec($this->handle);
    }

    public function getInfo() {
        return curl_getinfo($this->handle);
    }

    public function close() {
        curl_close($this->handle);
    }

    public function error() {
        return curl_error($this->handle);
    }
}
