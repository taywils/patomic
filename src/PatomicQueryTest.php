<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * PHPUnit Test class for PatomicQuery
 */
class PatomicQueryTest extends PHPUnit_Framework_TestCase
{
    private static $EDN_PARSER_EXCEPTION_CLASSNAME = 'igorw\edn\ParserException';
	/**
	 * @covers PatomicQuery::__construct
	 */
	public function testConstructor() {
        try {
            $pq = new PatomicQuery();
        } catch(PatomicException $e) {
            $this->fail("PatomicQuery::__construct should not throw an exception");
        }
	}

    /**
     * @covers PatomicQuery::newRawQuery
     * @covers PatomicQuery::getRawQuery
     */
    public function testNewRawQuery() {
        /* Valid Datalog should not throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->newRawQuery("[:find ?e ?v :in $ :where [?e :db/doc ?v]]");
            $rawQueryString = $pq->getRawQuery();
            $expectedString = '[:find ?e ?v :in $ :where [?e :db/doc ?v]]';
            $this->assertEquals($expectedString, $rawQueryString);
        } catch(\igorw\edn\ParserException $e) {
            $this->fail("Should not throw exception");
        }

        /* Invalid Datalog should throw an exception from the EDN parser */
        try {
            $pq = new PatomicQuery();
            $pq->newRawQuery("[&)_--find ?e ?v :in $ @@ :where [?e :db/doc");
        } catch(Exception $e) {
            $this->assertEquals(self::$EDN_PARSER_EXCEPTION_CLASSNAME, get_class($e));
        }

        /* Empty newRawQuery should throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->newRawQuery("");
            $this->fail("Exception should be thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::newRawQuery expects a non-empty string input";
            $this->assertEquals($expectedString, $e->getMessage());
        }
    }

    /**
     * @covers PatomicQuery::addRawQueryArgs
     * @covers PatomicQuery::getRawQueryArgs
     */
    public function testAddRawQueryArgs() {
        /* Valid Datalog should not throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->newRawQuery("[:find ?e ?v :in $ :where [?e :db/doc ?v]]")
                ->addRawQueryArgs("[{:db/alias taywils/testing}]");
            $rawQueryArgsString= $pq->getRawQueryArgs();
            $expectedString = '[{:db/alias taywils/testing}]';
            $this->assertEquals($expectedString, $rawQueryArgsString);
        } catch(Exception $e) {
            $this->fail("Should not throw exception");
        }

        /* Invalid Datalog should throw an exception from the EDN parser */
        try {
            $pq = new PatomicQuery();
            $pq->newRawQuery("[:find ?e ?v :in $ :where [?e :db/doc ?v]]")
                ->addRawQueryArgs("[^%$#@*)([{:db/alias taywils/testing}]");
            $this->fail("Exception was not thrown");
        } catch(Exception $e) {
            $this->assertEquals(self::$EDN_PARSER_EXCEPTION_CLASSNAME, get_class($e));
        }

        /* Trying to add rawQueryArgs without a rawQuery should throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->addRawQueryArgs("[{:db/alias taywils/testing}]");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::addRawQueryArgs create a newRawQuery before adding raw query arguments";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* Trying to add empty string as a rawQueryArg should throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->newRawQuery("[:find ?e ?v :in $ :where [?e :db/doc ?v]]")
                ->addRawQueryArgs("");
            $this->fail("Exception was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::addRawQueryArgs expects a non-empty string argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }
    }

    /**
     * @covers PatomicQuery::find
     * @covers PatomicQuery::getQuery
     */
    public function testFind() {
        /* Create a simple find query with multiple variables */
        $pq = new PatomicQuery();
        $pq->find("e", "x", "s");
        $queryString = $pq->getQuery();
        $expectedString = '[:find ?e ?x ?s :in $ :where]';
        $this->assertEquals($expectedString, $queryString);

        /* Passing zero argument should throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->find();
            $this->fail("Exception was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::find expects at least one \"string\" as an argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* Passing a non-string argument should throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->find("e", 123, "s");
            $this->fail("Exception was not thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::find encountered a non string argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }
    }

    /**
     * @covers PatomicQuery::in
     * @covers PatomicQuery::getQuery
     */
    public function testIn() {
        /* Create a simple query using PatomicQuery::in */
        $pq = new PatomicQuery();
        $pq->find("e", "x", "s")
            ->in("fname, lname");
        $expectedString = '[:find ?e ?x ?s :in $ ?fname ?lname :where]';
        $this->assertEquals($expectedString, $pq->getQuery());

        /* Zero arguments passed should throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->find("e", "x")
                ->in();
            $this->fail("PatomicException should have been thrown since function expects at least on argument");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::in expects at least one \"string\" and an optional \"array\" as arguments";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* A single argument passed must be a string */
        try {
            $pq = new PatomicQuery();
            $pq->find("e", "x")
                ->in(12345);
            $this->fail("PatomicException should have been thrown since 1st argument is not a string");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::in first argument was not a string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* Empty string should be a valid argument */
        try {
            $pq = new PatomicQuery();
            $pq->find("e", "x")
                ->in("");
        } catch(PatomicException $e) {
            $this->fail("PatomicException should not have been thrown");
        }

        /* Second argument must be an array and each array element must be a string */
        try {
            $pq = new PatomicQuery();
            $pq->find("e", "x")
                ->in("amount", array("one", "two"));
        } catch(PatomicException $e) {
            $this->fail("PatomicException should not have been thrown");
        }
        try {
            $pq = new PatomicQuery();
            $pq->find("e", "x")
                ->in("amount", array("one", 123));
            $this->fail("PatomicException should have been thrown since 2nd argument was an integer");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::in expects an array containing only string elements";
            $this->assertEquals($expectedString, $e->getMessage());
        }
    }

    /**
     * @covers PatomicQuery::where
     * @covers PatomicQuery::getQuery
     */
    public function testWhere() {
        /* Valid where query should resemble the following */
        /*
         * [:find ?e
         *  :in $ ?fname
         *  :where [?e :user/firstName ?fname]
         * ]
         */
        try {
            $pq = new PatomicQuery();
            $pq->find("e")
                ->in("fname lname")
                ->where(array("e" => "user/firstName", "fname"));
            $expectedString = "[:find ?e :in $ ?fname ?lname :where [?e :user/firstName ?fname]]";
            $this->assertEquals($expectedString, $pq->getQuery());
        } catch(PatomicException $e) {
            $this->fail("Exception should not have been thrown");
        }

        /* Zero arguments should throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->find("e")
                ->in("fname lname")
                ->where();
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::where expects an array as an argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* Non-array arguments should throw an exception */
        try {
            $pq = new PatomicQuery();
            $pq->find("e")
                ->in("fname lname")
                ->where(1231);
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "PatomicQuery::where expects an array as an argument";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* Multiple where invocations should resemble the following */
        /*
         * [:find ?e
         *  :in $ ?fname ?lname
         *  :where [?e :user/firstName ?fname]
         *         [?e :user/lastName ?lname]]
         * ]
         */
        try {
            $pq = new PatomicQuery();
            $pq->find("e")
                ->in("fname lname")
                ->where(array("e" => "user/firstName", "fname"))
                ->where(array("e" => "user/lastName", "lname"));
            $expectedString = "[:find ?e :in $ ?fname ?lname :where [?e :user/firstName ?fname] [?e :user/lastName ?lname]]";
            $this->assertEquals($expectedString, $pq->getQuery());
        } catch(PatomicException $e) {
            $this->fail("Exception should not have been thrown");
        }

        /* Integer should be valid where clauses */
        /* [:find ?e :in $ :where [?e :age 42]] */
        try {
            $pq = new PatomicQuery();
            $pq->find("e")
                ->where(array("e" => "age", 42));
            $expectedString = "[:find ?e :in $ :where [?e :age 42]]";
            $this->assertEquals($expectedString, $pq->getQuery());
        } catch(PatomicException $e) {
            $this->fail("Exception should not have been thrown");
        }
    }

    /**
     * @covers PatomicQuery::arg
     * @covers PatomicQuery::getQueryArgs
     */
    public function testArg() {

    }
}
