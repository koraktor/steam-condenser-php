<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Servers;

use SteamCondenser\Exceptions\TimeoutException;
use SteamCondenser\Servers\Packets\M2ASERVERBATCHPacket;

class TestableMasterServer extends MasterServer {

    public $ipAddress;

    public $portNumber;

    public $socket;

}

class MasterServerTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->socket = $this->getMockBuilder('\SteamCondenser\Servers\Sockets\MasterServerSocket')->disableOriginalConstructor()->setMethods(['getReply', 'send'])->getMock();
        $this->server = $this->getMockBuilder('\SteamCondenser\Servers\TestableMasterServer')->disableOriginalConstructor()->setMethods(['rotateIp'])->getMock();
        $this->server->setLogger(\SteamCondenser\getLogger(get_class($this->server)));

        $this->server->socket = $this->socket;
    }

    public function testGetServers() {
        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2ASERVERBATCHPacket("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->returnValue(new M2ASERVERBATCHPacket("\xA\x7F\0\0\x2\x69\x87\x7F\0\0\x2\x69\x88\0\0\0\0\0\0")));

        $this->assertEquals([['127.0.0.1', 27015], ['127.0.0.1', 27016], ['127.0.0.2', 27015], ['127.0.0.2', 27016]], $this->server->getServers());
    }

    public function testGetServersDisrupted() {
        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2ASERVERBATCHPacket("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(4))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(5))->method('getReply')->will($this->returnValue(new M2ASERVERBATCHPacket("\xA\x7F\0\0\x2\x69\x87\x7F\0\0\x2\x69\x88\0\0\0\0\0\0")));

        $this->assertEquals([['127.0.0.1', 27015], ['127.0.0.1', 27016], ['127.0.0.2', 27015], ['127.0.0.2', 27016]], $this->server->getServers());
    }

    public function testGetServersFailed() {
        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2ASERVERBATCHPacket("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(4))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(5))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(6))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(7))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->server->expects($this->once())->method('rotateIp')->will($this->returnValue(true));

        $this->setExpectedException('\SteamCondenser\Exceptions\TimeoutException');

        $this->server->getServers();
    }

    public function testGetServersForced() {
        MasterServer::setRetries(1);

        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2ASERVERBATCHPacket("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->server->expects($this->exactly(1))->method('rotateIp')->will($this->returnValue(true));

        $this->assertEquals([['127.0.0.1', 27015], ['127.0.0.1', 27016]], $this->server->getServers(MasterServer::REGION_ALL, 'filter', true));
    }

    public function testGetServersSwapIp() {
        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2ASERVERBATCHPacket("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(4))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(5))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(6))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(7))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->server->expects($this->exactly(3))->method('rotateIp')->will($this->returnValue(false));
        $this->socket->expects($this->at(8))->method('send')->with($this->isInstanceOf('\SteamCondenser\Servers\Packets\A2MGETSERVERSBATCH2Packet'));
        $this->socket->expects($this->at(9))->method('getReply')->will($this->returnValue(new M2ASERVERBATCHPacket("\xA\x7F\0\0\x2\x69\x87\x7F\0\0\x2\x69\x88\0\0\0\0\0\0")));

        $this->assertEquals([['127.0.0.1', 27015], ['127.0.0.1', 27016], ['127.0.0.2', 27015], ['127.0.0.2', 27016]], $this->server->getServers());
    }

    public function testSetRetries() {
        MasterServer::setRetries(4);

        $this->assertAttributeEquals(4, 'retries', '\SteamCondenser\Servers\MasterServer');
    }

}
