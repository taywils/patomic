<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Main class for Patomic
 * Every instance of this class represents a connection to the Datomic REST service
 */
class Patomic
{
    private $config = array(
        "port"          => null,
        "storage"       => null,
        "alias"         => null,
        "dataUrl"       => null,
        "apiUrl"        => null,
        "serverUrl"     => null,
        "dbName"        => null
    );
    private $storageTypes   = array("mem", "dev", "sql", "inf", "ddb");
    private $statusQueue    = null;

    public $queryResult             = array();
    private $queryResponse          = null;
    private $transactionResponse    = null;

    private static $RAW_QUERY       = "rawquery";
    private static $REGULAR_QUERY   = "regularquery";

    private static $SUCCESS = true;
    private static $FAILURE = false;
    private static $ST_WARN = "WARN: ";
    private static $ST_FATL = "FATAL: ";
    private static $ST_INFO = "INFO: ";

    use TraitEdn;

    /**
     * Creates a new Patomic object
     *
     * @param string $serverUrl
     * @param int $port
     * @param string $storage
     * @param string $alias
     *
     * @throws PatomicException
     *
     * @return Patomic object
     */
    public function __construct($serverUrl = null, $port = null, $storage = "mem", $alias = null) {
        try {
            if(!isset($serverUrl)) {
                throw new PatomicException("\$serverUrl argument must be set");
            }
            if(!is_string($serverUrl)) {
                throw new PatomicException("\$serverUrl must be a string");
            }
            if(!filter_var($serverUrl, FILTER_VALIDATE_URL)) {
                throw new PatomicException("\$serverUrl must be a valid URL");
            }

            if(!isset($port)) {
                throw new PatomicException("\$port argument must be set");
            }
            if(!is_int($port)) {
                throw new PatomicException("\$port must be an integer");
            }

            if(!is_string($storage)) {
                throw new PatomicException("\$storage must be a string");
            }
            if(!in_array($storage, $this->storageTypes)) {
                throw new PatomicException("\$storage must be one of the following ["
                    . implode(", ", $this->storageTypes) . "]");
            }

            if(!isset($alias)) {
                throw new PatomicException("\$alias argument must be set");
            }
            if(!is_string($alias)) {
                throw new PatomicException("\$alias must be a string");
            }

            if(!in_array($storage, $this->storageTypes)) {
                $msg = "\$storage argument must be the correct string".PHP_EOL;
                $msg .= "Valid storage strings are \"" . implode($this->storageTypes, " ") . "\"";
                throw new PatomicException($msg);
            }

            $this->config["serverUrl"]  = $serverUrl . ":";
            $this->config["port"]       = $port;
            $this->config["storage"]    = $storage;
            $this->config["alias"]      = $alias;
            $this->config["dataUrl"]    = $this->config["serverUrl"] . "$port/data/";
            $this->config["apiUrl"]     = $this->config["serverUrl"] . "$port/api/query";

            $this->statusQueue = new SplQueue();
        } catch(Exception $e) {
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
            CURLOPT_POSTFIELDS => "db-name=" . strtolower($dbName),
            CURLOPT_RETURNTRANSFER => 1
        ));

        curl_exec($ch);

        if(curl_error($ch)) {
            $this->addStatus(self::$ST_WARN, "Non HTTP error, something else caused database creation to fail");
            $retCode = self::$FAILURE;
        } else {
            $info = curl_getinfo($ch);
            switch($info["http_code"]) {
                case "200":
                    $this->addStatus(self::$ST_WARN, "Database \"$dbName\" already exists");
                    $retCode = self::$FAILURE;
                    break;

                case "201":
                    $this->addStatus(self::$ST_INFO, "Database \"$dbName\" created");
                    $retCode = self::$SUCCESS;
                    break;

                default:
                    $this->addStatus(self::$ST_FATL, "HTTP Status code " . $info["http_code"] . " returned");
                    $retCode = self::$FAILURE;
            }
        }

        $this->printStatus();
        curl_close($ch);
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

        if(!curl_error($ch)) {
            // Parse the Datomic string response into a EDN vector
            $parsedOut = $this->_parse($out);
            if(!empty($parsedOut)) {
                // Transform EDN vector into PHP array
                $dbVector = array_values($parsedOut)[0];
                $dbNames = $dbVector->data;
            }
        }

