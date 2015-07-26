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

use SteamCondenser\Exceptions\RCONNoAuthException;

class TestableGoldSrcServer extends GoldSrcServer {

    public $rconAuthenticated;

    public $rconPassword;

    public $socket;

}

class GoldSrcServerTest extends \PHPUnit_Framework_TestCase {

    public function testRconAuthFailed() {
        $socket = $this->getMockBuilder('\SteamCondenser\UDPSocket')->setMethods(['rconExec'])->disableOriginalConstructor()->getMock();
        $socket->expects($this->once())->method('rconExec')->with('password', '')->will($this->throwException(new RCONNoAuthException()));
        $server = new TestableGoldSrcServer('127.0.0.1');
        $server->socket = $socket;

        $this->assertFalse($server->rconAuth('password'));
        $this->assertNull($server->rconPassword);
    }

    public function testRconAuthSuccessful() {
        $socket = $this->getMockBuilder('\SteamCondenser\UDPSocket')->setMethods(['rconExec'])->disableOriginalConstructor()->getMock();
        $socket->expects($this->once())->method('rconExec')->with('password', '')->will($this->returnValue(''));
        $server = new TestableGoldSrcServer('127.0.0.1');
        $server->socket = $socket;

        $this->assertTrue($server->rconAuth('password'));
        $this->assertEquals('password', $server->rconPassword);
    }

    public function testRconExec() {
        $socket = $this->getMockBuilder('\SteamCondenser\UDPSocket')->setMethods(['rconExec'])->disableOriginalConstructor()->getMock();
        $socket->expects($this->once())->method('rconExec')->with('password', 'command')->will($this->returnValue('test'));
        $server = new TestableGoldSrcServer('127.0.0.1');
        $server->rconAuthenticated = true;
        $server->rconPassword = 'password';
        $server->socket = $socket;

        $this->assertEquals('test', $server->rconExec('command'));
    }

}
