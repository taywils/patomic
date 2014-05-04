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
}