        curl_close($ch);
        return $dbNames;
    }

    /**
     * Assigns the current Patomic object to a specific database.
     * The database currently set will be where all transactions and queries take place.
     *
     * @param string $dbName Name of database
     *
     * @return string The current database name
     *
     * @throws PatomicException
     */
    public function setDatabase($dbName = null) {
        if(!isset($dbName) || !is_string($dbName)) {
            throw new PatomicException("No \$dbName was given cannot assign Database");
        }

        $dbName = strtolower($dbName);

        $dbNames = $this->getDatabaseNames();

        if(empty($dbNames)) {
            throw new PatomicException("Cannot assign Database because none have been created");
        } else {
            // If the user gives an incorrect dbName just assign the first one found
            $this->config["dbName"] = (in_array($dbName, $dbNames)) ? $dbName : array_values($dbNames)[0];
            $this->addStatus(self::$ST_INFO, "A Patomic object set database to " . $this->config["dbName"]);
        }

        $this->printStatus();
        return $this->config["dbName"];
    }

    /**
     * Adds data to the database via a transaction.
     * All data to be added must be apart of an existing PatomicTransaction object
     *
     * @param PatomicTransaction $patomicTransaction
     *
     * @return bool
     */
    public function commitTransaction(PatomicTransaction $patomicTransaction) {
        $ch = curl_init();

        // Uses the __toString method of the PatomicTransaction class
        $transaction = sprintf($patomicTransaction);

        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->config["dataUrl"] . $this->config["alias"] . "/" . $this->config["dbName"] . "/",
            CURLOPT_HTTPHEADER => array('Accept: application/edn'),
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => "tx-data=" . $transaction,
            CURLOPT_RETURNTRANSFER => 1
        ));

        // To obtain the Datomic response use $this->_parse($rawResponse);
        $this->transactionResponse = curl_exec($ch);

        if(curl_error($ch)) {
            $this->addStatus(self::$ST_WARN, "Non HTTP error, something else caused database creation to fail");
            $retCode = self::$FAILURE;
        } else {
            $info = curl_getinfo($ch);

            switch($info["http_code"]) {
                case "201":
                    $this->addStatus(self::$ST_INFO, __FUNCTION__ . " success");
                    $retCode = self::$SUCCESS;
                    break;

                default:
                    $this->addStatus(self::$ST_WARN, __FUNCTION__ .  " HTTP Status code " . $info["http_code"] . " returned");
                    $retCode = self::$FAILURE;
            }
        }

        $this->printStatus();
        curl_close($ch);
        return $retCode;
    }

    public function getTransactionResponse() {
        return $this->transactionResponse;
    }

    public function commitRawQuery(PatomicQuery $patomicQuery) {
        $this->commitQuery($patomicQuery, self::$RAW_QUERY);
    }

    public function commitRegularQuery(PatomicQuery $patomicQuery) {
        $this->commitQuery($patomicQuery, self::$REGULAR_QUERY);
    }

    private function commitQuery(PatomicQuery $patomicQuery, $queryType) {
        if(self::$RAW_QUERY == $queryType) {
            $queryStr       = urlencode($patomicQuery->getRawQuery());
            $queryArgStr    = urlencode($patomicQuery->getRawQueryArgs());
        } else {
            $queryStr       = urlencode($patomicQuery->getQuery());
            $queryArgStr    = urlencode($patomicQuery->getQueryArgs());
        }

        $ch = curl_init();
        $this->queryResult = array();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->config["apiUrl"] . "?q=" . $queryStr . "&args=" . $queryArgStr,
            CURLOPT_HTTPHEADER => array('Accept: application/edn'),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => 1
        ));

        $this->queryResponse = curl_exec($ch);

        if(curl_error($ch)) {
            $this->addStatus(self::$ST_WARN, "Non HTTP error, something else caused database creation to fail");
            $retCode = self::$FAILURE;
        } else {
            $info = curl_getinfo($ch);

            switch($info["http_code"]) {
                case "200":
                    $this->addStatus(self::$ST_INFO, __FUNCTION__ . " success");
                    $retCode = self::$SUCCESS;
                    break;

                default:
                    $this->addStatus(self::$ST_WARN, __FUNCTION__ .  " HTTP Status code " . $info["http_code"] . " returned");
                    $retCode = self::$FAILURE;
            }
        }

        // Datomic query results are returned as a EDN vector where each result row is another vector
        if(self::$FAILURE == $retCode) {
            //Handle failure
        } else if(self::$RAW_QUERY == $queryType) {
            // Transforms the results Vector of Vectors into a multi-dimensional PHP array
            $this->queryResponse = array_values($this->_parse($this->queryResponse))[0];
            foreach($this->queryResponse->data as $rowVector) {
                $this->queryResult[] = array_values($rowVector->data);
            }
        } else {
            // Handle regular query results based on the PatomicQuery object's in() data and such
        }

        $this->printStatus();
        curl_close($ch);
        return $retCode;
    }

    public function getQueryResult() {
        return $this->queryResult;
    }

    public function getQueryResponse() {
        return $this->queryResponse;
    }

    /**
     * Adds a message to the status queue
     *
     * @param $statusCode
     * @param $msg
     */
    private function addStatus($statusCode, $msg) {
        $this->statusQueue->enqueue($statusCode.$msg);
    }

    /**
     * Prints a single message from the status queue
     *
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

/*try {
    $p = new Patomic("http://localhost", 9998, "mem", "taywils");
    $p->createDatabase("energy");
    $p->setDatabase("energy");

    $pe = new PatomicEntity("db");
    $pe->ident("country", "population")
        ->valueType("string")
        ->cardinality("one")
        ->unique("value")
        ->doc("The population of the country")
        ->install("attribute");

    $pt = new PatomicTransaction();
    $pt->append($pe);
    //$p->commitTransaction($pt);
} catch(PatomicException $e) {
    echo $e;
}*/

/*
try {
    $p = new Patomic("http://localhost", 9998, "mem", "taywils");
    $p->createDatabase("energy");
    $p->setDatabase("energy");

    $pq = new PatomicQuery();
    $pq->newRawQuery("[:find ?e ?v :in $ :where [?e :db/doc ?v]]")
        ->addRawQueryArgs("[{:db/alias taywils/energy}]");

    $test = $p->commitRawQuery($pq);
    print_r($test);
} catch(Exception $e) {
    echo $e . PHP_EOL;
}*/