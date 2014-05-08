<?php

/**
 * PHPUnit tests for PatomicTransaction
 */
class PatomicTransactionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers PatomicTransaction::__construct
     */
    public function testConstructor() {
        /* A new PatomicTransaction object should be an empty Datalog vector */
        $pt = new PatomicTransaction();
        $this->assertEquals("[]", sprintf($pt));
    }

    /**
     * @covers PatomicTransaction::append
     */
    public function testAppend() {
    	/* Append a PatomicEntity object to the current transaction */
        $pt = new PatomicTransaction();
        $pe = new PatomicEntity();
        $pe->ident("community", "name")
            ->valueType("string")
            ->cardinality("one")
            ->fullText(true)
            ->doc("A community's name")
            ->install("attribute");
        $pt->append($pe);
        $expectedString = '[{:db/id #db/id [:db.part/db] :db/ident :community/name :db/valueType :db.type/string :db/cardinality :db.cardinality/one :db/fulltext true :db/doc "A community\'s name" :db.install/_attribute :db.part/db}]';
    	$this->assertEquals($expectedString, sprintf($pt)); 

    	/* Append a non PatomicEntity object to the current transaction */
    	try {
	        $pt2 = new PatomicTransaction();
	        $pt2->append("string");
	        $this->fail("PatomicException was not thrown");
    	} catch(PatomicException $e) {
    		$expectedString = "PatomicTransaction::append argument must be a valid PatomicEntity object";
    		$this->assertEquals($expectedString, $e->getMessage());
    	}
    }

    /**
     * @covers PatomicTransaction::add
     */
    public function testAdd() {

    }

    /**
     * @covers PatomicTransaction::retract
     */
    public function testRetract() {

    }

    /**
     * @covers PatomicTransaction::clearData
     */
    public function testClearData() {
    	/* after clearData is called the Transaction data should be an empty Datalog vector */
        $pt = new PatomicTransaction();
        $pe = new PatomicEntity();
        $pe->ident("community", "name")
            ->valueType("string")
            ->cardinality("one")
            ->fullText(true)
            ->doc("A community's name")
            ->install("attribute");
        $pt->append($pe);
        $pt->clearData();
        $this->assertEquals("[]", sprintf($pt));
    }

    /**
     * @covers PatomicTransaction::prettyPrint
     */
    public function prettyPrint() {

    }

    /**
     * @covers PatomicTransaction::__toString
     */
    public function testToString() {

    }
}