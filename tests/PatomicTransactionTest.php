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

        /* If the value part of the eav triplet is a DateTime object then use the Datomic #inst tag */
        try {
            $pt = new PatomicTransaction();

            $birthday = new DateTime('1988-06-16');
            $pt->add("employee", "dob", $birthday);

            ob_start();
            echo $pt;
            $output = ob_get_contents();
            ob_end_clean();

            $expectedString = '[[:db/add #db/id [:db.part/user] :employee/dob #inst "1988-06-16"]]';

            $this->assertEquals($expectedString, $output);
        } catch(PatomicException $e) {
            $this->fail("PatomicTransaction::add should not throw an exception");
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

        $expectedString = <<<'EOD'
[

{:db/id #db/id[:db.part/db]
 :db/ident :community/name
 :db/valueType :db.type/string
 :db/cardinality :db.cardinality/one
 :db/fulltext true
 :db/doc "A community's name"
 :db.install/_attribute :db.part/db}

]

EOD;
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
     * @covers PatomicTransaction::prettyPrint
     * @covers PatomicTransaction::__toString
     * @covers PatomicTransaction::loadFromFile
     */
    public function testAddMany() {
        /* Zero arguments passed will throw an exception */
        try {
            $pt = new PatomicTransaction();

            $pt->addMany();
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addMany expects at minimum two arguments";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* A single valid argument will throw an exception */
        try {
            $pt = new PatomicTransaction();

            $pt->addMany(-20001, "string");
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addMany was given an empty or non-array argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* Valid tempId with empty array will throw exception */
        try {
            $pt = new PatomicTransaction();

            $pt->addMany(-20001, array());
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addMany was given an empty or non-array argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* Null tempId should not throw an exception */
        try {
            $pt = new PatomicTransaction();

            $pt->addMany(null, array("book" => "pageCount", 321));
        } catch(PatomicException $e) {
            $this->fail("PatomicException should not have been thrown ");
        }

        /* Integer tempId and valid basic array should not throw an exception */
        try {
            $pt = new PatomicTransaction();

            $pt->addMany(-10007, array("book" => "pageCount", 321));
        } catch(PatomicException $e) {
            $this->fail("PatomicException should not have been thrown");
        }

        /* Non-null non-integer tempId will throw an exception */
        try {
            $pt = new PatomicTransaction();

            $pt->addMany("string", array("book" => "pageCount", 321));
        } catch(PatomicException $e) {
            $expectedString = "PatomicTransaction::addMany tempId argument must be an integer";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* Valid tempId with basic eav arrays will match prettyPrint output */
        try {
            $pt = new PatomicTransaction();

            $pt->addMany(-100,
                array("post" => "title", "This mad world"),
                array("post" => "author", "Taywils")
            );

            ob_start();
            $pt->prettyPrint();
            $prettyPrintString = ob_get_contents();
            ob_end_clean();

            $expectedString = <<<'EOD'
[

{:db/id #db/id [:db.part/user -100] :post/title "This mad world" :post/author "Taywils"}

]

EOD;

            $this->assertEquals($expectedString, $prettyPrintString);
        } catch(PatomicException $e) {
            $this->fail("PatomicTransaction::addMany should not throw an exception");
        }

        /* addMany should not cause __toString to throw an exception */
        try {
            $pt = new PatomicTransaction();

            $pt->addMany(-100,
                array("post" => "title", "This mad world"),
                array("post" => "author", "Taywils")
            );

            ob_start();
            echo $pt;
            ob_end_clean();
        } catch(PatomicException $e) {
            $this->fail("PatomicTransaction::addMany should not throw an exception");
        }

        /* addMany will clear the transaction body previously loaded from a file */
        try {
            $pt = new PatomicTransaction();

            $contents = sprintf($pt);
            $this->assertEquals(true, "[]" == $contents);

            $pt->loadFromFile(__DIR__ . DIRECTORY_SEPARATOR . "seattle-data0.edn");

            $contents = sprintf($pt);
            $this->assertEquals(true, "[]" != $contents);

            $pt->addMany(-100,
                array("post" => "title", "This mad world"),
                array("post" => "author", "Taywils")
            );

            $echoString = sprintf($pt);

            $expectedString = '[{:db/id #db/id [:db.part/user -100] :post/title "This mad world" :post/author "Taywils"}]';
            $this->assertEquals($echoString, $expectedString);
        } catch(PatomicException $e) {
            $this->fail("PatomicTransaction::addMany should not throw an exception");
        }
    }
}