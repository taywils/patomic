<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * cURL abstraction class for Patomic
 * A wrapper class built around php-curl-class
 *
 * @see http://www.php.net/manual/en/book.curl.php
 * @see https://github.com/php-curl-class/php-curl-class
 */
class PatomicCurl
{
        private $curl;

        public function __construct() {
                $this->curl = new Curl();
        }
}

$pcurl = new PatomicCurl();
