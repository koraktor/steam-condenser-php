<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2012, Sebastian Staudt
 */

require_once dirname(__FILE__) . '/../../../lib/steam-condenser.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/WebApi.php';

class WebApiTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        WebApi::setApiKey('0123456789ABCDEF0123456789ABCDEF');
    }

    public function testGetApiKey() {
        $this->assertEquals('0123456789ABCDEF0123456789ABCDEF', WebApi::getApiKey());
    }

    public function testSetApiKey() {
        WebApi::setApiKey('FEDCBA9876543210FEDCBA9876543210');

        $this->assertEquals('FEDCBA9876543210FEDCBA9876543210', WebApi::getApiKey());
    }

    public function testInvalidApiKey() {
        $this->setExpectedException('WebApiException', 'This is not a valid Steam Web API key.');
        WebApi::setApiKey('test');
    }

    public function testGetJSON() {
        $webApi = $this->getMockClass('WebApi', array('load'));
        $webApi::staticExpects($this->once())->method('load')->with('json', 'interface', 'method', 2, array('test' => 'param'));

        $webApi::getJSON('interface', 'method', 2, array('test' => 'param'));
    }

    public function testGetJSONData() {
        $data = '{ "result": { "status": 1 } }';
        $webApi = $this->getMockClass('WebApi', array('getJSON'));
        $webApi::staticExpects($this->once())->method('getJSON')->with('interface', 'method', 2, array('test' => 'param'))->will($this->returnValue($data));

        $result = $webApi::getJSONData('interface', 'method', 2, array('test' => 'param'));
        $this->assertEquals(1, $result->status);
    }

    public function testGetJSONDataError() {
        $data = '{ "result": { "status": 2, "statusDetail": "error" } }';
        $webApi = $this->getMockClass('WebApi', array('getJSON'));
        $webApi::staticExpects($this->once())->method('getJSON')->with('interface', 'method', 2, array('test' => 'param'))->will($this->returnValue($data));

        $this->setExpectedException('WebApiException', 'The Web API request failed with the following error: error (status code: 2).');

        $webApi::getJSONData('interface', 'method', 2, array('test' => 'param'));
    }


    public function testLoad() {
        $data = 'data';
        $webApi = $this->getMockClass('WebApi', array('request'));
        $webApi::staticExpects($this->once())->method('request')->with('http://api.steampowered.com/interface/method/v0002/?test=param&format=json&key=0123456789ABCDEF0123456789ABCDEF')->will($this->returnValue($data));

        $this->assertEquals('data', $webApi::load('json', 'interface', 'method', 2, array('test' => 'param')));
    }

}
