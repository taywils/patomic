<?php

require_once "../vendor/autoload.php";
require_once "PatomicException.php";
require_once "TraitEdn.php";

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

    use TraitEdn;

    /**
     * Creates the :db/id for a new Datomic attribute as a part of a schema
     *
     * @return PatomicSchema A new Datomic attribute with the id set
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

    public function ident($name, $namespace, $identity) {
        $this->name         = $name;
        $this->namespace    = $namespace;
        $this->identity     = $identity;

        $ident = $namespace . $name . "/" . $identity;

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
            $this->valueType = $valueType;
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
            if($idx == 0) {
                echo $vals[0]->value . PHP_EOL;
            } elseif($max - 1 == $idx) {
                echo " " . $vals[0]->value . " " . $this->printHandler($vals) . "}";
            } else {
                echo " " . $vals[0]->value . $this->printHandler($vals) . PHP_EOL;
            }
            $idx++;
        }
        echo PHP_EOL;
    }

    public function printHandler(&$vals) {
        if(is_string($vals[1]) && gettype($vals[1]) != "object") {
            return $vals[1];
        }

        switch(gettype($vals[1])) {
            default:
                return " :" . $vals[1]->value;
        }
    }
}

$test2 = new PatomicSchema();
$test2->ident("ffl", null, "statistics")
    ->valueType("ref")
    ->cardinality("many")
    ->doc("The player's collection of game statistics");
$test2->prettyPrint();
