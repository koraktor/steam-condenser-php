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

use SteamCondenser\Servers\Packets\RCON\RCONAuthResponse;
use SteamCondenser\Servers\Packets\RCON\RCONExecResponse;

class TestableSourceServer extends SourceServer {

    public $rconAuthenticated;

    public $rconRequestId;

    public $rconSocket;

    public function generateRconRequestId() {
        parent::generateRconRequestId();
    }

}

class SourceServerTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->rconSocket = $this->getMockBuilder('\SteamCondenser\Servers\Sockets\RCONSocket')->disableOriginalConstructor()->setMethods(['close', 'getReply', 'send'])->getMock();
    }

    public function testDisconnect() {
        $this->rconSocket->expects($this->exactly(2))->method('close');

        $server = new TestableSourceServer('127.0.0.1');
        $server->rconSocket = $this->rconSocket;

        $server->disconnect();
    }

    public function testGenerateRconRequestId() {
        $server = new TestableSourceServer('127.0.0.1');
        $requestId = $server->generateRconRequestId();

        $this->assertTrue($requestId >= 0);
        $this->assertTrue($requestId < pow(1, 16));
    }

    public function testRconAuth() {
        $this->rconSocket->expects($this->once())->method('send')->with($this->logicalAnd($this->isInstanceOf('\SteamCondenser\Servers\Packets\RCON\RCONAuthRequest'), $this->attributeEqualTo('requestId', 1234)));
        $this->rconSocket->expects($this->exactly(2))->method('getReply')->will($this->returnValue(new RCONAuthResponse(1234)));
        $server = $this->getMockBuilder('\SteamCondenser\Servers\TestableSourceServer')->disableOriginalConstructor()->setMethods(['generateRconRequestId'])->getMock();
        $server->rconSocket = $this->rconSocket;
        $server->expects($this->once())->method('generateRconRequestId')->will($this->returnValue(1234));

        $this->assertTrue($server->rconAuth('password'));
    }

    public function testRconExecEmpty() {
        $this->rconSocket->expects($this->once())->method('send')->with($this->logicalAnd($this->isInstanceOf('\SteamCondenser\Servers\Packets\RCON\RCONExecRequest'), $this->attributeEqualTo('requestId', 1234)));
        $this->rconSocket->expects($this->once())->method('getReply')->will($this->returnValue(new RCONExecResponse(1234, '')));
        $server = new TestableSourceServer('127.0.0.1');
        $server->rconAuthenticated = true;
        $server->rconRequestId = 1234;
        $server->rconSocket = $this->rconSocket;

        $this->assertEquals('', $server->rconExec('testx'));
    }

    public function testRconExecLongReply() {
        $this->rconSocket->expects($this->at(0))->method('send')->with($this->logicalAnd($this->isInstanceOf('\SteamCondenser\Servers\Packets\RCON\RCONExecRequest'), $this->attributeEqualTo('requestId', 1234)));
        $this->rconSocket->expects($this->at(1))->method('getReply')->will($this->returnValue(new RCONExecResponse(1234, 'test ')));
        $this->rconSocket->expects($this->at(2))->method('send')->with($this->logicalAnd($this->isInstanceOf('\SteamCondenser\Servers\Packets\RCON\RCONTerminator'), $this->attributeEqualTo('requestId', 1234)));
        $this->rconSocket->expects($this->at(3))->method('getReply')->will($this->returnValue(new RCONExecResponse(1234, 'test')));
        $this->rconSocket->expects($this->at(4))->method('getReply')->will($this->returnValue(new RCONExecResponse(1234, '')));
        $this->rconSocket->expects($this->at(5))->method('getReply')->will($this->returnValue(new RCONExecResponse(1234, '')));
        $server = new TestableSourceServer('127.0.0.1');
        $server->rconAuthenticated = true;
        $server->rconRequestId = 1234;
        $server->rconSocket = $this->rconSocket;

        $this->assertEquals('test test', $server->rconExec('test'));
    }

    public function testRconExecNoAuth() {
        $server = new SourceServer('127.0.0.1');
        $this->setExpectedException('\SteamCondenser\Exceptions\RCONNoAuthException');

        $server->rconExec('test');
    }

    public function testRconExecInvalidAuth() {
        $this->rconSocket->expects($this->once())->method('send')->with($this->logicalAnd($this->isInstanceOf('\SteamCondenser\Servers\Packets\RCON\RCONExecRequest'), $this->attributeEqualTo('requestId', 1234)));
        $this->rconSocket->expects($this->once())->method('getReply')->will($this->returnValue(new RCONAuthResponse(1234)));
        $server = new TestableSourceServer('127.0.0.1');
        $server->rconAuthenticated = true;
        $server->rconRequestId = 1234;
        $server->rconSocket = $this->rconSocket;

        $this->setExpectedException('\SteamCondenser\Exceptions\RCONNoAuthException');

        $server->rconExec('test');
    }

}
