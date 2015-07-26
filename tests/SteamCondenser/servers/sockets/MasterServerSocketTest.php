<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers\Sockets;

class MasterServerSocketTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->socketBuilder = $this->getMockBuilder('\SteamCondenser\Servers\Sockets\MasterServerSocket');
        $this->socketBuilder->disableOriginalConstructor();
        $this->socketBuilder->setMethods(['receivePacket']);
        $this->socket = $this->socketBuilder->getMock();
        $this->socket->setLogger(\SteamCondenser\getLogger(get_class($this->socket)));

        $this->buffer = $this->getMockBuilder('\SteamCondenser\ByteBuffer')->disableOriginalConstructor()->getMock();
        $reflectionSocket = new \ReflectionObject($this->socket);
        $bufferProperty = $reflectionSocket->getProperty('buffer');
        $bufferProperty->setAccessible(true);
        $bufferProperty->setValue($this->socket, $this->buffer);
    }

    public function testIncorrectPacket() {
        $this->socket->expects($this->once())->method('receivePacket')->with(1500);

        $this->buffer->expects($this->once())->method('getLong')->will($this->returnValue(1));

        $this->setExpectedException('\SteamCondenser\Exceptions\PacketFormatException', 'Master query response has wrong packet header.');

        $this->socket->getReply();
    }

    public function testReply() {
        $this->socket->expects($this->once())->method('receivePacket')->with(1500);

        $this->buffer->expects($this->once())->method('getLong')->will($this->returnValue(-1));
        $this->buffer->expects($this->once())->method('get')->will($this->returnValue("\x66\x0A\0\0\0\0\0\0"));

        $this->assertInstanceOf('\SteamCondenser\Servers\Packets\M2ASERVERBATCHPacket', $this->socket->getReply());
    }

}
