<?php

/**
 * A class for interacting with the command line from Patomic
 * Used as a dependency for the Patomic class
 */
class PatomicShell implements SplObserver
{
        private $config         = array();
        private $filePointer    = null;

        public function startService() {
                $script = "cd ".$this->config["dir"].";"; 
                $script .= " ./bin/rest -p ".$this->config["port"];
                $script .= " ".$this->config["alias"];
                $script .= " datomic:".$this->config["storage"]."://";

                //$filePointer = popen($script, "r");
                shell_exec($script."> /dev/null 2>/dev/null &");
        }

        public function setConfig(Array $datomic) {
                $this->config = $datomic;
        }

        /**
         * @override
         */
        public function update(SplSubject $subject) {
                echo $subject->getStatus().PHP_EOL;
        }
}
