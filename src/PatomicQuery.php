<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class designed to assist building Datomic Queries
 * Supports both the writing of raw Datalog style queries and more PHP friendly ORM style queries
 *
 * Many thanks to the authors of Diametric the Datomic Active Record wrapper for the Ruby programming language
 * for inspiration
 *
 * @see https://github.com/relevance/diametric
 */
class PatomicQuery
{
    private $rawQueryBody;
    private $rawQueryArgs;

    use TraitEdn;

    public function __construct() {
        $this->rawQueryArgs = null;
        $this->rawQueryBody = null;
    }

    public function newRawQuery($datalogString) {
        $this->rawQueryBody = "";

        foreach($this->_parse($datalogString) as $queryPart) {
            $this->rawQueryBody .= $this->_encode($queryPart);
        }

        return $this;
    }

    public function addRawQueryArgs($datalogString) {
        if("" == $this->rawQueryBody) {
            throw new PatomicException("Create a newRawQuery before adding raw query arguments");
        }

        foreach($this->_parse($datalogString) as $argumentPart) {
            $this->rawQueryArgs .= $this->_encode($argumentPart);
        }
    }

    public function getQuery() {
        return $this->rawQueryBody;
    }

    public function getQueryArgs() {
        return $this->rawQueryArgs;
    }
}