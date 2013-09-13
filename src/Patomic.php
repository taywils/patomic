<?php
require "PatomicException.php";
require "PatomicShell.php";

/**
 * Main class for Patomic
 * Every instance of this class represents a connection to the Datomic REST service
 * Uses Observer pattern to notify PatomicShell
 *
 * @see http://www.php.net/manual/en/class.splobserver.php
 */
class Patomic implements SplSubject
{
        private $shellConfig = array(
                "dir"           => "",
                "port"          => 0,
                "storage"       => "",
                "alias"         => "",
        );
        private $storageTypes   = array("mem", "dev", "sql", "inf", "ddb");
        private $isRunning      = false;
        private $observers      = array();
        private $status         = "";

        public function __construct($dir = null, $port = 9998, $storage = "mem", $alias = null, PatomicShell $pShell = null) {
                try {
                        if(!isset($dir)) {
                                throw new PatomicException("Did not provide a value for the Datomic directory");
                        }

                        if(!isset($alias)) {
                                throw new PatomicException("Datomic alias not set");
                        }

                        if(!isset($pShell)) {
                                throw new PatomicException("Must add a PatomicShell object to this constructor");
                        }

                        $this->shellConfig["dir"]           = $dir;
                        $this->shellConfig["port"]          = $port;
                        $this->shellConfig["storage"]       = $storage;
                        $this->shellConfig["alias"]         = $alias;

                        $pShell->setConfig($this->shellConfig);

                        $pShell->startService();

                        $this->attach($pShell);

                        $this->isRunning = true;
                        $this->status = "datomic:$storage:// $alias running on localhost:$port"; 
                        $this->notify();
                } catch(PatomicException $e) {
                        echo $e;
                }
        }

        public function getStatus() {
                return $this->status;
        }

        /**
         * @override
         */
        public function attach(SplObserver $obs) {
                $this->observers[] = $obs;        
        }

        /**
         * @override
         */
        public function detach(SplObserver $obs) {
                $key = array_search($obs, $this->observers, true);
                if($key) {
                        unset($this->observers[$key]);
                }
        }

        /**
         * @override
         */
        public function notify() {
                foreach($this->observers as $observer) {
                        $observer->update($this);
                }
        }
}

$ps = new PatomicShell();
$patomic = new Patomic("/Users/demetriouswilson/Documents/datomic", 9998, "mem", "patomic", $ps);
