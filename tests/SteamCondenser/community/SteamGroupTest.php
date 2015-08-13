<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2015, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace SteamCondenser\Community;

/**
 * @author     Sebastian Staudt
 * @covers     SteamGroup
 * @package    steam-condenser
 * @subpackage tests
 */
class SteamGroupTest extends \PHPUnit_Framework_TestCase {

    public function testCacheSteamId64() {
        $this->assertFalse(SteamGroup::isCached('103582791429521412'));

        $steamGroup = new SteamGroup('103582791429521412', false);

        $reflectionObject = new \ReflectionObject($steamGroup);
        $cacheMethod = $reflectionObject->getMethod('cache');
        $cacheMethod->setAccessible(true);
        $cacheMethod->invoke($steamGroup);

        $this->assertTrue(SteamGroup::isCached('103582791429521412'));
    }

    public function testCacheCustomUrl() {
        $this->assertFalse(SteamGroup::isCached('valve'));

        $steamGroup = new SteamGroup('valve', false);

        $reflectionObject = new \ReflectionObject($steamGroup);
        $cacheMethod = $reflectionObject->getMethod('cache');
        $cacheMethod->setAccessible(true);
        $cacheMethod->invoke($steamGroup);

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
        $data = new \SimpleXMLElement(getFixture('valve-members.xml'));
        $mockBuilder = $this->getMockBuilder('\SteamCondenser\Community\SteamGroup');
        $mockBuilder->setConstructorArgs(['valve', false]);
        $mockBuilder->setMethods(['getData']);
        $group = $mockBuilder->getMock();
        $group->expects($this->once())->method('getData')->with('http://steamcommunity.com/groups/valve/memberslistxml?p=1')->will($this->returnValue($data));
        $group->fetch();

        $this->assertEquals('http://media.steampowered.com/steamcommunity/public/images/avatars/1d/1d8baf5a2b5968ae5ca65d7a971c02e222c9a17e_full.jpg', $group->getAvatarFullUrl());
        $this->assertEquals('http://media.steampowered.com/steamcommunity/public/images/avatars/1d/1d8baf5a2b5968ae5ca65d7a971c02e222c9a17e.jpg', $group->getAvatarIconUrl());
        $this->assertEquals('http://media.steampowered.com/steamcommunity/public/images/avatars/1d/1d8baf5a2b5968ae5ca65d7a971c02e222c9a17e_medium.jpg', $group->getAvatarMediumUrl());
        $this->assertEquals('Valve', $group->getCustomUrl());
        $this->assertEquals('VALVE', $group->getHeadline());
        $this->assertEquals(239, $group->getMemberCount());
        $this->assertEquals('Valve', $group->getName());
        $this->assertEquals('In addition to producing best-selling entertainment titles, Valve is a developer of leading-edge technologies such as the Source™ game engine and Steam™, a broadband platform for the delivery and management of digital content.', $group->getSummary());

        $groupMembers = $group->getMembers();
        $this->assertEquals('103582791429521412', $group->getGroupId64());
        $this->assertEquals('76561197985607672', $groupMembers[0]->getSteamId64());
        $this->assertFalse($groupMembers[0]->isFetched());
        $this->assertEquals('76561198086572943', $groupMembers[sizeof($groupMembers) - 1]->getSteamId64());
        $this->assertTrue($group->isFetched());
    }

    public function testMemberCount() {
        $data = new \SimpleXMLElement(getFixture('valve-members.xml'));
        $mockBuilder = $this->getMockBuilder('\SteamCondenser\Community\SteamGroup');
        $mockBuilder->setConstructorArgs(['valve', false]);
        $mockBuilder->setMethods(['getData']);
        $group = $mockBuilder->getMock();
        $group->expects($this->once())->method('getData')->with('http://steamcommunity.com/groups/valve/memberslistxml?p=1')->will($this->returnValue($data));

        $this->assertEquals(239, $group->getMemberCount());
        $this->assertTrue($group->isFetched());
    }

    public function tearDown() {
        SteamId::clearCache();
    }

}
