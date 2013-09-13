<?php

/**
 * A class for interacting with the command line from Patomic
 * Used as a dependency for the Patomic class
 */
class PatomicShell
{
        private $config = array();

        public function startService() {
                $script = "cd ".$this->config["dir"].";"; 
                $script .= "./bin/rest -p ".$this->config["port"];
                //$script .=; 
                shell_exec($script);
        }

        public function setConfig(Array $datomic) {
                $this->config = $datomic;
        }
}
