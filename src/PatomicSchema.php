<?php
require_once("temploader.php");
require_once("PatomicException.php");

/**
 * PatomicSchema is a PHP object representation of a Datomic schema.
 */
class PatomicSchema
{
        private $name;
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
                        "noHistory",
                )
        );

        public function __construct($name, $valueType) {
                if(!in_array($valueType, $this->schemaDef['db']['valueType'])) { 
                        throw new PatomicException("Invalid schema valueType, the valueType must be " 
                                . print_r($this->schemaDef["db"]["valueType"], true));
                }
                if(!is_string($name)) {
                        throw new PatomicException(__METHOD__ . " expects \$name to be a string");
                }
                $this->name = $name;
                $this->valueType = $valueType;
                $this->schema = new \igorw\edn\Map();
        }

        public function field() {
                $argc = func_num_args();
                if($argc < 2) {
                        throw new PatomicException(__METHOD__ . " requires at least two arguments");
                }
                $argv = func_get_args();
                $symbol = $argv[0];
        }
}
