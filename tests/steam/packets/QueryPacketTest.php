<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2020, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

class QueryPacketTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->packet = $this->getMockForAbstractClass('QueryPacket', array(0x61, 'test'));
    }

    public function testPadding() {
        $this->assertEquals(str_pad("\xFF\xFF\xFF\xFFatest", 1200, "\0"), $this->packet->__toString());
    }

}
