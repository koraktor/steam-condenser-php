<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers\Packets;

class SteamPacketTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->packet = $this->getMockForAbstractClass('\SteamCondenser\Servers\Packets\SteamPacket', [0x61, 'test']);
    }

    public function testGetData() {
        $data = $this->packet->getData();

        $this->assertInstanceOf('\SteamCondenser\ByteBuffer', $data);
        $this->assertEquals('test', $data->_array());
    }

    public function testGetHeader() {
        $this->assertEquals(0x61, $this->packet->getHeader());
    }

    public function testToString() {
        $this->assertEquals("\xFF\xFF\xFF\xFFatest", $this->packet->__toString());
    }

}
