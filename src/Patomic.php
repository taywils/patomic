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
        );
        private $pShell = null;

        public function __construct($dir = null, $port = 9998, $storage = "mem", PatomicShell $pShell = null ) {
                try {
                        if(!isset($dir)) {
                                throw new PatomicException("Could find datomic folder");
                        }

                        if(!isset($pShell)) {
                                throw new PatomicException("Must add a PatomicShell object to this constructor");
                        }

                        $this->datomic["dir"]           = $dir;
                        $this->datomic["port"]          = $port;
                        $this->datomic["storage"]       = $storage;

                        $this->pShell = $pShell;
                } catch(PatomicException $e) {
                        echo $e;
                }
        }
}
