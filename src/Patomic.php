<?php

require_once "../vendor/autoload.php";

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

    const SUCCESS = true;
    const FAILURE = false;
    const ST_WARN = "WARN: ";
    const ST_FATL = "FATAL: ";
    const ST_INFO = "INFO: ";

    use TraitEdn;

    /**
     * Creates a new Patomic object
     *
     * @param int $port
     * @param string $storage
     * @param string $alias
     *
     * @throws PatomicException
     *
     * @return Patomic object
     */
    public function __construct($port = 9998, $storage = "mem", $alias = null) {
        try {
            if(!isset($alias)) {
                throw new PatomicException("\$alias argument must be set");
            }

            if(!in_array($storage, $this->storageTypes)) {
                $msg = "\$storage argument must be the correct string".PHP_EOL;
                $msg .= "Valid storage strings are \"" . implode($this->storageTypes, " ") . "\"";
                throw new PatomicException($msg);
            }

            $this->config["port"]          = $port;
            $this->config["storage"]       = $storage;
            $this->config["alias"]         = $alias;
            $this->config["dataUrl"]       = $this->config["serverUrl"]."$port/data/";
            $this->config["apiUrl"]        = $this->config["serverUrl"]."$port/api/";

            $this->statusQueue = new SplQueue();
        } catch(Exception $e) {
            echo $e.PHP_EOL;
            exit();
        }
    }

    /**
     * Creates a new PatomicEntity object
     *
     * @param string $schemaName
     * @param string $valueType
     *
     * @return PatomicEntity object
     */
    public function createSchema($schemaName, $valueType) {
        try {
            return new PatomicEntity($schemaName, $valueType);
        } catch(PatomicException $e) {
            echo $e . PHP_EOL;
        }
    }

    /**
     * Creates a new database
     *
     * @param string $dbName
     *
     * @return bool true if successful
     */
    public function createDatabase($dbName = null) {
        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->config["dataUrl"] . $this->config["alias"] . "/",
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => "db-name=$dbName",
            CURLOPT_RETURNTRANSFER => 1
        ));

        curl_exec($ch);

        if(curl_error($ch)) {
            $this->addStatus(self::ST_WARN, "Non HTTP error, something is wrong with the request");
            return self::FAILURE;
        } else {
            $info = curl_getinfo($ch);
            switch($info["http_code"]) {
                case "200":
                    $this->addStatus(self::ST_WARN, "Database \"$dbName\" already exists");
                    $retCode = self::FAILURE;
                    break;

                case "201":
                    $this->addStatus(self::ST_INFO, "Database \"$dbName\" created");
                    $retCode = self::SUCCESS;
                    break;

                default:
                    $this->addStatus(self::ST_WARN, "HTTP Status code " . $info["http_code"] . " returned");
                    $retCode = self::FAILURE;
            }
            curl_close($ch);
        }

        $this->printStatus();        

        return $retCode;
    }

    /**
     * Get the names of each database created
     * Datomic will return the database names as a string representation 
     * of an EDN vector
     *
     * @return array An array of database names
     */
    public function getDatabaseNames() {
        $dbVector = null;
        $dbNames = array();

        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->config["dataUrl"] . $this->config["alias"] . "/",
            CURLOPT_HTTPHEADER => array('Accept: application/edn'),
            CURLOPT_RETURNTRANSFER => 1
        ));

        $out = curl_exec($ch);

        if(curl_error($ch)) {
            return $dbNames;
        } else {
            // Parse the Datomic string response into a EDN vector
            $parsedOut = $this->_parse($out);
            if(!empty($parsedOut)) {
                // Transform EDN vector into PHP array
                $dbVector = $parsedOut[0];
                $dbNames = $dbVector->data;
            }
            curl_close($ch);
        }

        return $dbNames;
    }

    /**
     * Adds a message to the status queue
     * @param $statusCode
     * @param $msg
     */
    private function addStatus($statusCode, $msg) {
        $this->statusQueue->enqueue($statusCode.$msg);
    }

    /**
     * Prints a single message from the status queue
     * @param bool $printAll When true will dump the status queue
     */
    private function printStatus($printAll = false) {
        if(!$this->statusQueue->isEmpty()) {
            if($printAll) {
                while(!$this->statusQueue->isEmpty()) {
                    echo $this->statusQueue->dequeue() . PHP_EOL;
                }
            } else {
                echo $this->statusQueue->dequeue() . PHP_EOL;
            }
        }
    }
}

$patomic = new Patomic(9998, "mem", "taywils");
if($patomic->createDatabase("donkey")) {
    $names = $patomic->getDatabaseNames();
    var_dump($names);
}