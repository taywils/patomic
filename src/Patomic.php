<?php
require "PatomicException.php";
require "PatomicShell.php";

/**
 * Main class for Patomic
 * Every instance of this class represents a connection to the Datomic REST service
 */
class Patomic
{
        private $datomic = array(
                "dir"           => "",
                "port"          => 0,
                "storage"       => "",
                "alias"         => "",
        );
        private $pShell = null;

        public function __construct($dir = null, $port = 9998, $storage = "mem", $alias = null, PatomicShell $pShell = null ) {
                try {
                        if(!isset($dir)) {
                                throw new PatomicException("Could find datomic folder");
                        }

                        if(!isset($alias)) {
                                throw new PatomicException("Datomic alias not set");
                        }

                        if(!isset($pShell)) {
                                throw new PatomicException("Must add a PatomicShell object to this constructor");
                        }

                        $this->datomic["dir"]           = $dir;
                        $this->datomic["port"]          = $port;
                        $this->datomic["storage"]       = $storage;
                        $this->datomic["alias"]         = $alias;

                        $this->pShell = $pShell;
                        $this->pShell->setConfig($this->datomic);
                } catch(PatomicException $e) {
                        echo $e;
                }
        }
}
