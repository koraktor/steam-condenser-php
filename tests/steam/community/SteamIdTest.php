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

/**
 * @author     Sebastian Staudt
 * @covers     SteamId
 * @package    steam-condenser
 * @subpackage tests
 */
class SteamIdTest extends PHPUnit_Framework_TestCase {

    public function testConvertCommunityIdToSteamId() {
        $steamId = SteamId::convertCommunityIdToSteamId('76561197960290418');
        $this->assertEquals('STEAM_0:0:12345', $steamId);
    }

    public function testConvertSteamIdToCommunityId() {
        $steamId64 = SteamId::convertSteamIdToCommunityId('STEAM_0:0:12345');
        $this->assertEquals('76561197960290418', $steamId64);
    }

    public function testCacheSteamId64() {
        $this->assertFalse(SteamId::isCached('76561197983311154'));

        $steamId = new SteamId('76561197983311154', false);
        $steamId->cache();

        $this->assertTrue(SteamId::isCached('76561197983311154'));
    }

    public function testCacheCustomUrl() {
        $this->assertFalse(SteamId::isCached('Son_of_Thor'));

        $steamId = new SteamId('Son_of_Thor', false);
        $steamId->cache();

        $this->assertTrue(SteamId::isCached('son_of_thor'));
    }

  public function testFetch() {
      $data = new SimpleXMLElement(getFixture('sonofthor.xml'));
      $mockBuilder = $this->getMockBuilder('SteamId');
      $mockBuilder->setConstructorArgs(array('Son_of_Thor', false));
      $mockBuilder->setMethods(array('getData'));
      $steamId = $mockBuilder->getMock();
      $steamId->expects($this->once())->method('getData')->with('http://steamcommunity.com/id/son_of_thor?xml=1')->will($this->returnValue($data));
      $steamId->fetchData();

      $this->assertEquals('76561197983311154', $steamId->getSteamId64());
      $this->assertTrue($steamId->isFetched());
  }

    public function testBaseUrlSteamId64() {
        $steamId = new SteamId('76561197983311154', false);

        $this->assertEquals('76561197983311154', $steamId->getSteamId64());
        $this->assertEquals('http://steamcommunity.com/profiles/76561197983311154', $steamId->getBaseUrl());
    }

    public function testBaseUrlCustomUrl() {
        $steamId = new SteamId('Son_of_Thor', false);

        $this->assertEquals('son_of_thor', $steamId->getCustomUrl());
        $this->assertEquals('http://steamcommunity.com/id/son_of_thor', $steamId->getBaseUrl());
    }

    public function tearDown() {
        SteamId::clearCache();
    }

}
