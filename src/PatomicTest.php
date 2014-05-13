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

			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			
		}

		/* invalid serverUrl string should throw exception */
		try {

			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			
		}

		/* empty serverUrl should throw exception */
		try {

			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			
		}

		/* null port should throw exception */
		try {

			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			
		}

		/* non-integer port should throw exception */
		try {

			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			
		}

		/* null storage should throw exception */
		try {

			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			
		}

		/* invalid storage should throw exception */
		try {

			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			
		}

		/* null alias should throw exception */
		try {

			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			
		}

		/* non-string alias should throw exception */
		try {

			$this->fail("PatomicException should have been thrown");
		} catch(PatomicException $e) {
			
		}
	}
}
