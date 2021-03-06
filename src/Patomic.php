<?php

namespace taywils\Patomic;

/**
 * Main class for Patomic
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
    private $transactionResponse    = "";
    private $dbNames                = array();

    private static $RAW_QUERY       = "rawquery";
    private static $REGULAR_QUERY   = "regularquery";

    private static $SUCCESS = true;
    private static $FAILURE = false;
    private static $ST_WARN = "WARN: ";
    private static $ST_FATL = "FATAL: ";
    private static $ST_INFO = "INFO: ";

    private $reflection;

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
        $this->statusQueue = new \SplQueue();
        $this->reflection = new \ReflectionClass($this);

        if(!isset($serverUrl)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$serverUrl argument must be set");
        }
        if(!is_string($serverUrl) || strlen(trim($serverUrl)) == 0) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$serverUrl must be a non-empty string");
        }

        if(!isset($port)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$port argument must be set");
        }
        if(!is_int($port)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$port must be an integer");
        }

        if(!is_string($storage) || strlen(trim($storage)) == 0) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$storage must be a non-empty string");
        }
        if(!in_array($storage, $this->storageTypes)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$storage must be one of the following ["
                . implode(", ", $this->storageTypes) . "]");
        }

        if(!in_array($storage, $this->storageTypes)) {
            $msg = " \$storage argument must be the correct string".PHP_EOL;
            $msg .= "Valid storage strings are \"" . implode($this->storageTypes, " ") . "\"";
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . $msg);
        }

        if(!isset($alias)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$alias argument must be set");
        }
        if(!is_string($alias) || strlen(trim($alias)) == 0) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$alias must be a non-empty string");
        }

        $this->config["serverUrl"]  = $serverUrl . ":";
        $this->config["port"]       = $port;
        $this->config["storage"]    = $storage;
        $this->config["alias"]      = $alias;
        $this->config["dataUrl"]    = $this->config["serverUrl"] . "$port/data/";
        $this->config["apiUrl"]     = $this->config["serverUrl"] . "$port/api/query";
    }

    /**
     * Creates a new database
     *
     * @param string $dbName
     *
     * @return bool true if successful
     */
    public function createDatabase($dbName = null) {
        $patomicCurl = new PatomicCurl();

        $patomicCurl->setOptionArray(array(
            CURLOPT_URL => $this->config["dataUrl"] . $this->config["alias"] . "/",
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => "db-name=" . urlencode(strtolower($dbName)),
            CURLOPT_RETURNTRANSFER => 1
        ));

        $patomicCurl->execute();

        if($patomicCurl->error()) {
            $this->addStatus(self::$ST_WARN, "Non HTTP error, something else caused database creation to fail");
            $retCode = self::$FAILURE;
        } else {
            $info = $patomicCurl->getInfo();
            $lambdaAddToDbNames = function($dbName) {
                if(!in_array($dbName, $this->dbNames)) {
                    $this->dbNames[] = $dbName;
                }
            };

            switch($info["http_code"]) {
                case "200":
                    $this->addStatus(self::$ST_WARN, "Database \"$dbName\" already exists");
                    $lambdaAddToDbNames($dbName);
                    $retCode = self::$FAILURE;
                    break;

                case "201":
                    $this->addStatus(self::$ST_INFO, "Database \"$dbName\" created");
                    $lambdaAddToDbNames($dbName);
                    $retCode = self::$SUCCESS;
                    break;

                default:
                    $this->addStatus(self::$ST_FATL, "HTTP Status code " . $info["http_code"] . " returned");
                    $retCode = self::$FAILURE;
            }
        }

        $this->printStatus();
        $patomicCurl->close();
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

        $patomicCurl = new PatomicCurl();

        $patomicCurl->setOptionArray(array(
            CURLOPT_URL => $this->config["dataUrl"] . $this->config["alias"] . "/",
            CURLOPT_HTTPHEADER => array('Accept: application/edn'),
            CURLOPT_RETURNTRANSFER => 1
        ));

        $out = $patomicCurl->execute();

        if(!$patomicCurl->error()) {
            // Parse the Datomic string response into a EDN vector
            $parsedOut = $this->_parse($out);
            if(!empty($parsedOut)) {
                // Transform EDN vector into PHP array
                $dbVector = array_values($parsedOut)[0];
                $dbNames = $dbVector->data;
            }
        }

        $patomicCurl->close();
        $this->dbNames = $dbNames;
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
        if(!isset($dbName) || !is_string($dbName) || strlen(trim($dbName)) == 0) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " \$dbName must be a non-empty string");
        }

        $dbName = strtolower($dbName);

        if(!in_array($dbName, $this->dbNames)) {
            $this->dbNames[] = $dbName;
        }

        $this->config["dbName"] = $dbName;
        $this->addStatus(self::$ST_INFO, "A Patomic object set database to " . $this->config["dbName"]);

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
        $patomicCurl = new PatomicCurl();

        // Uses the __toString method of the PatomicTransaction class
        $transaction = sprintf($patomicTransaction);

        $patomicCurl->setOptionArray(array(
            CURLOPT_URL => $this->config["dataUrl"] . $this->config["alias"] . "/" . $this->config["dbName"] . "/",
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => "tx-data=" . urlencode($transaction),
            CURLOPT_RETURNTRANSFER => 1
        ));

        // To obtain the Datomic response use $this->_parse($rawResponse);
        $this->transactionResponse = $patomicCurl->execute();

        if($patomicCurl->error()) {
            $this->addStatus(self::$ST_WARN, "Non HTTP error, something else caused database creation to fail");
            $retCode = self::$FAILURE;
        } else {
            $info = $patomicCurl->getInfo();

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
        $patomicCurl->close();
        return $retCode;
    }

    /**
     * Returns a string representing the most recent transaction
     *
     * @return string
     */
    public function getTransactionResponse() {
        return $this->transactionResponse;
    }

    /**
     * Accepts a new query object initialized with a raw query and performs a transaction.
     *
     * @param PatomicQuery $patomicQuery
     */
    public function commitRawQuery(PatomicQuery $patomicQuery) {
        $this->commitQuery($patomicQuery, self::$RAW_QUERY);
    }

    /**
     * Accepts a new query object initialized with a regular query and performs a transaction.
     *
     * @param PatomicQuery $patomicQuery
     */
    public function commitRegularQuery(PatomicQuery $patomicQuery) {
        $this->commitQuery($patomicQuery, self::$REGULAR_QUERY);
    }

    /**
     * Sends the desired query out to the Datomic REST client and stores the result.
     * @param PatomicQuery $patomicQuery
     * @param string $queryType will either be "regular" or "raw"
     * @return boolean true if successful
     */
    protected function commitQuery(PatomicQuery $patomicQuery, $queryType) {
        if(self::$RAW_QUERY == $queryType) {
            $queryStr       = urlencode($patomicQuery->getRawQuery());
            $queryArgStr    = urlencode($patomicQuery->getRawQueryArgs());
        } else {
            $queryStr       = urlencode($patomicQuery->getQuery());

            // Append the {:db/alias "storageName/dbName"} to the front of the current query arguments string
            $parsedEdnArray                 = $this->_parse("{:db/alias \"" . $this->config["alias"] . "/" . $this->config["dbName"] . "\"}");
            $nonUrlEncodedQueryArgString    = substr_replace($patomicQuery->getQueryArgs(), $this->_encode($parsedEdnArray[0]) . " ", 1, 0);

            $queryArgStr = urlencode($nonUrlEncodedQueryArgString);
        }

        $patomicCurl        = new PatomicCurl();
        $this->queryResult  = array();

        $patomicCurl->setOptionArray(array(
            CURLOPT_URL => $this->buildQueryUrl($queryStr, $queryArgStr, $patomicQuery),
            CURLOPT_HTTPHEADER => array('Accept: application/edn'),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_RETURNTRANSFER => 1
        ));

        $this->queryResponse = $patomicCurl->execute();

        if($patomicCurl->error()) {
            $this->addStatus(self::$ST_WARN, "Non HTTP error, something else caused database creation to fail");
            $retCode = self::$FAILURE;
        } else {
            $info = $patomicCurl->getInfo();

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
        if(self::$FAILURE != $retCode) {
            // Transforms the results Vector of Vectors into a multi-dimensional PHP array
            $this->queryResponse = array_values($this->_parse($this->queryResponse))[0];

            foreach($this->queryResponse->data as $rowVector) {
                if(self::$REGULAR_QUERY == $queryType) {
                    $this->queryResult[] = array_combine($patomicQuery->getFindEdn(), array_values($rowVector->data));
                } else {
                    $this->queryResult[] = array_values($rowVector->data);
                }
            }
        }

        $this->printStatus();
        $patomicCurl->close();
        return $retCode;
    }

    /**
     * Returns the most recent query result
     */
    public function getQueryResult() {
        return $this->queryResult;
    }

    /**
     * Adds a message to the status queue
     *
     * @param $statusCode
     * @param $msg
     */
    protected function addStatus($statusCode, $msg) {
        $this->statusQueue->enqueue($statusCode.$msg);
    }

    /**
     * Prints a single message from the status queue
     *
     * @param bool $printAll When true will dump the status queue
     */
    protected function printStatus($printAll = false) {
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

    /**
     * Constructs the Datomic REST client query path
     * GET /api/query?q=<query>&args=<args>[&limit=<limit>][&offset=<offset>]
     * @see http://docs.datomic.com/rest.html
     *
     * @param string $queryStr The current PatomicQuery query body
     * @param string $queryArgStr The current PatomicQuery argument body
     * @param PatomicQuery $patomicQuery
     *
     * @return string $path
     */
    protected function buildQueryUrl($queryStr, $queryArgStr, PatomicQuery $patomicQuery) {
        $path = $this->config["apiUrl"] . "?q=" . $queryStr . "&args=" . $queryArgStr;

        $limitValue     = $patomicQuery->getLimit();
        $offsetValue    = $patomicQuery->getOffset();

        $path .= ($limitValue > 0)  ? "&limit="     . $limitValue   : "";
        $path .= ($offsetValue > 0) ? "&offset="    . $offsetValue  : "";

        return $path;
    }
}
