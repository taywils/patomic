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
     * @param string $namespace
     * @param string $identity
     * @param string $valuetype
     *
     * @return A new Datomic attribute with the id, ident and valueType datoms defined
     */
    public function __construct($name, $namespace, $identity, $valueType) {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->identity = $identity;
        $this->valueType = $valueType;
        $this->schema = $this->_vector();

        $attr = $this->_map();
        //$attr[$this->_keyword("db/id")] = $this->_symbol("db/id"
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
        //$this->schema[\igorw\edn\keyword($attribute)
    }

    // In case I ever need to swap out the EDN datastructures
    private function _keyword($k) {
            return \igorw\edn\keyword($k);
    }
    private function _symbol($s) {
            return \igorw\edn\symbol($s);
    }
    private function _map() {
            return new \igorw\edn\Map();
    }
    private function _vector() {
            return new \igorw\edn\Vector();
    }
}

//Just testing... this will be deleted soon
$map = new \igorw\edn\Map();
$vec = new \igorw\edn\Vector(array(\igorw\edn\keyword("db.part/db")));
$tag = new \igorw\edn\Tag("db/id");
$tagged = new \igorw\edn\Tagged($tag, $vec);
$map[\igorw\edn\keyword('db/id')] = $tagged;
$map[\igorw\edn\keyword('db/doc')] = 'Too busy testing';
$map[\igorw\edn\keyword('db/cardinality')] = \igorw\edn\keyword('db.cardinality/one');
echo \igorw\edn\encode($map).PHP_EOL;
