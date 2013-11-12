<?php

require_once "../vendor/autoload.php";
require_once "PatomicException.php";
require_once "TraitEdn.php";

/**
 * PatomicSchema is a PHP object representation of a Datomic schema.
 * @see http://docs.datomic.com/schema.html
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
                "bigdec",
                "bigint",
                "boolean",
                "bytes",
                "double",
                "float",
                "instant",
                "keyword",
                "long",
                "ref",
                "string",
                "uuid",
                "uri"
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
     * @return string
     */
    public function __toString() {
        return $this->_encode($this->schema);
    }

    /**
     * Set the required "ident" Datom
     *
     * @param string $namespace
     * @param string $name
     * @param string $identity
     * @return $this
     */
    public function ident($namespace, $name, $identity) {
        $this->name         = $name;
        $this->namespace    = $namespace;
        $this->identity     = $identity;

        $ident = $namespace . "." . $name . "/" . $identity;

        $this->schema[$this->_keyword("db/ident")] = $this->_keyword($ident);

        return $this;
    }

    /**
     * Set the required "cardinality" Datom
     *
     * @param string $cardinal A valid Datomic cardinality
     * @return $this
     * @throws PatomicException
     */
    public function cardinality($cardinal) {
        $cardinal = strtolower($cardinal);

        if(array_search($cardinal, $this->schemaDef['db']['cardinality']) < 0) {
            throw new PatomicException("Cardinality must be \"one\" or \"many\"");
        } else {
            $this->schema[$this->_keyword("db/cardinality")] = $this->_keyword("db.cardinality/" . $cardinal);

            return $this;
        }
    }

    /**
     * Set the required "type" Datom
     *
     * @param string $valueType A valid Datomic valueType
     * @return $this
     * @throws PatomicException
     */
    public function valueType($valueType) {
        $valueType = strtolower($valueType);

        if(array_search($valueType, $this->schemaDef['db']['valueType']) < 0) {
            $debugInfo = PHP_EOL . "[" . implode(", ", $this->schemaDef['db']['valueType']) . "]";
            throw new PatomicException("Invalid ValueType assigned try one of the following instead" . $debugInfo);
        } else {
            $this->valueType = $valueType;
            $this->schema[$this->_keyword("db/valueType")] = $this->_keyword("db.type/" . $valueType);

            return $this;
        }
    }

    /**
     * Set the optional "doc" Datom
     *
     * @param string $doc Documentation description
     * @return $this
     * @throws PatomicException
     */
    public function doc($doc) {
        if(!is_string($doc)) {
            throw new PatomicException("Doc must be a string");
        } else {
            $this->schema[$this->_keyword("db/doc")] = $doc;

            return $this;
        }
    }

    /**
     * Set the optional "unique" Datom
     * Implies :db/index
     *
     * @param string $unique
     * @return $this
     * @throws PatomicException
     */
    public function unique($unique) {
        $unique = strtolower($unique);

        if(array_search($unique, $this->schemaDef['db']['unique']) < 0) {
            $debugInfo = implode(", ", $this->schemaDef['db']['unique']);
            throw new PatomicException("unique must be xdone of the following [" . $debugInfo . "]");
        } else {
            $this->schema[$this->_keyword("db/unique")] = $this->_keyword("db.unique/" . $unique);

            if(!$this->schema->offsetExists($this->_keyword("db/index"))) {
                $this->index(true);
            }

            return $this;
        }
    }

    /**
     * Set the optional "index" Datom
     *
     * @param boolean $index
     * @return $this
     */
    public function index($index) {
        $index = !is_bool($index) ? false : $index;
        $this->schema[$this->_keyword("db/index")] = $index;
        return $this;
    }

    /**
     * Set the optional "fulltext" Datom
     *
     * @param boolean $fullText
     * @return $this
     */
    public function fullText($fullText) {
        $fullText = !is_bool($fullText) ? false : $fullText;
        $this->schema[$this->_keyword("db/fulltext")] = $fullText;
        return $this;
    }

    /**
     * Set the optional "isComponent" Datom
     *
     * @param boolean $component
     * @return $this
     */
    public function isComponent($component) {
        $component = !is_bool($component) ? false : $component;
        $this->schema[$this->_keyword("db/isComponent")] = $component;
        return $this;
    }

    /**
     * Set the optional "noHistory" Datom
     * @param boolean $history
     * @return $this
     */
    public function noHistory($history) {
        $history = !is_bool($history) ? false : $history;
        $this->schema[$this->_keyword("db/noHistory")] = $history;
        return $this;
    }


    /**
     * Set the optional "install" Datom
     *
     * @return $this
     */
    public function install() {
        if(!$this->schema->offsetExists($this->_keyword("db.install/_attribute"))) {
            $this->schema[$this->_keyword("db.install/_attribute")] = $this->_keyword("db.part/db");
        }
        return $this;
    }

    /**
     * Prints out a line by line dump of the current Schema
     * The style for displaying Datomic schemas was borrowed from the official documentation
     */
    public function prettyPrint() {
        $iter = $this->schema->getIterator();
        $idx = 0;
        $max = count($iter);

        echo "{";
        foreach($iter as $vals) {
            if($idx == 0) { // First line of printed Schema, will always be set to the :db/id
                echo ":" . $vals[0]->value . $this->printHandler($vals). PHP_EOL;
            } elseif($max - 1 == $idx) { // Last line of printed Schema
                echo " :" . $vals[0]->value . $this->printHandler($vals) . "}";
            } else {
                echo " :" . $vals[0]->value . $this->printHandler($vals) . PHP_EOL;
            }
            $idx++;
        }
        echo PHP_EOL;
    }

    /**
     * Generates pretty string output for various EDN data structures
     *
     * @param mixed $vals
     * @return string Output of pretty print
     */
    protected function printHandler(&$vals) {
        // Handle PHP primitives
        if("object" != gettype($vals[1])) {
            switch(gettype($vals[1])) {
                case 'boolean':
                    return " " . var_export($vals[1], true);

                case 'string':
                    return " " . $vals[1];

                default:
                    break;
            }
        }

        $output = " "; // Whitespace for aesthetic purposes

        // Print handlers for each EDN type may be placed here
        switch(get_class($vals[1])) {
            case 'igorw\edn\Tagged':
                $output .= "#" . $vals[1]->tag->name . "[";

                if(get_class($vals[1]->value) == 'igorw\edn\Vector') {
                    foreach($vals[1]->value->data as $vectorElem) {
                        $output .= ":" . $vectorElem->value;
                    }

                    $output .= "]";
                }
                break;

            default:
                return " :" . $vals[1]->value;
        }
        return $output;
    }
}

$test2 = new PatomicSchema();

$test2->ident("taywils", "script", "name")
    ->valueType("uUid")
    ->cardinality("one")
    ->unique("value")
    ->isComponent(false)
    ->noHistory(true)
    ->doc("The name of the script")
    ->install();

$test2->prettyPrint();
