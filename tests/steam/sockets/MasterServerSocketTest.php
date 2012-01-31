<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once dirname(__FILE__) . '/../../../lib/steam-condenser.php';
require_once STEAM_CONDENSER_PATH . 'steam/sockets/MasterServerSocket.php';

class MasterServerSocketTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->socketBuilder = $this->getMockBuilder('MasterServerSocket');
        $this->socketBuilder->disableOriginalConstructor();
        $this->socketBuilder->setMethods(array('receivePacket'));
        $this->socket = $this->socketBuilder->getMock();

        $this->buffer = $this->getMockBuilder('ByteBuffer')->disableOriginalConstructor()->getMock();
        $reflectionSocket = new ReflectionObject($this->socket);
        $bufferProperty = $reflectionSocket->getProperty('buffer');
        $bufferProperty->setAccessible(true);
        $bufferProperty->setValue($this->socket, $this->buffer);
    }

    public function testIncorrectPacket() {
        $this->socket->expects($this->once())->method('receivePacket')->with(1500);

        $this->buffer->expects($this->once())->method('getLong')->will($this->returnValue(1));

        $this->setExpectedException('PacketFormatException', 'Master query response has wrong packet header.');

        $this->socket->getReply();
    }

    public function testReply() {
        $this->socket->expects($this->once())->method('receivePacket')->with(1500);

        $this->buffer->expects($this->once())->method('getLong')->will($this->returnValue(-1));
        $this->buffer->expects($this->once())->method('get')->will($this->returnValue("\x66\x0A\0\0\0\0\0\0"));

        $this->assertInstanceOf('M2A_SERVER_BATCH_Packet', $this->socket->getReply());
    }

}
