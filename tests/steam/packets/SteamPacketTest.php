<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

class SteamPacketTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->packet = $this->getMockForAbstractClass('SteamPacket', array(0x61, 'test'));
    }

    public function testGetData() {
        $data = $this->packet->getData();

        $this->assertInstanceOf('ByteBuffer', $data);
        $this->assertEquals('test', $data->_array());
    }

    public function testGetHeader() {
        $this->assertEquals(0x61, $this->packet->getHeader());
    }

    public function testToString() {
        $this->assertEquals("\xFF\xFF\xFF\xFFatest", $this->packet->__toString());
    }

}
