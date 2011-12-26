<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

error_reporting(E_ALL & ~E_USER_NOTICE);

require_once dirname(__FILE__) . '/../lib/steam-condenser.php';

/**
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage tests
 */
class QueryTest extends PHPUnit_Framework_TestCase {

    /**
     * @expectedException TimeoutException
     */
    public function testInvalidGoldSrcServer() {
        $server = new GoldSrcServer('1.0.0.0', 27015);
        $server->getPing();
    }

    /**
     * @expectedException TimeoutException
     */
    public function testInvalidSourceServer() {
        $server = new SourceServer('1.0.0.0', 27015);
        $server->getPing();
    }

    public function testRandomGoldsrcServer() {
        $masterServer = new MasterServer(MasterServer::GOLDSRC_MASTER_SERVER);
        $serverArray = $masterServer->getServers(MasterServer::REGION_ALL, '\type\d\empty\1\full\1\gamedir\valve');
        $serverAddress = $serverArray[array_rand($serverArray)];
        $server = new GoldSrcServer($serverAddress[0], $serverAddress[1]);
        $server->initialize();
        $server->updatePlayers();
        $server->updateRules();

        $this->assertNotNull($server->getPing());
        $this->assertNotNull($server->getPlayers());
        $this->assertNotNull($server->getRules());
        $this->assertNotNull($server->getServerInfo());

        echo $server;
    }

    public function testRandomSourceServer() {
        $masterServer = new MasterServer(MasterServer::SOURCE_MASTER_SERVER);
        $serverArray = $masterServer->getServers(MasterServer::REGION_ALL, '\type\d\empty\1\full\1\gamedir\tf');
        $serverAddress = $serverArray[array_rand($serverArray)];
        $server = new SourceServer($serverAddress[0], $serverAddress[1]);
        $server->initialize();
        $server->updatePlayers();
        $server->updateRules();

        $this->assertNotNull($server->getPing());
        $this->assertNotNull($server->getPlayers());
        $this->assertNotNull($server->getRules());
        $this->assertNotNull($server->getServerInfo());

        echo $server;
    }
}
