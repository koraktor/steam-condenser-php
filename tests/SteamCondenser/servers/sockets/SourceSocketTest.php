<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers\Sockets;

class SourceSocketTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->socketBuilder = $this->getMockBuilder('\SteamCondenser\Servers\Sockets\SourceSocket');
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

    public function testSimpleReply() {
        $this->socket->expects($this->once())->method('receivePacket')->with(1400);

        $this->buffer->expects($this->once())->method('getLong')->will($this->returnValue(-1));
        $this->buffer->expects($this->once())->method('get')->will($this->returnValue('A'));

        $this->assertInstanceOf('\SteamCondenser\Servers\Packets\S2CCHALLENGEPacket', $this->socket->getReply());
    }

    public function testSplitReply() {
        $this->socket->expects($this->at(0))->method('receivePacket')->with(1400);
        $this->socket->expects($this->at(1))->method('receivePacket')->with()->will($this->returnValue(1400));

        $this->buffer->expects($this->exactly(4))->method('getLong')->will($this->onConsecutiveCalls(-2, 1234, -2, 1234));
        $this->buffer->expects($this->exactly(4))->method('getByte')->will($this->onConsecutiveCalls(0x2, 0x0, 0x2, 0x1));
        $this->buffer->expects($this->exactly(2))->method('getShort');
        $this->buffer->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls("\0\0\0\0A", "\xFF\0\0\0"));

        $reply = $this->socket->getReply();
        $this->assertInstanceOf('\SteamCondenser\Servers\Packets\S2CCHALLENGEPacket', $reply);
        $this->assertEquals(255, $reply->getChallengeNumber());
    }

    public function testCompressedReply() {
        $this->socket->expects($this->at(0))->method('receivePacket')->with(1400);
        $this->socket->expects($this->at(1))->method('receivePacket')->with()->will($this->returnValue(1400));

        $this->buffer->expects($this->exactly(6))->method('getLong')->will($this->onConsecutiveCalls(-2, 2147484882, 0, -2, 2147484882, 0));
        $this->buffer->expects($this->exactly(2))->method('getUnsignedLong')->will($this->returnValue(1570726822));
        $this->buffer->expects($this->exactly(4))->method('getByte')->will($this->onConsecutiveCalls(0x2, 0x0, 0x2, 0x1));
        $this->buffer->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls("BZh91AY&SY\265\217T\317\000\000\001\304\000\300\000 ", "\000\000\000\240\000!&A\230\220..\344\212p\241!k\036\251\236"));

        $reply = $this->socket->getReply();
        $this->assertInstanceOf('\SteamCondenser\Servers\Packets\S2CCHALLENGEPacket', $reply);
        $this->assertEquals(255, $reply->getChallengeNumber());
    }

}
