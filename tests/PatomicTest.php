<?php

use \taywils\Patomic\Patomic;
use \taywils\Patomic\PatomicException;

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
		    new Patomic("http://localhost", 9998, "mem", "taywils");
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

        /* If no database(s) exists then just set the DB name anyways do not throw an exception */
        try {
            $p = new Patomic("http://localhost", 9998, "mem", "taywils");

            ob_start();
            $p->setDatabase("something");
            $cliOutput = ob_get_contents();
            ob_end_clean();

            // We need to set the private property with valid data using Reflection
            $reflectionClass = new ReflectionClass('\taywils\Patomic\Patomic');
            $reflectionProperty = $reflectionClass->getProperty('dbNames');
            $reflectionProperty->setAccessible(true);
            $dbNamesArray = $reflectionProperty->getValue($p);

            $this->assertEquals($dbNamesArray, array('something'));
            $this->assertEquals("INFO: A Patomic object set database to something" . PHP_EOL, $cliOutput);
        } catch(PatomicException $e) {
            $this->fail("PatomicException should not be thrown");
        }

        /* The config dbName should be updated */
        try {
            $p = new Patomic("http://localhost", 9998, "mem", "taywils");

            ob_start();
            $p->setDatabase('rhino');
            ob_end_clean();

            // We need to read the private property data using Reflection
            $reflectionClass = new ReflectionClass('\taywils\Patomic\Patomic');
            $reflectionProperty = $reflectionClass->getProperty('config');
            $reflectionProperty->setAccessible(true);
            $currentConfigDbName = $reflectionProperty->getValue($p);

            $this->assertEquals($currentConfigDbName['dbName'], 'rhino');

            ob_start();
            $p->setDatabase('testdb');
            $cliOutput = ob_get_contents();
            ob_end_clean();

            $this->assertEquals("INFO: A Patomic object set database to testdb" . PHP_EOL, $cliOutput);
        } catch(PatomicException $e) {
            $this->fail("PatomicException should not be thrown");
        }

        /* If the argument to setDatabase is a name that already exists within the dbNames array
            do not throw an exception just print the console message indicating that the db name was properly set
         */
        try {
            $p = new Patomic("http://localhost", 9998, "mem", "taywils");
            $dbNamesValue = array('rhino', 'cheetah');

            // We need to set the private property with valid data using Reflection
            $reflectionClass = new ReflectionClass('\taywils\Patomic\Patomic');
            $reflectionProperty = $reflectionClass->getProperty('dbNames');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($p, $dbNamesValue);

            ob_start();
            $p->setDatabase('cheetah');
            $cliOutput = ob_get_contents();
            ob_end_clean();

            $this->assertEquals("INFO: A Patomic object set database to cheetah" . PHP_EOL, $cliOutput);

            // The list of database names should not include a duplicate
            $reflectionDbNames = $reflectionProperty->getValue($p);
            $this->assertEquals(array(), array_diff($reflectionDbNames, array('rhino', 'cheetah')));
        } catch(PatomicException $e) {
            $this->fail("PatomicException should not be thrown");
        }

        /* If the argument to setDatabase is a name that does not exist within the dbNames array
            do not throw an exception just print the console message indicating that the db name was properly set
         */
        try {
            $p = new Patomic("http://localhost", 9998, "mem", "taywils");
            $dbNamesValue = array('rhino');

            // We need to set the private property with valid data using Reflection
            $reflectionClass = new ReflectionClass('\taywils\Patomic\Patomic');
            $reflectionProperty = $reflectionClass->getProperty('dbNames');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($p, $dbNamesValue);

            ob_start();
            $p->setDatabase('cheetah');
            $cliOutput = ob_get_contents();
            ob_end_clean();

            $this->assertEquals("INFO: A Patomic object set database to cheetah" . PHP_EOL, $cliOutput);

            // The name should be added to the 'dbNames' property
            $reflectionDbNames = $reflectionProperty->getValue($p);
            $this->assertEquals(array(), array_diff($reflectionDbNames, array('rhino', 'cheetah')));
        } catch(PatomicException $e) {
            $this->fail("PatomicException should not be thrown");
        }
    }
}
