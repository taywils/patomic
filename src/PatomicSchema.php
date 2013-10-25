<?php

require_once "../vendor/autoload.php";
require_once "PatomicException.php";

/**
 * PatomicSchema is a PHP object representation of a Datomic schema.
 */
class PatomicSchema
{
    private $name;
    private $namespace;
    private $identity;
    private $valueType;
    private $schema;
    private $schemaDef = array(
        "db" => array(
            "ident",
            "cardinality" => array("one", "many"),
            "valueType" => array(
                "keyword",
                "string",
                "boolean",
                "long",
                "bigint",
                "float",
                "double",
                "bigdec",
                "ref",
                "instant",
                "uuid",
                "uri",
                "bytes",
            ),
            "doc",
            "unique" => array("value", "identity"),
            "index",
            "fulltext",
            "isComponent",
            "noHistory"
        )
    );

    /**
     * Creates the :db/id for a new Datomic attribute as a part of a schema
     *
     * @return PatomicSchema A new Datomic attribute with the id, ident and valueType datoms defined
     */
    public function __construct() {
        $this->schema = $this->_map();

        $idTag = $this->_tag("db/id");
        $dbPart = $this->_vector(array($this->_keyword("db.part/db")));
        $idTagged = $this->_tagged($idTag, $dbPart);
        $this->schema[$this->_keyword("db/id")] = $idTagged;
    }

    /**
     * @override
     * @return String
     */
    public function __toString() {
        return $this->_encode($this->schema);
    }

    /**
     * Appends a new datom into the current attribute, this is a generalized method
     * <br />
     * when possible please use the explicit setter methods for adding datoms
     * <br />
     * Require at minimum two arguments; the attribute and the value(s)
     * @throws PatomicException
     */
    public function datom() {
        $argc = func_num_args();
        if($argc < 2) {
            throw new PatomicException(__METHOD__ . " requires at least two arguments");
        }
        $argv = func_get_args();
        $attribute = $argv[0];
        $value = $argv[1];
        $this->schema[$this->_keyword($attribute)] = $value;

        return $this;
    }

    public function ident($name, $namespace = null, $identity = null) {
            $ident = (is_null($namespace) || !is_string($namespace)) ? $name : $namespace . "." . $name;

            if(is_null($identity) || !is_string($identity)) {
                    $ident .= "/" . $this->identity;
            } else {
                    $ident .= "/" . $identity;
            }

            $this->schema[$this->_keyword("db/ident")] = $this->_keyword($ident);

            return $this;
    }

    public function cardinality($cardinal) {
            $cardinal = strtolower($cardinal);
            if(!array_search($cardinal, $this->schemaDef['db']['cardinality'])) {
                    throw new PatomicException("Cardinality must be \"one\" or \"many\"");
            } else {
                    $this->schema[$this->_keyword("db/cardinality")] = $this->_keyword("db/cardinality/" . $cardinal);

                    return $this;
            }
    }

    public function valueType($valueType) {
            if(!array_search($valueType, $this->schemaDef['db']['valueType'])) {
                    throw new PatomicException("ValueType unknown");
            } else {
                    $this->schema[$this->_keyword("db/valueType")] = $this->_keyword("db.type/" . $valueType);

                    return $this;
            }
    }

    public function doc($doc) {
            if(!is_string($doc)) {
                    throw new PatomicException("Doc must be a string");
            } else {
                    $this->schema[$this->_keyword("db/doc")] = $doc;

                    return $this;
            }
    }

    public function prettyPrint() {
            $iter = $this->schema->getIterator();
            $idx = 0;
            $max = count($iter);
            
            echo "{"; 
            foreach($iter as $vals) {
                    //print_r($vals);
                    if($idx == 0) {
                            echo $vals[0]->value . PHP_EOL;
                    } elseif($max - 1 == $idx) {
                            echo " " . $vals[0]->value . "}";
                    } else {
                            echo " " . $vals[0]->value . " :" . $vals[1]->value . PHP_EOL;
                    }
                    $idx++;
            }
            echo PHP_EOL;
    }

    private function _keyword($k) {
            return \igorw\edn\keyword($k);
    }
    private function _symbol($s) {
            return \igorw\edn\symbol($s);
    }
    private function _map($a = null) {
            $map = new \igorw\edn\Map();

            if(isset($a) && is_array($a)) {
                    $map->init($a);
            }

            return $map;
    }
    private function _vector($a) {
            return new \igorw\edn\Vector($a);
    }
    private function _tag($t) {
            return new \igorw\edn\tag($t);
    }
    private function _tagged($t, $v) {
            return new \igorw\edn\Tagged($t, $v);
    }
    private function _encode($edn) {
            return \igorw\edn\encode($edn);
    }
}

$test2 = new PatomicSchema();
$test2->ident("ffl", null, "statistics")
        ->valueType("ref")
        ->cardinality("many")
        ->doc("The player's collection of game statistics");
$test2->prettyPrint();
