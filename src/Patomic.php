<?php
require "temploader.php";
require "PatomicException.php";

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
        private $restClient     = null;
        private $dataRequest    = null;
        private $apiRequest     = null;

        public function __construct($port = 9998, $storage = "mem", $alias = null) {
                try {
                        if(!isset($alias)) {
                                throw new PatomicException("\$alias argument must be set");
                        }

                        $this->config["port"]          = $port;
                        $this->config["storage"]       = $storage;
                        $this->config["alias"]         = $alias;

                        $this->statusQueue = new SplQueue();

                        $this->restClient = new \Guzzle\Http\Client("http://localhost:$port/");
                } catch(PatomicException $e) {
                        echo $e.PHP_EOL;
                }
        }

        public function connect() {
                $this->dataRequest = $this->restClient->get('/data/');
                $this->apiRequest  = $this->restClient->get('/api/');

                $this->statusQueue->enqueue($this->dataRequest);
                $this->statusQueue->enqueue($this->apiRequest);

                $this->printStatus(true);
        }

        public function createDatabase() {
                //curl -H "Content-Type: application/x-www-form-urlencoded" -X POST http://localhost:9998/data/demo/?db-name=apple
        }

        private function printStatus($printAll = false) {
                if(!$this->statusQueue->isEmpty()) {
                        if($printAll) {
                                while(!$this->statusQueue->isEmpty()) {
                                        echo $this->statusQueue->dequeue().PHP_EOL;
                                }
                        } else {
                                echo $this->statusQueue->dequeue().PHP_EOL;
                        }
                }
        }
}

$patomic = new Patomic(9998, "mem", "patomic");
$patomic->connect();
