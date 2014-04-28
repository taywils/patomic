<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class designed to assist building Datomic Queries
 * Supports both the writing of raw Datalog style queries and more PHP friendly style queries
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
    private $queryLimit     = 0;
    private $queryOffset    = 0;
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
     * Add a datalog string to the current rawQuery
     *
     * @param string $datalogString A string consisting of valid datalog
     * @return $this
     * @throws PatomicException
     */
    public function addRawQueryArgs($datalogString) {
        $this->rawQueryArgs = "";

        if(!isset($this->rawQueryBody) || empty($this->rawQueryBody)) {
            throw new PatomicException("Create a newRawQuery before adding raw query arguments");
        }

        foreach($this->_parse($datalogString) as $argumentPart) {
            $this->rawQueryArgs .= $this->_encode($argumentPart);
        }

        return $this;
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
            $this->findEdn[] = trim($arg);
        }

        return $this;
    }

    public function in() {
        $numargs = func_num_args();

        if($numargs > 2 || $numargs < 1) {
            throw new PatomicException(__CLASS__ . "::" . __FUNCTION__  . " expects at least one \"string\" and an optional \"array\" as arguments");
        }

        $argsArray = func_get_args();

        if(1 == $numargs && !is_string($argsArray[0])) {
            throw new PatomicException(__CLASS__ . "::" . __FUNCTION__  . " first argument was not a string");
        }

        if(2 == $numargs && !is_array($argsArray[1])) {
            throw new PatomicException(__CLASS__ . "::" . __FUNCTION__  . " second argument was not an array");
        }

        if(is_string($this->validateInArgs($numargs, $argsArray))) {
            throw new PatomicException(__CLASS__ . "::" . __FUNCTION__  . $this->validateInArgs($numargs, $argsArray));
        }

        $parts = preg_split("/[\s,]+/", $argsArray[0]);
        foreach($parts as $part) {
            if(strlen($part) > 0) {
                $this->inEdn[] = $part;
            } else {
                continue;
            }
        }

        if(2 == $numargs) { // Handle the case where a binding collection is passed as an array argument
            $this->inEdn[] = $argsArray[1];
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

        $this->whereEdn[] = $argArray;

        return $this;
    }

    public function arg($argArray) {
        if(!isset($argArray) || !is_array($argArray)) {
            throw new PatomicException(__CLASS__ . "::" . __FUNCTION__  . " expects an array as an argument");
        }

        $this->argsEdn[] = $argArray;

        return $this;
    }

    public function limit($limit) {
        $this->limitOrOffset($limit, true);
    }

    public function offset($offset) {
        $this->limitOrOffset($offset, false);
    }

    public function getOffset() {
        return $this->queryOffset;
    }

    public function getLimit() {
        return $this->queryLimit;
    }

    public function getRawQuery() {
        return $this->rawQueryBody;
    }

    public function getRawQueryArgs() {
        return $this->rawQueryArgs;
    }

    public function getQuery() {
        $this->createQueryBody();

        return $this->queryBody;
    }

    public function getQueryArgs() {
        $this->createQueryArgs();

        return $this->queryArgs;
    }

    /**
     * Deletes all query related data
     * Useful when you want to re-use the same PatomicQuery object
     */
    public function clear() {
        $this->findEdn  = array();
        $this->inEdn    = array();
        $this->whereEdn = array();
        $this->argsEdn  = array();

        $this->rawQueryBody   = null;
        $this->rawQueryArgs   = null;
        $this->queryBody      = null;
        $this->queryArgs      = null;
        $this->queryLimit     = 0;
        $this->queryOffset    = 0;
    }

    private function limitOrOffset($value, $useLimit) {
        if(!isset($value) || !is_int($value) || ($value < 1)) {
            throw new PatomicException(__CLASS__ . "::" . __FUNCTION__  . " expects a positive integer as an argument");
        }

        if($useLimit) {
            $this->queryLimit = $value;
        } else {
            $this->queryOffset = $value;
        }

        return $this;
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

    private function validateInArgs($numArgs, $argsArray) {
        $validateString = function() use($numArgs, $argsArray) {
            $stringIsJustWhitespace = (strlen(trim($argsArray[0]))) == 0;

            if(1 == $numArgs && $stringIsJustWhitespace) {
                return " expects a non-empty string when no array is given";
            } else if(2 == $numArgs) {
                return true;
            } else {
                return " expects a non-empty string when no array is given";
            }
        };

        $validateArray = function() use($numArgs, $argsArray) {
            $allElementsAreString = true;

            foreach($argsArray[1] as $elem) {
                $allElementsAreString = is_string($elem);
                if(false == $allElementsAreString) {
                    break;
                }
            }

            if($allElementsAreString) {
                return true;
            } else {
                return " expects an array containing only string elements";
            }
        };

        switch($numArgs) {
            case 1:
                return $validateString;
                break;

            case 2:
                return $validateString && $validateArray;
                break;

            default:
                return false;
        }
    }

    private function createFindEdn() {
        $findDatalog = "[:find ";

        foreach($this->findEdn as $findPart) {
            $findDatalog .= "?" . $findPart . " ";
        }

        $this->queryBody .= $findDatalog;

        //echo $this->queryBody . PHP_EOL;
    }

    private function createInEdn() {
        $inDatalog = ":in $ ";

        foreach($this->inEdn as $stringOrArray) {
            if(is_string($stringOrArray)) {
                $inDatalog .= "?" . $stringOrArray . " ";
                continue;
            } else {
                $inDatalog .= "[[";

                for($index = 0, $size = count($stringOrArray); $index < $size; $index++ ) {
                    $whitespace = ($index == $size - 1) ? "" : " ";
                    $inDatalog .= "?" . $stringOrArray[$index] . $whitespace;
                }

                $inDatalog .= "]] ";
            }

        }
        $this->queryBody .= $inDatalog;
    }

    private function createWhereEdn() {
        $whereDatalog = ":where ";

        foreach($this->whereEdn as $whereArray) {
            $whereDatalog .= "[";

            foreach($whereArray as $key => $value) {

                if(is_int($key) && !is_string($key)) {
                    $whereDatalog .=  "?" . $value . " ";
                } else {
                    $whereDatalog .= "?" . $key . " :" . $value . " ";
                }

            }

            $whereDatalog .= "]";
        }

        $this->queryBody .= $whereDatalog . "]";

        echo $this->queryBody . PHP_EOL;
    }

    private function createQueryBody() {
        $this->createFindEdn();
        $this->createInEdn();
        $this->createWhereEdn();
    }

    private function createQueryArgs() {
        $argDatalog = "[";

        foreach($this->argsEdn as $argArray) {
            $argDatalog .= "{";

            foreach($argArray as $key => $value) {
                $argDatalog .= ":" . $key . " \"" . $value . "\"";
            }

            $argDatalog .= "}";
        }

        $argDatalog .= "]";

        echo $argDatalog . PHP_EOL;
        $this->queryArgs = $argDatalog;
    }
}

try {
    $pq = new PatomicQuery();
    $pq->find("e", "v")
        ->in("communityName, communityType")
        ->where(array("e" => "db/doc", "v"))
        ->arg(array("db/alias" => "demo/energy"));

    $pq->getQuery();
    $pq->getQueryArgs();
} catch (PatomicException $e) {
    echo $e . PHP_EOL;
}
