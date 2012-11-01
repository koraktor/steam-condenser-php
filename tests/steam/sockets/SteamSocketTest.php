<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . "steam/sockets/SteamSocket.php";

class GenericSteamSocket extends SteamSocket {

    public $buffer;

    public $socket;

    public function getReply() {
        return null;
    }

}

/**
 * @author     Sebastian Staudt
 * @covers     SteamSocket
 * @package    steam-condenser
 * @subpackage tests
 */
class SteamSocketTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->udpSocket = $this->getMock("UDPSocket");

        $this->socket = new GenericSteamSocket('127.0.0.1');
        $this->socket->socket = $this->udpSocket;
    }

    public function testCloseUdpSocket() {
        $this->udpSocket->expects($this->at(0))->method('isOpen')->will($this->returnValue(true));
        $this->udpSocket->expects($this->at(1))->method('isOpen')->will($this->returnValue(false));
        $this->udpSocket->expects($this->once())->method('close');

        $this->socket->close();
    }

    public function testReceiveIntoNewBuffer() {
        $this->udpSocket->expects($this->once())->method('select')->will($this->returnValue(true));
        $this->udpSocket->expects($this->once())->method('recv')->with(4)->will($this->returnValue('test'));

        $this->assertEquals(4, $this->socket->receivePacket(4));

        $buffer = $this->socket->buffer;
        $this->assertEquals(0, $buffer->position());
        $this->assertEquals(4, $buffer->remaining());
        $this->assertEquals('test', $buffer->_array());
    }

    public function testReceiveIntoExistingBuffer() {
        $this->socket->buffer = ByteBuffer::allocate(10);

        $this->udpSocket->expects($this->once())->method('select')->will($this->returnValue(true));
        $this->udpSocket->expects($this->once())->method('recv')->with(4)->will($this->returnValue('test'));

        $this->assertEquals(4, $this->socket->receivePacket(4));

        $buffer = $this->socket->buffer;
        $this->assertEquals(0, $buffer->position());
        $this->assertEquals(4, $buffer->remaining());
        $this->assertEquals('test', $buffer->_array());
    }

    public function testSendPacket() {
        PHPUnit_Framework_Error_Notice::$enabled = FALSE;

        $packet = $this->getMockBuilder('SteamPacket')
                        ->disableOriginalConstructor()->getMock();
        $packet->expects($this->once())->method('__toString')->will($this->returnValue('test'));
        $this->udpSocket->expects($this->once())->method('send')->with('test');

        $this->socket->send($packet);
    }

    public function testSetTimeout() {
        SteamSocket::setTimeout(2000);

        $steamSocketClass = new ReflectionClass('SteamSocket');
        $timeoutProperty = $steamSocketClass->getProperty('timeout');
        $timeoutProperty->setAccessible(true);
        $this->assertEquals(2000, $timeoutProperty->getValue());
    }

    public function testTimeout() {
        $this->udpSocket->expects($this->once())->method('select')->will($this->returnValue(false));
        $this->setExpectedException('TimeoutException');

        $this->socket->receivePacket();
    }

}
