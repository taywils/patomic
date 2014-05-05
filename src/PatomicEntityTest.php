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
        $uniqueTypes = array("value", "identity");

        /* non string argument should throw an exception */

        /* PatomicEntity should only have a single unique attribute */
    }
}
