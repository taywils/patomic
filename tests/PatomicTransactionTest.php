<?php

use \taywils\Patomic\PatomicTransaction;
use \taywils\Patomic\PatomicEntity;
use \taywils\Patomic\PatomicException;

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
        try {
            $pt = new PatomicTransaction();
            $this->assertEquals("[]", sprintf($pt));
        } catch(PatomicException $e) {
            $this->fail("PatomicTransaction::__construct should not throw an exception");
        }
    }

    /**
     * @covers PatomicTransaction::append
     * @covers PatomicTransaction::clearIfLoadedFromFile
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
    		$expectedString = "PatomicTransaction::append argument must be a valid " . 'taywils\Patomic\PatomicEntity' . " object";
    		$this->assertEquals($expectedString, $e->getMessage());
    	}
    }

    /**
     * @covers PatomicTransaction::add
     * @covers PatomicTransaction::addOrRetract
     * @covers PatomicTransaction::clearIfLoadedFromFile
     */
    public function testAdd() {
        /* Add valid data to a PatomicTransaction without a tempId */
        $pt = new PatomicTransaction();
        $pt->add("account", "balance", 10);
        $expectedString = '[[:db/add #db/id [:db.part/user] :account/balance 10]]';
        $this->assertEquals($expectedString, sprintf($pt));

        /* Add valid data to a PatomicTransaction with tempId */
        $pt2 = new PatomicTransaction();
        $pt2->add("company", "name", "Microsoft", -20);
        $expectedString = '[[:db/add #db/id [:db.part/user -20] :company/name "Microsoft"]]';
        $this->assertEquals($expectedString, sprintf($pt2));

        /* invalid entityName argument should throw exception */
        try {
            $pt3 = new PatomicTransaction();
            $pt3->add(1414, "name", "Google");
            $this->fail("PatomicException was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addOrRetract entityName must be a string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* invalid attributeName argument should throw exception */
        try {
            $pt3 = new PatomicTransaction();
            $pt3->add("company", array(), "Google");
            $this->fail("PatomicException was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addOrRetract attributeName must be a string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* value argument should not be null */
        try {
            $pt4 = new PatomicTransaction();
            $pt4->add("company", "name");
            $this->fail("PatomicException was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addOrRetract value argument cannot be null";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* non-integer tempId should throw an exception */
        try {
            $pt5 = new PatomicTransaction();
            $pt5->add("company", "name", "Amazon", "-1");
            $this->fail("PatomicException was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addOrRetract tempId argument must be an integer";
            $this->assertEquals($expectedString, $e->getMessage());
        }
    }

    /**
     * @covers PatomicTransaction::retract
     * @covers PatomicTransaction::addOrRetract
     * @covers PatomicTransaction::clearIfLoadedFromFile
     */
    public function testRetract() {
        /* retract valid data to a PatomicTransaction without a tempId */
        $pt = new PatomicTransaction();
        $pt->retract("account", "balance", 10);
        $expectedString = '[[:db/retract #db/id [:db.part/user] :account/balance 10]]';
        $this->assertEquals($expectedString, sprintf($pt));

        /* retract valid data to a PatomicTransaction with tempId */
        $pt2 = new PatomicTransaction();
        $pt2->retract("company", "name", "Microsoft", -20);
        $expectedString = '[[:db/retract #db/id [:db.part/user -20] :company/name "Microsoft"]]';
        $this->assertEquals($expectedString, sprintf($pt2));

        /* invalid entityName argument should throw exception */
        try {
            $pt3 = new PatomicTransaction();
            $pt3->retract(1414, "name", "Google");
            $this->fail("PatomicException was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addOrRetract entityName must be a string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* invalid attributeName argument should throw exception */
        try {
            $pt3 = new PatomicTransaction();
            $pt3->retract("company", array(), "Google");
            $this->fail("PatomicException was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addOrRetract attributeName must be a string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* value argument should not be null */
        try {
            $pt4 = new PatomicTransaction();
            $pt4->retract("company", "name");
            $this->fail("PatomicException was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addOrRetract value argument cannot be null";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* non-integer tempId should throw an exception */
        try {
            $pt5 = new PatomicTransaction();
            $pt5->retract("company", "name", "Amazon", "-1");
            $this->fail("PatomicException was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addOrRetract tempId argument must be an integer";
            $this->assertEquals($expectedString, $e->getMessage());
        }
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
    public function testPrettyPrint() {
        /* prettyPrint should match the style shown on http://www.docs.datomic.com */
        /*
         [

            {:db/id #db/id[:db.part/db]
             :db/ident :community/name
             :db/valueType :db.type/string
             :db/cardinality :db.cardinality/one
             :db/fulltext true
             :db/doc "A community's name"
             :db.install/_attribute :db.part/db}

         ]
         */
        $pt = new PatomicTransaction();
        $pe = new PatomicEntity();
        $pe->ident("community", "name")
            ->valueType("string")
            ->cardinality("one")
            ->fullText(true)
            ->doc("A community's name")
            ->install("attribute");
        $pt->append($pe);
        $expectedString = "[" . PHP_EOL;
        $expectedStringArray = array(
            '{:db/id #db/id[:db.part/db]',
             ':db/ident :community/name',
             ':db/valueType :db.type/string',
             ':db/cardinality :db.cardinality/one',
             ':db/fulltext true',
             ':db/doc "A community\'s name"',
             ':db.install/_attribute :db.part/db}'
        );
        $expectedString .= implode("\n ", $expectedStringArray) . PHP_EOL . PHP_EOL . ']' . PHP_EOL;
        ob_start();
        $pt->prettyPrint();
        $prettyPrintString = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($expectedString, $prettyPrintString);
    }

    /**
     * @covers PatomicTransaction::__toString
     */
    public function testToString() {
        /* Append a PatomicEntity object and add data to the current transaction */
        $pt = new PatomicTransaction();
        $pe = new PatomicEntity();
        $pe->ident("community", "name")
            ->valueType("string")
            ->cardinality("one")
            ->fullText(true)
            ->doc("A community's name")
            ->install("attribute");
        $pt->append($pe);
        $expectedString1 = '[{:db/id #db/id [:db.part/db] :db/ident :community/name :db/valueType :db.type/string :db/cardinality :db.cardinality/one :db/fulltext true :db/doc "A community\'s name" :db.install/_attribute :db.part/db}]';
        $this->assertEquals($expectedString1, sprintf($pt));
        $pt->add("community", "name", "Beacon Hill");
        $expectedString2 = '[{:db/id #db/id [:db.part/db] :db/ident :community/name :db/valueType :db.type/string :db/cardinality :db.cardinality/one :db/fulltext true :db/doc "A community\'s name" :db.install/_attribute :db.part/db}[:db/add #db/id [:db.part/user] :community/name "Beacon Hill"]]';
        $this->assertEquals($expectedString2, sprintf($pt));
    }

    /**
     * @covers PatomicTransaction::loadFromFile
     */
    public function testLoadFromFile() {
        /* fileName Argument is not a string */
        try {
            $pt = new PatomicTransaction();
            $pt->loadFromFile(124);
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::loadFromFile \$fileName argument must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* fileName Argument is an empty string */
        try {
            $pt = new PatomicTransaction();
            $pt->loadFromFile(" ");
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::loadFromFile \$fileName argument must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* fileName Argument has the incorrect file extension */
        try {
            $pt = new PatomicTransaction();
            $pt->loadFromFile("seattle-schema.txt");
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::loadFromFile seattle-schema.txt does not have the extension .edn";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* fileName does not exists or is not readable */
        try {
            $pt = new PatomicTransaction();
            $pt->loadFromFile("seattle-schem.edn");
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::loadFromFile seattle-schem.edn was not found or cannot be read, please change file the permissions";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* loadFromFile should not throw an exception for valid edn files */
        try {
            $pt = new PatomicTransaction();
            $pt->loadFromFile(__DIR__ . DIRECTORY_SEPARATOR . "seattle-schema.edn");
            $pt->loadFromFile(__DIR__ . DIRECTORY_SEPARATOR . "seattle-data0.edn");
        } catch(Exception $e) {
            $this->fail("Exception should not have been thrown" . PHP_EOL . $e->getMessage());
        }
    }

    /**
     * @covers PatomicTransaction::addMany
     */
    public function testAddMany() {
        try {
            $pt = new PatomicTransaction();

            $pt->addMany(-100,
                array("post" => "title", "This mad world"),
                array("post" => "author", "Taywils")
            );
        } catch(PatomicException $e) {
            $this->fail("PatomicTransaction::addMany should not throw an exception");
        }
    }
}