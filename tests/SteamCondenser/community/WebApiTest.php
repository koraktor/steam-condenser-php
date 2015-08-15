<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012-2015, Sebastian Staudt
 */

namespace SteamCondenser\Community;

class WebApiTest extends \PHPUnit_Framework_TestCase {

    private $instance;

    public function setUp() {
        WebApi::setApiKey('0123456789ABCDEF0123456789ABCDEF');
        WebApi::setSecure(true);

        $this->instance = new \ReflectionProperty('\SteamCondenser\Community\WebApi', 'instance');
        $this->instance->setAccessible(true);
    }

    public function testGetApiKey() {
        $this->assertEquals('0123456789ABCDEF0123456789ABCDEF', WebApi::getApiKey());
    }

    public function testSetApiKey() {
        WebApi::setApiKey('FEDCBA9876543210FEDCBA9876543210');

        $this->assertEquals('FEDCBA9876543210FEDCBA9876543210', WebApi::getApiKey());
    }

    public function testInvalidApiKey() {
        $this->setExpectedException('\SteamCondenser\Exceptions\WebApiException', 'This is not a valid Steam Web API key.');
        WebApi::setApiKey('test');
    }

    public function testGetJSON() {
        $webApi = $this->getMockBuilder('\SteamCondenser\Community\WebApi')->setMethods(['_load'])->disableOriginalConstructor()->getMock();
        $webApi->expects($this->once())->method('_load')->with('json', 'interface', 'method', 2, ['test' => 'param']);
        $this->instance->setValue($webApi);

        WebApi::getJSON('interface', 'method', 2, ['test' => 'param']);
    }

    public function testGetJSONData() {
        $data = '{ "result": { "status": 1 } }';
        $webApi = $this->getMockBuilder('\SteamCondenser\Community\WebApi')->setMethods(['_load'])->disableOriginalConstructor()->getMock();
        $webApi->expects($this->once())->method('_load')->with('json', 'interface', 'method', 2, ['test' => 'param'])->will($this->returnValue($data));
        $this->instance->setValue($webApi);

        $result = WebApi::getJSONData('interface', 'method', 2, ['test' => 'param']);
        $this->assertEquals(1, $result->status);
    }

    public function testGetJSONDataError() {
        $data = '{ "result": { "status": 2, "statusDetail": "error" } }';
        $webApi = $this->getMockBuilder('\SteamCondenser\Community\WebApi')->setMethods(['_load'])->disableOriginalConstructor()->getMock();
        $webApi->expects($this->once())->method('_load')->with('json', 'interface', 'method', 2, ['test' => 'param'])->will($this->returnValue($data));
        $this->instance->setValue($webApi);

        $this->setExpectedException('\SteamCondenser\Exceptions\WebApiException', 'The Web API request failed with the following error: error (status code: 2).');

        WebApi::getJSONData('interface', 'method', 2, ['test' => 'param']);
    }

    public function testLoad() {
        $data = 'data';
        $webApi = $this->getMockBuilder('\SteamCondenser\Community\WebApi')->setMethods(['request'])->disableOriginalConstructor()->getMock();
        $webApi->expects($this->once())->method('request')->with('https://api.steampowered.com/interface/method/v2/?test=param&format=json&key=0123456789ABCDEF0123456789ABCDEF')->will($this->returnValue($data));
        $this->instance->setValue($webApi);

        $this->assertEquals('data', WebApi::load('json', 'interface', 'method', 2, ['test' => 'param']));
    }

    public function testLoadInsecure() {
        WebApi::setSecure(false);

        $data = 'data';
        $webApi = $this->getMockBuilder('\SteamCondenser\Community\WebApi')->setMethods(['request'])->disableOriginalConstructor()->getMock();
        $webApi->expects($this->once())->method('request')->with('http://api.steampowered.com/interface/method/v2/?test=param&format=json&key=0123456789ABCDEF0123456789ABCDEF')->will($this->returnValue($data));
        $this->instance->setValue($webApi);

        $this->assertEquals('data', WebApi::load('json', 'interface', 'method', 2, ['test' => 'param']));
    }

    public function testLoadWithoutKey() {
        WebApi::setApiKey(null);

        $data = 'data';
        $webApi = $this->getMockBuilder('\SteamCondenser\Community\WebApi')->setMethods(['request'])->disableOriginalConstructor()->getMock();
        $webApi->expects($this->once())->method('request')->with('https://api.steampowered.com/interface/method/v2/?test=param&format=json')->will($this->returnValue($data));
        $this->instance->setValue($webApi);

        $this->assertEquals('data', WebApi::load('json', 'interface', 'method', 2, ['test' => 'param']));
    }

}
