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
require_once STEAM_CONDENSER_PATH . 'steam/servers/MasterServer.php';

class TestableMasterServer extends MasterServer {

    public $ipAddress;

    public $portNumber;

    public $socket;

}

class MasterServerTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->socket = $this->getMockBuilder('MasterServerSocket')->disableOriginalConstructor()->setMethods(array('getReply', 'send'))->getMock();
        $this->server = $this->getMockBuilder('TestableMasterServer')->disableOriginalConstructor()->setMethods(array('rotateIp'))->getMock();

        $this->server->socket = $this->socket;
    }

    public function testGetChallenge() {
        $this->socket->expects($this->once())->method('send')->with($this->isInstanceOf('C2M_CHECKMD5_Packet'));
        $this->socket->expects($this->once())->method('getReply')->will($this->returnValue(new M2C_ISVALIDMD5_Packet("\0\xFF\0\0\0")));

        $this->assertEquals(255, $this->server->getChallenge());
    }

    public function testGetServers() {
        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2A_SERVER_BATCH_Packet("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->returnValue(new M2A_SERVER_BATCH_Packet("\xA\x7F\0\0\x2\x69\x87\x7F\0\0\x2\x69\x88\0\0\0\0\0\0")));

        $this->assertEquals(array(array('127.0.0.1', 27015), array('127.0.0.1', 27016), array('127.0.0.2', 27015), array('127.0.0.2', 27016)), $this->server->getServers());
    }

    public function testGetServersDisrupted() {
        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2A_SERVER_BATCH_Packet("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(4))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(5))->method('getReply')->will($this->returnValue(new M2A_SERVER_BATCH_Packet("\xA\x7F\0\0\x2\x69\x87\x7F\0\0\x2\x69\x88\0\0\0\0\0\0")));

        $this->assertEquals(array(array('127.0.0.1', 27015), array('127.0.0.1', 27016), array('127.0.0.2', 27015), array('127.0.0.2', 27016)), $this->server->getServers());
    }

    public function testGetServersFailed() {
        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2A_SERVER_BATCH_Packet("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(4))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(5))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(6))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(7))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->server->expects($this->once())->method('rotateIp')->will($this->returnValue(true));

        $this->setExpectedException('TimeoutException');

        $this->server->getServers();
    }

    public function testGetServersForced() {
        MasterServer::setRetries(1);

        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2A_SERVER_BATCH_Packet("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->throwException(new TimeoutException()));

        $this->assertEquals(array(array('127.0.0.1', 27015), array('127.0.0.1', 27016)), $this->server->getServers(MasterServer::REGION_ALL, 'filter', true));
    }

    public function testGetServersSwapIp() {
        $this->socket->expects($this->at(0))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue(new M2A_SERVER_BATCH_Packet("\xA\x7F\0\0\x1\x69\x87\x7F\0\0\x1\x69\x88")));
        $this->socket->expects($this->at(2))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(4))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(5))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->socket->expects($this->at(6))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(7))->method('getReply')->will($this->throwException(new TimeoutException()));
        $this->server->expects($this->exactly(3))->method('rotateIp')->will($this->returnValue(false));
        $this->socket->expects($this->at(8))->method('send')->with($this->isInstanceOf('A2M_GET_SERVERS_BATCH2_Packet'));
        $this->socket->expects($this->at(9))->method('getReply')->will($this->returnValue(new M2A_SERVER_BATCH_Packet("\xA\x7F\0\0\x2\x69\x87\x7F\0\0\x2\x69\x88\0\0\0\0\0\0")));

        $this->assertEquals(array(array('127.0.0.1', 27015), array('127.0.0.1', 27016), array('127.0.0.2', 27015), array('127.0.0.2', 27016)), $this->server->getServers());
    }

    public function testSendHeartbeat() {
        $reply1 = 'reply1';
        $reply2 = 'reply2';
        $this->socket->expects($this->once())->method('send')->with($this->isInstanceOf('S2M_HEARTBEAT2_Packet'));
        $this->socket->expects($this->at(1))->method('getReply')->will($this->returnValue($reply1));
        $this->socket->expects($this->at(2))->method('getReply')->will($this->returnValue($reply2));
        $this->socket->expects($this->at(3))->method('getReply')->will($this->throwException(new TimeoutException()));

        $this->assertEquals(array($reply1, $reply2), $this->server->sendHeartbeat(array('challenge' => 1)));
    }

    public function testSetRetries() {
        MasterServer::setRetries(4);

        $this->assertAttributeEquals(4, 'retries', 'MasterServer');
    }

}
