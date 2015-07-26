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

class TestableServer extends Server {

    public $ipAddress;

    public $ipAddresses;

    public function initSocket() {}

}

class ServerTest extends \PHPUnit_Framework_TestCase {

    public function testRotateIp() {
        $server = $this->getMockBuilder('\SteamCondenser\Servers\TestableServer')->disableOriginalConstructor()->setMethods(['initSocket'])->getMock();
        $server->ipAddresses = ['127.0.0.1', '127.0.0.2'];
        $server->ipAddress = '127.0.0.1';
        $server->expects($this->exactly(2))->method('initSocket');

        $this->assertFalse($server->rotateIp());
        $this->assertEquals('127.0.0.2', $server->ipAddress);
        $this->assertTrue($server->rotateIp());
        $this->assertEquals('127.0.0.1', $server->ipAddress);
    }

}
