<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Queries consist of three sections: :find, :in and :where.
 * The :find section specifies what the query should return.
 * The :in section specifies data sources. It is not necessary when querying a single data source, we'll talk about it later.
 * The :where section specifies one or more data, expression or rule clauses.
 *
 * @see http://docs.datomic.com/tutorial.html
 */

/**
 * Class designed to assist building Datomic Queries
 * Supports both the writing of raw Datalog style queries and more PHP friendly ORM style queries
 *
 * Many thanks to the authors of Diametric the Datomic Active Record wrapper for the Ruby programming language
 * additional documentation on how to design a Query wrapper for Datomic's REST API
 *
 * @see https://github.com/relevance/diametric
 */
class PatomicQuery
{
    private $rawQueryBody   = null;
    private $rawQueryArgs   = null;
    private $queryBody      = null;
    private $queryArgs      = null;
    private $findEdn        = array();
    private $whereEdn       = array();
    private $inEdn          = array();
    private $argsEdn        = array();

    use TraitEdn;

    public function __construct() {
    }

    /**
     * Allows one to write pure Datalog queries
     *
     * @param string $datalogString A string consisting of valid Datalog
     *
     * @return $this
     */
    public function newRawQuery($datalogString) {
        $this->rawQueryBody = "";

        foreach($this->_parse($datalogString) as $queryPart) {
            $this->rawQueryBody .= $this->_encode($queryPart);
        }

        return $this;
    }

    /**
     * Allows one to add arguments to a newRawQuery
     *
     * @param string $datalogString A string consisting of valid Datalog
     *
     * @throws PatomicException
     */
    public function addRawQueryArgs($datalogString) {
        $this->rawQueryArgs = "";

        if(!isset($this->rawQueryBody) || "" == $this->rawQueryBody) {
            throw new PatomicException("Create a newRawQuery before adding raw query arguments");
        }

        foreach($this->_parse($datalogString) as $argumentPart) {
            $this->rawQueryArgs .= $this->_encode($argumentPart);
        }
    }

    /**
     * Example: $patomicQuery->find("communityName");
     * Will represent the "find" part of a the actual EDN [:find ?communityName ... ]
     *
     * @throws PatomicException
     * @return $this
     */
    public function find() {
        $numargs = func_num_args();

        if($numargs < 1) {
            throw new PatomicException(__CLASS__ . "::" . __FUNCTION__  . " expects at least one \"string\" as an argument");
        }

        $argsArray = func_get_args();

        if(false == $this->validateFindArgs($argsArray)) {
            throw new PatomicException(__CLASS__ . "::" . __FUNCTION__  . " encountered a non string argument");
        }

        foreach($argsArray as $arg) {
            $this->findEdn[] = $arg;
        }

        return $this;
    }

    /**
     * Example:
     * $patomicQuery
     *      ->find("communityName")
     *      ->where(array("communityName" => "community/name"));
     *
     * Represents the "where" part of the actual EDN [:find ?communityName :where [?communityName :community/name]]
     *
     * @param $argArray
     * @throws PatomicException
     * @return $this
     */
    public function where($argArray) {
        if(!isset($argArray) || !is_array($argArray)) {
            throw new PatomicException(__CLASS__ . "::" . __FUNCTION__  . " expects an array as an argument");
        }

        // There might be a requirement that every key within the argArray must also be found within the $findEdn
        $this->whereEdn = array_merge($this->whereEdn, $argArray);

        print_r($this->whereEdn);

        return $this;
    }

    public function getRawQuery() {
        return $this->rawQueryBody;
    }

    public function getRawQueryArgs() {
        return $this->rawQueryArgs;
    }

    public function getQuery() {
        return $this->queryBody;
    }

    public function getQueryArgs() {
        return $this->queryArgs;
    }

    /**
     * Validates the arguments passed to the find function
     *
     * @param array $findArgsArray
     *
     * @return bool True if valid
     */
    private function validateFindArgs($findArgsArray) {
        $areAllArgumentsStrings = true;

        $lambdaIsString = function($variable) {
            return is_string($variable);
        };

        $findArgsArrayCopy = $findArgsArray;

        $isStringArray = array_map($lambdaIsString, $findArgsArrayCopy);

        foreach($isStringArray as $boolVal) {
            if(false == $boolVal) {
                $areAllArgumentsStrings = false;
                break;
            }
        }

        return $areAllArgumentsStrings;
    }

    /**
     * Deletes all query related data
     * Useful when you want to re-use the same PatomicQuery object
     */
    private function clear() {
        $this->findEdn  = array();
        $this->whereEdn = array();
        $this->inEdn    = array();
        $this->argsEdn  = array();

        $rawQueryBody   = null;
        $rawQueryArgs   = null;
        $queryBody      = null;
        $queryArgs      = null;
    }
}

try {
    $pq = new PatomicQuery();
    $pq->find("communityName")
        ->where(array("communityName" => "community/name"))
        ->where(array("communityPopulation" => ""));
} catch(PatomicException $e) {
    echo $e . PHP_EOL;
}
