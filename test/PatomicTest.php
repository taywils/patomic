<?php

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * PHPUnit Test class for Patomic
 * @see http://phpunit.de/manual/current/en/test-doubles.html
 */
class PatomicTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers Patomic::__construct
	 */
	public function testConstructor() {
		/* Valid constructor inputs should not throw an exception */
		try {
		    $p = new Patomic("http://localhost", 9998, "mem", "taywils");
		} catch(PatomicException $e) {
			$this->fail("Patomic::__construct should not throw an exception for valid input");
		}

		/* not setting serverUrl should throw exception */
		try {
		    $p = new Patomic(null, 9998, "mem", "taywils");
			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			$expectedString = "Patomic::__construct \$serverUrl argument must be set";
			$this->assertEquals($expectedString, $e->getMessage());
		}

		/* non-string serverUrl should throw exception */
		try {
            $p = new Patomic(12341, 9998, "mem", "taywils");
			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
            $expectedString = "Patomic::__construct \$serverUrl must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
		}

		/* empty serverUrl should throw exception */
        try {
            $p = new Patomic("", 9998, "mem", "taywils");
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "Patomic::__construct \$serverUrl must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

		/* null port should throw exception */
		try {
            $p = new Patomic("http://localhost", null, "mem", "taywils");
			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
            $expectedString = "Patomic::__construct \$port argument must be set";
            $this->assertEquals($expectedString, $e->getMessage());
		}

		/* non-integer port should throw exception */
		try {
            $p = new Patomic("http://localhost", "port", "mem", "taywils");
			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
            $expectedString = "Patomic::__construct \$port must be an integer";
            $this->assertEquals($expectedString, $e->getMessage());
		}

		/* null storage should throw exception */
		try {
            $p = new Patomic("http://localhost", 1334, null, "taywils");
			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
            $expectedString = "Patomic::__construct \$storage must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
		}

        /* empty storage should throw exception */
        try {
            $p = new Patomic("http://localhost", 74681, "", "taywils");
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "Patomic::__construct \$storage must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

		/* invalid storage should throw exception */
		try {
            $p = new Patomic("http://localhost", 74681, "squid", "taywils");
			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
            $expectedString = "Patomic::__construct \$storage must be one of the following [mem, dev, sql, inf, ddb]";
            $this->assertEquals($expectedString, $e->getMessage());
		}

		/* null alias should throw exception */
		try {
            $p = new Patomic("http://localhost", 74681, "sql", null);
			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			$expectedString = "Patomic::__construct \$alias argument must be set";
            $this->assertEquals($expectedString, $e->getMessage());
		}

        /* empty string alias should throw exception */
        try {
            $p = new Patomic("http://localhost", 74681, "sql", "   ");
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "Patomic::__construct \$alias must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

		/* non-string alias should throw exception */
		try {
            $p = new Patomic("http://localhost", 74681, "sql", array());
			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
            $expectedString = "Patomic::__construct \$alias must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }
	}

    /**
     * @covers Patomic::setDatabase
     */
    public function testSetDatabase() {
        /* non-string argument should throw exception */
        try {
            $p = new Patomic("http://localhost", 9998, "mem", "taywils");
            $p->setDatabase(12354);
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "Patomic::setDatabase \$dbName must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* empty string argument should throw exception */
        try {
            $p = new Patomic("http://localhost", 9998, "mem", "taywils");
            $p->setDatabase("  ");
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "Patomic::setDatabase \$dbName must be a non-empty string";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* If no database(s) exist then throw an exception */
        try {
            $p = new Patomic("http://localhost", 9998, "mem", "taywils");
            $p->setDatabase("something");
            $this->fail("PatomicException should have been thrown");
        } catch(PatomicException $e) {
            $expectedString = "Patomic::setDatabase Cannot assign Database because none have been created";
            $this->assertEquals($expectedString, $e->getMessage());
        }

        /* Assing new database name */        
        try {
            // We need to set the private property using Reflection
            // @see http://www.php.net/manual/en/reflectionproperty.setvalue.php
            $p = new Patomic("http://localhost", 9998, "mem", "taywils");
            $this->fail("Finish this test");
        } catch(PatomicException $e) {
            $this->fail("PatomicException should not be thrown");
        }
    }
}
