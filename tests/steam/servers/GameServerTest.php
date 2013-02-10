<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once STEAM_CONDENSER_PATH . 'steam/servers/GameServer.php';

abstract class TestableGameServer extends GameServer {

    public $challengeNumber;

    public $infoHash;

    public $ping;

    public $playerHash;

    public $rconAuthenticated;

    public $rulesHash;

    public $socket;

    public function getReply() {
        return parent::getReply();
    }

    public function handleResponseForRequest($request, $repeatOnFailure = true) {
        parent::handleResponseForRequest($request, $repeatOnFailure);
    }

    public function sendRequest(SteamPacket $packet) {
        parent::sendRequest($packet);
    }

}

class GameServerTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->serverBuilder = $this->getMockBuilder('TestableGameServer');
        $this->serverBuilder->disableOriginalConstructor();
    }

    public function testGetReply() {
        $packet = $this->getMockForAbstractClass('SteamPacket', array(''));

        $socket = $this->getMockBuilder('SteamSocket')->disableOriginalConstructor()->setMethods(array('getReply'))->getMock();
        $socket->expects($this->once())->method('getReply')->will($this->returnValue($packet));
        $server = $this->getMockForAbstractClass('TestableGameServer', array('127.0.0.1'));
        $server->socket = $socket;

        $this->assertEquals($packet, $server->getReply());
    }

    public function testSendRequest() {
        $packet = $this->getMockForAbstractClass('SteamPacket', array(''));

        $socket = $this->getMockBuilder('SteamSocket')->disableOriginalConstructor()->setMethods(array('getReply', 'send'))->getMock();
        $socket->expects($this->once())->method('send')->with($packet);
        $server = $this->getMockForAbstractClass('TestableGameServer', array('127.0.0.1'));
        $server->socket = $socket;

        $server->sendRequest($packet);
    }

    public function testUpdatePing() {
        $server = $this->serverBuilder->setMethods(array('getReply', 'initSocket', 'rconAuth', 'rconExec', 'sendRequest'))->getMock();
        $server->expects($this->once())->method('sendRequest')->with($this->isInstanceOf('A2S_INFO_Packet'));
        $server->expects($this->once())->method('getReply')->will($this->returnCallback(function() { usleep(50000); }));

        $server->updatePing();
        $this->assertAttributeGreaterThanOrEqual(50, 'ping', $server);
    }

    public function testUpdateChallengeNumber() {
        $server = $this->serverBuilder->setMethods(array('handleResponseForRequest', 'initSocket', 'rconAuth', 'rconExec'))->getMock();
        $server->expects($this->once())->method('handleResponseForRequest')->with($this->equalTo(GameServer::REQUEST_CHALLENGE));

        $server->updateChallengeNumber();
    }

    public function testUpdateServerInfo() {
        $server = $this->serverBuilder->setMethods(array('handleResponseForRequest', 'initSocket', 'rconAuth', 'rconExec'))->getMock();
        $server->expects($this->once())->method('handleResponseForRequest')->with($this->equalTo(GameServer::REQUEST_INFO));

        $server->updateServerInfo();
    }

    public function testUpdatePlayers() {
        $server = $this->serverBuilder->setMethods(array('handleResponseForRequest', 'initSocket', 'rconAuth', 'rconExec'))->getMock();
        $server->expects($this->once())->method('handleResponseForRequest')->with($this->equalTo(GameServer::REQUEST_PLAYER));

        $server->updatePlayers();
    }

    public function testUpdateRules() {
        $server = $this->serverBuilder->setMethods(array('handleResponseForRequest', 'initSocket', 'rconAuth', 'rconExec'))->getMock();
        $server->expects($this->once())->method('handleResponseForRequest')->with($this->equalTo(GameServer::REQUEST_RULES));

        $server->updateRules();
    }

    public function testInitialize() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'rconAuth', 'rconExec', 'updateChallengeNumber', 'updatePing', 'updateServerInfo'))->getMock();
        $server->expects($this->once())->method('updateChallengeNumber');
        $server->expects($this->once())->method('updatePing');
        $server->expects($this->once())->method('updateServerInfo');

        $server->initialize();
    }

    public function testIsRconAuthenticated() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'rconAuth', 'rconExec'))->getMock();

        $this->assertEquals($this->readAttribute($server, 'rconAuthenticated'), $server->isRconAuthenticated());
    }

    public function testCachePing() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'rconAuth', 'rconExec', 'updatePing'))->getMock();
        $server->expects($this->once())->method('updatePing');

        $server->getPing();
        $server->ping = 1;
        $server->getPing();
    }

    public function testCachePlayers() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'rconAuth', 'rconExec', 'updatePlayers'))->getMock();
        $server->expects($this->once())->method('updatePlayers');

        $server->getPlayers();
        $server->playerHash = 1;
        $server->getPlayers();
    }

    public function testCacheRules() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'rconAuth', 'rconExec', 'updateRules'))->getMock();
        $server->expects($this->once())->method('updateRules');

        $server->getRules();
        $server->rulesHash = 1;
        $server->getRules();
    }

    public function testCacheServerInfo() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'rconAuth', 'rconExec', 'updateServerInfo'))->getMock();
        $server->expects($this->once())->method('updateServerInfo');

        $server->getServerInfo();
        $server->infoHash = 1;
        $server->getServerInfo();
    }

    public function testGetPlayerInfoFromSourceWithPassword() {
        $status = getFixture('status_source');
        $server = $this->serverBuilder->setMethods(array('handleResponseForRequest', 'initSocket', 'rconAuth', 'rconExec'))->getMock();
        $someone = $this->getMock('stdClass', array('addInformation'));
        $somebody = $this->getMock('stdClass', array('addInformation'));

        $playerHash = array('someone' => $someone, 'somebody' => $somebody);
        $server->playerHash = $playerHash;

        $server->expects($this->once())->method('handleResponseForRequest')->with($this->equalTo(GameServer::REQUEST_PLAYER));
        $server->expects($this->once())->method('rconExec')->with($this->equalTo('status'))->will($this->returnValue($status));

        $someoneData = array('name' => 'someone', 'userid' => '1', 'uniqueid' => 'STEAM_0:0:123456', 'score' => '10', 'time' => '3:52', 'ping' => '12', 'loss' => '0', 'state' => 'active');
        $somebodyData = array('name' => 'somebody', 'userid' => '2', 'uniqueid' => 'STEAM_0:0:123457', 'score' => '3', 'time' => '2:42', 'ping' => '34', 'loss' => '0', 'state' => 'active');

        $somebody->expects($this->once())->method('addInformation')->with($this->equalTo($somebodyData));
        $someone->expects($this->once())->method('addInformation')->with($this->equalTo($someoneData));

        $server->expects($this->once())->method('rconAuth')->with($this->equalTo('password'));

        $server->updatePlayers('password');
    }

    public function testGetPlayerInfoFromSourceAuthenticated() {
        $status = getFixture('status_source');
        $server = $this->serverBuilder->setMethods(array('handleResponseForRequest', 'initSocket', 'rconAuth', 'rconExec'))->getMock();
        $someone = $this->getMock('stdClass', array('addInformation'));
        $somebody = $this->getMock('stdClass', array('addInformation'));

        $playerHash = array('someone' => $someone, 'somebody' => $somebody);
        $server->playerHash = $playerHash;

        $server->expects($this->once())->method('handleResponseForRequest')->with($this->equalTo(GameServer::REQUEST_PLAYER));
        $server->expects($this->once())->method('rconExec')->with($this->equalTo('status'))->will($this->returnValue($status));

        $someoneData = array('name' => 'someone', 'userid' => '1', 'uniqueid' => 'STEAM_0:0:123456', 'score' => '10', 'time' => '3:52', 'ping' => '12', 'loss' => '0', 'state' => 'active');
        $somebodyData = array('name' => 'somebody', 'userid' => '2', 'uniqueid' => 'STEAM_0:0:123457', 'score' => '3', 'time' => '2:42', 'ping' => '34', 'loss' => '0', 'state' => 'active');

        $somebody->expects($this->once())->method('addInformation')->with($this->equalTo($somebodyData));
        $someone->expects($this->once())->method('addInformation')->with($this->equalTo($someoneData));

        $server->rconAuthenticated = true;
        $server->updatePlayers();
    }

    public function testGetPlayerInfoFromGoldSrcWithPassword() {
        $status = getFixture('status_goldsrc');
        $server = $this->serverBuilder->setMethods(array('handleResponseForRequest', 'initSocket', 'rconAuth', 'rconExec'))->getMock();
        $someone = $this->getMock('stdClass', array('addInformation'));
        $somebody = $this->getMock('stdClass', array('addInformation'));

        $playerHash = array('someone' => $someone, 'somebody' => $somebody);
        $server->playerHash = $playerHash;

        $server->expects($this->once())->method('handleResponseForRequest')->with($this->equalTo(GameServer::REQUEST_PLAYER));
        $server->expects($this->once())->method('rconExec')->with($this->equalTo('status'))->will($this->returnValue($status));

        $someoneData = array('name' => 'someone', 'userid' => '1', 'uniqueid' => 'STEAM_0:0:123456', 'score' => '10', 'time' => '3:52', 'ping' => '12', 'loss' => '0', 'adr' => '0');
        $somebodyData = array('name' => 'somebody', 'userid' => '2', 'uniqueid' => 'STEAM_0:0:123457', 'score' => '3', 'time' => '2:42', 'ping' => '34', 'loss' => '0', 'adr' => '0');

        $somebody->expects($this->once())->method('addInformation')->with($this->equalTo($somebodyData));
        $someone->expects($this->once())->method('addInformation')->with($this->equalTo($someoneData));

        $server->expects($this->once())->method('rconAuth')->with($this->equalTo('password'));

        $server->updatePlayers('password');
    }

    public function testGetPlayerInfoFromGoldSrcAuthenticated() {
        $status = getFixture('status_goldsrc');
        $server = $this->serverBuilder->setMethods(array('handleResponseForRequest', 'initSocket', 'rconAuth', 'rconExec'))->getMock();
        $someone = $this->getMock('stdClass', array('addInformation'));
        $somebody = $this->getMock('stdClass', array('addInformation'));

        $playerHash = array('someone' => $someone, 'somebody' => $somebody);
        $server->playerHash = $playerHash;

        $server->expects($this->once())->method('handleResponseForRequest')->with($this->equalTo(GameServer::REQUEST_PLAYER));
        $server->expects($this->once())->method('rconExec')->with($this->equalTo('status'))->will($this->returnValue($status));

        $someoneData = array('name' => 'someone', 'userid' => '1', 'uniqueid' => 'STEAM_0:0:123456', 'score' => '10', 'time' => '3:52', 'ping' => '12', 'loss' => '0', 'adr' => '0');
        $somebodyData = array('name' => 'somebody', 'userid' => '2', 'uniqueid' => 'STEAM_0:0:123457', 'score' => '3', 'time' => '2:42', 'ping' => '34', 'loss' => '0', 'adr' => '0');

        $somebody->expects($this->once())->method('addInformation')->with($this->equalTo($somebodyData));
        $someone->expects($this->once())->method('addInformation')->with($this->equalTo($someoneData));

        $server->rconAuthenticated = true;
        $server->updatePlayers();
    }

    public function testHandleChallengeRequest() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'getReply', 'rconAuth', 'rconExec', 'sendRequest'))->getMock();
        $server->expects($this->once())->method('sendRequest')->with($this->isInstanceOf('A2S_PLAYER_Packet'));

        $packet = $this->getMockBuilder('S2C_CHALLENGE_Packet')->disableOriginalConstructor()->setMethods(array('getChallengeNumber'))->getMock();
        $packet->expects($this->once())->method('getChallengeNumber')->will($this->returnValue(1234));
        $server->expects($this->once())->method('getReply')->will($this->returnValue($packet));

        $server->handleResponseForRequest(GameServer::REQUEST_CHALLENGE);

        $this->assertEquals(1234, $server->challengeNumber);
    }

    public function testHandleInfoRequest() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'getReply', 'rconAuth', 'rconExec', 'sendRequest'))->getMock();
        $server->expects($this->once())->method('sendRequest')->with($this->isInstanceOf('A2S_INFO_Packet'));

        $packet = $this->getMockBuilder('S2A_INFO_BasePacket')->disableOriginalConstructor()->setMethods(array('getInfo'))->getMock();
        $packet->expects($this->once())->method('getInfo')->will($this->returnValue(array('test' => 'test')));
        $server->expects($this->once())->method('getReply')->will($this->returnValue($packet));

        $server->handleResponseForRequest(GameServer::REQUEST_INFO);

        $this->assertEquals(array('test' => 'test'), $server->infoHash);
    }

    public function testHandlePlayersRequest() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'getReply', 'rconAuth', 'rconExec', 'sendRequest'))->getMock();
        $server->expects($this->once())->method('sendRequest')->with($this->isInstanceOf('A2S_PLAYER_Packet'));

        $packet = $this->getMockBuilder('S2A_PLAYER_Packet')->disableOriginalConstructor()->setMethods(array('getPlayerHash'))->getMock();
        $packet->expects($this->once())->method('getPlayerHash')->will($this->returnValue(array('test' => 'test')));
        $server->expects($this->once())->method('getReply')->will($this->returnValue($packet));

        $server->handleResponseForRequest(GameServer::REQUEST_PLAYER);

        $this->assertEquals(array('test' => 'test'), $server->playerHash);
    }

    public function testHandleRulesRequest() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'getReply', 'rconAuth', 'rconExec', 'sendRequest'))->getMock();
        $server->expects($this->once())->method('sendRequest')->with($this->isInstanceOf('A2S_RULES_Packet'));

        $packet = $this->getMockBuilder('S2A_RULES_Packet')->disableOriginalConstructor()->setMethods(array('getRulesArray'))->getMock();
        $packet->expects($this->once())->method('getRulesArray')->will($this->returnValue(array('test' => 'test')));
        $server->expects($this->once())->method('getReply')->will($this->returnValue($packet));

        $server->handleResponseForRequest(GameServer::REQUEST_RULES);

        $this->assertEquals(array('test' => 'test'), $server->rulesHash);
    }

    public function testHandleUnexpectedResponse() {
        $server = $this->serverBuilder->setMethods(array('initSocket', 'getReply', 'rconAuth', 'rconExec', 'sendRequest'))->getMock();
        $server->expects($this->exactly(2))->method('sendRequest')->with($this->isInstanceOf('A2S_PLAYER_Packet'));

        $packet1 = $this->getMockBuilder('S2C_CHALLENGE_Packet')->disableOriginalConstructor()->setMethods(array('getChallengeNumber'))->getMock();
        $packet1->expects($this->once())->method('getChallengeNumber')->will($this->returnValue(1234));
        $server->expects($this->at(1))->method('getReply')->will($this->returnValue($packet1));
        $packet2 = $this->getMockBuilder('S2A_PLAYER_Packet')->disableOriginalConstructor()->setMethods(array('getPlayerHash'))->getMock();
        $packet2->expects($this->once())->method('getPlayerHash')->will($this->returnValue(array('test' => 'test')));
        $server->expects($this->at(3))->method('getReply')->will($this->returnValue($packet2));

        $server->handleResponseForRequest(GameServer::REQUEST_PLAYER);

        $this->assertEquals(1234, $server->challengeNumber);
        $this->assertEquals(array('test' => 'test'), $server->playerHash);
    }

}
