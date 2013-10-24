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
     * Creates the :db/id, :db/ident and :db/valueType for a new Datomic attribute
     * as a part of a schema
     *
     * @param string $name
     * @param string identity
     * @param string $namespace
     * @param string $valuetype
     *
     * @return PatomicSchema A new Datomic attribute with the id, ident and valueType datoms defined
     */
    public function __construct($name, $namespace, $identity, $valueType) {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->identity = $identity;
        $this->valueType = $valueType;

        $this->schema = $this->_map();

        $idTag = $this->_tag("db/id");
        $dbPart = $this->_vector(array($this->_keyword("db.part/db")));
        $idTagged = $this->_tagged($idTag, $dbPart);
        echo $this->_encode($dbPart).PHP_EOL;
        $this->schema[$this->_keyword("db/id")] = $idTagged;

        $ident = (is_null($namespace) || '' == $namespace) ? $name : $namespace . "." . $name;
        $ident .= "/" . $identity;
        $this->schema[$this->_keyword("db/ident")] = $this->_keyword($ident);

        $this->schema[$this->_keyword("db/valueType")] = $this->_keyword("db.type/" . $valueType);
    }

    /**
     * @override
     * @return String
     */
    public function __toString() {
        return $this->_encode($this->schema);
    }


    /**
     * Appends a new datom into the current attribute
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
        $this->schema[$this->_keyword($attribute)] = $this->_keyword($value);
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

$test = new PatomicSchema("league", "ffl", "name", "string");
echo $test.PHP_EOL;
