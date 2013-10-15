<?php

class PatomicSchemaTest extends PHPUnit_Framework_TestCase
{
        /**
         * @expectedException PatomicException
         */
        public function testConstructValueType()
        {
                $ps = new PatomicSchema("Foo", "Bar");
        }

        /**
         * @expectedException PatomicException
         */
        public function testConstructName()
        {
                $ps = new PatomicSchema(null, "ref");
        }
}
