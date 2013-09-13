<?php
require "temploader.php";
require "PatomicException.php";

use Guzzle\Http\Client;

/**
 * Main class for Patomic
 * Every instance of this class represents a connection to the Datomic REST service
 */
class Patomic
{
        private $config = array(
                "port"          => 0,
                "storage"       => "",
                "alias"         => "",
        );
        private $storageTypes   = array("mem", "dev", "sql", "inf", "ddb");
        private $statusQueue    = null;

        public function __construct($port = 9998, $storage = "mem", $alias = null, $url = null) {
                try {
                        if(!isset($alias)) {
                                throw new PatomicException("\$alias argument must be set");
                        }

                        $this->config["port"]          = $port;
                        $this->config["storage"]       = $storage;
                        $this->config["alias"]         = $alias;

                        $this->statusQueue = new SplQueue();
                } catch(PatomicException $e) {
                        echo $e.PHP_EOL;
                }
        }

        public function ping() {
                
        }

        private function printStatus() {
                if(!$this->statusQueue->isEmpty()) {
                        echo $this->statusQueue->dequeue().PHP_EOL;
                }
        }
}

$patomic = new Patomic(9998, "mem", "patomic");
