<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once dirname(__FILE__) . '/../../../lib/steam-condenser.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/SteamGroup.php';

/**
 * @author     Sebastian Staudt
 * @covers     SteamGroup
 * @package    steam-condenser
 * @subpackage tests
 */
class SteamGroupTest extends PHPUnit_Framework_TestCase {

    public function testCacheSteamId64() {
        $this->assertFalse(SteamGroup::isCached('103582791429521412'));

        $steamId = new SteamGroup('103582791429521412', false);
        $steamId->cache();

        $this->assertTrue(SteamGroup::isCached('103582791429521412'));
    }

    public function testCacheCustomUrl() {
        $this->assertFalse(SteamGroup::isCached('valve'));

        $steamId = new SteamGroup('valve', false);
        $steamId->cache();

        $this->assertTrue(SteamGroup::isCached('valve'));
    }

    public function testBaseUrlSteamId64() {
        $group = new SteamGroup('103582791429521412', false);

        $this->assertEquals('103582791429521412', $group->getGroupId64());
        $this->assertEquals('http://steamcommunity.com/gid/103582791429521412', $group->getBaseUrl());
    }

    public function testBaseUrlCustomUrl() {
        $group = new SteamGroup('valve', false);

        $this->assertEquals('valve', $group->getCustomUrl());
        $this->assertEquals('http://steamcommunity.com/groups/valve', $group->getBaseUrl());
    }

    public function testFetchMembers() {
        $data = new SimpleXMLElement(getFixture('valve-members.xml'));
        $mockBuilder = $this->getMockBuilder('SteamGroup');
        $mockBuilder->setConstructorArgs(array('valve', false));
        $mockBuilder->setMethods(array('getData'));
        $group = $mockBuilder->getMock();
        $group->expects($this->once())->method('getData')->with('http://steamcommunity.com/groups/valve/memberslistxml?p=1')->will($this->returnValue($data));
        $group->fetchMembers();

        $groupMembers = $group->getMembers();
        $this->assertEquals('103582791429521412', $group->getGroupId64());
        $this->assertEquals('76561197960265740', $groupMembers[0]->getSteamId64());
        $this->assertFalse($groupMembers[0]->isFetched());
        $this->assertEquals('76561197970323416', $groupMembers[sizeof($groupMembers) - 1]->getSteamId64());
        $this->assertTrue($group->isFetched());
    }

    public function testMemberCount() {
        $data = new SimpleXMLElement(getFixture('valve-members.xml'));
        $mockBuilder = $this->getMockBuilder('SteamGroup');
        $mockBuilder->setConstructorArgs(array('valve', false));
        $mockBuilder->setMethods(array('getData'));
        $group = $mockBuilder->getMock();
        $group->expects($this->once())->method('getData')->with('http://steamcommunity.com/groups/valve/memberslistxml')->will($this->returnValue($data));

        $this->assertEquals(221, $group->getMemberCount());
        $this->assertFalse($group->isFetched());
    }

    public function tearDown() {
        SteamId::clearCache();
    }

}
