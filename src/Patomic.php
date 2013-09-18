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
                "serverUrl"     => "http://localhost:",
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

                        if(!in_array($storage, $this->storageTypes)) {
                                $msg = "\$storage argument must be the correct string".PHP_EOL;
                                $msg .= "Valid storage strings are \"".implode($this->storageTypes, " ")."\"";
                                throw new PatomicException($msg);
                        }

                        $this->config["port"]          = $port;
                        $this->config["storage"]       = $storage;
                        $this->config["alias"]         = $alias;
                        $this->config["dataUrl"]       = $this->config["serverUrl"]."$port/data/";
                        $this->config["apiUrl"]        = $this->config["serverUrl"]."$port/api/";

                        $this->statusQueue = new SplQueue();

                        $this->dataClient = new \Guzzle\Http\Client($this->config["dataUrl"]);
                        $this->apiClient  = new \Guzzle\Http\Client($this->config["apiUrl"]);

                        $this->connect();
                } catch(Exception $e) {
                        echo $e.PHP_EOL;
                        exit();
                }
        }

        public function connect() {
                try {
                        $dataRes = $this->dataClient->get('/')->send();
                        $apiRes  = $this->apiClient->get('/')->send();

                        if(!($dataRes->isSuccessful() && $apiRes->isSuccessful())) {
                                throw new PatomicException("Patomic::connect failure, is the Datomic server running?");
                        } else {
                                $this->addStatus("Patomic connection sucessful on ".$this->config["dataUrl"]." and ".$this->config["apiUrl"]);
                        }
                } catch(Exception $e) {
                        echo $e.PHP_EOL;
                }

                $this->printStatus(true);
        }

        public function createDatabase($dbName = null) {
                try {
                        //curl -H "Content-Type: application/x-www-form-urlencoded" -X POST http://localhost:9998/data/demo/?db-name=apple
                        if(!isset($dbName)) {
                               throw new PatomicException("Patomic::createDatabase called without a valid \$dbName argument"); 
                        } else {
                                $req = $this->dataClient->post($this->config["alias"]."/");

                                $query = $req->getQuery();
                                $query->add('db-name', $dbName);

                                $res = $req->send();

                                print_r($res);

                                $this->addStatus($req->getUrl());
                                $this->addStatus($res);
                        }
                } catch(Exception $e) {
                        echo $e.PHP_EOL;
                }
                $this->printStatus(true);
        }

        private function addStatus($msg) {
                $this->statusQueue->enqueue($msg);
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

$patomic = new Patomic(9998, "mem", "demo");
$patomic->createDatabase("squid");
