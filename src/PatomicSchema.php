<?php
require_once("temploader.php");
require_once("PatomicException.php");

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
                if(!array_searchRecursive($valueType, $this->schemaDef)) { 
                        throw new PatomicException("Invalid schema valueType, the valueType must be " 
                                . print_r($this->schemaDef["db"]["valueType"], true));
                }
                $this->name = $name;
                $this->valueType = $valueType;
                $this->schema = new \igorw\edn\Map();
        }

        public function field() {
        }

        private function array_searchRecursive( $needle, $haystack, $strict=false, $path=array() )
        {
                if( !is_array($haystack) ) {
                        return false;
                }

                foreach( $haystack as $key => $val ) {
                        if( is_array($val) && $subPath = array_searchRecursive($needle, $val, $strict, $path) ) {
                                $path = array_merge($path, array($key), $subPath);
                                return $path;
                        } elseif( (!$strict && $val == $needle) || ($strict && $val === $needle) ) {
                                $path[] = $key;
                                return $path;
                        }
                }
                return false;
        }
}
