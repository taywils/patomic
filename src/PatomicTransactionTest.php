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
}