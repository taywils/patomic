<?php

namespace taywils\Patomic;

/**
 * Class designed to assist building Datomic Queries
 * Supports both the writing of raw Datalog style queries and more PHP friendly style queries
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

    private $reflection;

    use TraitEdn;

    public function __construct() {
        $this->reflection = new \ReflectionClass($this);
    }

    /**
     * Allows one to write pure Datalog queries
     *
     * @param string $datalogString A string consisting of valid Datalog
     *
     * @return $this
     *
     * @throws PatomicException
     */
    public function newRawQuery($datalogString) {
        $this->rawQueryBody = "";

        if(!isset($datalogString) || !is_string($datalogString) || empty($datalogString)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " expects a non-empty string input");
        }

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
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " create a newRawQuery before adding raw query arguments");
        }

        if(!is_string($datalogString) || empty($datalogString)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " expects a non-empty string argument");
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
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " expects at least one \"string\" as an argument");
        }

        $argsArray = func_get_args();

        if(false == $this->validateFindArgs($argsArray)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " encountered a non string argument");
        }

        foreach($argsArray as $arg) {
            $this->findEdn[] = trim($arg);
        }

        return $this;
    }

    public function in() {
        $numargs = func_num_args();

        if($numargs > 2 || $numargs < 1) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " expects at least one \"string\" and an optional \"array\" as arguments");
        }

        $argsArray = func_get_args();

        if(1 == $numargs && !is_string($argsArray[0])) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " first argument was not a string");
        }

        if(2 == $numargs && !is_array($argsArray[1])) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " second argument was not an array");
        }

        $validationResult = (2 == $numargs) ? $this->validateInArgs($numargs, $argsArray) : true;
        if(is_string($validationResult)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . $validationResult);
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
    public function where($argArray = null) {
        if(!isset($argArray) || !is_array($argArray)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " expects an array as an argument");
        }

        $this->whereEdn[] = $argArray;

        return $this;
    }

    public function arg($argArray) {
        if(!isset($argArray) || !is_array($argArray)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " expects an array as an argument");
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

        $parsedQueryBody = $this->_parse($this->queryBody);
        $encodedQueryBody = $this->_encode($parsedQueryBody);

        return $encodedQueryBody;
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

    /**
     * Returns the array of pattern variables used within the :find clause
     * @return array
     */
    public function getFindEdn() {
        return $this->findEdn;
    }

    private function limitOrOffset($value, $useLimit) {
        if(!isset($value) || !is_int($value) || ($value < 1)) {
            throw new PatomicException($this->reflection->getShortName() . "::" . __FUNCTION__ . " expects a positive integer as an argument");
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

        return $validateArray();
    }

    private function createFindEdn() {
        $findDatalog = "[:find ";

        foreach($this->findEdn as $findPart) {
            $findDatalog .= "?" . $findPart . " ";
        }

        $this->queryBody .= $findDatalog;
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

                if(is_int($key)) {
                    $questionMark = (is_int($value)) ? "" : "?";
                    $whereDatalog .=  $questionMark . $value . " ";
                } else {
                    $whereDatalog .= "?" . $key . " :" . $value . " ";
                }

            }

            $whereDatalog .= "]";
        }

        $this->queryBody .= $whereDatalog . "]";
    }

    private function createQueryBody() {
        //HACK: If findEdn, inEdn and whereEdn are all empty arrays
        if(empty($this->findEdn) && empty($this->inEdn) && empty($this->whereEdn)) {
            $this->queryBody = "[]";
        } else {
            $this->createFindEdn();
            $this->createInEdn();
            $this->createWhereEdn();
        }
    }

    private function createQueryArgs() {
        $argDatalog = "[";

        foreach($this->argsEdn as $argArray) {
            $argDatalog .= "[";

            $argArrayIdx    = 0;
            $argArraySize   = count($argArray);
            foreach($argArray as $key => $value) {
                $whitespace = ($argArraySize - 1 == $argArrayIdx++) ? "" : " ";
                if(is_int($key)) {
                    $argDatalog .= ":" . $value . $whitespace;
                } else {
                    $argDatalog .= ":" . $key . " \"" . $value . "\"" . $whitespace;
                }
            }

            $argDatalog .= "]";
        }

        $argDatalog .= "]";

        $this->queryArgs = $argDatalog;
    }
}
