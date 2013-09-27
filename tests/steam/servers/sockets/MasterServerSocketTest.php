<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012-2014, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers\Sockets;

class MasterServerSocketTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->socketBuilder = $this->getMockBuilder('\SteamCondenser\Servers\Sockets\MasterServerSocket');
        $this->socketBuilder->disableOriginalConstructor();
        $this->socketBuilder->setMethods(array('receivePacket'));
        $this->socket = $this->socketBuilder->getMock();

        $this->buffer = $this->getMockBuilder('\SteamCondenser\ByteBuffer')->disableOriginalConstructor()->getMock();
        $reflectionSocket = new \ReflectionObject($this->socket);
        $bufferProperty = $reflectionSocket->getProperty('buffer');
        $bufferProperty->setAccessible(true);
        $bufferProperty->setValue($this->socket, $this->buffer);

        $log = new \ReflectionProperty('\SteamCondenser\Servers\Sockets\MasterServerSocket', 'log');
        $log->setAccessible(true);
        $log->setValue(new \Monolog\Logger('\SteamCondenser\Servers\Sockets\MasterServerSocket'));
        $log->getValue()->pushHandler(new \Monolog\Handler\NullHandler());
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
