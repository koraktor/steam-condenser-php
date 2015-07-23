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

use SteamCondenser\Servers\Packets\RCON\RCONGoldSrcRequest;
use SteamCondenser\Servers\Packets\RCON\RCONGoldSrcResponse;

class GoldSrcSocketTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->socketBuilder = $this->getMockBuilder('\SteamCondenser\Servers\Sockets\GoldSrcSocket');
        $this->socketBuilder->setConstructorArgs(['127.0.0.1']);
    }

    public function testHLTV() {
        $socket1 = $this->socketBuilder->getMock();
        $this->socketBuilder->setConstructorArgs(['127.0.0.1', 27015, true]);
        $socket2 = $this->socketBuilder->getMock();

        $this->assertAttributeEquals(false, "isHLTV", $socket1);
        $this->assertAttributeEquals(true, "isHLTV", $socket2);
    }

    public function testRconSend() {
        $packet = new RCONGoldSrcRequest('test');
        $this->socketBuilder->setMethods(['close', 'send']);
        $socket = $this->socketBuilder->getMock();
        $socket->expects($this->once())->method('send')->with($packet);

        $socket->rconSend('test');
    }

    public function testRconChallenge() {
        $this->socketBuilder->setMethods(['close', 'getReply', 'rconSend']);
        $socket = $this->socketBuilder->getMock();
        $socket->expects($this->once())->method('rconSend')->with('challenge rcon');

        $reply = new RCONGoldSrcResponse("challenge rcon 12345678\0\0");
        $socket->expects($this->once())->method('getReply')->will($this->returnValue($reply));

        $socket->rconGetChallenge();

        $this->assertAttributeEquals(12345678, 'rconChallenge', $socket);
    }

    public function testBannedChallenge() {
        $this->socketBuilder->setMethods(['close', 'getReply', 'rconSend']);
        $socket = $this->socketBuilder->getMock();
        $socket->expects($this->once())->method('rconSend')->with('challenge rcon');

        $reply = new RCONGoldSrcResponse("You have been banned from this server.\0\0");
        $socket->expects($this->once())->method('getReply')->will($this->returnValue($reply));
        $this->setExpectedException('\SteamCondenser\Exceptions\RCONBanException');

        $socket->rconGetChallenge();
    }

    public function testSinglePacket() {
        $this->socketBuilder->setMethods(['receivePacket']);
        $socket = $this->socketBuilder->getMock();
        $socket->expects($this->once())->method('receivePacket')->with(1400);

        $bufferBuilder = $this->getMockBuilder('\SteamCondenser\ByteBuffer');
        $bufferBuilder->setMethods(['get', 'getLong']);
        $bufferBuilder->disableOriginalConstructor();
        $buffer = $bufferBuilder->getMock();
        $reflectionSocket = new \ReflectionObject($socket);
        $bufferProperty = $reflectionSocket->getProperty('buffer');
        $bufferProperty->setAccessible(true);
        $bufferProperty->setValue($socket, $buffer);
        $data = 'A';
        $buffer->expects($this->once())->method('getLong')->will($this->returnValue(0xFFFFFFFF));
        $buffer->expects($this->once())->method('get')->will($this->returnValue($data));

        $packetBuilder = $this->getMockBuilder('\SteamCondenser\Servers\Packets\SteamPacket');
        $packetBuilder->disableOriginalConstructor();

        $this->assertInstanceOf('\SteamCondenser\Servers\Packets\S2CCHALLENGEPacket', $socket->getReply());
    }

    public function testSplitPackets() {
        $this->socketBuilder->setMethods(['receivePacket']);
        $socket = $this->socketBuilder->getMock();
        $socket->expects($this->at(0))->method('receivePacket')->with(1400);
        $socket->expects($this->at(1))->method('receivePacket')->with()->will($this->returnValue(1400));

        $bufferBuilder = $this->getMockBuilder('\SteamCondenser\ByteBuffer');
        $bufferBuilder->setMethods(['get', 'getByte', 'getLong']);
        $bufferBuilder->disableOriginalConstructor();
        $buffer = $bufferBuilder->getMock();
        $reflectionSocket = new \ReflectionObject($socket);
        $bufferProperty = $reflectionSocket->getProperty('buffer');
        $bufferProperty->setAccessible(true);
        $bufferProperty->setValue($socket, $buffer);
        $data1 = "XXXXA";
        $data2 = "\0";
        $buffer->expects($this->exactly(4))->method('getLong')->will($this->onConsecutiveCalls(-2, 1234, -2, 1234));
        $buffer->expects($this->exactly(2))->method('getByte')->will($this->onConsecutiveCalls(0x02, 0x12));
        $buffer->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls($data1, $data2));

        $packetBuilder = $this->getMockBuilder('\SteamCondenser\Servers\Packets\SteamPacket');
        $packetBuilder->disableOriginalConstructor();

        $this->assertInstanceOf('\SteamCondenser\Servers\Packets\S2CCHALLENGEPacket', $socket->getReply());
    }

    public function testRconExec() {
        $this->socketBuilder->setMethods(['close', 'getReply', 'rconGetChallenge', 'rconSend']);
        $socket = $this->socketBuilder->getMock();
        $socket->expects($this->once())->method('rconGetChallenge');
        $socket->expects($this->at(1))->method('rconSend')->with('rcon -1 password command');
        $socket->expects($this->at(3))->method('rconSend')->with('rcon -1 password');

        $packet1 = new RCONGoldSrcResponse("test \0\0");
        $packet2 = new RCONGoldSrcResponse("test\0\0");
        $packet3 = new RCONGoldSrcResponse("\0\0");

        $socket->expects($this->exactly(3))->method('getReply')->will($this->onConsecutiveCalls($packet1, $packet2, $packet3));

        $this->assertEquals('test test', $socket->rconExec('password', 'command'));
    }

    public function testRconExecHLTV() {
        $this->socketBuilder->setConstructorArgs(['127.0.0.1', 27015, true]);
        $this->socketBuilder->setMethods(['close', 'getReply', 'rconGetChallenge', 'rconSend']);
        $socket = $this->socketBuilder->getMock();
        $socket->expects($this->once())->method('rconGetChallenge');
        $socket->expects($this->at(1))->method('rconSend')->with('rcon -1 password command');
        $socket->expects($this->at(3))->method('rconSend')->with('rcon -1 password');

        $packet1 = new RCONGoldSrcResponse("test \0\0");
        $packet2 = new RCONGoldSrcResponse("test\0\0");
        $packet3 = new RCONGoldSrcResponse("\0\0");

        $socket->expects($this->at(2))->method('getReply')->will($this->throwException(new \SteamCondenser\Exceptions\TimeoutException()));
        $socket->expects($this->at(4))->method('getReply')->will($this->returnValue($packet1));
        $socket->expects($this->at(5))->method('getReply')->will($this->returnValue($packet2));
        $socket->expects($this->at(6))->method('getReply')->will($this->returnValue($packet3));

        $this->assertEquals('test test', $socket->rconExec('password', 'command'));
    }

}
