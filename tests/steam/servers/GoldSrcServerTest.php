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
require_once STEAM_CONDENSER_PATH . 'steam/servers/GoldSrcServer.php';

class TestableGoldSrcServer extends GoldSrcServer {

    public $rconPassword;

    public $socket;

}

class GoldSrcServerTest extends PHPUnit_Framework_TestCase {

    public function testRconAuth() {
        $server = new TestableGoldSrcServer('127.0.0.1');

        $this->assertTrue($server->rconAuth('password'));
        $this->assertEquals('password', $server->rconPassword);
    }

    public function testRconExec() {
        $socket = $this->getMockBuilder('UDPSocket')->setMethods(array('rconExec'))->disableOriginalConstructor()->getMock();
        $socket->expects($this->once())->method('rconExec')->with('password', 'command')->will($this->returnValue('test'));
        $server = new TestableGoldSrcServer('127.0.0.1');
        $server->rconPassword = 'password';
        $server->socket = $socket;

        $this->assertEquals('test', $server->rconExec('command'));
    }

}
