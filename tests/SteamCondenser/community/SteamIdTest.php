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
 * @covers     SteamId
 * @package    steam-condenser
 * @subpackage tests
 */
class SteamIdTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->webApiInstance = new \ReflectionProperty('\SteamCondenser\Community\WebApi', 'instance');
        $this->webApiInstance->setAccessible(true);
    }

    public function testConvertCommunityIdToSteamId() {
        $steamId = SteamId::convertCommunityIdToSteamId('76561197960290418');
        $this->assertEquals('STEAM_0:0:12345', $steamId);
    }

    public function testConvertCommunityIdToSteamId3() {
        $steamId = SteamId::convertCommunityIdToSteamId3('76561197960497430');
        $this->assertEquals('[U:1:231702]', $steamId);

        $steamId = SteamId::convertCommunityIdToSteamId3('76561197998273743');
        $this->assertEquals('[U:1:38008015]', $steamId);

        $steamId = SteamId::convertCommunityIdToSteamId3('76561198000009691');
        $this->assertEquals('[U:1:39743963]', $steamId);
    }

    public function testConvertSteamIdToCommunityId() {
        $steamId64 = SteamId::convertSteamIdToCommunityId('STEAM_0:0:12345');
        $this->assertEquals('76561197960290418', $steamId64);
    }

    public function testConvertUIdToCommunityId() {
        $steamId64 = SteamId::convertSteamIdToCommunityId('[U:1:12345]');
        $this->assertEquals('76561197960278073', $steamId64);

        $steamId64 = SteamId::convertSteamIdToCommunityId('[U:1:39743963]');
        $this->assertEquals('76561198000009691', $steamId64);
    }

    public function testCacheSteamId64() {
        $this->assertFalse(SteamId::isCached('76561197983311154'));

        $steamId = new SteamId('76561197983311154', false);

        $reflectionObject = new \ReflectionObject($steamId);
        $cacheMethod = $reflectionObject->getMethod('cache');
        $cacheMethod->setAccessible(true);
        $cacheMethod->invoke($steamId);

        $this->assertTrue(SteamId::isCached('76561197983311154'));
    }

    public function testCacheCustomUrl() {
        $this->assertFalse(SteamId::isCached('Son_of_Thor'));

        $steamId = new SteamId('Son_of_Thor', false);

        $reflectionObject = new \ReflectionObject($steamId);
        $cacheMethod = $reflectionObject->getMethod('cache');
        $cacheMethod->setAccessible(true);
        $cacheMethod->invoke($steamId);

        $this->assertTrue(SteamId::isCached('son_of_thor'));
    }

    public function testFetch() {
        $data = new \SimpleXMLElement(getFixture('sonofthor.xml'));
        $mockBuilder = $this->getMockBuilder('\SteamCondenser\Community\SteamId');
        $mockBuilder->setConstructorArgs(['Son_of_Thor', false]);
        $mockBuilder->setMethods(['getData']);
        $steamId = $mockBuilder->getMock();
        $steamId->expects($this->once())->method('getData')->with('http://steamcommunity.com/id/son_of_thor?xml=1')->will($this->returnValue($data));
        $steamId->fetch();

        $this->assertEquals('76561197983311154', $steamId->getSteamId64());
        $this->assertTrue($steamId->isFetched());
    }

    public function testBaseUrlSteamId64() {
        $steamId = new SteamId('76561197983311154', false);

        $this->assertEquals('76561197983311154', $steamId->getSteamId64());

        $baseUrl = (new \ReflectionObject($steamId))->getMethod('getBaseUrl');
        $baseUrl->setAccessible(true);
        $this->assertEquals('http://steamcommunity.com/profiles/76561197983311154', $baseUrl->invoke($steamId));
    }

    public function testBaseUrlCustomUrl() {
        $steamId = new SteamId('Son_of_Thor', false);

        $this->assertEquals('son_of_thor', $steamId->getCustomUrl());

        $baseUrl = (new \ReflectionObject($steamId))->getMethod('getBaseUrl');
        $baseUrl->setAccessible(true);
        $this->assertEquals('http://steamcommunity.com/id/son_of_thor', $baseUrl->invoke($steamId));
    }

    public function testResolveVanityUrl() {
        $webApi = $this->getMockBuilder('\SteamCondenser\Community\WebApi')->setMethods(['_load'])->disableOriginalConstructor()->getMock();
        $webApi->expects($this->once())->method('_load')->with('json', 'ISteamUser', 'ResolveVanityURL', 1, ['vanityurl' => 'koraktor'])->will($this->returnValue('{ "response": { "success": 1, "steamid": "76561197961384956" } }'));
        $this->webApiInstance->setValue($webApi);

        $steamId64 = SteamId::resolveVanityUrl('koraktor');
        $this->assertEquals('76561197961384956', $steamId64);
    }

    public function testResolveUnknownVanityUrl() {
        $webApi = $this->getMockBuilder('\SteamCondenser\Community\WebApi')->setMethods(['_load'])->disableOriginalConstructor()->getMock();
        $webApi->expects($this->once())->method('_load')->with('json', 'ISteamUser', 'ResolveVanityURL', 1, ['vanityurl' => 'unknown'])->will($this->returnValue('{ "response": { "success": 42 } }'));
        $this->webApiInstance->setValue($webApi);

        $this->assertNull(SteamId::resolveVanityUrl('unknown'));
    }

    public function tearDown() {
        SteamId::clearCache();
    }

}
