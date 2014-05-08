<?php

/**
 * PHPUnit tests for PatomicEntity
 */
class PatomicEntityTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers PatomicEntity::__construct
     */
    public function testConstructor() {
        /* Default constructor */
        $pe = new PatomicEntity();
        $expectedString = "{:db/id #db/id [:db.part/db]}";

        $this->assertEquals($expectedString, sprintf($pe));
    }

    /**
     * @covers PatomicEntity::toString()
     */
    public function testToString() {
        $pe = new PatomicEntity("db");
        $expectedString = "{:db/id #db/id [:db.part/db]}";

        $this->assertEquals($expectedString, sprintf($pe));
    }

    /**
     * @covers PatomicEntity::ident
     */
    public function testIdent() {
        /* ident() no namespace */
        $pe1 = new PatomicEntity("db");
        $pe1->ident("community", "name");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/ident :community/name}";
        $this->assertEquals($expectedString, sprintf($pe1));

        /* ident() with namespace */
        $pe2 = new PatomicEntity("db");
        $pe2->ident("community", "name", "taywils");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/ident :taywils.community/name}";
        $this->assertEquals($expectedString, sprintf($pe2));

        /* A PatomicEntity can only have a single :ident attribute */
        $pe1->ident("community", "url");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/ident :community/url}";
        $this->assertEquals($expectedString, sprintf($pe1));

        /* ident() should only take string arguments */
        try {
            $pe3 = new PatomicEntity("db");
            $pe3->ident("community", 1234);
        } catch(PatomicException $e) {
            $this->assertEquals("PatomicEntity::ident \$identity argument should be a non-empty string", $e->getMessage());
        }
        try {
            $pe4 = new PatomicEntity("db");
            $pe4->ident(array(), "location");
        } catch(PatomicException $e) {
            $this->assertEquals("PatomicEntity::ident \$name argument should be a non-empty string", $e->getMessage());
        }
    }

    /**
     * @covers Patomic::cardinality
     */
    public function testCardinality() {
        /* Set cardinality to "one" */
        $pe = new PatomicEntity();
        $pe->ident("community", "name")
            ->cardinality("one");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/ident :community/name :db/cardinality :db.cardinality/one}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* Set cardinality to "many" */
        $pe->cardinality("many");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/ident :community/name :db/cardinality :db.cardinality/many}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* Cardinality string input should be case insensitive */
        $pe->cardinality("MaNy");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/ident :community/name :db/cardinality :db.cardinality/many}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* non string input should throw an exception */
        try {
            $pe->cardinality();
        } catch(PatomicException $e) {
            $this->assertEquals("argument must be a non-empty string", $e->getMessage());
        }
        try {
            $pe->cardinality(array(1 => "a"));
        } catch(PatomicException $e) {
            $this->assertEquals("argument must be a non-empty string", $e->getMessage());
        }

        /* If the argument is a string it must be a valid cardinality */
        try {
            $pe->cardinality("single");
        } catch(PatomicException $e) {
            $this->assertEquals("Cardinality must be \"one\" or \"many\"", $e->getMessage());
        }
    }

    /**
     * @covers PatomicEntity::valueType
     */
    public function testValueType() {
        /* Only accept valueType listed on http://docs.datomic.com/schema.html */
        $validValueTypes = array(
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
        );
        foreach($validValueTypes as $valueType) {
            $pe = new PatomicEntity();
            $pe->valueType($valueType);
            $expectedString = "{:db/id #db/id [:db.part/db] :db/valueType :db.type/" . $valueType . "}";
            $this->assertEquals($expectedString, sprintf($pe));
        }

        /* ValueType argument should be case insensitive */
        foreach($validValueTypes as $valueType) {
            $pe = new PatomicEntity();
            $pe->valueType(strtoupper($valueType));
            $expectedString = "{:db/id #db/id [:db.part/db] :db/valueType :db.type/" . $valueType . "}";
            $this->assertEquals($expectedString, sprintf($pe));
        }

        /* non-string arguments should throw exceptions */
        try {
            $pe = new PatomicEntity();
            $pe->valueType(132);
        } catch(PatomicException $e) {
            $this->assertEquals("PatomicEntity::valueType expects a non-empty string argument", $e->getMessage());
        }
        try {
            $pe = new PatomicEntity();
            $pe->valueType();
        } catch(PatomicException $e) {
            $this->assertEquals("PatomicEntity::valueType expects a non-empty string argument", $e->getMessage());
        }

        /* Should throw exception when given a valid string that is not a Datomic valueType */
        try {
            $pe = new PatomicEntity();
            $pe->valueType("datetime");
        } catch(PatomicException $e) {
            $debugInfo = PHP_EOL . "[" . implode(", ", $validValueTypes) . "]";
            $this->assertEquals("PatomicEntity::valueType invalid ValueType assigned try one of the following instead" . $debugInfo, $e->getMessage());
        }
    }

    /**
     * @covers PatomicEntity::doc
     */
    public function testDoc() {
        /* valid doc() argument is a string */
        $pe = new PatomicEntity();
        $pe->doc("A community's name");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/doc \"A community's name\"}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* non-string doc() argument should throw exception */
        try {
            $pe = new PatomicEntity();
            $pe->doc();
        } catch(PatomicException $e) {
            $expectedString = "PatomicEntity::doc argument must be a string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* PatomicEntity object should have only a single doc attribute */
        $pe->doc("A community's population");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/doc \"A community's population\"}";
        $this->assertEquals($expectedString, sprintf($pe));
    }

    /**
     * @covers PatomicEntity::unique
     */
    public function testUnique() {
        /* valid unique() input is a string that is one of the valid unique types */
        $pe = new PatomicEntity();
        $pe->unique("value");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/unique :db.unique/value}";
        $this->assertEquals($expectedString, sprintf($pe));
        $pe->unique("identity");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/unique :db.unique/identity}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* unique attribute should be case-insensitive */
        $pe->unique("Identity");
        $expectedString = "{:db/id #db/id [:db.part/db] :db/unique :db.unique/identity}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* non string argument should throw an exception */
        try {
            $pe->unique();
        } catch(PatomicException $e) {
            $expectedString = "PatomicEntity::unique expects a non-empty string argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }
        try {
            $pe->unique(414);
        } catch(PatomicException $e) {
            $expectedString = "PatomicEntity::unique expects a non-empty string argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }
        try {
            $pe->unique(array());
        } catch(PatomicException $e) {
            $expectedString = "PatomicEntity::unique expects a non-empty string argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* string argument */
        try {
            $pe->unique("limit");
        } catch(PatomicException $e) {
            $expectedString = "PatomicEntity::unique string argument must be one of the following [value, identity]";
            $this->assertEquals($expectedString, $e->getMessage());
        }
    }

    /**
     * @covers PatomicEntity::index
     */
    public function testIndex() {
        /* index only accepts a boolean value */
        $pe = new PatomicEntity();
        $pe->index(true);
        $expectedString = "{:db/id #db/id [:db.part/db] :db/index true}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* non-boolean values should default index to false */
        $pe2 = new PatomicEntity();
        $pe2->index();
        $expectedString = "{:db/id #db/id [:db.part/db] :db/index false}";
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->index(1);
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->index("true");
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->index(array("foo" => "bar"));
        $this->assertEquals($expectedString, sprintf($pe2));

        /* PatomicEntity can only have one index attribute */
        $pe->index(false);
        $expectedString = "{:db/id #db/id [:db.part/db] :db/index false}";
        $this->assertEquals($expectedString, sprintf($pe));
    }

    /**
     * @covers PatomicEntity::fullText
     */
    public function testFullText() {
        /* fullText only accepts a boolean value */
        $pe = new PatomicEntity();
        $pe->fullText(true);
        $expectedString = "{:db/id #db/id [:db.part/db] :db/fulltext true}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* non-boolean values should default fulltext to false */
        $pe2 = new PatomicEntity();
        $pe2->fullText();
        $expectedString = "{:db/id #db/id [:db.part/db] :db/fulltext false}";
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->fullText(1);
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->fullText("true");
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->fullText(array("foo" => "bar"));
        $this->assertEquals($expectedString, sprintf($pe2));

        /* PatomicEntity can only have one fulltext attribute */
        $pe->fullText(false);
        $expectedString = "{:db/id #db/id [:db.part/db] :db/fulltext false}";
        $this->assertEquals($expectedString, sprintf($pe));
    }

    /**
     * @covers PatomicEntity::isComponent
     */
    public function testIsComponent() {
        /* isComponent only accepts a boolean value */
        $pe = new PatomicEntity();
        $pe->isComponent(true);
        $expectedString = "{:db/id #db/id [:db.part/db] :db/isComponent true}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* non-boolean values should default isComponent to false */
        $pe2 = new PatomicEntity();
        $pe2->isComponent();
        $expectedString = "{:db/id #db/id [:db.part/db] :db/isComponent false}";
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->isComponent(1);
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->isComponent("true");
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->isComponent(array("foo" => "bar"));
        $this->assertEquals($expectedString, sprintf($pe2));

        /* PatomicEntity can only have one isComponent attribute */
        $pe->isComponent(false);
        $expectedString = "{:db/id #db/id [:db.part/db] :db/isComponent false}";
        $this->assertEquals($expectedString, sprintf($pe));    
    }

    /**
     * @covers PatomicEntity::noHistory
     */
    public function testNoHistory() {
        /* noHistory only accepts a boolean value */
        $pe = new PatomicEntity();
        $pe->noHistory(true);
        $expectedString = "{:db/id #db/id [:db.part/db] :db/noHistory true}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* non-boolean values should default noHistory to false */
        $pe2 = new PatomicEntity();
        $pe2->noHistory();
        $expectedString = "{:db/id #db/id [:db.part/db] :db/noHistory false}";
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->noHistory(1);
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->noHistory("true");
        $this->assertEquals($expectedString, sprintf($pe2));
        $pe2->noHistory(array("foo" => "bar"));
        $this->assertEquals($expectedString, sprintf($pe2));

        /* PatomicEntity can only have one noHistory attribute */
        $pe->noHistory(false);
        $expectedString = "{:db/id #db/id [:db.part/db] :db/noHistory false}";
        $this->assertEquals($expectedString, sprintf($pe));
    }

    /**
     * @covers PatomicEntity::install
     */
    public function testInstall() {
        /* valid install attributes values must be one of the defined set */
        $pe = new PatomicEntity();
        $pe->install("attribute");
        $expectedString = "{:db/id #db/id [:db.part/db] :db.install/_attribute :db.part/db}";
        $this->assertEquals($expectedString, sprintf($pe));
        $pe2 = new PatomicEntity();
        $pe2->install("partition");
        $expectedString = "{:db/id #db/id [:db.part/db] :db.install/_partition :db.part/db}";
        $this->assertEquals($expectedString, sprintf($pe2));

        /* valid install attributes should handle non lower cased string input */
        $pe = new PatomicEntity();
        $pe->install("AttrIbuTe");
        $expectedString = "{:db/id #db/id [:db.part/db] :db.install/_attribute :db.part/db}";
        $this->assertEquals($expectedString, sprintf($pe));

        /* non string input should throw an exception */
        try {
            $pe->install();
        } catch(PatomicException $e) {
            $expectedString = "PatomicEntity::install installType must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }
        try {
            $pe->install(123123);
        } catch(PatomicException $e) {
            $expectedString = "PatomicEntity::install installType must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }
        try {
            $pe->install(array("hi", 78));
        } catch(PatomicException $e) {
            $expectedString = "PatomicEntity::install installType must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* string input that is not a valid install attribute type should throw an exception */
        try {
            $pe->install("database");
        } catch(PatomicException $e) {
            $expectedString = "PatomicEntity::install installType must be one of the following [attribute, partition]";
            $this->assertEquals($expectedString, $e->getMessage());
        }
    }

    /**
     * @covers PatomicEntity::prettyPrint
     */
    public function testPrettyPrint() {
        /* prettyPrint should match the datalog style shown on http://docs.datomic.com */
        /*
            {:db/id #db/id[:db.part/db]
             :db/ident :community/name
             :db/valueType :db.type/string
             :db/cardinality :db.cardinality/one
             :db/fulltext true
             :db/doc "A community's name"
             :db.install/_attribute :db.part/db}
        */
        $pe = new PatomicEntity();
        $pe->ident("community", "name")
            ->valueType("string")
            ->cardinality("one")
            ->fullText(true)
            ->doc("A community's name")
            ->install("attribute");
        $expectedStrings =  array(
            '{:db/id #db/id[:db.part/db]',
            ':db/ident :community/name',
            ':db/valueType :db.type/string',
            ':db/cardinality :db.cardinality/one',
            ':db/fulltext true',
            ':db/doc "A community\'s name"',
            ':db.install/_attribute :db.part/db}'
        );
        $expectedString = implode("\n ", $expectedStrings);
        ob_start();
        $pe->prettyPrint();
        $prettyPrintOutput = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($expectedString, $prettyPrintOutput);
    }
}
