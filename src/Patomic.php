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

                        $this->connect();
                } catch(Exception $e) {
                        echo $e.PHP_EOL;
                        exit();
                }
        }

        public function connect() {
                try {
                } catch(Exception $e) {
                        echo $e.PHP_EOL;
                }

                $this->printStatus(true);
        }

        // I ran into too many issues with Guzzle
        //curl -H "Content-Type: application/x-www-form-urlencoded" -X POST http://localhost:9998/data/demo/?db-name=apple
        public function createDatabase($dbName = null) {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $this->config["dataUrl"].$this->config["alias"]."/");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "db-name=$dbName");

                curl_exec($ch);
                curl_close($ch);
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
