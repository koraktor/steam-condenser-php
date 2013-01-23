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
require_once STEAM_CONDENSER_PATH . 'steam/sockets/RCONSocket.php';

class TestableRCONSocket extends RCONSocket {

    public $buffer;

    public $socket;

}

class RCONSocketTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->socketBuilder = $this->getMockBuilder('TestableRCONSocket');
        $this->socketBuilder->setConstructorArgs(array('127.0.0.1', 27015));
    }

    public function testConstructor() {
        $socket = $this->socketBuilder->getMock();

        $this->assertAttributeEquals('127.0.0.1', 'ipAddress', $socket);
        $this->assertAttributeEquals(27015, 'portNumber', $socket);
        $this->assertAttributeEmpty('socket', $socket);
    }

    public function testClose() {
        $socket = new TestableRCONSocket('127.0.0.1', 27015);
        $tcpSocket = $this->getMock('TCPSocket');
        $socket->socket = $tcpSocket;

        $tcpSocket->expects($this->at(1))->method('isOpen')->will($this->returnValue(true));
        $tcpSocket->expects($this->at(2))->method('isOpen')->will($this->returnValue(false));
        $tcpSocket->expects($this->once())->method('close');

        $socket->close();
    }

    public function testSend() {
        $socket = new TestableRCONSocket('127.0.0.1', 27015);
        $tcpSocket = $this->getMock('TCPSocket');
        $socket->socket = $tcpSocket;
        $packet = $this->getMockBuilder('RCONPacket')->disableOriginalConstructor()->getMock();
        $packet->expects($this->once())->method('__toString')->will($this->returnValue('test'));
        $tcpSocket->expects($this->exactly(2))->method('isOpen')->will($this->returnValue(true));
        $tcpSocket->expects($this->once())->method('send')->with('test');

        $socket->send($packet);
    }

    public function testGetReply() {
        $buffer = $this->getMockBuilder('ByteBuffer')->disableOriginalConstructor()->getMock();
        $this->socketBuilder->setMethods(array('receivePacket'));
        $socket = $this->socketBuilder->getMock();
        $socket->buffer = $buffer;

        $buffer->expects($this->once())->method('getLong')->will($this->returnValue(1234));
        $buffer->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls("\xFF\0\0\0\0\0\0\0", "test\0\0"));
        $socket->expects($this->at(0))->method('receivePacket')->with(4)->will($this->returnValue(1));
        $socket->expects($this->at(1))->method('receivePacket')->with(1234)->will($this->returnValue(1000));
        $socket->expects($this->at(2))->method('receivePacket')->with(234)->will($this->returnValue(234));

        $reply = $socket->getReply();
        $this->assertInstanceOf('RCONExecResponse', $reply);
        $this->assertEquals(255, $reply->getRequestId());
        $this->assertEquals('test', $reply->getResponse());
    }

    public function testConnectionDropped() {
        $this->socketBuilder->setMethods(array('receivePacket'));
        $socket = $this->socketBuilder->getMock();
        $tcpSocket = $this->getMock('TCPSocket');
        $tcpSocket->expects($this->once())->method('close');
        $socket->socket = $tcpSocket;
        $socket->expects($this->once())->method('receivePacket')->with(4)->will($this->returnValue(0));

        try {
            $socket->getReply();
            $this->fail("No exception thrown.");
        } catch (Exception $e) {
            $this->assertInstanceOf('RCONBanException', $e);
        }
    }

    public function testConnectionReset() {
        $this->socketBuilder->setMethods(array('receivePacket'));
        $socket = $this->socketBuilder->getMock();
        $socket->expects($this->once())->method('receivePacket')->with(4)->will($this->throwException(new SocketException(SOCKET_ECONNRESET)));

        try {
            $socket->getReply();
            $this->fail("No exception thrown.");
        } catch (Exception $e) {
            $this->assertInstanceOf('SocketException', $e);
        }
    }

}
