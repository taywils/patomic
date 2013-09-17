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
                "dataUrl"       => "",
                "apiUrl"        => "",
        );
        private $storageTypes   = array("mem", "dev", "sql", "inf", "ddb");
        private $statusQueue    = null;
        private $dataClient     = null;
        private $apiClient      = null;

        public function __construct($port = 9998, $storage = "mem", $alias = null) {
                try {
                        if(!isset($alias)) {
                                throw new PatomicException("\$alias argument must be set");
                        }

                        if(!array_search($storage, $this->storageTypes, true)) {
                                $msg = "\$storage argument must be the correct string".PHP_EOL;
                                $msg .= "Valid storage strings are \"".implode($this->storageTypes, " ")."\"";
                                throw new PatomicException($msg);
                        }

                        $this->config["port"]          = $port;
                        $this->config["storage"]       = $storage;
                        $this->config["alias"]         = $alias;
                        $this->config["dataUrl"]       = "http://localhost:$port/data/";
                        $this->config["apiUrl"]        = "http://localhost:$port/api/";

                        $this->statusQueue = new SplQueue();

                        $this->dataClient = new \Guzzle\Http\Client($this->config["dataUrl"]);
                        $this->apiClient  = new \Guzzle\Http\Client($this->config["apiUrl"]);
                } catch(Exception $e) {
                        echo $e.PHP_EOL;
                        exit();
                }
        }

        public function connect() {
                $req = $this->dataClient->get('/');
                $this->statusQueue->enqueue($req->send());

                $req = $this->apiClient->get('/');
                $this->statusQueue->enqueue($req->send());

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
